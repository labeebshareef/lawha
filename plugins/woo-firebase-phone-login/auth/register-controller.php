<?php
/**
 * Register Controller — WordPress user registration via verified phone.
 *
 * Flow:
 *   1. Receive Firebase ID token + registration data
 *   2. Verify token via OTP module (extract phone)
 *   3. Ensure phone is not already registered
 *   4. Create WordPress user via wp_create_user
 *   5. Store billing_phone + phone_verified meta
 *   6. Log the user in via wp_set_auth_cookie
 *
 * @package PhoneAuth\Auth
 */

namespace PhoneAuth\Auth;

defined( 'ABSPATH' ) || exit;

class Register_Controller {

    /**
     * Process a registration request with a Firebase ID token.
     *
     * @param string $firebase_token Firebase ID token (JWT).
     * @param array  $user_data      Optional user data: name, email.
     * @return array|\WP_Error
     */
    public static function register( $firebase_token, $user_data = array() ) {
        // 1. Verify the Firebase token and extract phone.
        $verification = \PhoneAuth\OTP\Verify_OTP::verify( $firebase_token );
        if ( is_wp_error( $verification ) ) {
            return $verification;
        }

        $phone = $verification['phone'];

        // 2. Validate phone format.
        if ( ! \PhoneAuth\Database\Phone_Lookup::is_valid_e164( $phone ) ) {
            return new \WP_Error(
                'phone_auth_invalid_phone',
                __( 'Invalid phone number format.', 'woo-firebase-phone-login' ),
                array( 'status' => 400 )
            );
        }

        // 3. Check if phone is already registered — login instead of duplicating.
        $existing_user = \PhoneAuth\Database\Phone_Lookup::find_user_by_phone( $phone );
        if ( $existing_user ) {
            $login = Login_Controller::wp_login( $existing_user->ID );
            if ( is_wp_error( $login ) ) {
                return $login;
            }

            update_user_meta( $existing_user->ID, 'phone_verified', 1 );

            return array(
                'success' => true,
                'user_id' => $existing_user->ID,
                'phone'   => $phone,
                'created' => false,
            );
        }

        // 4. Create WordPress user.
        $user_id = self::create_wp_user( $phone, $user_data );
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        // 5. Store phone metadata (single source: billing_phone).
        update_user_meta( $user_id, 'billing_phone', $phone );
        update_user_meta( $user_id, 'phone_verified', 1 );

        do_action( 'phone_auth_user_registered', $user_id, $phone );

        // 6. Log the user in.
        $login = Login_Controller::wp_login( $user_id );
        if ( is_wp_error( $login ) ) {
            return $login;
        }

        return array(
            'success' => true,
            'user_id' => $user_id,
            'phone'   => $phone,
            'created' => true,
        );
    }

    /**
     * Create a WordPress user from phone + optional data.
     *
     * @param string $phone     E.164 phone number.
     * @param array  $user_data { name?: string, email?: string, password?: string }
     * @return int|\WP_Error User ID on success.
     */
    private static function create_wp_user( $phone, $user_data = array() ) {
        global $wpdb;

        // Advisory lock to prevent race-condition duplicates.
        $lock_key = 'pa_create_' . md5( $phone );
        $lock     = $wpdb->get_var( $wpdb->prepare( "SELECT GET_LOCK(%s, 5)", $lock_key ) );

        if ( ! $lock ) {
            return new \WP_Error(
                'phone_auth_lock_failed',
                __( 'Could not acquire lock. Please try again.', 'woo-firebase-phone-login' )
            );
        }

        try {
            // Re-check inside lock.
            if ( \PhoneAuth\Database\Phone_Lookup::find_user_by_phone( $phone ) ) {
                return new \WP_Error(
                    'phone_auth_phone_exists',
                    __( 'An account with this phone number already exists.', 'woo-firebase-phone-login' )
                );
            }

            $name     = isset( $user_data['name'] ) ? sanitize_text_field( $user_data['name'] ) : '';
            $email    = isset( $user_data['email'] ) ? sanitize_email( $user_data['email'] ) : '';
            $password = isset( $user_data['password'] ) ? $user_data['password'] : wp_generate_password( 24, true, true );

            // Generate unique username.
            $suffix   = substr( md5( $phone . wp_generate_password( 8, false ) ), 0, 6 );
            $username = 'user_' . $suffix;

            // Generate placeholder email if not provided.
            if ( empty( $email ) || ! is_email( $email ) ) {
                $email = $suffix . '@noreply.' . wp_parse_url( home_url(), PHP_URL_HOST );
            }

            $user_id = wp_insert_user( array(
                'user_login' => $username,
                'user_email' => $email,
                'user_pass'  => $password,
                'role'       => 'customer',
            ) );

            if ( is_wp_error( $user_id ) ) {
                return $user_id;
            }

            // Set name if provided.
            if ( $name ) {
                $parts = explode( ' ', $name, 2 );
                wp_update_user( array(
                    'ID'           => $user_id,
                    'first_name'   => $parts[0],
                    'last_name'    => isset( $parts[1] ) ? $parts[1] : '',
                    'display_name' => $name,
                ) );
                update_user_meta( $user_id, 'billing_first_name', $parts[0] );
                update_user_meta( $user_id, 'billing_last_name', isset( $parts[1] ) ? $parts[1] : '' );
            }

            return $user_id;

        } finally {
            $wpdb->query( $wpdb->prepare( "SELECT RELEASE_LOCK(%s)", $lock_key ) );
        }
    }
}

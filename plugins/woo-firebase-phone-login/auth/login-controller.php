<?php
/**
 * Login Controller — WordPress-only login via verified phone number.
 *
 * Flow:
 *   1. Receive Firebase ID token
 *   2. Verify token via OTP module (extract phone)
 *   3. Look up user by billing_phone
 *   4. If found → wp_set_auth_cookie → login
 *   5. If not found → return "not registered" (never auto-create)
 *
 * @package PhoneAuth\Auth
 */

namespace PhoneAuth\Auth;

defined( 'ABSPATH' ) || exit;

class Login_Controller {

    const MAX_LOGIN_ATTEMPTS  = 5;
    const LOGIN_LOCKOUT_WINDOW = 900; // 15 minutes

    private static function is_login_locked( $phone ) {
        $key   = 'pa_login_' . md5( $phone );
        $count = absint( get_transient( $key ) );
        return $count >= self::MAX_LOGIN_ATTEMPTS;
    }

    private static function record_login_attempt( $phone ) {
        $key   = 'pa_login_' . md5( $phone );
        $count = absint( get_transient( $key ) );
        set_transient( $key, $count + 1, self::LOGIN_LOCKOUT_WINDOW );
    }

    private static function clear_login_attempts( $phone ) {
        delete_transient( 'pa_login_' . md5( $phone ) );
    }

    /**
     * Process a login request with a Firebase ID token.
     *
     * @param string $firebase_token Firebase ID token (JWT).
     * @return array|\WP_Error
     */
    public static function login( $firebase_token ) {
        // 1. Verify the Firebase token and extract phone.
        $verification = \PhoneAuth\OTP\Verify_OTP::verify( $firebase_token );
        if ( is_wp_error( $verification ) ) {
            return $verification;
        }

        $phone = $verification['phone'];

        // 2. Check login lockout.
        if ( self::is_login_locked( $phone ) ) {
            return new \WP_Error(
                'phone_auth_login_locked',
                __( 'Too many login attempts. Please try again in 15 minutes.', 'woo-firebase-phone-login' ),
                array( 'status' => 429 )
            );
        }

        // 3. Rate limit check.
        if ( \PhoneAuth\OTP\Send_OTP::is_rate_limited( $phone ) ) {
            return new \WP_Error(
                'phone_auth_rate_limited',
                __( 'Too many attempts. Please try again later.', 'woo-firebase-phone-login' ),
                array( 'status' => 429 )
            );
        }

        // 3. Look up user by billing_phone (single source of truth).
        $user = \PhoneAuth\Database\Phone_Lookup::find_user_by_phone( $phone );

        if ( ! $user ) {
            self::record_login_attempt( $phone );
            // User does not exist — DO NOT auto-create. Return redirect instruction.
            return new \WP_Error(
                'phone_auth_not_registered',
                __( 'No account found with this phone number. Please register first.', 'woo-firebase-phone-login' ),
                array(
                    'status'      => 404,
                    'phone'       => $phone,
                    'redirect_to' => 'register',
                )
            );
        }

        // 4. Log the user in via WordPress.
        $login_result = self::wp_login( $user->ID );
        if ( is_wp_error( $login_result ) ) {
            return $login_result;
        }

        // 5. Ensure phone_verified meta is set.
        update_user_meta( $user->ID, 'phone_verified', 1 );

        self::clear_login_attempts( $phone );

        return array(
            'success' => true,
            'user_id' => $user->ID,
            'phone'   => $phone,
            'created' => false,
        );
    }

    /**
     * Programmatically log a WordPress user in.
     *
     * @param int $user_id WordPress user ID.
     * @return true|\WP_Error
     */
    public static function wp_login( $user_id ) {
        $user = get_user_by( 'ID', $user_id );

        if ( ! $user ) {
            return new \WP_Error(
                'phone_auth_user_not_found',
                __( 'User not found.', 'woo-firebase-phone-login' )
            );
        }

        wp_clear_auth_cookie();
        wp_set_current_user( $user_id, $user->user_login );
        wp_set_auth_cookie( $user_id, true );

        do_action( 'wp_login', $user->user_login, $user );
        do_action( 'phone_auth_after_login', $user_id, get_user_meta( $user_id, 'billing_phone', true ) );

        return true;
    }
}

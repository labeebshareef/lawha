<?php
/**
 * User handler — find, create, and login WordPress users by phone.
 *
 * @package WFPL
 */

namespace WFPL;

defined( 'ABSPATH' ) || exit;

/**
 * Class User_Handler
 */
class User_Handler {

    /**
     * User meta key for the verified phone number.
     */
    const META_KEY = 'wfpl_phone';

    /*--------------------------------------------------------------
     * Lookup
     *------------------------------------------------------------*/

    /**
     * Find a WordPress user by phone number.
     *
     * @param string $phone E.164 phone number.
     * @return \WP_User|false
     */
    public static function get_user_by_phone( $phone ) {
        $phone = Helpers::sanitize_phone( $phone );

        $users = get_users( array(
            'meta_key'   => self::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_value' => $phone,         // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            'number'     => 1,
        ) );

        return ! empty( $users ) ? $users[0] : false;
    }

    /**
     * Check if a phone number is already registered.
     *
     * @param string $phone E.164 phone number.
     * @return bool
     */
    public static function is_phone_registered( $phone ) {
        return (bool) self::get_user_by_phone( $phone );
    }

    /*--------------------------------------------------------------
     * Account creation
     *------------------------------------------------------------*/

    /**
     * Create a new WordPress / WooCommerce user from a phone number.
     *
     * @param string $phone E.164 phone number.
     * @return int|\WP_Error User ID on success.
     */
    public static function create_user( $phone ) {
        $phone = Helpers::sanitize_phone( $phone );

        if ( ! Helpers::is_valid_e164( $phone ) ) {
            return new \WP_Error( 'wfpl_invalid_phone', __( 'Invalid phone number format.', 'woo-firebase-phone-login' ) );
        }

        // Prevent duplicates.
        if ( self::is_phone_registered( $phone ) ) {
            return new \WP_Error( 'wfpl_phone_exists', __( 'An account with this phone number already exists.', 'woo-firebase-phone-login' ) );
        }

        // Generate unique username and email placeholder.
        $suffix   = substr( md5( $phone . wp_generate_password( 8, false ) ), 0, 6 );
        $username = 'phone_' . $suffix;
        $email    = $username . '@phone.local'; // placeholder; user can update later.
        $password = wp_generate_password( 24, true, true );

        /**
         * Action fired before user creation.
         *
         * @param string $phone E.164 phone number.
         */
        do_action( 'wfpl_before_create_user', $phone );

        $user_id = wp_insert_user( array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass'  => $password,
            'role'       => 'customer',
        ) );

        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        // Store phone.
        update_user_meta( $user_id, self::META_KEY, $phone );

        // Store WooCommerce billing phone.
        update_user_meta( $user_id, 'billing_phone', $phone );

        /**
         * Action fired after user creation.
         *
         * @param int    $user_id WordPress user ID.
         * @param string $phone   E.164 phone number.
         */
        do_action( 'wfpl_user_created', $user_id, $phone );

        return $user_id;
    }

    /*--------------------------------------------------------------
     * Login
     *------------------------------------------------------------*/

    /**
     * Programmatically log a user in.
     *
     * @param int $user_id WordPress user ID.
     * @return true|\WP_Error
     */
    public static function login_user( $user_id ) {
        $user = get_user_by( 'ID', $user_id );

        if ( ! $user ) {
            return new \WP_Error( 'wfpl_user_not_found', __( 'User not found.', 'woo-firebase-phone-login' ) );
        }

        /**
         * Action fired before login.
         *
         * @param string $phone Phone number.
         */
        $phone = get_user_meta( $user_id, self::META_KEY, true );
        do_action( 'wfpl_before_login', $phone );

        wp_clear_auth_cookie();
        wp_set_current_user( $user_id, $user->user_login );
        wp_set_auth_cookie( $user_id, true );

        /**
         * Standard WordPress login action.
         *
         * @param string   $user_login User login slug.
         * @param \WP_User $user       User object.
         */
        do_action( 'wp_login', $user->user_login, $user );

        /**
         * Action fired after successful login.
         *
         * @param int    $user_id User ID.
         * @param string $phone   Phone number.
         */
        do_action( 'wfpl_after_login', $user_id, $phone );

        return true;
    }

    /*--------------------------------------------------------------
     * Login-or-create orchestration
     *------------------------------------------------------------*/

    /**
     * Find an existing user or create a new one, then log them in.
     *
     * @param string $phone E.164 phone number.
     * @return array|\WP_Error  { user_id: int, created: bool }
     */
    public static function login_or_create( $phone ) {
        $phone   = Helpers::sanitize_phone( $phone );
        $created = false;
        $user    = self::get_user_by_phone( $phone );

        if ( ! $user ) {
            if ( ! Helpers::is_feature_enabled( 'auto_create_account' ) && ! Helpers::is_feature_enabled( 'enable_registration' ) ) {
                return new \WP_Error( 'wfpl_registration_disabled', __( 'Registration is disabled.', 'woo-firebase-phone-login' ) );
            }

            $user_id = self::create_user( $phone );
            if ( is_wp_error( $user_id ) ) {
                return $user_id;
            }

            $created = true;
        } else {
            $user_id = $user->ID;
        }

        $login = self::login_user( $user_id );
        if ( is_wp_error( $login ) ) {
            return $login;
        }

        return array(
            'user_id' => $user_id,
            'created' => $created,
        );
    }
}

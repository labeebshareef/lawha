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
        global $wpdb;

        $phone = Helpers::sanitize_phone( $phone );

        if ( ! Helpers::is_valid_e164( $phone ) ) {
            return new \WP_Error( 'wfpl_invalid_phone', __( 'Invalid phone number format.', 'woo-firebase-phone-login' ) );
        }

        // Acquire advisory lock to prevent race-condition duplicates.
        $lock_key = 'wfpl_create_' . md5( $phone );
        $lock     = $wpdb->get_var( $wpdb->prepare( "SELECT GET_LOCK(%s, 5)", $lock_key ) );

        if ( ! $lock ) {
            return new \WP_Error( 'wfpl_lock_failed', __( 'Could not acquire lock. Please try again.', 'woo-firebase-phone-login' ) );
        }

        try {
            // Re-check inside lock — another request may have created the user.
            if ( self::is_phone_registered( $phone ) ) {
                return new \WP_Error( 'wfpl_phone_exists', __( 'An account with this phone number already exists.', 'woo-firebase-phone-login' ) );
            }

            // Generate unique username and email placeholder.
            $suffix   = substr( md5( $phone . wp_generate_password( 8, false ) ), 0, 6 );
            $username = 'phone_' . $suffix;
            $email    = $suffix . '@noreply.' . wp_parse_url( home_url(), PHP_URL_HOST );
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

            // Store phone metadata.
            update_user_meta( $user_id, self::META_KEY, $phone );
            update_user_meta( $user_id, 'billing_phone', $phone );
            update_user_meta( $user_id, 'wfpl_phone_verified', 1 );

            /**
             * Action fired after user creation.
             *
             * @param int    $user_id WordPress user ID.
             * @param string $phone   E.164 phone number.
             */
            do_action( 'wfpl_user_created', $user_id, $phone );

            return $user_id;

        } finally {
            $wpdb->query( $wpdb->prepare( "SELECT RELEASE_LOCK(%s)", $lock_key ) );
        }
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

    /*--------------------------------------------------------------
     * Phone ↔ WooCommerce sync
     *------------------------------------------------------------*/

    /**
     * Register hooks to keep wfpl_phone and billing_phone in sync.
     * Call this from the plugin bootstrap.
     */
    public static function register_sync_hooks() {
        // When WooCommerce saves an address, re-sync billing_phone → wfpl_phone.
        add_action( 'woocommerce_customer_save_address', array( __CLASS__, 'sync_phone_on_address_save' ), 10, 2 );

        // Pre-fill billing_phone from wfpl_phone for logged-in users at checkout.
        add_filter( 'woocommerce_checkout_get_value', array( __CLASS__, 'prefill_billing_phone' ), 10, 2 );
    }

    /**
     * When a customer edits their address, normalize and sync the phone.
     *
     * @param int    $user_id      User ID.
     * @param string $address_type 'billing' or 'shipping'.
     */
    public static function sync_phone_on_address_save( $user_id, $address_type ) {
        if ( 'billing' !== $address_type ) {
            return;
        }

        $billing_phone = get_user_meta( $user_id, 'billing_phone', true );
        if ( empty( $billing_phone ) ) {
            return;
        }

        $normalized = Helpers::sanitize_phone( $billing_phone );

        // Only update wfpl_phone if it's valid E.164 and differs.
        if ( Helpers::is_valid_e164( $normalized ) ) {
            $current_wfpl = get_user_meta( $user_id, self::META_KEY, true );
            if ( $normalized !== $current_wfpl ) {
                // Ensure no other user owns this phone.
                $existing = self::get_user_by_phone( $normalized );
                if ( ! $existing || $existing->ID === $user_id ) {
                    update_user_meta( $user_id, self::META_KEY, $normalized );
                }
            }
            // Always normalize the billing_phone back to E.164.
            update_user_meta( $user_id, 'billing_phone', $normalized );
        }
    }

    /**
     * Pre-fill billing phone at checkout from wfpl_phone.
     *
     * @param mixed  $value Current field value.
     * @param string $input Field key.
     * @return mixed
     */
    public static function prefill_billing_phone( $value, $input ) {
        if ( 'billing_phone' !== $input ) {
            return $value;
        }

        if ( ! is_user_logged_in() ) {
            return $value;
        }

        $wfpl_phone = get_user_meta( get_current_user_id(), self::META_KEY, true );
        return ! empty( $wfpl_phone ) ? $wfpl_phone : $value;
    }
}

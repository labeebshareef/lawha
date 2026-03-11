<?php
/**
 * AJAX Endpoints — Handles all phone auth AJAX requests.
 *
 * Endpoints:
 *   phone_auth_login           — Verify token + login existing user
 *   phone_auth_register        — Verify token + create new user
 *   phone_auth_check_phone     — Check if phone is registered
 *   phone_auth_send_otp        — Rate limit + return Firebase config
 *   phone_auth_store_otp       — Store OTP verification in session (for registration form)
 *   phone_auth_forgot_check    — Check if phone has an account (forgot password)
 *   phone_auth_forgot_reset    — Reset password after OTP verification
 *
 * @package PhoneAuth\API
 */

namespace PhoneAuth\API;

defined( 'ABSPATH' ) || exit;

class Ajax_Endpoints {

    /**
     * Register all AJAX hooks.
     */
    public static function init() {
        $endpoints = array(
            'phone_auth_login',
            'phone_auth_register',
            'phone_auth_check_phone',
            'phone_auth_send_otp',
            'phone_auth_store_otp',
            'phone_auth_forgot_check',
            'phone_auth_forgot_reset',
        );

        foreach ( $endpoints as $action ) {
            add_action( 'wp_ajax_' . $action, array( __CLASS__, 'handle_' . str_replace( 'phone_auth_', '', $action ) ) );
            add_action( 'wp_ajax_nopriv_' . $action, array( __CLASS__, 'handle_' . str_replace( 'phone_auth_', '', $action ) ) );
        }
    }

    /**
     * Verify nonce from request. Dies on failure.
     */
    private static function verify_request_nonce() {
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
        if ( ! \PhoneAuth\Database\Phone_Lookup::verify_nonce( $nonce ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed. Please refresh the page.', 'woo-firebase-phone-login' ),
            ), 403 );
        }
    }

    /**
     * AJAX: Login with Firebase token.
     * Does NOT auto-create accounts. Returns error if user not found.
     */
    public static function handle_login() {
        self::verify_request_nonce();

        $token = isset( $_POST['firebase_token'] ) ? sanitize_text_field( wp_unslash( $_POST['firebase_token'] ) ) : '';
        if ( empty( $token ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing authentication token.', 'woo-firebase-phone-login' ) ), 400 );
        }

        $result = \PhoneAuth\Auth\Login_Controller::login( $token );

        if ( is_wp_error( $result ) ) {
            $status = 400;
            $data   = $result->get_error_data();
            if ( is_array( $data ) && isset( $data['status'] ) ) {
                $status = $data['status'];
            }
            wp_send_json_error( array(
                'message'     => $result->get_error_message(),
                'code'        => $result->get_error_code(),
                'redirect_to' => isset( $data['redirect_to'] ) ? $data['redirect_to'] : '',
                'phone'       => isset( $data['phone'] ) ? $data['phone'] : '',
            ), $status );
        }

        wp_send_json_success( array(
            'message'      => __( 'Login successful!', 'woo-firebase-phone-login' ),
            'user_id'      => $result['user_id'],
            'phone'        => $result['phone'],
            'created'      => false,
            'redirect_url' => self::get_redirect_url(),
        ) );
    }

    /**
     * AJAX: Register with Firebase token.
     */
    public static function handle_register() {
        self::verify_request_nonce();

        $token = isset( $_POST['firebase_token'] ) ? sanitize_text_field( wp_unslash( $_POST['firebase_token'] ) ) : '';
        if ( empty( $token ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing authentication token.', 'woo-firebase-phone-login' ) ), 400 );
        }

        $user_data = array(
            'name'  => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
            'email' => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
        );

        $result = \PhoneAuth\Auth\Register_Controller::register( $token, $user_data );

        if ( is_wp_error( $result ) ) {
            $status = 400;
            $data   = $result->get_error_data();
            if ( is_array( $data ) && isset( $data['status'] ) ) {
                $status = $data['status'];
            }
            wp_send_json_error( array(
                'message' => $result->get_error_message(),
                'code'    => $result->get_error_code(),
            ), $status );
        }

        wp_send_json_success( array(
            'message'      => __( 'Account created successfully!', 'woo-firebase-phone-login' ),
            'user_id'      => $result['user_id'],
            'phone'        => $result['phone'],
            'created'      => true,
            'redirect_url' => self::get_redirect_url(),
        ) );
    }

    /**
     * AJAX: Check if a phone number is registered.
     */
    public static function handle_check_phone() {
        self::verify_request_nonce();

        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $phone = \PhoneAuth\Database\Phone_Lookup::normalize_phone( $phone );

        if ( empty( $phone ) || ! \PhoneAuth\Database\Phone_Lookup::is_valid_e164( $phone ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid phone number.', 'woo-firebase-phone-login' ) ), 400 );
        }

        wp_send_json_success( array(
            'registered' => \PhoneAuth\Database\Phone_Lookup::is_phone_registered( $phone ),
        ) );
    }

    /**
     * AJAX: Send OTP (rate limit check + return Firebase config).
     */
    public static function handle_send_otp() {
        self::verify_request_nonce();

        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $phone = \PhoneAuth\Database\Phone_Lookup::normalize_phone( $phone );

        $result = \PhoneAuth\OTP\Send_OTP::process( $phone );

        if ( is_wp_error( $result ) ) {
            $status = 400;
            $data   = $result->get_error_data();
            if ( is_array( $data ) && isset( $data['status'] ) ) {
                $status = $data['status'];
            }
            wp_send_json_error( array( 'message' => $result->get_error_message() ), $status );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX: Store OTP verification in WC session (for registration form).
     * Validates the Firebase token server-side to prevent OTP bypass.
     */
    public static function handle_store_otp() {
        self::verify_request_nonce();

        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $firebase_token = isset( $_POST['firebase_token'] ) ? sanitize_text_field( wp_unslash( $_POST['firebase_token'] ) ) : '';

        if ( empty( $phone ) || empty( $firebase_token ) ) {
            wp_send_json_error( array( 'message' => __( 'Phone and verification token are required.', 'woo-firebase-phone-login' ) ), 400 );
        }

        // Verify the Firebase token.
        $payload = \PhoneAuth\OTP\Verify_OTP::verify_id_token( $firebase_token );
        if ( is_wp_error( $payload ) ) {
            wp_send_json_error( array( 'message' => __( 'Phone verification failed. Please try again.', 'woo-firebase-phone-login' ) ), 400 );
        }

        $token_phone = isset( $payload['phone_number'] ) ? $payload['phone_number'] : '';
        $normalized  = \PhoneAuth\Database\Phone_Lookup::normalize_phone( $phone );

        if ( $token_phone !== $normalized ) {
            wp_send_json_error( array( 'message' => __( 'Phone number mismatch.', 'woo-firebase-phone-login' ) ), 400 );
        }

        // Store in WooCommerce session.
        if ( function_exists( 'WC' ) && WC()->session ) {
            if ( method_exists( WC()->session, 'has_session' ) && ! WC()->session->has_session() ) {
                WC()->session->set_customer_session_cookie( true );
            }
            WC()->session->set( 'phone_auth_verified_phone', $normalized );
            WC()->session->set( 'phone_auth_verified_at', time() );
        }

        wp_send_json_success( array( 'phone' => $normalized ) );
    }

    /**
     * AJAX: Check if a phone has an account (forgot password flow).
     */
    public static function handle_forgot_check() {
        self::verify_request_nonce();

        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $phone = \PhoneAuth\Database\Phone_Lookup::normalize_phone( $phone );

        if ( empty( $phone ) || ! \PhoneAuth\Database\Phone_Lookup::is_valid_e164( $phone ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid phone number.', 'woo-firebase-phone-login' ) ), 400 );
        }

        $user = \PhoneAuth\Database\Phone_Lookup::find_user_by_phone( $phone );
        if ( ! $user ) {
            wp_send_json_error( array( 'message' => __( 'No account found with this phone number.', 'woo-firebase-phone-login' ) ) );
        }

        wp_send_json_success( array( 'found' => true ) );
    }

    /**
     * AJAX: Reset password after OTP verification.
     */
    public static function handle_forgot_reset() {
        self::verify_request_nonce();

        $phone        = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $new_password = isset( $_POST['new_password'] ) ? $_POST['new_password'] : '';
        $id_token     = isset( $_POST['id_token'] ) ? sanitize_text_field( wp_unslash( $_POST['id_token'] ) ) : '';

        if ( empty( $phone ) || empty( $new_password ) ) {
            wp_send_json_error( array( 'message' => __( 'Phone and new password are required.', 'woo-firebase-phone-login' ) ), 400 );
        }

        if ( strlen( $new_password ) < 8 ) {
            wp_send_json_error( array( 'message' => __( 'Password must be at least 8 characters.', 'woo-firebase-phone-login' ) ), 400 );
        }

        // Verify Firebase token to confirm OTP was completed.
        if ( empty( $id_token ) ) {
            wp_send_json_error( array( 'message' => __( 'Verification token is required.', 'woo-firebase-phone-login' ) ), 400 );
        }

        $payload = \PhoneAuth\OTP\Verify_OTP::verify_id_token( $id_token );
        if ( is_wp_error( $payload ) ) {
            wp_send_json_error( array( 'message' => __( 'OTP verification failed. Please try again.', 'woo-firebase-phone-login' ) ), 400 );
        }

        $normalized  = \PhoneAuth\Database\Phone_Lookup::normalize_phone( $phone );
        $token_phone = isset( $payload['phone_number'] ) ? $payload['phone_number'] : '';
        if ( $token_phone !== $normalized ) {
            wp_send_json_error( array( 'message' => __( 'Phone number mismatch.', 'woo-firebase-phone-login' ) ), 400 );
        }

        $user = \PhoneAuth\Database\Phone_Lookup::find_user_by_phone( $normalized );

        if ( ! $user ) {
            wp_send_json_error( array( 'message' => __( 'No account found with this phone number.', 'woo-firebase-phone-login' ) ) );
        }

        wp_set_password( $new_password, $user->ID );

        wp_send_json_success( array( 'message' => __( 'Password reset successfully.', 'woo-firebase-phone-login' ) ) );
    }

    /**
     * Get the redirect URL after successful login.
     *
     * @return string
     */
    private static function get_redirect_url() {
        $redirect = isset( $_POST['redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_url'] ) ) : '';

        if ( empty( $redirect ) && function_exists( 'wc_get_page_permalink' ) ) {
            $redirect = wc_get_page_permalink( 'myaccount' );
        }

        return apply_filters( 'phone_auth_login_redirect', $redirect );
    }
}

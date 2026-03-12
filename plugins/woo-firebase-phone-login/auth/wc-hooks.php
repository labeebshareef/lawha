<?php
/**
 * WooCommerce authentication hooks.
 *
 * Moved from the theme (lawha-theme/functions.php) so that all
 * authentication logic lives inside the plugin.
 *
 * Covers:
 *  - Registration validation (phone + OTP)
 *  - Post-registration data save
 *  - Phone-as-username login
 *  - Checkout force-login
 *  - Email verification (send, handle link, resend AJAX)
 *
 * @package PhoneAuth\Auth
 */

namespace PhoneAuth\Auth;

defined( 'ABSPATH' ) || exit;

class WC_Hooks {

    /**
     * Register all hooks. Called once from the plugin bootstrap.
     */
    public static function init() {
        // Registration.
        add_action( 'woocommerce_register_post', array( __CLASS__, 'validate_registration' ), 10, 3 );
        add_action( 'woocommerce_created_customer', array( __CLASS__, 'save_registration_data' ), 10, 1 );

        // Login.
        add_filter( 'authenticate', array( __CLASS__, 'authenticate_by_phone' ), 20, 3 );

        // Checkout.
        add_action( 'template_redirect', array( __CLASS__, 'checkout_force_login' ) );

        // Email verification.
        add_action( 'init', array( __CLASS__, 'handle_email_verification' ) );
        add_action( 'wp_ajax_lawha_resend_verification_email', array( __CLASS__, 'ajax_resend_verification_email' ) );
    }

    /* =========================================================
       REGISTRATION VALIDATION
       ========================================================= */

    /**
     * Validate phone number and OTP during WooCommerce registration.
     */
    public static function validate_registration( $username, $email, $errors ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC handles nonce
        $name  = isset( $_POST['lawha_reg_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lawha_reg_name'] ) ) : '';
        $phone = isset( $_POST['lawha_reg_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['lawha_reg_phone'] ) ) : '';
        $pass1 = isset( $_POST['password'] ) ? $_POST['password'] : '';
        $pass2 = isset( $_POST['lawha_reg_password_confirm'] ) ? $_POST['lawha_reg_password_confirm'] : '';
        $otp_flag = isset( $_POST['lawha_phone_verified'] ) ? sanitize_text_field( $_POST['lawha_phone_verified'] ) : '';

        // Full name is required.
        if ( empty( $name ) ) {
            $errors->add( 'lawha_reg_name_error', __( '<strong>Error</strong>: Full name is required.', 'woo-firebase-phone-login' ) );
        }

        // Phone is required.
        if ( empty( $phone ) ) {
            $errors->add( 'lawha_reg_phone_error', __( '<strong>Error</strong>: Phone number is required.', 'woo-firebase-phone-login' ) );
            return;
        }

        // Basic E.164-ish validation.
        $digits_only = preg_replace( '/[^\d]/', '', $phone );
        if ( strlen( $digits_only ) < 8 || strlen( $digits_only ) > 15 ) {
            $errors->add( 'lawha_reg_phone_error', __( '<strong>Error</strong>: Please enter a valid phone number with country code.', 'woo-firebase-phone-login' ) );
            return;
        }

        // Check for duplicate phone number.
        $normalized = \PhoneAuth\Database\Phone_Lookup::normalize_phone( $phone );
        $existing = get_users( array(
            'meta_key'   => 'billing_phone',
            'meta_value' => $normalized,
            'number'     => 1,
            'fields'     => 'ID',
        ) );
        if ( ! empty( $existing ) ) {
            $errors->add( 'lawha_reg_phone_error', __( '<strong>Error</strong>: An account with this phone number already exists. Please log in instead.', 'woo-firebase-phone-login' ) );
            return;
        }

        // Enforce India (+91) / UAE (+971) only.
        if ( substr( $normalized, 0, 3 ) !== '+91' && substr( $normalized, 0, 4 ) !== '+971' ) {
            $errors->add( 'lawha_reg_phone_error', __( '<strong>Error</strong>: Only Indian (+91) and UAE (+971) phone numbers are accepted.', 'woo-firebase-phone-login' ) );
            return;
        }

        // OTP verification is mandatory - check session matches the submitted phone.
        $session_phone = '';
        $otp_verified_at = 0;
        if ( \WC()->session ) {
            $session_phone   = \WC()->session->get( 'phone_auth_verified_phone', '' );
            $otp_verified_at = (int) \WC()->session->get( 'phone_auth_verified_at', 0 );
        }
        if ( '1' !== $otp_flag || $session_phone !== $normalized ) {
            $errors->add( 'lawha_reg_phone_error', __( '<strong>Error</strong>: Please verify your phone number with OTP before registering.', 'woo-firebase-phone-login' ) );
            return;
        }

        // OTP session TTL: reject if verification was more than 10 minutes ago.
        if ( $otp_verified_at > 0 && ( time() - $otp_verified_at ) > 600 ) {
            \WC()->session->set( 'phone_auth_verified_phone', '' );
            \WC()->session->set( 'phone_auth_verified_at', 0 );
            $errors->add( 'lawha_reg_phone_error', __( '<strong>Error</strong>: Phone verification has expired. Please verify again.', 'woo-firebase-phone-login' ) );
            return;
        }

        // Password strength.
        if ( strlen( $pass1 ) < 8 ) {
            $errors->add( 'lawha_reg_pass_error', __( '<strong>Error</strong>: Password must be at least 8 characters.', 'woo-firebase-phone-login' ) );
        }

        // Password confirmation.
        if ( $pass1 !== $pass2 ) {
            $errors->add( 'lawha_reg_pass_error', __( '<strong>Error</strong>: Passwords do not match.', 'woo-firebase-phone-login' ) );
        }
    }

    /* =========================================================
       SAVE REGISTRATION DATA
       ========================================================= */

    /**
     * Save user data after successful WooCommerce registration.
     */
    public static function save_registration_data( $customer_id ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC handles nonce
        $name  = isset( $_POST['lawha_reg_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lawha_reg_name'] ) ) : '';
        $phone = isset( $_POST['lawha_reg_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['lawha_reg_phone'] ) ) : '';

        if ( $name ) {
            $parts = explode( ' ', $name, 2 );
            wp_update_user( array(
                'ID'           => $customer_id,
                'first_name'   => $parts[0],
                'last_name'    => isset( $parts[1] ) ? $parts[1] : '',
                'display_name' => $name,
            ) );
            update_user_meta( $customer_id, 'billing_first_name', $parts[0] );
            update_user_meta( $customer_id, 'billing_last_name', isset( $parts[1] ) ? $parts[1] : '' );
        }

        if ( $phone ) {
            $normalized = \PhoneAuth\Database\Phone_Lookup::normalize_phone( $phone );
            update_user_meta( $customer_id, 'billing_phone', $normalized );
            update_user_meta( $customer_id, 'phone_verified', 1 );
        }

        // Clear OTP session data.
        if ( \WC()->session ) {
            \WC()->session->set( 'phone_auth_verified_phone', '' );
            \WC()->session->set( 'phone_auth_verified_at', 0 );
        }

        // Send email verification.
        self::send_verification_email( $customer_id );
    }

    /* =========================================================
       PHONE-AS-USERNAME LOGIN
       ========================================================= */

    /**
     * When a user enters a phone number as username, look up the
     * actual WordPress user and authenticate with their credentials.
     */
    public static function authenticate_by_phone( $user, $username, $password ) {
        if ( $user instanceof \WP_User || is_wp_error( $user ) ) {
            return $user;
        }

        if ( empty( $username ) || empty( $password ) ) {
            return $user;
        }

        // Check if username looks like a phone number.
        $cleaned = preg_replace( '/[\s\-\(\)]/', '', $username );
        if ( ! preg_match( '/^\+?\d{8,15}$/', $cleaned ) ) {
            return $user;
        }

        $normalized = \PhoneAuth\Database\Phone_Lookup::normalize_phone( $cleaned );
        $found_user = \PhoneAuth\Database\Phone_Lookup::find_user_by_phone( $normalized );

        if ( ! $found_user ) {
            return $user;
        }

        return wp_authenticate_username_password( null, $found_user->user_login, $password );
    }

    /* =========================================================
       CHECKOUT — FORCE LOGIN
       ========================================================= */

    /**
     * Redirect guests away from checkout to the login page.
     * Also blocks users whose phone is not verified.
     */
    public static function checkout_force_login() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        if ( ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
            return;
        }

        $myaccount_url = wc_get_page_permalink( 'myaccount' );

        // Block guests entirely.
        if ( ! is_user_logged_in() ) {
            $redirect = add_query_arg( 'redirect_to', urlencode( wc_get_checkout_url() ), $myaccount_url );
            wp_safe_redirect( $redirect );
            exit;
        }

        // Block logged-in users whose phone is not verified.
        $phone_verified = get_user_meta( get_current_user_id(), 'phone_verified', true );
        if ( '1' !== (string) $phone_verified ) {
            wc_add_notice( __( 'Please verify your phone number before checkout.', 'woo-firebase-phone-login' ), 'error' );
            wp_safe_redirect( $myaccount_url );
            exit;
        }
    }

    /* =========================================================
       EMAIL VERIFICATION
       ========================================================= */

    /**
     * Send a verification email to a newly registered user.
     */
    public static function send_verification_email( $user_id ) {
        $user = get_user_by( 'ID', $user_id );
        if ( ! $user || ! is_email( $user->user_email ) ) {
            return;
        }

        // Don't send for placeholder emails.
        if ( strpos( $user->user_email, '@noreply.' ) !== false ) {
            return;
        }

        $token = wp_generate_password( 32, false );
        update_user_meta( $user_id, 'lawha_email_verify_token', $token );
        update_user_meta( $user_id, 'lawha_email_verify_sent', time() );
        update_user_meta( $user_id, 'lawha_email_verified', 0 );

        // 7-day grace period.
        update_user_meta( $user_id, 'lawha_email_verify_deadline', time() + ( 7 * DAY_IN_SECONDS ) );

        $verify_url = add_query_arg( array(
            'lawha_verify_email' => $token,
            'uid'                => $user_id,
        ), home_url( '/' ) );

        $site_name = get_bloginfo( 'name' );
        $subject   = sprintf( __( 'Verify your email — %s', 'woo-firebase-phone-login' ), $site_name );
        $message   = sprintf(
            __( "Hello %s,\n\nThank you for creating an account with %s.\n\nPlease verify your email address by clicking the link below:\n\n%s\n\nThis link will expire in 7 days.\n\nIf you did not create this account, you can safely ignore this email.\n\nBest regards,\n%s", 'woo-firebase-phone-login' ),
            $user->display_name ?: $user->user_login,
            $site_name,
            esc_url( $verify_url ),
            $site_name
        );

        wp_mail( $user->user_email, $subject, $message );
    }

    /**
     * Handle email verification link clicks.
     */
    public static function handle_email_verification() {
        if ( ! isset( $_GET['lawha_verify_email'] ) || ! isset( $_GET['uid'] ) ) {
            return;
        }

        $token   = sanitize_text_field( $_GET['lawha_verify_email'] );
        $user_id = absint( $_GET['uid'] );

        if ( empty( $token ) || empty( $user_id ) ) {
            return;
        }

        $stored_token = get_user_meta( $user_id, 'lawha_email_verify_token', true );
        $deadline     = get_user_meta( $user_id, 'lawha_email_verify_deadline', true );

        if ( empty( $stored_token ) || ! hash_equals( $stored_token, $token ) ) {
            wc_add_notice( __( 'Invalid verification link.', 'woo-firebase-phone-login' ), 'error' );
            wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
            exit;
        }

        if ( $deadline && time() > (int) $deadline ) {
            wc_add_notice( __( 'Verification link has expired. Please request a new one.', 'woo-firebase-phone-login' ), 'error' );
            wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
            exit;
        }

        update_user_meta( $user_id, 'lawha_email_verified', 1 );
        delete_user_meta( $user_id, 'lawha_email_verify_token' );

        wc_add_notice( __( 'Email verified successfully!', 'woo-firebase-phone-login' ), 'success' );
        wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
        exit;
    }

    /**
     * AJAX: Resend verification email.
     */
    public static function ajax_resend_verification_email() {
        check_ajax_referer( 'lawha_wc_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'You must be logged in.' ) );
        }

        $user_id   = get_current_user_id();
        $last_sent = get_user_meta( $user_id, 'lawha_email_verify_sent', true );

        // Rate limit: 1 email per 60 seconds.
        if ( $last_sent && ( time() - (int) $last_sent ) < 60 ) {
            wp_send_json_error( array( 'message' => 'Please wait before requesting another email.' ) );
        }

        self::send_verification_email( $user_id );
        wp_send_json_success( array( 'message' => 'Verification email sent!' ) );
    }
}

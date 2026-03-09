<?php
/**
 * AJAX handlers for phone authentication.
 *
 * @package WFPL
 */

namespace WFPL\Frontend;

use WFPL\Helpers;
use WFPL\Auth_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Class Ajax_Handlers
 */
class Ajax_Handlers {

    /**
     * Constructor.
     */
    public function __construct() {
        // Available to both logged-in and guest users.
        add_action( 'wp_ajax_wfpl_verify_token', array( $this, 'handle_verify_token' ) );
        add_action( 'wp_ajax_nopriv_wfpl_verify_token', array( $this, 'handle_verify_token' ) );

        add_action( 'wp_ajax_wfpl_check_phone', array( $this, 'handle_check_phone' ) );
        add_action( 'wp_ajax_nopriv_wfpl_check_phone', array( $this, 'handle_check_phone' ) );
    }

    /**
     * Handle Firebase token verification + login.
     */
    public function handle_verify_token() {
        // Nonce check.
        if ( ! isset( $_POST['nonce'] ) || ! Helpers::verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed. Please refresh the page.', 'woo-firebase-phone-login' ),
            ), 403 );
        }

        // Get token.
        $token = isset( $_POST['firebase_token'] ) ? sanitize_text_field( wp_unslash( $_POST['firebase_token'] ) ) : '';

        if ( empty( $token ) ) {
            wp_send_json_error( array(
                'message' => __( 'Missing authentication token.', 'woo-firebase-phone-login' ),
            ), 400 );
        }

        // Authenticate.
        $result = Auth_Controller::authenticate( $token );

        if ( is_wp_error( $result ) ) {
            $status = $result->get_error_data( 'status' ) ?? 400;
            wp_send_json_error( array(
                'message' => $result->get_error_message(),
                'code'    => $result->get_error_code(),
            ), $status );
        }

        wp_send_json_success( array(
            'message'      => __( 'Login successful!', 'woo-firebase-phone-login' ),
            'user_id'      => $result['user_id'],
            'created'      => $result['created'],
            'redirect_url' => $this->get_redirect_url(),
        ) );
    }

    /**
     * Check if a phone number is registered.
     */
    public function handle_check_phone() {
        if ( ! isset( $_POST['nonce'] ) || ! Helpers::verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed.', 'woo-firebase-phone-login' ),
            ), 403 );
        }

        $phone = isset( $_POST['phone'] ) ? Helpers::sanitize_phone( sanitize_text_field( wp_unslash( $_POST['phone'] ) ) ) : '';

        if ( empty( $phone ) || ! Helpers::is_valid_e164( $phone ) ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid phone number.', 'woo-firebase-phone-login' ),
            ), 400 );
        }

        wp_send_json_success( array(
            'registered' => \WFPL\User_Handler::is_phone_registered( $phone ),
        ) );
    }

    /**
     * Get the redirect URL after successful login.
     *
     * @return string
     */
    private function get_redirect_url() {
        $redirect = isset( $_POST['redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_url'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if ( empty( $redirect ) ) {
            $redirect = wc_get_page_permalink( 'myaccount' );
        }

        /**
         * Filter the login redirect URL.
         *
         * @param string $redirect URL.
         */
        return apply_filters( 'wfpl_login_redirect', $redirect );
    }
}

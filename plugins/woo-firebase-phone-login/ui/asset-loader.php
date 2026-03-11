<?php
/**
 * Asset Loader — Enqueue scripts and styles for phone authentication.
 *
 * Operates in headless mode: only loads the Firebase SDK, intl-tel-input,
 * and the phone-auth JS SDK. The theme provides its own UI.
 *
 * @package PhoneAuth\UI
 */

namespace PhoneAuth\UI;

defined( 'ABSPATH' ) || exit;

class Asset_Loader {

    /**
     * Register WordPress hooks.
     */
    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /**
     * Enqueue Firebase SDK, intl-tel-input, and phone-auth JS on auth pages.
     */
    public static function enqueue_assets() {
        if ( ! self::should_load() ) {
            return;
        }

        // intl-tel-input CSS.
        wp_enqueue_style(
            'intl-tel-input',
            'https://cdn.jsdelivr.net/npm/intl-tel-input@21.1.1/build/css/intlTelInput.min.css',
            array(),
            '21.1.1'
        );

        // Firebase JS SDK (compat bundle).
        wp_enqueue_script(
            'firebase-app',
            'https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js',
            array(),
            '10.12.0',
            true
        );

        wp_enqueue_script(
            'firebase-auth',
            'https://www.gstatic.com/firebasejs/10.12.0/firebase-auth-compat.js',
            array( 'firebase-app' ),
            '10.12.0',
            true
        );

        // intl-tel-input JS.
        wp_enqueue_script(
            'intl-tel-input',
            'https://cdn.jsdelivr.net/npm/intl-tel-input@21.1.1/build/js/intlTelInput.min.js',
            array(),
            '21.1.1',
            true
        );

        // Phone Auth SDK — exposes global WFPL object.
        wp_enqueue_script(
            'phone-auth-sdk',
            WFPL_PLUGIN_URL . 'assets/js/firebase-auth.js',
            array( 'jquery', 'firebase-app', 'firebase-auth', 'intl-tel-input' ),
            WFPL_VERSION,
            true
        );

        // Phone Auth UI — registration OTP, forgot-password flow, etc.
        wp_enqueue_script(
            'phone-auth-ui',
            WFPL_PLUGIN_URL . 'assets/js/phone-auth-ui.js',
            array( 'jquery', 'phone-auth-sdk', 'intl-tel-input' ),
            WFPL_VERSION,
            true
        );

        // Localize with config.
        $firebase_config = \PhoneAuth\OTP\Send_OTP::get_firebase_config();

        wp_localize_script( 'phone-auth-sdk', 'phoneAuthConfig', array(
            'ajax_url'       => admin_url( 'admin-ajax.php' ),
            'nonce'          => \PhoneAuth\Database\Phone_Lookup::create_nonce(),
            'firebase'       => $firebase_config,
            'otp_expiration' => 120,
            'i18n'           => array(
                'sending'        => __( 'Sending OTP…', 'woo-firebase-phone-login' ),
                'verifying'      => __( 'Verifying…', 'woo-firebase-phone-login' ),
                'success'        => __( 'Login successful! Redirecting…', 'woo-firebase-phone-login' ),
                'invalid_phone'  => __( 'Please enter a valid phone number.', 'woo-firebase-phone-login' ),
                'otp_sent'       => __( 'OTP sent! Check your phone.', 'woo-firebase-phone-login' ),
                'otp_failed'     => __( 'Failed to send OTP. Please try again.', 'woo-firebase-phone-login' ),
                'verify_failed'  => __( 'Verification failed. Please try again.', 'woo-firebase-phone-login' ),
                'resend'         => __( 'Resend OTP', 'woo-firebase-phone-login' ),
                'resend_in'      => __( 'Resend in %s', 'woo-firebase-phone-login' ),
                'enter_otp'      => __( 'Enter the 6-digit code', 'woo-firebase-phone-login' ),
            ),
        ) );
    }

    /**
     * Determine if assets should load on this page.
     *
     * @return bool
     */
    private static function should_load() {
        // Must have Firebase configured.
        if ( ! \PhoneAuth\OTP\Send_OTP::is_firebase_configured() ) {
            return false;
        }

        // Load on My Account page (for guests: login/register, for logged-in: email verify banner).
        if ( function_exists( 'is_account_page' ) && is_account_page() ) {
            return true;
        }

        // Load on checkout for guests only.
        if ( ! is_user_logged_in() && function_exists( 'is_checkout' ) && is_checkout() ) {
            return true;
        }

        return false;
    }
}

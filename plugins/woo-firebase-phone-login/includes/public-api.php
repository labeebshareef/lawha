<?php
/**
 * Global public API functions.
 *
 * Convenience wrappers so developers can call
 * wfpl_get_user_by_phone(), wfpl_is_phone_registered(), etc.
 * without touching namespaced classes.
 *
 * NOTE: This file is not auto-loaded. Require it manually if needed.
 * The main plugin file (woo-firebase-phone-login.php) already defines
 * wfpl_get_option() and wfpl_is_firebase_configured().
 *
 * @package PhoneAuth
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wfpl_verify_firebase_token' ) ) {
    function wfpl_verify_firebase_token( $token ) {
        return \PhoneAuth\OTP\Verify_OTP::verify_id_token( $token );
    }
}

if ( ! function_exists( 'wfpl_authenticate' ) ) {
    function wfpl_authenticate( $token ) {
        return \PhoneAuth\Auth\Login_Controller::login( $token );
    }
}

if ( ! function_exists( 'wfpl_get_user_by_phone' ) ) {
    function wfpl_get_user_by_phone( $phone ) {
        return \PhoneAuth\Database\Phone_Lookup::find_user_by_phone( $phone );
    }
}

if ( ! function_exists( 'wfpl_is_phone_registered' ) ) {
    function wfpl_is_phone_registered( $phone ) {
        return \PhoneAuth\Database\Phone_Lookup::is_phone_registered( $phone );
    }
}

if ( ! function_exists( 'wfpl_is_rate_limited' ) ) {
    function wfpl_is_rate_limited( $phone ) {
        return \PhoneAuth\OTP\Send_OTP::is_rate_limited( $phone );
    }
}

if ( ! function_exists( 'wfpl_get_option' ) ) {
    function wfpl_get_option( $key, $default = '' ) {
        return get_option( 'wfpl_' . $key, $default );
    }
}

if ( ! function_exists( 'wfpl_is_firebase_configured' ) ) {
    function wfpl_is_firebase_configured() {
        return \PhoneAuth\OTP\Send_OTP::is_firebase_configured();
    }
}

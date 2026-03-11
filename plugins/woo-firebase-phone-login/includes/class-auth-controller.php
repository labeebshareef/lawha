<?php
/**
 * DEPRECATED — Legacy Auth Controller stub.
 *
 * This file is kept only to prevent fatal errors if any third-party code
 * still references \WFPL\Auth_Controller. All logic has been moved to:
 *   - \PhoneAuth\Auth\Login_Controller  (auth/login-controller.php)
 *   - \PhoneAuth\Auth\Register_Controller (auth/register-controller.php)
 *
 * @package WFPL
 * @deprecated 2.0.0
 */

namespace WFPL;

defined( 'ABSPATH' ) || exit;

class Auth_Controller {

    /**
     * @deprecated Use \PhoneAuth\Auth\Login_Controller::login() instead.
     */
    public static function authenticate( $firebase_token ) {
        _deprecated_function( __METHOD__, '2.0.0', '\PhoneAuth\Auth\Login_Controller::login()' );
        return \PhoneAuth\Auth\Login_Controller::login( $firebase_token );
    }

    /**
     * @deprecated Use \PhoneAuth\OTP\Verify_OTP::verify() instead.
     */
    public static function verify_only( $firebase_token ) {
        _deprecated_function( __METHOD__, '2.0.0', '\PhoneAuth\OTP\Verify_OTP::verify()' );
        return \PhoneAuth\OTP\Verify_OTP::verify( $firebase_token );
    }
}

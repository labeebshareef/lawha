<?php
/**
 * DEPRECATED — Legacy User Handler stub.
 *
 * This file is kept only to prevent fatal errors if any third-party code
 * still references \WFPL\User_Handler. All logic has been moved to:
 *   - \PhoneAuth\Database\Phone_Lookup  (database/phone-lookup.php)
 *   - \PhoneAuth\Auth\Register_Controller (auth/register-controller.php)
 *   - \PhoneAuth\Auth\Login_Controller  (auth/login-controller.php)
 *
 * @package WFPL
 * @deprecated 2.0.0
 */

namespace WFPL;

defined( 'ABSPATH' ) || exit;

class User_Handler {

    /**
     * @deprecated Use \PhoneAuth\Database\Phone_Lookup::find_user_by_phone() instead.
     */
    public static function get_user_by_phone( $phone ) {
        _deprecated_function( __METHOD__, '2.0.0', '\PhoneAuth\Database\Phone_Lookup::find_user_by_phone()' );
        return \PhoneAuth\Database\Phone_Lookup::find_user_by_phone( $phone );
    }

    /**
     * @deprecated Use \PhoneAuth\Database\Phone_Lookup::is_phone_registered() instead.
     */
    public static function is_phone_registered( $phone ) {
        _deprecated_function( __METHOD__, '2.0.0', '\PhoneAuth\Database\Phone_Lookup::is_phone_registered()' );
        return \PhoneAuth\Database\Phone_Lookup::is_phone_registered( $phone );
    }

    /**
     * @deprecated Use \PhoneAuth\Auth\Register_Controller::register() instead.
     */
    public static function create_user( $phone ) {
        _deprecated_function( __METHOD__, '2.0.0', '\PhoneAuth\Auth\Register_Controller::register()' );
        return new \WP_Error( 'wfpl_deprecated', 'User creation via User_Handler is deprecated. Use Register_Controller::register() with a Firebase token.' );
    }

    /**
     * @deprecated Use \PhoneAuth\Auth\Login_Controller::wp_login() instead.
     */
    public static function login_user( $user_id ) {
        _deprecated_function( __METHOD__, '2.0.0', '\PhoneAuth\Auth\Login_Controller::wp_login()' );
        return \PhoneAuth\Auth\Login_Controller::wp_login( $user_id );
    }

    /**
     * @deprecated Use \PhoneAuth\Auth\Login_Controller::login() instead.
     */
    public static function login_or_create( $phone ) {
        _deprecated_function( __METHOD__, '2.0.0', '\PhoneAuth\Auth\Login_Controller::login()' );
        return new \WP_Error( 'wfpl_deprecated', 'login_or_create is deprecated. Use Login_Controller::login() or Register_Controller::register() with a Firebase token.' );
    }
}

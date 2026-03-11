<?php
/**
 * DEPRECATED — Legacy Firebase Auth stub.
 *
 * Token verification has been moved to:
 *   - \PhoneAuth\OTP\Verify_OTP  (otp/verify-otp.php)
 *
 * @package WFPL
 * @deprecated 2.0.0
 */

namespace WFPL;

defined( 'ABSPATH' ) || exit;

class Firebase_Auth {

    /**
     * @deprecated Use \PhoneAuth\OTP\Verify_OTP::verify_id_token() instead.
     */
    public static function verify_id_token( $id_token ) {
        _deprecated_function( __METHOD__, '2.0.0', '\PhoneAuth\OTP\Verify_OTP::verify_id_token()' );
        return \PhoneAuth\OTP\Verify_OTP::verify_id_token( $id_token );
    }

    /**
     * @deprecated Use \PhoneAuth\OTP\Verify_OTP::get_phone_from_payload() instead.
     */
    public static function get_phone_from_payload( $payload ) {
        _deprecated_function( __METHOD__, '2.0.0', '\PhoneAuth\OTP\Verify_OTP::get_phone_from_payload()' );
        return \PhoneAuth\OTP\Verify_OTP::get_phone_from_payload( $payload );
    }
}

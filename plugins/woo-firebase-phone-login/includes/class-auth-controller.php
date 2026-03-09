<?php
/**
 * Auth controller — orchestrates the full Firebase OTP authentication flow.
 *
 * @package WFPL
 */

namespace WFPL;

defined( 'ABSPATH' ) || exit;

/**
 * Class Auth_Controller
 */
class Auth_Controller {

    /**
     * Process a Firebase ID token: verify → extract phone → login/create user.
     *
     * @param string $firebase_token Raw Firebase ID token from client.
     * @return array|\WP_Error Response array on success.
     */
    public static function authenticate( $firebase_token ) {
        // 1. Verify token.
        $payload = Firebase_Auth::verify_id_token( $firebase_token );
        if ( is_wp_error( $payload ) ) {
            return $payload;
        }

        // 2. Extract phone number.
        $phone = Firebase_Auth::get_phone_from_payload( $payload );
        if ( empty( $phone ) ) {
            return new \WP_Error( 'wfpl_no_phone', __( 'No phone number in token.', 'woo-firebase-phone-login' ) );
        }

        $phone = Helpers::sanitize_phone( $phone );

        // 3. Rate limit check.
        if ( Helpers::is_rate_limited( $phone ) ) {
            return new \WP_Error(
                'wfpl_rate_limited',
                __( 'Too many attempts. Please try again later.', 'woo-firebase-phone-login' ),
                array( 'status' => 429 )
            );
        }

        Helpers::increment_otp_counter( $phone );

        // 4. Login or create user.
        $result = User_Handler::login_or_create( $phone );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return array(
            'success' => true,
            'user_id' => $result['user_id'],
            'created' => $result['created'],
            'phone'   => $phone,
        );
    }

    /**
     * Verify a Firebase token without logging in.
     *
     * Useful for the REST "verify" endpoint.
     *
     * @param string $firebase_token Firebase ID token.
     * @return array|\WP_Error
     */
    public static function verify_only( $firebase_token ) {
        $payload = Firebase_Auth::verify_id_token( $firebase_token );
        if ( is_wp_error( $payload ) ) {
            return $payload;
        }

        $phone      = Firebase_Auth::get_phone_from_payload( $payload );
        $registered = User_Handler::is_phone_registered( $phone );

        return array(
            'success'    => true,
            'phone'      => $phone,
            'registered' => $registered,
            'firebase_uid' => $payload['sub'] ?? '',
        );
    }
}

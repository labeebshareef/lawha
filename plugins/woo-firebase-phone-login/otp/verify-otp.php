<?php
/**
 * OTP Verify — Verify Firebase ID tokens server-side.
 *
 * Validates Firebase JWT tokens using RS256 signature verification
 * against Google's public keys. Also handles token replay protection.
 *
 * @package PhoneAuth\OTP
 */

namespace PhoneAuth\OTP;

defined( 'ABSPATH' ) || exit;

class Verify_OTP {

    /**
     * Google public-key endpoint for Firebase tokens.
     */
    const GOOGLE_CERTS_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

    /**
     * Transient key used to cache Google public keys.
     */
    const CERTS_TRANSIENT = 'pa_google_certs';

    /**
     * OTP expiry in seconds.
     */
    const OTP_EXPIRY = 120;

    /**
     * Verify a Firebase ID token and extract the phone number.
     *
     * @param string $firebase_token Raw JWT string from the client.
     * @return array|\WP_Error { phone: string, firebase_uid: string } on success.
     */
    public static function verify( $firebase_token ) {
        // Token replay protection.
        $token_hash = hash( 'sha256', $firebase_token );
        if ( self::is_token_used( $token_hash ) ) {
            return new \WP_Error(
                'phone_auth_token_replay',
                __( 'This authentication token has already been used.', 'woo-firebase-phone-login' ),
                array( 'status' => 403 )
            );
        }

        // Verify JWT.
        $payload = self::verify_id_token( $firebase_token );
        if ( is_wp_error( $payload ) ) {
            return $payload;
        }

        // Extract phone number.
        $phone = isset( $payload['phone_number'] ) ? $payload['phone_number'] : '';
        if ( empty( $phone ) ) {
            return new \WP_Error(
                'phone_auth_no_phone',
                __( 'No phone number in verification token.', 'woo-firebase-phone-login' )
            );
        }

        $phone = \PhoneAuth\Database\Phone_Lookup::normalize_phone( $phone );

        // Mark token as used.
        self::mark_token_used( $token_hash );

        return array(
            'phone'        => $phone,
            'firebase_uid' => $payload['sub'] ?? '',
        );
    }

    /**
     * Verify a Firebase ID token and return decoded payload.
     *
     * @param string $id_token Raw JWT string.
     * @return array|\WP_Error Decoded payload on success.
     */
    public static function verify_id_token( $id_token ) {
        $parts = explode( '.', $id_token );
        if ( count( $parts ) !== 3 ) {
            return new \WP_Error( 'phone_auth_invalid_token', __( 'Malformed token.', 'woo-firebase-phone-login' ) );
        }

        list( $header_b64, $payload_b64, $signature_b64 ) = $parts;

        $header  = self::base64url_decode_json( $header_b64 );
        $payload = self::base64url_decode_json( $payload_b64 );

        if ( ! $header || ! $payload ) {
            return new \WP_Error( 'phone_auth_invalid_token', __( 'Cannot decode token.', 'woo-firebase-phone-login' ) );
        }

        if ( empty( $header['alg'] ) || 'RS256' !== $header['alg'] ) {
            return new \WP_Error( 'phone_auth_invalid_alg', __( 'Invalid token algorithm.', 'woo-firebase-phone-login' ) );
        }

        $project_id = get_option( 'wfpl_firebase_project_id', '' );

        if ( empty( $payload['iss'] ) || $payload['iss'] !== 'https://securetoken.google.com/' . $project_id ) {
            return new \WP_Error( 'phone_auth_invalid_iss', __( 'Invalid token issuer.', 'woo-firebase-phone-login' ) );
        }

        if ( empty( $payload['aud'] ) || $payload['aud'] !== $project_id ) {
            return new \WP_Error( 'phone_auth_invalid_aud', __( 'Invalid token audience.', 'woo-firebase-phone-login' ) );
        }

        $now = time();
        if ( empty( $payload['exp'] ) || $payload['exp'] < $now ) {
            return new \WP_Error( 'phone_auth_token_expired', __( 'Token has expired.', 'woo-firebase-phone-login' ) );
        }

        if ( empty( $payload['iat'] ) || $payload['iat'] > $now + 300 ) {
            return new \WP_Error( 'phone_auth_invalid_iat', __( 'Token issued in the future.', 'woo-firebase-phone-login' ) );
        }

        // OTP expiry: reject if authentication happened more than OTP_EXPIRY seconds ago.
        if ( ! empty( $payload['auth_time'] ) && ( $now - $payload['auth_time'] ) > self::OTP_EXPIRY ) {
            return new \WP_Error( 'phone_auth_otp_expired', __( 'OTP verification has expired. Please request a new code.', 'woo-firebase-phone-login' ) );
        }

        if ( empty( $payload['sub'] ) ) {
            return new \WP_Error( 'phone_auth_missing_sub', __( 'Token missing subject.', 'woo-firebase-phone-login' ) );
        }

        $verified = self::verify_signature( $header_b64 . '.' . $payload_b64, $signature_b64, $header['kid'] ?? '' );
        if ( is_wp_error( $verified ) ) {
            return $verified;
        }

        if ( empty( $payload['phone_number'] ) ) {
            return new \WP_Error( 'phone_auth_no_phone', __( 'Token does not contain a phone number.', 'woo-firebase-phone-login' ) );
        }

        return $payload;
    }

    /**
     * Extract phone number from a verified token payload.
     *
     * @param array $payload Verified payload.
     * @return string
     */
    public static function get_phone_from_payload( $payload ) {
        return $payload['phone_number'] ?? '';
    }

    /**
     * Verify RS256 signature against Google public keys.
     *
     * @param string $data          Header.Payload.
     * @param string $signature_b64 Base64url-encoded signature.
     * @param string $kid           Key ID.
     * @return true|\WP_Error
     */
    private static function verify_signature( $data, $signature_b64, $kid ) {
        if ( empty( $kid ) ) {
            return new \WP_Error( 'phone_auth_missing_kid', __( 'Token missing key ID.', 'woo-firebase-phone-login' ) );
        }

        $certs = self::get_google_certs();
        if ( is_wp_error( $certs ) ) {
            return $certs;
        }

        if ( ! isset( $certs[ $kid ] ) ) {
            delete_transient( self::CERTS_TRANSIENT );
            $certs = self::get_google_certs();
            if ( is_wp_error( $certs ) ) {
                return $certs;
            }
            if ( ! isset( $certs[ $kid ] ) ) {
                return new \WP_Error( 'phone_auth_unknown_kid', __( 'Unknown signing key.', 'woo-firebase-phone-login' ) );
            }
        }

        $public_key = openssl_pkey_get_public( $certs[ $kid ] );
        if ( ! $public_key ) {
            return new \WP_Error( 'phone_auth_key_error', __( 'Failed to parse public key.', 'woo-firebase-phone-login' ) );
        }

        $signature = self::base64url_decode( $signature_b64 );
        $result    = openssl_verify( $data, $signature, $public_key, OPENSSL_ALGO_SHA256 );

        if ( PHP_MAJOR_VERSION < 8 ) {
            openssl_free_key( $public_key );
        }

        if ( 1 === $result ) {
            return true;
        }

        return new \WP_Error( 'phone_auth_sig_invalid', __( 'Token signature verification failed.', 'woo-firebase-phone-login' ) );
    }

    /**
     * Fetch Google public certificates, cached via transient.
     *
     * @return array|\WP_Error
     */
    private static function get_google_certs() {
        $cached = get_transient( self::CERTS_TRANSIENT );
        if ( $cached && is_array( $cached ) ) {
            return $cached;
        }

        $response = wp_remote_get( self::GOOGLE_CERTS_URL, array( 'timeout' => 10 ) );
        if ( is_wp_error( $response ) ) {
            return new \WP_Error( 'phone_auth_cert_fetch', __( 'Failed to fetch Google certificates.', 'woo-firebase-phone-login' ) );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return new \WP_Error( 'phone_auth_cert_fetch', __( 'Google certificate endpoint returned HTTP ' . $code, 'woo-firebase-phone-login' ) );
        }

        $body  = wp_remote_retrieve_body( $response );
        $certs = json_decode( $body, true );

        if ( ! is_array( $certs ) || empty( $certs ) ) {
            return new \WP_Error( 'phone_auth_cert_parse', __( 'Failed to parse Google certificates.', 'woo-firebase-phone-login' ) );
        }

        set_transient( self::CERTS_TRANSIENT, $certs, DAY_IN_SECONDS );
        return $certs;
    }

    /**
     * Check if a Firebase token has already been used.
     *
     * @param string $token_hash SHA-256 hash.
     * @return bool
     */
    private static function is_token_used( $token_hash ) {
        return (bool) get_transient( 'pa_used_' . $token_hash );
    }

    /**
     * Mark a Firebase token as used.
     *
     * @param string $token_hash SHA-256 hash.
     */
    private static function mark_token_used( $token_hash ) {
        set_transient( 'pa_used_' . $token_hash, 1, HOUR_IN_SECONDS );
    }

    /**
     * Decode base64url string.
     *
     * @param string $data Base64url-encoded string.
     * @return string
     */
    private static function base64url_decode( $data ) {
        $remainder = strlen( $data ) % 4;
        if ( $remainder ) {
            $data .= str_repeat( '=', 4 - $remainder );
        }
        return base64_decode( strtr( $data, '-_', '+/' ) );
    }

    /**
     * Decode base64url JSON.
     *
     * @param string $data Base64url-encoded JSON.
     * @return array|false
     */
    private static function base64url_decode_json( $data ) {
        $decoded = self::base64url_decode( $data );
        if ( false === $decoded ) {
            return false;
        }
        $result = json_decode( $decoded, true );
        return is_array( $result ) ? $result : false;
    }
}

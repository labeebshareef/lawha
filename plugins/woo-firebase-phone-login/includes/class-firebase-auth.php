<?php
/**
 * Firebase ID Token verification.
 *
 * Verifies RS256-signed Firebase ID tokens using Google's public keys.
 *
 * @package WFPL
 */

namespace WFPL;

defined( 'ABSPATH' ) || exit;

/**
 * Class Firebase_Auth
 */
class Firebase_Auth {

    /**
     * Google public-key endpoint for Firebase tokens.
     */
    const GOOGLE_CERTS_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

    /**
     * Transient key used to cache Google public keys.
     */
    const CERTS_TRANSIENT = 'wfpl_google_certs';

    /*--------------------------------------------------------------
     * Public API
     *------------------------------------------------------------*/

    /**
     * Verify a Firebase ID token and return decoded payload.
     *
     * @param string $id_token Raw JWT string from the client.
     * @return array|\WP_Error Decoded payload on success, WP_Error on failure.
     */
    public static function verify_id_token( $id_token ) {
        // 1. Decode JWT parts.
        $parts = explode( '.', $id_token );
        if ( count( $parts ) !== 3 ) {
            return new \WP_Error( 'wfpl_invalid_token', __( 'Malformed token.', 'woo-firebase-phone-login' ) );
        }

        list( $header_b64, $payload_b64, $signature_b64 ) = $parts;

        $header  = self::base64url_decode_json( $header_b64 );
        $payload = self::base64url_decode_json( $payload_b64 );

        if ( ! $header || ! $payload ) {
            return new \WP_Error( 'wfpl_invalid_token', __( 'Cannot decode token.', 'woo-firebase-phone-login' ) );
        }

        // 2. Validate algorithm.
        if ( empty( $header['alg'] ) || 'RS256' !== $header['alg'] ) {
            return new \WP_Error( 'wfpl_invalid_alg', __( 'Invalid token algorithm.', 'woo-firebase-phone-login' ) );
        }

        // 3. Validate claims.
        $project_id = Helpers::get_option( 'firebase_project_id' );

        if ( empty( $payload['iss'] ) || $payload['iss'] !== 'https://securetoken.google.com/' . $project_id ) {
            return new \WP_Error( 'wfpl_invalid_iss', __( 'Invalid token issuer.', 'woo-firebase-phone-login' ) );
        }

        if ( empty( $payload['aud'] ) || $payload['aud'] !== $project_id ) {
            return new \WP_Error( 'wfpl_invalid_aud', __( 'Invalid token audience.', 'woo-firebase-phone-login' ) );
        }

        $now = time();
        if ( empty( $payload['exp'] ) || $payload['exp'] < $now ) {
            return new \WP_Error( 'wfpl_token_expired', __( 'Token has expired.', 'woo-firebase-phone-login' ) );
        }

        if ( empty( $payload['iat'] ) || $payload['iat'] > $now + 300 ) {
            return new \WP_Error( 'wfpl_invalid_iat', __( 'Token issued in the future.', 'woo-firebase-phone-login' ) );
        }

        if ( empty( $payload['sub'] ) ) {
            return new \WP_Error( 'wfpl_missing_sub', __( 'Token missing subject.', 'woo-firebase-phone-login' ) );
        }

        // 4. Verify signature with Google public keys.
        $verified = self::verify_signature( $header_b64 . '.' . $payload_b64, $signature_b64, $header['kid'] ?? '' );

        if ( is_wp_error( $verified ) ) {
            return $verified;
        }

        // 5. Ensure phone_number claim exists.
        if ( empty( $payload['phone_number'] ) ) {
            return new \WP_Error( 'wfpl_no_phone', __( 'Token does not contain a phone number.', 'woo-firebase-phone-login' ) );
        }

        return $payload;
    }

    /**
     * Extract phone number from a verified token payload.
     *
     * @param array $payload Verified payload.
     * @return string Phone number in E.164 format.
     */
    public static function get_phone_from_payload( $payload ) {
        return $payload['phone_number'] ?? '';
    }

    /*--------------------------------------------------------------
     * Signature verification
     *------------------------------------------------------------*/

    /**
     * Verify the RS256 signature against Google public keys.
     *
     * @param string $data          Header.Payload base64url-encoded.
     * @param string $signature_b64 Base64url-encoded signature.
     * @param string $kid           Key ID from the JWT header.
     * @return true|\WP_Error
     */
    private static function verify_signature( $data, $signature_b64, $kid ) {
        if ( empty( $kid ) ) {
            return new \WP_Error( 'wfpl_missing_kid', __( 'Token missing key ID.', 'woo-firebase-phone-login' ) );
        }

        $certs = self::get_google_certs();
        if ( is_wp_error( $certs ) ) {
            return $certs;
        }

        if ( ! isset( $certs[ $kid ] ) ) {
            // Refresh certs in case they rotated.
            delete_transient( self::CERTS_TRANSIENT );
            $certs = self::get_google_certs();
            if ( is_wp_error( $certs ) ) {
                return $certs;
            }
            if ( ! isset( $certs[ $kid ] ) ) {
                return new \WP_Error( 'wfpl_unknown_kid', __( 'Unknown signing key.', 'woo-firebase-phone-login' ) );
            }
        }

        $public_key = openssl_pkey_get_public( $certs[ $kid ] );
        if ( ! $public_key ) {
            return new \WP_Error( 'wfpl_key_error', __( 'Failed to parse public key.', 'woo-firebase-phone-login' ) );
        }

        $signature = self::base64url_decode( $signature_b64 );

        $result = openssl_verify( $data, $signature, $public_key, OPENSSL_ALGO_SHA256 );

        if ( PHP_MAJOR_VERSION < 8 ) {
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
            openssl_free_key( $public_key );
        }

        if ( 1 === $result ) {
            return true;
        }

        return new \WP_Error( 'wfpl_sig_invalid', __( 'Token signature verification failed.', 'woo-firebase-phone-login' ) );
    }

    /*--------------------------------------------------------------
     * Google certificate fetching (cached)
     *------------------------------------------------------------*/

    /**
     * Fetch Google public certificates, cached via transient.
     *
     * @return array|\WP_Error Associative array of kid => PEM.
     */
    private static function get_google_certs() {
        $cached = get_transient( self::CERTS_TRANSIENT );
        if ( $cached && is_array( $cached ) ) {
            return $cached;
        }

        $response = wp_remote_get( self::GOOGLE_CERTS_URL, array(
            'timeout' => 10,
        ) );

        if ( is_wp_error( $response ) ) {
            return new \WP_Error( 'wfpl_cert_fetch', __( 'Failed to fetch Google certificates.', 'woo-firebase-phone-login' ) );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return new \WP_Error( 'wfpl_cert_fetch', __( 'Google certificate endpoint returned HTTP ' . $code, 'woo-firebase-phone-login' ) );
        }

        $body  = wp_remote_retrieve_body( $response );
        $certs = json_decode( $body, true );

        if ( ! is_array( $certs ) || empty( $certs ) ) {
            return new \WP_Error( 'wfpl_cert_parse', __( 'Failed to parse Google certificates.', 'woo-firebase-phone-login' ) );
        }

        // Cache for 24 hours.
        set_transient( self::CERTS_TRANSIENT, $certs, DAY_IN_SECONDS );

        return $certs;
    }

    /*--------------------------------------------------------------
     * Base64url helpers
     *------------------------------------------------------------*/

    /**
     * Decode base64url string.
     *
     * @param string $data Base64url-encoded string.
     * @return string Raw bytes.
     */
    private static function base64url_decode( $data ) {
        $remainder = strlen( $data ) % 4;
        if ( $remainder ) {
            $data .= str_repeat( '=', 4 - $remainder );
        }
        return base64_decode( strtr( $data, '-_', '+/' ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
    }

    /**
     * Decode base64url JSON.
     *
     * @param string $data Base64url-encoded JSON string.
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

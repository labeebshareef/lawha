<?php
/**
 * Utility helpers.
 *
 * @package WFPL
 */

namespace WFPL;

defined( 'ABSPATH' ) || exit;

/**
 * Class Helpers
 *
 * Static utility methods used across the plugin.
 */
class Helpers {

    /*--------------------------------------------------------------
     * Option helpers
     *------------------------------------------------------------*/

    /**
     * Get a plugin option with fallback.
     *
     * @param string $key     Option key (without prefix).
     * @param mixed  $default Default value.
     * @return mixed
     */
    public static function get_option( $key, $default = '' ) {
        return get_option( 'wfpl_' . $key, $default );
    }

    /**
     * Check whether a feature toggle is enabled.
     *
     * @param string $key Option key (without prefix).
     * @return bool
     */
    public static function is_feature_enabled( $key ) {
        return 'yes' === self::get_option( $key, 'no' );
    }

    /*--------------------------------------------------------------
     * Phone helpers
     *------------------------------------------------------------*/

    /**
     * Sanitize a phone number to E.164 format.
     *
     * Strips all non-numeric characters except leading +.
     *
     * @param string $phone Raw phone input.
     * @return string Sanitized phone.
     */
    public static function sanitize_phone( $phone ) {
        $phone = trim( $phone );

        // Keep leading + and strip everything else non-numeric.
        if ( strpos( $phone, '+' ) === 0 ) {
            $phone = '+' . preg_replace( '/[^0-9]/', '', substr( $phone, 1 ) );
        } else {
            $phone = preg_replace( '/[^0-9]/', '', $phone );
        }

        /**
         * Filter the sanitized phone number.
         *
         * @param string $phone Sanitized phone.
         */
        return apply_filters( 'wfpl_phone_validation', $phone );
    }

    /**
     * Validate E.164 format.
     *
     * @param string $phone Phone number.
     * @return bool
     */
    public static function is_valid_e164( $phone ) {
        // E.164: +, 1-3 digit country code, subscriber number, total 8-15 digits.
        return (bool) preg_match( '/^\+[1-9]\d{6,14}$/', $phone );
    }

    /*--------------------------------------------------------------
     * Rate-limiting helpers
     *------------------------------------------------------------*/

    /**
     * Check if a phone number has exceeded the OTP rate limit.
     *
     * @param string $phone E.164 phone number.
     * @return bool True if rate-limited.
     */
    public static function is_rate_limited( $phone ) {
        $max     = absint( self::get_option( 'max_otp_per_hour', 5 ) );
        $key     = 'wfpl_otp_count_' . md5( $phone );
        $current = absint( get_transient( $key ) );

        return $current >= $max;
    }

    /**
     * Increment OTP request counter.
     *
     * @param string $phone E.164 phone number.
     */
    public static function increment_otp_counter( $phone ) {
        $key     = 'wfpl_otp_count_' . md5( $phone );
        $current = absint( get_transient( $key ) );

        set_transient( $key, $current + 1, HOUR_IN_SECONDS );
    }

    /*--------------------------------------------------------------
     * Firebase config
     *------------------------------------------------------------*/

    /**
     * Get Firebase configuration array.
     *
     * @return array
     */
    public static function get_firebase_config() {
        return array(
            'apiKey'     => self::get_option( 'firebase_api_key' ),
            'authDomain' => self::get_option( 'firebase_auth_domain' ),
            'projectId'  => self::get_option( 'firebase_project_id' ),
        );
    }

    /**
     * Check if Firebase is configured.
     *
     * @return bool
     */
    public static function is_firebase_configured() {
        $config = self::get_firebase_config();
        return ! empty( $config['apiKey'] ) && ! empty( $config['projectId'] );
    }

    /*--------------------------------------------------------------
     * Misc
     *------------------------------------------------------------*/

    /**
     * Generate a nonce for the plugin.
     *
     * @return string
     */
    public static function create_nonce() {
        return wp_create_nonce( 'wfpl_auth_nonce' );
    }

    /**
     * Verify the plugin nonce.
     *
     * @param string $nonce Nonce value.
     * @return bool
     */
    public static function verify_nonce( $nonce ) {
        return (bool) wp_verify_nonce( $nonce, 'wfpl_auth_nonce' );
    }
}

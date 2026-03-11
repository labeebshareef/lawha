<?php
/**
 * OTP Send — Rate limiting and Firebase config for client-side OTP dispatch.
 *
 * Firebase sends OTP client-side. This module handles server-side rate limiting
 * and returns Firebase config to the client.
 *
 * @package PhoneAuth\OTP
 */

namespace PhoneAuth\OTP;

defined( 'ABSPATH' ) || exit;

class Send_OTP {

    /**
     * Max OTP requests per phone per 5 minutes.
     */
    const MAX_REQUESTS_PER_WINDOW = 3;

    /**
     * Rate limit window in seconds (5 minutes).
     */
    const RATE_LIMIT_WINDOW = 300;

    /**
     * Process a send-OTP request: validate phone, check rate limit, return Firebase config.
     *
     * @param string $phone E.164 phone number.
     * @return array|\WP_Error
     */
    public static function process( $phone ) {
        $phone = \PhoneAuth\Database\Phone_Lookup::normalize_phone( $phone );

        if ( ! \PhoneAuth\Database\Phone_Lookup::is_valid_e164( $phone ) ) {
            return new \WP_Error(
                'phone_auth_invalid_phone',
                __( 'Invalid phone number format.', 'woo-firebase-phone-login' ),
                array( 'status' => 400 )
            );
        }

        if ( self::is_rate_limited( $phone ) ) {
            return new \WP_Error(
                'phone_auth_rate_limited',
                __( 'Too many OTP requests. Please try again in a few minutes.', 'woo-firebase-phone-login' ),
                array( 'status' => 429 )
            );
        }

        if ( self::is_ip_rate_limited() ) {
            return new \WP_Error(
                'phone_auth_rate_limited',
                __( 'Too many requests from this IP. Please try again later.', 'woo-firebase-phone-login' ),
                array( 'status' => 429 )
            );
        }

        self::increment_counter( $phone );

        $config = self::get_firebase_config();
        if ( empty( $config['apiKey'] ) || empty( $config['projectId'] ) ) {
            return new \WP_Error(
                'phone_auth_not_configured',
                __( 'Firebase is not configured.', 'woo-firebase-phone-login' ),
                array( 'status' => 500 )
            );
        }

        return array(
            'success'  => true,
            'phone'    => $phone,
            'firebase' => $config,
        );
    }

    /**
     * Check if a phone number has exceeded the OTP rate limit.
     *
     * @param string $phone E.164 phone number.
     * @return bool
     */
    public static function is_rate_limited( $phone ) {
        $key     = 'pa_otp_' . md5( $phone );
        $current = absint( get_transient( $key ) );
        return $current >= self::MAX_REQUESTS_PER_WINDOW;
    }

    /**
     * Check if the current IP has exceeded the rate limit.
     *
     * @return bool
     */
    public static function is_ip_rate_limited() {
        $ip  = self::get_client_ip();
        $key = 'pa_ip_' . md5( $ip );
        return absint( get_transient( $key ) ) >= 15;
    }

    /**
     * Increment OTP request counters for phone and IP.
     *
     * @param string $phone E.164 phone number.
     */
    public static function increment_counter( $phone ) {
        $key     = 'pa_otp_' . md5( $phone );
        $current = absint( get_transient( $key ) );
        set_transient( $key, $current + 1, self::RATE_LIMIT_WINDOW );

        $ip      = self::get_client_ip();
        $ip_key  = 'pa_ip_' . md5( $ip );
        $ip_curr = absint( get_transient( $ip_key ) );
        set_transient( $ip_key, $ip_curr + 1, HOUR_IN_SECONDS );
    }

    /**
     * Get Firebase configuration.
     *
     * @return array
     */
    public static function get_firebase_config() {
        return array(
            'apiKey'     => get_option( 'wfpl_firebase_api_key', '' ),
            'authDomain' => get_option( 'wfpl_firebase_auth_domain', '' ),
            'projectId'  => get_option( 'wfpl_firebase_project_id', '' ),
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

    /**
     * Get client IP address.
     *
     * @return string
     */
    private static function get_client_ip() {
        $headers = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }
        return '127.0.0.1';
    }
}

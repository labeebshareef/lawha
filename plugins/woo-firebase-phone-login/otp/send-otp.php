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
            'success' => true,
            'phone'   => $phone,
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
        $remote = isset( $_SERVER['REMOTE_ADDR'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
            : '127.0.0.1';

        // Only trust Cloudflare header if admin has enabled it and REMOTE_ADDR is a Cloudflare IP.
        if ( get_option( 'wfpl_behind_cloudflare', false )
             && ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] )
             && self::is_cloudflare_ip( $remote ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                return $ip;
            }
        }

        return filter_var( $remote, FILTER_VALIDATE_IP ) ? $remote : '127.0.0.1';
    }

    /**
     * Get Cloudflare IPv4 ranges, cached for one week.
     *
     * Fetches the live list from Cloudflare's published endpoint.
     * Falls back to a hardcoded snapshot if the fetch fails.
     *
     * @return array CIDR strings.
     */
    private static function get_cloudflare_ranges() {
        $cached = get_transient( 'wfpl_cf_ip_ranges' );
        if ( is_array( $cached ) && ! empty( $cached ) ) {
            return $cached;
        }

        $response = wp_remote_get( 'https://www.cloudflare.com/ips-v4', array( 'timeout' => 5 ) );

        if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
            $body   = trim( wp_remote_retrieve_body( $response ) );
            $ranges = array_filter( array_map( 'trim', explode( "\n", $body ) ) );
            if ( ! empty( $ranges ) ) {
                set_transient( 'wfpl_cf_ip_ranges', $ranges, WEEK_IN_SECONDS );
                return $ranges;
            }
        }

        // Hardcoded fallback — last known good list (updated March 2026).
        return array(
            '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22',
            '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
            '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22',
            '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
            '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        );
    }

    /**
     * Check if an IP belongs to Cloudflare's published ranges.
     *
     * @param string $ip
     * @return bool
     */
    private static function is_cloudflare_ip( $ip ) {
        $cf_ranges = self::get_cloudflare_ranges();
        $ip_long   = ip2long( $ip );
        if ( false === $ip_long ) {
            return false;
        }
        foreach ( $cf_ranges as $range ) {
            list( $subnet, $bits ) = explode( '/', $range );
            $subnet_long = ip2long( $subnet );
            $mask        = -1 << ( 32 - (int) $bits );
            if ( ( $ip_long & $mask ) === ( $subnet_long & $mask ) ) {
                return true;
            }
        }
        return false;
    }
}

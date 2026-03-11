<?php
/**
 * Phone Lookup — Single source of truth for phone-based user lookup.
 *
 * All phone numbers are stored in billing_phone (E.164 format).
 * This module handles normalization, validation, and lookup.
 *
 * @package PhoneAuth\Database
 */

namespace PhoneAuth\Database;

defined( 'ABSPATH' ) || exit;

class Phone_Lookup {

    /**
     * The single meta key for phone numbers.
     */
    const META_KEY = 'billing_phone';

    /**
     * Find a WordPress user by phone number.
     *
     * @param string $phone E.164 phone number.
     * @return \WP_User|false
     */
    public static function find_user_by_phone( $phone ) {
        $phone = self::normalize_phone( $phone );

        if ( ! self::is_valid_e164( $phone ) ) {
            return false;
        }

        $users = get_users( array(
            'meta_key'   => self::META_KEY,
            'meta_value' => $phone,
            'number'     => 1,
        ) );

        return ! empty( $users ) ? $users[0] : false;
    }

    /**
     * Check if a phone number is registered.
     *
     * @param string $phone E.164 phone number.
     * @return bool
     */
    public static function is_phone_registered( $phone ) {
        return (bool) self::find_user_by_phone( $phone );
    }

    /**
     * Normalize a phone number to E.164 format.
     *
     * Handles:
     *   0501234567     → +971501234567  (UAE local)
     *   971501234567   → +971501234567  (UAE without +)
     *   +971501234567  → +971501234567  (already E.164)
     *   00971501234567 → +971501234567  (international prefix)
     *   5XXXXXXXX      → +9715XXXXXXXX  (UAE 9-digit)
     *
     * @param string $phone Raw phone input.
     * @return string Normalized E.164 phone.
     */
    public static function normalize_phone( $phone ) {
        $phone = trim( $phone );

        // Preserve leading + and strip all non-digits.
        $has_plus = ( substr( $phone, 0, 1 ) === '+' );
        $digits   = preg_replace( '/[^\d]/', '', $phone );

        if ( empty( $digits ) ) {
            return $phone;
        }

        // Already E.164 with +.
        if ( $has_plus ) {
            return '+' . $digits;
        }

        // International prefix 00 → +
        if ( substr( $digits, 0, 2 ) === '00' ) {
            return '+' . substr( $digits, 2 );
        }

        // UAE local: 05XXXXXXXX (10 digits starting with 0) → +9715XXXXXXXX
        if ( strlen( $digits ) === 10 && substr( $digits, 0, 2 ) === '05' ) {
            return '+971' . substr( $digits, 1 );
        }

        // UAE without leading zero: 5XXXXXXXX (9 digits starting with 5) → +9715XXXXXXXX
        if ( strlen( $digits ) === 9 && substr( $digits, 0, 1 ) === '5' ) {
            return '+971' . $digits;
        }

        // Indian local: 0XXXXXXXXXX (11 digits starting with 0) → +91XXXXXXXXXX
        if ( substr( $digits, 0, 1 ) === '0' && strlen( $digits ) === 11 ) {
            return '+91' . substr( $digits, 1 );
        }

        // Indian 10-digit (not starting with 5, to avoid UAE collision)
        if ( strlen( $digits ) === 10 && substr( $digits, 0, 1 ) !== '5' ) {
            return '+91' . $digits;
        }

        // Assume digits already contain country code.
        return '+' . $digits;
    }

    /**
     * Validate E.164 format.
     *
     * E.164: +, 1-3 digit country code, subscriber number, total 8-15 digits.
     *
     * @param string $phone Phone number.
     * @return bool
     */
    public static function is_valid_e164( $phone ) {
        return (bool) preg_match( '/^\+[1-9]\d{6,14}$/', $phone );
    }

    /**
     * Create the database index for fast phone lookups.
     *
     * Should be called during plugin activation or migration.
     */
    public static function create_index() {
        global $wpdb;

        $existing = $wpdb->get_results(
            "SHOW INDEX FROM {$wpdb->usermeta} WHERE Key_name = 'idx_phone_login'"
        );

        if ( ! empty( $existing ) ) {
            return;
        }

        $wpdb->query(
            "CREATE INDEX idx_phone_login
             ON {$wpdb->usermeta} (meta_key(20), meta_value(32))"
        );
    }

    /**
     * Generate a nonce for phone auth AJAX requests.
     *
     * @return string
     */
    public static function create_nonce() {
        return wp_create_nonce( 'phone_auth_nonce' );
    }

    /**
     * Verify phone auth nonce.
     *
     * @param string $nonce Nonce value.
     * @return bool
     */
    public static function verify_nonce( $nonce ) {
        return (bool) wp_verify_nonce( $nonce, 'phone_auth_nonce' );
    }
}

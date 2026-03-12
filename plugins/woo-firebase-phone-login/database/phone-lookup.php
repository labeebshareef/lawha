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
     * Only handles format cleanup — no country-code guessing.
     * Firebase's intl-tel-input already provides E.164 from the frontend.
     *
     * @param string $phone Raw phone input.
     * @return string Normalized phone (E.164 if possible).
     */
    public static function normalize_phone( $phone ) {
        $phone = trim( $phone );

        // Strip formatting characters (spaces, dashes, parens, dots)
        $clean = preg_replace( '/[\s\-\(\)\.]+/', '', $phone );

        // 00-prefix → +
        if ( substr( $clean, 0, 2 ) === '00' ) {
            return '+' . substr( $clean, 2 );
        }

        // Already E.164
        if ( substr( $clean, 0, 1 ) === '+' ) {
            return $clean;
        }

        // Cannot safely normalize without a country code — return as-is
        // (will fail is_valid_e164() check downstream)
        return $phone;
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

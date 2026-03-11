<?php
/**
 * Migration — Consolidate phone data to billing_phone.
 *
 * Steps:
 *   1. Normalize all billing_phone values to E.164 (+971 UAE, +91 India)
 *   2. Copy wfpl_phone → billing_phone where billing_phone is empty
 *   3. Detect & report duplicate phone numbers
 *   4. Rename wfpl_phone_verified → phone_verified
 *   5. Create database index for fast phone lookups
 *   6. Clean up stale wfpl_* meta keys
 *
 * Usage: Load this file via admin action or WP-CLI.
 * The migrate() method is idempotent and safe to run multiple times.
 *
 * @package PhoneAuth\Database
 */

namespace PhoneAuth\Database;

defined( 'ABSPATH' ) || exit;

class Migration {

    /**
     * Results log.
     *
     * @var array
     */
    private static $log = array();

    /**
     * Run the full migration.
     *
     * @return array Migration results log.
     */
    public static function migrate() {
        self::$log = array();

        self::log( '=== Phone Auth Migration Started ===' );
        self::log( 'Time: ' . gmdate( 'Y-m-d H:i:s' ) );

        // Step 1: Copy wfpl_phone → billing_phone where billing_phone is empty or missing.
        self::step_copy_wfpl_to_billing();

        // Step 2: Normalize all billing_phone values to E.164.
        self::step_normalize_phones();

        // Step 3: Detect duplicate phone numbers.
        self::step_detect_duplicates();

        // Step 4: Rename wfpl_phone_verified → phone_verified.
        self::step_rename_verified_flag();

        // Step 5: Create database index.
        self::step_create_index();

        // Step 6: Mark migration version.
        update_option( 'phone_auth_migration_version', '2.0.0' );
        update_option( 'phone_auth_migration_date', gmdate( 'Y-m-d H:i:s' ) );

        self::log( '=== Migration Complete ===' );

        return self::$log;
    }

    /**
     * Step 1: Copy wfpl_phone → billing_phone where billing_phone is empty.
     */
    private static function step_copy_wfpl_to_billing() {
        global $wpdb;

        self::log( '--- Step 1: Copy wfpl_phone → billing_phone ---' );

        // Find users with wfpl_phone but no billing_phone.
        $users = $wpdb->get_results(
            "SELECT um.user_id, um.meta_value AS wfpl_phone
             FROM {$wpdb->usermeta} um
             WHERE um.meta_key = 'wfpl_phone'
               AND um.meta_value != ''
               AND um.user_id NOT IN (
                   SELECT user_id FROM {$wpdb->usermeta}
                   WHERE meta_key = 'billing_phone' AND meta_value != ''
               )"
        );

        $count = count( $users );
        self::log( "Found {$count} users with wfpl_phone but no billing_phone." );

        foreach ( $users as $row ) {
            $normalized = Phone_Lookup::normalize_phone( $row->wfpl_phone );
            update_user_meta( $row->user_id, 'billing_phone', $normalized );
            self::log( "  User #{$row->user_id}: set billing_phone = {$normalized}" );
        }

        self::log( "Step 1 complete: {$count} users updated." );
    }

    /**
     * Step 2: Normalize all billing_phone values to E.164.
     */
    private static function step_normalize_phones() {
        global $wpdb;

        self::log( '--- Step 2: Normalize billing_phone values ---' );

        $phones = $wpdb->get_results(
            "SELECT user_id, meta_value
             FROM {$wpdb->usermeta}
             WHERE meta_key = 'billing_phone'
               AND meta_value != ''"
        );

        $updated = 0;
        foreach ( $phones as $row ) {
            $normalized = Phone_Lookup::normalize_phone( $row->meta_value );
            if ( $normalized !== $row->meta_value ) {
                update_user_meta( $row->user_id, 'billing_phone', $normalized );
                self::log( "  User #{$row->user_id}: '{$row->meta_value}' → '{$normalized}'" );
                $updated++;
            }
        }

        self::log( "Step 2 complete: {$updated} of " . count( $phones ) . " phones normalized." );
    }

    /**
     * Step 3: Detect duplicate phone numbers and log them.
     */
    private static function step_detect_duplicates() {
        global $wpdb;

        self::log( '--- Step 3: Detect duplicate phone numbers ---' );

        $duplicates = $wpdb->get_results(
            "SELECT meta_value AS phone, GROUP_CONCAT(user_id) AS user_ids, COUNT(*) AS cnt
             FROM {$wpdb->usermeta}
             WHERE meta_key = 'billing_phone'
               AND meta_value != ''
             GROUP BY meta_value
             HAVING cnt > 1"
        );

        if ( empty( $duplicates ) ) {
            self::log( 'No duplicate phone numbers found.' );
            return;
        }

        self::log( 'WARNING: Found ' . count( $duplicates ) . ' duplicate phone numbers:' );
        foreach ( $duplicates as $dup ) {
            self::log( "  Phone: {$dup->phone} — Users: {$dup->user_ids}" );
        }
        self::log( 'ACTION REQUIRED: Manually resolve duplicate phone numbers above.' );
    }

    /**
     * Step 4: Rename wfpl_phone_verified → phone_verified.
     */
    private static function step_rename_verified_flag() {
        global $wpdb;

        self::log( '--- Step 4: Rename wfpl_phone_verified → phone_verified ---' );

        // Find users with wfpl_phone_verified but no phone_verified.
        $users = $wpdb->get_results(
            "SELECT user_id, meta_value
             FROM {$wpdb->usermeta}
             WHERE meta_key = 'wfpl_phone_verified'
               AND user_id NOT IN (
                   SELECT user_id FROM {$wpdb->usermeta}
                   WHERE meta_key = 'phone_verified'
               )"
        );

        $count = count( $users );
        foreach ( $users as $row ) {
            update_user_meta( $row->user_id, 'phone_verified', $row->meta_value );
        }

        self::log( "Step 4 complete: {$count} users migrated to phone_verified." );
    }

    /**
     * Step 5: Create database index.
     */
    private static function step_create_index() {
        self::log( '--- Step 5: Create idx_phone_login index ---' );
        Phone_Lookup::create_index();
        self::log( 'Step 5 complete.' );
    }

    /**
     * Clean up old wfpl_* meta keys (optional, run after verifying migration).
     * NOT called automatically — must be invoked manually after confirming data integrity.
     *
     * @return int Number of rows deleted.
     */
    public static function cleanup_old_meta() {
        global $wpdb;

        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->usermeta}
             WHERE meta_key IN ('wfpl_phone', 'wfpl_phone_verified', 'wfpl_firebase_uid')"
        );

        return (int) $deleted;
    }

    /**
     * Check if migration has been run.
     *
     * @return bool
     */
    public static function is_migrated() {
        return (bool) get_option( 'phone_auth_migration_version', false );
    }

    /**
     * Add a log entry.
     *
     * @param string $message Log message.
     */
    private static function log( $message ) {
        self::$log[] = $message;
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::log( $message );
        }
    }
}

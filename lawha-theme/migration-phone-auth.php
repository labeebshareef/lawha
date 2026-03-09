<?php
/**
 * LAWHA Phone Auth — Migration & Data Cleanup Script
 *
 * Run via WP-CLI:   wp eval-file migration-phone-auth.php
 * Or place in theme and run from admin:
 *   add_action('admin_init', function(){ include 'migration-phone-auth.php'; });
 *
 * Tasks:
 *   1. Normalize all phone numbers in user meta to E.164
 *   2. Detect & report duplicate phone accounts
 *   3. Sync wfpl_phone → billing_phone (and vice versa)
 *   4. Add missing wfpl_phone_verified meta
 *   5. Create recommended DB index
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    // Allow running via WP-CLI eval-file
    if ( ! defined( 'WP_CLI' ) ) {
        exit( 'Run this via WP-CLI: wp eval-file migration-phone-auth.php' );
    }
}

/* ================================================================
 *  Configuration
 * ============================================================= */
$DRY_RUN = true;  // Set to false to apply changes.
$LOG     = array();

function migration_log( $msg, &$log ) {
    $log[] = $msg;
    if ( defined( 'WP_CLI' ) ) {
        WP_CLI::log( $msg );
    }
}

/* ================================================================
 *  Step 1: Normalize phone numbers to E.164
 * ============================================================= */
function step1_normalize_phones( $dry_run, &$log ) {
    global $wpdb;

    migration_log( '--- Step 1: Normalize phone numbers to E.164 ---', $log );

    $phone_metas = $wpdb->get_results(
        "SELECT um.umeta_id, um.user_id, um.meta_key, um.meta_value
         FROM {$wpdb->usermeta} um
         WHERE um.meta_key IN ('wfpl_phone', 'billing_phone')
         AND um.meta_value != ''
         ORDER BY um.user_id"
    );

    $updated = 0;
    foreach ( $phone_metas as $meta ) {
        $original   = $meta->meta_value;
        $normalized = normalize_phone_e164( $original );

        if ( $normalized !== $original ) {
            migration_log(
                sprintf( '  User #%d [%s]: "%s" → "%s"', $meta->user_id, $meta->meta_key, $original, $normalized ),
                $log
            );
            if ( ! $dry_run ) {
                $wpdb->update(
                    $wpdb->usermeta,
                    array( 'meta_value' => $normalized ),
                    array( 'umeta_id'   => $meta->umeta_id )
                );
            }
            $updated++;
        }
    }

    migration_log( sprintf( '  Total to normalize: %d %s', $updated, $dry_run ? '(DRY RUN)' : '(APPLIED)' ), $log );
}

/**
 * Basic E.164 normalization:
 * - Strip spaces, dashes, parens, dots
 * - Ensure leading +
 * - If no country code and starts with 0, assume Saudi Arabia (+966)
 */
function normalize_phone_e164( $phone ) {
    // Remove all non-digit except leading +
    $has_plus = ( substr( $phone, 0, 1 ) === '+' );
    $digits   = preg_replace( '/[^\d]/', '', $phone );

    if ( empty( $digits ) ) {
        return $phone; // Can't normalize empty
    }

    if ( $has_plus ) {
        return '+' . $digits;
    }

    // If starts with 00 (international prefix), replace with +
    if ( substr( $digits, 0, 2 ) === '00' ) {
        return '+' . substr( $digits, 2 );
    }

    // Saudi local number: 05xxxxxxxx → +9665xxxxxxxx
    if ( substr( $digits, 0, 1 ) === '0' && strlen( $digits ) === 10 ) {
        return '+966' . substr( $digits, 1 );
    }

    // Already has country code (no leading 0, 10+ digits)
    if ( strlen( $digits ) >= 10 ) {
        return '+' . $digits;
    }

    // Can't determine — return with +
    return '+' . $digits;
}


/* ================================================================
 *  Step 2: Detect duplicate phone accounts
 * ============================================================= */
function step2_detect_duplicates( &$log ) {
    global $wpdb;

    migration_log( '--- Step 2: Detect duplicate phone accounts ---', $log );

    $duplicates = $wpdb->get_results(
        "SELECT meta_value AS phone, GROUP_CONCAT(user_id ORDER BY user_id) AS user_ids, COUNT(*) AS cnt
         FROM {$wpdb->usermeta}
         WHERE meta_key = 'wfpl_phone'
         AND meta_value != ''
         GROUP BY meta_value
         HAVING cnt > 1
         ORDER BY cnt DESC"
    );

    if ( empty( $duplicates ) ) {
        migration_log( '  No duplicate phone numbers found.', $log );
        return;
    }

    migration_log( sprintf( '  Found %d phone numbers with duplicates:', count( $duplicates ) ), $log );
    foreach ( $duplicates as $dup ) {
        $user_ids = explode( ',', $dup->user_ids );
        $details  = array();
        foreach ( $user_ids as $uid ) {
            $user = get_userdata( (int) $uid );
            if ( $user ) {
                $order_count = wc_get_customer_order_count( (int) $uid );
                $details[]   = sprintf( '#%d (%s, %d orders)', $uid, $user->user_email, $order_count );
            }
        }
        migration_log( sprintf( '  Phone %s: %s', $dup->phone, implode( ' | ', $details ) ), $log );
    }

    migration_log( '  ⚠ MANUAL ACTION REQUIRED: Merge these accounts or remove duplicates.', $log );
    migration_log( '  Recommendation: Keep the account with the most orders and merge data from others.', $log );
}


/* ================================================================
 *  Step 3: Sync wfpl_phone ↔ billing_phone
 * ============================================================= */
function step3_sync_phones( $dry_run, &$log ) {
    global $wpdb;

    migration_log( '--- Step 3: Sync wfpl_phone ↔ billing_phone ---', $log );

    // Users with wfpl_phone but missing/empty billing_phone
    $wfpl_only = $wpdb->get_results(
        "SELECT u.ID, wfpl.meta_value AS wfpl_phone, bp.meta_value AS billing_phone
         FROM {$wpdb->users} u
         INNER JOIN {$wpdb->usermeta} wfpl ON u.ID = wfpl.user_id AND wfpl.meta_key = 'wfpl_phone' AND wfpl.meta_value != ''
         LEFT JOIN {$wpdb->usermeta} bp ON u.ID = bp.user_id AND bp.meta_key = 'billing_phone'
         WHERE bp.meta_value IS NULL OR bp.meta_value = ''"
    );

    $synced = 0;
    foreach ( $wfpl_only as $row ) {
        migration_log( sprintf( '  User #%d: billing_phone ← wfpl_phone (%s)', $row->ID, $row->wfpl_phone ), $log );
        if ( ! $dry_run ) {
            update_user_meta( $row->ID, 'billing_phone', $row->wfpl_phone );
        }
        $synced++;
    }

    // Users with billing_phone but missing wfpl_phone
    $billing_only = $wpdb->get_results(
        "SELECT u.ID, bp.meta_value AS billing_phone, wfpl.meta_value AS wfpl_phone
         FROM {$wpdb->users} u
         INNER JOIN {$wpdb->usermeta} bp ON u.ID = bp.user_id AND bp.meta_key = 'billing_phone' AND bp.meta_value != ''
         LEFT JOIN {$wpdb->usermeta} wfpl ON u.ID = wfpl.user_id AND wfpl.meta_key = 'wfpl_phone'
         WHERE wfpl.meta_value IS NULL OR wfpl.meta_value = ''"
    );

    foreach ( $billing_only as $row ) {
        $normalized = normalize_phone_e164( $row->billing_phone );
        migration_log( sprintf( '  User #%d: wfpl_phone ← billing_phone (%s)', $row->ID, $normalized ), $log );
        if ( ! $dry_run ) {
            update_user_meta( $row->ID, 'wfpl_phone', $normalized );
        }
        $synced++;
    }

    migration_log( sprintf( '  Total synced: %d %s', $synced, $dry_run ? '(DRY RUN)' : '(APPLIED)' ), $log );
}


/* ================================================================
 *  Step 4: Add missing wfpl_phone_verified meta
 * ============================================================= */
function step4_add_verified_flag( $dry_run, &$log ) {
    global $wpdb;

    migration_log( '--- Step 4: Add wfpl_phone_verified meta ---', $log );

    // Users with wfpl_phone but no verified flag
    $unverified = $wpdb->get_results(
        "SELECT u.ID
         FROM {$wpdb->users} u
         INNER JOIN {$wpdb->usermeta} wfpl ON u.ID = wfpl.user_id AND wfpl.meta_key = 'wfpl_phone' AND wfpl.meta_value != ''
         LEFT JOIN {$wpdb->usermeta} ver ON u.ID = ver.user_id AND ver.meta_key = 'wfpl_phone_verified'
         WHERE ver.meta_value IS NULL"
    );

    $count = count( $unverified );
    migration_log( sprintf( '  Users needing verified flag: %d', $count ), $log );

    if ( ! $dry_run ) {
        foreach ( $unverified as $row ) {
            update_user_meta( $row->ID, 'wfpl_phone_verified', '1' );
        }
    }

    migration_log( sprintf( '  %s', $dry_run ? '(DRY RUN)' : '(APPLIED)' ), $log );
}


/* ================================================================
 *  Step 5: Create DB index for phone lookups
 * ============================================================= */
function step5_create_index( $dry_run, &$log ) {
    global $wpdb;

    migration_log( '--- Step 5: Create DB index for phone lookups ---', $log );

    // Check if index already exists
    $existing = $wpdb->get_results(
        "SHOW INDEX FROM {$wpdb->usermeta} WHERE Key_name = 'idx_wfpl_phone_lookup'"
    );

    if ( ! empty( $existing ) ) {
        migration_log( '  Index idx_wfpl_phone_lookup already exists. Skipping.', $log );
        return;
    }

    if ( ! $dry_run ) {
        $wpdb->query(
            "CREATE INDEX idx_wfpl_phone_lookup
             ON {$wpdb->usermeta} (meta_key(20), meta_value(20));"
        );
        migration_log( '  Index idx_wfpl_phone_lookup created.', $log );
    } else {
        migration_log( '  Would create: CREATE INDEX idx_wfpl_phone_lookup ON usermeta(meta_key(20), meta_value(20))', $log );
        migration_log( '  (DRY RUN)', $log );
    }
}


/* ================================================================
 *  Execute all steps
 * ============================================================= */
migration_log( '========================================', $LOG );
migration_log( 'LAWHA Phone Auth Migration Script', $LOG );
migration_log( 'Mode: ' . ( $DRY_RUN ? 'DRY RUN (no changes)' : 'LIVE (applying changes)' ), $LOG );
migration_log( 'Date: ' . current_time( 'mysql' ), $LOG );
migration_log( '========================================', $LOG );

step1_normalize_phones( $DRY_RUN, $LOG );
step2_detect_duplicates( $LOG );
step3_sync_phones( $DRY_RUN, $LOG );
step4_add_verified_flag( $DRY_RUN, $LOG );
step5_create_index( $DRY_RUN, $LOG );

migration_log( '========================================', $LOG );
migration_log( 'Migration complete. Review the output above.', $LOG );
if ( $DRY_RUN ) {
    migration_log( 'To apply changes, set $DRY_RUN = false and re-run.', $LOG );
}
migration_log( '========================================', $LOG );

// If run from admin (not CLI), display log
if ( ! defined( 'WP_CLI' ) && is_admin() ) {
    echo '<pre style="background:#1a1a1a;color:#d7a37b;padding:20px;font-size:13px;border-radius:8px;max-height:600px;overflow:auto;">';
    echo esc_html( implode( "\n", $LOG ) );
    echo '</pre>';
}

<?php
/**
 * DEPRECATED — Legacy migration script.
 *
 * This file has been superseded by the plugin's migration module:
 *   plugins/woo-firebase-phone-login/database/migration.php
 *   Class: \PhoneAuth\Database\Migration::migrate()
 *
 * To run the migration, use the plugin's admin notice or WP-CLI:
 *   wp eval 'echo implode("\n", \PhoneAuth\Database\Migration::migrate());'
 *
 * @package LAWHA
 * @deprecated 2.0.0 Use \PhoneAuth\Database\Migration::migrate() instead.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'WP_CLI' ) && class_exists( '\\PhoneAuth\\Database\\Migration' ) ) {
    WP_CLI::warning( 'This script is deprecated. Running plugin migration instead...' );
    $log = \PhoneAuth\Database\Migration::migrate();
    foreach ( $log as $line ) {
        WP_CLI::log( $line );
    }
} elseif ( is_admin() ) {
    echo '<div class="notice notice-warning"><p>';
    echo '<strong>Deprecated:</strong> This migration script has been replaced. ';
    echo 'Use <code>\\PhoneAuth\\Database\\Migration::migrate()</code> from the woo-firebase-phone-login plugin.';
    echo '</p></div>';
}

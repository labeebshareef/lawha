<?php
/**
 * Plugin Name:       WooCommerce Firebase Phone Login
 * Plugin URI:        https://github.com/lawhahijabs/woo-firebase-phone-login
 * Description:       Phone number authentication for WooCommerce using Firebase OTP verification. WordPress handles all user/session management; Firebase is OTP-only.
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Lawha
 * Author URI:        https://lawhahijabs.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-firebase-phone-login
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   8.5
 *
 * @package PhoneAuth
 */

defined( 'ABSPATH' ) || exit;

/*--------------------------------------------------------------
 * Constants
 *------------------------------------------------------------*/
define( 'WFPL_VERSION', '2.0.0' );
define( 'WFPL_PLUGIN_FILE', __FILE__ );
define( 'WFPL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WFPL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WFPL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/*--------------------------------------------------------------
 * Load new modular files
 *------------------------------------------------------------*/
require_once WFPL_PLUGIN_DIR . 'database/phone-lookup.php';
require_once WFPL_PLUGIN_DIR . 'database/migration.php';
require_once WFPL_PLUGIN_DIR . 'otp/send-otp.php';
require_once WFPL_PLUGIN_DIR . 'otp/verify-otp.php';
require_once WFPL_PLUGIN_DIR . 'auth/login-controller.php';
require_once WFPL_PLUGIN_DIR . 'auth/register-controller.php';
require_once WFPL_PLUGIN_DIR . 'api/ajax-endpoints.php';
require_once WFPL_PLUGIN_DIR . 'ui/asset-loader.php';

/*--------------------------------------------------------------
 * Legacy autoloader — kept for admin settings page & backward compat
 *------------------------------------------------------------*/
spl_autoload_register( function ( $class ) {

    $prefix = 'WFPL\\';
    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }

    $relative = substr( $class, strlen( $prefix ) );

    $map = array(
        'Admin\\'    => WFPL_PLUGIN_DIR . 'admin/',
        'Frontend\\' => WFPL_PLUGIN_DIR . 'public/',
        'API\\'      => WFPL_PLUGIN_DIR . 'api/',
    );

    $file = '';
    foreach ( $map as $ns => $dir ) {
        if ( strpos( $relative, $ns ) === 0 ) {
            $relative = substr( $relative, strlen( $ns ) );
            $file     = $dir . 'class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
            break;
        }
    }

    if ( empty( $file ) ) {
        $file = WFPL_PLUGIN_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
    }

    if ( file_exists( $file ) ) {
        require_once $file;
    }
});

/*--------------------------------------------------------------
 * WooCommerce HPOS compatibility declaration
 *------------------------------------------------------------*/
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});

/*--------------------------------------------------------------
 * Activation hook
 *------------------------------------------------------------*/
function wfpl_activate() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            esc_html__( 'WooCommerce Firebase Phone Login requires WooCommerce to be installed and active.', 'woo-firebase-phone-login' ),
            'Plugin Dependency Check',
            array( 'back_link' => true )
        );
    }

    // Set default options on first activation.
    $defaults = array(
        'wfpl_firebase_api_key'       => '',
        'wfpl_firebase_project_id'    => '',
        'wfpl_firebase_auth_domain'   => '',
    );

    foreach ( $defaults as $key => $value ) {
        if ( false === get_option( $key ) ) {
            update_option( $key, $value );
        }
    }

    // Create database index for fast phone lookups.
    \PhoneAuth\Database\Phone_Lookup::create_index();
}
register_activation_hook( __FILE__, 'wfpl_activate' );

/**
 * Main plugin class — Singleton.
 */
final class WFPL_Plugin {

    /** @var WFPL_Plugin|null */
    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if ( ! $this->check_dependencies() ) {
            return;
        }
        $this->init_hooks();
    }

    private function check_dependencies() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', function () {
                echo '<div class="notice notice-error"><p>';
                esc_html_e( 'WooCommerce Firebase Phone Login requires WooCommerce to be installed and active.', 'woo-firebase-phone-login' );
                echo '</p></div>';
            });
            return false;
        }
        return true;
    }

    private function init_hooks() {
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'init_modules' ) );
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'woo-firebase-phone-login',
            false,
            dirname( WFPL_PLUGIN_BASENAME ) . '/languages/'
        );
    }

    /**
     * Initialise all modules.
     *
     * Architecture:
     *   - PhoneAuth\API\Ajax_Endpoints  → AJAX handlers (login, register, check, OTP, forgot)
     *   - PhoneAuth\UI\Asset_Loader     → Enqueues Firebase SDK + phone-auth JS (headless)
     *   - WFPL\Admin\Settings_Page      → Admin settings (kept from v1)
     */
    public function init_modules() {
        // New modules (static classes).
        \PhoneAuth\API\Ajax_Endpoints::init();
        \PhoneAuth\UI\Asset_Loader::init();

        // Admin settings page (legacy, still useful).
        if ( is_admin() ) {
            new \WFPL\Admin\Settings_Page();
            $this->maybe_show_firebase_notice();
            $this->maybe_show_migration_notice();
        }
    }

    private function maybe_show_firebase_notice() {
        if ( \PhoneAuth\OTP\Send_OTP::is_firebase_configured() ) {
            return;
        }

        add_action( 'admin_notices', function () {
            $url = admin_url( 'admin.php?page=wc-settings&tab=wfpl' );
            echo '<div class="notice notice-warning is-dismissible"><p>';
            echo '<strong>' . esc_html__( 'Phone Auth:', 'woo-firebase-phone-login' ) . '</strong> ';
            echo esc_html__( 'Firebase API keys are not configured. Phone authentication will not work.', 'woo-firebase-phone-login' );
            echo ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Configure now &rarr;', 'woo-firebase-phone-login' ) . '</a>';
            echo '</p></div>';
        });
    }

    private function maybe_show_migration_notice() {
        if ( \PhoneAuth\Database\Migration::is_migrated() ) {
            return;
        }

        // Only show if there are legacy wfpl_phone entries to migrate.
        global $wpdb;
        $legacy_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'wfpl_phone' AND meta_value != ''"
        );

        if ( $legacy_count === 0 ) {
            return;
        }

        add_action( 'admin_notices', function () use ( $legacy_count ) {
            echo '<div class="notice notice-info is-dismissible"><p>';
            echo '<strong>' . esc_html__( 'Phone Auth Migration:', 'woo-firebase-phone-login' ) . '</strong> ';
            echo esc_html( sprintf(
                /* translators: %d: number of users */
                __( '%d users have legacy phone data (wfpl_phone) that should be migrated to billing_phone. Run the migration from Tools or WP-CLI.', 'woo-firebase-phone-login' ),
                $legacy_count
            ) );
            echo '</p></div>';
        });
    }
}

/*--------------------------------------------------------------
 * Boot the plugin after all plugins loaded.
 *------------------------------------------------------------*/
add_action( 'plugins_loaded', function () {
    WFPL_Plugin::instance();
});

/*--------------------------------------------------------------
 * Global helper functions — backward compatibility
 *------------------------------------------------------------*/

/**
 * Get a plugin option (backward compat wrapper).
 */
function wfpl_get_option( $key, $default = '' ) {
    return get_option( 'wfpl_' . ltrim( $key, 'wfpl_' ), $default );
}

/**
 * Check if Firebase is configured.
 */
function wfpl_is_firebase_configured() {
    return \PhoneAuth\OTP\Send_OTP::is_firebase_configured();
}

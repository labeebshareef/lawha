<?php
/**
 * Plugin Name:       WooCommerce Firebase Phone Login
 * Plugin URI:        https://github.com/lawhahijabs/woo-firebase-phone-login
 * Description:       Phone number based login & registration for WooCommerce using Firebase OTP authentication. Supports headless APIs, checkout integration, and developer extensibility.
 * Version:           1.0.0
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
 * @package WFPL
 */

defined( 'ABSPATH' ) || exit;

/*--------------------------------------------------------------
 * Constants
 *------------------------------------------------------------*/
define( 'WFPL_VERSION', '1.0.0' );
define( 'WFPL_PLUGIN_FILE', __FILE__ );
define( 'WFPL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WFPL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WFPL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/*--------------------------------------------------------------
 * Autoloader
 *------------------------------------------------------------*/
spl_autoload_register( function ( $class ) {

    $prefix = 'WFPL\\';
    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }

    $relative = substr( $class, strlen( $prefix ) );

    // Map namespace segments to directories.
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

    // Default: includes directory.
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
 * Dependency check & bootstrap
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
        'wfpl_enable_login'           => 'yes',
        'wfpl_enable_registration'    => 'yes',
        'wfpl_enable_checkout_login'  => 'yes',
        'wfpl_auto_create_account'    => 'yes',
        'wfpl_enable_popup'           => 'no',
        'wfpl_firebase_api_key'       => '',
        'wfpl_firebase_project_id'    => '',
        'wfpl_firebase_auth_domain'   => '',
        'wfpl_otp_expiration'         => 300,
        'wfpl_max_otp_per_hour'       => 5,
    );

    foreach ( $defaults as $key => $value ) {
        if ( false === get_option( $key ) ) {
            update_option( $key, $value );
        }
    }
}
register_activation_hook( __FILE__, 'wfpl_activate' );

/**
 * Main plugin class — Singleton.
 */
final class WFPL_Plugin {

    /** @var WFPL_Plugin|null */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @return WFPL_Plugin
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor — hooks everything.
     */
    private function __construct() {
        $this->check_dependencies();
        $this->init_hooks();
    }

    /**
     * Bail early if WooCommerce is not active.
     */
    private function check_dependencies() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', function () {
                echo '<div class="notice notice-error"><p>';
                esc_html_e( 'WooCommerce Firebase Phone Login requires WooCommerce to be installed and active.', 'woo-firebase-phone-login' );
                echo '</p></div>';
            });
            return;
        }
    }

    /**
     * Register all hooks.
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'init_modules' ) );
    }

    /**
     * Load plugin text domain.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'woo-firebase-phone-login',
            false,
            dirname( WFPL_PLUGIN_BASENAME ) . '/languages/'
        );
    }

    /**
     * Initialise all modules.
     */
    public function init_modules() {
        // Core.
        new \WFPL\Helpers();
        new \WFPL\Firebase_Auth();
        new \WFPL\User_Handler();
        new \WFPL\Auth_Controller();

        // Admin.
        if ( is_admin() ) {
            new \WFPL\Admin\Settings_Page();
        }

        // Frontend.
        new \WFPL\Frontend\Login_UI();
        new \WFPL\Frontend\Shortcodes();
        new \WFPL\Frontend\Ajax_Handlers();
        new \WFPL\Frontend\Checkout_Enforcer();

        // REST API.
        new \WFPL\API\Rest_API();
    }
}

/*--------------------------------------------------------------
 * Boot the plugin after all plugins loaded.
 *------------------------------------------------------------*/
add_action( 'plugins_loaded', function () {
    WFPL_Plugin::instance();
});

/*--------------------------------------------------------------
 * Load global public API functions.
 *------------------------------------------------------------*/
require_once WFPL_PLUGIN_DIR . 'includes/public-api.php';

<?php
/**
 * Shortcodes and block registration.
 *
 * @package WFPL
 */

namespace WFPL\Frontend;

use WFPL\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class Shortcodes
 */
class Shortcodes {

    /**
     * Constructor.
     */
    public function __construct() {
        add_shortcode( 'wfpl_login_form', array( $this, 'render_shortcode' ) );
        add_action( 'init', array( $this, 'register_block' ) );
    }

    /**
     * Render the [wfpl_login_form] shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_shortcode( $atts ) {
        if ( is_user_logged_in() ) {
            return '<p>' . esc_html__( 'You are already logged in.', 'woo-firebase-phone-login' ) . '</p>';
        }

        if ( ! Helpers::is_feature_enabled( 'enable_login' ) ) {
            return '';
        }

        if ( ! Helpers::is_firebase_configured() ) {
            return '<!-- WFPL: Firebase not configured -->';
        }

        // Ensure assets are loaded.
        $this->ensure_assets();

        return Login_UI::get_login_form_html( 'shortcode' );
    }

    /**
     * Register Gutenberg block.
     */
    public function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        register_block_type( 'wfpl/phone-login', array(
            'render_callback' => array( $this, 'render_shortcode' ),
            'attributes'      => array(),
        ) );
    }

    /**
     * Make sure frontend assets are enqueued.
     */
    private function ensure_assets() {
        if ( ! wp_script_is( 'wfpl-auth', 'enqueued' ) ) {
            $login_ui = new Login_UI();
            $login_ui->register_assets();
        }
    }
}

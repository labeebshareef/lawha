<?php
/**
 * DEPRECATED — Legacy Shortcodes stub.
 *
 * The [wfpl_login_form] shortcode now renders a simple message
 * directing users to use the theme's login form.
 *
 * @package WFPL
 * @deprecated 2.0.0
 */

namespace WFPL\Frontend;

defined( 'ABSPATH' ) || exit;

class Shortcodes {

    public function __construct() {
        add_shortcode( 'wfpl_login_form', array( $this, 'render_shortcode' ) );
    }

    public function render_shortcode( $atts ) {
        if ( is_user_logged_in() ) {
            return '<p>' . esc_html__( 'You are already logged in.', 'woo-firebase-phone-login' ) . '</p>';
        }

        $myaccount_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
        return '<p><a href="' . esc_url( $myaccount_url ) . '">' . esc_html__( 'Log in with your phone number', 'woo-firebase-phone-login' ) . '</a></p>';
    }
}

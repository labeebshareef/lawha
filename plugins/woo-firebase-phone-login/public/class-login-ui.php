<?php
/**
 * Frontend login UI — renders phone login forms on WooCommerce pages.
 *
 * @package WFPL
 */

namespace WFPL\Frontend;

use WFPL\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class Login_UI
 */
class Login_UI {

    /**
     * Constructor.
     */
    public function __construct() {
        if ( ! Helpers::is_feature_enabled( 'enable_login' ) ) {
            return;
        }

        // Enqueue assets.
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

        // WooCommerce My Account login form.
        add_action( 'woocommerce_login_form_start', array( $this, 'render_phone_login' ) );

        // WooCommerce checkout — login prompt.
        if ( Helpers::is_feature_enabled( 'enable_checkout_login' ) ) {
            add_action( 'woocommerce_before_checkout_form', array( $this, 'render_checkout_phone_login' ), 5 );
        }

        // Popup login (site-wide).
        if ( Helpers::is_feature_enabled( 'enable_popup' ) ) {
            add_action( 'wp_footer', array( $this, 'render_popup_modal' ) );
        }
    }

    /*--------------------------------------------------------------
     * Asset registration
     *------------------------------------------------------------*/

    /**
     * Register and conditionally enqueue scripts & styles.
     */
    public function register_assets() {
        // Only load on relevant pages.
        if ( ! $this->should_load_assets() ) {
            return;
        }

        // CSS.
        wp_enqueue_style(
            'wfpl-login',
            WFPL_PLUGIN_URL . 'assets/css/login.css',
            array(),
            WFPL_VERSION
        );

        // intl-tel-input CSS (CDN).
        wp_enqueue_style(
            'intl-tel-input',
            'https://cdn.jsdelivr.net/npm/intl-tel-input@21.1.1/build/css/intlTelInput.min.css',
            array(),
            '21.1.1'
        );

        // Firebase JS SDK (CDN — modular compat bundle).
        wp_enqueue_script(
            'firebase-app',
            'https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js',
            array(),
            '10.12.0',
            true
        );

        wp_enqueue_script(
            'firebase-auth',
            'https://www.gstatic.com/firebasejs/10.12.0/firebase-auth-compat.js',
            array( 'firebase-app' ),
            '10.12.0',
            true
        );

        // intl-tel-input JS.
        wp_enqueue_script(
            'intl-tel-input',
            'https://cdn.jsdelivr.net/npm/intl-tel-input@21.1.1/build/js/intlTelInput.min.js',
            array(),
            '21.1.1',
            true
        );

        // Plugin main JS.
        wp_enqueue_script(
            'wfpl-auth',
            WFPL_PLUGIN_URL . 'assets/js/firebase-auth.js',
            array( 'jquery', 'firebase-app', 'firebase-auth', 'intl-tel-input' ),
            WFPL_VERSION,
            true
        );

        // Localize script with config.
        wp_localize_script( 'wfpl-auth', 'wfpl_config', array(
            'ajax_url'        => admin_url( 'admin-ajax.php' ),
            'rest_url'        => esc_url_raw( rest_url( 'wfpl/v1/' ) ),
            'nonce'           => Helpers::create_nonce(),
            'rest_nonce'      => wp_create_nonce( 'wp_rest' ),
            'firebase'        => Helpers::get_firebase_config(),
            'otp_expiration'  => absint( Helpers::get_option( 'otp_expiration', 300 ) ),
            'i18n'            => array(
                'sending'        => __( 'Sending OTP…', 'woo-firebase-phone-login' ),
                'verifying'      => __( 'Verifying…', 'woo-firebase-phone-login' ),
                'success'        => __( 'Login successful! Redirecting…', 'woo-firebase-phone-login' ),
                'invalid_phone'  => __( 'Please enter a valid phone number.', 'woo-firebase-phone-login' ),
                'otp_sent'       => __( 'OTP sent! Check your phone.', 'woo-firebase-phone-login' ),
                'otp_failed'     => __( 'Failed to send OTP. Please try again.', 'woo-firebase-phone-login' ),
                'verify_failed'  => __( 'Verification failed. Please try again.', 'woo-firebase-phone-login' ),
                'resend'         => __( 'Resend OTP', 'woo-firebase-phone-login' ),
                'resend_in'      => __( 'Resend in %s s', 'woo-firebase-phone-login' ),
                'enter_otp'      => __( 'Enter the 6-digit code', 'woo-firebase-phone-login' ),
            ),
            'redirect_url'    => $this->get_redirect_url(),
        ) );

        // Checkout integration script.
        if ( is_checkout() ) {
            wp_enqueue_script(
                'wfpl-checkout',
                WFPL_PLUGIN_URL . 'assets/js/checkout-integration.js',
                array( 'wfpl-auth' ),
                WFPL_VERSION,
                true
            );
        }
    }

    /**
     * Determine if we should load assets on this page.
     *
     * @return bool
     */
    private function should_load_assets() {
        if ( is_user_logged_in() ) {
            return false;
        }

        // Load on My Account, Checkout, or if a shortcode/popup is expected.
        if ( is_account_page() || is_checkout() ) {
            return true;
        }

        // Popup mode — load everywhere.
        if ( Helpers::is_feature_enabled( 'enable_popup' ) ) {
            return true;
        }

        // Shortcode detection (set by Shortcodes class).
        global $post;
        if ( $post && has_shortcode( $post->post_content ?? '', 'wfpl_login_form' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Determine redirect URL after login.
     *
     * @return string
     */
    private function get_redirect_url() {
        if ( is_checkout() ) {
            return wc_get_checkout_url();
        }
        return wc_get_page_permalink( 'myaccount' );
    }

    /*--------------------------------------------------------------
     * Render login form
     *------------------------------------------------------------*/

    /**
     * Render the phone login form (My Account page).
     */
    public function render_phone_login() {
        if ( is_user_logged_in() ) {
            return;
        }
        echo self::get_login_form_html( 'myaccount' );
    }

    /**
     * Render phone login for checkout.
     */
    public function render_checkout_phone_login() {
        if ( is_user_logged_in() ) {
            return;
        }
        echo '<div id="wfpl-checkout-login-wrapper">';
        echo '<h3>' . esc_html__( 'Quick Login with Phone', 'woo-firebase-phone-login' ) . '</h3>';
        echo self::get_login_form_html( 'checkout' );
        echo '</div>';
    }

    /**
     * Render popup modal.
     */
    public function render_popup_modal() {
        if ( is_user_logged_in() ) {
            return;
        }
        ?>
        <div id="wfpl-modal-overlay" class="wfpl-modal-overlay" style="display:none;">
            <div class="wfpl-modal">
                <button class="wfpl-modal-close" aria-label="<?php esc_attr_e( 'Close', 'woo-firebase-phone-login' ); ?>">&times;</button>
                <?php echo self::get_login_form_html( 'popup' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </div>
        <?php
    }

    /**
     * Generate the login form HTML.
     *
     * @param string $context Context: 'myaccount', 'checkout', 'popup', 'shortcode'.
     * @return string HTML.
     */
    public static function get_login_form_html( $context = 'myaccount' ) {
        ob_start();
        ?>
        <div class="wfpl-login-container" data-context="<?php echo esc_attr( $context ); ?>">

            <!-- Step 1: Phone Number -->
            <div class="wfpl-step wfpl-step-phone wfpl-active" data-step="phone">
                <div class="wfpl-form-header">
                    <div class="wfpl-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </div>
                    <h4 class="wfpl-title"><?php esc_html_e( 'Login with Phone', 'woo-firebase-phone-login' ); ?></h4>
                    <p class="wfpl-subtitle"><?php esc_html_e( 'Enter your phone number to receive a verification code.', 'woo-firebase-phone-login' ); ?></p>
                </div>
                <div class="wfpl-form-body">
                    <div class="wfpl-field">
                        <input type="tel" id="wfpl-phone-input-<?php echo esc_attr( $context ); ?>" class="wfpl-phone-input" placeholder="<?php esc_attr_e( 'Phone number', 'woo-firebase-phone-login' ); ?>" autocomplete="tel" />
                    </div>
                    <div id="wfpl-recaptcha-<?php echo esc_attr( $context ); ?>" class="wfpl-recaptcha"></div>
                    <button type="button" class="wfpl-btn wfpl-btn-primary wfpl-send-otp-btn">
                        <span class="wfpl-btn-text"><?php esc_html_e( 'Send OTP', 'woo-firebase-phone-login' ); ?></span>
                        <span class="wfpl-btn-loader" style="display:none;">
                            <svg class="wfpl-spinner" width="20" height="20" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.416" stroke-dashoffset="10" stroke-linecap="round"/>
                            </svg>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Step 2: OTP Verification -->
            <div class="wfpl-step wfpl-step-otp" data-step="otp" style="display:none;">
                <div class="wfpl-form-header">
                    <div class="wfpl-icon wfpl-icon-shield">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <h4 class="wfpl-title"><?php esc_html_e( 'Verify OTP', 'woo-firebase-phone-login' ); ?></h4>
                    <p class="wfpl-subtitle wfpl-otp-subtitle"><?php esc_html_e( 'Enter the 6-digit code sent to your phone.', 'woo-firebase-phone-login' ); ?></p>
                </div>
                <div class="wfpl-form-body">
                    <div class="wfpl-otp-inputs">
                        <input type="text" class="wfpl-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" data-index="0" />
                        <input type="text" class="wfpl-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="1" />
                        <input type="text" class="wfpl-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="2" />
                        <input type="text" class="wfpl-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="3" />
                        <input type="text" class="wfpl-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="4" />
                        <input type="text" class="wfpl-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="5" />
                    </div>
                    <button type="button" class="wfpl-btn wfpl-btn-primary wfpl-verify-otp-btn">
                        <span class="wfpl-btn-text"><?php esc_html_e( 'Verify & Login', 'woo-firebase-phone-login' ); ?></span>
                        <span class="wfpl-btn-loader" style="display:none;">
                            <svg class="wfpl-spinner" width="20" height="20" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.416" stroke-dashoffset="10" stroke-linecap="round"/>
                            </svg>
                        </span>
                    </button>
                    <div class="wfpl-resend-row">
                        <span class="wfpl-timer"></span>
                        <button type="button" class="wfpl-resend-btn" style="display:none;"><?php esc_html_e( 'Resend OTP', 'woo-firebase-phone-login' ); ?></button>
                    </div>
                    <button type="button" class="wfpl-back-btn">&larr; <?php esc_html_e( 'Change phone number', 'woo-firebase-phone-login' ); ?></button>
                </div>
            </div>

            <!-- Step 3: Success -->
            <div class="wfpl-step wfpl-step-success" data-step="success" style="display:none;">
                <div class="wfpl-form-header">
                    <div class="wfpl-icon wfpl-icon-success">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <h4 class="wfpl-title"><?php esc_html_e( 'Welcome!', 'woo-firebase-phone-login' ); ?></h4>
                    <p class="wfpl-subtitle"><?php esc_html_e( 'Login successful. Redirecting…', 'woo-firebase-phone-login' ); ?></p>
                </div>
            </div>

            <!-- Messages -->
            <div class="wfpl-message" style="display:none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}

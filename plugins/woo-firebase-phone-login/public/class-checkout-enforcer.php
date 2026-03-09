<?php
/**
 * Checkout phone verification enforcer.
 *
 * Prevents guests from completing checkout without verifying their phone number.
 * Sets a WooCommerce session flag upon successful verification.
 *
 * @package WFPL
 */

namespace WFPL\Frontend;

use WFPL\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class Checkout_Enforcer
 */
class Checkout_Enforcer {

    /**
     * Session key to track verification status.
     */
    const SESSION_KEY = 'wfpl_phone_verified';

    /**
     * Constructor.
     */
    public function __construct() {
        if ( ! Helpers::is_feature_enabled( 'enable_checkout_login' ) ) {
            return;
        }

        // Server-side: Block checkout if phone not verified.
        add_action( 'woocommerce_checkout_process', array( $this, 'validate_phone_verification' ) );

        // After successful AJAX login — set session flag.
        add_action( 'wfpl_after_login', array( $this, 'set_verified_session' ), 10, 2 );

        // Inject hidden field and verification state into checkout form.
        add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'render_hidden_field' ) );

        // Save verified phone to order meta.
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_phone_to_order' ), 10, 3 );

        // Clear session after order.
        add_action( 'woocommerce_thankyou', array( $this, 'clear_session' ) );
    }

    /**
     * Validate that the customer has verified their phone before allowing checkout.
     *
     * Only enforced for guest users (non-logged-in) OR logged-in users without a verified phone.
     */
    public function validate_phone_verification() {
        // If user is already logged in AND has a verified phone, skip.
        if ( is_user_logged_in() ) {
            $phone = get_user_meta( get_current_user_id(), 'wfpl_phone', true );
            if ( ! empty( $phone ) ) {
                return;
            }
        }

        // Check session flag (server-side only — cannot be spoofed).
        if ( $this->is_phone_verified() ) {
            return;
        }

        // NOTE: We intentionally do NOT trust the hidden POST field
        // (wfpl_phone_verified) because it can be forged by the client.
        // Only the WooCommerce server-side session is trusted.

        Helpers::log( 'Checkout blocked — phone not verified.', 'checkout_enforcer' );

        wc_add_notice(
            __( 'Please verify your phone number before placing the order.', 'woo-firebase-phone-login' ),
            'error'
        );
    }

    /**
     * Set the WooCommerce session flag when phone is verified.
     *
     * Hooked to wfpl_after_login action.
     *
     * @param int    $user_id User ID.
     * @param string $phone   Phone number.
     */
    public function set_verified_session( $user_id, $phone ) {
        if ( function_exists( 'WC' ) && WC()->session ) {
            WC()->session->set( self::SESSION_KEY, $phone );
        }
    }

    /**
     * Check if phone has been verified in the current session.
     *
     * @return bool
     */
    public function is_phone_verified() {
        if ( function_exists( 'WC' ) && WC()->session ) {
            return ! empty( WC()->session->get( self::SESSION_KEY ) );
        }
        return false;
    }

    /**
     * Get the verified phone from session.
     *
     * @return string
     */
    public function get_verified_phone() {
        if ( function_exists( 'WC' ) && WC()->session ) {
            return WC()->session->get( self::SESSION_KEY, '' );
        }
        return '';
    }

    /**
     * Render a hidden field in the checkout form to track verification status.
     *
     * Also outputs a data attribute for JS to detect the state.
     *
     * @param \WC_Checkout $checkout Checkout object.
     */
    public function render_hidden_field( $checkout ) {
        $verified = $this->is_phone_verified() || ( is_user_logged_in() && get_user_meta( get_current_user_id(), 'wfpl_phone', true ) );
        $value    = $verified ? 'yes' : 'no';
        ?>
        <input type="hidden" id="wfpl_phone_verified" name="wfpl_phone_verified" value="<?php echo esc_attr( $value ); ?>" />
        <input type="hidden" id="wfpl_verified_phone" name="wfpl_verified_phone" value="<?php echo esc_attr( $this->get_verified_phone() ); ?>" />
        <?php
    }

    /**
     * Save the verified phone number to the order.
     *
     * @param int       $order_id Order ID.
     * @param array     $posted   Posted data.
     * @param \WC_Order $order    Order object.
     */
    public function save_phone_to_order( $order_id, $posted, $order ) {
        $phone = $this->get_verified_phone();
        if ( $phone ) {
            $order->update_meta_data( '_wfpl_verified_phone', $phone );
            $order->save();
        }
    }

    /**
     * Clear session data after order is complete.
     *
     * @param int $order_id Order ID.
     */
    public function clear_session( $order_id ) {
        if ( function_exists( 'WC' ) && WC()->session ) {
            WC()->session->__unset( self::SESSION_KEY );
        }
    }
}

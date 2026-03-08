<?php
/**
 * Custom Checkout Form
 * Overrides WooCommerce checkout/form-checkout.php
 * Matches LAWHA luxury fashion aesthetic
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

// Login prompt
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
    echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'lawha' ) ) );
    return;
}
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

  <div class="lawha-checkout__grid">
    <!-- Left Column: Forms -->
    <div>
      <?php if ( $checkout->get_checkout_fields() ) : ?>

        <!-- Billing -->
        <div class="lawha-checkout__section">
          <?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
          <div id="customer_details">
            <div>
              <?php do_action( 'woocommerce_checkout_billing' ); ?>
            </div>
            <div>
              <?php do_action( 'woocommerce_checkout_shipping' ); ?>
            </div>
          </div>
          <?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
        </div>

      <?php endif; ?>

      <!-- Additional Info -->
      <?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>

      <?php if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) : ?>
        <div class="lawha-checkout__section">
          <h3><?php esc_html_e( 'Additional Information', 'lawha' ); ?></h3>
          <?php foreach ( $checkout->get_checkout_fields( 'order' ) as $key => $field ) : ?>
            <?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>
    </div>

    <!-- Right Column: Order Review -->
    <div>
      <div class="lawha-checkout__order-summary">
        <h3 id="order_review_heading"><?php esc_html_e( 'Your Order', 'lawha' ); ?></h3>

        <?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

        <div id="order_review" class="woocommerce-checkout-review-order">
          <?php do_action( 'woocommerce_checkout_order_review' ); ?>
        </div>

        <?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
      </div>
    </div>
  </div>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>

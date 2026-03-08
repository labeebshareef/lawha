<?php
/**
 * Custom Cart Page Template
 * Overrides WooCommerce cart/cart.php
 * Matches LAWHA luxury fashion aesthetic
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_before_cart' );
?>

<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
  <?php do_action( 'woocommerce_before_cart_table' ); ?>

  <div class="lawha-cart__grid">
    <!-- Cart Items -->
    <div>
      <div class="lawha-cart__items">
        <?php
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
            $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
            $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

            if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) :
                $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
                $thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
                ?>
                <div class="lawha-cart__item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
                  <!-- Image -->
                  <div class="lawha-cart__item-image">
                    <?php if ( $product_permalink ) : ?>
                      <a href="<?php echo esc_url( $product_permalink ); ?>">
                        <?php echo wp_kses_post( $thumbnail ); ?>
                      </a>
                    <?php else : ?>
                      <?php echo wp_kses_post( $thumbnail ); ?>
                    <?php endif; ?>
                  </div>

                  <!-- Info -->
                  <div class="lawha-cart__item-info">
                    <h4>
                      <?php if ( $product_permalink ) : ?>
                        <a href="<?php echo esc_url( $product_permalink ); ?>">
                          <?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ); ?>
                        </a>
                      <?php else : ?>
                        <?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ); ?>
                      <?php endif; ?>
                    </h4>
                    <?php echo wc_get_formatted_cart_item_data( $cart_item ); ?>
                    <p class="lawha-cart__item-price">
                      <?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); ?>
                    </p>
                  </div>

                  <!-- Quantity -->
                  <div>
                    <?php
                    if ( $_product->is_sold_individually() ) {
                        $min_quantity = 1;
                        $max_quantity = 1;
                    } else {
                        $min_quantity = 0;
                        $max_quantity = $_product->get_max_purchase_quantity();
                    }
                    $product_quantity = woocommerce_quantity_input(
                        array(
                            'input_name'   => "cart[{$cart_item_key}][qty]",
                            'input_value'  => $cart_item['quantity'],
                            'max_value'    => $max_quantity,
                            'min_value'    => $min_quantity,
                            'product_name' => $_product->get_name(),
                        ),
                        $_product,
                        false
                    );
                    echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item );
                    ?>
                  </div>

                  <!-- Subtotal -->
                  <div class="lawha-cart__item-subtotal">
                    <?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>
                  </div>

                  <!-- Remove -->
                  <div>
                    <?php
                    echo apply_filters(
                        'woocommerce_cart_item_remove_link',
                        sprintf(
                            '<a href="%s" class="lawha-cart__item-remove" aria-label="%s" data-product_id="%s" data-product_sku="%s"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></a>',
                            esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
                            /* translators: %s product name */
                            esc_attr( sprintf( __( 'Remove %s from cart', 'lawha' ), wp_strip_all_tags( $_product->get_name() ) ) ),
                            esc_attr( $product_id ),
                            esc_attr( $_product->get_sku() )
                        ),
                        $cart_item_key
                    );
                    ?>
                  </div>
                </div>
                <?php
            endif;
        endforeach;
        ?>
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:var(--space-5);flex-wrap:wrap;gap:var(--space-3);">
        <?php if ( wc_coupons_enabled() ) : ?>
          <div style="display:flex;gap:var(--space-3);">
            <input type="text" name="coupon_code" class="form-input" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'lawha' ); ?>" style="width:200px;" />
            <button type="submit" class="btn btn--outline" name="apply_coupon" value="<?php esc_attr_e( 'Apply', 'lawha' ); ?>" style="padding:var(--space-3) var(--space-5);"><?php esc_html_e( 'Apply Coupon', 'lawha' ); ?></button>
          </div>
        <?php endif; ?>

        <button type="submit" class="btn btn--outline" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'lawha' ); ?>" style="padding:var(--space-3) var(--space-5);">
          <?php esc_html_e( 'Update Cart', 'lawha' ); ?>
        </button>
      </div>

      <?php do_action( 'woocommerce_cart_contents' ); ?>
      <?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
    </div>

    <!-- Cart Summary -->
    <div>
      <div class="lawha-cart__summary">
        <h3><?php esc_html_e( 'Order Summary', 'lawha' ); ?></h3>

        <div class="lawha-cart__summary-row">
          <span><?php esc_html_e( 'Subtotal', 'lawha' ); ?></span>
          <span><?php wc_cart_totals_subtotal_html(); ?></span>
        </div>

        <?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
          <div class="lawha-cart__summary-row">
            <span><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
            <span><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
          </div>
        <?php endforeach; ?>

        <?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
          <?php do_action( 'woocommerce_cart_totals_before_shipping' ); ?>
          <?php wc_cart_totals_shipping_html(); ?>
          <?php do_action( 'woocommerce_cart_totals_after_shipping' ); ?>
        <?php elseif ( WC()->cart->needs_shipping() && 'yes' === get_option( 'woocommerce_enable_shipping_calc' ) ) : ?>
          <div class="lawha-cart__summary-row">
            <span><?php esc_html_e( 'Shipping', 'lawha' ); ?></span>
            <span><?php woocommerce_shipping_calculator(); ?></span>
          </div>
        <?php endif; ?>

        <?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
          <div class="lawha-cart__summary-row">
            <span><?php echo esc_html( $fee->name ); ?></span>
            <span><?php wc_cart_totals_fee_html( $fee ); ?></span>
          </div>
        <?php endforeach; ?>

        <?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
          <?php if ( 'itemized' === get_option( 'woocommerce_tax_display_cart' ) ) : ?>
            <?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>
              <div class="lawha-cart__summary-row">
                <span><?php echo esc_html( $tax->label ); ?></span>
                <span><?php echo wp_kses_post( $tax->formatted_amount ); ?></span>
              </div>
            <?php endforeach; ?>
          <?php else : ?>
            <div class="lawha-cart__summary-row">
              <span><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></span>
              <span><?php wc_cart_totals_taxes_total_html(); ?></span>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <?php do_action( 'woocommerce_cart_totals_before_order_total' ); ?>

        <div class="lawha-cart__summary-row lawha-cart__summary-total">
          <span><?php esc_html_e( 'Total', 'lawha' ); ?></span>
          <span><?php wc_cart_totals_order_total_html(); ?></span>
        </div>

        <?php do_action( 'woocommerce_cart_totals_after_order_total' ); ?>

        <div class="wc-proceed-to-checkout" style="margin-top:var(--space-5);">
          <?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
        </div>
      </div>
    </div>
  </div>

  <?php do_action( 'woocommerce_after_cart_table' ); ?>
</form>

<?php do_action( 'woocommerce_after_cart' ); ?>

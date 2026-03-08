<?php
/**
 * Empty Cart Page
 * Matches LAWHA luxury fashion aesthetic
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_cart_is_empty' );

if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
  <div class="text-center" style="padding: var(--space-9) 0;">
    <h2 style="font-family:var(--font-heading);font-size:var(--text-3xl);font-weight:var(--weight-light);margin-bottom:var(--space-4);">
      <?php esc_html_e( 'Your cart is empty', 'lawha' ); ?>
    </h2>
    <p style="color:var(--text-secondary);font-size:var(--text-md);margin-bottom:var(--space-7);">
      <?php esc_html_e( 'Looks like you haven\'t added anything to your cart yet.', 'lawha' ); ?>
    </p>
    <a class="btn btn--primary" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
      <?php esc_html_e( 'Browse Collection', 'lawha' ); ?>
      <span class="btn__arrow">→</span>
    </a>
  </div>
<?php endif; ?>

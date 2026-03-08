<?php
/**
 * WooCommerce Content Product Template
 * Individual product card in the shop/archive loop, matching the existing product-card design
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
    return;
}

$product_id  = $product->get_id();
$image_id    = $product->get_image_id();
$image_url   = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : wc_placeholder_img_src( 'large' );
$image_alt   = $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : $product->get_name();
$is_featured = $product->is_featured();
$on_sale     = $product->is_on_sale();
$categories  = wc_get_product_category_list( $product_id );
?>

<div class="product-card anim-fade-up" data-category="<?php echo esc_attr( $product->get_slug() ); ?>">
  <div class="product-card__image-wrapper">
    <?php if ( $on_sale ) : ?>
      <span class="product-card__badge"><?php esc_html_e( 'Sale', 'lawha' ); ?></span>
    <?php elseif ( $is_featured ) : ?>
      <span class="product-card__badge"><?php esc_html_e( 'New', 'lawha' ); ?></span>
    <?php endif; ?>
    <img
      src="<?php echo esc_url( $image_url ); ?>"
      alt="<?php echo esc_attr( $image_alt ); ?>"
      class="product-card__image"
      loading="lazy"
    >
    <div class="product-card__overlay">
      <button type="button" class="btn btn--secondary lawha-quick-view" data-product_id="<?php echo esc_attr( $product_id ); ?>" style="font-size: 0.65rem; padding: 10px 20px;">
        <?php esc_html_e( 'Quick View', 'lawha' ); ?>
      </button>
      <a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="btn btn--secondary" style="font-size: 0.65rem; padding: 10px 20px; margin-top: 8px;">
        <?php esc_html_e( 'View Product', 'lawha' ); ?>
      </a>
    </div>
  </div>
  <div class="product-card__info">
    <h4 class="product-card__name">
      <a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
        <?php echo esc_html( $product->get_name() ); ?>
      </a>
    </h4>
    <p class="product-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
  </div>
</div>

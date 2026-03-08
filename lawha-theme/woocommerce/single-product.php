<?php
/**
 * WooCommerce Single Product Template
 * Styled to match the LAWHA luxury fashion aesthetic
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>
  <?php
  global $product;
  $product_id   = $product->get_id();
  $image_id     = $product->get_image_id();
  $gallery_ids  = $product->get_gallery_image_ids();
  $image_url    = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : wc_placeholder_img_src( 'full' );
  $image_alt    = $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : $product->get_name();
  $on_sale      = $product->is_on_sale();
  $categories   = wc_get_product_category_list( $product_id, ', ' );
  ?>

  <main class="site-main">

    <!-- PRODUCT DETAIL -->
    <section class="section" style="padding-top: calc(var(--space-10) + var(--space-7));">
      <div class="container">
        <div class="product-detail">

          <!-- Product Gallery -->
          <div class="product-detail__gallery anim-fade-up">
            <div class="product-detail__main-image">
              <?php if ( $on_sale ) : ?>
                <span class="product-card__badge" style="position: absolute; top: var(--space-4); left: var(--space-4); z-index: 2;">
                  <?php esc_html_e( 'Sale', 'lawha' ); ?>
                </span>
              <?php endif; ?>
              <img
                src="<?php echo esc_url( $image_url ); ?>"
                alt="<?php echo esc_attr( $image_alt ); ?>"
                class="product-detail__image"
                id="mainProductImage"
              >
            </div>
            <?php if ( ! empty( $gallery_ids ) ) : ?>
              <div class="product-detail__thumbnails">
                <button class="product-detail__thumb active" data-image="<?php echo esc_url( $image_url ); ?>">
                  <img src="<?php echo esc_url( wp_get_attachment_image_url( $image_id, 'thumbnail' ) ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>">
                </button>
                <?php foreach ( $gallery_ids as $gallery_id ) : ?>
                  <?php
                  $gallery_url  = wp_get_attachment_image_url( $gallery_id, 'full' );
                  $gallery_thumb = wp_get_attachment_image_url( $gallery_id, 'thumbnail' );
                  $gallery_alt  = get_post_meta( $gallery_id, '_wp_attachment_image_alt', true );
                  ?>
                  <button class="product-detail__thumb" data-image="<?php echo esc_url( $gallery_url ); ?>">
                    <img src="<?php echo esc_url( $gallery_thumb ); ?>" alt="<?php echo esc_attr( $gallery_alt ); ?>">
                  </button>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Product Info -->
          <div class="product-detail__info anim-fade-up">
            <?php if ( $categories ) : ?>
              <p class="overline"><?php echo wp_kses_post( strip_tags( $categories ) ); ?></p>
            <?php endif; ?>

            <h1 class="product-detail__title"><?php the_title(); ?></h1>
            <div class="divider"></div>

            <p class="product-detail__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>

            <?php if ( $product->get_short_description() ) : ?>
              <div class="product-detail__description">
                <?php echo wp_kses_post( $product->get_short_description() ); ?>
              </div>
            <?php endif; ?>

            <!-- Add to Cart -->
            <div class="product-detail__add-to-cart">
              <?php if ( $product->is_type( 'simple' ) && $product->is_in_stock() ) : ?>
                <form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data">
                  <div class="product-detail__quantity">
                    <label for="quantity" class="form-label"><?php esc_html_e( 'Quantity', 'lawha' ); ?></label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo esc_attr( $product->get_stock_quantity() ? $product->get_stock_quantity() : 99 ); ?>" class="form-input" style="width: 80px; text-align: center;">
                  </div>
                  <button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product_id ); ?>" class="btn btn--primary" style="width: 100%;">
                    <?php esc_html_e( 'Add to Cart', 'lawha' ); ?>
                    <span class="btn__arrow">→</span>
                  </button>
                </form>
              <?php elseif ( $product->is_type( 'variable' ) ) : ?>
                <?php woocommerce_variable_add_to_cart(); ?>
              <?php elseif ( ! $product->is_in_stock() ) : ?>
                <p class="product-detail__stock-status"><?php esc_html_e( 'Out of Stock', 'lawha' ); ?></p>
              <?php endif; ?>
            </div>

            <!-- WhatsApp Order -->
            <a href="<?php echo esc_url( 'https://wa.me/971501234567?text=' . rawurlencode( 'Hi LAWHA! I\'d like to order: ' . $product->get_name() ) ); ?>" class="btn btn--whatsapp" style="width: 100%; margin-top: var(--space-3);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
              <?php esc_html_e( 'Order via WhatsApp', 'lawha' ); ?>
            </a>

            <!-- Product Meta -->
            <div class="product-detail__meta" style="margin-top: var(--space-7); padding-top: var(--space-5); border-top: 1px solid var(--border-color);">
              <?php if ( $product->get_sku() ) : ?>
                <p class="text-small" style="color: var(--text-secondary); margin-bottom: var(--space-2);">
                  <strong><?php esc_html_e( 'SKU:', 'lawha' ); ?></strong> <?php echo esc_html( $product->get_sku() ); ?>
                </p>
              <?php endif; ?>
              <?php if ( $categories ) : ?>
                <p class="text-small" style="color: var(--text-secondary);">
                  <strong><?php esc_html_e( 'Category:', 'lawha' ); ?></strong> <?php echo wp_kses_post( $categories ); ?>
                </p>
              <?php endif; ?>
            </div>
          </div>

        </div>

        <!-- Full Description -->
        <?php if ( $product->get_description() ) : ?>
          <div class="product-detail__full-description anim-fade-up" style="margin-top: var(--space-9);">
            <div class="section-header">
              <h2 class="section-header__title"><?php esc_html_e( 'Details', 'lawha' ); ?></h2>
            </div>
            <div class="container container--narrow">
              <?php echo wp_kses_post( $product->get_description() ); ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Related Products -->
        <?php
        $related_ids = wc_get_related_products( $product_id, 4 );
        if ( ! empty( $related_ids ) ) :
            $related_products = array_map( 'wc_get_product', $related_ids );
            ?>
            <div class="related-products" style="margin-top: var(--space-9);">
              <div class="section-header anim-fade-up">
                <p class="section-header__overline"><?php esc_html_e( 'You May Also Like', 'lawha' ); ?></p>
                <h2 class="section-header__title"><?php esc_html_e( 'Related Products', 'lawha' ); ?></h2>
              </div>
              <div class="grid grid--4 stagger-children">
                <?php foreach ( $related_products as $rel_product ) :
                    if ( ! $rel_product ) continue;
                    $rel_id        = $rel_product->get_id();
                    $rel_image_id  = $rel_product->get_image_id();
                    $rel_image_url = $rel_image_id ? wp_get_attachment_image_url( $rel_image_id, 'large' ) : wc_placeholder_img_src( 'large' );
                    $rel_image_alt = $rel_image_id ? get_post_meta( $rel_image_id, '_wp_attachment_image_alt', true ) : $rel_product->get_name();
                    ?>
                    <div class="product-card anim-fade-up">
                      <div class="product-card__image-wrapper">
                        <?php if ( $rel_product->is_on_sale() ) : ?>
                          <span class="product-card__badge"><?php esc_html_e( 'Sale', 'lawha' ); ?></span>
                        <?php endif; ?>
                        <img
                          src="<?php echo esc_url( $rel_image_url ); ?>"
                          alt="<?php echo esc_attr( $rel_image_alt ); ?>"
                          class="product-card__image"
                          loading="lazy"
                        >
                        <div class="product-card__overlay">
                          <button type="button" class="btn btn--secondary lawha-quick-view" data-product_id="<?php echo esc_attr( $rel_id ); ?>" style="font-size: 0.65rem; padding: 10px 20px;">
                            <?php esc_html_e( 'Quick View', 'lawha' ); ?>
                          </button>
                        </div>
                      </div>
                      <div class="product-card__info">
                        <h4 class="product-card__name">
                          <a href="<?php echo esc_url( get_permalink( $rel_id ) ); ?>"><?php echo esc_html( $rel_product->get_name() ); ?></a>
                        </h4>
                        <p class="product-card__price"><?php echo wp_kses_post( $rel_product->get_price_html() ); ?></p>
                      </div>
                    </div>
                <?php endforeach; ?>
              </div>
            </div>
        <?php endif; ?>

      </div>
    </section>

  </main>

  <!-- Product Gallery Script -->
  <script>
  (function() {
    'use strict';
    var thumbs = document.querySelectorAll('.product-detail__thumb');
    var mainImage = document.getElementById('mainProductImage');

    if (!thumbs.length || !mainImage) return;

    thumbs.forEach(function(thumb) {
      thumb.addEventListener('click', function() {
        var newSrc = this.getAttribute('data-image');
        mainImage.style.opacity = '0';
        mainImage.style.transition = 'opacity 0.3s ease';
        setTimeout(function() {
          mainImage.src = newSrc;
          mainImage.style.opacity = '1';
        }, 300);

        thumbs.forEach(function(t) { t.classList.remove('active'); });
        this.classList.add('active');
      });
    });
  })();
  </script>

<?php endwhile; ?>

<?php get_footer(); ?>

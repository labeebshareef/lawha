<?php
/**
 * WooCommerce Archive Product Template (Shop Page)
 * Replaces collections.html — Matches the luxury fashion grid design
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

  <!-- PAGE HERO -->
  <section class="page-hero">
    <img src="<?php echo esc_url( LAWHA_URI . '/assets/banners/collections-banner.jpg' ); ?>" alt="<?php esc_attr_e( 'LAWHA HIJABS Collections', 'lawha' ); ?>" class="page-hero__bg">
    <div class="page-hero__content">
      <h1 class="page-hero__title"><?php esc_html_e( 'Our Collections', 'lawha' ); ?></h1>
      <p class="page-hero__subtitle"><?php esc_html_e( 'Curated for the modern modest woman', 'lawha' ); ?></p>
    </div>
  </section>

  <!-- FILTER BAR -->
  <section class="section" style="padding-top: var(--space-7); padding-bottom: 0;">
    <div class="container">
      <div class="flex-between stack-mobile" style="gap: var(--space-4);">
        <p class="overline">
          <?php
          $total = $wp_query->found_posts;
          /* translators: %d is the product count */
          printf( esc_html( _n( 'Showing %d product', 'Showing %d products', $total, 'lawha' ) ), $total );
          ?>
        </p>
        <div class="flex gap-3 flex-wrap">
          <?php
          $product_categories = get_terms( array(
              'taxonomy'   => 'product_cat',
              'hide_empty' => true,
              'parent'     => 0,
          ) );

          $current_cat = get_queried_object();
          $is_shop     = is_shop();
          ?>
          <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="btn btn--outline filter-btn<?php echo $is_shop ? ' active' : ''; ?>" style="padding: 8px 20px;">
            <?php esc_html_e( 'All', 'lawha' ); ?>
          </a>
          <?php if ( ! is_wp_error( $product_categories ) && ! empty( $product_categories ) ) : ?>
            <?php foreach ( $product_categories as $cat ) : ?>
              <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="btn btn--outline filter-btn<?php echo ( ! $is_shop && is_product_category( $cat->slug ) ) ? ' active' : ''; ?>" style="padding: 8px 20px;">
                <?php echo esc_html( $cat->name ); ?>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- PRODUCTS GRID -->
  <section class="section" id="products">
    <div class="container">
      <?php if ( woocommerce_product_loop() ) : ?>
        <div class="grid products-grid stagger-children">
          <?php
          while ( have_posts() ) :
              the_post();
              wc_get_template_part( 'content', 'product' );
          endwhile;
          ?>
        </div>

        <!-- Pagination -->
        <div class="text-center" style="margin-top: var(--space-7);">
          <?php
          echo wp_kses_post( paginate_links( array(
              'total'     => $wp_query->max_num_pages,
              'current'   => max( 1, get_query_var( 'paged' ) ),
              'prev_text' => '← ' . esc_html__( 'Previous', 'lawha' ),
              'next_text' => esc_html__( 'Next', 'lawha' ) . ' →',
              'type'      => 'list',
          ) ) );
          ?>
        </div>
      <?php else : ?>
        <div class="text-center" style="padding: var(--space-9) 0;">
          <p class="section-header__desc"><?php esc_html_e( 'No products found. Check back soon for our latest collections.', 'lawha' ); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- CTA BANNER -->
  <section class="cta-banner" id="cta">
    <img src="<?php echo esc_url( LAWHA_URI . '/assets/banners/cta-banner.jpg' ); ?>" alt="<?php esc_attr_e( 'LAWHA HIJABS — Shop Now', 'lawha' ); ?>" class="cta-banner__bg" loading="lazy">
    <div class="cta-banner__content anim-fade-up">
      <p class="overline" style="color: var(--accent-light); margin-bottom: var(--space-4);"><?php esc_html_e( "Can't Decide?", 'lawha' ); ?></p>
      <h2 class="cta-banner__title"><?php esc_html_e( 'Let Us Help You Choose', 'lawha' ); ?></h2>
      <p class="cta-banner__desc"><?php esc_html_e( 'Reach out to us on WhatsApp and our styling team will help you find your perfect hijab.', 'lawha' ); ?></p>
      <a href="<?php echo esc_url( 'https://wa.me/971501234567' ); ?>" class="btn btn--whatsapp">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        <?php esc_html_e( 'Chat on WhatsApp', 'lawha' ); ?>
      </a>
    </div>
  </section>

<?php get_footer(); ?>

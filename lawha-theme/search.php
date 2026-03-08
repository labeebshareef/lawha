<?php
/**
 * Search Results Template
 * Matches LAWHA luxury fashion aesthetic
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
    <div class="page-hero__content">
      <h1 class="page-hero__title">
        <?php
        /* translators: %s: search query */
        printf( esc_html__( 'Search Results for "%s"', 'lawha' ), get_search_query() );
        ?>
      </h1>
      <p class="page-hero__subtitle">
        <?php
        $total = $wp_query->found_posts;
        /* translators: %d: number of results */
        printf( esc_html( _n( '%d result found', '%d results found', $total, 'lawha' ) ), $total );
        ?>
      </p>
    </div>
  </section>

  <section class="section">
    <div class="container">

      <!-- Search Form -->
      <div style="margin-bottom:var(--space-7);">
        <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="lawha-search-form" style="margin:0 auto;">
          <input type="search" name="s" placeholder="<?php esc_attr_e( 'Search again...', 'lawha' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>">
          <button type="submit" class="btn btn--primary" style="padding:var(--space-4) var(--space-5);">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          </button>
        </form>
      </div>

      <?php if ( have_posts() ) : ?>

        <?php
        // Check if WooCommerce products are in results
        $has_products = false;
        if ( class_exists( 'WooCommerce' ) ) {
            $temp_query = new WP_Query( array(
                's'         => get_search_query(),
                'post_type' => 'product',
                'fields'    => 'ids',
            ) );
            $has_products = $temp_query->have_posts();
            wp_reset_postdata();
        }
        ?>

        <?php if ( $has_products ) : ?>
          <!-- Product Results -->
          <div style="margin-bottom:var(--space-7);">
            <h2 style="font-family:var(--font-heading);font-size:var(--text-2xl);font-weight:var(--weight-light);margin-bottom:var(--space-5);">
              <?php esc_html_e( 'Products', 'lawha' ); ?>
            </h2>
            <div class="grid grid--4 stagger-children">
              <?php
              $product_query = new WP_Query( array(
                  's'              => get_search_query(),
                  'post_type'      => 'product',
                  'posts_per_page' => 8,
              ) );
              while ( $product_query->have_posts() ) :
                  $product_query->the_post();
                  wc_get_template_part( 'content', 'product' );
              endwhile;
              wp_reset_postdata();
              ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Other Results -->
        <div>
          <h2 style="font-family:var(--font-heading);font-size:var(--text-2xl);font-weight:var(--weight-light);margin-bottom:var(--space-5);">
            <?php esc_html_e( 'Pages & Posts', 'lawha' ); ?>
          </h2>
          <?php while ( have_posts() ) : the_post(); ?>
            <?php if ( get_post_type() !== 'product' ) : ?>
              <div class="lawha-search-results__item anim-fade-up">
                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <p><?php the_excerpt(); ?></p>
              </div>
            <?php endif; ?>
          <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <div class="text-center" style="margin-top:var(--space-7);">
          <?php
          the_posts_pagination( array(
              'prev_text' => '← ' . esc_html__( 'Previous', 'lawha' ),
              'next_text' => esc_html__( 'Next', 'lawha' ) . ' →',
          ) );
          ?>
        </div>

      <?php else : ?>

        <div class="text-center" style="padding:var(--space-9) 0;">
          <h2 style="font-family:var(--font-heading);font-size:var(--text-2xl);font-weight:var(--weight-light);margin-bottom:var(--space-4);">
            <?php esc_html_e( 'No results found', 'lawha' ); ?>
          </h2>
          <p style="color:var(--text-secondary);margin-bottom:var(--space-6);">
            <?php esc_html_e( 'Try searching with different keywords or browse our collection.', 'lawha' ); ?>
          </p>
          <?php if ( class_exists( 'WooCommerce' ) ) : ?>
            <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="btn btn--primary">
              <?php esc_html_e( 'Browse Collection', 'lawha' ); ?>
              <span class="btn__arrow">→</span>
            </a>
          <?php endif; ?>
        </div>

      <?php endif; ?>

    </div>
  </section>

<?php get_footer(); ?>

<?php
/**
 * 404 Page Template
 * Matches LAWHA luxury fashion aesthetic
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

  <main class="site-main">
    <div class="lawha-404">
      <p class="lawha-404__number">404</p>
      <h1 class="lawha-404__title"><?php esc_html_e( 'Page Not Found', 'lawha' ); ?></h1>
      <p class="lawha-404__desc"><?php esc_html_e( 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.', 'lawha' ); ?></p>

      <div style="display:flex;gap:var(--space-4);flex-wrap:wrap;justify-content:center;">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--primary">
          <?php esc_html_e( 'Back to Home', 'lawha' ); ?>
          <span class="btn__arrow">→</span>
        </a>
        <?php if ( class_exists( 'WooCommerce' ) ) : ?>
          <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="btn btn--outline">
            <?php esc_html_e( 'Browse Collection', 'lawha' ); ?>
          </a>
        <?php endif; ?>
      </div>

      <div style="margin-top:var(--space-8);width:100%;max-width:500px;">
        <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="lawha-search-form">
          <input type="search" name="s" placeholder="<?php esc_attr_e( 'Search our store...', 'lawha' ); ?>" value="" required>
          <button type="submit" class="btn btn--primary" style="padding:var(--space-4) var(--space-5);">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          </button>
        </form>
      </div>
    </div>
  </main>

<?php get_footer(); ?>

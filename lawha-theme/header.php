<?php
/**
 * Theme Header
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="<?php echo esc_url( LAWHA_URI . '/assets/logo/favicon.png' ); ?>">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

  <!-- Scroll Progress Bar -->
  <div class="scroll-progress" id="scrollProgress"></div>

  <!-- NAVBAR -->
  <nav class="navbar navbar--transparent" id="navbar" role="navigation" aria-label="<?php esc_attr_e( 'Main navigation', 'lawha' ); ?>">
    <div class="container">
      <div class="navbar__inner">

        <!-- Logo -->
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="navbar__logo" aria-label="<?php esc_attr_e( 'LAWHA HIJABS Home', 'lawha' ); ?>">
          <?php if ( has_custom_logo() ) : ?>
            <?php
            $custom_logo_id = get_theme_mod( 'custom_logo' );
            $logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
            ?>
            <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php bloginfo( 'name' ); ?> Logo" width="40" height="40">
          <?php else : ?>
            <img src="<?php echo esc_url( LAWHA_URI . '/assets/logo/logo.png' ); ?>" alt="<?php bloginfo( 'name' ); ?> Logo" width="40" height="40">
          <?php endif; ?>
          <span class="navbar__logo-text">Lawha</span>
        </a>

        <!-- Desktop Navigation -->
        <div class="navbar__nav" id="navLinks">
          <?php
          if ( has_nav_menu( 'primary' ) ) {
              wp_nav_menu( array(
                  'theme_location' => 'primary',
                  'container'      => false,
                  'items_wrap'     => '%3$s',
                  'walker'         => new Lawha_Nav_Walker(),
                  'depth'          => 1,
              ) );
          } else {
              // Fallback navigation
              ?>
              <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="navbar__link<?php echo is_front_page() ? ' active' : ''; ?>"><?php esc_html_e( 'Home', 'lawha' ); ?></a>
              <a href="<?php echo class_exists( 'WooCommerce' ) ? esc_url( wc_get_page_permalink( 'shop' ) ) : '#'; ?>" class="navbar__link<?php echo ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() ) ) ? ' active' : ''; ?>"><?php esc_html_e( 'Collections', 'lawha' ); ?></a>
              <?php
              $about_page = get_page_by_path( 'about' );
              $contact_page = get_page_by_path( 'contact' );
              ?>
              <a href="<?php echo $about_page ? esc_url( get_permalink( $about_page ) ) : '#'; ?>" class="navbar__link<?php echo is_page( 'about' ) ? ' active' : ''; ?>"><?php esc_html_e( 'About', 'lawha' ); ?></a>
              <a href="<?php echo $contact_page ? esc_url( get_permalink( $contact_page ) ) : '#'; ?>" class="navbar__link<?php echo is_page( 'contact' ) ? ' active' : ''; ?>"><?php esc_html_e( 'Contact', 'lawha' ); ?></a>
              <?php
          }
          ?>
        </div>

        <!-- Cart Icon + Hamburger -->
        <div class="navbar__actions">
          <?php if ( class_exists( 'WooCommerce' ) ) : ?>
            <button class="navbar__cart lawha-cart-trigger" aria-label="<?php esc_attr_e( 'Shopping Cart', 'lawha' ); ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 01-8 0"></path></svg>
              <span class="lawha-cart-count"<?php echo WC()->cart->get_cart_contents_count() > 0 ? '' : ' style="display:none"'; ?>><?php echo esc_html( WC()->cart->get_cart_contents_count() ); ?></span>
            </button>
          <?php endif; ?>

          <!-- Hamburger Menu Button -->
          <button class="navbar__hamburger" id="hamburger" aria-label="<?php esc_attr_e( 'Toggle menu', 'lawha' ); ?>" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile Overlay -->
    <div class="navbar__mobile-overlay" id="mobileOverlay">
      <?php
      if ( has_nav_menu( 'primary' ) ) {
          wp_nav_menu( array(
              'theme_location' => 'primary',
              'container'      => false,
              'items_wrap'     => '%3$s',
              'walker'         => new Lawha_Nav_Walker(),
              'depth'          => 1,
          ) );
      } else {
          ?>
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="navbar__link<?php echo is_front_page() ? ' active' : ''; ?>"><?php esc_html_e( 'Home', 'lawha' ); ?></a>
          <a href="<?php echo class_exists( 'WooCommerce' ) ? esc_url( wc_get_page_permalink( 'shop' ) ) : '#'; ?>" class="navbar__link<?php echo ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() ) ) ? ' active' : ''; ?>"><?php esc_html_e( 'Collections', 'lawha' ); ?></a>
          <?php
          $about_page = get_page_by_path( 'about' );
          $contact_page = get_page_by_path( 'contact' );
          ?>
          <a href="<?php echo $about_page ? esc_url( get_permalink( $about_page ) ) : '#'; ?>" class="navbar__link<?php echo is_page( 'about' ) ? ' active' : ''; ?>"><?php esc_html_e( 'About', 'lawha' ); ?></a>
          <a href="<?php echo $contact_page ? esc_url( get_permalink( $contact_page ) ) : '#'; ?>" class="navbar__link<?php echo is_page( 'contact' ) ? ' active' : ''; ?>"><?php esc_html_e( 'Contact', 'lawha' ); ?></a>
          <?php
      }
      ?>
    </div>
  </nav>

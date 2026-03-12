<?php
/**
 * Theme Footer
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

  <!-- FOOTER -->
  <footer class="footer" id="footer">
    <div class="container">
      <div class="footer__grid">

        <!-- Brand Column -->
        <div>
          <h3 class="footer__brand-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h3>
          <p class="footer__brand-desc"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
        </div>

        <!-- Quick Links -->
        <div>
          <h4 class="footer__heading"><?php esc_html_e( 'Explore', 'lawha' ); ?></h4>
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="footer__link"><?php esc_html_e( 'Home', 'lawha' ); ?></a>
          <?php if ( class_exists( 'WooCommerce' ) ) : ?>
            <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="footer__link"><?php esc_html_e( 'Collections', 'lawha' ); ?></a>
          <?php endif; ?>
          <?php
          // Cache all footer page lookups in a single batch to avoid repeated queries.
          $footer_pages = get_transient( 'lawha_footer_pages' );
          if ( false === $footer_pages ) {
              $footer_slugs = array( 'about', 'contact', 'shipping-policy', 'size-guide', 'faq', 'returns' );
              $footer_pages = array();
              foreach ( $footer_slugs as $slug ) {
                  $footer_pages[ $slug ] = get_page_by_path( $slug );
              }
              set_transient( 'lawha_footer_pages', $footer_pages, DAY_IN_SECONDS );
          }
          ?>
          <?php if ( $footer_pages['about'] ) : ?>
            <a href="<?php echo esc_url( get_permalink( $footer_pages['about'] ) ); ?>" class="footer__link"><?php esc_html_e( 'About Us', 'lawha' ); ?></a>
          <?php endif; ?>
          <?php if ( $footer_pages['contact'] ) : ?>
            <a href="<?php echo esc_url( get_permalink( $footer_pages['contact'] ) ); ?>" class="footer__link"><?php esc_html_e( 'Contact', 'lawha' ); ?></a>
          <?php endif; ?>
        </div>

        <!-- Help -->
        <div>
          <h4 class="footer__heading"><?php esc_html_e( 'Help', 'lawha' ); ?></h4>
          <a href="<?php echo $footer_pages['shipping-policy'] ? esc_url( get_permalink( $footer_pages['shipping-policy'] ) ) : '#'; ?>" class="footer__link"><?php esc_html_e( 'Shipping & Returns', 'lawha' ); ?></a>
          <a href="<?php echo $footer_pages['size-guide'] ? esc_url( get_permalink( $footer_pages['size-guide'] ) ) : '#'; ?>" class="footer__link"><?php esc_html_e( 'Size Guide', 'lawha' ); ?></a>
          <a href="#" class="footer__link"><?php esc_html_e( 'Care Instructions', 'lawha' ); ?></a>
          <a href="<?php echo $footer_pages['faq'] ? esc_url( get_permalink( $footer_pages['faq'] ) ) : '#'; ?>" class="footer__link"><?php esc_html_e( 'FAQ', 'lawha' ); ?></a>
        </div>

        <!-- Contact -->
        <div>
          <h4 class="footer__heading"><?php esc_html_e( 'Get in Touch', 'lawha' ); ?></h4>
          <a href="mailto:<?php echo esc_attr( lawha_get_contact_email() ); ?>" class="footer__link"><?php echo esc_html( lawha_get_contact_email() ); ?></a>
          <a href="tel:<?php echo esc_attr( lawha_get_contact_phone() ); ?>" class="footer__link"><?php echo esc_html( lawha_get_contact_phone() ); ?></a>
          <a href="<?php echo esc_url( 'https://wa.me/' . lawha_get_whatsapp_number() ); ?>" class="footer__link"><?php esc_html_e( 'WhatsApp Order', 'lawha' ); ?></a>
        </div>
      </div>

      <div class="footer__bottom">
        <div class="footer__meta">
          <p class="footer__copyright">&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. <?php esc_html_e( 'All rights reserved.', 'lawha' ); ?></p>
          <p class="footer__build"><?php printf( esc_html__( 'Theme %1$s | Build %2$s', 'lawha' ), esc_html( LAWHA_VERSION ), esc_html( lawha_get_build_version() ) ); ?></p>
        </div>
        <div class="footer__socials">
          <a href="#" class="footer__social-link" aria-label="<?php esc_attr_e( 'Instagram', 'lawha' ); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
          </a>
          <a href="#" class="footer__social-link" aria-label="<?php esc_attr_e( 'TikTok', 'lawha' ); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.88-2.88 2.89 2.89 0 012.88-2.88c.28 0 .54.04.79.11v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.73a8.26 8.26 0 004.78 1.52v-3.4a4.85 4.85 0 01-1.02-.16z"/></svg>
          </a>
          <a href="#" class="footer__social-link" aria-label="<?php esc_attr_e( 'Facebook', 'lawha' ); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          </a>
        </div>
      </div>
    </div>
  </footer>

  <?php if ( class_exists( 'WooCommerce' ) ) : ?>
  <!-- QUICK VIEW MODAL -->
  <div class="lawha-quickview" aria-hidden="true" role="dialog">
    <div class="lawha-quickview__overlay"></div>
    <div class="lawha-quickview__panel">
      <button class="lawha-quickview__close" aria-label="<?php esc_attr_e( 'Close', 'lawha' ); ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
      <div class="lawha-quickview__loader">
        <div class="lawha-quickview__spinner"></div>
      </div>
      <div class="lawha-quickview__content"></div>
    </div>
  </div>

  <!-- MINI CART DRAWER -->
  <div class="lawha-minicart" aria-hidden="true">
    <div class="lawha-minicart__overlay"></div>
    <div class="lawha-minicart__panel">
      <div class="lawha-minicart__header">
        <h3 class="lawha-minicart__title"><?php esc_html_e( 'Your Cart', 'lawha' ); ?></h3>
        <button class="lawha-minicart__close" aria-label="<?php esc_attr_e( 'Close cart', 'lawha' ); ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>
      <div class="lawha-minicart__body">
        <?php lawha_render_mini_cart_items(); ?>
      </div>
      <div class="lawha-minicart__footer"<?php echo WC()->cart->get_cart_contents_count() > 0 ? '' : ' style="display:none"'; ?>>
        <div class="lawha-minicart__subtotal">
          <span><?php esc_html_e( 'Subtotal', 'lawha' ); ?></span>
          <span class="lawha-minicart__subtotal-amount"><?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?></span>
        </div>
        <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="btn btn--outline w-full mb-3">
          <?php esc_html_e( 'View Cart', 'lawha' ); ?>
        </a>
        <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="btn btn--primary w-full">
          <?php esc_html_e( 'Checkout', 'lawha' ); ?>
          <span class="btn__arrow">→</span>
        </a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php wp_footer(); ?>
</body>
</html>

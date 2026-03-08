<?php
/**
 * My Account Page
 * Overrides WooCommerce myaccount/my-account.php
 * Matches LAWHA luxury fashion aesthetic
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="lawha-account__grid">
  <!-- Sidebar Navigation -->
  <nav class="lawha-account__nav">
    <?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
      <a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>" class="<?php echo wc_get_account_menu_item_classes( $endpoint ); ?><?php echo wc_is_current_account_menu_item( $endpoint ) ? ' is-active' : ''; ?>">
        <?php echo esc_html( $label ); ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <!-- Main Content -->
  <div class="lawha-account__content">
    <?php
      /**
       * My Account content output.
       */
      do_action( 'woocommerce_account_content' );
    ?>
  </div>
</div>

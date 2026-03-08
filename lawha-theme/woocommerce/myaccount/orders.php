<?php
/**
 * My Account — Orders
 * Overrides WooCommerce myaccount/orders.php
 * Matches LAWHA luxury fashion aesthetic
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_before_account_orders', $has_orders );

if ( $has_orders ) : ?>

  <h2><?php esc_html_e( 'Order History', 'lawha' ); ?></h2>

  <table class="lawha-orders-table">
    <thead>
      <tr>
        <th><?php esc_html_e( 'Order', 'lawha' ); ?></th>
        <th><?php esc_html_e( 'Date', 'lawha' ); ?></th>
        <th><?php esc_html_e( 'Status', 'lawha' ); ?></th>
        <th><?php esc_html_e( 'Total', 'lawha' ); ?></th>
        <th><?php esc_html_e( 'Actions', 'lawha' ); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php
      foreach ( $customer_orders->orders as $customer_order ) :
          $order      = wc_get_order( $customer_order );
          $item_count = $order->get_item_count() - $order->get_item_count_refunded();
          ?>
          <tr>
            <td>
              <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
                #<?php echo esc_html( $order->get_order_number() ); ?>
              </a>
            </td>
            <td><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></td>
            <td><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></td>
            <td>
              <?php
              /* translators: 1: formatted order total 2: total order items */
              echo wp_kses_post( sprintf( _n( '%1$s for %2$s item', '%1$s for %2$s items', $item_count, 'lawha' ), $order->get_formatted_order_total(), $item_count ) );
              ?>
            </td>
            <td>
              <?php
              $actions = wc_get_account_orders_actions( $order );
              if ( ! empty( $actions ) ) {
                  foreach ( $actions as $key => $action ) {
                      echo '<a href="' . esc_url( $action['url'] ) . '" class="btn btn--outline" style="padding:var(--space-2) var(--space-4);font-size:var(--text-xs);margin-right:var(--space-2);">' . esc_html( $action['name'] ) . '</a>';
                  }
              }
              ?>
            </td>
          </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>

  <?php if ( 1 < $customer_orders->max_num_pages ) : ?>
    <div class="text-center" style="margin-top:var(--space-6);">
      <?php if ( 1 !== $current_page ) : ?>
        <a class="btn btn--outline" style="padding:var(--space-2) var(--space-5);" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>"><?php esc_html_e( '← Previous', 'lawha' ); ?></a>
      <?php endif; ?>
      <?php if ( intval( $customer_orders->max_num_pages ) !== $current_page ) : ?>
        <a class="btn btn--outline" style="padding:var(--space-2) var(--space-5);" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next →', 'lawha' ); ?></a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

<?php else : ?>

  <div class="text-center" style="padding:var(--space-9) 0;">
    <h2 style="font-family:var(--font-heading);font-size:var(--text-2xl);font-weight:var(--weight-light);margin-bottom:var(--space-4);">
      <?php esc_html_e( 'No orders yet', 'lawha' ); ?>
    </h2>
    <p style="color:var(--text-secondary);margin-bottom:var(--space-6);">
      <?php esc_html_e( 'When you place your first order, it will appear here.', 'lawha' ); ?>
    </p>
    <a class="btn btn--primary" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
      <?php esc_html_e( 'Start Shopping', 'lawha' ); ?>
      <span class="btn__arrow">→</span>
    </a>
  </div>

<?php endif; ?>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>

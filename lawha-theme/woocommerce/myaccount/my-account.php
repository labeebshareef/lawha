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

<?php
/* ── Email Verification Banner ── */
if ( is_user_logged_in() ) {
    $user_id        = get_current_user_id();
    $email_verified = get_user_meta( $user_id, 'lawha_email_verified', true );
    $deadline       = get_user_meta( $user_id, 'lawha_email_verify_deadline', true );
    $user_email     = wp_get_current_user()->user_email;

    // Show banner only if email is not verified and not a placeholder email.
    if ( '1' !== $email_verified && ! empty( $user_email ) && strpos( $user_email, '@noreply.' ) === false && ! empty( $deadline ) ) {
        $days_left = max( 0, ceil( ( (int) $deadline - time() ) / DAY_IN_SECONDS ) );
        ?>
        <div class="lawha-email-verify-banner">
          <div class="lawha-email-verify-banner__text">
            <strong><?php esc_html_e( 'Verify your email address', 'lawha' ); ?></strong><br>
            <?php
            if ( $days_left > 0 ) {
                /* translators: %d: number of days remaining */
                printf( esc_html__( 'Please verify %s within %d day(s) to secure your account.', 'lawha' ), esc_html( $user_email ), $days_left );
            } else {
                printf( esc_html__( 'Your verification period for %s has expired. Please resend the verification email.', 'lawha' ), esc_html( $user_email ) );
            }
            ?>
          </div>
          <button type="button" id="lawhaResendVerifyEmail" class="lawha-email-verify-banner__btn"><?php esc_html_e( 'Resend Email', 'lawha' ); ?></button>
        </div>
        <?php
    }
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

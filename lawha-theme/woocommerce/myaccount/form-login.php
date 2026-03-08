<?php
/**
 * Login / Register Form
 * Overrides WooCommerce myaccount/form-login.php
 * Matches LAWHA luxury fashion aesthetic
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_before_customer_login_form' );
?>

<div class="lawha-auth">

  <?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
    <div class="lawha-auth__tabs" id="lawhaAuthTabs">
      <button type="button" class="lawha-auth__tab is-active" data-tab="login"><?php esc_html_e( 'Login', 'lawha' ); ?></button>
      <button type="button" class="lawha-auth__tab" data-tab="register"><?php esc_html_e( 'Register', 'lawha' ); ?></button>
    </div>
  <?php endif; ?>

  <!-- LOGIN FORM -->
  <div class="lawha-auth__panel" id="lawhaLoginPanel">
    <h2><?php esc_html_e( 'Welcome Back', 'lawha' ); ?></h2>

    <form class="woocommerce-form woocommerce-form-login login" method="post">
      <?php do_action( 'woocommerce_login_form_start' ); ?>

      <div class="form-group">
        <label for="username" class="form-label"><?php esc_html_e( 'Email or Username', 'lawha' ); ?>&nbsp;<span class="required">*</span></label>
        <input type="text" class="form-input" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required />
      </div>

      <div class="form-group">
        <label for="password" class="form-label"><?php esc_html_e( 'Password', 'lawha' ); ?>&nbsp;<span class="required">*</span></label>
        <input class="form-input" type="password" name="password" id="password" autocomplete="current-password" required />
      </div>

      <?php do_action( 'woocommerce_login_form' ); ?>

      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-5);flex-wrap:wrap;gap:var(--space-3);">
        <label style="display:flex;align-items:center;gap:var(--space-2);font-size:var(--text-sm);color:var(--text-secondary);cursor:pointer;">
          <input name="rememberme" type="checkbox" id="rememberme" value="forever" />
          <?php esc_html_e( 'Remember me', 'lawha' ); ?>
        </label>
        <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" style="font-size:var(--text-sm);color:var(--secondary);"><?php esc_html_e( 'Forgot password?', 'lawha' ); ?></a>
      </div>

      <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>

      <button type="submit" class="btn btn--primary" name="login" value="<?php esc_attr_e( 'Log in', 'lawha' ); ?>" style="width:100%;">
        <?php esc_html_e( 'Log In', 'lawha' ); ?>
        <span class="btn__arrow">→</span>
      </button>

      <?php do_action( 'woocommerce_login_form_end' ); ?>
    </form>
  </div>

  <?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
  <!-- REGISTER FORM -->
  <div class="lawha-auth__panel" id="lawhaRegisterPanel" style="display:none;">
    <h2><?php esc_html_e( 'Create Account', 'lawha' ); ?></h2>

    <form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?>>
      <?php do_action( 'woocommerce_register_form_start' ); ?>

      <?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
        <div class="form-group">
          <label for="reg_username" class="form-label"><?php esc_html_e( 'Username', 'lawha' ); ?>&nbsp;<span class="required">*</span></label>
          <input type="text" class="form-input" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required />
        </div>
      <?php endif; ?>

      <div class="form-group">
        <label for="reg_email" class="form-label"><?php esc_html_e( 'Email Address', 'lawha' ); ?>&nbsp;<span class="required">*</span></label>
        <input type="email" class="form-input" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required />
      </div>

      <?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
        <div class="form-group">
          <label for="reg_password" class="form-label"><?php esc_html_e( 'Password', 'lawha' ); ?>&nbsp;<span class="required">*</span></label>
          <input type="password" class="form-input" name="password" id="reg_password" autocomplete="new-password" required />
        </div>
      <?php else : ?>
        <p style="font-size:var(--text-sm);color:var(--text-secondary);margin-bottom:var(--space-5);">
          <?php esc_html_e( 'A link to set a new password will be sent to your email address.', 'lawha' ); ?>
        </p>
      <?php endif; ?>

      <?php do_action( 'woocommerce_register_form' ); ?>

      <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>

      <button type="submit" class="btn btn--primary" name="register" value="<?php esc_attr_e( 'Register', 'lawha' ); ?>" style="width:100%;">
        <?php esc_html_e( 'Create Account', 'lawha' ); ?>
        <span class="btn__arrow">→</span>
      </button>

      <?php do_action( 'woocommerce_register_form_end' ); ?>
    </form>
  </div>
  <?php endif; ?>

</div>

<?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
<script>
(function(){
  'use strict';
  var tabs = document.querySelectorAll('.lawha-auth__tab');
  var loginPanel = document.getElementById('lawhaLoginPanel');
  var registerPanel = document.getElementById('lawhaRegisterPanel');

  if (!tabs.length || !loginPanel || !registerPanel) return;

  tabs.forEach(function(tab) {
    tab.addEventListener('click', function() {
      tabs.forEach(function(t) { t.classList.remove('is-active'); });
      this.classList.add('is-active');
      var target = this.getAttribute('data-tab');
      if (target === 'login') {
        loginPanel.style.display = '';
        registerPanel.style.display = 'none';
      } else {
        loginPanel.style.display = 'none';
        registerPanel.style.display = '';
      }
    });
  });
})();
</script>
<?php endif; ?>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>

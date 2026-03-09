<?php
/**
 * Login / Register Form — Phone-First Authentication
 * Overrides WooCommerce myaccount/form-login.php
 * Primary: Phone OTP via Firebase (uses WFPL SDK in headless mode)
 * Fallback: Email / Password when Firebase is not configured
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── Detect Firebase availability ── */
$firebase_configured = false;
if ( function_exists( 'wfpl_get_option' ) ) {
    $api_key    = wfpl_get_option( 'firebase_api_key', '' );
    $project_id = wfpl_get_option( 'firebase_project_id', '' );
    $firebase_configured = ! empty( $api_key ) && ! empty( $project_id );
}

do_action( 'woocommerce_before_customer_login_form' );
?>

<div class="lawha-auth">

<?php if ( $firebase_configured ) : ?>
  <!-- ════════════════════════════════════════════════
       PHONE-FIRST LOGIN  (Firebase OTP)
       ════════════════════════════════════════════════ -->

  <h2 class="lawha-auth__heading"><?php esc_html_e( 'Welcome to LAWHA', 'lawha' ); ?></h2>
  <p class="lawha-auth__subtitle"><?php esc_html_e( 'Enter your phone number to continue', 'lawha' ); ?></p>

  <!-- Message area -->
  <div id="lawhaAuthMessage" class="lawha-auth__message" role="alert" aria-live="polite" style="display:none;"></div>

  <!-- STEP 1 — Phone Input -->
  <div id="lawhaPhoneStep" class="lawha-phone-step">
    <div class="form-group">
      <label for="lawha_phone" class="form-label"><?php esc_html_e( 'Phone Number', 'lawha' ); ?>&nbsp;<span class="required">*</span></label>
      <input type="tel" class="form-input" id="lawha_phone" name="phone" autocomplete="tel" required />
    </div>

    <!-- reCAPTCHA container (invisible, required by Firebase) -->
    <div id="lawha-recaptcha" class="lawha-recaptcha-container"></div>

    <button type="button" id="lawhaSendOtp" class="btn btn--primary" style="width:100%;">
      <span class="btn__text"><?php esc_html_e( 'Send Verification Code', 'lawha' ); ?></span>
      <span class="btn__spinner lawha-spinner" style="display:none;"></span>
      <span class="btn__arrow">→</span>
    </button>
  </div>

  <!-- STEP 2 — OTP Input -->
  <div id="lawhaOtpStep" class="lawha-phone-step" style="display:none;">
    <p class="lawha-auth__subtitle">
      <?php esc_html_e( 'Enter the 6-digit code sent to', 'lawha' ); ?>
      <strong id="lawhaPhoneDisplay"></strong>
    </p>

    <div class="lawha-otp-inputs" dir="ltr">
      <input type="text" class="lawha-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" data-idx="0" aria-label="Digit 1" />
      <input type="text" class="lawha-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-idx="1" aria-label="Digit 2" />
      <input type="text" class="lawha-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-idx="2" aria-label="Digit 3" />
      <input type="text" class="lawha-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-idx="3" aria-label="Digit 4" />
      <input type="text" class="lawha-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-idx="4" aria-label="Digit 5" />
      <input type="text" class="lawha-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-idx="5" aria-label="Digit 6" />
    </div>

    <button type="button" id="lawhaVerifyOtp" class="btn btn--primary" style="width:100%;" disabled>
      <span class="btn__text"><?php esc_html_e( 'Verify & Log In', 'lawha' ); ?></span>
      <span class="btn__spinner lawha-spinner" style="display:none;"></span>
      <span class="btn__arrow">→</span>
    </button>

    <div class="lawha-auth__resend">
      <span id="lawhaResendTimer" class="lawha-auth__timer"></span>
      <button type="button" id="lawhaResendOtp" class="lawha-auth__resend-btn" style="display:none;">
        <?php esc_html_e( 'Resend Code', 'lawha' ); ?>
      </button>
    </div>

    <button type="button" id="lawhaBackToPhone" class="lawha-auth__back-link">
      ← <?php esc_html_e( 'Change phone number', 'lawha' ); ?>
    </button>
  </div>

  <!-- STEP 3 — Success -->
  <div id="lawhaSuccessStep" class="lawha-phone-step" style="display:none;">
    <div class="lawha-auth__success-icon">✓</div>
    <p class="lawha-auth__subtitle"><?php esc_html_e( 'Verified! Redirecting…', 'lawha' ); ?></p>
  </div>

  <!-- Divider + email fallback link -->
  <div class="lawha-auth__divider">
    <span><?php esc_html_e( 'or', 'lawha' ); ?></span>
  </div>
  <button type="button" id="lawhaShowEmail" class="lawha-auth__alt-link">
    <?php esc_html_e( 'Sign in with email & password', 'lawha' ); ?>
  </button>

  <!-- Hidden email/password fallback -->
  <div id="lawhaEmailPanel" style="display:none;">
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

    <button type="button" id="lawhaShowPhone" class="lawha-auth__alt-link" style="margin-top:var(--space-4);">
      ← <?php esc_html_e( 'Back to phone login', 'lawha' ); ?>
    </button>
  </div>

<?php else : ?>
  <!-- ════════════════════════════════════════════════
       FALLBACK — Email / Password (Firebase not configured)
       ════════════════════════════════════════════════ -->

  <?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
    <div class="lawha-auth__tabs" id="lawhaAuthTabs">
      <button type="button" class="lawha-auth__tab is-active" data-tab="login"><?php esc_html_e( 'Login', 'lawha' ); ?></button>
      <button type="button" class="lawha-auth__tab" data-tab="register"><?php esc_html_e( 'Register', 'lawha' ); ?></button>
    </div>
  <?php endif; ?>

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

<?php endif; /* end firebase_configured check */ ?>

</div>

<?php if ( ! $firebase_configured && 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
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

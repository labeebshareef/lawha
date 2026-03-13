<?php
/**
 * Login / Register Form — Account-Based Authentication
 * Overrides WooCommerce myaccount/form-login.php
 * Primary: Email/Phone + Password login
 * Registration: Full form with phone OTP verification
 * Forgot Password: OTP-based reset
 *
 * @package LAWHA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── Detect Firebase availability (used for phone OTP in registration & forgot password) ── */
$firebase_configured = function_exists( 'wfpl_is_firebase_configured' ) && wfpl_is_firebase_configured();

$enable_registration = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );

do_action( 'woocommerce_before_customer_login_form' );
?>

<div class="lawha-auth">

  <!-- ════════════════════════════════════════════════
       TABS: Login / Register
       ════════════════════════════════════════════════ -->
  <?php if ( $enable_registration ) : ?>
    <div class="lawha-auth__tabs" id="lawhaAuthTabs">
      <button type="button" class="lawha-auth__tab is-active" data-tab="login"><?php esc_html_e( 'Login', 'lawha' ); ?></button>
      <button type="button" class="lawha-auth__tab" data-tab="register"><?php esc_html_e( 'Register', 'lawha' ); ?></button>
    </div>
  <?php endif; ?>

  <!-- Message area (shared) -->
  <div id="lawhaAuthMessage" class="lawha-auth__message" role="alert" aria-live="polite" style="display:none;"></div>

  <!-- ════════════════════════════════════════════════
       LOGIN PANEL — Email/Phone + Password (Primary)
       ════════════════════════════════════════════════ -->
  <div class="lawha-auth__panel" id="lawhaLoginPanel">
    <h2 class="lawha-auth__heading"><?php esc_html_e( 'Welcome Back', 'lawha' ); ?></h2>
    <p class="lawha-auth__subtitle"><?php esc_html_e( 'Sign in with your email or phone number', 'lawha' ); ?></p>

    <form class="woocommerce-form woocommerce-form-login login" method="post">
      <?php do_action( 'woocommerce_login_form_start' ); ?>

      <div class="form-group">
        <label for="username" class="form-label"><?php esc_html_e( 'Email or Phone Number', 'lawha' ); ?>&nbsp;<span class="required">*</span></label>
        <input type="text" class="form-input" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" placeholder="<?php esc_attr_e( 'you@example.com or +91 XXXXX XXXXX', 'lawha' ); ?>" required />
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
        <button type="button" id="lawhaShowForgot" style="font-size:var(--text-sm);color:var(--secondary);background:none;border:none;cursor:pointer;text-decoration:underline;text-underline-offset:3px;"><?php esc_html_e( 'Forgot password?', 'lawha' ); ?></button>
      </div>

      <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>

      <button type="submit" class="btn btn--primary" name="login" value="<?php esc_attr_e( 'Log in', 'lawha' ); ?>" style="width:100%;">
        <?php esc_html_e( 'Log In', 'lawha' ); ?>
        <span class="btn__arrow">→</span>
      </button>

      <?php do_action( 'woocommerce_login_form_end' ); ?>
    </form>
  </div>

  <!-- ════════════════════════════════════════════════
       REGISTER PANEL — Full Form with Phone OTP Verification
       ════════════════════════════════════════════════ -->
  <?php if ( $enable_registration ) : ?>
  <div class="lawha-auth__panel" id="lawhaRegisterPanel" style="display:none;">
    <h2 class="lawha-auth__heading"><?php esc_html_e( 'Create Account', 'lawha' ); ?></h2>
    <p class="lawha-auth__subtitle"><?php esc_html_e( 'Join LAWHA for a seamless shopping experience', 'lawha' ); ?></p>

    <form method="post" class="woocommerce-form woocommerce-form-register register" id="lawhaRegisterForm" <?php do_action( 'woocommerce_register_form_tag' ); ?>>
      <?php do_action( 'woocommerce_register_form_start' ); ?>

      <div class="form-group">
        <label for="reg_first_name" class="form-label"><?php esc_html_e( 'Full Name', 'lawha' ); ?>&nbsp;<span class="required">*</span></label>
        <input type="text" class="form-input" name="lawha_reg_name" id="reg_first_name" autocomplete="name" value="<?php echo ( ! empty( $_POST['lawha_reg_name'] ) ) ? esc_attr( wp_unslash( $_POST['lawha_reg_name'] ) ) : ''; ?>" required />
      </div>

      <div class="form-group">
        <label for="reg_email" class="form-label"><?php esc_html_e( 'Email Address', 'lawha' ); ?>&nbsp;<span class="required">*</span></label>
        <input type="email" class="form-input" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required />
      </div>

      <!-- Phone with OTP verification -->
      <div class="form-group">
        <label for="reg_phone" class="form-label"><?php esc_html_e( 'Phone Number', 'lawha' ); ?>&nbsp;<span class="required">*</span></label>
        <div class="lawha-phone-verify-wrap">
          <input type="tel" class="form-input" name="lawha_reg_phone" id="reg_phone" autocomplete="tel" value="<?php echo ( ! empty( $_POST['lawha_reg_phone'] ) ) ? esc_attr( wp_unslash( $_POST['lawha_reg_phone'] ) ) : ''; ?>" required />
          <?php if ( $firebase_configured ) : ?>
            <button type="button" id="lawhaRegSendOtp" class="btn btn--outline btn--sm lawha-verify-phone-btn">
              <span class="btn__text"><?php esc_html_e( 'Verify', 'lawha' ); ?></span>
              <span class="btn__spinner lawha-spinner" style="display:none;"></span>
            </button>
          <?php endif; ?>
        </div>
        <input type="hidden" name="lawha_phone_verified" id="lawha_phone_verified" value="0" />
        <div id="lawhaRegPhoneStatus" class="lawha-phone-status" style="display:none;"></div>
      </div>

      <?php if ( $firebase_configured ) : ?>
      <!-- OTP input for registration phone verification -->
      <div id="lawhaRegOtpWrap" class="form-group" style="display:none;">
        <label class="form-label"><?php esc_html_e( 'Verification Code', 'lawha' ); ?></label>
        <div class="lawha-otp-inputs lawha-otp-inputs--reg" dir="ltr">
          <?php for ( $i = 0; $i < 6; $i++ ) : ?>
            <input type="text" class="lawha-otp-digit lawha-reg-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-idx="<?php echo $i; ?>" aria-label="<?php echo esc_attr( sprintf( 'Digit %d', $i + 1 ) ); ?>" />
          <?php endfor; ?>
        </div>
        <button type="button" id="lawhaRegVerifyOtp" class="btn btn--primary btn--sm" style="width:100%;" disabled>
          <span class="btn__text"><?php esc_html_e( 'Verify Code', 'lawha' ); ?></span>
          <span class="btn__spinner lawha-spinner" style="display:none;"></span>
        </button>
        <div class="lawha-auth__resend" style="margin-top:var(--space-3);">
          <span id="lawhaRegResendTimer" class="lawha-auth__timer"></span>
          <button type="button" id="lawhaRegResendOtp" class="lawha-auth__resend-btn" style="display:none;"><?php esc_html_e( 'Resend Code', 'lawha' ); ?></button>
        </div>
      </div>
      <!-- reCAPTCHA container (invisible) -->
      <div id="lawha-recaptcha" class="lawha-recaptcha-container"></div>
      <?php endif; ?>

      <div class="form-group">
        <label for="reg_password" class="form-label"><?php esc_html_e( 'Password', 'lawha' ); ?>&nbsp;<span class="required">*</span></label>
        <input type="password" class="form-input" name="password" id="reg_password" autocomplete="new-password" required minlength="8" />
        <span class="form-hint" style="font-size:var(--text-xs);color:var(--text-secondary);margin-top:var(--space-1);display:block;"><?php esc_html_e( 'Minimum 8 characters', 'lawha' ); ?></span>
      </div>

      <div class="form-group">
        <label for="reg_password_confirm" class="form-label"><?php esc_html_e( 'Confirm Password', 'lawha' ); ?>&nbsp;<span class="required">*</span></label>
        <input type="password" class="form-input" name="lawha_reg_password_confirm" id="reg_password_confirm" autocomplete="new-password" required />
      </div>

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

  <!-- ════════════════════════════════════════════════
       FORGOT PASSWORD PANEL — OTP-based reset
       ════════════════════════════════════════════════ -->
  <div class="lawha-auth__panel" id="lawhaForgotPanel" style="display:none;">
    <?php if ( $firebase_configured ) : ?>
      <!-- Step 1: Enter phone -->
      <div id="lawhaForgotPhoneStep">
        <h2 class="lawha-auth__heading"><?php esc_html_e( 'Reset Password', 'lawha' ); ?></h2>
        <p class="lawha-auth__subtitle"><?php esc_html_e( 'Enter your phone number to receive a verification code', 'lawha' ); ?></p>

        <div class="form-group">
          <label for="forgot_phone" class="form-label"><?php esc_html_e( 'Phone Number', 'lawha' ); ?>&nbsp;<span class="required">*</span></label>
          <input type="tel" class="form-input" id="forgot_phone" autocomplete="tel" required />
        </div>
        <div id="lawha-recaptcha-forgot" class="lawha-recaptcha-container"></div>
        <button type="button" id="lawhaForgotSendOtp" class="btn btn--primary" style="width:100%;">
          <span class="btn__text"><?php esc_html_e( 'Send Verification Code', 'lawha' ); ?></span>
          <span class="btn__spinner lawha-spinner" style="display:none;"></span>
          <span class="btn__arrow">→</span>
        </button>
      </div>

      <!-- Step 2: Verify OTP -->
      <div id="lawhaForgotOtpStep" style="display:none;">
        <h2 class="lawha-auth__heading"><?php esc_html_e( 'Verify Code', 'lawha' ); ?></h2>
        <p class="lawha-auth__subtitle">
          <?php esc_html_e( 'Enter the 6-digit code sent to', 'lawha' ); ?>
          <strong id="lawhaForgotPhoneDisplay"></strong>
        </p>
        <div class="lawha-otp-inputs lawha-otp-inputs--forgot" dir="ltr">
          <?php for ( $i = 0; $i < 6; $i++ ) : ?>
            <input type="text" class="lawha-otp-digit lawha-forgot-otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" data-idx="<?php echo $i; ?>" aria-label="<?php echo esc_attr( sprintf( 'Digit %d', $i + 1 ) ); ?>" />
          <?php endfor; ?>
        </div>
        <button type="button" id="lawhaForgotVerifyOtp" class="btn btn--primary" style="width:100%;" disabled>
          <span class="btn__text"><?php esc_html_e( 'Verify Code', 'lawha' ); ?></span>
          <span class="btn__spinner lawha-spinner" style="display:none;"></span>
          <span class="btn__arrow">→</span>
        </button>
        <div class="lawha-auth__resend" style="margin-top:var(--space-3);">
          <span id="lawhaForgotResendTimer" class="lawha-auth__timer"></span>
          <button type="button" id="lawhaForgotResendOtp" class="lawha-auth__resend-btn" style="display:none;"><?php esc_html_e( 'Resend Code', 'lawha' ); ?></button>
        </div>
      </div>

      <!-- Step 3: Set new password -->
      <div id="lawhaForgotNewPwStep" style="display:none;">
        <h2 class="lawha-auth__heading"><?php esc_html_e( 'Set New Password', 'lawha' ); ?></h2>
        <p class="lawha-auth__subtitle"><?php esc_html_e( 'Choose a strong new password for your account', 'lawha' ); ?></p>

        <div class="form-group">
          <label for="forgot_new_password" class="form-label"><?php esc_html_e( 'New Password', 'lawha' ); ?>&nbsp;<span class="required">*</span></label>
          <input type="password" class="form-input" id="forgot_new_password" autocomplete="new-password" required minlength="8" />
        </div>
        <div class="form-group">
          <label for="forgot_confirm_password" class="form-label"><?php esc_html_e( 'Confirm Password', 'lawha' ); ?>&nbsp;<span class="required">*</span></label>
          <input type="password" class="form-input" id="forgot_confirm_password" autocomplete="new-password" required />
        </div>
        <button type="button" id="lawhaForgotResetPw" class="btn btn--primary" style="width:100%;">
          <span class="btn__text"><?php esc_html_e( 'Reset Password', 'lawha' ); ?></span>
          <span class="btn__spinner lawha-spinner" style="display:none;"></span>
          <span class="btn__arrow">→</span>
        </button>
      </div>

      <!-- Step 4: Success -->
      <div id="lawhaForgotSuccessStep" style="display:none;">
        <div class="lawha-auth__success-icon">✓</div>
        <p class="lawha-auth__subtitle"><?php esc_html_e( 'Password reset successfully! You can now log in.', 'lawha' ); ?></p>
      </div>
    <?php else : ?>
      <!-- Fallback: standard WordPress email reset -->
      <h2 class="lawha-auth__heading"><?php esc_html_e( 'Reset Password', 'lawha' ); ?></h2>
      <p class="lawha-auth__subtitle"><?php esc_html_e( 'Enter your email address to receive a password reset link', 'lawha' ); ?></p>
      <form method="post" action="<?php echo esc_url( wp_lostpassword_url() ); ?>">
        <div class="form-group">
          <label for="forgot_email" class="form-label"><?php esc_html_e( 'Email Address', 'lawha' ); ?>&nbsp;<span class="required">*</span></label>
          <input type="email" class="form-input" name="user_login" id="forgot_email" required />
        </div>
        <button type="submit" class="btn btn--primary" style="width:100%;">
          <?php esc_html_e( 'Send Reset Link', 'lawha' ); ?>
          <span class="btn__arrow">→</span>
        </button>
      </form>
    <?php endif; ?>

    <div style="margin-top:var(--space-5);">
      <button type="button" id="lawhaBackToLogin" class="lawha-auth__back-link">
        ← <?php esc_html_e( 'Back to login', 'lawha' ); ?>
      </button>
    </div>
  </div>

</div>

<script>
(function(){
  'use strict';
  var tabs = document.querySelectorAll('.lawha-auth__tab');
  var loginPanel = document.getElementById('lawhaLoginPanel');
  var registerPanel = document.getElementById('lawhaRegisterPanel');
  var forgotPanel = document.getElementById('lawhaForgotPanel');
  var showForgotBtn = document.getElementById('lawhaShowForgot');
  var backToLoginBtn = document.getElementById('lawhaBackToLogin');
  var msgEl = document.getElementById('lawhaAuthMessage');

  function hideAllPanels() {
    if (loginPanel) loginPanel.style.display = 'none';
    if (registerPanel) registerPanel.style.display = 'none';
    if (forgotPanel) forgotPanel.style.display = 'none';
    if (msgEl) { msgEl.style.display = 'none'; msgEl.textContent = ''; }
  }

  function showPanel(panel) {
    hideAllPanels();
    if (panel) panel.style.display = '';
  }

  function setActiveTab(tabName) {
    tabs.forEach(function(t) {
      t.classList.toggle('is-active', t.getAttribute('data-tab') === tabName);
    });
  }

  if (tabs.length) {
    tabs.forEach(function(tab) {
      tab.addEventListener('click', function() {
        var target = this.getAttribute('data-tab');
        setActiveTab(target);
        if (target === 'login') showPanel(loginPanel);
        else if (target === 'register') showPanel(registerPanel);
      });
    });
  }

  if (showForgotBtn) {
    showForgotBtn.addEventListener('click', function() {
      tabs.forEach(function(t) { t.classList.remove('is-active'); });
      showPanel(forgotPanel);
    });
  }

  if (backToLoginBtn) {
    backToLoginBtn.addEventListener('click', function() {
      setActiveTab('login');
      showPanel(loginPanel);
    });
  }

  // Check URL params for tab switching (e.g., ?tab=register)
  var urlParams = new URLSearchParams(window.location.search);
  var tabParam = urlParams.get('tab');
  if (tabParam === 'register' && registerPanel) {
    setActiveTab('register');
    showPanel(registerPanel);
  } else if (tabParam === 'forgot' && forgotPanel) {
    tabs.forEach(function(t) { t.classList.remove('is-active'); });
    showPanel(forgotPanel);
  }
})();
</script>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>

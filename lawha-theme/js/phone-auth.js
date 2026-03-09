/**
 * LAWHA Theme — Phone-First Authentication
 *
 * Bridges the LAWHA login template (form-login.php) with
 * the WFPL Firebase SDK (headless mode).
 *
 * Dependencies: jQuery, WFPL (wfpl-auth), intl-tel-input
 *
 * @package LAWHA
 */

/* global jQuery, WFPL, wfpl_config, intlTelInput */

(function ($) {
    'use strict';

    /* ================================================================
     *  DOM refs
     * ============================================================= */
    const $phoneStep    = $('#lawhaPhoneStep');
    const $otpStep      = $('#lawhaOtpStep');
    const $successStep  = $('#lawhaSuccessStep');
    const $message      = $('#lawhaAuthMessage');
    const $phoneInput   = $('#lawha_phone');
    const $phoneDisplay = $('#lawhaPhoneDisplay');
    const $sendBtn      = $('#lawhaSendOtp');
    const $verifyBtn    = $('#lawhaVerifyOtp');
    const $resendBtn    = $('#lawhaResendOtp');
    const $timerEl      = $('#lawhaResendTimer');
    const $backBtn      = $('#lawhaBackToPhone');
    const $showEmail    = $('#lawhaShowEmail');
    const $showPhone    = $('#lawhaShowPhone');
    const $emailPanel   = $('#lawhaEmailPanel');
    const $otpDigits    = $('.lawha-otp-digit');

    // Bail if the phone login template is not present.
    if (!$phoneStep.length) return;

    /* ================================================================
     *  State
     * ============================================================= */
    let iti           = null; // intl-tel-input instance
    let timerInterval = null;
    let currentPhone  = '';

    /* ================================================================
     *  Initialize intl-tel-input
     * ============================================================= */
    if ($phoneInput.length && typeof intlTelInput !== 'undefined') {
        iti = intlTelInput($phoneInput[0], {
            preferredCountries: ['sa', 'ae', 'kw', 'bh', 'qa', 'om'],
            separateDialCode:   true,
            utilsScript:        'https://cdn.jsdelivr.net/npm/intl-tel-input@21.1.1/build/js/utils.js',
            initialCountry:     'auto',
            geoIpLookup(callback) {
                fetch('https://ipapi.co/json/')
                    .then(r => r.json())
                    .then(data => callback(data.country_code))
                    .catch(() => callback('sa'));
            },
        });
    }

    /* ================================================================
     *  UI helpers
     * ============================================================= */

    function showMsg(text, type) {
        // type: 'error' | 'success' | 'info'
        $message
            .removeClass('lawha-msg--error lawha-msg--success lawha-msg--info')
            .addClass('lawha-msg--' + type)
            .text(text)
            .slideDown(200);
    }

    function clearMsg() {
        $message.slideUp(150, function () { $(this).text(''); });
    }

    function setLoading($btn, loading) {
        if (loading) {
            $btn.prop('disabled', true);
            $btn.find('.btn__text').hide();
            $btn.find('.btn__spinner').show();
            $btn.find('.btn__arrow').hide();
        } else {
            $btn.prop('disabled', false);
            $btn.find('.btn__text').show();
            $btn.find('.btn__spinner').hide();
            $btn.find('.btn__arrow').show();
        }
    }

    function switchStep(step) {
        $phoneStep.hide();
        $otpStep.hide();
        $successStep.hide();

        if (step === 'phone')   $phoneStep.fadeIn(250);
        if (step === 'otp')     $otpStep.fadeIn(250);
        if (step === 'success') $successStep.fadeIn(250);
    }

    /* ================================================================
     *  OTP digit input behaviour
     * ============================================================= */

    $otpDigits
        .on('input', function () {
            const $this = $(this);
            const val   = $this.val().replace(/\D/g, '');
            $this.val(val);
            // Auto-advance to next digit.
            if (val && $this.next('.lawha-otp-digit').length) {
                $this.next('.lawha-otp-digit').focus();
            }
            // Auto-submit when all 6 entered.
            updateVerifyButton();
        })
        .on('keydown', function (e) {
            const $this = $(this);
            if (e.key === 'Backspace' && !$this.val() && $this.prev('.lawha-otp-digit').length) {
                $this.prev('.lawha-otp-digit').focus();
            }
        })
        .on('paste', function (e) {
            e.preventDefault();
            const paste = (e.originalEvent.clipboardData || window.clipboardData)
                            .getData('text').replace(/\D/g, '').substring(0, 6);
            paste.split('').forEach(function (ch, i) {
                if ($otpDigits[i]) $($otpDigits[i]).val(ch);
            });
            updateVerifyButton();
            if (paste.length === 6) $verifyBtn.focus();
        });

    function getOtpCode() {
        let code = '';
        $otpDigits.each(function () { code += $(this).val(); });
        return code;
    }

    function resetOtpInputs() {
        $otpDigits.val('');
        updateVerifyButton();
    }

    function updateVerifyButton() {
        $verifyBtn.prop('disabled', getOtpCode().length !== 6);
    }

    /* ================================================================
     *  Timer
     * ============================================================= */

    function startTimer() {
        clearTimer();
        let seconds = parseInt(wfpl_config.otp_expiration, 10) || 120;
        $resendBtn.hide();
        $timerEl.show();
        updateTimerText(seconds);

        timerInterval = setInterval(function () {
            seconds--;
            if (seconds <= 0) {
                clearTimer();
                $timerEl.hide();
                $resendBtn.fadeIn(200);
            } else {
                updateTimerText(seconds);
            }
        }, 1000);
    }

    function clearTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }

    function updateTimerText(seconds) {
        var m = String(Math.floor(seconds / 60)).padStart(2, '0');
        var s = String(seconds % 60).padStart(2, '0');
        var tmpl = (wfpl_config.i18n && wfpl_config.i18n.resend_in) || 'Resend in %s';
        $timerEl.text(tmpl.replace('%s', m + ':' + s));
    }

    /* ================================================================
     *  Send OTP
     * ============================================================= */

    $sendBtn.on('click', async function () {
        var phone = iti ? iti.getNumber() : $phoneInput.val();

        if (!phone || phone.length < 8) {
            showMsg(
                (wfpl_config.i18n && wfpl_config.i18n.invalid_phone) || 'Please enter a valid phone number.',
                'error'
            );
            return;
        }

        currentPhone = phone;
        setLoading($sendBtn, true);
        clearMsg();

        try {
            var recaptchaEl = document.getElementById('lawha-recaptcha');
            await WFPL.sendOTP(phone, recaptchaEl);

            showMsg(
                (wfpl_config.i18n && wfpl_config.i18n.otp_sent) || 'Verification code sent!',
                'success'
            );
            $phoneDisplay.text(phone);
            switchStep('otp');
            startTimer();
            $otpDigits.first().focus();
        } catch (err) {
            console.error('[LAWHA Phone Auth]', err);
            var msg = err.message || (wfpl_config.i18n && wfpl_config.i18n.otp_failed) || 'Failed to send code. Please try again.';

            // Friendlier Firebase error messages.
            if (err.code === 'auth/too-many-requests') {
                msg = 'Too many attempts. Please wait a few minutes and try again.';
            } else if (err.code === 'auth/invalid-phone-number') {
                msg = 'Invalid phone number format. Please check and try again.';
            }

            showMsg(msg, 'error');
        } finally {
            setLoading($sendBtn, false);
        }
    });

    /* ================================================================
     *  Verify OTP
     * ============================================================= */

    $verifyBtn.on('click', async function () {
        var code = getOtpCode();
        if (code.length !== 6) {
            showMsg(
                (wfpl_config.i18n && wfpl_config.i18n.enter_otp) || 'Please enter the 6-digit code.',
                'error'
            );
            return;
        }

        setLoading($verifyBtn, true);
        clearMsg();

        try {
            var credential = await WFPL.verifyOTP(code);
            var idToken    = await credential.user.getIdToken();

            showMsg(
                (wfpl_config.i18n && wfpl_config.i18n.verifying) || 'Verifying…',
                'info'
            );

            var result = await WFPL.login(idToken);

            // Success!
            clearTimer();
            switchStep('success');

            $(document).trigger('lawha:phone_login_success', [{ phone: currentPhone, result: result }]);

            // Redirect after a short delay.
            setTimeout(function () {
                window.location.href = result.redirect_url || wfpl_config.redirect_url || wfpl_config.myaccount_url || '/my-account/';
            }, 1200);

        } catch (err) {
            console.error('[LAWHA Phone Auth]', err);
            var msg = err.message || (wfpl_config.i18n && wfpl_config.i18n.verify_failed) || 'Verification failed. Please try again.';

            if (err.code === 'auth/invalid-verification-code') {
                msg = 'Incorrect code. Please check and try again.';
            } else if (err.code === 'auth/code-expired') {
                msg = 'Code has expired. Please request a new one.';
            }

            showMsg(msg, 'error');
            resetOtpInputs();
            $otpDigits.first().focus();
        } finally {
            setLoading($verifyBtn, false);
        }
    });

    /* ================================================================
     *  Back / Resend / Panel toggles
     * ============================================================= */

    $backBtn.on('click', function () {
        clearTimer();
        clearMsg();
        resetOtpInputs();
        switchStep('phone');
        // Reset reCAPTCHA so a new one is created next time.
        if (WFPL._resetRecaptcha) WFPL._resetRecaptcha();
    });

    $resendBtn.on('click', async function () {
        $resendBtn.hide();
        clearMsg();

        try {
            var recaptchaEl = document.getElementById('lawha-recaptcha');
            // Reset reCAPTCHA verifier for a fresh attempt.
            if (WFPL._resetRecaptcha) WFPL._resetRecaptcha();
            await WFPL.sendOTP(currentPhone, recaptchaEl);
            showMsg(
                (wfpl_config.i18n && wfpl_config.i18n.otp_sent) || 'Verification code sent!',
                'success'
            );
            startTimer();
            resetOtpInputs();
            $otpDigits.first().focus();
        } catch (err) {
            showMsg(
                err.message || (wfpl_config.i18n && wfpl_config.i18n.otp_failed) || 'Failed to resend code.',
                'error'
            );
            $resendBtn.show();
        }
    });

    // Toggle phone ↔ email panels.
    $showEmail.on('click', function () {
        $phoneStep.hide();
        $otpStep.hide();
        $successStep.hide();
        $(this).hide();
        $('.lawha-auth__divider').hide();
        $emailPanel.slideDown(250);
        clearMsg();
        clearTimer();
    });

    $showPhone.on('click', function () {
        $emailPanel.slideUp(250, function () {
            switchStep('phone');
            $showEmail.show();
            $('.lawha-auth__divider').show();
        });
        clearMsg();
    });

    /* ================================================================
     *  Keyboard: Enter key triggers verify
     * ============================================================= */

    $otpDigits.last().on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $verifyBtn.trigger('click');
        }
    });

    $phoneInput.on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $sendBtn.trigger('click');
        }
    });

})(jQuery);

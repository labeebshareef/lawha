/**
 * LAWHA Theme — Account Authentication
 *
 * Handles:
 *  1. Registration phone OTP inline verification
 *  2. Forgot password OTP flow (phone → OTP → new password)
 *  3. intl-tel-input initialization for all phone fields
 *
 * Dependencies: jQuery, WFPL (wfpl-auth), intl-tel-input
 *
 * @package LAWHA
 */

/* global jQuery, WFPL, wfpl_config, lawhaAuth, intlTelInput */

(function ($) {
    'use strict';

    /* ================================================================
     *  Shared refs
     * ============================================================= */
    var $message = $('#lawhaAuthMessage');

    /* ================================================================
     *  intl-tel-input factory
     * ============================================================= */
    var itiDefaults = {
        onlyCountries:    ['in', 'ae'],
        initialCountry:   'ae',
        separateDialCode: true,
        utilsScript:      'https://cdn.jsdelivr.net/npm/intl-tel-input@21.1.1/build/js/utils.js'
    };

    function initIti(el) {
        if (el && typeof intlTelInput !== 'undefined') {
            return intlTelInput(el, itiDefaults);
        }
        return null;
    }

    /* ================================================================
     *  UI helpers
     * ============================================================= */

    function showMsg(text, type) {
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

    function friendlyError(err) {
        var msg = err.message || 'Something went wrong. Please try again.';
        if (err.code === 'auth/too-many-requests') msg = 'Too many attempts. Please wait a few minutes and try again.';
        else if (err.code === 'auth/invalid-phone-number') msg = 'Invalid phone number format. Please check and try again.';
        else if (err.code === 'auth/invalid-verification-code') msg = 'Incorrect code. Please check and try again.';
        else if (err.code === 'auth/code-expired') msg = 'Code has expired. Please request a new one.';
        return msg;
    }

    /* ================================================================
     *  OTP digit input behaviour (reusable)
     * ============================================================= */
    function bindOtpInputs($digits, onComplete) {
        $digits
            .on('input', function () {
                var $this = $(this);
                var val   = $this.val().replace(/\D/g, '');
                $this.val(val);
                if (val && $this.next('.lawha-otp-digit').length) {
                    $this.next('.lawha-otp-digit').focus();
                }
                if (onComplete) onComplete();
            })
            .on('keydown', function (e) {
                var $this = $(this);
                if (e.key === 'Backspace' && !$this.val() && $this.prev('.lawha-otp-digit').length) {
                    $this.prev('.lawha-otp-digit').focus();
                }
            })
            .on('paste', function (e) {
                e.preventDefault();
                var paste = (e.originalEvent.clipboardData || window.clipboardData)
                                .getData('text').replace(/\D/g, '').substring(0, 6);
                paste.split('').forEach(function (ch, i) {
                    if ($digits[i]) $($digits[i]).val(ch);
                });
                if (onComplete) onComplete();
            });
    }

    function getOtpCode($digits) {
        var code = '';
        $digits.each(function () { code += $(this).val(); });
        return code;
    }

    function resetOtpInputs($digits) {
        $digits.val('');
    }

    /* ================================================================
     *  Timer (reusable)
     * ============================================================= */
    function createTimer($timerEl, $resendBtn) {
        var interval = null;

        function formatTime(seconds) {
            var m = String(Math.floor(seconds / 60)).padStart(2, '0');
            var s = String(seconds % 60).padStart(2, '0');
            var tmpl = (typeof wfpl_config !== 'undefined' && wfpl_config.i18n && wfpl_config.i18n.resend_in) || 'Resend in %s';
            return tmpl.replace('%s', m + ':' + s);
        }

        return {
            start: function () {
                this.clear();
                var seconds = (typeof wfpl_config !== 'undefined' && parseInt(wfpl_config.otp_expiration, 10)) || 120;
                $resendBtn.hide();
                $timerEl.show().text(formatTime(seconds));

                interval = setInterval(function () {
                    seconds--;
                    if (seconds <= 0) {
                        clearInterval(interval);
                        interval = null;
                        $timerEl.hide();
                        $resendBtn.fadeIn(200);
                    } else {
                        $timerEl.text(formatTime(seconds));
                    }
                }, 1000);
            },
            clear: function () {
                if (interval) { clearInterval(interval); interval = null; }
            }
        };
    }

    /* ================================================================
     *  1. REGISTRATION — Phone OTP Inline Verification
     * ============================================================= */
    (function initRegPhone() {
        var $regPhone   = $('#reg_phone');
        var $sendBtn    = $('#lawhaRegSendOtp');
        var $otpWrap    = $('#lawhaRegOtpWrap');
        var $verifyBtn  = $('#lawhaRegVerifyOtp');
        var $statusEl   = $('#lawhaRegPhoneStatus');
        var $hiddenFlag = $('#lawha_phone_verified');
        var $resendBtn  = $('#lawhaRegResendOtp');
        var $timerEl    = $('#lawhaRegResendTimer');
        var $digits     = $('.lawha-reg-otp-digit');

        if (!$regPhone.length || !$sendBtn.length) return;

        var regIti        = initIti($regPhone[0]);
        var regTimer      = createTimer($timerEl, $resendBtn);
        var regPhone      = '';
        var phoneVerified = false;

        function updateVerifyBtn() {
            $verifyBtn.prop('disabled', getOtpCode($digits).length !== 6);
        }

        bindOtpInputs($digits, updateVerifyBtn);

        /* Send OTP */
        $sendBtn.on('click', async function () {
            var phone = regIti ? regIti.getNumber() : $regPhone.val();
            if (!phone || phone.length < 8) {
                showMsg('Please enter a valid phone number.', 'error');
                return;
            }

            regPhone = phone;
            setLoading($sendBtn, true);
            clearMsg();

            try {
                var recaptchaEl = document.getElementById('lawha-recaptcha');
                await WFPL.sendOTP(phone, recaptchaEl);
                showMsg('Verification code sent to ' + phone, 'success');
                $otpWrap.slideDown(250);
                regTimer.start();
                $digits.first().focus();
            } catch (err) {
                console.error('[LAWHA Reg OTP]', err);
                showMsg(friendlyError(err), 'error');
            } finally {
                setLoading($sendBtn, false);
            }
        });

        /* Verify OTP */
        $verifyBtn.on('click', async function () {
            var code = getOtpCode($digits);
            if (code.length !== 6) return;

            setLoading($verifyBtn, true);
            clearMsg();

            try {
                var credential = await WFPL.verifyOTP(code);
                // Successful verification — store in session via AJAX with Firebase token.
                var idToken = await credential.user.getIdToken();

                var response = await $.ajax({
                    url: lawhaAuth.ajax_url,
                    method: 'POST',
                    data: {
                        action:         'lawha_store_otp_verification',
                        nonce:          lawhaAuth.nonce,
                        phone:          regPhone,
                        firebase_token: idToken
                    }
                });

                if (!response || !response.success) {
                    throw new Error(response && response.data && response.data.message ? response.data.message : 'Failed to store phone verification.');
                }

                phoneVerified = true;
                $hiddenFlag.val('1');
                regTimer.clear();
                $otpWrap.slideUp(200);
                $sendBtn.hide();
                $statusEl.html('✓ Phone verified').addClass('is-verified').show();
                $regPhone.prop('readonly', true);
                showMsg('Phone number verified successfully!', 'success');
            } catch (err) {
                console.error('[LAWHA Reg OTP Verify]', err);
                showMsg(friendlyError(err), 'error');
                resetOtpInputs($digits);
                $digits.first().focus();
            } finally {
                setLoading($verifyBtn, false);
            }
        });

        /* Resend OTP */
        $resendBtn.on('click', async function () {
            $resendBtn.hide();
            clearMsg();
            try {
                if (WFPL._resetRecaptcha) WFPL._resetRecaptcha();
                var recaptchaEl = document.getElementById('lawha-recaptcha');
                await WFPL.sendOTP(regPhone, recaptchaEl);
                showMsg('Verification code re-sent!', 'success');
                regTimer.start();
                resetOtpInputs($digits);
                $digits.first().focus();
            } catch (err) {
                showMsg(friendlyError(err), 'error');
                $resendBtn.show();
            }
        });

        /* Prevent submit if phone not verified */
        $('#lawhaRegisterForm').on('submit', function (e) {
            if (!phoneVerified && $sendBtn.length) {
                e.preventDefault();
                showMsg('Please verify your phone number before creating your account.', 'error');
                $regPhone.focus();
            }
        });
    })();


    /* ================================================================
     *  2. FORGOT PASSWORD — OTP-based Reset
     * ============================================================= */
    (function initForgotPassword() {
        var $forgotPhone    = $('#forgot_phone');
        var $sendBtn        = $('#lawhaForgotSendOtp');
        var $phoneStep      = $('#lawhaForgotPhoneStep');
        var $otpStep        = $('#lawhaForgotOtpStep');
        var $newPwStep      = $('#lawhaForgotNewPwStep');
        var $successStep    = $('#lawhaForgotSuccessStep');
        var $phoneDisplay   = $('#lawhaForgotPhoneDisplay');
        var $verifyBtn      = $('#lawhaForgotVerifyOtp');
        var $resendBtn      = $('#lawhaForgotResendOtp');
        var $timerEl        = $('#lawhaForgotResendTimer');
        var $resetPwBtn     = $('#lawhaForgotResetPw');
        var $digits         = $('.lawha-forgot-otp-digit');

        if (!$forgotPhone.length) return;

        var forgotIti     = initIti($forgotPhone[0]);
        var forgotTimer   = createTimer($timerEl, $resendBtn);
        var forgotPhone   = '';
        var forgotIdToken = '';

        function updateVerifyBtn() {
            $verifyBtn.prop('disabled', getOtpCode($digits).length !== 6);
        }

        bindOtpInputs($digits, updateVerifyBtn);

        function showStep(which) {
            $phoneStep.hide();
            $otpStep.hide();
            $newPwStep.hide();
            $successStep.hide();
            which.fadeIn(250);
        }

        /* Step 1: Check phone then send OTP */
        $sendBtn.on('click', async function () {
            var phone = forgotIti ? forgotIti.getNumber() : $forgotPhone.val();
            if (!phone || phone.length < 8) {
                showMsg('Please enter a valid phone number.', 'error');
                return;
            }

            setLoading($sendBtn, true);
            clearMsg();

            try {
                // First check if phone has an account.
                var checkResult = await $.ajax({
                    url: lawhaAuth.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'lawha_forgot_check_phone',
                        nonce:  lawhaAuth.nonce,
                        phone:  phone
                    }
                });

                if (!checkResult.success) {
                    showMsg(checkResult.data.message || 'No account found with this phone number.', 'error');
                    return;
                }

                forgotPhone = phone;

                // Send OTP via Firebase.
                var recaptchaEl = document.getElementById('lawha-recaptcha-forgot');
                await WFPL.sendOTP(phone, recaptchaEl);

                showMsg('Verification code sent!', 'success');
                $phoneDisplay.text(phone);
                showStep($otpStep);
                forgotTimer.start();
                $digits.first().focus();
            } catch (err) {
                console.error('[LAWHA Forgot OTP]', err);
                showMsg(friendlyError(err), 'error');
            } finally {
                setLoading($sendBtn, false);
            }
        });

        /* Step 2: Verify OTP */
        $verifyBtn.on('click', async function () {
            var code = getOtpCode($digits);
            if (code.length !== 6) return;

            setLoading($verifyBtn, true);
            clearMsg();

            try {
                var credential = await WFPL.verifyOTP(code);
                forgotIdToken = await credential.user.getIdToken();
                forgotTimer.clear();
                showMsg('Phone verified! Set your new password.', 'success');
                showStep($newPwStep);
            } catch (err) {
                console.error('[LAWHA Forgot Verify]', err);
                showMsg(friendlyError(err), 'error');
                resetOtpInputs($digits);
                $digits.first().focus();
            } finally {
                setLoading($verifyBtn, false);
            }
        });

        /* Step 3: Reset password */
        $resetPwBtn.on('click', async function () {
            var newPw      = $('#forgot_new_password').val();
            var confirmPw  = $('#forgot_confirm_password').val();

            if (!newPw || newPw.length < 8) {
                showMsg('Password must be at least 8 characters.', 'error');
                return;
            }
            if (newPw !== confirmPw) {
                showMsg('Passwords do not match.', 'error');
                return;
            }

            setLoading($resetPwBtn, true);
            clearMsg();

            try {
                var result = await $.ajax({
                    url: lawhaAuth.ajax_url,
                    method: 'POST',
                    data: {
                        action:       'lawha_forgot_reset_password',
                        nonce:        lawhaAuth.nonce,
                        phone:        forgotPhone,
                        new_password: newPw,
                        id_token:     forgotIdToken
                    }
                });

                if (!result.success) {
                    showMsg(result.data.message || 'Failed to reset password.', 'error');
                    return;
                }

                showStep($successStep);
                showMsg('Password reset successfully!', 'success');

                // Redirect to login after 2 seconds.
                setTimeout(function () {
                    var backBtn = document.getElementById('lawhaBackToLogin');
                    if (backBtn) backBtn.click();
                }, 2000);
            } catch (err) {
                console.error('[LAWHA Forgot Reset]', err);
                showMsg('Failed to reset password. Please try again.', 'error');
            } finally {
                setLoading($resetPwBtn, false);
            }
        });

        /* Resend OTP */
        $resendBtn.on('click', async function () {
            $resendBtn.hide();
            clearMsg();
            try {
                if (WFPL._resetRecaptcha) WFPL._resetRecaptcha();
                var recaptchaEl = document.getElementById('lawha-recaptcha-forgot');
                await WFPL.sendOTP(forgotPhone, recaptchaEl);
                showMsg('Verification code re-sent!', 'success');
                forgotTimer.start();
                resetOtpInputs($digits);
                $digits.first().focus();
            } catch (err) {
                showMsg(friendlyError(err), 'error');
                $resendBtn.show();
            }
        });
    })();


    /* ================================================================
     *  3. EMAIL VERIFICATION BANNER — Resend
     * ============================================================= */
    (function initEmailVerify() {
        var $resendBtn = $('#lawhaResendVerifyEmail');
        if (!$resendBtn.length) return;

        $resendBtn.on('click', function () {
            var $btn = $(this);
            $btn.prop('disabled', true).text('Sending…');

            $.ajax({
                url: lawhaAuth.ajax_url,
                method: 'POST',
                data: {
                    action: 'lawha_resend_verification_email',
                    nonce:  lawhaAuth.nonce
                },
                success: function (res) {
                    if (res.success) {
                        $btn.text('Email Sent!');
                        setTimeout(function () { $btn.prop('disabled', false).text('Resend Email'); }, 60000);
                    } else {
                        $btn.prop('disabled', false).text('Resend Email');
                        alert(res.data.message || 'Please wait before requesting another email.');
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('Resend Email');
                }
            });
        });
    })();

})(jQuery);

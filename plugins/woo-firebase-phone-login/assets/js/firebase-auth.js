/**
 * WooCommerce Firebase Phone Login — Frontend JS SDK
 *
 * Exposes the global `WFPL` object with methods:
 *   WFPL.init()
 *   WFPL.sendOTP(phone)
 *   WFPL.verifyOTP(code)
 *   WFPL.login(phone, token)
 *
 * @package WFPL
 */

/* global firebase, intlTelInput, wfpl_config, jQuery */

(function ($) {
    'use strict';

    /* ================================================================
     *  State
     * ============================================================= */
    const state = {
        confirmationResult: null,
        recaptchaVerifier:  null,
        iti:                null, // intl-tel-input instance
        timerInterval:      null,
        currentContainer:   null,
        phone:              '',
    };

    /* ================================================================
     *  WFPL Public SDK
     * ============================================================= */
    const WFPL = window.WFPL = {

        /**
         * Initialize Firebase and bind UI.
         */
        init() {
            if (!wfpl_config || !wfpl_config.firebase || !wfpl_config.firebase.apiKey) {
                console.warn('[WFPL] Firebase not configured.');
                return;
            }

            // Initialize Firebase (compat SDK).
            if (!firebase.apps.length) {
                firebase.initializeApp(wfpl_config.firebase);
            }

            // Set language to browser language for reCAPTCHA.
            firebase.auth().languageCode = navigator.language || 'en';

            // Bind all containers.
            $('.wfpl-login-container').each(function () {
                bindContainer($(this));
            });
        },

        /**
         * Send OTP to a phone number.
         *
         * @param {string}      phone     E.164 phone number.
         * @param {HTMLElement}  recaptchaEl  Element for invisible reCAPTCHA.
         * @returns {Promise<firebase.auth.ConfirmationResult>}
         */
        async sendOTP(phone, recaptchaEl) {
            if (!state.recaptchaVerifier) {
                state.recaptchaVerifier = new firebase.auth.RecaptchaVerifier(recaptchaEl, {
                    size: 'invisible',
                    callback() { /* solved */ },
                    'expired-callback'() {
                        state.recaptchaVerifier.render().then(widgetId => {
                            // eslint-disable-next-line no-undef
                            grecaptcha.reset(widgetId);
                        });
                    },
                });
            }

            const confirmation = await firebase.auth().signInWithPhoneNumber(phone, state.recaptchaVerifier);
            state.confirmationResult = confirmation;
            return confirmation;
        },

        /**
         * Verify the OTP code entered by the user.
         *
         * @param {string} code 6-digit OTP.
         * @returns {Promise<firebase.auth.UserCredential>}
         */
        async verifyOTP(code) {
            if (!state.confirmationResult) {
                throw new Error('No OTP request pending.');
            }
            return state.confirmationResult.confirm(code);
        },

        /**
         * Send Firebase ID token to the backend for login.
         *
         * @param {string} idToken Firebase ID token.
         * @returns {Promise<Object>} Backend response.
         */
        async login(idToken) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url:      wfpl_config.ajax_url,
                    method:   'POST',
                    dataType: 'json',
                    data: {
                        action:         'wfpl_verify_token',
                        nonce:          wfpl_config.nonce,
                        firebase_token: idToken,
                    },
                    success(res) {
                        if (res.success) {
                            resolve(res.data);
                        } else {
                            reject(new Error(res.data?.message || 'Login failed.'));
                        }
                    },
                    error(xhr) {
                        reject(new Error(xhr.responseJSON?.data?.message || 'Network error.'));
                    },
                });
            });
        },
    };

    /* ================================================================
     *  Container binding (per login form instance)
     * ============================================================= */

    function bindContainer($container) {
        const ctx = $container.data('context');

        // Intl Tel Input.
        const phoneInput = $container.find('.wfpl-phone-input')[0];
        if (phoneInput && typeof intlTelInput !== 'undefined') {
            const iti = intlTelInput(phoneInput, {
                preferredCountries: ['ae', 'sa', 'in', 'us', 'gb'],
                separateDialCode:  true,
                utilsScript:       'https://cdn.jsdelivr.net/npm/intl-tel-input@21.1.1/build/js/utils.js',
                initialCountry:    'auto',
                geoIpLookup(callback) {
                    fetch('https://ipapi.co/json/')
                        .then(r => r.json())
                        .then(data => callback(data.country_code))
                        .catch(() => callback('ae'));
                },
            });
            $container.data('iti', iti);
        }

        // reCAPTCHA container.
        const recaptchaEl = $container.find('.wfpl-recaptcha')[0];

        // Send OTP button.
        $container.find('.wfpl-send-otp-btn').on('click', async function () {
            const btn   = $(this);
            const iti   = $container.data('iti');
            const phone = iti ? iti.getNumber() : $(phoneInput).val();

            if (!phone || phone.length < 8) {
                showMessage($container, wfpl_config.i18n.invalid_phone, 'error');
                return;
            }

            state.phone = phone;
            setLoading(btn, true);
            clearMessage($container);

            try {
                await WFPL.sendOTP(phone, recaptchaEl);
                showMessage($container, wfpl_config.i18n.otp_sent, 'success');
                switchStep($container, 'otp');
                startTimer($container);
                autoFocusOtp($container);
            } catch (err) {
                console.error('[WFPL]', err);
                showMessage($container, err.message || wfpl_config.i18n.otp_failed, 'error');
                // Reset reCAPTCHA on error.
                state.recaptchaVerifier = null;
            } finally {
                setLoading(btn, false);
            }
        });

        // Verify OTP button.
        $container.find('.wfpl-verify-otp-btn').on('click', async function () {
            const btn  = $(this);
            const code = getOtpValue($container);

            if (code.length !== 6) {
                showMessage($container, wfpl_config.i18n.enter_otp, 'error');
                return;
            }

            setLoading(btn, true);
            clearMessage($container);

            try {
                const credential = await WFPL.verifyOTP(code);
                const idToken    = await credential.user.getIdToken();

                showMessage($container, wfpl_config.i18n.verifying, 'info');

                const result = await WFPL.login(idToken);

                switchStep($container, 'success');

                // Dispatch event for checkout integration and third-party listeners.
                $(document).trigger('wfpl:login_success', [{ ...result, phone: state.phone }]);

                // On checkout, stay on the page so the user can continue filling out billing/shipping.
                // On other pages (My Account, popup, shortcode), redirect.
                if (ctx !== 'checkout') {
                    setTimeout(() => {
                        window.location.href = result.redirect_url || wfpl_config.redirect_url || '/';
                    }, 1000);
                }
            } catch (err) {
                console.error('[WFPL]', err);
                showMessage($container, err.message || wfpl_config.i18n.verify_failed, 'error');
            } finally {
                setLoading(btn, false);
            }
        });

        // OTP digit inputs — auto-advance, paste support.
        $container.find('.wfpl-otp-digit').on('input', function () {
            const $this = $(this);
            const val   = $this.val().replace(/\D/g, '');
            $this.val(val);
            if (val && $this.next('.wfpl-otp-digit').length) {
                $this.next('.wfpl-otp-digit').focus();
            }
        }).on('keydown', function (e) {
            const $this = $(this);
            if (e.key === 'Backspace' && !$this.val() && $this.prev('.wfpl-otp-digit').length) {
                $this.prev('.wfpl-otp-digit').focus();
            }
        }).on('paste', function (e) {
            e.preventDefault();
            const paste = (e.originalEvent.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').substring(0, 6);
            const digits = $container.find('.wfpl-otp-digit');
            paste.split('').forEach((ch, i) => {
                if (digits[i]) $(digits[i]).val(ch);
            });
            if (paste.length === 6) {
                $container.find('.wfpl-verify-otp-btn').focus();
            }
        });

        // Back button.
        $container.find('.wfpl-back-btn').on('click', function () {
            switchStep($container, 'phone');
            clearTimer();
            clearMessage($container);
            resetOtpInputs($container);
            // Reset reCAPTCHA for new attempt.
            state.recaptchaVerifier = null;
            state.confirmationResult = null;
        });

        // Resend button.
        $container.find('.wfpl-resend-btn').on('click', async function () {
            const btn = $(this);
            btn.hide();

            // Reset reCAPTCHA.
            state.recaptchaVerifier = null;
            state.confirmationResult = null;

            try {
                await WFPL.sendOTP(state.phone, recaptchaEl);
                showMessage($container, wfpl_config.i18n.otp_sent, 'success');
                startTimer($container);
                resetOtpInputs($container);
                autoFocusOtp($container);
            } catch (err) {
                showMessage($container, err.message || wfpl_config.i18n.otp_failed, 'error');
                btn.show();
            }
        });

        // Popup modal support.
        if (ctx === 'popup') {
            $(document).on('click', '[data-wfpl-popup]', function (e) {
                e.preventDefault();
                $('#wfpl-modal-overlay').fadeIn(200);
            });
            $container.closest('.wfpl-modal').find('.wfpl-modal-close').on('click', function () {
                $('#wfpl-modal-overlay').fadeOut(200);
            });
            $('#wfpl-modal-overlay').on('click', function (e) {
                if (e.target === this) $(this).fadeOut(200);
            });
        }
    }

    /* ================================================================
     *  UI Helpers
     * ============================================================= */

    function switchStep($container, step) {
        $container.find('.wfpl-step').hide().removeClass('wfpl-active');
        $container.find(`[data-step="${step}"]`).fadeIn(250).addClass('wfpl-active');
    }

    function setLoading($btn, loading) {
        if (loading) {
            $btn.prop('disabled', true).addClass('wfpl-loading');
            $btn.find('.wfpl-btn-text').hide();
            $btn.find('.wfpl-btn-loader').show();
        } else {
            $btn.prop('disabled', false).removeClass('wfpl-loading');
            $btn.find('.wfpl-btn-text').show();
            $btn.find('.wfpl-btn-loader').hide();
        }
    }

    function showMessage($container, text, type) {
        const $msg = $container.find('.wfpl-message');
        $msg.removeClass('wfpl-msg-success wfpl-msg-error wfpl-msg-info')
            .addClass('wfpl-msg-' + type)
            .text(text)
            .fadeIn(200);
    }

    function clearMessage($container) {
        $container.find('.wfpl-message').hide().text('');
    }

    function getOtpValue($container) {
        let code = '';
        $container.find('.wfpl-otp-digit').each(function () {
            code += $(this).val();
        });
        return code;
    }

    function resetOtpInputs($container) {
        $container.find('.wfpl-otp-digit').val('');
    }

    function autoFocusOtp($container) {
        $container.find('.wfpl-otp-digit').first().focus();
    }

    /* ================================================================
     *  Timer
     * ============================================================= */

    function startTimer($container) {
        clearTimer();
        let seconds = parseInt(wfpl_config.otp_expiration, 10) || 300;
        const $timer  = $container.find('.wfpl-timer');
        const $resend = $container.find('.wfpl-resend-btn');

        $resend.hide();
        updateTimerDisplay($timer, seconds);
        $timer.show();

        state.timerInterval = setInterval(() => {
            seconds--;
            if (seconds <= 0) {
                clearTimer();
                $timer.hide();
                $resend.fadeIn(200);
            } else {
                updateTimerDisplay($timer, seconds);
            }
        }, 1000);
    }

    function clearTimer() {
        if (state.timerInterval) {
            clearInterval(state.timerInterval);
            state.timerInterval = null;
        }
    }

    function updateTimerDisplay($timer, seconds) {
        const m = String(Math.floor(seconds / 60)).padStart(2, '0');
        const s = String(seconds % 60).padStart(2, '0');
        $timer.text(wfpl_config.i18n.resend_in.replace('%s', `${m}:${s}`));
    }

    /* ================================================================
     *  Bootstrap
     * ============================================================= */

    $(function () {
        WFPL.init();
    });

})(jQuery);

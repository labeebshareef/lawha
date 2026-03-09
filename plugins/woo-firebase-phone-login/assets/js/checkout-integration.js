/**
 * WooCommerce Checkout integration for phone login.
 *
 * Enforces phone verification before allowing checkout:
 * - Disables "Place Order" button until phone is verified
 * - Shows phone login form prominently
 * - Auto-fills billing phone after verification
 * - Sets hidden field for server-side validation
 *
 * @package WFPL
 */

/* global jQuery, wfpl_config */

(function ($) {
    'use strict';

    $(function () {
        const $wrapper = $('#wfpl-checkout-login-wrapper');
        if (!$wrapper.length) return;

        const $placeOrderBtn = $('#place_order');
        const $hiddenVerified = $('#wfpl_phone_verified');
        const $hiddenPhone = $('#wfpl_verified_phone');

        // Collapse the default WC login prompt.
        const $wcLogin = $('.woocommerce-form-login-toggle');
        if ($wcLogin.length) {
            $wcLogin.hide();
            $('.woocommerce-form-login').hide();
        }

        // --- Determine initial verification state ---
        const isAlreadyVerified = $hiddenVerified.val() === 'yes';

        if (!isAlreadyVerified) {
            // Block checkout: disable Place Order, show visual gate.
            lockCheckout();
        }

        // --- After successful phone login, unlock checkout ---
        $(document).on('wfpl:login_success', function (e, data) {
            const $billingPhone = $('#billing_phone');
            if ($billingPhone.length && data && data.phone) {
                $billingPhone.val(data.phone);
            }

            // Update hidden fields.
            $hiddenVerified.val('yes');
            if ($hiddenPhone.length && data && data.phone) {
                $hiddenPhone.val(data.phone);
            }

            // Unlock checkout.
            unlockCheckout();

            // Trigger WC checkout update.
            $('body').trigger('update_checkout');
        });

        // --- Lock / unlock helpers ---

        function lockCheckout() {
            // Disable place order button.
            $placeOrderBtn
                .prop('disabled', true)
                .css({ opacity: '0.5', cursor: 'not-allowed', position: 'relative' });

            // Add a notice above the button.
            if (!$('#wfpl-checkout-notice').length) {
                const noticeHtml = `
                    <div id="wfpl-checkout-notice" style="
                        background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
                        color: #fff;
                        padding: 14px 20px;
                        border-radius: 8px;
                        margin-bottom: 16px;
                        font-size: 14px;
                        font-weight: 500;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        box-shadow: 0 2px 12px rgba(108,92,231,0.25);
                    ">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <span>Please verify your phone number above to place your order.</span>
                    </div>
                `;
                $placeOrderBtn.closest('.form-row').before(noticeHtml);
            }

            // Scroll to phone login if it's not visible.
            if ($wrapper.length && $wrapper.is(':visible')) {
                $('html, body').animate({
                    scrollTop: $wrapper.offset().top - 100
                }, 600);
            }
        }

        function unlockCheckout() {
            // Re-enable place order button.
            $placeOrderBtn
                .prop('disabled', false)
                .css({ opacity: '1', cursor: 'pointer' });

            // Remove notice.
            $('#wfpl-checkout-notice').slideUp(300, function () {
                $(this).remove();
            });

            // Hide the phone login wrapper.
            $wrapper.slideUp(300);
        }

        // --- Prevent form submission if still not verified (safety net) ---
        $('form.checkout').on('checkout_place_order', function () {
            if ($hiddenVerified.val() !== 'yes') {
                // Show WC error.
                $('.woocommerce-notices-wrapper').first().html(
                    '<div class="woocommerce-error" role="alert">' +
                    'Please verify your phone number before placing your order.' +
                    '</div>'
                );
                lockCheckout();
                return false;
            }
            return true;
        });
    });

})(jQuery);

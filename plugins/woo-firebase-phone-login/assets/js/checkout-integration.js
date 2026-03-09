/**
 * WooCommerce Checkout integration for phone login.
 *
 * Customises the checkout experience:
 * - Hides the default WooCommerce "click to login" coupon bar.
 * - Shows inline phone login.
 * - Auto-fills billing phone after login.
 *
 * @package WFPL
 */

/* global jQuery, wfpl_config */

(function ($) {
    'use strict';

    $(function () {
        const $wrapper = $('#wfpl-checkout-login-wrapper');
        if (!$wrapper.length) return;

        // Collapse the default WC login prompt.
        const $wcLogin = $('.woocommerce-form-login-toggle');
        if ($wcLogin.length) {
            $wcLogin.hide();
            $('.woocommerce-form-login').hide();
        }

        // After successful login, update billing phone field.
        $(document).on('wfpl:login_success', function (e, data) {
            const $billingPhone = $('#billing_phone');
            if ($billingPhone.length && data && data.phone) {
                $billingPhone.val(data.phone);
            }

            // Hide the phone login wrapper once logged in.
            $wrapper.slideUp(300);

            // Trigger WC checkout update.
            $('body').trigger('update_checkout');
        });
    });

})(jQuery);

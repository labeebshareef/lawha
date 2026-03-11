<?php
/**
 * DEPRECATED — Legacy Checkout Enforcer stub.
 *
 * Checkout login enforcement is now handled by:
 *   - template_redirect hook in the theme (lawha_checkout_force_login)
 *   - \PhoneAuth\Auth\Checkout_Guard (added in v2.0)
 *
 * The old session-based phone verification for guests is removed.
 * Instead, guests are simply redirected to My Account before checkout.
 *
 * @package WFPL
 * @deprecated 2.0.0
 */

namespace WFPL\Frontend;

defined( 'ABSPATH' ) || exit;

class Checkout_Enforcer {

    public function __construct() {
        // No-op: checkout enforcement moved to template_redirect hook.
    }
}

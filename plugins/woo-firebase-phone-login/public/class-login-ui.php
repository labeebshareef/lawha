<?php
/**
 * DEPRECATED — Legacy Login UI stub.
 *
 * The plugin now operates in headless mode only.
 * UI rendering is handled by the theme (form-login.php).
 * Asset loading is handled by \PhoneAuth\UI\Asset_Loader.
 *
 * @package WFPL
 * @deprecated 2.0.0
 */

namespace WFPL\Frontend;

defined( 'ABSPATH' ) || exit;

class Login_UI {

    public function __construct() {
        // No-op: headless mode only. Theme controls UI.
    }

    public static function get_login_form_html( $context = '' ) {
        return '<!-- Phone login UI is provided by the theme -->';
    }
}

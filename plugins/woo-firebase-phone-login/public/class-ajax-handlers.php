<?php
/**
 * DEPRECATED — Legacy AJAX Handlers stub.
 *
 * All AJAX handling has been moved to:
 *   - \PhoneAuth\API\Ajax_Endpoints  (api/ajax-endpoints.php)
 *
 * This file is kept to prevent class-not-found errors from the autoloader.
 * It no longer registers any AJAX hooks.
 *
 * @package WFPL
 * @deprecated 2.0.0
 */

namespace WFPL\Frontend;

defined( 'ABSPATH' ) || exit;

class Ajax_Handlers {

    public function __construct() {
        _deprecated_function( __METHOD__, '2.0.0', '\PhoneAuth\API\Ajax_Endpoints::init()' );
    }
}

<?php
/**
 * DEPRECATED — Legacy REST API stub.
 *
 * All REST / AJAX functionality has been moved to:
 *   - \PhoneAuth\API\Ajax_Endpoints  (api/ajax-endpoints.php)
 *
 * The REST routes under wfpl/v1/ are no longer registered.
 * Use the AJAX endpoints (phone_auth_login, phone_auth_register, etc.) instead.
 *
 * @package WFPL
 * @deprecated 2.0.0
 */

namespace WFPL\API;

defined( 'ABSPATH' ) || exit;

class Rest_API {

    public function __construct() {
        _deprecated_function( __METHOD__, '2.0.0', '\PhoneAuth\API\Ajax_Endpoints::init()' );
    }
}

<?php
/**
 * Global public API functions.
 *
 * These are convenience wrappers so developers can call
 * wfpl_verify_firebase_token(), wfpl_login_or_create_user(), etc.
 * without touching namespaced classes.
 *
 * @package WFPL
 */

defined( 'ABSPATH' ) || exit;

/**
 * Verify a Firebase ID token server-side.
 *
 * @param string $token Firebase ID token (JWT).
 * @return array|WP_Error Decoded payload or error.
 */
function wfpl_verify_firebase_token( $token ) {
    return \WFPL\Firebase_Auth::verify_id_token( $token );
}

/**
 * Authenticate: verify token → login or create user.
 *
 * @param string $token Firebase ID token.
 * @return array|WP_Error { success, user_id, created, phone }
 */
function wfpl_authenticate( $token ) {
    return \WFPL\Auth_Controller::authenticate( $token );
}

/**
 * Login or create a user by phone number.
 * NOTE: This bypasses Firebase token verification — use only when
 * you have already verified the phone through your own means.
 *
 * @param string $phone E.164 phone number.
 * @return array|WP_Error { user_id, created }
 */
function wfpl_login_or_create_user( $phone ) {
    return \WFPL\User_Handler::login_or_create( $phone );
}

/**
 * Get WordPress user by phone number.
 *
 * @param string $phone E.164 phone number.
 * @return WP_User|false
 */
function wfpl_get_user_by_phone( $phone ) {
    return \WFPL\User_Handler::get_user_by_phone( $phone );
}

/**
 * Check if a phone number is already registered.
 *
 * @param string $phone E.164 phone number.
 * @return bool
 */
function wfpl_is_phone_registered( $phone ) {
    return \WFPL\User_Handler::is_phone_registered( $phone );
}

/**
 * Check if the current phone is rate-limited.
 *
 * @param string $phone E.164 phone number.
 * @return bool
 */
function wfpl_is_rate_limited( $phone ) {
    return \WFPL\Helpers::is_rate_limited( $phone );
}

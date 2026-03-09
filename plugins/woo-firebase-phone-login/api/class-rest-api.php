<?php
/**
 * REST API endpoints for headless authentication.
 *
 * @package WFPL
 */

namespace WFPL\API;

use WFPL\Helpers;
use WFPL\Auth_Controller;
use WFPL\User_Handler;

defined( 'ABSPATH' ) || exit;

/**
 * Class Rest_API
 *
 * Registers WP REST routes under the wfpl/v1 namespace.
 */
class Rest_API {

    /**
     * Route namespace.
     */
    const NAMESPACE = 'wfpl/v1';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register all routes.
     */
    public function register_routes() {

        // POST /wfpl/v1/send-otp — returns Firebase config for client-side OTP.
        register_rest_route( self::NAMESPACE, '/send-otp', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'handle_send_otp' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'phone' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => array( '\WFPL\Helpers', 'sanitize_phone' ),
                    'validate_callback' => function ( $value ) {
                        return Helpers::is_valid_e164( Helpers::sanitize_phone( $value ) );
                    },
                ),
            ),
        ) );

        // POST /wfpl/v1/verify — verify token only (no login).
        register_rest_route( self::NAMESPACE, '/verify', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'handle_verify' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'firebase_token' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );

        // POST /wfpl/v1/login — full login/register flow.
        register_rest_route( self::NAMESPACE, '/login', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'handle_login' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'firebase_token' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );

        // GET /wfpl/v1/check-phone — check registration status.
        register_rest_route( self::NAMESPACE, '/check-phone', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array( $this, 'handle_check_phone' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'phone' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => array( '\WFPL\Helpers', 'sanitize_phone' ),
                    'validate_callback' => function ( $value ) {
                        return Helpers::is_valid_e164( Helpers::sanitize_phone( $value ) );
                    },
                ),
            ),
        ) );
    }

    /*--------------------------------------------------------------
     * Route handlers
     *------------------------------------------------------------*/

    /**
     * POST /send-otp — Returns Firebase config for client-side OTP dispatch.
     *
     * OTP is sent by the client using Firebase JS SDK, not server-side.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function handle_send_otp( $request ) {
        $phone = $request->get_param( 'phone' );

        // Rate limit.
        if ( Helpers::is_rate_limited( $phone ) ) {
            return new \WP_REST_Response( array(
                'success' => false,
                'message' => __( 'Too many requests. Please try again later.', 'woo-firebase-phone-login' ),
            ), 429 );
        }

        Helpers::increment_otp_counter( $phone );

        // Check if Firebase is configured.
        if ( ! Helpers::is_firebase_configured() ) {
            return new \WP_REST_Response( array(
                'success' => false,
                'message' => __( 'Firebase is not configured.', 'woo-firebase-phone-login' ),
            ), 500 );
        }

        return new \WP_REST_Response( array(
            'success'  => true,
            'phone'    => $phone,
            'firebase' => Helpers::get_firebase_config(),
            'message'  => __( 'Use the Firebase config to send OTP from the client.', 'woo-firebase-phone-login' ),
        ), 200 );
    }

    /**
     * POST /verify — Verify Firebase ID token (no login).
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function handle_verify( $request ) {
        $token = $request->get_param( 'firebase_token' );

        $result = Auth_Controller::verify_only( $token );

        if ( is_wp_error( $result ) ) {
            return self::error_response( $result );
        }

        return new \WP_REST_Response( $result, 200 );
    }

    /**
     * POST /login — Full authentication flow.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function handle_login( $request ) {
        $token = $request->get_param( 'firebase_token' );

        $result = Auth_Controller::authenticate( $token );

        if ( is_wp_error( $result ) ) {
            return self::error_response( $result );
        }

        return new \WP_REST_Response( $result, 200 );
    }

    /**
     * GET /check-phone — Check if phone is registered.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function handle_check_phone( $request ) {
        $phone = $request->get_param( 'phone' );

        /**
         * Filter whether to reveal phone registration status.
         * Set to false to prevent user enumeration attacks.
         *
         * @param bool   $reveal Whether to reveal registration status.
         * @param string $phone  E.164 phone number.
         */
        $reveal = apply_filters( 'wfpl_reveal_phone_status', true, $phone );

        return new \WP_REST_Response( array(
            'success'    => true,
            'phone'      => $phone,
            'registered' => $reveal ? User_Handler::is_phone_registered( $phone ) : true,
        ), 200 );
    }

    /*--------------------------------------------------------------
     * Helpers
     *------------------------------------------------------------*/

    /**
     * Convert a WP_Error to a REST response.
     *
     * @param \WP_Error $error Error object.
     * @return \WP_REST_Response
     */
    private static function error_response( $error ) {
        $status = 400;
        $data   = $error->get_error_data();
        if ( is_array( $data ) && isset( $data['status'] ) ) {
            $status = $data['status'];
        }

        return new \WP_REST_Response( array(
            'success' => false,
            'code'    => $error->get_error_code(),
            'message' => $error->get_error_message(),
        ), $status );
    }
}

<?php
/**
 * WooCommerce Settings tab for Firebase Phone Login.
 *
 * @package WFPL
 */

namespace WFPL\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings_Page
 *
 * Adds a "Firebase Phone Login" tab under WooCommerce → Settings.
 */
class Settings_Page {

    /**
     * Constructor.
     */
    public function __construct() {
        add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
        add_action( 'woocommerce_settings_tabs_wfpl', array( $this, 'output_settings' ) );
        add_action( 'woocommerce_update_options_wfpl', array( $this, 'save_settings' ) );

        // Add admin user column for phone.
        add_filter( 'manage_users_columns', array( $this, 'add_phone_column' ) );
        add_filter( 'manage_users_custom_column', array( $this, 'render_phone_column' ), 10, 3 );
    }

    /*--------------------------------------------------------------
     * Tab registration
     *------------------------------------------------------------*/

    /**
     * Add the settings tab.
     *
     * @param array $tabs Existing WC settings tabs.
     * @return array
     */
    public function add_settings_tab( $tabs ) {
        $tabs['wfpl'] = __( 'Firebase Phone Login', 'woo-firebase-phone-login' );
        return $tabs;
    }

    /*--------------------------------------------------------------
     * Output
     *------------------------------------------------------------*/

    /**
     * Render the settings page.
     */
    public function output_settings() {
        woocommerce_admin_fields( $this->get_settings() );
    }

    /**
     * Save settings.
     */
    public function save_settings() {
        woocommerce_update_options( $this->get_settings() );
    }

    /*--------------------------------------------------------------
     * Fields definition
     *------------------------------------------------------------*/

    /**
     * Get all settings fields.
     *
     * @return array
     */
    private function get_settings() {
        return array(

            /*--- General Section ---*/
            array(
                'title' => __( 'General Settings', 'woo-firebase-phone-login' ),
                'type'  => 'title',
                'desc'  => __( 'Configure phone-based login and registration.', 'woo-firebase-phone-login' ),
                'id'    => 'wfpl_general_section',
            ),

            array(
                'title'   => __( 'Enable Phone Login', 'woo-firebase-phone-login' ),
                'desc'    => __( 'Allow customers to log in using their phone number.', 'woo-firebase-phone-login' ),
                'id'      => 'wfpl_enable_login',
                'default' => 'yes',
                'type'    => 'checkbox',
            ),

            array(
                'title'   => __( 'Enable Phone Registration', 'woo-firebase-phone-login' ),
                'desc'    => __( 'Allow new users to register using their phone number.', 'woo-firebase-phone-login' ),
                'id'      => 'wfpl_enable_registration',
                'default' => 'yes',
                'type'    => 'checkbox',
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'wfpl_general_section',
            ),

            /*--- Firebase Configuration Section ---*/
            array(
                'title' => __( 'Firebase Configuration', 'woo-firebase-phone-login' ),
                'type'  => 'title',
                'desc'  => __( 'Enter your Firebase project credentials. You can find these in the Firebase Console → Project Settings → General.', 'woo-firebase-phone-login' ),
                'id'    => 'wfpl_firebase_section',
            ),

            array(
                'title'    => __( 'Firebase API Key', 'woo-firebase-phone-login' ),
                'desc_tip' => __( 'Your Firebase Web API key.', 'woo-firebase-phone-login' ),
                'id'       => 'wfpl_firebase_api_key',
                'default'  => '',
                'type'     => 'text',
                'css'      => 'min-width: 400px;',
            ),

            array(
                'title'    => __( 'Firebase Project ID', 'woo-firebase-phone-login' ),
                'desc_tip' => __( 'Your Firebase project ID (e.g., my-app-12345).', 'woo-firebase-phone-login' ),
                'id'       => 'wfpl_firebase_project_id',
                'default'  => '',
                'type'     => 'text',
                'css'      => 'min-width: 400px;',
            ),

            array(
                'title'    => __( 'Firebase Auth Domain', 'woo-firebase-phone-login' ),
                'desc_tip' => __( 'Your Firebase auth domain (e.g., my-app-12345.firebaseapp.com).', 'woo-firebase-phone-login' ),
                'id'       => 'wfpl_firebase_auth_domain',
                'default'  => '',
                'type'     => 'text',
                'css'      => 'min-width: 400px;',
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'wfpl_firebase_section',
            ),

            /*--- OTP Configuration Section ---*/
            array(
                'title' => __( 'OTP Configuration', 'woo-firebase-phone-login' ),
                'type'  => 'title',
                'desc'  => __( 'Configure OTP security and rate limiting.', 'woo-firebase-phone-login' ),
                'id'    => 'wfpl_otp_section',
            ),

            array(
                'title'             => __( 'OTP Expiration (seconds)', 'woo-firebase-phone-login' ),
                'desc_tip'          => __( 'Time in seconds before the OTP expires on the frontend timer. Firebase manages actual expiration server-side.', 'woo-firebase-phone-login' ),
                'id'                => 'wfpl_otp_expiration',
                'default'           => '300',
                'type'              => 'number',
                'custom_attributes' => array(
                    'min'  => 60,
                    'max'  => 600,
                    'step' => 30,
                ),
            ),

            array(
                'title'             => __( 'Max OTP Requests / Hour', 'woo-firebase-phone-login' ),
                'desc_tip'          => __( 'Maximum number of OTP requests a single phone number can make per hour.', 'woo-firebase-phone-login' ),
                'id'                => 'wfpl_max_otp_per_hour',
                'default'           => '5',
                'type'              => 'number',
                'custom_attributes' => array(
                    'min'  => 1,
                    'max'  => 20,
                    'step' => 1,
                ),
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'wfpl_otp_section',
            ),
        );
    }

    /*--------------------------------------------------------------
     * Admin users list — phone column
     *------------------------------------------------------------*/

    /**
     * Add a "Phone" column to the Users table.
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function add_phone_column( $columns ) {
        $columns['billing_phone'] = __( 'Phone', 'woo-firebase-phone-login' );
        return $columns;
    }

    /**
     * Render the phone column content.
     *
     * @param string $value       Current column value.
     * @param string $column_name Column name.
     * @param int    $user_id     User ID.
     * @return string
     */
    public function render_phone_column( $value, $column_name, $user_id ) {
        if ( 'billing_phone' === $column_name ) {
            $phone = get_user_meta( $user_id, 'billing_phone', true );
            return $phone ? esc_html( $phone ) : '—';
        }
        return $value;
    }
}

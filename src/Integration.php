<?php


/**
* WooCommerce Integration class to handle settings
*
* @package  incredimike\WCShipmentTrackingByNote
* @since 1.0.0
*/

class WC_STBN_Integration extends WC_Integration {


    /**
     * Init and hook in the integration.
     */
    public function __construct()
    {
        global $woocommerce;

        $this->id                 = WC_STBN_ID;
        $this->method_title       = __( 'Tracking by API Note', 'wc-stbn-plugin' );

        $description = 'This plugin provides added functionality to the Shipping Tracking plugin for WooCommerce. 
                        Once enabled, this add-on looks for specially-formatted order notes submitted through the API. 
                        When a note with shipping tracking is found, this add-on will create a new Shipping Tracking 
                        note for the order.';

        $this->method_description = __( $description, 'wc-stbn-plugin' );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->wc_stbn_enabled              = $this->get_option( 'wc_stbn_enabled' );
        $this->wc_stbn_note_regex           = $this->get_option( 'wc_stbn_note_regex' );
        $this->wc_stbn_shipping_carrier     = $this->get_option( 'wc_stbn_shipping_carrier' );
        $this->wc_stbn_order_status         = $this->get_option( 'wc_stbn_order_status' );
        $this->wc_stbn_logging              = $this->get_option( 'wc_stbn_logging' );


        // Actions.
        add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
        // Filters.
        add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'sanitize_settings' ) );

        // Display an admin notice, if setup is required.
        add_action( 'admin_notices', array( $this, 'maybe_display_admin_notices' ) );
    }


    /**
     * Initialize integration settings form fields.
     * @since 1.0.0
     */
    public function init_form_fields()
    {

        /*
         * Enable the plugin
         */
        $this->form_fields['wc_stbn_enabled'] = array(
            'title'         => __( 'Enable Tracking by Note', 'wc-stbn-plugin' ),
            'label'         => __( 'Enable', 'wc-stbn-plugin'),
            'type'          => 'checkbox',
            'default'       => 'no'
        );

        /*
         * Configure the regular expression to be used to fetch the Tracking Number
         */
        $this->form_fields['wc_stbn_note_regex'] = array(
            'title'         => __( 'Note Regular Expression', 'wc-stbn-plugin' ),
            'type'          => 'text',
            'description'   => __( 'Default regular expression is "Tracking Number: (.*)$"', 'wc-stbn-plugin' ),
            'default'       => 'Tracking Number: (.*)$'
        );

        /*
         * Select the Shipping Carrier to be used for tracked shipments.
         */

        $providers = (new WC_Shipment_Tracking_Actions())->get_providers();
        $provider_options = [];
        foreach ($providers as $region => $group) {
            $key = wc_clean(sanitize_title($region));
            $provider_options[$key] = $region;
            foreach ($group as $service => $url) {
                $key = wc_clean(sanitize_title($service));
                $provider_options[$key] = "&nbsp;&nbsp;{$service}";
            }
        }

        $this->form_fields['wc_stbn_shipping_carrier'] = array(
            'title'         => __( 'Shipping Carrier', 'wc-stbn-plugin' ),
            'type'          => 'select',
            'description'   => __( 'Currently this plugin supports one shipping provider.', 'wc-stbn-plugin' ),
            'options'       => $provider_options,
            'default'       => 'ups'
        );

        /*
         * Change Order Status after Tracking Shipment
         */
        $order_statuses = ['no-change' => 'No Change'] + wc_get_order_statuses();
        $this->form_fields['wc_stbn_order_status'] = array(
                'disabled'      => true,
                'title'         => __( 'Change Order Status after Shipping', 'wc-stbn-plugin' ),
                'type'          => 'select',
                'description'   => __( 'Beta feature currently disabled.', 'wc-stbn-plugin' ),
                'options'       => $order_statuses,
                'default'       => 'no-change',
        );


        // Debug logging
        $label = __( 'Enable Logging', $this->id );

        if ( defined( 'WC_LOG_DIR' ) ) {
            $log_url = add_query_arg( 'tab', 'logs', add_query_arg( 'page', 'wc-status', admin_url( 'admin.php' ) ) );
            $log_key = $this->id . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '-log';
            $log_url = add_query_arg( 'log_file', $log_key, $log_url );
            $label .= ' | ' . sprintf( __( '%1$sView Log%2$s', $this->id ), '<a href="' . esc_url( $log_url ) . '">', '</a>' );
        }

        $this->form_fields['wc_stbn_logging'] = array(
            'title'       => __( 'Debug Log', $this->id ),
            'label'       => $label,
            'description' => __( 'Enable the logging of errors.', $this->id),
            'type'        => 'checkbox',
            'default'     => 'no'
        );
    }


    /**
     * Santize our settings
     * @see process_admin_options()
     */
    public function sanitize_settings( $settings )
    {
        return $settings;
    }

    /**
     * @since 1.1.0
     * @param $key
     * @param $value
     * @return mixed
     */
    public function validate_wc_stbn_note_regex_field( $key, $value ) {
        if ( isset( $value ) && @preg_match('~'.$value.'~', null) === false ) {
            WC_Admin_Settings::add_error( esc_html__( 'The regular expression appears invalid.', 'wcstbn' ) );
        }

        return $value;
    }

    /**
     * Display an admin notice, if not on the integration screen and if the account isn't yet connected.
     * @access public
     * @since 1.0.0
     * @return void
     */
    public function maybe_display_admin_notices ()
    {
        // Don't show these notices on our admin screen.
        if ( isset( $_GET['page'] ) && 'wc-settings' == $_GET['page'] && isset( $_GET['section'] ) && $this->id == $_GET['section'] ) {
            return; // Don't show these notices on our admin screen.
        }
    } // End maybe_display_admin_notices()

    /**
     * Generate a URL to our specific settings screen.
     * @access public
     * @since 1.0.0
     * @return string Generated URL.
     */
    public function get_settings_url () {
        $url = admin_url( 'admin.php' );
        $url = add_query_arg( 'page', 'wc-settings', $url );
        $url = add_query_arg( 'tab', 'integration', $url );
        $url = add_query_arg( 'section', $this->id, $url );
        return $url;
    }
}

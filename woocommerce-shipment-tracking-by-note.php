<?php
/*
Plugin Name:  WooCommerce Shipment Tracking Add-on: Track By Note
Plugin URI:   https://github.com/incredimike/
Description:  Extends the WooCommerce Shipment Tracking Extension to register shipment tracking when tracking info is discovered in an order note.
Version:      1.1.1
Author:       incredimike
Author URI:   https://incredimike.com
License:      MIT License
*/


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WC_STBN_ID',       'wc-stbn-api' );
define( 'WC_STBN_VERSION',  '1.1.1' );
define( 'WC_STBN_FILE',     __FILE__ );
define( 'WC_STBN_PATH',     plugin_dir_path( WC_STBN_FILE ) );
define( 'WC_STBN_URL',      plugin_dir_url( WC_STBN_FILE ) );

/*
 * Require Composer autoloader if installed on it's own
 */
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/*
 * Set up Tracking By Note functionality.
 */
require_once __DIR__ . '/src/ShipmentTrackingByNote.php';

/*
 * Set up Tracking By Note Integration with WooCommerce fields (Setup -> Integration)
 */
if ( ! class_exists( 'WC_STBN_Setup' ) ) :
    class WC_STBN_Setup
    {
        /**
         * Construct the plugin.
         */
        public function __construct()
        {
            add_action('plugins_loaded', array($this, 'init'));
        }

        public function init()
        {
            // Checks if WooCommerce is installed.
            if (class_exists('WC_Integration')) {
                // Include our integration class.
                require_once __DIR__ . '/src/Integration.php';
                // Register the integration.
                add_filter('woocommerce_integrations', [$this, 'add_integration']);
            }
        }

        public function add_integration($integrations)
        {
            $integrations[] = 'WC_STBN_Integration';
            return $integrations;
        }

    }
    $WC_STBN_Plugin = new WC_STBN_Setup( __FILE__ );
endif;
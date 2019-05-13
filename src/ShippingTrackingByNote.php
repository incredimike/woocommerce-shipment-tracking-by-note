<?php

/**
 * Class ShippingTrackingByNote
 *
 * Handles all the note-to-tracking logic.
 *
 * @package  incredimike\WCShipmentTrackingByNote
 * @since 1.0.0
 */
class ShippingTrackingByNote
{
    protected $carrier;
    protected $enabled;
    protected $id;
    protected $logging;
    protected $regex;
    protected $tracking_number;
    protected $tracking_provider;

    /**
     * ShippingTrackingByNote constructor.
     */
    public function __construct()
    {
        $this->id = WC_STBN_ID;

        add_action('woocommerce_rest_insert_order_note', [$this, 'capture_inserted_note'], 3, 20);
    }

    /**
     * Process a captured note
     * @since 1.0.0
     * @param $note
     * @param $request
     * @param bool $create
     * @return bool|WP_Error
     */
    public function capture_inserted_note($note, $request, $create=false)
    {
        $this->enabled      = $this->get_option('wc_stbn_enabled');
        $this->regex        = $this->get_option('wc_stbn_note_regex');
        $this->logging      = $this->get_option('wc_stbn_logging');
        $order_id = $note->comment_post_ID;


        // If disabled, end early.
        if (!$this->enabled) {
            $this->log('New Note but plugin disabled. Skipping.');
            return false;
        }

        // Only fire on new notes
        if (!$create) {
            $this->log('Note updated. Skipping.');
            return false;
        }

        $tracking_number = $this->parse_tracking_number( $note, $this->regex );
        $carrier_code = $this->get_carrier( $note, $order_id );

        // Pulled this configuration from WC_Shipment_Tracking_V1_REST_API_Controller around line 217
        $args = array(
            'custom_tracking_provider' => wc_clean( '' ),
            'custom_tracking_link'     => wc_clean( '' ),
            'date_shipped'             => wc_clean( date() ),
            'tracking_number'          => wc_clean( $tracking_number ),
            'tracking_provider'        => wc_clean( sanitize_title( $carrier_code ) ),
        );

        // Add the new tracking item to Shipment Tracking.
        WC_Shipment_Tracking_Actions::get_instance()->add_tracking_item( $order_id, $args );

        $this->update_order_status( $order_id );

    }

    /**
     * Get Tracking code from the note content
     * @since 1.1.0
     * @param $note     String  Note text
     * @param $regex    String  Regular expression
     * @return bool|string
     */
    private function parse_tracking_number( $note, $regex )
    {
        $note_content = $note->comment_content;
        // Search for tracking number in note. If it doesn't exist, end early.
        preg_match('/' . $regex . '/i', $note_content, $matches);
        if (empty($matches[1])) {
            $this->log('Could not locate Tracking Number in note. Skipping.');
            return false;
        }

        // Set the tracking number to the first match.
        return $matches[1];
    }

    /**
     * Return the value of the default carrier set in the setting panel
     * This can be overridden by
     * @since 1.1.0
     */
    private function get_carrier($note)
    {
        $carrier = $this->get_option('wc_stbn_shipping_carrier');
        $order_id = $note->comment_post_ID;

        return apply_filters('wc_stbn_shipping_carrier', $carrier, $order_id);
    }

    /**
     * Maybe update the order status
     * @since 1.1.0
     * @param $order_id
     * @return bool
     */
    private function update_order_status( $order_id )
    {
        $status = $this->get_option('wc_stbn_order_status');
        if ('no-change' === $status) { return false; }

        $order = new WC_Order($order_id);
        $order->update_status($status);
    }

    /**
     * Get the value of an Integration Option set via the admin.
     *
     * @since 1.0.0
     * @param $name
     * @return mixed
     */
    private function get_option( $name )
    {
        return \WC()->integrations->integrations[$this->id]->get_option($name);
    }

    /**
     * Checks if an order ID is a valid order. Borrowed from Shipping Tracking
     *
     * @since 1.0.0
     * @param int $order_id
     * @return bool
     */
    public function is_valid_order_id( $order_id ) {
        if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
            $order = get_post( $order_id );
            if ( empty( $order->post_type ) || $this->post_type !== $order->post_type ) {
                return false;
            }
        } else {
            $order = wc_get_order( $order_id );
            // in 3.0 the order factor will return false if the order class
            // throws an exception or the class doesn't exist.
            if ( false === $order ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Logging Helper
     *
     * @since 1.0.0
     * @param $message
     * @param string $level
     */
    private function log($message, $level='debug')
    {
        if (!$this->logging) return;

        $logger = \wc_get_logger();
        $context = array( 'source' => $this->id );
        if (is_array($message)) {
           $message = json_encode($message);
        }
        $logger->log($level, $message, $context);
    }
}

new ShippingTrackingByNote();
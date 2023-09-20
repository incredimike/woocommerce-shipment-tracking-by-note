# woocommerce-shipment-tracking-by-note

## A Brief Description
Extend the WooCommerce Shipment Tracking Extension to register shipment tracking when tracking info is discovered
in an order note.

## What does this plugin do?
This plugin adds functionality to the official [Shipping Tracking](https://woocommerce.com/products/shipment-tracking/) plugin for WooCommerce.

This add-on will watch for new order notes to be submitted through the WooCommerce API Shipping Tracking endpoint for any notes which match a specified regular expression (regex), as configured in the plugin's settings. When a submitted order note matches the check, this add-on will create a new Shipping Tracking note for the order, which triggers the rest of the workflows (emailing customer, setting order to "shipped" status, etc).

### Why does this plugin exist?

WooCommerce integrates with hundreds of 3rd party software packages, including ERPs, accounting systems, order management systems, etc. Most of those 3rd party system integrations will handle creating new Order Notes for a WooCommerce order, but few (if any) will support the WooCommerce [Shipping Tracking](https://woocommerce.com/products/shipment-tracking/) plugin directly, despite it being a premium plugin. That means that the external systems have no way to mark orders as "shipped" in WooCommerce. 

This WooCommerce plugin allows you to work around that issue by using the exising, well-supported Order Note APIs to trigger the remaining workflows for shipped orders within WooCommerce.

<?php
/*
Plugin Name: PAYMENT BOOK Gateway
Description: WooCommerce payment gateway for PAYMENT BOOK
Version: 0.0.1
Author: DOMONK Factory
*/

if ( ! defined( 'ABSPATH' ) ) exit;

require __DIR__ . '/vendor/autoload.php';

add_action( 'plugins_loaded', function() {

    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

    require_once __DIR__ . '/includes/class-payment-book-gateway.php';

    add_filter( 'woocommerce_payment_gateways', function( $gateways ) {
        $gateways[] = 'PBGateway\\PaymentBook_Gateway';
        return $gateways;
    });
});

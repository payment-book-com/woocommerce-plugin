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

    // Add DOB field to checkout
    add_filter( 'woocommerce_checkout_fields', function( $fields ) {
        $fields['billing']['billing_dob'] = array(
            'type'        => 'date',
            'label'       => __('Date of Birth', 'woocommerce'),
            'placeholder' => _x('YYYY-MM-DD', 'placeholder', 'woocommerce'),
            'required'    => true,
            'class'       => array('form-row-wide'),
            'priority'    => 25,
        );
        return $fields;
    });

    // Validate DOB
    add_action( 'woocommerce_checkout_process', function() {
        if ( ! $_POST['billing_dob'] ) {
            wc_add_notice( __( 'Please enter your date of birth.', 'woocommerce' ), 'error' );
        }
        // Basic age validation (server-side backup to UI)
        $dob = strtotime( $_POST['billing_dob'] );
        $age = (time() - $dob) / 31557600; // 365.25 * 24 * 60 * 60
        if ($age < 18 || $age > 100) {
             wc_add_notice( __( 'You must be between 18 and 100 years old.', 'woocommerce' ), 'error' );
        }
    });

    // Save DOB
    add_action( 'woocommerce_checkout_update_order_meta', function( $order_id ) {
        if ( ! empty( $_POST['billing_dob'] ) ) {
            update_post_meta( $order_id, 'billing_dob', sanitize_text_field( $_POST['billing_dob'] ) );
        }
    });

    // Display DOB in Admin
    add_action( 'woocommerce_admin_order_data_after_billing_address', function( $order ) {
        echo '<p><strong>' . __( 'Date of Birth', 'woocommerce' ) . ':</strong> ' . get_post_meta( $order->get_id(), 'billing_dob', true ) . '</p>';
    }, 10, 1 );

    require_once __DIR__ . '/includes/payment_book-gateway.php';

    add_filter( 'woocommerce_payment_gateways', function( $gateways ) {
        $gateways[] = 'PBGateway\\PaymentBook_Gateway';
        return $gateways;
    });
});

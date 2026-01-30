<?php
namespace PBGateway;

use PB\Signer;

if (!defined('ABSPATH')) {
    exit();
}

class PaymentBook_Gateway extends \WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'payment_book';
        $this->has_fields = false;
        $this->method_title = 'PAYMENT BOOK';
        $this->method_description = 'Pay via PAYMENT BOOK gateway.';

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->token_name = $this->get_option('token_name');
        $this->token = $this->get_option('token');
        $this->service_id = $this->get_option('service_id');
        $this->api_url = $this->get_option('api_url', 'https://payment-book.com/api/transaction/purchase/create');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_api_payment_book', [$this, 'check_callback']);
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Enable PAYMENT BOOK',
                'default' => 'yes',
            ],
            'title' => [
                'title' => 'Title',
                'type' => 'text',
                'description' => 'Payment method title visible to customers',
                'default' => 'Pay with PAYMENT BOOK',
            ],
            'description' => [
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'Payment method description',
                'default' => 'Pay securely using PAYMENT BOOK',
            ],
            'api_url' => [
                'title' => 'API URL',
                'type' => 'text',
                'default' => 'https://payment-book.com/api/transaction/purchase/create',
            ],
            'token_name' => [
                'title' => 'API Key Name',
                'type' => 'text',
            ],
            'token' => [
                'title' => 'API Secret Key',
                'type' => 'password',
            ],
            'service_id' => [
                'title' => 'Service ID',
                'type' => 'text',
            ],
        ];
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        $amount = $order->get_total();
        $currency = $order->get_currency();
        // Fallback for DOB if not set (should not happen due to validation)
        $dob = get_post_meta($order_id, 'billing_dob', true) ?: '1990-01-01';

        // Prepare return URLs
        $returnUrl = $this->get_return_url($order);
        // We can use a custom endpoint for failure or just the cart/checkout
        $failUrl = $order->get_cancel_order_url();

        // Webhook URL
        $callbackUrl = add_query_arg(['wc-api' => 'payment_book'], home_url('/'));

        // Build Payload
        $payload = [
            'meta' => [
                'service_id' => (int)$this->service_id,
                'reference_id' => (string)$order_id,
                // signature added by Signer::sign
            ],
            'payment' => [
                'amount' => $amount,
                'currency' => $currency,
            ],
            'user' => [
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'day_of_birth' => $dob,
                'address' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'zip' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country(),
                // language could be added if available in WP context
            ],
            'redirect' => [
                'success' => $returnUrl,
                'fail' => $failUrl,
                'return' => $returnUrl,
            ],
            'webhook' => [
                'url' => $callbackUrl,
                'token_name' => $this->token_name
            ]
        ];

        // Generate signature (Static method)
        try {
            $signedPayload = Signer::sign($payload, $this->token);
        } catch (\Exception $e) {
            wc_add_notice('Signing error: ' . $e->getMessage(), 'error');
            return;
        }

        // Send request
        $response = wp_remote_post($this->api_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-REQUEST-API-KEY' => $this->token_name,
            ],
            'body' => json_encode($signedPayload),
            'timeout' => 45,
        ]);

        if (is_wp_error($response)) {
            wc_add_notice('Payment error: ' . $response->get_error_message(), 'error');
            return;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);

        if ($code !== 200 || empty($body['data']['redirect_url'])) {
            $errorMsg = $body['meta']['message'] ?? 'Unknown gateway error.';
            if (!empty($body['errors'])) {
                $errorMsg .= ' ' . json_encode($body['errors']);
            }
            wc_add_notice('Payment gateway error: ' . $errorMsg, 'error');
            return;
        }

        return [
            'result' => 'success',
            'redirect' => $body['data']['redirect_url'],
        ];
    }

    public function check_callback()
    {
        $payload = json_decode(file_get_contents('php://input'), true);

        if (!$payload || !isset($payload['meta']['signature'])) {
            exit('Invalid payload');
        }

        // Verify signature (Static method)
        if (!Signer::validate($payload, $this->token)) {
            header('HTTP/1.1 401 Unauthorized');
            exit('Signature mismatch');
        }

        // Process status
        $status = $payload['data']['operation']['status'] ?? 'unknown';
        $orderId = $payload['meta']['reference_id'] ?? 0;

        $order = wc_get_order($orderId);
        if (!$order) {
            exit('Order not found');
        }

        if ($status === 'success') {
            $order->payment_complete($payload['data']['operation']['ulid'] ?? '');
            $order->add_order_note('Payment confirmed via Webhook. Operation ID: ' . ($payload['data']['operation']['id'] ?? 'N/A'));
        } elseif ($status === 'failed' || $status === 'error') {
            $order->update_status('failed', 'Payment failed via Webhook.');
        }

        echo 'OK';
        exit;
    }
}

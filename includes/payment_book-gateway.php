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
        $this->id = 'wltpay';
        $this->has_fields = false;
        $this->method_title = 'WLT Pay';
        $this->method_description = 'Pay via WLT Pay gateway.';

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->token_name = $this->get_option('token_name');
        $this->token = $this->get_option('token');
        $this->service_id = $this->get_option('service_id');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Enable WLT Pay',
                'default' => 'yes',
            ],
            'title' => [
                'title' => 'Title',
                'type' => 'text',
                'description' => 'Payment method title visible to customers',
                'default' => 'WLT Pay',
            ],
            'description' => [
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'Payment method description',
                'default' => 'Pay securely using WLT Pay',
            ],
            'token_name' => [
                'title' => 'API Key Name',
                'type' => 'text',
            ],
            'token' => [
                'title' => 'API Secret Key',
                'type' => 'text',
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
        $currency = get_woocommerce_currency();

        // build payload
        $payload = [
            'service_id' => $this->service_id,
            'amount' => $amount,
            'currency' => $currency,
            'order_id' => $order_id,
        ];

        // generate signature
        $signer = new Signer($this->token_name, $this->token);
        $signature = $signer->sign($payload);

        // send request
        $response = wp_remote_post('https://pb.wltpay.pro/api/payment', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-REQUEST-API-KEY' => $this->token_name,
                'Signature' => $signature,
            ],
            'body' => json_encode($payload),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            wc_add_notice('Payment error: ' . $response->get_error_message(), 'error');
            return;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['payment_url'])) {
            wc_add_notice('Payment gateway error.', 'error');
            return;
        }

        // redirect customer to payment page
        return [
            'result' => 'success',
            'redirect' => $body['payment_url'],
        ];
    }
}

<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Mock Constants
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}

// Mock Classes
class WC_Payment_Gateway {
    public $id;
    public $has_fields;
    public $method_title;
    public $method_description;
    public $title;
    public $description;
    public $form_fields;
    public $token_name;
    public $token;
    public $service_id;
    public $api_url;

    public function init_form_fields() {}
    public function init_settings() {}
    public function get_option($key, $default = null) {
        return $default;
    }
    public function get_return_url($order) {
        return 'http://example.com/return';
    }
    public function process_admin_options() {}
}

class WC_Order {
    public function get_total() { return 100.00; }
    public function get_currency() { return 'USD'; }
    public function get_billing_email() { return 'test@example.com'; }
    public function get_billing_phone() { return '1234567890'; }
    public function get_billing_first_name() { return 'John'; }
    public function get_billing_last_name() { return 'Doe'; }
    public function get_billing_address_1() { return '123 Main St'; }
    public function get_billing_address_2() { return ''; }
    public function get_billing_city() { return 'New York'; }
    public function get_billing_postcode() { return '10001'; }
    public function get_billing_country() { return 'US'; }
    public function get_cancel_order_url() { return 'http://example.com/cancel'; }
    public function payment_complete($id = '') {}
    public function add_order_note($note) {}
    public function update_status($status, $note = '') {}
}

class WP_Error {
    public function get_error_message() { return 'Mock Error'; }
}

// Mock Functions
function add_action($hook, $callback) {}
function add_filter($hook, $callback) {}
function wc_get_order($id) {
    if ($id == 999) return false;
    return new WC_Order();
}
function get_post_meta($id, $key, $single = false) {
    if ($key === 'billing_dob') return '1990-01-01';
    return '';
}
function add_query_arg($args, $url) {
    return $url . '?' . http_build_query($args);
}
function home_url($path = '/') {
    return 'http://example.com' . $path;
}
function wc_add_notice($message, $type = 'success') {}
function wp_remote_post($url, $args = []) {
    if ($url === 'https://error.com') return new WP_Error();
    if ($url === 'https://fail.com') return ['response' => ['code' => 500], 'body' => json_encode(['meta' => ['message' => 'Failed']])];
    return [
        'response' => ['code' => 200],
        'body' => json_encode([
            'meta' => ['message' => 'Success'],
            'data' => ['redirect_url' => 'https://payment-book.com/pay/123']
        ])
    ];
}
function is_wp_error($thing) {
    return $thing instanceof WP_Error;
}
function wp_remote_retrieve_body($response) {
    return $response['body'] ?? '';
}
function wp_remote_retrieve_response_code($response) {
    return $response['response']['code'] ?? 500;
}
function _x($text, $context, $domain = 'default') { return $text; }
function __($text, $domain = 'default') { return $text; }
function sanitize_text_field($str) { return trim($str); }
function update_post_meta($id, $key, $value) {}

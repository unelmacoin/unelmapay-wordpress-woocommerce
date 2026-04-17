<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_UnelmaPay extends WC_Payment_Gateway {

    public function __construct() {
        error_log('=== UnelmaPay Constructor Called ===');
        
        $this->id                 = 'unelmapay';
        $this->icon               = '';
        $this->has_fields         = false;
        $this->method_title       = __('UnelmaPay', 'unelmapay-woocommerce');
        $this->method_description = __('Accept payments via UnelmaPay payment gateway. Supports sandbox and production environments.', 'unelmapay-woocommerce');
        $this->supports           = array('products');

        $this->init_form_fields();
        $this->init_settings();

        $this->title              = $this->get_option('title');
        $this->description        = $this->get_option('description');
        $this->merchant_id        = $this->get_option('merchant_id');
        $this->merchant_password  = $this->get_option('merchant_password');
        $this->sandbox_mode       = 'yes' === $this->get_option('sandbox_mode');
        $this->debug_mode         = 'yes' === $this->get_option('debug_mode');

        if ($this->sandbox_mode) {
            $this->payment_url = 'https://dev.unelmapay.com/merchant_api/payrequest';
        } else {
            $this->payment_url = 'https://unelmapay.com.np/merchant_api/payrequest';
        }

        $this->success_url       = $this->get_option('success_url');
        $this->fail_url          = $this->get_option('fail_url');
        $this->cancel_url        = $this->get_option('cancel_url');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_wc_gateway_unelmapay', array($this, 'handle_ipn'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    public function is_available() {
        error_log('=== UnelmaPay is_available() Called ===');

        if (empty($this->merchant_id) || empty($this->merchant_password)) {
            error_log('UnelmaPay is available: Merchant ID or Password is missing, but defaulting to available.');
        }

        if ($this->get_option('enabled') !== 'yes') {
            error_log('UnelmaPay is available: Gateway is disabled in settings, but defaulting to available.');
        }

        return true; 
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Enable/Disable', 'unelmapay-woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable UnelmaPay Payment Gateway', 'unelmapay-woocommerce'),
                'default' => 'yes'
            ),
            'title' => array(
                'title'       => __('Title', 'unelmapay-woocommerce'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'unelmapay-woocommerce'),
                'default'     => __('UnelmaPay', 'unelmapay-woocommerce'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'unelmapay-woocommerce'),
                'type'        => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'unelmapay-woocommerce'),
                'default'     => __('Pay securely via UnelmaPay.', 'unelmapay-woocommerce'),
            ),
            'merchant_id' => array(
                'title'       => __('Merchant ID', 'unelmapay-woocommerce'),
                'type'        => 'text',
                'description' => __('Enter your UnelmaPay Merchant ID.', 'unelmapay-woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'merchant_password' => array(
                'title'       => __('Merchant Password', 'unelmapay-woocommerce'),
                'type'        => 'password',
                'description' => __('Enter your UnelmaPay Merchant Password (used for IPN verification).', 'unelmapay-woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'merchant_name' => array(
                'title'       => __('Merchant Name', 'unelmapay-woocommerce'),
                'type'        => 'text',
                'description' => __('Your business/merchant name (optional).', 'unelmapay-woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'merchant_email' => array(
                'title'       => __('Merchant Email', 'unelmapay-woocommerce'),
                'type'        => 'email',
                'description' => __('Your merchant contact email (optional).', 'unelmapay-woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'success_url' => array(
                'title'       => __('Success URL', 'unelmapay-woocommerce'),
                'type'        => 'text',
                'description' => __('Custom URL to redirect after successful payment (leave empty for default order received page).', 'unelmapay-woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => home_url('/payment-success/'),
            ),
            'fail_url' => array(
                'title'       => __('Fail URL', 'unelmapay-woocommerce'),
                'type'        => 'text',
                'description' => __('Custom URL to redirect after failed payment (leave empty for default checkout page).', 'unelmapay-woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => home_url('/payment-failed/'),
            ),
            'cancel_url' => array(
                'title'       => __('Cancel URL', 'unelmapay-woocommerce'),
                'type'        => 'text',
                'description' => __('Custom URL to redirect if payment is cancelled (leave empty for default cart page).', 'unelmapay-woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => home_url('/payment-cancelled/'),
            ),
            'sandbox_mode' => array(
                'title'       => __('Sandbox Mode', 'unelmapay-woocommerce'),
                'type'        => 'checkbox',
                'label'       => __('Enable Sandbox Mode (dev.unelmapay.com)', 'unelmapay-woocommerce'),
                'default'     => 'yes',
                'description' => __('Use sandbox environment for testing. Uncheck for production.', 'unelmapay-woocommerce'),
            ),
            'debug_mode' => array(
                'title'       => __('Debug Mode', 'unelmapay-woocommerce'),
                'type'        => 'checkbox',
                'label'       => __('Enable Debug Logging', 'unelmapay-woocommerce'),
                'default'     => 'yes',
                'description' => sprintf(__('Log UnelmaPay events inside %s', 'unelmapay-woocommerce'), '<code>' . WC_Log_Handler_File::get_log_file_path('unelmapay') . '</code>'),
            ),
        );
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        $this->log('Processing payment for order #' . $order_id);

        $response = wp_remote_post($this->payment_url, array(
            'body' => array(
                'merchant_id' => $this->merchant_id,
                'amount'      => $order->get_total(),
                'currency'    => 'NPR', 
                'item_name'   => implode(', ', array_map(function($item) {
                    return $item->get_name();
                }, $order->get_items())),
                'order_id'    => $order->get_id(),
                'success_url' => !empty($this->success_url) ? $this->success_url : $this->get_return_url($order),
                'fail_url'    => !empty($this->fail_url) ? $this->fail_url : $order->get_checkout_payment_url(),
                'cancel_url'  => !empty($this->cancel_url) ? $this->cancel_url : $order->get_cancel_order_url_raw(),
                'custom'      => 'customer_id_' . $order->get_customer_id(),
            ),
        ));

        if (is_wp_error($response)) {
            $this->log('Payment request failed: ' . $response->get_error_message());
            wc_add_notice(__('Payment error: Could not connect to UnelmaPay.', 'unelmapay-woocommerce'), 'error');
            return;
        }

        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($response_body['payment_id'])) {
            $payment_id = $response_body['payment_id'];
            $redirect_url = 'https://dev.unelmapay.com/pay/' . $payment_id;

            $this->log('Redirecting to UnelmaPay payment page: ' . $redirect_url);

            return array(
                'result'   => 'success',
                'redirect' => $redirect_url,
            );
        } else {
            $this->log('Payment request failed: ' . print_r($response_body, true));
            wc_add_notice(__('Payment error: Invalid response from UnelmaPay.', 'unelmapay-woocommerce'), 'error');
            return;
        }
    }

    public function receipt_page($order_id) {
        $this->log('Receipt page called for order #' . $order_id);

        $order = wc_get_order($order_id);
        
        echo '<p>' . __('Thank you for your order. Please click the button below to pay with UnelmaPay.', 'unelmapay-woocommerce') . '</p>';
        echo $this->generate_payment_form($order);
    }

    protected function generate_payment_form($order) {
        $order_id = $order->get_id();
        $amount = $order->get_total();
        $currency = 'NPR';
        
        $item_names = array();
        foreach ($order->get_items() as $item) {
            $item_names[] = $item->get_name();
        }
        $item_name = implode(', ', $item_names);
        if (strlen($item_name) > 100) {
            $item_name = substr($item_name, 0, 97) . '...';
        }

        $return_url = !empty($this->success_url) ? $this->success_url : $this->get_return_url($order);
        $fail_url = !empty($this->fail_url) ? $this->fail_url : $order->get_checkout_payment_url();
        $cancel_url = !empty($this->cancel_url) ? $this->cancel_url : $order->get_cancel_order_url_raw();
        $notify_url = WC()->api_request_url('WC_Gateway_UnelmaPay');

        $this->log('Payment form data for order #' . $order_id . ': merchant=' . $this->merchant_id . ', amount=' . $amount . ', notify_url=' . $notify_url);

        $form_html = '<form method="POST" action="' . esc_url($this->payment_url) . '" id="unelmapay_payment_form">';
        $form_html .= '<input type="hidden" name="merchant" value="' . esc_attr($this->merchant_id) . '">';
        $form_html .= '<input type="hidden" name="item_name" value="' . esc_attr($item_name) . '">';
        $form_html .= '<input type="hidden" name="amount" value="' . esc_attr($amount) . '">';
        $form_html .= '<input type="hidden" name="currency" value="' . esc_attr($currency) . '">';
        $form_html .= '<input type="hidden" name="custom" value="' . esc_attr($order_id) . '">';
        $form_html .= '<input type="hidden" name="return_url" value="' . esc_attr($return_url) . '">';
        $form_html .= '<input type="hidden" name="fail_url" value="' . esc_attr($fail_url) . '">';
        $form_html .= '<input type="hidden" name="cancel_url" value="' . esc_attr($cancel_url) . '">';
        $form_html .= '<input type="hidden" name="notify_url" value="' . esc_attr($notify_url) . '">';
        
        $logo_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="32" height="32" style="vertical-align: middle; margin-right: 8px;"><circle cx="100" cy="100" r="100" fill="white"/><text x="100" y="85" font-family="Arial, sans-serif" font-size="70" font-weight="bold" fill="#7B4397" text-anchor="middle">U</text><text x="100" y="145" font-family="Arial, sans-serif" font-size="35" font-weight="bold" fill="#7B4397" text-anchor="middle">PAY</text></svg>';
        
        $form_html .= '<button type="submit" class="button alt" id="submit_unelmapay_payment_form">' . $logo_svg . __('Pay with UnelmaPay', 'unelmapay-woocommerce') . '</button>';
        $form_html .= '<a class="button cancel" href="' . esc_url($cancel_url) . '">' . __('Cancel order &amp; restore cart', 'unelmapay-woocommerce') . '</a>';
        $form_html .= '</form>';

        return $form_html;
    }

    public function handle_ipn() {
        $this->log('IPN Request received: ' . print_r($_POST, true));

        if (empty($_POST)) {
            $this->log('IPN Error: Empty POST data');
            wp_die('UnelmaPay IPN Request Failure', 'UnelmaPay IPN', array('response' => 400));
        }

        $total = isset($_POST['total']) ? sanitize_text_field($_POST['total']) : '';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $id_transfer = isset($_POST['id_transfer']) ? sanitize_text_field($_POST['id_transfer']) : '';
        $received_hash = isset($_POST['hash']) ? sanitize_text_field($_POST['hash']) : '';
        $custom = isset($_POST['custom']) ? sanitize_text_field($_POST['custom']) : '';
        $item_name = isset($_POST['item_name']) ? sanitize_text_field($_POST['item_name']) : '';
        $currency = isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        $hash_string = $total . ':' . $this->test123456 . ':' . $date . ':' . $id_transfer;
        $calculated_hash = strtoupper(md5($hash_string));

        $this->log('IPN Hash verification: received=' . $received_hash . ', calculated=' . $calculated_hash);

        if ($received_hash !== $calculated_hash) {
            $this->log('IPN Error: Hash mismatch');
            wp_die('Invalid IPN Hash', 'UnelmaPay IPN', array('response' => 400));
        }

        $order_id = intval($custom);
        $order = wc_get_order($order_id);

        if (!$order) {
            $this->log('IPN Error: Order #' . $order_id . ' not found');
            wp_die('Order not found', 'UnelmaPay IPN', array('response' => 404));
        }

        if ($order->has_status('completed') || $order->has_status('processing')) {
            $this->log('IPN: Order #' . $order_id . ' already processed');
            wp_die('IPN Received', 'UnelmaPay IPN', array('response' => 200));
        }

        $order->add_order_note(sprintf(
            __('UnelmaPay payment completed. Transaction ID: %s, Amount: %s', 'unelmapay-woocommerce'),
            $id_transfer,
            $total
        ));

        $order->payment_complete($id_transfer);

        $this->log('IPN Success: Order #' . $order_id . ' marked as paid. Transaction ID: ' . $id_transfer);

        status_header(200);
        echo 'IPN Received and Verified';
        exit;
    }

    protected function log($message) {
        if ($this->debug_mode) {
            if (!function_exists('wc_get_logger')) {
                return;
            }
            $logger = wc_get_logger();
            $logger->info($message, array('source' => 'unelmapay'));
        }
    }

    public function handle_redirect_endpoint() {
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $key      = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';

        if (!$order_id || !$key) {
            wp_die('Invalid request.');
        }

        $order = wc_get_order($order_id);

        if (!$order || $order->get_order_key() !== $key) {
        wp_die('Invalid order.');
        }

        $this->log('API redirect handler called for order #' . $order_id);

        wc_nocache_headers();
        echo $this->generate_payment_form($order);
        exit;
    }

    public function register_rest_routes() {
        error_log('=== Registering REST API Routes ===');
        register_rest_route('unelmamail/v1', '/webhook/(?P<hook>[a-zA-Z0-9_-]+)', array(
            'methods'  => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'args'     => array(
                'hook' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_string($param);
                    }
                ),
            ),
            'permission_callback' => '__return_true',
        ));
    }

    public function handle_webhook(WP_REST_Request $request) {
        $hook = $request->get_param('hook');
        $body = $request->get_body();

        $this->log('Webhook received: Hook=' . $hook . ', Body=' . $body);

        $response = array(
            'status' => 'success',
            'message' => 'Webhook received successfully',
            'hook' => $hook,
        );

        return new WP_REST_Response($response, 200);
    }
}

add_filter('woocommerce_payment_gateways', 'add_gateway_class');
function add_gateway_class($methods) {
    $methods[] = 'WC_Gateway_UnelmaPay';
    return $methods;
}

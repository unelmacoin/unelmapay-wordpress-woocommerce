<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_UnelmaPay extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'unelmapay';
        $this->icon               = '';
        $this->has_fields         = false;
        $this->method_title       = __('UnelmaPay', 'unelmapay-payment-gateway');
        $this->method_description = __('Accept payments via UnelmaPay payment gateway. Supports sandbox and production environments.', 'unelmapay-payment-gateway');
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
            $this->payment_url = 'https://dev.unelmapay.com/sci/form';
        } else {
            $this->payment_url = 'https://unelmapay.com.np/sci/form';
        }

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_api_wc_gateway_unelmapay', array($this, 'handle_ipn'));

        add_action('wp_enqueue_scripts', array($this, 'enqueue_checkout_script'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Enable/Disable', 'unelmapay-payment-gateway'),
                'type'    => 'checkbox',
                'label'   => __('Enable UnelmaPay Payment Gateway', 'unelmapay-payment-gateway'),
                'default' => 'no'
            ),
            'title' => array(
                'title'       => __('Title', 'unelmapay-payment-gateway'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'unelmapay-payment-gateway'),
                'default'     => __('UnelmaPay', 'unelmapay-payment-gateway'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'unelmapay-payment-gateway'),
                'type'        => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'unelmapay-payment-gateway'),
                'default'     => __('Pay securely via UnelmaPay.', 'unelmapay-payment-gateway'),
            ),
            'merchant_id' => array(
                'title'       => __('Merchant ID', 'unelmapay-payment-gateway'),
                'type'        => 'text',
                'description' => __('Enter your UnelmaPay Merchant ID.', 'unelmapay-payment-gateway'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'merchant_password' => array(
                'title'       => __('Merchant Password', 'unelmapay-payment-gateway'),
                'type'        => 'password',
                'description' => __('Enter your UnelmaPay Merchant Password (used for IPN verification).', 'unelmapay-payment-gateway'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'merchant_name' => array(
                'title'       => __('Merchant Name', 'unelmapay-payment-gateway'),
                'type'        => 'text',
                'description' => __('Your business/merchant name (optional).', 'unelmapay-payment-gateway'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'merchant_email' => array(
                'title'       => __('Merchant Email', 'unelmapay-payment-gateway'),
                'type'        => 'email',
                'description' => __('Your merchant contact email (optional).', 'unelmapay-payment-gateway'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'success_url' => array(
                'title'       => __('Success URL', 'unelmapay-payment-gateway'),
                'type'        => 'text',
                'description' => __('Custom URL to redirect after successful payment (leave empty for default order received page).', 'unelmapay-payment-gateway'),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => home_url('/payment-success/'),
            ),
            'fail_url' => array(
                'title'       => __('Fail URL', 'unelmapay-payment-gateway'),
                'type'        => 'text',
                'description' => __('Custom URL to redirect after failed payment (leave empty for default checkout page).', 'unelmapay-payment-gateway'),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => home_url('/payment-failed/'),
            ),
            'cancel_url' => array(
                'title'       => __('Cancel URL', 'unelmapay-payment-gateway'),
                'type'        => 'text',
                'description' => __('Custom URL to redirect if payment is cancelled (leave empty for default cart page).', 'unelmapay-payment-gateway'),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => home_url('/payment-cancelled/'),
            ),
            'sandbox_mode' => array(
                'title'       => __('Sandbox Mode', 'unelmapay-payment-gateway'),
                'type'        => 'checkbox',
                'label'       => __('Enable Sandbox Mode (dev.unelmapay.com)', 'unelmapay-payment-gateway'),
                'default'     => 'yes',
                'description' => __('Use sandbox environment for testing. Uncheck for production.', 'unelmapay-payment-gateway'),
            ),
            'debug_mode' => array(
                'title'       => __('Debug Mode', 'unelmapay-payment-gateway'),
                'type'        => 'checkbox',
                'label'       => __('Enable Debug Logging', 'unelmapay-payment-gateway'),
                'default'     => 'yes',
                'description' => sprintf(__('Log UnelmaPay events inside %s', 'unelmapay-payment-gateway'), '<code>' . WC_Log_Handler_File::get_log_file_path('unelmapay') . '</code>'),
            ),
        );
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        $this->log('Processing payment for order #' . $order_id);

        return array(
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    public function receipt_page($order_id) {
        $order = wc_get_order($order_id);

        $redirect_url = $this->get_return_url($order); 
        $checkout_message = __('Redirecting to UnelmaPay...', 'unelmapay-payment-gateway');

        echo '<p>' . esc_html__('Thank you for your order. Please click the button below to pay with UnelmaPay.', 'unelmapay-payment-gateway') . '</p>';

        $payment_form_html = $this->generate_payment_form($order);
        echo wp_kses($payment_form_html, $this->get_receipt_allowed_html());

        ?>
        <script>
        var upay_vars = {
            redirect_url: <?php echo json_encode($redirect_url); ?>,
            checkout_message: <?php echo json_encode($checkout_message); ?>
        };
        </script>
        <?php
        wp_enqueue_script(
            'unelmapay-checkout',
            plugins_url('../assets/js/unelmapay-checkout.js', __FILE__),
            array('jquery'),
            '1.0.0',
            true
        );
    }

    protected function generate_payment_form($order) {
        $order_id = $order->get_id();
        $amount = $order->get_total();
        $currency = 'debit_base';

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
        
        $form_html .= '<button type="submit" class="button alt" id="submit_unelmapay_payment_form">' . $logo_svg . __('Pay with UnelmaPay', 'unelmapay-payment-gateway') . '</button>';
        $form_html .= '<a class="button cancel" href="' . esc_url($cancel_url) . '">' . __('Cancel order &amp; restore cart', 'unelmapay-payment-gateway') . '</a>';
        $form_html .= '</form>';

        return $form_html;
    }

    public function handle_ipn() {
        $request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : 'UNKNOWN';

        // Logging to file removed for production compliance.
        $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
        // [' . gmdate('Y-m-d H:i:s') . '] IPN endpoint hit. Method=' . $request_method . ' IP=' . $remote_addr


        if ($request_method === 'GET') {
            $this->log('IPN health-check ping received (GET)');
            status_header(200);
            header('Content-Type: application/json');
            wp_send_json(array(
                'status'    => 'ok',
                'gateway'   => 'UnelmaPay',
                'endpoint'  => 'IPN',
                'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
                'method'    => 'GET (health-check)',
                'note'      => 'IPN callbacks must be POST with fields: total, date, id_transfer, hash, custom',
            ));
            exit;
        }

        // Note: Nonce verification is not possible for IPN callbacks from external payment systems.

        $this->log('=== IPN HANDLER ENTERED ===');

        $content_type = isset($_SERVER['CONTENT_TYPE']) ? sanitize_text_field( wp_unslash( $_SERVER['CONTENT_TYPE'] ) ) : (isset($_SERVER['HTTP_CONTENT_TYPE']) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CONTENT_TYPE'] ) ) : 'not set');
        $raw_body = file_get_contents('php://input');

        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[substr($key, 5)] = sanitize_text_field( wp_unslash( $value ) );
            }
        }

        $this->log('IPN Request method: ' . $request_method);
        $this->log('IPN Content-Type: ' . $content_type);
        // Debug code removed: print_r($headers), print_r($_POST), print_r($_GET)
        $this->log('IPN Raw body (' . strlen($raw_body) . ' bytes)');


        $post_data = $_POST;

        if (empty($post_data) && !empty($raw_body)) {
            $content_type_lower = strtolower($content_type);

            if (strpos($content_type_lower, 'application/json') !== false) {
                $decoded = json_decode($raw_body, true);
                if (is_array($decoded)) {
                    $post_data = $decoded;
                    $this->log('IPN: Parsed JSON body into post_data');
                }
            } else {
                parse_str($raw_body, $parsed);
                if (!empty($parsed)) {
                    $post_data = $parsed;
                    $this->log('IPN: Parsed raw body as form-urlencoded into post_data');
                }
            }
        }

        if (empty($post_data)) {
            $this->log('IPN Error: Empty POST data after all parse attempts. Method=' . $request_method . ', Content-Type=' . $content_type . ', Raw body length=' . strlen($raw_body));
            wp_die('UnelmaPay IPN Request Failure', 'UnelmaPay IPN', array('response' => 400));
        }

        $total = isset($post_data['total']) ? sanitize_text_field( wp_unslash( $post_data['total'] ) ) : '';
        $date = isset($post_data['date']) ? sanitize_text_field( wp_unslash( $post_data['date'] ) ) : '';
        $id_transfer = isset($post_data['id_transfer']) ? sanitize_text_field( wp_unslash( $post_data['id_transfer'] ) ) : '';
        $received_hash = isset($post_data['hash']) ? sanitize_text_field( wp_unslash( $post_data['hash'] ) ) : '';
        $custom = isset($post_data['custom']) ? sanitize_text_field( wp_unslash( $post_data['custom'] ) ) : '';
        $item_name = isset($post_data['item_name']) ? sanitize_text_field( wp_unslash( $post_data['item_name'] ) ) : '';
        $currency = isset($post_data['currency']) ? sanitize_text_field( wp_unslash( $post_data['currency'] ) ) : '';
        $status = isset($post_data['status']) ? sanitize_text_field( wp_unslash( $post_data['status'] ) ) : '';

        $hash_string = $total . ':' . $this->merchant_password . ':' . $date . ':' . $id_transfer;
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
            // translators: %1$s is the transaction ID, %2$s is the amount.
            __('UnelmaPay payment completed. Transaction ID: %1$s, Amount: %2$s', 'unelmapay-payment-gateway'),
            $id_transfer,
            $total
        ));

        $order->payment_complete($id_transfer);

        $this->log('IPN Success: Order #' . $order_id . ' marked as paid. Transaction ID: ' . $id_transfer);

        status_header(200);
        echo esc_html__('IPN Received and Verified', 'unelmapay-payment-gateway');
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

    public function enqueue_checkout_script() {
        if (is_checkout()) {
            wp_enqueue_script(
                'unelmapay-checkout',
                plugins_url('../assets/js/unelmapay-checkout.js', __FILE__),
                array('jquery'),
                '1.0.0',
                true
            );
            wp_localize_script('unelmapay-checkout', 'upay_vars', array(
                'redirect_url' => $redirect_url,
                'checkout_message' => $checkout_message,
            ));
        }
    }

    protected function get_receipt_allowed_html() {
        return array(
            'form' => array(
                'method' => true,
                'action' => true,
                'id' => true,
            ),
            'input' => array(
                'type' => true,
                'name' => true,
                'value' => true,
            ),
            'button' => array(
                'type' => true,
                'class' => true,
                'id' => true,
            ),
            'a' => array(
                'class' => true,
                'href' => true,
            ),
            'svg' => array(
                'xmlns' => true,
                'viewbox' => true,
                'width' => true,
                'height' => true,
                'style' => true,
            ),
            'circle' => array(
                'cx' => true,
                'cy' => true,
                'r' => true,
                'fill' => true,
            ),
            'text' => array(
                'x' => true,
                'y' => true,
                'font-family' => true,
                'font-size' => true,
                'font-weight' => true,
                'fill' => true,
                'text-anchor' => true,
            ),
        );
    }
}

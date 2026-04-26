<?php
if (!defined('ABSPATH')) {
    exit;
}

class UNELMAPAY_Core {
    
    private static $instance = null;
    private $merchant_id;
    private $merchant_password;
    private $sandbox_mode;
    private $debug_mode;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_settings();
        $this->init_hooks();
    }
    
    private function load_settings() {
        $options = get_option('unelmapay_settings', array());
        $this->merchant_id = isset($options['merchant_id']) ? $options['merchant_id'] : '';
        $this->merchant_password = isset($options['merchant_password']) ? $options['merchant_password'] : '';
        $this->sandbox_mode = isset($options['sandbox_mode']) ? $options['sandbox_mode'] : 'yes';
        $this->debug_mode = isset($options['debug_mode']) ? $options['debug_mode'] : 'yes';
        $this->merchant_name = isset($options['merchant_name']) ? $options['merchant_name'] : '';
        $this->merchant_email = isset($options['merchant_email']) ? $options['merchant_email'] : '';
        $this->success_url = isset($options['success_url']) ? $options['success_url'] : '';
        $this->fail_url = isset($options['fail_url']) ? $options['fail_url'] : '';
        $this->cancel_url = isset($options['cancel_url']) ? $options['cancel_url'] : '';
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'register_post_type'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('unelmapay_button', array($this, 'payment_button_shortcode'));
        add_action('template_redirect', array($this, 'handle_ipn'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('manage_unelmapay_payment_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_unelmapay_payment_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_action('add_meta_boxes', array($this, 'add_payment_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    public function register_post_type() {
        register_post_type('unelmapay_payment', array(
            'labels' => array(
                'name' => __('Payments', 'unelmapay-payment-gateway'),
                'singular_name' => __('Payment', 'unelmapay-payment-gateway'),
                'add_new' => __('Add New', 'unelmapay-payment-gateway'),
                'add_new_item' => __('Add New Payment', 'unelmapay-payment-gateway'),
                'edit_item' => __('Edit Payment', 'unelmapay-payment-gateway'),
                'view_item' => __('View Payment', 'unelmapay-payment-gateway'),
                'all_items' => __('All Payments', 'unelmapay-payment-gateway'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'unelmapay-settings',
            'capability_type' => 'post',
            'capabilities' => array('create_posts' => false),
            'map_meta_cap' => true,
            'supports' => array('title'),
            'menu_icon' => 'dashicons-money-alt',
        ));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('UnelmaPay', 'unelmapay-payment-gateway'),
            __('UnelmaPay', 'unelmapay-payment-gateway'),
            'manage_options',
            'unelmapay-settings',
            array($this, 'settings_page'),
            'dashicons-money-alt',
            56
        );
        
        add_submenu_page(
            'unelmapay-settings',
            __('Settings', 'unelmapay-payment-gateway'),
            __('Settings', 'unelmapay-payment-gateway'),
            'manage_options',
            'unelmapay-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'unelmapay-settings',
            __('Debug Logs', 'unelmapay-payment-gateway'),
            __('Debug Logs', 'unelmapay-payment-gateway'),
            'manage_options',
            'unelmapay-logs',
            array($this, 'logs_page')
        );
    }
    
    public function register_settings() {
        register_setting('unelmapay_settings_group', 'unelmapay_settings');
        
        add_settings_section(
            'unelmapay_main_section',
            __('UnelmaPay Settings', 'unelmapay-payment-gateway'),
            array($this, 'settings_section_callback'),
            'unelmapay-settings'
        );
        
        add_settings_field('merchant_id', __('Merchant ID', 'unelmapay-payment-gateway'), array($this, 'merchant_id_field'), 'unelmapay-settings', 'unelmapay_main_section');
        add_settings_field('merchant_password', __('Merchant Password', 'unelmapay-payment-gateway'), array($this, 'merchant_password_field'), 'unelmapay-settings', 'unelmapay_main_section');
        add_settings_field('merchant_name', __('Merchant Name', 'unelmapay-payment-gateway'), array($this, 'merchant_name_field'), 'unelmapay-settings', 'unelmapay_main_section');
        add_settings_field('merchant_email', __('Merchant Email', 'unelmapay-payment-gateway'), array($this, 'merchant_email_field'), 'unelmapay-settings', 'unelmapay_main_section');
        add_settings_field('success_url', __('Success URL', 'unelmapay-payment-gateway'), array($this, 'success_url_field'), 'unelmapay-settings', 'unelmapay_main_section');
        add_settings_field('fail_url', __('Fail URL', 'unelmapay-payment-gateway'), array($this, 'fail_url_field'), 'unelmapay-settings', 'unelmapay_main_section');
        add_settings_field('cancel_url', __('Cancel URL', 'unelmapay-payment-gateway'), array($this, 'cancel_url_field'), 'unelmapay-settings', 'unelmapay_main_section');
        add_settings_field('sandbox_mode', __('Sandbox Mode', 'unelmapay-payment-gateway'), array($this, 'sandbox_mode_field'), 'unelmapay-settings', 'unelmapay_main_section');
        add_settings_field('debug_mode', __('Debug Mode', 'unelmapay-payment-gateway'), array($this, 'debug_mode_field'), 'unelmapay-settings', 'unelmapay_main_section');
    }
    
    public function settings_section_callback() {
        echo '<p>' . esc_html__('Configure your UnelmaPay payment gateway settings.', 'unelmapay-payment-gateway') . '</p>';
        if (class_exists('WooCommerce')) {
            echo '<div class="notice notice-info"><p>' . esc_html__('WooCommerce detected! You can also configure UnelmaPay in WooCommerce → Settings → Payments.', 'unelmapay-payment-gateway') . '</p></div>';
        }
        echo '<div class="notice notice-info inline" style="margin-top: 10px;"><p><strong>' . esc_html__('Currency:', 'unelmapay-payment-gateway') . '</strong> ' . esc_html__('All payments are processed in NPR (Nepalese Rupee), which is the base currency in the UnelmaPay system.', 'unelmapay-payment-gateway') . '</p></div>';
    }
    
    public function merchant_id_field() {
        $options = get_option('unelmapay_settings');
        $value = isset($options['merchant_id']) ? $options['merchant_id'] : '';
        echo '<input type="text" name="unelmapay_settings[merchant_id]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Enter your UnelmaPay Merchant ID', 'unelmapay-payment-gateway') . '</p>';
    }
    
    public function merchant_password_field() {
        $options = get_option('unelmapay_settings');
        $value = isset($options['merchant_password']) ? $options['merchant_password'] : '';
        echo '<input type="password" name="unelmapay_settings[merchant_password]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Enter your UnelmaPay Merchant Password (used for IPN verification)', 'unelmapay-payment-gateway') . '</p>';
    }
    
    public function sandbox_mode_field() {
        $options = get_option('unelmapay_settings');
        $checked = isset($options['sandbox_mode']) && $options['sandbox_mode'] === 'yes' ? 'checked' : '';
        echo '<input type="hidden" name="unelmapay_settings[sandbox_mode]" value="no" />';
        echo '<label><input type="checkbox" name="unelmapay_settings[sandbox_mode]" value="yes" ' . $checked . ' /> ';
        echo esc_html__('Enable Sandbox Mode (dev.unelmapay.com)', 'unelmapay-payment-gateway') . '</label>';
        echo '<p class="description">' . esc_html__('Use sandbox environment for testing. Uncheck for production (unelmapay.com.np).', 'unelmapay-payment-gateway') . '</p>';
    }
    
    public function merchant_name_field() {
        $options = get_option('unelmapay_settings');
        $value = isset($options['merchant_name']) ? $options['merchant_name'] : '';
        echo '<input type="text" name="unelmapay_settings[merchant_name]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Your business/merchant name (optional)', 'unelmapay-payment-gateway') . '</p>';
    }
    
    public function merchant_email_field() {
        $options = get_option('unelmapay_settings');
        $value = isset($options['merchant_email']) ? $options['merchant_email'] : '';
        echo '<input type="email" name="unelmapay_settings[merchant_email]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Your merchant contact email (optional)', 'unelmapay-payment-gateway') . '</p>';
    }
    
    public function success_url_field() {
        $options = get_option('unelmapay_settings');
        $value = isset($options['success_url']) ? $options['success_url'] : home_url('/payment-success/');
        echo '<input type="url" name="unelmapay_settings[success_url]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('URL to redirect after successful payment (leave empty for homepage)', 'unelmapay-payment-gateway') . '</p>';
    }
    
    public function fail_url_field() {
        $options = get_option('unelmapay_settings');
        $value = isset($options['fail_url']) ? $options['fail_url'] : home_url('/payment-failed/');
        echo '<input type="url" name="unelmapay_settings[fail_url]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('URL to redirect after failed payment (leave empty for homepage)', 'unelmapay-payment-gateway') . '</p>';
    }
    
    public function cancel_url_field() {
        $options = get_option('unelmapay_settings');
        $value = isset($options['cancel_url']) ? $options['cancel_url'] : home_url('/payment-cancelled/');
        echo '<input type="url" name="unelmapay_settings[cancel_url]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('URL to redirect if payment is cancelled (leave empty for homepage)', 'unelmapay-payment-gateway') . '</p>';
    }
    
    public function debug_mode_field() {
        $options = get_option('unelmapay_settings');
        $checked = isset($options['debug_mode']) && $options['debug_mode'] === 'yes' ? 'checked' : '';
        echo '<input type="hidden" name="unelmapay_settings[debug_mode]" value="no" />';
        echo '<label><input type="checkbox" name="unelmapay_settings[debug_mode]" value="yes" ' . $checked . ' /> ';
        echo esc_html__('Enable Debug Logging', 'unelmapay-payment-gateway') . '</label>';
        echo '<p class="description">' . esc_html__('Log UnelmaPay events for troubleshooting', 'unelmapay-payment-gateway') . '</p>';
    }
    
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=unelmapay-settings" class="nav-tab nav-tab-active"><?php esc_html_e('Settings', 'unelmapay-payment-gateway'); ?></a>
                <a href="edit.php?post_type=unelmapay_payment" class="nav-tab"><?php esc_html_e('Payments', 'unelmapay-payment-gateway'); ?></a>
            </h2>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('unelmapay_settings_group');
                do_settings_sections('unelmapay-settings');
                submit_button();
                ?>
            </form>
            
            <hr>
            
            <h2><?php esc_html_e('Usage Instructions', 'unelmapay-payment-gateway'); ?></h2>
            
            <?php if (class_exists('WooCommerce')): ?>
                <div class="notice notice-success inline">
                    <p><strong><?php esc_html_e('WooCommerce Mode Active', 'unelmapay-payment-gateway'); ?></strong></p>
                    <p><?php esc_html_e('UnelmaPay is available as a payment method in your WooCommerce checkout.', 'unelmapay-payment-gateway'); ?></p>
                </div>
            <?php endif; ?>
            
            <h3><?php esc_html_e('Shortcode Usage', 'unelmapay-payment-gateway'); ?></h3>
            <p><?php esc_html_e('Use the following shortcode to add a payment button anywhere on your site:', 'unelmapay-payment-gateway'); ?></p>
            
            <pre style="background: #f5f5f5; padding: 15px; border-left: 4px solid #0073aa;">[unelmapay_button amount="100" title="Product Name" description="Product Description"]</pre>
            
            <h4><?php esc_html_e('Shortcode Parameters:', 'unelmapay-payment-gateway'); ?></h4>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><code>amount</code> - <?php esc_html_e('Payment amount (required)', 'unelmapay-payment-gateway'); ?></li>
                <li><code>title</code> - <?php esc_html_e('Item name (required)', 'unelmapay-payment-gateway'); ?></li>
                <li><code>description</code> - <?php esc_html_e('Item description (optional)', 'unelmapay-payment-gateway'); ?></li>
                <li><code>button_text</code> - <?php esc_html_e('Button text (default: "Pay Now")', 'unelmapay-payment-gateway'); ?></li>
            </ul>
            
            <h4><?php esc_html_e('Example:', 'unelmapay-payment-gateway'); ?></h4>
            <pre style="background: #f5f5f5; padding: 15px; border-left: 4px solid #0073aa;">[unelmapay_button amount="250" title="Donation" description="Support our cause" button_text="Donate Now"]</pre>
            
            <h3><?php esc_html_e('IPN Callback URL', 'unelmapay-payment-gateway'); ?></h3>
            <p><?php esc_html_e('Your IPN callback URL is:', 'unelmapay-payment-gateway'); ?></p>
            <pre style="background: #f5f5f5; padding: 15px; border-left: 4px solid #0073aa;"><?php echo esc_url(home_url('/?unelmapay_ipn=1')); ?></pre>
            <p class="description"><?php esc_html_e('Make sure this URL is accessible from the internet for payment notifications to work.', 'unelmapay-payment-gateway'); ?></p>
        </div>
        <?php
    }
    
    public function payment_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '',
            'title' => '',
            'description' => '',
            'button_text' => __('Pay with UnelmaPay', 'unelmapay-payment-gateway'),
        ), $atts);
        
        if (empty($atts['amount']) || empty($atts['title'])) {
            return '<p style="color: red;">' . esc_html__('Error: amount and title are required parameters.', 'unelmapay-payment-gateway') . '</p>';
        }
        
        $payment_url = $this->sandbox_mode === 'yes' ? 'https://dev.unelmapay.com/sci/form' : 'https://unelmapay.com.np/sci/form';
        $payment_id = uniqid('pay_');
        
        $this->create_payment_record($payment_id, $atts);
        
        $success_url = !empty($this->success_url) ? $this->success_url : home_url('/?unelmapay_return=1&payment_id=' . $payment_id);
        $fail_url = !empty($this->fail_url) ? $this->fail_url : home_url('/?unelmapay_return=1&status=failed');
        $cancel_url = !empty($this->cancel_url) ? $this->cancel_url : home_url('/?unelmapay_cancel=1');
        
        $logo_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="32" height="32" style="vertical-align: middle; margin-right: 8px;"><circle cx="100" cy="100" r="100" fill="white"/><text x="100" y="85" font-family="Arial, sans-serif" font-size="70" font-weight="bold" fill="#7B4397" text-anchor="middle">U</text><text x="100" y="145" font-family="Arial, sans-serif" font-size="35" font-weight="bold" fill="#7B4397" text-anchor="middle">PAY</text></svg>';
        
        ob_start();
        ?>
        <form method="POST" action="<?php echo esc_url($payment_url); ?>" class="unelmapay-payment-form">
            <input type="hidden" name="merchant" value="<?php echo esc_attr($this->merchant_id); ?>">
            <input type="hidden" name="item_name" value="<?php echo esc_attr($atts['title']); ?>">
            <input type="hidden" name="amount" value="<?php echo esc_attr($atts['amount']); ?>">
            <input type="hidden" name="currency" value="debit_base">
            <input type="hidden" name="custom" value="<?php echo esc_attr($payment_id); ?>">
            <input type="hidden" name="return_url" value="<?php echo esc_url($success_url); ?>">
            <input type="hidden" name="fail_url" value="<?php echo esc_url($fail_url); ?>">
            <input type="hidden" name="cancel_url" value="<?php echo esc_url($cancel_url); ?>">
            <input type="hidden" name="notify_url" value="<?php echo esc_url(home_url('/?unelmapay_ipn=1')); ?>">
            <button type="submit" class="unelmapay-button">
                <?php
                $allowed_svg = array(
                    'svg' => array(
                        'xmlns'   => true,
                        'viewbox' => true,
                        'viewBox' => true,
                        'width'   => true,
                        'height'  => true,
                        'style'   => true,
                    ),
                    'circle' => array(
                        'cx'   => true,
                        'cy'   => true,
                        'r'    => true,
                        'fill' => true,
                    ),
                    'text' => array(
                        'x'           => true,
                        'y'           => true,
                        'font-family' => true,
                        'font-size'   => true,
                        'font-weight' => true,
                        'fill'        => true,
                        'text-anchor' => true,
                    ),
                );

                echo wp_kses($logo_svg, $allowed_svg);
                ?>
                <?php echo esc_html($atts['button_text']); ?>
            </button>
        </form>
        <?php
        return ob_get_clean();
    }
    
    private function create_payment_record($payment_id, $data) {
        $post_id = wp_insert_post(array(
            'post_type' => 'unelmapay_payment',
            'post_title' => $data['title'] . ' - ' . $payment_id,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ));
        
        if ($post_id) {
            update_post_meta($post_id, '_payment_id', $payment_id);
            update_post_meta($post_id, '_amount', $data['amount']);
            update_post_meta($post_id, '_title', $data['title']);
            update_post_meta($post_id, '_description', $data['description']);
            update_post_meta($post_id, '_status', 'pending');
            update_post_meta($post_id, '_created_at', current_time('mysql'));
        }
        
        return $post_id;
    }
    
    public function handle_ipn() {
        if (!isset($_GET['unelmapay_ipn'])) {
            return;
        }
        
        $request_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'UNKNOWN';
        $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : (isset($_SERVER['HTTP_CONTENT_TYPE']) ? $_SERVER['HTTP_CONTENT_TYPE'] : 'not set');
        $raw_body = file_get_contents('php://input');

        $this->log('IPN Request method: ' . $request_method);
        $this->log('IPN Content-Type: ' . $content_type);
        $this->log('IPN Raw body: ' . $raw_body);
        $this->log('IPN $_POST: ' . print_r($_POST, true));

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
            status_header(400);
            exit('Empty POST data');
        }
        
        $total = isset($post_data['total']) ? sanitize_text_field($post_data['total']) : '';
        $date = isset($post_data['date']) ? sanitize_text_field($post_data['date']) : '';
        $id_transfer = isset($post_data['id_transfer']) ? sanitize_text_field($post_data['id_transfer']) : '';
        $received_hash = isset($post_data['hash']) ? sanitize_text_field($post_data['hash']) : '';
        $custom = isset($post_data['custom']) ? sanitize_text_field($post_data['custom']) : '';
        
        $hash_string = $total . ':' . $this->merchant_password . ':' . $date . ':' . $id_transfer;
        $calculated_hash = strtoupper(md5($hash_string));
        
        $this->log('IPN Hash verification: received=' . $received_hash . ', calculated=' . $calculated_hash);
        
        if ($received_hash !== $calculated_hash) {
            $this->log('IPN Error: Hash mismatch');
            status_header(400);
            exit('Invalid hash');
        }
        
        $payment_post = $this->get_payment_by_id($custom);
        
        if ($payment_post) {
            update_post_meta($payment_post->ID, '_status', 'completed');
            update_post_meta($payment_post->ID, '_transaction_id', $id_transfer);
            update_post_meta($payment_post->ID, '_completed_at', current_time('mysql'));
            
            $this->log('IPN Success: Payment ' . $custom . ' marked as completed. Transaction ID: ' . $id_transfer);
        } else {
            $this->log('IPN Warning: Payment record not found for ID: ' . $custom);
        }
        
        status_header(200);
        exit('IPN Received and Verified');
    }
    
    private function get_payment_by_id($payment_id) {
        $args = array(
            'post_type' => 'unelmapay_payment',
            'meta_key' => '_payment_id',
            'meta_value' => $payment_id,
            'posts_per_page' => 1,
        );
        
        $posts = get_posts($args);
        return !empty($posts) ? $posts[0] : null;
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('unelmapay-styles', UNELMAPAY_PLUGIN_URL . 'assets/css/unelmapay.css', array(), UNELMAPAY_VERSION);
    }
    
    public function set_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('Payment ID', 'unelmapay-payment-gateway');
        $new_columns['amount'] = __('Amount (NPR)', 'unelmapay-payment-gateway');
        $new_columns['status'] = __('Status', 'unelmapay-payment-gateway');
        $new_columns['transaction_id'] = __('Transaction ID', 'unelmapay-payment-gateway');
        $new_columns['date'] = __('Date', 'unelmapay-payment-gateway');
        return $new_columns;
    }
    
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'amount':
                $amount = get_post_meta($post_id, '_amount', true);
                echo $amount ? 'NPR ' . esc_html($amount) : '-';
                break;
            case 'status':
                $status = get_post_meta($post_id, '_status', true);
                $status_label = $status === 'completed' ? __('Completed', 'unelmapay-payment-gateway') : __('Pending', 'unelmapay-payment-gateway');
                $status_class = $status === 'completed' ? 'completed' : 'pending';
                echo '<span class="unelmapay-status unelmapay-status-' . esc_attr($status_class) . '">' . esc_html($status_label) . '</span>';
                break;
            case 'transaction_id':
                $transaction_id = get_post_meta($post_id, '_transaction_id', true);
                echo $transaction_id ? esc_html($transaction_id) : '-';
                break;
        }
    }
    
    public function add_payment_meta_boxes() {
        add_meta_box(
            'unelmapay_payment_details',
            __('Payment Details', 'unelmapay-payment-gateway'),
            array($this, 'render_payment_details_meta_box'),
            'unelmapay_payment',
            'normal',
            'high'
        );
    }
    
    public function render_payment_details_meta_box($post) {
        $payment_id = get_post_meta($post->ID, '_payment_id', true);
        $amount = get_post_meta($post->ID, '_amount', true);
        $title = get_post_meta($post->ID, '_title', true);
        $description = get_post_meta($post->ID, '_description', true);
        $status = get_post_meta($post->ID, '_status', true);
        $transaction_id = get_post_meta($post->ID, '_transaction_id', true);
        $created_at = get_post_meta($post->ID, '_created_at', true);
        $completed_at = get_post_meta($post->ID, '_completed_at', true);
        
        ?>
 
        <table class="unelmapay-details-table">
            <tr>
                <th><?php esc_html_e('Payment ID', 'unelmapay-payment-gateway'); ?></th>
                <td><code><?php echo esc_html($payment_id); ?></code></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Item Name', 'unelmapay-payment-gateway'); ?></th>
                <td><?php echo esc_html($title); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Description', 'unelmapay-payment-gateway'); ?></th>
                <td><?php echo esc_html($description ? $description : '-'); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Amount', 'unelmapay-payment-gateway'); ?></th>
                <td><strong>NPR <?php echo esc_html($amount); ?></strong></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Status', 'unelmapay-payment-gateway'); ?></th>
                <td>
                    <?php
                    $status_label = $status === 'completed' ? __('Completed', 'unelmapay-payment-gateway') : __('Pending', 'unelmapay-payment-gateway');
                    $status_class = $status === 'completed' ? 'completed' : 'pending';
                    echo '<span class="unelmapay-status unelmapay-status-' . esc_attr($status_class) . '">' . esc_html($status_label) . '</span>';
                    ?>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Transaction ID', 'unelmapay-payment-gateway'); ?></th>
                <td><?php echo $transaction_id ? '<code>' . esc_html($transaction_id) . '</code>' : '-'; ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Created At', 'unelmapay-payment-gateway'); ?></th>
                <td><?php echo esc_html($created_at); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Completed At', 'unelmapay-payment-gateway'); ?></th>
                <td><?php echo $completed_at ? esc_html($completed_at) : '-'; ?></td>
            </tr>
        </table>
        <p class="description" style="margin-top: 15px;">
            <?php esc_html_e('All payments are processed in NPR (Nepalese Rupee), which is the base currency in UnelmaPay system.', 'unelmapay-payment-gateway'); ?>
        </p>
        <?php
    }
    
    public function logs_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="notice notice-info">
                <p><?php esc_html_e('Debug logs are stored in your WordPress error log. Enable Debug Mode in settings to start logging.', 'unelmapay-payment-gateway'); ?></p>
            </div>
            
            <?php
            $log_file = WP_CONTENT_DIR . '/debug.log';
            
            if (file_exists($log_file)) {
                $log_content = file_get_contents($log_file);
                $log_lines = explode("\n", $log_content);
                $unelmapay_logs = array_filter($log_lines, function($line) {
                    return strpos($line, '[UnelmaPay]') !== false;
                });
                
                if (!empty($unelmapay_logs)) {
                    $recent_logs = array_slice(array_reverse($unelmapay_logs), 0, 100);
                    ?>
                    <div style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; border-radius: 4px; max-height: 600px; overflow-y: auto;">
                        <h3><?php esc_html_e('Recent UnelmaPay Logs (Last 100 entries)', 'unelmapay-payment-gateway'); ?></h3>
                        <pre style="white-space: pre-wrap; word-wrap: break-word; font-size: 12px;"><?php echo esc_html(implode("\n", $recent_logs)); ?></pre>
                    </div>
                    
                    <p style="margin-top: 15px;">
                        <a
                            href="<?php echo esc_url(admin_url('admin.php?page=unelmapay-logs&action=clear')); ?>"
                            class="button"
                            onclick="return confirm('<?php echo esc_js(__('Are you sure you want to clear all UnelmaPay logs?', 'unelmapay-payment-gateway')); ?>');"
                        >
                            <?php esc_html_e('Clear UnelmaPay Logs', 'unelmapay-payment-gateway'); ?>
                        </a>

                        <a
                            href="<?php echo esc_url(admin_url('admin.php?page=unelmapay-logs&action=download')); ?>"
                            class="button"
                        >
                            <?php esc_html_e('Download Logs', 'unelmapay-payment-gateway'); ?>
                        </a>
                    </p>
                    <?php
                } else {
                    echo '<div class="notice notice-warning"><p>' . esc_html__('No UnelmaPay logs found. Make sure Debug Mode is enabled in settings.', 'unelmapay-payment-gateway') . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Debug log file not found. Make sure WP_DEBUG_LOG is enabled in wp-config.php.', 'unelmapay-payment-gateway') . '</p></div>';
                echo '<p>' . esc_html__('Add the following to your wp-config.php:', 'unelmapay-payment-gateway') . '</p>';
                echo '<pre style="background: #f5f5f5; padding: 10px;">define(\'WP_DEBUG\', true);<br>define(\'WP_DEBUG_LOG\', true);<br>define(\'WP_DEBUG_DISPLAY\', false);</pre>';
            }
            
            if (isset($_GET['action'])) {
                if ($_GET['action'] === 'clear' && file_exists($log_file)) {
                    $log_content = file_get_contents($log_file);
                    $log_lines = explode("\n", $log_content);
                    $filtered_logs = array_filter($log_lines, function($line) {
                        return strpos($line, '[UnelmaPay]') === false;
                    });
                    file_put_contents($log_file, implode("\n", $filtered_logs));
                    echo '<div class="notice notice-success"><p>' . esc_html__('UnelmaPay logs cleared successfully.', 'unelmapay-payment-gateway') . '</p></div>';
                }
                
                if ($_GET['action'] === 'download' && file_exists($log_file)) {
                    $log_content = file_get_contents($log_file);
                    $log_lines = explode("\n", $log_content);
                    $unelmapay_logs = array_filter($log_lines, function($line) {
                        return strpos($line, '[UnelmaPay]') !== false;
                    });
                    
                    header('Content-Type: text/plain');
                    header('Content-Disposition: attachment; filename="unelmapay-logs-' . date('Y-m-d-His') . '.txt"');
                    echo esc_html(implode("\n", $unelmapay_logs));
                    exit;
                }
            }
            ?>
            
            <h3 style="margin-top: 30px;"><?php esc_html_e('What Gets Logged?', 'unelmapay-payment-gateway'); ?></h3>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><?php esc_html_e('Payment form generation', 'unelmapay-payment-gateway'); ?></li>
                <li><?php esc_html_e('IPN callback requests', 'unelmapay-payment-gateway'); ?></li>
                <li><?php esc_html_e('Hash verification results', 'unelmapay-payment-gateway'); ?></li>
                <li><?php esc_html_e('Payment status updates', 'unelmapay-payment-gateway'); ?></li>
                <li><?php esc_html_e('Error messages and warnings', 'unelmapay-payment-gateway'); ?></li>
            </ul>
            
            <h3 style="margin-top: 20px;"><?php esc_html_e('Currency Information', 'unelmapay-payment-gateway'); ?></h3>
            <div class="notice notice-info inline">
                <p><strong><?php esc_html_e('All payments are processed in NPR (Nepalese Rupee)', 'unelmapay-payment-gateway'); ?></strong></p>
                <p><?php esc_html_e('NPR is the base currency in the UnelmaPay system. All amounts are automatically converted to NPR during payment processing.', 'unelmapay-payment-gateway'); ?></p>
            </div>
        </div>
        <?php
    }
    
    private function log($message) {
        if ($this->debug_mode === 'yes') {
            error_log('[UnelmaPay] ' . $message);
        }
    }

    public function enqueue_admin_assets($hook) {
        if (isset($_GET['page']) && strpos($_GET['page'], 'unelmapay') !== false) {
            wp_enqueue_style(
                'unelmapay-admin',
                plugins_url('../assets/css/unelmapay-admin.css', __FILE__),
                array(),
                '1.0.0'
            );
        }
    }
}
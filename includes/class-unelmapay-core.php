<?php
if (!defined('ABSPATH')) {
    exit;
}

class UnelmaPay_Core {
    
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
    }
    
    public function register_post_type() {
        register_post_type('unelmapay_payment', array(
            'labels' => array(
                'name' => __('Payments', 'unelmapay-woocommerce'),
                'singular_name' => __('Payment', 'unelmapay-woocommerce'),
                'add_new' => __('Add New', 'unelmapay-woocommerce'),
                'add_new_item' => __('Add New Payment', 'unelmapay-woocommerce'),
                'edit_item' => __('Edit Payment', 'unelmapay-woocommerce'),
                'view_item' => __('View Payment', 'unelmapay-woocommerce'),
                'all_items' => __('All Payments', 'unelmapay-woocommerce'),
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
            __('UnelmaPay', 'unelmapay-woocommerce'),
            __('UnelmaPay', 'unelmapay-woocommerce'),
            'manage_options',
            'unelmapay-settings',
            array($this, 'settings_page'),
            'dashicons-money-alt',
            56
        );
        
        add_submenu_page(
            'unelmapay-settings',
            __('Settings', 'unelmapay-woocommerce'),
            __('Settings', 'unelmapay-woocommerce'),
            'manage_options',
            'unelmapay-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'unelmapay-settings',
            __('Debug Logs', 'unelmapay-woocommerce'),
            __('Debug Logs', 'unelmapay-woocommerce'),
            'manage_options',
            'unelmapay-logs',
            array($this, 'logs_page')
        );
    }
    
    public function register_settings() {
        register_setting('unelmapay_settings_group', 'unelmapay_settings');
        
        add_settings_section(
            'unelmapay_main_section',
            __('UnelmaPay Settings', 'unelmapay-woocommerce'),
            array($this, 'settings_section_callback'),
            'unelmapay-settings'
        );
        
        add_settings_field('merchant_id', __('Merchant ID', 'unelmapay-woocommerce'), array($this, 'merchant_id_field'), 'unelmapay-settings', 'unelmapay_main_section');
        add_settings_field('merchant_password', __('Merchant Password', 'unelmapay-woocommerce'), array($this, 'merchant_password_field'), 'unelmapay-settings', 'unelmapay_main_section');
        add_settings_field('merchant_name', __('Merchant Name', 'unelmapay-woocommerce'), array($this, 'merchant_name_field'), 'unelmapay-settings', 'unelmapay_main_section');
        add_settings_field('merchant_email', __('Merchant Email', 'unelmapay-woocommerce'), array($this, 'merchant_email_field'), 'unelmapay-settings', 'unelmapay_main_section');
        add_settings_field('success_url', __('Success URL', 'unelmapay-woocommerce'), array($this, 'success_url_field'), 'unelmapay-settings', 'unelmapay_main_section');
        add_settings_field('fail_url', __('Fail URL', 'unelmapay-woocommerce'), array($this, 'fail_url_field'), 'unelmapay-settings', 'unelmapay_main_section');
        add_settings_field('cancel_url', __('Cancel URL', 'unelmapay-woocommerce'), array($this, 'cancel_url_field'), 'unelmapay-settings', 'unelmapay_main_section');
        add_settings_field('sandbox_mode', __('Sandbox Mode', 'unelmapay-woocommerce'), array($this, 'sandbox_mode_field'), 'unelmapay-settings', 'unelmapay_main_section');
        add_settings_field('debug_mode', __('Debug Mode', 'unelmapay-woocommerce'), array($this, 'debug_mode_field'), 'unelmapay-settings', 'unelmapay_main_section');
    }
    
    public function settings_section_callback() {
        echo '<p>' . __('Configure your UnelmaPay payment gateway settings.', 'unelmapay-woocommerce') . '</p>';
        if (class_exists('WooCommerce')) {
            echo '<div class="notice notice-info"><p>' . __('WooCommerce detected! You can also configure UnelmaPay in WooCommerce → Settings → Payments.', 'unelmapay-woocommerce') . '</p></div>';
        }
        echo '<div class="notice notice-info inline" style="margin-top: 10px;"><p><strong>' . __('Currency:', 'unelmapay-woocommerce') . '</strong> ' . __('All payments are processed in NPR (Nepalese Rupee), which is the base currency in the UnelmaPay system.', 'unelmapay-woocommerce') . '</p></div>';
    }
    
    public function merchant_id_field() {
        $options = get_option('unelmapay_settings');
        $value = isset($options['merchant_id']) ? $options['merchant_id'] : '';
        echo '<input type="text" name="unelmapay_settings[merchant_id]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter your UnelmaPay Merchant ID', 'unelmapay-woocommerce') . '</p>';
    }
    
    public function merchant_password_field() {
        $options = get_option('unelmapay_settings');
        $value = isset($options['merchant_password']) ? $options['merchant_password'] : '';
        echo '<input type="password" name="unelmapay_settings[merchant_password]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter your UnelmaPay Merchant Password (used for IPN verification)', 'unelmapay-woocommerce') . '</p>';
    }
    
    public function sandbox_mode_field() {
        $options = get_option('unelmapay_settings');
        $checked = isset($options['sandbox_mode']) && $options['sandbox_mode'] === 'yes' ? 'checked' : '';
        echo '<label><input type="checkbox" name="unelmapay_settings[sandbox_mode]" value="yes" ' . $checked . ' /> ';
        echo __('Enable Sandbox Mode (dev.unelmapay.com)', 'unelmapay-woocommerce') . '</label>';
        echo '<p class="description">' . __('Use sandbox environment for testing. Uncheck for production.', 'unelmapay-woocommerce') . '</p>';
    }
    
    public function merchant_name_field() {
        $options = get_option('unelmapay_settings');
        $value = isset($options['merchant_name']) ? $options['merchant_name'] : '';
        echo '<input type="text" name="unelmapay_settings[merchant_name]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Your business/merchant name (optional)', 'unelmapay-woocommerce') . '</p>';
    }
    
    public function merchant_email_field() {
        $options = get_option('unelmapay_settings');
        $value = isset($options['merchant_email']) ? $options['merchant_email'] : '';
        echo '<input type="email" name="unelmapay_settings[merchant_email]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Your merchant contact email (optional)', 'unelmapay-woocommerce') . '</p>';
    }
    
    public function success_url_field() {
        $options = get_option('unelmapay_settings');
        $value = isset($options['success_url']) ? $options['success_url'] : home_url('/payment-success/');
        echo '<input type="url" name="unelmapay_settings[success_url]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('URL to redirect after successful payment (leave empty for homepage)', 'unelmapay-woocommerce') . '</p>';
    }
    
    public function fail_url_field() {
        $options = get_option('unelmapay_settings');
        $value = isset($options['fail_url']) ? $options['fail_url'] : home_url('/payment-failed/');
        echo '<input type="url" name="unelmapay_settings[fail_url]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('URL to redirect after failed payment (leave empty for homepage)', 'unelmapay-woocommerce') . '</p>';
    }
    
    public function cancel_url_field() {
        $options = get_option('unelmapay_settings');
        $value = isset($options['cancel_url']) ? $options['cancel_url'] : home_url('/payment-cancelled/');
        echo '<input type="url" name="unelmapay_settings[cancel_url]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('URL to redirect if payment is cancelled (leave empty for homepage)', 'unelmapay-woocommerce') . '</p>';
    }
    
    public function debug_mode_field() {
        $options = get_option('unelmapay_settings');
        $checked = isset($options['debug_mode']) && $options['debug_mode'] === 'yes' ? 'checked' : '';
        echo '<label><input type="checkbox" name="unelmapay_settings[debug_mode]" value="yes" ' . $checked . ' /> ';
        echo __('Enable Debug Logging', 'unelmapay-woocommerce') . '</label>';
        echo '<p class="description">' . __('Log UnelmaPay events for troubleshooting', 'unelmapay-woocommerce') . '</p>';
    }
    
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=unelmapay-settings" class="nav-tab nav-tab-active"><?php _e('Settings', 'unelmapay-woocommerce'); ?></a>
                <a href="edit.php?post_type=unelmapay_payment" class="nav-tab"><?php _e('Payments', 'unelmapay-woocommerce'); ?></a>
            </h2>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('unelmapay_settings_group');
                do_settings_sections('unelmapay-settings');
                submit_button();
                ?>
            </form>
            
            <hr>
            
            <h2><?php _e('Usage Instructions', 'unelmapay-woocommerce'); ?></h2>
            
            <?php if (class_exists('WooCommerce')): ?>
                <div class="notice notice-success inline">
                    <p><strong><?php _e('WooCommerce Mode Active', 'unelmapay-woocommerce'); ?></strong></p>
                    <p><?php _e('UnelmaPay is available as a payment method in your WooCommerce checkout.', 'unelmapay-woocommerce'); ?></p>
                </div>
            <?php endif; ?>
            
            <h3><?php _e('Shortcode Usage', 'unelmapay-woocommerce'); ?></h3>
            <p><?php _e('Use the following shortcode to add a payment button anywhere on your site:', 'unelmapay-woocommerce'); ?></p>
            
            <pre style="background: #f5f5f5; padding: 15px; border-left: 4px solid #0073aa;">[unelmapay_button amount="100" title="Product Name" description="Product Description"]</pre>
            
            <h4><?php _e('Shortcode Parameters:', 'unelmapay-woocommerce'); ?></h4>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><code>amount</code> - <?php _e('Payment amount (required)', 'unelmapay-woocommerce'); ?></li>
                <li><code>title</code> - <?php _e('Item name (required)', 'unelmapay-woocommerce'); ?></li>
                <li><code>description</code> - <?php _e('Item description (optional)', 'unelmapay-woocommerce'); ?></li>
                <li><code>button_text</code> - <?php _e('Button text (default: "Pay Now")', 'unelmapay-woocommerce'); ?></li>
            </ul>
            
            <h4><?php _e('Example:', 'unelmapay-woocommerce'); ?></h4>
            <pre style="background: #f5f5f5; padding: 15px; border-left: 4px solid #0073aa;">[unelmapay_button amount="250" title="Donation" description="Support our cause" button_text="Donate Now"]</pre>
            
            <h3><?php _e('IPN Callback URL', 'unelmapay-woocommerce'); ?></h3>
            <p><?php _e('Your IPN callback URL is:', 'unelmapay-woocommerce'); ?></p>
            <pre style="background: #f5f5f5; padding: 15px; border-left: 4px solid #0073aa;"><?php echo home_url('/?unelmapay_ipn=1'); ?></pre>
            <p class="description"><?php _e('Make sure this URL is accessible from the internet for payment notifications to work.', 'unelmapay-woocommerce'); ?></p>
        </div>
        <?php
    }
    
    public function payment_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '',
            'title' => '',
            'description' => '',
            'button_text' => __('Pay now via UnelmaPay', 'unelmapay-woocommerce'),
        ), $atts);
        
        if (empty($atts['amount']) || empty($atts['title'])) {
            return '<p style="color: red;">' . __('Error: amount and title are required parameters.', 'unelmapay-woocommerce') . '</p>';
        }
        
        $payment_url = $this->sandbox_mode === 'yes' ? 'https://dev.unelmapay.com/sci/form' : 'https://unelmapay.com.np/sci/form';
        $payment_id = uniqid('pay_');
        
        $this->create_payment_record($payment_id, $atts);
        
        $success_url = !empty($this->success_url) ? $this->success_url : home_url('/?unelmapay_return=1&payment_id=' . $payment_id);
        $fail_url = !empty($this->fail_url) ? $this->fail_url : home_url('/?unelmapay_return=1&status=failed');
        $cancel_url = !empty($this->cancel_url) ? $this->cancel_url : home_url('/?unelmapay_cancel=1');
        
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
            <button type="submit" class="unelmapay-button"><?php echo esc_html($atts['button_text']); ?></button>
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
        
        $this->log('IPN Request received: ' . print_r($_POST, true));
        
        if (empty($_POST)) {
            $this->log('IPN Error: Empty POST data');
            status_header(400);
            exit('Empty POST data');
        }
        
        $total = isset($_POST['total']) ? sanitize_text_field($_POST['total']) : '';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $id_transfer = isset($_POST['id_transfer']) ? sanitize_text_field($_POST['id_transfer']) : '';
        $received_hash = isset($_POST['hash']) ? sanitize_text_field($_POST['hash']) : '';
        $custom = isset($_POST['custom']) ? sanitize_text_field($_POST['custom']) : '';
        
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
        $new_columns['title'] = __('Payment ID', 'unelmapay-woocommerce');
        $new_columns['amount'] = __('Amount (NPR)', 'unelmapay-woocommerce');
        $new_columns['status'] = __('Status', 'unelmapay-woocommerce');
        $new_columns['transaction_id'] = __('Transaction ID', 'unelmapay-woocommerce');
        $new_columns['date'] = __('Date', 'unelmapay-woocommerce');
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
                $status_label = $status === 'completed' ? __('Completed', 'unelmapay-woocommerce') : __('Pending', 'unelmapay-woocommerce');
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
            __('Payment Details', 'unelmapay-woocommerce'),
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
        <style>
            .unelmapay-details-table { width: 100%; border-collapse: collapse; }
            .unelmapay-details-table th { text-align: left; padding: 10px; background: #f5f5f5; width: 30%; }
            .unelmapay-details-table td { padding: 10px; border-bottom: 1px solid #ddd; }
            .unelmapay-status { padding: 5px 10px; border-radius: 3px; font-weight: bold; }
            .unelmapay-status-completed { background: #d4edda; color: #155724; }
            .unelmapay-status-pending { background: #fff3cd; color: #856404; }
        </style>
        <table class="unelmapay-details-table">
            <tr>
                <th><?php _e('Payment ID', 'unelmapay-woocommerce'); ?></th>
                <td><code><?php echo esc_html($payment_id); ?></code></td>
            </tr>
            <tr>
                <th><?php _e('Item Name', 'unelmapay-woocommerce'); ?></th>
                <td><?php echo esc_html($title); ?></td>
            </tr>
            <tr>
                <th><?php _e('Description', 'unelmapay-woocommerce'); ?></th>
                <td><?php echo esc_html($description ? $description : '-'); ?></td>
            </tr>
            <tr>
                <th><?php _e('Amount', 'unelmapay-woocommerce'); ?></th>
                <td><strong>NPR <?php echo esc_html($amount); ?></strong></td>
            </tr>
            <tr>
                <th><?php _e('Status', 'unelmapay-woocommerce'); ?></th>
                <td>
                    <?php
                    $status_label = $status === 'completed' ? __('Completed', 'unelmapay-woocommerce') : __('Pending', 'unelmapay-woocommerce');
                    $status_class = $status === 'completed' ? 'completed' : 'pending';
                    echo '<span class="unelmapay-status unelmapay-status-' . esc_attr($status_class) . '">' . esc_html($status_label) . '</span>';
                    ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Transaction ID', 'unelmapay-woocommerce'); ?></th>
                <td><?php echo $transaction_id ? '<code>' . esc_html($transaction_id) . '</code>' : '-'; ?></td>
            </tr>
            <tr>
                <th><?php _e('Created At', 'unelmapay-woocommerce'); ?></th>
                <td><?php echo esc_html($created_at); ?></td>
            </tr>
            <tr>
                <th><?php _e('Completed At', 'unelmapay-woocommerce'); ?></th>
                <td><?php echo $completed_at ? esc_html($completed_at) : '-'; ?></td>
            </tr>
        </table>
        <p class="description" style="margin-top: 15px;">
            <?php _e('All payments are processed in NPR (Nepalese Rupee), which is the base currency in UnelmaPay system.', 'unelmapay-woocommerce'); ?>
        </p>
        <?php
    }
    
    public function logs_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="notice notice-info">
                <p><?php _e('Debug logs are stored in your WordPress error log. Enable Debug Mode in settings to start logging.', 'unelmapay-woocommerce'); ?></p>
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
                        <h3><?php _e('Recent UnelmaPay Logs (Last 100 entries)', 'unelmapay-woocommerce'); ?></h3>
                        <pre style="white-space: pre-wrap; word-wrap: break-word; font-size: 12px;"><?php echo esc_html(implode("\n", $recent_logs)); ?></pre>
                    </div>
                    
                    <p style="margin-top: 15px;">
                        <a href="<?php echo admin_url('admin.php?page=unelmapay-logs&action=clear'); ?>" class="button" onclick="return confirm('<?php esc_attr_e('Are you sure you want to clear all UnelmaPay logs?', 'unelmapay-woocommerce'); ?>');">
                            <?php _e('Clear UnelmaPay Logs', 'unelmapay-woocommerce'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=unelmapay-logs&action=download'); ?>" class="button">
                            <?php _e('Download Logs', 'unelmapay-woocommerce'); ?>
                        </a>
                    </p>
                    <?php
                } else {
                    echo '<div class="notice notice-warning"><p>' . __('No UnelmaPay logs found. Make sure Debug Mode is enabled in settings.', 'unelmapay-woocommerce') . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>' . __('Debug log file not found. Make sure WP_DEBUG_LOG is enabled in wp-config.php.', 'unelmapay-woocommerce') . '</p></div>';
                echo '<p>' . __('Add the following to your wp-config.php:', 'unelmapay-woocommerce') . '</p>';
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
                    echo '<div class="notice notice-success"><p>' . __('UnelmaPay logs cleared successfully.', 'unelmapay-woocommerce') . '</p></div>';
                    echo '<script>setTimeout(function(){ window.location.href = "' . admin_url('admin.php?page=unelmapay-logs') . '"; }, 2000);</script>';
                }
                
                if ($_GET['action'] === 'download' && file_exists($log_file)) {
                    $log_content = file_get_contents($log_file);
                    $log_lines = explode("\n", $log_content);
                    $unelmapay_logs = array_filter($log_lines, function($line) {
                        return strpos($line, '[UnelmaPay]') !== false;
                    });
                    
                    header('Content-Type: text/plain');
                    header('Content-Disposition: attachment; filename="unelmapay-logs-' . date('Y-m-d-His') . '.txt"');
                    echo implode("\n", $unelmapay_logs);
                    exit;
                }
            }
            ?>
            
            <h3 style="margin-top: 30px;"><?php _e('What Gets Logged?', 'unelmapay-woocommerce'); ?></h3>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><?php _e('Payment form generation', 'unelmapay-woocommerce'); ?></li>
                <li><?php _e('IPN callback requests', 'unelmapay-woocommerce'); ?></li>
                <li><?php _e('Hash verification results', 'unelmapay-woocommerce'); ?></li>
                <li><?php _e('Payment status updates', 'unelmapay-woocommerce'); ?></li>
                <li><?php _e('Error messages and warnings', 'unelmapay-woocommerce'); ?></li>
            </ul>
            
            <h3 style="margin-top: 20px;"><?php _e('Currency Information', 'unelmapay-woocommerce'); ?></h3>
            <div class="notice notice-info inline">
                <p><strong><?php _e('All payments are processed in NPR (Nepalese Rupee)', 'unelmapay-woocommerce'); ?></strong></p>
                <p><?php _e('NPR is the base currency in the UnelmaPay system. All amounts are automatically converted to NPR during payment processing.', 'unelmapay-woocommerce'); ?></p>
            </div>
        </div>
        <?php
    }
    
    private function log($message) {
        if ($this->debug_mode === 'yes') {
            error_log('[UnelmaPay] ' . $message);
        }
    }
}

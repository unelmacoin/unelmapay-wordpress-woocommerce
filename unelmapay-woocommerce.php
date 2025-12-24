<?php
/**
 * Plugin Name: UnelmaPay Payment Gateway
 * Plugin URI: https://unelmapay.com/
 * Description: Accept payments via UnelmaPay. Works standalone or with WooCommerce. Supports sandbox and production environments.
 * Version: 2.0.0
 * Author: UnelmaPay
 * Author URI: https://unelmapay.com/
 * Text Domain: unelmapay-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) {
    exit;
}

define('UNELMAPAY_VERSION', '2.0.0');
define('UNELMAPAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UNELMAPAY_PLUGIN_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', 'unelmapay_init', 11);

function unelmapay_init() {
    require_once UNELMAPAY_PLUGIN_DIR . 'includes/class-unelmapay-core.php';
    
    if (class_exists('WC_Payment_Gateway')) {
        require_once UNELMAPAY_PLUGIN_DIR . 'includes/class-wc-gateway-unelmapay.php';
        add_filter('woocommerce_payment_gateways', function($gateways) {
            $gateways[] = 'WC_Gateway_UnelmaPay';
            return $gateways;
        });
    }
    
    UnelmaPay_Core::instance();
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    if (class_exists('WooCommerce')) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=unelmapay') . '">Settings</a>';
    } else {
        $settings_link = '<a href="' . admin_url('admin.php?page=unelmapay-settings') . '">Settings</a>';
    }
    array_unshift($links, $settings_link);
    return $links;
});

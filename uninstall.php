<?php
/**
 * Uninstall UnelmaPay Payment Gateway
 *
 * @package UnelmaPay_WooCommerce
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('woocommerce_unelmapay_settings');

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class UnelmaPay_Blocks_Support extends AbstractPaymentMethodType {
    protected $name = 'unelmapay';
    protected $settings = array();

    public function initialize() {
        $this->settings = get_option( 'woocommerce_unelmapay_settings', array() );
    }

    public function is_active() {
        $enabled = isset( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
        $has_credentials = ! empty( $this->settings['merchant_id'] ) && ! empty( $this->settings['merchant_password'] );
        return $enabled && $has_credentials;
    }

    public function get_payment_method_script_handles() {
        $script_path = UNELMAPAY_PLUGIN_DIR . 'assets/js/checkout-block.js';
        $script_url  = UNELMAPAY_PLUGIN_URL . 'assets/js/checkout-block.js';
        $script_ver  = file_exists( $script_path ) ? (string) filemtime( $script_path ) : UNELMAPAY_VERSION;

        wp_register_script(
            'unelmapay-checkout-block',
            $script_url,
            array( 'wp-element', 'wc-blocks-registry', 'wc-settings' ),
            $script_ver,
            true
        );

        return array( 'unelmapay-checkout-block' );
    }

    public function get_payment_method_data() {
        return array(
            'title'       => $this->settings['title'] ?? 'UnelmaPay',
            'description' => $this->settings['description'] ?? 'Pay securely via UnelmaPay.',
            'supports'    => array(
                'features' => array( 'products' ),
            ),
        );
    }
}
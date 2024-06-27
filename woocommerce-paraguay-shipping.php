<?php
/*
Plugin Name: WooCommerce Paraguay Shipping
Description: A WooCommerce shipping method for Paraguay with city-based rates.
Version: 1.0.0
Author: Fernando Roche
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include the main Paraguay Shipping class
if (!class_exists('WC_Paraguay_Shipping')) {
    class WC_Paraguay_Shipping {

        public function __construct() {
            add_action('woocommerce_shipping_init', array($this, 'init'));
            add_filter('woocommerce_shipping_methods', array($this, 'add_method'));
        }

        public function init() {
            //require_once plugin_dir_path(__FILE__) . 'includes/class-wc-shipping-paraguay.php';
        }

        public function add_method($methods) {
            $methods['paraguay_shipping'] = 'WC_Shipping_Paraguay';
            return $methods;
        }
    }

    new WC_Paraguay_Shipping();
}

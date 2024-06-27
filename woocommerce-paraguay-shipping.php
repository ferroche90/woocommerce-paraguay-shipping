<?php
/**
 * Plugin Name: WooCommerce Paraguay Shipping
 * Description: This plugin provides a custom shipping method for WooCommerce tailored to Paraguay, allowing merchants to set different shipping rates according to the different cities within the country.
 * Version: 1.0.2
 * Author: Fernando Roche
 * Author URI: https://fernandoroche.com/
 * Developer: Fernando Roche
 * Developer URI: https://fernandoroche.com/
 * Text Domain: woocommerce-paraguay-shipping
 * Domain Path: /languages
 *
 * WC requires at least: 8.8
 * WC tested up to: 9.0
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WooCommerce/woocommerce-paraguay-shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main class for Paraguay Shipping.
 */
if ( ! class_exists( 'WC_Paraguay_Shipping' ) ) {

	/**
	 * WC_Paraguay_Shipping class.
	 */
	class WC_Paraguay_Shipping {

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'woocommerce_shipping_init', array( $this, 'init' ) );
			add_filter( 'woocommerce_shipping_methods', array( $this, 'add_method' ) );
		}

		/**
		 * Initialize the shipping method.
		 */
		public function init() {
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-shipping-paraguay.php';
		}

		/**
		 * Add the shipping method to WooCommerce.
		 *
		 * @param array $methods Existing shipping methods.
		 * @return array Modified shipping methods.
		 */
		public function add_method( $methods ) {
			$methods['paraguay_shipping'] = 'WC_Shipping_Paraguay';
			return $methods;
		}
	}

	new WC_Paraguay_Shipping();
}

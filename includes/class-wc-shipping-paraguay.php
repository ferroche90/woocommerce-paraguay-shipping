<?php
/**
 * WC_Shipping_Paraguay class.
 *
 * @package WooCommerce/woocommerce-paraguay-shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Shipping_Paraguay' ) ) {

	/**
	 * Custom shipping method for Paraguay.
	 */
	class WC_Shipping_Paraguay extends WC_Shipping_Method {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id                 = 'paraguay_shipping';
			$this->method_title       = __( 'Paraguay Shipping', 'woocommerce-paraguay-shipping' );
			$this->method_description = __( 'Custom Shipping Method for Paraguay', 'woocommerce-paraguay-shipping' );

			// Availability & Countries.
			$this->availability = 'including';
			$this->countries    = array( 'PY' );

			$this->init();
		}

		/**
		 * Initialize settings and form fields.
		 */
		public function init() {
			$this->init_form_fields();
			$this->init_settings();

			// Save settings in admin if you have any defined.
			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		/**
		 * Define form fields for settings.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'          => array(
					'title'       => __( 'Enable', 'woocommerce-paraguay-shipping' ),
					'type'        => 'checkbox',
					'description' => __( 'Enable this shipping method.', 'woocommerce-paraguay-shipping' ),
					'default'     => 'yes',
				),
				'title'            => array(
					'title'       => __( 'Title', 'woocommerce-paraguay-shipping' ),
					'type'        => 'text',
					'description' => __( 'Title to be displayed on the checkout page.', 'woocommerce-paraguay-shipping' ),
					'default'     => __( 'Paraguay Shipping', 'woocommerce-paraguay-shipping' ),
				),
				'rates'            => array(
					'title'       => __( 'Rates', 'woocommerce-paraguay-shipping' ),
					'type'        => 'textarea',
					'description' => __( 'Enter rates for different cities in the format: City|Department|Rate. One city per line.', 'woocommerce-paraguay-shipping' ),
					'default'     => '',
				),
				'pickup_locations' => array(
					'title'       => __( 'Pickup Locations', 'woocommerce-paraguay-shipping' ),
					'type'        => 'textarea',
					'description' => __( 'Enter special pickup locations and rates in the format: Location|Rate. One location per line.', 'woocommerce-paraguay-shipping' ),
					'default'     => '',
				),
			);
		}

		/**
		 * Calculate shipping cost based on destination city.
		 *
		 * @param array $package The package being shipped.
		 */
		public function calculate_shipping( $package = array() ) {
			$destination_city = $package['destination']['city'];
			$rates            = $this->get_option( 'rates' );
			$pickup_locations = $this->get_option( 'pickup_locations' );
			$city_rates       = $this->parse_rates( $rates );
			$special_rates    = $this->parse_rates( $pickup_locations, false );
			$cost             = 0;

			if ( isset( $city_rates[ $destination_city ] ) ) {
				$cost = $city_rates[ $destination_city ]['rate'];
			}

			$rate = array(
				'id'       => $this->id,
				'label'    => $this->get_option( 'title' ) . ' a ' . $destination_city,
				'cost'     => $cost,
				'calc_tax' => 'per_item',
			);

			$this->add_rate( $rate );

			foreach ( $special_rates as $location => $rate_cost ) {
				$pickup_rate = array(
					'id'       => $this->id . '_' . sanitize_title( $location ),
					'label'    => $location,
					'cost'     => $rate_cost['rate'],
					'calc_tax' => 'per_item',
				);

				$this->add_rate( $pickup_rate );
			}
		}

		/**
		 * Parse the rates entered in the settings.
		 *
		 * @param string $rates The rates entered in the settings.
		 * @param bool   $normalize Whether to normalize the keys for matching.
		 * @return array Parsed city rates.
		 */
		public function parse_rates( $rates ) {
			$lines      = explode( "\n", $rates );
			$city_rates = array();

			foreach ( $lines as $line ) {
				list( $city, $department, $rate ) = explode( '|', trim( $line ) );
				$city                             = trim( $city );
				$department                       = trim( $department );
				$rate                             = trim( $rate );

				$city_rates[ $city ] = array(
					'department' => $department,
					'rate'       => $rate,
				);
			}

			return $city_rates;
		}

		/**
		 * Get the list of cities from the rates settings.
		 *
		 * This method parses the 'rates' option from the plugin settings and extracts the city names.
		 *
		 * @return array An associative array of city names and their departments.
		 */
		public function get_cities() {
			$rates  = $this->get_option( 'rates' );
			$cities = array();

			if ( ! empty( $rates ) ) {
				$lines = explode( "\n", $rates );

				foreach ( $lines as $line ) {
					list( $city, $department, $rate ) = explode( '|', trim( $line ) );
					$cities[ trim( $city ) ]          = array(
						'department' => trim( $department ),
						'rate'       => trim( $rate ),
					);
				}
			}

			return $cities;
		}
	}
}

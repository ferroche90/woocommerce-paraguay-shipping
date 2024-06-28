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
				'default_cost'     => array(
					'title'       => __( 'Default Cost', 'woocommerce-paraguay-shipping' ),
					'type'        => 'number',
					'description' => __( 'Default cost to be applied if the city is not found in the rates.', 'woocommerce-paraguay-shipping' ),
					'default'     => '',
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
			$destination_city = isset( $package['destination']['city'] ) ? $package['destination']['city'] : '';
			if ( empty( $destination_city ) ) {
				return; // Don't add shipping rates if the city is not set.
			}

			$normalized_city = $this->normalize_city_name( $destination_city );

			$rates            = $this->get_option( 'rates' );
			$pickup_locations = $this->get_option( 'pickup_locations' );
			$city_rates       = $this->parse_rates( $rates );
			$special_rates    = $this->parse_special_rates( $pickup_locations );
			$default_cost     = $this->get_option( 'default_cost' );
			$cost             = $default_cost;
			$original_city    = $destination_city;

			if ( isset( $city_rates[ $normalized_city ] ) ) {
				$cost          = $city_rates[ $normalized_city ]['rate'];
				$original_city = $city_rates[ $normalized_city ]['original'];
			}

			$rate = array(
				'id'       => $this->id,
				'label'    => $this->get_option( 'title' ) . ' a ' . $original_city,
				'cost'     => $cost,
				'calc_tax' => 'per_item',
			);

			$this->add_rate( $rate );

			foreach ( $special_rates as $location => $rate_info ) {
				$pickup_rate = array(
					'id'       => $this->id . '_' . sanitize_title( $location ),
					'label'    => $rate_info['original'],
					'cost'     => $rate_info['rate'],
					'calc_tax' => 'per_item',
				);

				$this->add_rate( $pickup_rate );
			}
		}

		/**
		 * Parse the rates entered in the settings.
		 *
		 * @param string $rates The rates entered in the settings.
		 * @return array Parsed city rates.
		 */
		public function parse_rates( $rates ) {
			$lines      = explode( "\n", $rates );
			$city_rates = array();

			foreach ( $lines as $line ) {
				list( $city, $department, $rate ) = explode( '|', trim( $line ) );
				$normalized_city                  = $this->normalize_city_name( trim( $city ) );
				$department                       = trim( $department );
				$rate                             = trim( $rate );

				$city_rates[ $normalized_city ] = array(
					'original'   => $city,
					'department' => $department,
					'rate'       => $rate,
				);
			}

			return $city_rates;
		}

		/**
		 * Parse the special rates entered in the settings.
		 *
		 * @param string $rates The rates entered in the settings.
		 * @return array Parsed special rates.
		 */
		public function parse_special_rates( $rates ) {
			$lines         = explode( "\n", $rates );
			$special_rates = array();

			foreach ( $lines as $line ) {
				list( $location, $rate ) = explode( '|', trim( $line ) );
				$normalized_location     = $this->normalize_city_name( trim( $location ) );
				$rate                    = trim( $rate );

				$special_rates[ $normalized_location ] = array(
					'original' => $location,
					'rate'     => $rate,
				);
			}

			return $special_rates;
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

		/**
		 * Normalize city name to a canonical form.
		 *
		 * @param string $city The city name to normalize.
		 * @return string The normalized city name.
		 */
		private function normalize_city_name( $city ) {
			if ( class_exists( 'Normalizer' ) ) {
				$city = Normalizer::normalize( $city, Normalizer::FORM_C );
			}
			// Remove accents.
			$city = transliterator_transliterate( 'Any-Latin; Latin-ASCII;', $city );
			return strtolower( $city );
		}
	}
}

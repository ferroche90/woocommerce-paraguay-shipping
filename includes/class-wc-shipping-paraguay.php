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
				'default_rate'     => array(
					'title'       => __( 'Default Rate', 'woocommerce-paraguay-shipping' ),
					'type'        => 'text',
					'description' => __( 'Default shipping rate for cities not listed.', 'woocommerce-paraguay-shipping' ),
					'default'     => '',
				),
				'rates'            => array(
					'title'       => __( 'Rates', 'woocommerce-paraguay-shipping' ),
					'type'        => 'textarea',
					'description' => __( 'Enter rates for different cities in the format: City|Rate per kg. One city per line.', 'woocommerce-paraguay-shipping' ),
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
		 * Calculate shipping cost based on destination city and department.
		 *
		 * @param array $package The package being shipped.
		 */
		public function calculate_shipping( $package = array() ) {
			$destination_city            = $this->remove_accents( $package['destination']['city'] );
			$destination_department_code = $package['destination']['state'];
			$destination_department      = $this->get_state_name( 'PY', $destination_department_code );
			$rates                       = $this->get_option( 'rates' );
			$pickup_locations            = $this->get_option( 'pickup_locations' );
			$city_rates                  = $this->parse_rates( $rates );
			$special_rates               = $this->parse_rates( $pickup_locations, false );
			$default_rate                = $this->get_option( 'default_rate' );
			$cost                        = $default_rate;

			if ( isset( $city_rates[ $destination_city ][ $destination_department ] ) ) {
				$rate_info    = $city_rates[ $destination_city ][ $destination_department ];
				$cost         = $rate_info['rate'];
			}

			$rate = array(
				'id'       => $this->id,
				'label'    => $this->get_option( 'title' ) . ' a ' . $package['destination']['city'],
				'cost'     => $cost,
				'calc_tax' => 'per_item',
			);

			$this->add_rate( $rate );

			foreach ( $special_rates as $location => $rate_cost ) {
				foreach ( $rate_cost as $rate_display => $cost ) {
					$pickup_rate = array(
						'id'       => $this->id . '_' . sanitize_title( $location ),
						'label'    => $location,
						'cost'     => $cost,
						'calc_tax' => 'per_item',
					);

					$this->add_rate( $pickup_rate );
				}
			}
		}

		/**
		 * Get the state name from the state code.
		 *
		 * @param string $country Country code.
		 * @param string $state_code State code.
		 * @return string State name.
		 */
		private function get_state_name( $country, $state_code ) {
			$states = WC()->countries->get_states( $country );
			if ( isset( $states[ $state_code ] ) ) {
				return $this->remove_accents( $states[ $state_code ] );
			}
			return $state_code;
		}

		/**
		 * Parse the rates entered in the settings.
		 *
		 * @param string $rates The rates entered in the settings.
		 * @param bool $normalize Whether to normalize the keys for matching.
		 * @return array Parsed city rates.
		 */
		private function parse_rates( $rates, $normalize = true ) {
			$lines      = explode( "\n", $rates );
			$city_rates = array();

			foreach ( $lines as $line ) {
				list( $city, $department, $rate ) = explode( '|', trim( $line ) );
				$city = trim( $city );
				$department = trim( $department );
				$rate = trim( $rate );

				if ( $normalize ) {
					$normalized_city = $this->remove_accents( $city );
					$normalized_department = $this->remove_accents( $department );
					$city_rates[ $normalized_city ][ $normalized_department ] = array(
						'rate' => $rate,
						'display' => array(
							'city' => $city,
							'department' => $department
						)
					);
				} else {
					$city_rates[ $city ][ $department ] = $rate;
				}
			}

			return $city_rates;
		}

		/**
		 * Remove accents from a string.
		 *
		 * @param string $str The string to normalize.
		 * @return string The normalized string.
		 */
		private function remove_accents( $str ) {
			return strtolower( preg_replace( '/[\x{0300}-\x{036f}]/u', '', normalizer_normalize( $str, Normalizer::FORM_D ) ) );
		}
	}
}

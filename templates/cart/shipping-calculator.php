<?php
/**
 * Shipping Calculator
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/shipping-calculator.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

// Retrieve the instance of the shipping method
$shipping_methods   = WC()->shipping()->get_shipping_methods();
$cities             = array();
$cities_departments = array();

if ( isset( $shipping_methods['paraguay_shipping'] ) ) {
	$paraguay_shipping        = $shipping_methods['paraguay_shipping'];
	$cities                   = $paraguay_shipping->get_cities();
	$cities_departments_rates = $paraguay_shipping->parse_rates( $paraguay_shipping->get_option( 'rates' ) );
}

do_action( 'woocommerce_before_shipping_calculator' ); ?>

<form class="woocommerce-shipping-calculator" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">

	<?php printf( '<a href="#" class="shipping-calculator-button">%s</a>', esc_html( ! empty( $button_text ) ? $button_text : __( 'Calculate shipping', 'woocommerce' ) ) ); ?>

	<section class="shipping-calculator-form" style="display:none;">

		<?php if ( apply_filters( 'woocommerce_shipping_calculator_enable_country', true ) ) : ?>
			<style>
				#calc_shipping_country_field,
				#calc_shipping_country {
					display:none !important;
				}
			</style>
			<p class="form-row form-row-wide" id="calc_shipping_country_field">
				<label for="calc_shipping_country" class="screen-reader-text"><?php esc_html_e( 'Country / region:', 'woocommerce' ); ?></label>
				<select name="calc_shipping_country" id="calc_shipping_country" class="country_to_state country_select" rel="calc_shipping_state">
					<option value="default"><?php esc_html_e( 'Select a country / region&hellip;', 'woocommerce' ); ?></option>
					<?php
					foreach ( WC()->countries->get_shipping_countries() as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '"' . selected( WC()->customer->get_shipping_country(), esc_attr( $key ), false ) . '>' . esc_html( $value ) . '</option>';
					}
					?>
				</select>
			</p>
		<?php endif; ?>

		<?php if ( apply_filters( 'woocommerce_shipping_calculator_enable_state', true ) ) : ?>
			<style>
				#calc_shipping_state_field,
				#calc_shipping_state {
					display:none !important;
				}
			</style>
			<p class="form-row form-row-wide" id="calc_shipping_state_field">
				<?php
				$current_cc = WC()->customer->get_shipping_country();
				$current_r  = WC()->customer->get_shipping_state();
				$states     = WC()->countries->get_states( $current_cc );

				if ( is_array( $states ) && empty( $states ) ) {
					?>
					<input type="hidden" name="calc_shipping_state" id="calc_shipping_state" placeholder="<?php esc_attr_e( 'State / County', 'woocommerce' ); ?>" />
					<?php
				} elseif ( is_array( $states ) ) {
					?>
					<span>
						<label for="calc_shipping_state" class="screen-reader-text"><?php esc_html_e( 'State / County:', 'woocommerce' ); ?></label>
						<select name="calc_shipping_state" class="state_select" id="calc_shipping_state" data-placeholder="<?php esc_attr_e( 'State / County', 'woocommerce' ); ?>">
							<option value=""><?php esc_html_e( 'Select an option&hellip;', 'woocommerce' ); ?></option>
							<?php
							foreach ( $states as $ckey => $cvalue ) {
								echo '<option value="' . esc_attr( $ckey ) . '" ' . selected( $current_r, $ckey, false ) . '>' . esc_html( $cvalue ) . '</option>';
							}
							?>
						</select>
					</span>
					<?php
				} else {
					?>
					<label for="calc_shipping_state" class="screen-reader-text"><?php esc_html_e( 'State / County:', 'woocommerce' ); ?></label>
					<input type="text" class="input-text" value="<?php echo esc_attr( $current_r ); ?>" placeholder="<?php esc_attr_e( 'State / County', 'woocommerce' ); ?>" name="calc_shipping_state" id="calc_shipping_state" />
					<?php
				}
				?>
			</p>
		<?php endif; ?>

		<?php if ( apply_filters( 'woocommerce_shipping_calculator_enable_city', true ) ) : ?>
			<p class="form-row form-row-wide" id="calc_shipping_city_field">
				<label for="calc_shipping_city" class="screen-reader-text"><?php esc_html_e( 'City:', 'woocommerce' ); ?></label>
				<span>
					<select name="calc_shipping_city" class="city_select state_select" id="calc_shipping_city" data-placeholder="<?php esc_attr_e( 'City', 'woocommerce' ); ?>">
						<option value=""><?php esc_html_e( 'Select a city&hellip;', 'woocommerce' ); ?></option>
						<?php
						if ( $cities ) {
							foreach ( $cities as $city => $data ) {
								echo '<option value="' . esc_attr( $city ) . '" data-department="' . esc_attr( $data['department'] ) . '">' . esc_html( $city ) . '</option>';
							}
						}
						?>
					</select>
				</span>
			</p>
		<?php endif; ?>

		<p><button type="submit" name="calc_shipping" value="1" class="button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"><?php esc_html_e( 'Update', 'woocommerce' ); ?></button></p>
		<?php wp_nonce_field( 'woocommerce-shipping-calculator', 'woocommerce-shipping-calculator-nonce' ); ?>
	</section>
</form>

<script type="text/javascript">
	jQuery(document).ready(function($) {
		function validateCitySelection() {
			var selectedCity = $('#calc_shipping_city').val();
			if (!selectedCity) {
				// Add WooCommerce error notice if it doesn't exist
				if (!$('.woocommerce-error.select-city-error').length) {
					$('.woocommerce-notices-wrapper').prepend('<div class="woocommerce-error select-city-error"><?php esc_html_e( "Seleccione una ciudad de envío antes de realizar el pago.", "woocommerce" ); ?></div>');
					$('html, body').animate({ scrollTop: 0 }, 'slow'); // Scroll to the top
				}
				return false;
			}
			// Remove error notice if city is selected
			$('.woocommerce-error.select-city-error').remove();
			return true;
		}

		// Add click event to the "Proceed to checkout" button
		$('a.checkout-button').on('click', function(e) {
			if (!validateCitySelection()) {
				e.preventDefault(); // Prevent the default action of the button
			}
		});

		// Additional event to handle form submission directly
		$('form.cart').on('submit', function(e) {
			if (!validateCitySelection()) {
				e.preventDefault(); // Prevent the form from submitting
			}
		});
	});
</script>

<?php do_action( 'woocommerce_after_shipping_calculator' ); ?>

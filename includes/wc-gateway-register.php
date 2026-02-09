<?php
/**
 * Registers NOWPayments gateway with WooCommerce.
 *
 * @package NowPayments_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'add_nowpayments_to_wc' ) ) {
	/**
	 * Adds NOWPayments gateway to WooCommerce.
	 *
	 * @param array $gateways Existing payment gateways.
	 * @return array Modified gateways list.
	 * @since 1.0
	 * @version 1.0
	 */
	function add_nowpayments_to_wc( $gateways ) {
		$gateways[] = 'NPWC_Gateway';
		return $gateways;
	}
}

add_filter( 'woocommerce_payment_gateways', 'add_nowpayments_to_wc' );

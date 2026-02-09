<?php
/**
 * NOWPayments off-page checkout API helper.
 *
 * @package NowPayments_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds NOWPayments off-page checkout URLs.
 */
class NPEC_API {

	/**
	 * Is Live or Sandbox.
	 *
	 * @var bool
	 * @since 1.0
	 * @version 1.0
	 */
	private $is_live;

	/**
	 * API endpoint base URL.
	 *
	 * @var string
	 * @since 1.0
	 * @version 1.0
	 */
	public $endpoint;

	/**
	 * API key.
	 *
	 * @var string
	 * @since 1.0
	 * @version 1.0
	 */
	private $api_key;

	/**
	 * NPEC_API constructor.
	 *
	 * @param string $api_key API key.
	 * @param bool   $is_live True for live, false for sandbox.
	 * @since 1.0
	 * @version 1.0
	 */
	public function __construct( $api_key, $is_live = true ) {

		$this->is_live = $is_live;
		$this->api_key = $api_key;

		if ( $is_live ) {
			$this->endpoint = 'https://nowpayments.io';
		} else {
			$this->endpoint = 'https://sandbox.nowpayments.io';
		}
	}

	/**
	 * Build URL for off-page checkout.
	 *
	 * @param array $parameters Checkout parameters.
	 * @return string Redirect URL.
	 * @since 1.0
	 * @version 1.0
	 */
	public function off_page_checkout( $parameters = array() ) {

		$parameters['apiKey'] = $this->api_key;
		$encoded              = rawurlencode( wp_json_encode( $parameters ) );
		$redirect_url         = "{$this->endpoint}/payment?data={$encoded}";

		return $redirect_url;
	}
}

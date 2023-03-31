<?php
/**
 * Class API
 *
 * @package NOWPayments For WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class NPEC_API
 */
class NPEC_API {

	/**
	 * Is Live or Sandbox
	 *
	 * @var string $is_live
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	private $is_live;


	/**
	 * Endpoint
	 *
	 * @var string $endpoint
	 * @since 1.0
	 * @version 1.0
	 */
	public $endpoint;


	/**
	 * API key
	 *
	 * @var string $api_key
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	private $api_key;

	/**
	 * NPEC_API constructor.
	 *
	 * @param bool   $is_live checking is live.
	 * @param string $api_key Api key.
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	public function __construct( $is_live = true, $api_key = '' ) {

		$this->is_live = $is_live;
		$this->api_key = $api_key;

		if ( $is_live ) {
			$this->endpoint = 'https://api.nowpayments.io/v1';
			$this->endpoint = 'https://nowpayments.io';
		} else {
			$this->endpoint = 'https://api-sandbox.nowpayments.io/v1';
			$this->endpoint = 'https://sandbox.nowpayments.io';
		}
	}

	/**
	 * Ready the url to process off-page checkout
	 *
	 * @param array $parameters Off page checkout parameters.
	 * @return string
	 * @version 1.0
	 * @since 1.0
	 */
	public function off_page_checkout( $parameters = array() ) {

		$parameters['apiKey'] = $this->api_key;
		$parameters           = rawurlencode( wp_json_encode( $parameters ) );
		$redirect_url         = "{$this->endpoint}/payment?data={$parameters}";

		return $redirect_url;
	}


}

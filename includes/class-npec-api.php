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
	 * Hosted payment page base URL (legacy data redirect).
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
	 * REST API base URL.
	 *
	 * @return string
	 */
	private function get_api_base() {
		return $this->is_live ? 'https://api.nowpayments.io/v1' : 'https://api-sandbox.nowpayments.io/v1';
	}

	/**
	 * Build URL for off-page checkout (invoice API preferred, legacy data URL as fallback).
	 *
	 * @param array $parameters Checkout parameters.
	 * @return string Redirect URL, or empty string on failure.
	 * @since 1.0
	 * @version 1.0
	 */
	public function off_page_checkout( $parameters = array() ) {

		$invoice_url = $this->create_invoice_checkout_url( $parameters );
		if ( $invoice_url ) {
			return $invoice_url;
		}

		$parameters['apiKey'] = $this->api_key;
		$encoded              = rawurlencode( wp_json_encode( $parameters ) );
		return "{$this->endpoint}/payment?data={$encoded}";
	}

	/**
	 * Create a NOWPayments invoice and return its hosted payment URL.
	 *
	 * Uses /v1/invoice (payment/?iid=…) instead of the legacy payment?data= flow, which is
	 * more reliable when customers pick coins such as ETH on the hosted payment page.
	 *
	 * @param array $parameters Checkout parameters from the gateway.
	 * @return string Invoice URL or empty string.
	 */
	private function create_invoice_checkout_url( $parameters ) {
		if ( empty( $this->api_key ) ) {
			return '';
		}

		$price_amount = isset( $parameters['paymentAmount'] ) ? (float) $parameters['paymentAmount'] : 0;
		if ( $price_amount <= 0 ) {
			return '';
		}

		$body = array(
			'price_amount'     => $price_amount,
			'price_currency'   => strtolower( (string) ( $parameters['paymentCurrency'] ?? 'usd' ) ),
			'order_id'         => (string) ( $parameters['orderID'] ?? '' ),
			'ipn_callback_url' => (string) ( $parameters['ipnURL'] ?? '' ),
			'success_url'      => (string) ( $parameters['successURL'] ?? '' ),
			'cancel_url'       => (string) ( $parameters['cancelURL'] ?? '' ),
		);

		if ( ! empty( $parameters['order_description'] ) ) {
			$body['order_description'] = (string) $parameters['order_description'];
		} elseif ( ! empty( $parameters['customerEmail'] ) ) {
			$body['order_description'] = sprintf(
				/* translators: 1: order ID, 2: customer email */
				__( 'WooCommerce order %1$s (%2$s)', 'nowpayments-for-woocommerce' ),
				$body['order_id'],
				$parameters['customerEmail']
			);
		}

		if ( ! empty( $parameters['pay_currency'] ) ) {
			$body['pay_currency'] = strtolower( (string) $parameters['pay_currency'] );
		}

		$body = apply_filters( 'npwc_invoice_parameters', $body, $parameters );

		$response = wp_remote_post(
			$this->get_api_base() . '/invoice',
			array(
				'headers' => array(
					'x-api-key'    => $this->api_key,
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 400 || ! is_array( $data ) || empty( $data['invoice_url'] ) ) {
			return '';
		}

		return esc_url_raw( (string) $data['invoice_url'] );
	}

	/**
	 * Create a payment via POST /v1/payment (returns deposit address; no WalletConnect UI).
	 *
	 * @param array $body Payment payload.
	 * @return array|\WP_Error Decoded API body or error.
	 */
	public function create_payment( array $body ) {
		if ( empty( $this->api_key ) ) {
			return new \WP_Error( 'npwc_no_api', __( 'NOWPayments API key is missing.', 'nowpayments-for-woocommerce' ) );
		}

		$body = apply_filters( 'npwc_create_payment_parameters', $body );

		$response = wp_remote_post(
			$this->get_api_base() . '/payment',
			array(
				'headers' => array(
					'x-api-key'    => $this->api_key,
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 400 || ! is_array( $data ) ) {
			$message = is_array( $data ) && ! empty( $data['message'] )
				? (string) $data['message']
				: __( 'NOWPayments could not create a payment.', 'nowpayments-for-woocommerce' );
			return new \WP_Error( 'npwc_api', $message, array( 'status' => $code ) );
		}

		return $data;
	}
}

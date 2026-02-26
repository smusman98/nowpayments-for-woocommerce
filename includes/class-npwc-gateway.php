<?php
/**
 * NOWPayments WooCommerce gateway.
 *
 * @package NowPayments_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * NOWPayments payment gateway for WooCommerce.
 */
class NPWC_Gateway extends WC_Payment_Gateway {

	/**
	 * NPWC_Gateway constructor.
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	public function __construct() {

		$this->id                 = 'nowpayments';
		$this->title              = $this->get_option( 'title' );
		$this->icon               = apply_filters( 'wcnp_icon', NPWC_PLUGIN_URL . '/assets/images/icon.png' );
		$this->has_fields         = false;
		$this->method_title       = 'NOWPayments';
		$this->description        = $this->get_option( 'description' );
		$this->has_fields         = false;
		$this->method_description = 'Allows customer to checkout with 300+ crypto currencies.';
		$this->init_form_fields();
		$this->init_settings();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_npwc_gateway', array( $this, 'ipn_callback' ) );
	}

	/**
	 * Admin form fields
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'             => array(
				'title'   => 'Enabled/ Disabled',
				'type'    => 'checkbox',
				'label'   => 'Enable NOWPayments',
				'default' => 'no',
			),
			'sandbox'             => array(
				'title'   => 'Enable/ Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable SandBox',
				'default' => 'no',
			),
			'single_product_icon' => array(
				'title'   => 'Enable/ Disable',
				'type'    => 'checkbox',
				'label'   => 'Show Crypto Icons and Individual Pricing on Product Page (Pro)',
				'default' => 'no',
			),
			'products_icons'      => array(
				'title'   => 'Enable/ Disable',
				'type'    => 'checkbox',
				'label'   => 'Show Crypto Icons and Individual Pricing on Shop Page (Pro)',
				'default' => 'no',
			),
			'subscription'        => array(
				'title'   => 'Enable/ Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable/ Disable Subscription with WooCommerce Subscription (Pro)',
				'default' => 'no',
			),
			'title'               => array(
				'title'       => 'Title',
				'type'        => 'text',
				'default'     => 'NOWPayments',
				'desc_tip'    => true,
				'description' => 'Title for NOWPayments',
			),
			'description'         => array(
				'title'       => 'Pay with NOWPayments',
				'type'        => 'textarea',
				'default'     => 'Pay with NOWPayments',
				'desc_tip'    => true,
				'description' => 'Add a new description for NOWPayments Gateway, Customers will se at checkout.',
			),
			'live_api_key'        => array(
				'title'       => 'Live API Key',
				'type'        => 'password',
				'description' => sprintf(
					'Get your API: %s',
					esc_url( 'https://account.nowpayments.io/store-settings' )
				),
			),
			'live_ipn_key'        => array(
				'title'       => 'Live IPN Secret Key',
				'type'        => 'text',
				'description' => sprintf(
					'Get your IPN Secret Key: %s',
					esc_url( 'https://account.nowpayments.io/store-settings' )
				),
			),
			'sandbox_api_key'     => array(
				'title'       => 'SandBox API Key',
				'type'        => 'password',
				'description' => sprintf(
					'Get your API: %s',
					esc_url( 'https://account-sandbox.nowpayments.io/store-settings' )
				),
			),
			'sandbox_ipn_key'     => array(
				'title'       => 'SandBox IPN Secret Key',
				'type'        => 'text',
				'description' => sprintf(
					'Get your IPN Secret Key: %s',
					esc_url( 'https://account-sandbox.nowpayments.io/store-settings' )
				),
			),
			'webhook_url'         => array(
				'title'             => 'Webhook URL',
				'type'              => 'text',
				'default'           => add_query_arg( 'wc-api', 'NPWC_Gateway', home_url( '/' ) ),
				'custom_attributes' => array( 'readonly' => 'readonly' ),
			),
		);
	}

	/**
	 * Process Admin Settings | Validate
	 *
	 * @return bool|void
	 * @since 1.0
	 * @version 1.0
	 */
	public function process_admin_options() {

		parent::process_admin_options();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies nonce for payment gateway settings.
		if ( empty( $_POST['woocommerce_nowpayments_live_api_key'] ) ) {
			WC_Admin_Settings::add_error( 'Error: Live API Key is required.' );
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies nonce for payment gateway settings.
		if ( isset( $_POST['woocommerce_nowpayments_sandbox'] ) && empty( $_POST['woocommerce_nowpayments_sandbox_api_key'] ) ) {
			WC_Admin_Settings::add_error( 'Error: SandBox API Key is required.' );
			return false;
		}
	}

	/**
	 * Process the payment and redirect to NOWPayments.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return array|void Redirect data or void on failure.
	 * @since 1.0
	 * @version 1.0
	 */
	public function process_payment( $order_id ) {

		$order   = wc_get_order( $order_id );
		$is_live = ( ! empty( $this->get_option( 'sandbox' ) ) && 'yes' === $this->get_option( 'sandbox' ) ) ? false : true;
		$api_key = '';

		if ( $is_live ) {
			$api_key = $this->get_option( 'live_api_key' );
		} else {
			$api_key = $this->get_option( 'sandbox_api_key' );
		}

		return $this->off_site_checkout( $is_live, $api_key, $order );
	}

	/**
	 * Off-site checkout: build redirect URL for NOWPayments.
	 *
	 * @param bool     $is_live True for live, false for sandbox.
	 * @param string   $api_key API key.
	 * @param WC_Order $order   Order object.
	 * @return array Result with redirect URL.
	 * @since 1.0
	 * @version 1.0
	 */
	public function off_site_checkout( $is_live, $api_key, $order ) {

		$order_id = $order->id;

		$parameters = array(
			'dataSource'      => 'woocommerce',
			'ipnURL'          => $this->get_option( 'webhook_url' ),
			'paymentCurrency' => $order->get_currency(),
			'successURL'      => $this->get_return_url( $order ),
			'cancelURL'       => esc_url_raw( $order->get_cancel_order_url_raw() ),
			'orderID'         => $order_id,
			'customerName'    => $order->billing_first_name,
			'customerEmail'   => $order->billing_email,
			'paymentAmount'   => number_format( $order->get_total(), 8, '.', '' ),
		);

		$order_items = $order->get_items();
		$items       = array();

		foreach ( $order_items as $item_id => $item ) {
			$items[] = $item->get_data();
		}

		$parameters['products'] = $items;
		$parameters             = apply_filters( 'wcnp_checkout_parameters', $parameters );

		$nowpayments  = new NPEC_API( $api_key, $is_live );
		$redirect_url = $nowpayments->off_page_checkout( $parameters );

		return array(
			'result'   => 'success',
			'redirect' => $redirect_url,
		);
	}

	/**
	 * Webhook Catcher | action_hook callback
	 *
	 * Verifies X-NOWPayments-Sig (HMAC-SHA512) when IPN secret is set to prevent spoofed payment confirmations.
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	public function ipn_callback() {

		$raw     = file_get_contents( 'php://input' );
		$request = json_decode( $raw, true );

		if ( ! is_array( $request ) || ! array_key_exists( 'order_id', $request ) ) {
			status_header( 400 );
			wp_die( 'Invalid Call', 'Invalid Call', array( 'response' => 400 ) );
		}

		$is_sandbox = ( $this->get_option( 'sandbox' ) === 'yes' );
		$ipn_secret = $is_sandbox ? $this->get_option( 'sandbox_ipn_key', '' ) : $this->get_option( 'live_ipn_key', '' );

		if ( '' !== $ipn_secret ) {
			if ( empty( $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ) ) {
				status_header( 401 );
				wp_die( 'Invalid signature', 'Unauthorized', array( 'response' => 401 ) );
			}
			$received   = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ) );
			$sorted     = $this->npwc_sort_array_recursive( $request );
			$calculated = hash_hmac( 'sha512', wp_json_encode( $sorted, JSON_UNESCAPED_SLASHES ), trim( $ipn_secret ) );
			if ( ! hash_equals( $calculated, $received ) ) {
				status_header( 401 );
				wp_die( 'Invalid signature', 'Unauthorized', array( 'response' => 401 ) );
			}
		}

		$order = wc_get_order( $request['order_id'] );
		if ( ! $order || ! ( $order instanceof WC_Order ) ) {
			status_header( 404 );
			wp_die( 'Order not found', 'Not Found', array( 'response' => 404 ) );
		}

		if ( $order->get_payment_method() !== $this->id ) {
			status_header( 400 );
			wp_die( 'Not a NOWPayments order', 'Bad Request', array( 'response' => 400 ) );
		}

		$payment_status = isset( $request['payment_status'] ) ? sanitize_text_field( $request['payment_status'] ) : '';

		// finished - the funds have reached your personal address and the payment is finished.
		if ( 'finished' === $payment_status ) {
			$order->update_status( 'completed', 'NOWPayments finished IPN Call.' );
		}

		// refunded - the funds were refunded back to the user.
		if ( 'refunded' === $payment_status ) {
			$order->update_status( 'refunded', 'NOWPayments refunded IPN Call.' );
		}

		// failed - the payment wasn't completed due to the error of some kind.
		if ( 'failed' === $payment_status ) {
			$order->update_status( 'failed', 'NOWPayments failed IPN Call.' );
		}

		status_header( 200 );
		wp_die( 'OK', 'OK', array( 'response' => 200 ) );
	}

	/**
	 * Recursively sort array by keys (for IPN signature verification).
	 *
	 * @param mixed $data Data.
	 * @return mixed
	 */
	private function npwc_sort_array_recursive( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}
		ksort( $data );
		foreach ( $data as $k => $v ) {
			$data[ $k ] = $this->npwc_sort_array_recursive( $v );
		}
		return $data;
	}
}

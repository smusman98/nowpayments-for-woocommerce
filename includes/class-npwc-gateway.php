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
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_payment_styles' ) );
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
				'label'   => 'Show Crypto Icons and Individual Pricing on Product Page <span class="npwc-pro-badge">PRO</span>',
				'default' => 'no',
				'custom_attributes' => array(
					'disabled' => 'disabled',
				),
			),
			'products_icons'      => array(
				'title'   => 'Enable/ Disable',
				'type'    => 'checkbox',
				'label'   => 'Show Crypto Icons and Individual Pricing on Shop Page <span class="npwc-pro-badge">PRO</span>',
				'default' => 'no',
				'custom_attributes' => array(
					'disabled' => 'disabled',
				),
			),
			'subscription'        => array(
				'title'   => 'Enable/ Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable/ Disable Subscription with WooCommerce Subscription <span class="npwc-pro-badge">PRO</span>',
				'default' => 'no',
				'custom_attributes' => array(
					'disabled' => 'disabled',
				),
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
			'onsite_payment'      => array(
				'title'       => 'On-site payment instructions',
				'type'        => 'checkbox',
				'label'       => __( 'Show crypto deposit address on your store (recommended for sandbox / ETH)', 'nowpayments-for-woocommerce' ),
				'default'     => 'no',
				'description' => __( 'Skips the NOWPayments wallet-connect page that can fail with Web3Modal errors. Set the pay currency field below (defaults to eth if empty).', 'nowpayments-for-woocommerce' ),
			),
			'onsite_pay_currency' => array(
				'title'       => __( 'Pay currency (on-site mode)', 'nowpayments-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Required when on-site mode is enabled. Examples: eth, btc, usdttrc20.', 'nowpayments-for-woocommerce' ),
				'default'     => 'eth',
				'desc_tip'    => true,
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
		if ( empty( $_POST['woocommerce_nowpayments_live_ipn_key'] ) ) {
			WC_Admin_Settings::add_error( 'Error: Live IPN Secret Key is required.' );
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies nonce for payment gateway settings.
		if ( isset( $_POST['woocommerce_nowpayments_sandbox'] ) && empty( $_POST['woocommerce_nowpayments_sandbox_api_key'] ) ) {
			WC_Admin_Settings::add_error( 'Error: SandBox API Key is required.' );
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies nonce for payment gateway settings.
		if ( isset( $_POST['woocommerce_nowpayments_sandbox'] ) && empty( $_POST['woocommerce_nowpayments_sandbox_ipn_key'] ) ) {
			WC_Admin_Settings::add_error( 'Error: SandBox IPN Secret Key is required when sandbox mode is enabled.' );
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies nonce for payment gateway settings.
		if ( isset( $_POST['woocommerce_nowpayments_onsite_payment'] ) && empty( $_POST['woocommerce_nowpayments_onsite_pay_currency'] ) ) {
			$_POST['woocommerce_nowpayments_onsite_pay_currency'] = 'eth';
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

		if ( $this->is_onsite_payment_enabled() ) {
			$pay_currency = $this->get_onsite_pay_currency( $order );
			if ( '' === $pay_currency ) {
				wc_add_notice(
					__( 'NOWPayments on-site mode is enabled but no pay currency is set. Enter eth (or another ticker) in payment settings.', 'nowpayments-for-woocommerce' ),
					'error'
				);
				return array( 'result' => 'failure' );
			}

			$onsite = $this->process_onsite_checkout( $is_live, $api_key, $order, $pay_currency );
			if ( ! empty( $onsite['result'] ) && 'success' === $onsite['result'] ) {
				return $onsite;
			}

			return array( 'result' => 'failure' );
		}

		return $this->off_site_checkout( $is_live, $api_key, $order );
	}

	/**
	 * Whether on-site payment mode is enabled in gateway settings.
	 *
	 * @return bool
	 */
	private function is_onsite_payment_enabled() {
		$enabled = ( 'yes' === $this->get_option( 'onsite_payment' ) );
		return (bool) apply_filters( 'npwc_use_onsite_payment', $enabled, null );
	}

	/**
	 * Pay currency for on-site API payments (defaults to eth when setting is empty).
	 *
	 * @param WC_Order $order Order.
	 * @return string Lowercase ticker or empty string.
	 */
	private function get_onsite_pay_currency( $order ) {
		$currency = strtolower( trim( (string) $this->get_option( 'onsite_pay_currency', '' ) ) );
		$currency = strtolower( trim( (string) apply_filters( 'npwc_onsite_pay_currency', $currency, $order ) ) );

		if ( '' === $currency && $this->is_onsite_payment_enabled() ) {
			$currency = 'eth';
		}

		return $currency;
	}

	/**
	 * Create payment via API and redirect to the order pay page with deposit details.
	 *
	 * @param bool     $is_live      True for live.
	 * @param string   $api_key      API key.
	 * @param WC_Order $order        Order.
	 * @param string   $pay_currency Crypto ticker.
	 * @return array
	 */
	private function process_onsite_checkout( $is_live, $api_key, $order, $pay_currency ) {

		$api     = new NPEC_API( $api_key, $is_live );
		$payment = $api->create_payment(
			array(
				'price_amount'      => (float) $order->get_total(),
				'price_currency'    => strtolower( $order->get_currency() ),
				'pay_currency'      => $pay_currency,
				'ipn_callback_url'  => $this->get_option( 'webhook_url' ),
				'order_id'          => (string) $order->get_id(),
				'order_description' => sprintf(
					/* translators: %s: WooCommerce order number */
					__( 'WooCommerce order #%s', 'nowpayments-for-woocommerce' ),
					$order->get_order_number()
				),
			)
		);

		if ( is_wp_error( $payment ) ) {
			wc_add_notice( $payment->get_error_message(), 'error' );
			return array( 'result' => 'failure' );
		}

		$pay_address = $this->extract_pay_address_from_payment( $payment );

		if ( '' === $pay_address ) {
			wc_add_notice(
				__( 'NOWPayments did not return a deposit address. Check your API key, currency pair, and sandbox account.', 'nowpayments-for-woocommerce' ),
				'error'
			);
			return array( 'result' => 'failure' );
		}

		$order->update_meta_data( '_npwc_payment_id', isset( $payment['payment_id'] ) ? (string) $payment['payment_id'] : '' );
		$order->update_meta_data( '_npwc_pay_address', $pay_address );
		$order->update_meta_data( '_npwc_pay_amount', isset( $payment['pay_amount'] ) ? (string) $payment['pay_amount'] : '' );
		$order->update_meta_data( '_npwc_pay_currency', isset( $payment['pay_currency'] ) ? (string) $payment['pay_currency'] : $pay_currency );
		$order->save();

		if ( ! $order->has_status( array( 'pending', 'on-hold', 'failed', 'cancelled' ) ) ) {
			$order->update_status( 'on-hold', __( 'Awaiting NOWPayments crypto transfer.', 'nowpayments-for-woocommerce' ) );
		}

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	/**
	 * Resolve deposit address from a NOWPayments payment API response.
	 *
	 * @param array $payment API response body.
	 * @return string
	 */
	private function extract_pay_address_from_payment( $payment ) {
		foreach ( array( 'pay_address', 'payment_address', 'deposit_address' ) as $key ) {
			if ( ! empty( $payment[ $key ] ) && is_string( $payment[ $key ] ) ) {
				return $payment[ $key ];
			}
		}
		return '';
	}

	/**
	 * Order pay page: show deposit address and amount (no WalletConnect).
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function receipt_page( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || $order->get_payment_method() !== $this->id ) {
			return;
		}

		$pay_address  = $order->get_meta( '_npwc_pay_address' );
		$pay_amount   = $order->get_meta( '_npwc_pay_amount' );
		$pay_currency = strtoupper( (string) $order->get_meta( '_npwc_pay_currency' ) );

		if ( '' === $pay_address ) {
			return;
		}

		$qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode( $pay_address );
		?>
		<div class="npwc-payment-instructions">
			<h2><?php esc_html_e( 'Complete your crypto payment', 'nowpayments-for-woocommerce' ); ?></h2>
			<p class="npwc-payment-note">
				<?php esc_html_e( 'Send the exact amount below to the deposit address. Do not use “Connect wallet” on NOWPayments — pay manually from your wallet app.', 'nowpayments-for-woocommerce' ); ?>
			</p>
			<?php if ( '' !== $pay_amount && '' !== $pay_currency ) : ?>
				<div class="npwc-payment-row">
					<strong><?php esc_html_e( 'Amount to pay', 'nowpayments-for-woocommerce' ); ?></strong>
					<div class="npwc-payment-value npwc-payment-amount">
						<?php echo esc_html( $pay_amount . ' ' . $pay_currency ); ?>
					</div>
				</div>
			<?php endif; ?>
			<div class="npwc-payment-row">
				<strong><?php esc_html_e( 'Deposit address', 'nowpayments-for-woocommerce' ); ?></strong>
				<div class="npwc-payment-value"><?php echo esc_html( $pay_address ); ?></div>
			</div>
			<div class="npwc-payment-qr">
				<img src="<?php echo esc_url( $qr_url ); ?>" width="200" height="200" alt="<?php esc_attr_e( 'Payment QR code', 'nowpayments-for-woocommerce' ); ?>" />
			</div>
			<p class="npwc-payment-note">
				<?php esc_html_e( 'Your order will update automatically after the payment is confirmed on the blockchain.', 'nowpayments-for-woocommerce' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Enqueue styles on the order pay page for NOWPayments orders.
	 *
	 * @return void
	 */
	public function enqueue_payment_styles() {
		if ( ! is_checkout_pay_page() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only order id for styling.
		$order_id = isset( $_GET['key'] ) ? wc_get_order_id_by_order_key( wc_clean( wp_unslash( $_GET['key'] ) ) ) : 0;
		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || $order->get_payment_method() !== $this->id || ! $order->get_meta( '_npwc_pay_address' ) ) {
			return;
		}

		wp_enqueue_style(
			'npwc-payment',
			NPWC_PLUGIN_URL . 'assets/css/npwc-payment.css',
			array(),
			NPWC_VERSION
		);
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

		$order_id = $order->get_id();

		$parameters = array(
			'dataSource'        => 'woocommerce',
			'ipnURL'            => $this->get_option( 'webhook_url' ),
			'paymentCurrency'   => $order->get_currency(),
			'successURL'        => $this->get_return_url( $order ),
			'cancelURL'         => esc_url_raw( $order->get_cancel_order_url_raw() ),
			'orderID'           => (string) $order_id,
			'order_description' => sprintf(
				/* translators: %s: WooCommerce order number */
				__( 'WooCommerce order #%s', 'nowpayments-for-woocommerce' ),
				$order->get_order_number()
			),
			'customerName'      => $order->get_billing_first_name(),
			'customerEmail'     => $order->get_billing_email(),
			'paymentAmount'     => number_format( (float) $order->get_total(), 8, '.', '' ),
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

		if ( empty( $redirect_url ) ) {
			wc_add_notice(
				__( 'Unable to start NOWPayments checkout. Please verify your API key and try again.', 'nowpayments-for-woocommerce' ),
				'error'
			);
			return array(
				'result' => 'failure',
			);
		}

		if ( ! $order->has_status( array( 'pending', 'failed', 'cancelled' ) ) ) {
			$order->update_status( 'pending', __( 'Customer redirected to NOWPayments.', 'nowpayments-for-woocommerce' ) );
		}

		return array(
			'result'   => 'success',
			'redirect' => $redirect_url,
		);
	}

	/**
	 * Webhook Catcher | action_hook callback
	 *
	 * Verifies X-NOWPayments-Sig (HMAC-SHA512) on every request; rejects unauthenticated IPN callbacks.
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
		$ipn_secret = trim( $is_sandbox ? $this->get_option( 'sandbox_ipn_key', '' ) : $this->get_option( 'live_ipn_key', '' ) );

		if ( '' === $ipn_secret ) {
			status_header( 503 );
			wp_die( 'IPN secret not configured', 'Service Unavailable', array( 'response' => 503 ) );
		}

		if ( empty( $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ) ) {
			status_header( 401 );
			wp_die( 'Invalid signature', 'Unauthorized', array( 'response' => 401 ) );
		}

		$received = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ) ) );
		$sorted   = $this->npwc_sort_array_recursive( $request );

		// Accept common, equivalent payload serialization variants used by senders/tools.
		$payload_variants = array_filter(
			array(
				$raw,
				wp_json_encode( $sorted ),
				wp_json_encode( $sorted, JSON_UNESCAPED_SLASHES ),
				wp_json_encode( $sorted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
				json_encode( $sorted ),
			)
		);

		$is_valid_signature = false;
		foreach ( $payload_variants as $payload ) {
			$calculated = strtolower( hash_hmac( 'sha512', $payload, $ipn_secret ) );
			if ( hash_equals( $calculated, $received ) ) {
				$is_valid_signature = true;
				break;
			}
		}

		if ( ! $is_valid_signature ) {
			status_header( 401 );
			wp_die( 'Invalid signature', 'Unauthorized', array( 'response' => 401 ) );
		}

		$wc_order_id = 0;
		if ( isset( $request['order_id'] ) ) {
			$wc_order_id = absint( $request['order_id'] );
		} elseif ( isset( $request['orderID'] ) ) {
			$wc_order_id = absint( $request['orderID'] );
		}

		$order = $wc_order_id ? wc_get_order( $wc_order_id ) : false;
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

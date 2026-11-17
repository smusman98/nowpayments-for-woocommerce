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
		$this->icon               = apply_filters( 'wcnp_icon', NPWC_PLUGIN_URL . 'assets/images/icon.png' );
		$this->has_fields         = false;
		$this->method_title       = __( 'NOWPayments', 'nowpayments-for-woocommerce' );
		$this->description        = $this->get_option( 'description' );
		$this->method_description = __( 'Allows customer to checkout with 300+ crypto currencies.', 'nowpayments-for-woocommerce' );
		$this->init_form_fields();
		$this->init_settings();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_npwc_gateway', array( $this, 'ipn_callback' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_payment_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_styles' ) );
	}

	/**
	 * Payment gateway icon for legacy checkout.
	 *
	 * @return string
	 */
	public function get_icon() {
		if ( empty( $this->icon ) ) {
			return '';
		}

		$icon_url = $this->icon;
		if ( class_exists( 'WC_HTTPS' ) ) {
			$icon_url = WC_HTTPS::force_https_url( $icon_url );
		}

		return apply_filters(
			'woocommerce_gateway_icon',
			'<img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $this->get_title() ) . '" class="npwc-gateway-icon" />',
			$this->id
		);
	}

	/**
	 * Enqueue checkout styles for legacy payment method icon sizing.
	 *
	 * @return void
	 */
	public function enqueue_checkout_styles() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		wp_enqueue_style(
			'npwc-checkout',
			NPWC_PLUGIN_URL . 'assets/css/npwc-checkout.css',
			array(),
			NPWC_VERSION
		);
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
				'title'   => __( 'Enable/Disable', 'nowpayments-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable NOWPayments', 'nowpayments-for-woocommerce' ),
				'default' => 'no',
			),
			'title'               => array(
				'title'       => __( 'Title', 'nowpayments-for-woocommerce' ),
				'type'        => 'text',
				'default'     => 'NOWPayments',
				'desc_tip'    => true,
				'description' => __( 'This controls the title which the user sees during checkout.', 'nowpayments-for-woocommerce' ),
			),
			'description'         => array(
				'title'       => __( 'Description', 'nowpayments-for-woocommerce' ),
				'type'        => 'textarea',
				'default'     => 'Pay with NOWPayments',
				'desc_tip'    => true,
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'nowpayments-for-woocommerce' ),
			),
			'api_mode'            => array(
				'title'       => __( 'Payment mode', 'nowpayments-for-woocommerce' ),
				'type'        => 'radio',
				'description' => __( 'Choose Live for real payments or Sandbox for testing. Only one environment is used at a time.', 'nowpayments-for-woocommerce' ),
				'default'     => 'live',
				'options'     => array(
					'live'    => __( 'Live', 'nowpayments-for-woocommerce' ),
					'sandbox' => __( 'SandBox', 'nowpayments-for-woocommerce' ),
				),
			),
			'sandbox_api_key'     => array(
				'title'       => 'SandBox API Key',
				'type'        => 'password',
				'class'       => 'npwc-settings-env npwc-settings-env--sandbox',
				'placeholder' => __( 'Paste your SandBox API Key', 'nowpayments-for-woocommerce' ),
				'description' => sprintf(
					/* translators: 1: create account link, 2: store settings link */
					__( 'Get your API: %1$s then %2$s', 'nowpayments-for-woocommerce' ),
					'<a href="' . esc_url( 'https://account-sandbox.nowpayments.io/create-account' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Create Account', 'nowpayments-for-woocommerce' ) . '</a>',
					'<a href="' . esc_url( 'https://account-sandbox.nowpayments.io/store-settings' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Store Settings', 'nowpayments-for-woocommerce' ) . '</a>'
				),
			),
			'sandbox_ipn_key'     => array(
				'title'       => 'SandBox IPN Secret Key',
				'type'        => 'text',
				'class'       => 'npwc-settings-env npwc-settings-env--sandbox',
				'placeholder' => __( 'Paste your SandBox IPN Secret Key', 'nowpayments-for-woocommerce' ),
				'description' => sprintf(
					/* translators: 1: create account link, 2: store settings link */
					__( 'Get your IPN Secret Key: %1$s then %2$s', 'nowpayments-for-woocommerce' ),
					'<a href="' . esc_url( 'https://account-sandbox.nowpayments.io/create-account' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Create Account', 'nowpayments-for-woocommerce' ) . '</a>',
					'<a href="' . esc_url( 'https://account-sandbox.nowpayments.io/store-settings' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Store Settings', 'nowpayments-for-woocommerce' ) . '</a>'
				),
			),
			'live_api_key'        => array(
				'title'       => 'Live API Key',
				'type'        => 'password',
				'class'       => 'npwc-settings-env npwc-settings-env--live',
				'placeholder' => __( 'Paste your Live API Key', 'nowpayments-for-woocommerce' ),
				'description' => sprintf(
					/* translators: 1: create account link, 2: store settings link */
					__( 'Get your API: %1$s then %2$s', 'nowpayments-for-woocommerce' ),
					'<a href="' . esc_url( 'https://account.nowpayments.io/create-account?link_id=3857577492' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Create Account', 'nowpayments-for-woocommerce' ) . '</a>',
					'<a href="' . esc_url( 'https://account.nowpayments.io/store-settings' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Store Settings', 'nowpayments-for-woocommerce' ) . '</a>'
				),
			),
			'live_ipn_key'        => array(
				'title'       => 'Live IPN Secret Key',
				'type'        => 'text',
				'class'       => 'npwc-settings-env npwc-settings-env--live',
				'placeholder' => __( 'Paste your Live IPN Secret Key', 'nowpayments-for-woocommerce' ),
				'description' => sprintf(
					/* translators: 1: create account link, 2: store settings link */
					__( 'Get your IPN Secret Key: %1$s then %2$s', 'nowpayments-for-woocommerce' ),
					'<a href="' . esc_url( 'https://account.nowpayments.io/create-account?link_id=3857577492' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Create Account', 'nowpayments-for-woocommerce' ) . '</a>',
					'<a href="' . esc_url( 'https://account.nowpayments.io/store-settings' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Store Settings', 'nowpayments-for-woocommerce' ) . '</a>'
				),
			),
			'webhook_url'         => array(
				'title'             => 'Webhook URL',
				'type'              => 'webhook_url',
				'default'           => add_query_arg( 'wc-api', 'NPWC_Gateway', home_url( '/' ) ),
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
			'single_product_icon' => array(
				'title'   => __( 'Product page', 'nowpayments-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Show Crypto Icons and Individual Pricing on Product Page', 'nowpayments-for-woocommerce' ) . ' <span class="npwc-pro-badge">PRO</span>',
				'default' => 'no',
				'custom_attributes' => array(
					'disabled' => 'disabled',
				),
			),
			'products_icons'      => array(
				'title'   => __( 'Shop page', 'nowpayments-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Show Crypto Icons and Individual Pricing on Shop Page', 'nowpayments-for-woocommerce' ) . ' <span class="npwc-pro-badge">PRO</span>',
				'default' => 'no',
				'custom_attributes' => array(
					'disabled' => 'disabled',
				),
			),
			'subscription'        => array(
				'title'   => __( 'Subscriptions', 'nowpayments-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Subscriptions with WooCommerce Subscriptions', 'nowpayments-for-woocommerce' ) . ' <span class="npwc-pro-badge">PRO</span>',
				'default' => 'no',
				'custom_attributes' => array(
					'disabled' => 'disabled',
				),
			),
		);
	}

	/**
	 * Active API environment: live or sandbox (migrates legacy sandbox checkbox).
	 *
	 * @return string 'live'|'sandbox'
	 */
	public function get_api_mode() {
		$mode = (string) $this->get_option( 'api_mode', '' );
		if ( 'live' === $mode || 'sandbox' === $mode ) {
			return $mode;
		}

		return ( 'yes' === $this->get_option( 'sandbox', 'no' ) ) ? 'sandbox' : 'live';
	}

	/**
	 * Whether API + IPN keys for the active environment (live or sandbox) are saved.
	 *
	 * @return bool
	 */
	public function has_api_credentials() {
		$prefix = $this->is_sandbox_mode() ? 'sandbox' : 'live';

		return '' !== trim( (string) $this->get_option( $prefix . '_api_key', '' ) )
			&& '' !== trim( (string) $this->get_option( $prefix . '_ipn_key', '' ) );
	}

	/**
	 * Only offer NOWPayments at checkout once API credentials are configured.
	 *
	 * The gateway can be enabled from WooCommerce → Settings → Payments before the keys
	 * are entered (Enable first, then Manage); until then customers must not see it.
	 *
	 * @return bool
	 */
	public function is_available() {
		return parent::is_available() && $this->has_api_credentials();
	}

	/**
	 * Whether the gateway uses the NOWPayments sandbox API.
	 *
	 * @return bool
	 */
	public function is_sandbox_mode() {
		return 'sandbox' === $this->get_api_mode();
	}

	/**
	 * Radio field markup (WC_Settings_API has no built-in radio; matches WooCommerce admin settings UI).
	 *
	 * @param string $key  Field key.
	 * @param array  $data Field definition.
	 * @return string
	 */
	public function generate_radio_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
			'options'           => array(),
		);

		$data  = wp_parse_args( $data, $defaults );
		$value = $this->get_api_mode();

		ob_start();
		?>
		<tr valign="top" class="npwc-settings-radio-inline">
			<th scope="row" class="titledesc">
				<span class="wc-settings-radio-title">
					<span id="<?php echo esc_attr( $field_key ); ?>-title"><?php echo wp_kses_post( $data['title'] ); ?></span>
					<?php echo $this->get_tooltip_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
			</th>
			<td class="forminp forminp-radio">
				<fieldset aria-labelledby="<?php echo esc_attr( $field_key ); ?>-title">
					<ul>
						<?php foreach ( (array) $data['options'] as $option_key => $option_label ) : ?>
							<li>
								<label for="<?php echo esc_attr( $field_key . '_' . $option_key ); ?>">
									<input
										type="radio"
										name="<?php echo esc_attr( $field_key ); ?>"
										id="<?php echo esc_attr( $field_key . '_' . $option_key ); ?>"
										value="<?php echo esc_attr( $option_key ); ?>"
										class="<?php echo esc_attr( $data['class'] ); ?>"
										style="<?php echo esc_attr( $data['css'] ); ?>"
										<?php checked( $value, (string) $option_key ); ?>
										<?php disabled( $data['disabled'], true ); ?>
										<?php echo $this->get_custom_attribute_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									/>
									<?php echo esc_html( $option_label ); ?>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php echo $this->get_description_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</fieldset>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Webhook URL with copy control.
	 *
	 * @param string $key  Field key.
	 * @param array  $data Field definition.
	 * @return string
	 */
	public function generate_webhook_url_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
			'default'           => '',
		);

		$data  = wp_parse_args( $data, $defaults );
		$value = (string) $this->get_option( $key, $data['default'] );
		if ( '' === $value ) {
			$value = (string) $data['default'];
		}

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
			</th>
			<td class="forminp">
				<div class="npwc-webhook-url-field">
					<input
						class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>"
						type="text"
						name="<?php echo esc_attr( $field_key ); ?>"
						id="<?php echo esc_attr( $field_key ); ?>"
						style="<?php echo esc_attr( $data['css'] ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						readonly="readonly"
					/>
					<button
						type="button"
						class="button npwc-copy-webhook-url"
						aria-label="<?php esc_attr_e( 'Copy webhook URL', 'nowpayments-for-woocommerce' ); ?>"
						title="<?php esc_attr_e( 'Copy', 'nowpayments-for-woocommerce' ); ?>"
						data-copied-label="<?php esc_attr_e( 'Copied!', 'nowpayments-for-woocommerce' ); ?>"
					>
						<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
					</button>
				</div>
				<?php echo $this->get_description_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Sanitize payment mode field.
	 *
	 * @param string $key   Field key.
	 * @param mixed  $value Submitted value.
	 * @return string
	 */
	public function validate_api_mode_field( $key, $value ) {
		$value = is_string( $value ) ? sanitize_text_field( $value ) : '';
		if ( ! in_array( $value, array( 'live', 'sandbox' ), true ) ) {
			return 'live';
		}
		return $value;
	}

	/**
	 * Read a setting from POST, or fall back to the saved option only when the field was not submitted.
	 *
	 * A submitted empty value means the merchant cleared the field; it must not fall back to the
	 * saved value, otherwise clearing a required key passes validation and the key is saved empty.
	 *
	 * @param string $key Setting key without prefix.
	 * @return string
	 */
	private function get_posted_or_saved_setting( $key ) {
		$post_key = 'woocommerce_nowpayments_' . $key;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies nonce for payment gateway settings.
		if ( isset( $_POST[ $post_key ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies nonce for payment gateway settings.
			return trim( (string) wc_clean( wp_unslash( $_POST[ $post_key ] ) ) );
		}

		return trim( (string) $this->get_option( $key, '' ) );
	}

	/**
	 * Resolve API mode from the current save request or saved settings.
	 *
	 * @return string 'live'|'sandbox'
	 */
	private function get_api_mode_for_save() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies nonce for payment gateway settings.
		if ( isset( $_POST['woocommerce_nowpayments_api_mode'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies nonce for payment gateway settings.
			$mode = sanitize_text_field( wp_unslash( $_POST['woocommerce_nowpayments_api_mode'] ) );
			if ( in_array( $mode, array( 'live', 'sandbox' ), true ) ) {
				return $mode;
			}
		}

		return $this->get_api_mode();
	}

	/**
	 * Process Admin Settings | Validate
	 *
	 * @return bool|void
	 * @since 1.0
	 * @version 1.0
	 */
	public function process_admin_options() {

		$api_mode = $this->get_api_mode_for_save();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies nonce for payment gateway settings.
		$is_enabling = ! empty( $_POST['woocommerce_nowpayments_enabled'] );

		// Credentials are only required to turn the gateway on; it can always be disabled.
		if ( $is_enabling ) {
			$required = 'sandbox' === $api_mode
				? array(
					'sandbox_api_key' => __( 'Error: SandBox API Key is required.', 'nowpayments-for-woocommerce' ),
					'sandbox_ipn_key' => __( 'Error: SandBox IPN Secret Key is required.', 'nowpayments-for-woocommerce' ),
				)
				: array(
					'live_api_key' => __( 'Error: Live API Key is required.', 'nowpayments-for-woocommerce' ),
					'live_ipn_key' => __( 'Error: Live IPN Secret Key is required.', 'nowpayments-for-woocommerce' ),
				);

			foreach ( $required as $setting_key => $message ) {
				if ( '' === $this->get_posted_or_saved_setting( $setting_key ) ) {
					WC_Admin_Settings::add_error( $message );
					return false;
				}
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies nonce for payment gateway settings.
		if ( isset( $_POST['woocommerce_nowpayments_onsite_payment'] ) && empty( $_POST['woocommerce_nowpayments_onsite_pay_currency'] ) ) {
			$_POST['woocommerce_nowpayments_onsite_pay_currency'] = 'eth';
		}

		parent::process_admin_options();

		$this->update_option( 'sandbox', 'sandbox' === $this->get_api_mode() ? 'yes' : 'no' );
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
		$is_live = ! $this->is_sandbox_mode();
		$api_key = $is_live ? $this->get_option( 'live_api_key' ) : $this->get_option( 'sandbox_api_key' );

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
	 * On finished payments, price_amount and price_currency must match the order total before completion.
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

		$ipn_secret = trim( $this->is_sandbox_mode() ? $this->get_option( 'sandbox_ipn_key', '' ) : $this->get_option( 'live_ipn_key', '' ) );

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
			if ( ! $this->npwc_ipn_payment_amount_valid( $request, $order ) ) {
				$ipn_amount   = isset( $request['price_amount'] ) ? (string) $request['price_amount'] : __( 'unknown', 'nowpayments-for-woocommerce' );
				$ipn_currency = isset( $request['price_currency'] ) ? (string) $request['price_currency'] : __( 'unknown', 'nowpayments-for-woocommerce' );

				$order->add_order_note(
					sprintf(
						/* translators: 1: IPN price amount, 2: IPN price currency, 3: order total, 4: order currency */
						__( 'NOWPayments IPN rejected: invoiced amount (%1$s %2$s) does not match order total (%3$s %4$s). Order left on hold.', 'nowpayments-for-woocommerce' ),
						$ipn_amount,
						strtoupper( $ipn_currency ),
						wc_format_decimal( $order->get_total(), wc_get_price_decimals() ),
						strtoupper( $order->get_currency() )
					)
				);
				$order->update_status( 'on-hold', __( 'NOWPayments IPN amount mismatch.', 'nowpayments-for-woocommerce' ) );

				status_header( 200 );
				wp_die( 'Amount mismatch', 'OK', array( 'response' => 200 ) );
			}

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
	 * Verify IPN price_amount and price_currency match the WooCommerce order.
	 *
	 * @param array    $request IPN payload.
	 * @param WC_Order $order   Order.
	 * @return bool
	 */
	private function npwc_ipn_payment_amount_valid( $request, $order ) {
		if ( ! isset( $request['price_amount'], $request['price_currency'] ) ) {
			return false;
		}

		$ipn_amount     = (float) $request['price_amount'];
		$ipn_currency   = strtolower( sanitize_text_field( (string) $request['price_currency'] ) );
		$order_amount   = (float) $order->get_total();
		$order_currency = strtolower( $order->get_currency() );

		if ( $ipn_currency !== $order_currency ) {
			return false;
		}

		/**
		 * Filter the tolerance used when comparing IPN price_amount to the order total.
		 *
		 * @param float    $tolerance Maximum allowed absolute difference.
		 * @param array    $request   IPN payload.
		 * @param WC_Order $order     Order.
		 */
		$tolerance = (float) apply_filters( 'npwc_ipn_amount_tolerance', 0.01, $request, $order );

		return abs( $ipn_amount - $order_amount ) <= $tolerance;
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

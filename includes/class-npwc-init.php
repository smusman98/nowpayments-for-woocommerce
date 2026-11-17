<?php
/**
 * NOWPayments for WooCommerce bootstrap and admin helpers.
 *
 * @package NowPayments_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Initializes NOWPayments hooks and admin helpers.
 */
class NPWC_Init {

	/**
	 * Singleton instance.
	 *
	 * @var NPWC_Init
	 * @since 1.0
	 * @version 1.0
	 */
	private static $instance;

	/**
	 * Single ton
	 *
	 * @return NPWC_Init
	 * @since 1.0
	 * @version 1.0
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * NPWC_Init constructor.
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	public function __construct() {

		$this->validate();
	}

	/**
	 * Meets requirements
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	public function validate() {

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$this->init();
		} else {
			add_action( 'admin_notices', array( $this, 'missing_wc' ) );
		}
	}

	/**
	 * Shows Notice
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	public function missing_wc() {

		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'In order to use NOWPayments for WooCommerce, make sure WooCommerce is installed and active.', 'nowpayments-for-woocommerce' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Finally initialize the Plugin
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	private function init() {

		add_action( 'woocommerce_blocks_loaded', array( $this, 'checkout_block_support' ) );

		$this->includes();
		$this->hooks();
	}

	/**
	 * Includes files
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	public function includes() {

		require 'class-npwc-gateway.php';
		require 'wc-gateway-register.php';
		require 'class-npec-api.php';
	}

	/**
	 * Action, Filter Hooks
	 *
	 * @since 1.0.1
	 * @version 1.0
	 */
	public function hooks() {

		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 5 );
		add_filter( 'plugin_action_links_' . plugin_basename( NPWC_PLUGIN_FILE ), array( $this, 'plugin_action_links' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'register_dashboard_menu' ) );
		add_action( 'admin_footer', array( $this, 'print_get_pro_menu_new_tab_script' ), 20 );
		add_filter( 'allowed_redirect_hosts', array( $this, 'npwc_allowed_external_redirect_hosts' ) );
		add_action( 'in_admin_header', array( $this, 'suppress_foreign_admin_notices_on_dashboard' ), 1000 );
		add_filter( 'admin_body_class', array( $this, 'dashboard_body_class' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	public function admin_enqueue_scripts() {
		wp_register_style(
			'npwc-poppins',
			'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google Fonts URL must not get a ver query arg.
		);

		// Global: Pro badge + highlighted "Get Pro" menu item + Pro modal styles.
		wp_enqueue_style(
			'npwc-admin',
			NPWC_PLUGIN_URL . 'assets/css/admin.css',
			array( 'npwc-poppins' ),
			NPWC_VERSION
		);

		// Settings UI script is only needed on WooCommerce → Settings → Payments.
		if ( $this->is_checkout_settings_tab() ) {
			wp_enqueue_script(
				'npwc-custom-scripts',
				NPWC_PLUGIN_URL . 'assets/js/scripts.js',
				array( 'jquery' ),
				NPWC_VERSION,
				true
			);

			wp_localize_script(
				'npwc-custom-scripts',
				'npwc',
				array(
					'images' => NPWC_PLUGIN_URL . 'assets/images/',
				)
			);
		}

		if ( ! $this->is_dashboard_page() ) {
			return;
		}

		$asset_path = NPWC_PLUGIN_DIR_PATH . 'assets/blocks/admin/dashboard.asset.php';
		if ( ! file_exists( $asset_path ) ) {
			return;
		}

		$asset = require $asset_path;

		wp_enqueue_script(
			'npwc-dashboard',
			NPWC_PLUGIN_URL . 'assets/blocks/admin/dashboard.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		if ( file_exists( NPWC_PLUGIN_DIR_PATH . 'assets/blocks/admin/dashboard.css' ) ) {
			wp_enqueue_style(
				'npwc-dashboard',
				NPWC_PLUGIN_URL . 'assets/blocks/admin/dashboard.css',
				array( 'npwc-poppins' ),
				$asset['version']
			);
			wp_add_inline_style(
				'npwc-dashboard',
				'body.npwc-dashboard-admin-page #wpbody-content>.notice,body.npwc-dashboard-admin-page #wpbody-content>.updated,body.npwc-dashboard-admin-page #wpbody-content>.error,body.npwc-dashboard-admin-page #wpbody-content>.update-nag,body.npwc-dashboard-admin-page #wpbody-content>.woocommerce-message,body.npwc-dashboard-admin-page #wpbody-content>.woocommerce-error,body.npwc-dashboard-admin-page #wpbody-content .wrap>.notice,body.npwc-dashboard-admin-page #wpbody-content .wrap>.updated,body.npwc-dashboard-admin-page #wpbody-content .wrap>.error{display:none!important;}'
			);
		}

		wp_localize_script(
			'npwc-dashboard',
			'npwcDashboardData',
			$this->get_dashboard_data()
		);
	}

	/**
	 * Register NOWPayments dashboard menu.
	 *
	 * @return void
	 */
	public function register_dashboard_menu() {
		if ( post_type_exists( 'nowpayment' ) ) {
			add_submenu_page(
				'edit.php?post_type=nowpayment',
				__( 'NOWPayments Dashboard', 'nowpayments-for-woocommerce' ),
				__( 'Dashboard', 'nowpayments-for-woocommerce' ),
				'manage_woocommerce',
				'npwc-dashboard',
				array( $this, 'render_dashboard_page' )
			);
			$this->maybe_add_payment_settings_submenu( 'edit.php?post_type=nowpayment' );
			$this->maybe_add_get_pro_submenu( 'edit.php?post_type=nowpayment' );
			return;
		}

		add_menu_page(
			__( 'NOWPayments Dashboard', 'nowpayments-for-woocommerce' ),
			__( 'NOWPayment', 'nowpayments-for-woocommerce' ),
			'manage_woocommerce',
			'npwc-dashboard-root',
			array( $this, 'render_dashboard_page' ),
			'dashicons-chart-area',
			56
		);

		add_submenu_page(
			'npwc-dashboard-root',
			__( 'Dashboard', 'nowpayments-for-woocommerce' ),
			__( 'Dashboard', 'nowpayments-for-woocommerce' ),
			'manage_woocommerce',
			'npwc-dashboard',
			array( $this, 'render_dashboard_page' )
		);

		$this->maybe_add_payment_settings_submenu( 'npwc-dashboard-root' );
		$this->maybe_add_get_pro_submenu( 'npwc-dashboard-root' );
		remove_submenu_page( 'npwc-dashboard-root', 'npwc-dashboard-root' );
	}

	/**
	 * Add WooCommerce payment settings submenu (opens in a new browser tab).
	 *
	 * @param string $parent_slug Parent admin menu slug.
	 * @return void
	 */
	private function maybe_add_payment_settings_submenu( $parent_slug ) {
		add_submenu_page(
			$parent_slug,
			__( 'Payment Setting', 'nowpayments-for-woocommerce' ),
			__( 'Payment Setting', 'nowpayments-for-woocommerce' ),
			'manage_woocommerce',
			'npwc-payment-settings',
			array( $this, 'render_payment_settings_redirect_page' )
		);
	}

	/**
	 * Add highlighted "Get Pro Now" submenu on free builds when Premium is not active.
	 *
	 * @param string $parent_slug Parent admin menu slug.
	 * @return void
	 */
	private function maybe_add_get_pro_submenu( $parent_slug ) {
		if ( ! $this->should_show_get_pro_submenu() ) {
			return;
		}

		add_submenu_page(
			$parent_slug,
			__( 'Upgrade NOWPayments', 'nowpayments-for-woocommerce' ),
			__( '⭐ Get Pro Now', 'nowpayments-for-woocommerce' ),
			'manage_woocommerce',
			'npwc-get-pro',
			array( $this, 'render_get_pro_redirect_page' )
		);
	}

	/**
	 * Whether to show the promotional Get Pro submenu (hide when Premium plugin is active).
	 *
	 * @return bool
	 */
	private function should_show_get_pro_submenu() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return ! is_plugin_active( 'nowpayments-for-woocommerce-pro/nowpayments-for-woocommerce-pro.php' );
	}

	/**
	 * Filtered upgrade landing URL before escaping (used for redirects + allowed hosts).
	 *
	 * @return string
	 */
	private function get_upgrade_url_unescaped() {
		return apply_filters(
			'npwc_upgrade_url',
			'https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=npwc-dashboard&utm_medium=upgrade-cta&utm_campaign=upgrade-to-pro'
		);
	}

	/**
	 * Allow wp_safe_redirect() to send users to the external upgrade / pricing host.
	 *
	 * @param string[] $hosts Allowed redirect hostnames.
	 * @return string[]
	 */
	public function npwc_allowed_external_redirect_hosts( $hosts ) {
		$url  = esc_url_raw( $this->get_upgrade_url_unescaped() );
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( is_string( $host ) && '' !== $host ) {
			$hosts[] = $host;
		}

		foreach ( array( 'nowpayments.io', 'sandbox.nowpayments.io' ) as $np_host ) {
			if ( ! in_array( $np_host, $hosts, true ) ) {
				$hosts[] = $np_host;
			}
		}

		return $hosts;
	}

	/**
	 * Upgrade landing URL (same `npwc_upgrade_url` filter + default as dashboard CTA).
	 *
	 * @return string
	 */
	private function get_upgrade_url() {
		return esc_url_raw( $this->get_upgrade_url_unescaped() );
	}

	/**
	 * Redirect admin "Get Pro" submenu to the upgrade landing page.
	 *
	 * @return void
	 */
	public function render_get_pro_redirect_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'nowpayments-for-woocommerce' ) );
		}

		wp_safe_redirect( $this->get_upgrade_url() );
		exit;
	}

	/**
	 * Redirect admin "Payment Setting" submenu to WooCommerce NOWPayments gateway settings.
	 *
	 * @return void
	 */
	public function render_payment_settings_redirect_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'nowpayments-for-woocommerce' ) );
		}

		wp_safe_redirect( $this->get_payment_settings_url() );
		exit;
	}

	/**
	 * Make selected NOWPayments admin submenu links open in a new browser tab.
	 *
	 * @return void
	 */
	public function print_get_pro_menu_new_tab_script() {
		$new_tab_pages = array( 'npwc-payment-settings' );
		if ( $this->should_show_get_pro_submenu() ) {
			$new_tab_pages[] = 'npwc-get-pro';
		}
		?>
		<script>
		(function () {
			var pages = <?php echo wp_json_encode( $new_tab_pages ); ?>;
			pages.forEach( function ( page ) {
				document.querySelectorAll( 'a[href*="page=' + page + '"]' ).forEach( function ( el ) {
					el.setAttribute( 'target', '_blank' );
					el.setAttribute( 'rel', 'noopener noreferrer' );
				} );
			} );
		})();
		</script>
		<?php
	}

	/**
	 * Render React dashboard root.
	 *
	 * @return void
	 */
	public function render_dashboard_page() {
		echo '<div id="npwc-dashboard-root"></div>';
	}

	/**
	 * Add body class for full-page dashboard styles.
	 *
	 * @param string $classes Existing body classes.
	 * @return string
	 */
	public function dashboard_body_class( $classes ) {
		if ( $this->is_dashboard_page() ) {
			$classes .= ' npwc-dashboard-admin-page';
		}

		if ( $this->is_nowpayments_gateway_settings_page() ) {
			$mode     = $this->get_nowpayments_gateway_api_mode();
			$classes .= ' npwc-nowpayments-settings-page npwc-active-mode-' . sanitize_html_class( $mode );
		}

		return $classes;
	}

	/**
	 * Whether the current admin screen is WooCommerce NOWPayments gateway settings.
	 *
	 * @return bool
	 */
	private function is_nowpayments_gateway_settings_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check.
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check.
		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';

		if ( 'wc-settings' === $page && 'checkout' === $tab && 'nowpayments' === $section ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check.
		$path = isset( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : '';

		return 'wc-settings' === $page && false !== stripos( $path, 'nowpayments' );
	}

	/**
	 * Active NOWPayments API mode for settings UI (live or sandbox).
	 *
	 * @return string
	 */
	private function get_nowpayments_gateway_api_mode() {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return 'live';
		}

		$gateways = WC()->payment_gateways()->payment_gateways();
		if ( empty( $gateways['nowpayments'] ) || ! is_a( $gateways['nowpayments'], 'NPWC_Gateway' ) ) {
			return 'live';
		}

		return $gateways['nowpayments']->get_api_mode();
	}

	/**
	 * Remove core and third-party admin notices on the dashboard screen only.
	 *
	 * @return void
	 */
	public function suppress_foreign_admin_notices_on_dashboard() {
		if ( ! $this->is_dashboard_page() ) {
			return;
		}

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'network_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
	}

	/**
	 * Whether the current screen is WooCommerce → Settings → Payments (any section).
	 *
	 * @return bool
	 */
	private function is_checkout_settings_tab() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check.
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

		return 'wc-settings' === $page && ( 'checkout' === $tab || $this->is_nowpayments_gateway_settings_page() );
	}

	/**
	 * Check whether current admin page is dashboard.
	 *
	 * @return bool
	 */
	private function is_dashboard_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only admin page check.
		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		return in_array( $current_page, array( 'npwc-dashboard', 'npwc-dashboard-root' ), true );
	}

	/**
	 * Dummy data for free-plugin Pro teaser sections only (donut, coins, table, revenue card).
	 *
	 * @param string $store_currency WooCommerce currency code.
	 * @return array{statusBreakdown:array,topCoins:array,recentTransactions:array,demoCryptoRevenueTotal:int}
	 */
	private function get_dashboard_demo_dataset( $store_currency ) {
		$currency = is_string( $store_currency ) && $store_currency ? $store_currency : 'USD';

		$coins = array(
			array(
				'coin'  => 'BTC',
				'count' => 612,
				'total' => 184320,
			),
			array(
				'coin'  => 'ETH',
				'count' => 481,
				'total' => 121440,
			),
			array(
				'coin'  => 'USDT',
				'count' => 392,
				'total' => 88210,
			),
			array(
				'coin'  => 'SOL',
				'count' => 218,
				'total' => 41210,
			),
			array(
				'coin'  => 'USDC',
				'count' => 174,
				'total' => 33880,
			),
			array(
				'coin'  => 'LTC',
				'count' => 95,
				'total' => 14820,
			),
		);

		$orders_url = admin_url( 'edit.php?post_type=shop_order' );
		$tx         = array(
			array(
				'orderId'       => 10428,
				'customerEmail' => 'alice@northwind.io',
				'amount'        => '248.00 ' . $currency,
				'coin'          => 'BTC',
				'status'        => __( 'Finished', 'nowpayments-for-woocommerce' ),
				'statusClass'   => 'finished',
				'subscription'  => '-',
				'timeAgo'       => __( '2m ago', 'nowpayments-for-woocommerce' ),
				'viewUrl'       => esc_url_raw( $orders_url ),
			),
			array(
				'orderId'       => 10419,
				'customerEmail' => 'dev@example.com',
				'amount'        => '1,120.50 ' . $currency,
				'coin'          => 'ETH',
				'status'        => __( 'Processing', 'nowpayments-for-woocommerce' ),
				'statusClass'   => 'processing',
				'subscription'  => __( 'Yes', 'nowpayments-for-woocommerce' ),
				'timeAgo'       => __( '14m ago', 'nowpayments-for-woocommerce' ),
				'viewUrl'       => esc_url_raw( $orders_url ),
			),
			array(
				'orderId'       => 10402,
				'customerEmail' => 'sam@acme.net',
				'amount'        => '89.99 ' . $currency,
				'coin'          => 'USDT',
				'status'        => __( 'Waiting', 'nowpayments-for-woocommerce' ),
				'statusClass'   => 'waiting',
				'subscription'  => '-',
				'timeAgo'       => __( '1h ago', 'nowpayments-for-woocommerce' ),
				'viewUrl'       => esc_url_raw( $orders_url ),
			),
			array(
				'orderId'       => 10388,
				'customerEmail' => 'guest@checkout.io',
				'amount'        => '512.00 ' . $currency,
				'coin'          => 'SOL',
				'status'        => __( 'Failed', 'nowpayments-for-woocommerce' ),
				'statusClass'   => 'failed',
				'subscription'  => '-',
				'timeAgo'       => __( '3h ago', 'nowpayments-for-woocommerce' ),
				'viewUrl'       => esc_url_raw( $orders_url ),
			),
			array(
				'orderId'       => 10371,
				'customerEmail' => 'team@codepress.co',
				'amount'        => '2,400.00 ' . $currency,
				'coin'          => 'BTC',
				'status'        => __( 'Finished', 'nowpayments-for-woocommerce' ),
				'statusClass'   => 'finished',
				'subscription'  => '-',
				'timeAgo'       => __( '5h ago', 'nowpayments-for-woocommerce' ),
				'viewUrl'       => esc_url_raw( $orders_url ),
			),
			array(
				'orderId'       => 10355,
				'customerEmail' => 'ops@merchant.shop',
				'amount'        => '76.25 ' . $currency,
				'coin'          => 'LTC',
				'status'        => __( 'Refunded', 'nowpayments-for-woocommerce' ),
				'statusClass'   => 'refunded',
				'subscription'  => '-',
				'timeAgo'       => __( '1d ago', 'nowpayments-for-woocommerce' ),
				'viewUrl'       => esc_url_raw( $orders_url ),
			),
			array(
				'orderId'       => 10340,
				'customerEmail' => 'buyer@studio.io',
				'amount'        => '425.00 ' . $currency,
				'coin'          => 'USDC',
				'status'        => __( 'Waiting', 'nowpayments-for-woocommerce' ),
				'statusClass'   => 'waiting',
				'subscription'  => '-',
				'timeAgo'       => __( '2d ago', 'nowpayments-for-woocommerce' ),
				'viewUrl'       => esc_url_raw( $orders_url ),
			),
		);

		return array(
			'statusBreakdown'        => array(
				'finished' => 1284,
				'waiting'  => 312,
				'failed'   => 84,
				'refunded' => 41,
			),
			'topCoins'               => $coins,
			'recentTransactions'     => $tx,
			'demoCryptoRevenueTotal' => 482910,
		);
	}

	/**
	 * Merge Pro-teaser dummy slices (free) onto real WC dashboard payload.
	 *
	 * @param array $payload Real metrics + series from orders.
	 * @return array
	 */
	private function merge_dashboard_demo_teasers( array $payload ) {
		$currency = isset( $payload['storeCurrency'] ) && is_string( $payload['storeCurrency'] ) ? $payload['storeCurrency'] : 'USD';
		$demo     = $this->get_dashboard_demo_dataset( $currency );

		$payload['statusBreakdown']        = $demo['statusBreakdown'];
		$payload['topCoins']               = $demo['topCoins'];
		$payload['recentTransactions']     = $demo['recentTransactions'];
		$payload['demoCryptoRevenueTotal'] = $demo['demoCryptoRevenueTotal'];

		return $payload;
	}

	/**
	 * Last 8 calendar days (including today) as chart buckets, using the site timezone.
	 *
	 * Keys must match order-day keys from wp_date( 'Y-m-d', $timestamp ) so revenue aggregates correctly.
	 *
	 * @return array<string, array{label: string, revenue: float}>
	 */
	private function get_dashboard_revenue_series_buckets() {
		$tz         = wp_timezone();
		$today      = new \DateTimeImmutable( 'today', $tz );
		$series_map = array();

		for ( $i = 7; $i >= 0; $i-- ) {
			$day                = $today->modify( '-' . $i . ' days' );
			$date_key           = $day->format( 'Y-m-d' );
			$series_map[ $date_key ] = array(
				'label'   => $day->format( 'M d' ),
				'revenue' => 0.0,
			);
		}

		return $series_map;
	}

	/**
	 * Build dashboard payload from WooCommerce orders; dummy data only for Pro-locked widgets.
	 *
	 * @return array
	 */
	private function get_dashboard_data() {
		$orders = function_exists( 'wc_get_orders' )
			? wc_get_orders(
				array(
					'limit'          => 200,
					'type'           => 'shop_order',
					'orderby'        => 'date',
					'order'          => 'DESC',
					'payment_method' => 'nowpayments',
					'status'         => array( 'pending', 'on-hold', 'processing', 'completed', 'failed', 'refunded' ),
					'return'         => 'objects',
				)
			)
			: array();

		$successful_payments = 0;
		$failed_payments     = 0;
		$refunded_payments   = 0;
		$series_map          = $this->get_dashboard_revenue_series_buckets();

		foreach ( $orders as $order ) {
			$status = $order->get_status();
			if ( in_array( $status, array( 'completed', 'processing' ), true ) ) {
				++$successful_payments;

				$order_date = $order->get_date_created();
				if ( $order_date ) {
					$date_key = wp_date( 'Y-m-d', $order_date->getTimestamp() );
					if ( isset( $series_map[ $date_key ] ) ) {
						$series_map[ $date_key ]['revenue'] += (float) $order->get_total();
					}
				}
			} elseif ( 'failed' === $status ) {
				++$failed_payments;
			} elseif ( 'refunded' === $status ) {
				++$refunded_payments;
			}
		}

		$payload = array(
			'defaultTheme'  => $this->get_saved_dashboard_theme(),
			'iconUrl'       => NPWC_PLUGIN_URL . 'assets/images/icon.png',
			'storeCurrency' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD',
			'upgradeUrl'    => $this->get_upgrade_url(),
			'restNonce'     => wp_create_nonce( 'wp_rest' ),
			'metrics'       => array(
				'totalOrders'        => count( $orders ),
				'successfulPayments' => $successful_payments,
				'failedPayments'     => $failed_payments,
				'refundedPayments'   => $refunded_payments,
			),
			'revenueSeries' => array_values( $series_map ),
		);

		// Pro-locked widgets (status donut, coins, transactions, revenue card) show demo data.
		$payload = $this->merge_dashboard_demo_teasers( $payload );

		return array_merge( $payload, $this->get_dashboard_api_payload() );
	}

	/**
	 * API configuration flags for the React dashboard.
	 *
	 * @return array{apiConfigured:int,settingsUrl:string,docsUrl:string,themeRestUrl:string} apiConfigured 1 = key present, 0 = missing (int for wp_localize_script; bool false becomes "" in JS).
	 */
	private function get_dashboard_api_payload() {
		$docs_url = apply_filters(
			'npwc_dashboard_docs_url',
			'https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=npwc-dashboard&utm_medium=api-banner&utm_campaign=documentation#setup'
		);

		return array(
			'apiConfigured' => '' !== $this->get_active_api_key() ? 1 : 0,
			'settingsUrl'   => $this->get_payment_settings_url(),
			'docsUrl'       => esc_url_raw( $docs_url ),
			'themeRestUrl'  => esc_url_raw( rest_url( 'npwc/v1/dashboard-theme' ) ),
		);
	}

	/**
	 * WooCommerce checkout settings URL for the NOWPayments gateway section.
	 *
	 * @return string
	 */
	private function get_payment_settings_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=nowpayments&from=WCADMIN_PAYMENT_SETTINGS' );
	}

	/**
	 * Saved dashboard theme for the current admin (user meta).
	 *
	 * @return string 'light'|'dark'
	 */
	private function get_saved_dashboard_theme() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return 'light';
		}
		$saved = get_user_meta( $user_id, 'npwc_dashboard_theme', true );
		if ( '' === $saved ) {
			return 'light';
		}
		return ( 'dark' === $saved ) ? 'dark' : 'light';
	}

	/**
	 * Register REST routes for dashboard tools.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'npwc/v1',
			'/dashboard-theme',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_save_dashboard_theme' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_woocommerce' );
				},
				'args'                => array(
					'theme' => array(
						'required'          => true,
						'type'              => 'string',
						'enum'              => array( 'light', 'dark' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Persist dashboard light/dark preference for the current user.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_save_dashboard_theme( \WP_REST_Request $request ) {
		$theme = $request->get_param( 'theme' );
		if ( ! is_string( $theme ) || ! in_array( $theme, array( 'light', 'dark' ), true ) ) {
			return new \WP_Error(
				'npwc_invalid_theme',
				__( 'Theme must be light or dark.', 'nowpayments-for-woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new \WP_Error(
				'npwc_no_user',
				__( 'Not authenticated.', 'nowpayments-for-woocommerce' ),
				array( 'status' => 401 )
			);
		}

		update_user_meta( $user_id, 'npwc_dashboard_theme', $theme );

		return rest_ensure_response(
			array(
				'success' => true,
				'theme'   => $theme,
			)
		);
	}

	/**
	 * API key for the active NOWPayments environment (live or sandbox).
	 *
	 * @return string Empty string when not configured.
	 */
	private function get_active_api_key() {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return '';
		}

		$gateways = WC()->payment_gateways()->payment_gateways();
		if ( empty( $gateways['nowpayments'] ) || ! is_a( $gateways['nowpayments'], 'NPWC_Gateway' ) ) {
			return '';
		}

		$gateway = $gateways['nowpayments'];
		$key     = $gateway->is_sandbox_mode() ? $gateway->get_option( 'sandbox_api_key' ) : $gateway->get_option( 'live_api_key' );

		return is_string( $key ) ? trim( $key ) : '';
	}


	/**
	 * Filter Callback
	 *
	 * @param array  $plugin_meta An array of the plugin's metadata.
	 * @param string $plugin_file Path to the plugin file.
	 * @param array  $plugin_data Plugin data from plugin headers.
	 * @param string $status      Status of the plugin (unused; required by filter).
	 * @return array
	 * @since 1.0.1
	 * @version 1.0
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {

		unset( $status ); // Filter signature; not used.
		if ( strpos( $plugin_file, basename( NPWC_PLUGIN_FILE ) ) !== false ) {

			$plugin_meta[] = sprintf(
				'<a href="%s" style="color: blue; font-weight: bold; font-family: Poppins, sans-serif;" target="_blank">%s</a>',
				esc_url( 'https://nowpayments.coderpress.co/shop/' ),
				esc_html__( 'Demo PRO 🚀', 'nowpayments-for-woocommerce' )
			);

		}

		return $plugin_meta;
	}

	/**
	 * Adds action link under plugin name in plugins list.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {

		if ( current_user_can( 'manage_woocommerce' ) ) {
			$settings_link = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $this->get_payment_settings_url() ),
				esc_html__( 'Settings', 'nowpayments-for-woocommerce' )
			);
			array_unshift( $links, $settings_link );
		}

		$links[] = sprintf(
			'<a href="%s" style="color: green; font-weight: bold; font-family: Poppins, sans-serif;" target="_blank">%s</a>',
			esc_url( 'https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=plugin-npwc&utm_medium=plugins-page&utm_campaign=upgrade-to-pro' ),
			esc_html__( 'Upgrade to Pro', 'nowpayments-for-woocommerce' )
		);

		return $links;
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	public function checkout_block_support() {

		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {

			require_once 'class-nowpaymentsgatewayblock.php';

			add_action( 'woocommerce_blocks_payment_method_type_registration', array( $this, 'register_checkout_block' ) );

		}
	}

	/**
	 * Register NOWPayments payment method type with Blocks.
	 *
	 * @param \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry Registry instance.
	 * @since 1.0
	 * @version 1.0
	 */
	public function register_checkout_block( $payment_method_registry ) {

		$payment_method_registry->register( new NowPaymentsGatewayBlock() );
	}
}

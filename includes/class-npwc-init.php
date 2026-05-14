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

		$is_hidden_notice = get_option( 'npwc_hide_save_cart_notice' );
		$is_hidden_notice = true;

		if ( ! $is_hidden_notice ) {

			add_action( 'admin_notices', array( $this, 'save_cart_notice' ) );
			add_action( 'admin_post_npwc_hide_notice', array( $this, 'save_cart_hide_notice' ) );

		}

		if ( ! class_exists( 'YooAnalytics' ) ) {

			add_action( 'woocommerce_product_options_pricing', array( $this, 'add_yooanalytics_banner' ) );

		}
	}

	/**
	 * Save Cart Promotional Notice | Action Callback
	 *
	 * @since 1.1.1
	 * @version 1.0
	 */
	public function save_cart_notice() {

		?>
		<div class="notice notice-success is-dismissible npwc-sc-notice" style="border-left-color: #03a0c7; padding: 15px 0;">
			<div style="display: flex; align-items: center;">
				<div>
					<img src="https://i0.wp.com/coderpress.co/wp-content/uploads/2023/12/New-Project.jpg?w=125&ssl=1" />
				</div>
				<div style="margin-left: 25px;">
					<h1 style="font-size: 20px;">Transform "Abundant Carts🛒" into "Successful Sales💹".</h1>
					<p>
						Say Good Bye🙋‍♂️! to your Abundant Carts, Sometimes users remove product from cart🛒, the reason could be unaffordability, this is the right time to secure the sale by offering a discount🚀.
					</p>
					<h4>
						Grab Your 30% OFF By Using Coupon: "NPSPECIAL"
					</h4>
					<a href="https://coderpress.co/products/save-cart-for-woocommerce/?utm_source=npwc&utm_medium=notice&utm_campaign=1&utm_id=savecart" target="_blank" class="button button-primary" style="background-color: #ffd814; border-color: #ffd814; color: #0F1111; font-weight: 400;">Get Started</a>
					<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=npwc_hide_notice' ) ); ?>" style="vertical-align: -webkit-baseline-middle; margin-left: 20px;">Not interested</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Hide Notice | Action Callback
	 *
	 * @since 1.1.1
	 * @version 1.0
	 */
	public function save_cart_hide_notice() {

		update_option( 'npwc_hide_save_cart_notice', 1 );

		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	public function admin_enqueue_scripts() {
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

		wp_enqueue_style(
			'npwc-admin',
			NPWC_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			NPWC_VERSION
		);

		if ( ! $this->is_dashboard_page() ) {
			return;
		}

		$asset_path = NPWC_PLUGIN_DIR_PATH . 'assets/blocks/admin/dashboard.asset.php';
		if ( ! file_exists( $asset_path ) ) {
			return;
		}

		$asset = require $asset_path;

		wp_enqueue_style(
			'npwc-dashboard-font',
			'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap',
			array(),
			NPWC_VERSION
		);

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
				array(),
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

		$this->maybe_add_get_pro_submenu( 'npwc-dashboard-root' );
		remove_submenu_page( 'npwc-dashboard-root', 'npwc-dashboard-root' );
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
	 * Make the "Get Pro Now" admin submenu open in a new browser tab.
	 *
	 * @return void
	 */
	public function print_get_pro_menu_new_tab_script() {
		if ( ! $this->should_show_get_pro_submenu() ) {
			return;
		}
		?>
		<script>
		(function () {
			document.querySelectorAll( 'a[href*="page=npwc-get-pro"]' ).forEach( function ( el ) {
				el.setAttribute( 'target', '_blank' );
				el.setAttribute( 'rel', 'noopener noreferrer' );
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
		return $classes;
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
				'logo'  => $this->coin_logo_url( 'btc' ),
			),
			array(
				'coin'  => 'ETH',
				'count' => 481,
				'total' => 121440,
				'logo'  => $this->coin_logo_url( 'eth' ),
			),
			array(
				'coin'  => 'USDT',
				'count' => 392,
				'total' => 88210,
				'logo'  => $this->coin_logo_url( 'usdt' ),
			),
			array(
				'coin'  => 'SOL',
				'count' => 218,
				'total' => 41210,
				'logo'  => $this->coin_logo_url( 'sol' ),
			),
			array(
				'coin'  => 'USDC',
				'count' => 174,
				'total' => 33880,
				'logo'  => $this->coin_logo_url( 'usdc' ),
			),
			array(
				'coin'  => 'LTC',
				'count' => 95,
				'total' => 14820,
				'logo'  => $this->coin_logo_url( 'ltc' ),
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
		$api_payload = $this->get_dashboard_api_payload();

		if ( ! function_exists( 'wc_get_orders' ) ) {
			$store_currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD';
			$payload          = array(
				'defaultTheme'       => $this->get_saved_dashboard_theme(),
				'iconUrl'            => NPWC_PLUGIN_URL . 'assets/images/icon.png',
				'storeCurrency'      => $store_currency,
				'isPro'              => false,
				'upgradeUrl'         => $this->get_upgrade_url(),
				'restUrl'            => esc_url_raw( rest_url( 'npwc/v1/estimate' ) ),
				'restNonce'          => wp_create_nonce( 'wp_rest' ),
				'metrics'            => array(
					'totalOrders'        => 0,
					'successfulPayments' => 0,
					'failedPayments'     => 0,
					'refundedPayments'   => 0,
				),
				'statusBreakdown'    => array(
					'finished' => 0,
					'failed'   => 0,
					'waiting'  => 0,
					'refunded' => 0,
				),
				'revenueSeries'      => array(),
				'topCoins'           => array(),
				'recentTransactions' => array(),
			);
			$payload = $this->merge_dashboard_demo_teasers( $payload );

			return array_merge( $payload, $api_payload );
		}

		$orders = wc_get_orders(
			array(
				'limit'          => 200,
				'type'           => 'shop_order',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'payment_method' => 'nowpayments',
				'status'         => array( 'pending', 'on-hold', 'processing', 'completed', 'failed', 'refunded' ),
				'return'         => 'objects',
			)
		);

		$store_currency      = get_woocommerce_currency();
		$total_orders        = count( $orders );
		$successful_payments = 0;
		$failed_payments     = 0;
		$refunded_payments   = 0;
		$waiting_payments    = 0;
		$series_map = $this->get_dashboard_revenue_series_buckets();

		foreach ( $orders as $order ) {
			$status = $order->get_status();
			if ( in_array( $status, array( 'completed', 'processing' ), true ) ) {
				++$successful_payments;
			} elseif ( 'failed' === $status ) {
				++$failed_payments;
			} elseif ( 'refunded' === $status ) {
				++$refunded_payments;
			} else {
				++$waiting_payments;
			}

			$order_date = $order->get_date_created();
			if ( $order_date && in_array( $status, array( 'completed', 'processing' ), true ) ) {
				$date_key = wp_date( 'Y-m-d', $order_date->getTimestamp() );
				if ( isset( $series_map[ $date_key ] ) ) {
					$series_map[ $date_key ]['revenue'] += (float) $order->get_total();
				}
			}
		}

		$payload = array(
			'defaultTheme'       => $this->get_saved_dashboard_theme(),
			'iconUrl'            => NPWC_PLUGIN_URL . 'assets/images/icon.png',
			'storeCurrency'      => $store_currency,
			'isPro'              => false,
			'upgradeUrl'         => $this->get_upgrade_url(),
			'restUrl'            => esc_url_raw( rest_url( 'npwc/v1/estimate' ) ),
			'restNonce'          => wp_create_nonce( 'wp_rest' ),
			'metrics'            => array(
				'totalOrders'        => $total_orders,
				'successfulPayments' => $successful_payments,
				'failedPayments'     => $failed_payments,
				'refundedPayments'   => $refunded_payments,
			),
			'statusBreakdown'    => array(
				'finished' => $successful_payments,
				'failed'   => $failed_payments,
				'waiting'  => $waiting_payments,
				'refunded' => $refunded_payments,
			),
			'revenueSeries'      => array_values( $series_map ),
			'topCoins'           => array(),
			'recentTransactions' => array(),
		);

		$payload = $this->merge_dashboard_demo_teasers( $payload );

		return array_merge( $payload, $api_payload );
	}

	/**
	 * API configuration flags for the React dashboard.
	 *
	 * @return array{apiConfigured:int,settingsUrl:string,docsUrl:string} apiConfigured 1 = key present, 0 = missing (int for wp_localize_script; bool false becomes "" in JS).
	 */
	private function get_dashboard_api_payload() {
		$creds = $this->get_gateway_api_credentials();

		$docs_url = apply_filters(
			'npwc_dashboard_docs_url',
			'https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=npwc-dashboard&utm_medium=api-banner&utm_campaign=documentation#setup'
		);

		return array(
			'apiConfigured' => ! empty( $creds['api_key'] ) ? 1 : 0,
			'settingsUrl'   => admin_url( 'admin.php?page=wc-settings&tab=checkout&section=nowpayments&from=WCADMIN_PAYMENT_SETTINGS' ),
			'docsUrl'       => esc_url_raw( $docs_url ),
			'themeRestUrl'  => esc_url_raw( rest_url( 'npwc/v1/dashboard-theme' ) ),
		);
	}

	/**
	 * Saved dashboard theme for the current admin (user meta).
	 *
	 * @return string 'light'|'dark'
	 */
	private function get_saved_dashboard_theme() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return 'dark';
		}
		$saved = get_user_meta( $user_id, 'npwc_dashboard_theme', true );
		return ( 'light' === $saved ) ? 'light' : 'dark';
	}

	/**
	 * Register REST routes for dashboard tools.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'npwc/v1',
			'/estimate',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_estimate' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_woocommerce' );
				},
				'args'                => array(
					'amount'        => array(
						'required' => true,
					),
					'currency_from' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'currency_to'   => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

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
	 * Proxy NOWPayments estimate endpoint (uses gateway API key).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_estimate( \WP_REST_Request $request ) {
		$creds = $this->get_gateway_api_credentials();
		if ( empty( $creds['api_key'] ) ) {
			return new \WP_Error(
				'npwc_no_api',
				__( 'Add your NOWPayments API key in WooCommerce → Settings → Payments → NOWPayments.', 'nowpayments-for-woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$base = ! empty( $creds['sandbox'] ) ? 'https://api-sandbox.nowpayments.io/v1' : 'https://api.nowpayments.io/v1';
		$url  = $base . '/estimate?' . http_build_query(
			array(
				'amount'        => $request->get_param( 'amount' ),
				'currency_from' => strtolower( (string) $request->get_param( 'currency_from' ) ),
				'currency_to'   => strtolower( (string) $request->get_param( 'currency_to' ) ),
			),
			'',
			'&',
			PHP_QUERY_RFC3986
		);

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'x-api-key' => $creds['api_key'],
				),
				'timeout' => 25,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'npwc_http', $response->get_error_message(), array( 'status' => 502 ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 400 ) {
			$message = is_array( $body ) && ! empty( $body['message'] ) ? (string) $body['message'] : __( 'NOWPayments estimate request failed.', 'nowpayments-for-woocommerce' );
			return new \WP_Error( 'npwc_api', $message, array( 'status' => $code ) );
		}

		return rest_ensure_response( is_array( $body ) ? $body : array() );
	}

	/**
	 * Read NOWPayments gateway API key and mode.
	 *
	 * @return array{api_key:string,sandbox:bool}
	 */
	private function get_gateway_api_credentials() {
		$out = array(
			'api_key' => '',
			'sandbox' => false,
		);

		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return $out;
		}

		$gateways = WC()->payment_gateways()->payment_gateways();
		if ( empty( $gateways['nowpayments'] ) || ! is_a( $gateways['nowpayments'], 'WC_Payment_Gateway', false ) ) {
			return $out;
		}

		/** @var \WC_Payment_Gateway $gateway */
		$gateway          = $gateways['nowpayments'];
		$out['sandbox']   = ( 'yes' === $gateway->get_option( 'sandbox' ) );
		$key              = $out['sandbox'] ? $gateway->get_option( 'sandbox_api_key' ) : $gateway->get_option( 'live_api_key' );
		$out['api_key']   = is_string( $key ) ? trim( $key ) : '';

		return $out;
	}

	/**
	 * Map WooCommerce status to dashboard class.
	 *
	 * @param string $status Order status.
	 * @return string
	 */
	private function status_to_class( $status ) {
		if ( in_array( $status, array( 'completed', 'processing' ), true ) ) {
			return 'finished';
		}
		if ( 'failed' === $status ) {
			return 'failed';
		}
		if ( 'refunded' === $status ) {
			return 'refunded';
		}
		return 'waiting';
	}

	/**
	 * Detect paid coin from order meta.
	 *
	 * @param WC_Order $order Order object.
	 * @return string
	 */
	private function extract_order_coin( $order ) {
		$keys = array( 'pay_currency', 'price_currency', 'actually_paid_currency', 'npwc_coin', '_npwc_coin', 'coin', 'currency' );
		foreach ( $keys as $key ) {
			$value = $order->get_meta( $key, true );
			if ( ! empty( $value ) ) {
				return strtoupper( sanitize_text_field( (string) $value ) );
			}
		}
		return strtoupper( $order->get_currency() );
	}

	/**
	 * Resolve coin logo URL (Cryptoicon API).
	 *
	 * @param string $coin Coin symbol.
	 * @return string
	 */
	private function coin_logo_url( $coin ) {
		$coin = strtolower( $coin );
		return 'https://coinicons-api.vercel.app/api/icon/' . rawurlencode( $coin );
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

		$upgrade_link = sprintf(
			'<a href="%s" style="color: green; font-weight: bold; font-family: Poppins, sans-serif;" target="_blank">%s</a>',
			esc_url( 'https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=plugin-npwc&utm_medium=plugins-page&utm_campaign=upgrade-to-pro' ),
			esc_html__( 'Upgrade to Pro', 'nowpayments-for-woocommerce' )
		);

		array_unshift( $links, $upgrade_link );

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

	/**
	 * Add YooAnalytics Banner | Action Callback
	 *
	 * @since 1.2.5
	 */
	public function add_yooanalytics_banner() {

		?>
			<div class="yooanalytics-woocommerce-wc-product-page" style="padding: 10px">
				<h3> 📊 Track your site's Views, Visitors & WooCommerce Purchase Journey for Free.</h3>
				<a href="<?php echo esc_url( admin_url( '/plugin-install.php?tab=plugin-information&plugin=yooanalytics&TB_iframe=true&width=772&height=644' ) ); ?>" class="thickbox open-plugin-details-modal" aria-label="More information about Midnight Deals for WooCommerce" data-title="Midnight Deals for WooCommerce">
					<img src="<?php echo esc_attr( NPWC_PLUGIN_URL . '/assets/images/yooanalytics.png' ); ?>" style="width: 100%;" />
				</a>
			</div>
		<?php
	}
}

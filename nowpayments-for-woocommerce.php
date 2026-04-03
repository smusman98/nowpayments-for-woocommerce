<?php
/**
 * Plugin Name: NOWPayments for WooCommerce
 * Plugin URI: https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=npwc&utm_medium=plugin-uri
 * Author: CoderPress
 * Description: Allow WooCommerce user to checkout with 300+ crypto currencies.
 * Version: 1.3.0
 * Author: CoderPress
 * Author URI: https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=npwc&utm_medium=author-uri
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nowpayments-for-woocommerce
 * @package NowPayments_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nfw_fs' ) ) {
	/**
	 * Freemius SDK helper.
	 *
	 * @return \Freemius Freemius SDK instance.
	 * @throws \Freemius_Exception When SDK initialization fails.
	 * @since 1.0
	 * @version 1.0
	 */
	function nfw_fs() {
		global $nfw_fs;

		if ( ! isset( $nfw_fs ) ) {
			// Include Freemius SDK.
			require_once __DIR__ . '/freemius/start.php';

			$nfw_fs = fs_dynamic_init(
				array(
					'id'             => '10766',
					'slug'           => 'nowpayments-for-woocommerce',
					'type'           => 'plugin',
					'public_key'     => 'pk_d1f216cade13caf8ec98da3aa993d',
					'is_premium'     => false,
					'has_addons'     => false,
					'has_paid_plans' => false,
					'menu'           => array(
						'first-path' => 'admin.php?page=wc-settings&tab=checkout&section=nowpayments&from=WCADMIN_PAYMENT_SETTINGS',
						'account'    => false,
						'contact'    => false,
						'support'    => false,
					),
				)
			);
		}

		return $nfw_fs;
	}

	// Init Freemius.
	nfw_fs();
	// Signal that SDK was initiated.
	do_action( 'nfw_fs_loaded' );
}

if ( ! defined( 'NPWC_PLUGIN_FILE' ) ) {
	define( 'NPWC_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'NPWC_VERSION' ) ) {
	define( 'NPWC_VERSION', '1.3.0' );
}

if ( ! defined( 'NPWC_PLUGIN_URL' ) ) {
	define( 'NPWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'NPWC_PLUGIN_DIR_PATH' ) ) {
	define( 'NPWC_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
}

require dirname( NPWC_PLUGIN_FILE ) . '/includes/class-npwc-init.php';

add_action( 'plugins_loaded', 'load_npwc' );


/**
 * Loads Plugin
 *
 * @since 1.0
 * @version 1.0
 */
function load_npwc() {
	NPWC_Init::get_instance();
}

/**
 * Check whether current admin page is NOWPayments WC settings page.
 *
 * @return bool
 */
function npwc_is_wc_nowpayments_settings_page() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check.
	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check.
	$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check.
	$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';

	return 'wc-settings' === $page && 'checkout' === $tab && 'nowpayments' === $section;
}

// New Year Sale admin promo notice.
add_action(
	'admin_notices',
	function () {
		$dismissed = get_user_meta( get_current_user_id(), 'npwc_pro_notice_dismissed_2026', true );

		if ( $dismissed || ! current_user_can( 'manage_options' ) || ! npwc_is_wc_nowpayments_settings_page() ) {
			return;
		}

		?>
		<div class="npwc-pro-conversion-notice notice notice-info is-dismissible" data-dismiss-action="npwc_dismiss_pro_notice">
			<div class="npwc-pro-notice-banner">
				<button type="button" class="notice-dismiss">
					<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'nowpayments-for-woocommerce' ); ?></span>
				</button>

				<div class="npwc-banner-confetti">
					<div class="npwc-banner-confetti-item npwc-banner-confetti-1"></div>
					<div class="npwc-banner-confetti-item npwc-banner-confetti-2"></div>
					<div class="npwc-banner-confetti-item npwc-banner-confetti-3"></div>
					<div class="npwc-banner-confetti-item npwc-banner-confetti-4"></div>
					<div class="npwc-banner-confetti-item npwc-banner-confetti-5"></div>
				</div>

				<div class="npwc-pro-notice-content">
					<div class="npwc-new-year-section">
						<div class="npwc-pro-notice-left">
							<div class="npwc-pro-notice-icon">
								🎉
							</div>
							<div class="npwc-pro-notice-text">
								<h3 class="npwc-ny-heading">
									<a href="https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=npwc&utm_medium=admin_notice&utm_campaign=new_year_2026&coupon=LIFETIME25" target="_blank" rel="noopener noreferrer">
										<?php esc_html_e( 'New Year Sale', 'nowpayments-for-woocommerce' ); ?>
									</a>
								</h3>
								<div class="npwc-ny-text">
									<?php
									printf(
										/* translators: %s: discount amount */
										esc_html__( 'Kick Off 2026 With a Whopping %s OFF! Use Code: LIFETIME25', 'nowpayments-for-woocommerce' ),
										'25%'
									);
									?>
								</div>
							</div>
						</div>
						<div class="npwc-pro-notice-right">
							<button
								type="button"
								class="npwc-coupon-code-btn shine-button"
								data-coupon="LIFETIME25"
								aria-label="<?php esc_attr_e( 'Copy coupon code LIFETIME25', 'nowpayments-for-woocommerce' ); ?>"
							>
								<span class="npwc-coupon-text">
									<?php esc_html_e( 'Use Code', 'nowpayments-for-woocommerce' ); ?>
									<strong>LIFETIME25</strong>
								</span>
							</button>
							<a
								href="https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=plugin-npwc&utm_medium=top-banner&utm_campaign=upgrade-to-pro"
								target="_blank"
								rel="noopener noreferrer"
								class="npwc-pro-notice-button shine-button"
							>
								<?php esc_html_e( 'Upgrade to Pro', 'nowpayments-for-woocommerce' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
);

add_action(
	'admin_enqueue_scripts',
	function () {
		$dismissed = get_user_meta( get_current_user_id(), 'npwc_pro_notice_dismissed_2026', true );

		if ( $dismissed || ! npwc_is_wc_nowpayments_settings_page() ) {
			return;
		}

		wp_enqueue_style(
			'npwc-pro-notice',
			NPWC_PLUGIN_URL . 'assets/css/npwc-pro-notice.css',
			array(),
			NPWC_VERSION
		);

		wp_enqueue_script(
			'npwc-pro-notice',
			NPWC_PLUGIN_URL . 'assets/js/npwc-pro-notice.js',
			array( 'jquery' ),
			NPWC_VERSION,
			true
		);

		wp_localize_script(
			'npwc-pro-notice',
			'npwcProNotice',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'npwc_dismiss_pro_notice' ),
			)
		);
	}
);

add_action(
	'wp_ajax_npwc_dismiss_pro_notice',
	function () {
		check_ajax_referer( 'npwc_dismiss_pro_notice', 'nonce' );
		update_user_meta( get_current_user_id(), 'npwc_pro_notice_dismissed_2026', true );
		wp_send_json_success();
	}
);

<?php
/**
 * Plugin Name: NOWPayments for WooCommerce
 * Plugin URI: https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=npwc&utm_medium=plugin-uri
 * Author: CoderPress
 * Description: Allow WooCommerce user to checkout with 300+ crypto currencies.
 * Version: 1.2.8
 * Author: CoderPress
 * Author URI: https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=npwc&utm_medium=author-uri
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
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
						'first-path' => 'plugins.php',
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
	define( 'NPWC_VERSION', '1.2.8' );
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

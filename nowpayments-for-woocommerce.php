<?php
/**
 * Plugin Name: NOWPayments for WooCommerce
 * Plugin URI: https://www.scintelligencia.com/
 * Author: SCI Intelligencia
 * Description: Allow WooCommerce user to checkout with 100+ crypto currencies.
 * Version: 1.0
 * Author: Syed Muhammad Usman
 * Author URI: https://www.linkedin.com/in/syed-muhammad-usman/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Tags: woocommerce, nowpayments, payment, payment gateway, commerce, product
 * @author Syed Muhammad Usman
 * @url https://www.linkedin.com/in/syed-muhammad-usman/
 */


defined( 'ABSPATH' ) || exit;

if ( ! defined( 'NPWC_PLUGIN_FILE' ) ) {
    define( 'NPWC_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'NPWC_VERSION' ) ) {
    define( 'NPWC_VERSION', '1.1.0' );
}

if ( ! defined( 'NPWC_PLUGIN_URL' ) ) {
    define( 'NPWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
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

<?php
/**
 * NPWC init
 *
 * @package NOWPayments For WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * CLass NPWC init
 */
class NPWC_Init {

	/**
	 * NPWC init
	 *
	 * @var self $instance
	 *
	 * @version 1.0
	 * @since 1.0
	 */
	private static $instance;

	/**
	 * Single ton
	 *
	 * @return NPWC_Init
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
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
			<p><?php esc_attr_e( 'In order to use NOWPayments for WooCommerce, make sure WooCommerce is installed and active.', 'sample-text-domain' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Finally initialize the Plugin :)
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	private function init() {

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
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Admin enqueue scripts.
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_script(
			'npwc-custom-scripts',
			NPWC_PLUGIN_URL . 'assets/js/scripts.js',
			array( 'jquery' ),
			NPWC_VERSION,
			true
		);
	}

	/**
	 * Filter Callback
	 *
	 * @param string $plugin_meta Plugin meta data.
	 * @param string $plugin_file Plugin file.
	 * @param string $plugin_data Plugin data.
	 *
	 * @since 1.0.1
	 * @version 1.0
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data ) {

		if ( 'nowpayments-for-woocommerce' === $plugin_data['slug'] ) {

			$plugin_meta[] = sprintf(
				'<a href="%s" style="color: green; font-weight: bold" target="_blank">%s</a>',
				esc_url( 'https://coderpress.co/products/nowpayments-for-woocommerce/' ),
				__( 'Go PRO' )
			);
			$plugin_meta[] = sprintf(
				'<a href="%s" style="color: green; font-weight: bold" target="_blank">%s</a>',
				esc_url( 'https://nowpayments.coderpress.co/shop/' ),
				__( 'Demo PRO' )
			);

		}

		return $plugin_meta;
	}

}

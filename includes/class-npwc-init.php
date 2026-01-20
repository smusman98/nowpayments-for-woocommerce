<?php

class NPWC_Init {

    /**
     * @var
     *
     * @version 1.0
     * @since 1.0
     */
    private static $_instance;

    /**
     * Single ton
     * @return NPWC_Init
     *
     * @since 1.0
     * @version 1.0
     */
    public static function get_instance() {

        if( self::$_instance == null ) {
            self::$_instance = new self();
        }

        return self::$_instance;

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

        if( !function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            $this->init();
        }
        else {
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
            <p><?php _e( 'In order to use NOWPayments for WooCommerce, make sure WooCommerce is installed and active.', 'sample-text-domain' ); ?></p>
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

        require 'class-gateway.php';
        require 'class-api.php';

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

        $is_hidden_notice = get_option( 'npwc_hide_save_cart_notice' );
        $is_hidden_notice = true;

        if( !$is_hidden_notice ) {

            add_action( 'admin_notices', array( $this, 'save_cart_notice' ) );
            add_action( 'admin_post_npwc_hide_notice', array( $this, 'save_cart_hide_notice' ) );

        }

        if( ! class_exists( 'YooAnalytics' ) ) {

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
                    <a href="<?php echo admin_url( 'admin-post.php?action=npwc_hide_notice' ); ?>" style="vertical-align: -webkit-baseline-middle; margin-left: 20px;">Not interested</a>
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

        wp_redirect( wp_get_referer() );

    }

    public function admin_enqueue_scripts() {
        wp_enqueue_script(
            'npwc-custom-scripts',
            NPWC_PLUGIN_URL . 'assets/js/scripts.js',
            array( 'jquery' ),
            NPWC_VERSION,
            true
        );

        wp_localize_script( 'npwc-custom-scripts', 'npwc', 
            array( 
                'images' => NPWC_PLUGIN_URL . 'assets/images/',
            ) 
        );
    }

    /**
     * Filter Callback
     *
     * @param $plugin_meta
     * @param $plugin_file
     * @param $plugin_data
     * @param $status
     * @since 1.0.1
     * @version 1.0
     */
    public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {

        if( isset( $plugin_data['slug'] ) && $plugin_data['slug'] == 'nowpayments-for-woocommerce' ) {

            $plugin_meta[] = sprintf(
                '<a href="%s" style="color: green; font-weight: bold" target="_blank">%s</a>',
                esc_url( 'https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=npwc&utm_medium=plugins-go-pro' ),
                __( 'GO PRO🚀' )
            );
            $plugin_meta[] = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                esc_url( 'https://nowpayments.coderpress.co/shop/' ),
                __( 'Demo PRO' )
            );

        }

        return $plugin_meta;

    }

    /**
	 * Registers WooCommerce Blocks integration.
	 *
	 */
	public function checkout_block_support() {
		
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			
			require_once 'nowpayments-gateway-block.php';
	
			add_action( 'woocommerce_blocks_payment_method_type_registration', array( $this, 'register_checkout_block' ) );
	
		}
	
	}

	public function register_checkout_block( $payment_method_registry ) {
		
		$payment_method_registry->register( new NowPaymentsGatewayBlock );

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
                <a href="<?php echo admin_url( '/plugin-install.php?tab=plugin-information&plugin=yooanalytics&TB_iframe=true&width=772&height=644' ); ?>" class="thickbox open-plugin-details-modal" aria-label="More information about Midnight Deals for WooCommerce" data-title="Midnight Deals for WooCommerce">
                    <img src="<?php echo esc_attr( NPWC_PLUGIN_URL . '/assets/images/yooanalytics.png' ) ?>" style="width: 100%;" />
                </a>
            </div>
        <?php

    }

}

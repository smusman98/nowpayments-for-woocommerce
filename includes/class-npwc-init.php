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

        if( $plugin_data['slug'] == 'nowpayments-for-woocommerce' ) {

            $plugin_meta[] = sprintf(
                '<a href="%s" style="color: green; font-weight: bold" target="_blank">%s</a>',
                esc_url( 'https://scintelligencia.com/products/nowpayments-for-woocommerce-pro/' ),
                __( 'Go PRO' )
            );

        }

        return $plugin_meta;

    }

}

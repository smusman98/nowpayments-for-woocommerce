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
     * Action and Filters
     *
     * @since 1.0
     * @version 1.0
     */
    public function hooks() {

        add_filter( 'plugin_action_links_nowpayments-for-woocommerce/nowpayments-for-woocommerce.php', array( $this, 'plugin_action_links' ) );

    }

    /**
     * Add plugin action links | filter call-back
     *
     * @param $links
     * @return array
     * @since 1.0
     * @version 1.0
     */
    public function plugin_action_links( $links ) {

        $gateway_settings = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=nowpayments' );

        $new_links = array(
            "<a href='{$gateway_settings}'>Settings<a/>"
        );

        return array_merge( $new_links, $links );

    }

}

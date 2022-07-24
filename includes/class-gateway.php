<?php

class NPWC_Gateway extends WC_Payment_Gateway {

    /**
     * NPWC_Gateway constructor.
     *
     * @since 1.0
     * @version 1.0
     */
    public function __construct() {

        $this->id = 'nowpayments';

    }

}

/**
 * Adds Gateway into WooCommerce
 *
 * @param $gateways
 * @return mixed
 * @since 1.0
 * @version 1.0
 */
if ( !function_exists( 'add_nowpayments_to_wc' ) ):
    function add_nowpayments_to_wc($gateways ) {
    $gateways[] = 'nowpayments';
    return $gateways;
}
endif;

add_filter( 'woocommerce_payment_gateways', 'add_nowpayments_to_wc' );

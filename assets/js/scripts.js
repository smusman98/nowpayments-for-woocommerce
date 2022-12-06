( function( $ ) {
    $( document ).ready( function() {
        $( '#woocommerce_nowpayments_single_product_icon, #woocommerce_nowpayments_products_icons' ).on( 'click', function( e ) {
            e.preventDefault();

            window.open( 'https://scintelligencia.com/products/nowpayments-for-woocommerce-pro/', '_blank' )
        } );
    } );
} )( jQuery );
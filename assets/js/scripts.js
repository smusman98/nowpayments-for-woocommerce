( function( $ ) {
    $( document ).ready( function() {
        $( '#woocommerce_nowpayments_single_product_icon, #woocommerce_nowpayments_products_icons' ).on( 'click', function( e ) {
            e.preventDefault();

            window.open( 'https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=npwc&utm_medium=settings-pro-checkbox', '_blank' )
        } );

        $( '#woocommerce_nowpayments_subscription' ).on( 'click', function( e ) {
            e.preventDefault();

            window.open( 'https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=npwc&utm_medium=settings-subscription', '_blank' )
        } );
    } );

    // How to setup
    var targetRow = jQuery( 'input#woocommerce_nowpayments_enabled' ).closest( 'tr' );
    var newRow = jQuery( 
        `<tr valign="top">
            <th scope="row" class="titledesc">
                <label for="new_field">How to Setup?</label>
            </th>
            <td class="forminp">
                <a href="https://coderpress.co/docs/nowpayments-for-woocommerce/?utm_source=npwc&utm_medium=how-to-setup" target="_blank">
                    Documentation
                </a>
            </td>
        </tr>` );
    targetRow.after( newRow );

    // Pro Product Page
    var targetRow = jQuery( 'input#woocommerce_nowpayments_single_product_icon' ).closest( 'tr' );
    var newRow = jQuery( 
        `<tr valign="top">
            <th scope="row" class="titledesc">
                <label for="new_field">Pro Product Page Preview</label>
            </th>
            <td class="forminp">
                <a href="https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=npwc&utm_medium=product-preview" target="_blank">
                    <img src="${npwc.images}/product-page.gif" width="400px" />
                </a>
            </td>
        </tr>` );
    targetRow.after( newRow );

    // Pro Product Icons
    var targetRow = jQuery( 'input#woocommerce_nowpayments_products_icons' ).closest( 'tr' );
    var newRow = jQuery( 
        `<tr valign="top">
            <th scope="row" class="titledesc">
                <label for="new_field">Pro Product Page Preview</label>
            </th>
            <td class="forminp">
                <a href="https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=npwc&utm_medium=shop-preview" target="_blank">
                    <img src="${npwc.images}/shop-page.gif" width="400px" />
                </a>
            </td>
        </tr>` );
    targetRow.after( newRow );

    // Pro Demo
    var targetRow = jQuery( 'input#woocommerce_nowpayments_webhook_url' ).closest( 'tr' );
    var newRow = jQuery( 
        `<tr valign="top">
            <th scope="row" class="titledesc">
                <label for="new_field">Launch Demo 🚀</label>
            </th>
            <td class="forminp">
                <a href="https://nowpayments.coderpress.co/shop" target="_blank">
                    <b>Try Pro Demo 🚀</b>
                </a>
            </td>
        </tr>` );
    targetRow.after( newRow );

} )( jQuery );
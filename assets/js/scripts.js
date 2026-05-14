( function( $ ) {
    $( document ).ready( function() {
        var proBaseUrl = 'https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=plugin-npwc&utm_medium=modal&utm_campaign=upgrade-to-pro';
        var currentModalSource = 'general_modal';

        function getTrackedProUrl( sourceKey ) {
            return proBaseUrl + '&utm_content=' + encodeURIComponent( sourceKey || 'general_modal' );
        }

        /* 
        * Force pro-only checkboxes to stay unchecked in free version UI.
        */
        $( '#woocommerce_nowpayments_single_product_icon, #woocommerce_nowpayments_products_icons, #woocommerce_nowpayments_subscription' )
            .prop( 'checked', false );

        /* 
        * Add popup once for pro-feature clicks.
        */
        if ( ! $( '#npwc-pro-popup-overlay' ).length ) {
            $( 'body' ).append(
                `<div id="npwc-pro-popup-overlay" class="npwc-pro-popup-overlay" style="display:none;">
                    <div class="npwc-pro-popup-content" role="dialog" aria-modal="true" aria-label="Unlock Pro Access">
                        <button type="button" class="npwc-pro-popup-close" aria-label="Close">❌</button>
                        <div class="npwc-pro-popup-inner">
                            <div class="npwc-pro-popup-rocket">🚀</div>
                            <h2>Unlock Your Pro Access</h2>
                            <p>Your premium license is ready — activate now and enjoy full access!</p>
                            <div class="npwc-pro-popup-offer">✨ Special intro offer - limited time only</div>
                            <a href="${getTrackedProUrl( currentModalSource )}" target="_blank" class="npwc-pro-popup-upgrade npwc-pro-upgrade-link">Upgrade to Pro →</a>
                            <button type="button" class="npwc-pro-popup-dismiss">No thanks, maybe later.</button>
                            <div class="npwc-divider"></div>
                            <div class="npwc-trust-badges">
                                <div class="npwc-trust-badge">
                                    <div class="npwc-trust-icon-wrapper">
                                        <span class="npwc-trust-icon dashicons dashicons-admin-site"></span>
                                    </div>
                                    <div class="npwc-trust-text">
                                        <div class="npwc-trust-text-primary">Trusted by</div>
                                        <div class="npwc-trust-text-secondary">3K+ website owners</div>
                                    </div>
                                </div>
                                <div class="npwc-trust-badge">
                                    <div class="npwc-trust-icon-wrapper">
                                        <span class="npwc-trust-icon dashicons dashicons-star-filled"></span>
                                    </div>
                                    <div class="npwc-trust-text">
                                        <div class="npwc-trust-text-primary">Rated 4.3/5</div>
                                        <div class="npwc-trust-text-secondary">by customers</div>
                                    </div>
                                </div>
                                <div class="npwc-trust-badge">
                                    <div class="npwc-trust-icon-wrapper">
                                        <span class="npwc-trust-icon dashicons dashicons-shield"></span>
                                    </div>
                                    <div class="npwc-trust-text">
                                        <div class="npwc-trust-text-primary">14-day</div>
                                        <div class="npwc-trust-text-secondary">money-back guarantee</div>
                                    </div>
                                </div>
                            </div>
                            <p class="npwc-footer-text">Thank you for choosing NOWPayments!</p>
                        </div>
                    </div>
                </div>`
            );
        }

        var $proRows = $(
            '#woocommerce_nowpayments_single_product_icon, #woocommerce_nowpayments_products_icons, #woocommerce_nowpayments_subscription'
        ).closest( 'tr' );
        $proRows.addClass( 'npwc-pro-trigger-row' );
        $( '#woocommerce_nowpayments_single_product_icon' ).closest( 'tr' ).attr( 'data-modal-source', 'product-page-modal' );
        $( '#woocommerce_nowpayments_products_icons' ).closest( 'tr' ).attr( 'data-modal-source', 'shop-page-modal' );
        $( '#woocommerce_nowpayments_subscription' ).closest( 'tr' ).attr( 'data-modal-source', 'woocommerce-subscription-modal' );

        function openProPopup( sourceKey ) {
            currentModalSource = sourceKey || 'general_modal';
            $( '.npwc-pro-upgrade-link' ).attr( 'href', getTrackedProUrl( currentModalSource ) );
            $( '#npwc-pro-popup-overlay' ).fadeIn( 180 );
            $( 'body' ).addClass( 'npwc-no-scroll' );
        }

        function closeProPopup() {
            $( '#npwc-pro-popup-overlay' ).fadeOut( 180 );
            $( 'body' ).removeClass( 'npwc-no-scroll' );
        }

        /* 
        * Clicking pro rows or badges opens popup.
        */
        $( document ).on( 'click', '.npwc-pro-trigger-row, .npwc-pro-badge', function( e ) {
            if ( $( e.target ).closest( 'a' ).length ) {
                return;
            }

            e.preventDefault();
            var sourceKey = $( this ).closest( 'tr' ).attr( 'data-modal-source' ) || 'general_modal';
            openProPopup( sourceKey );
        } );

        /* 
        * Keep checkboxes unchecked and open popup on direct click.
        */
        $( '#woocommerce_nowpayments_single_product_icon, #woocommerce_nowpayments_products_icons, #woocommerce_nowpayments_subscription' )
            .on( 'click', function( e ) {
                e.preventDefault();
                $( this ).prop( 'checked', false );
                var sourceKey = $( this ).closest( 'tr' ).attr( 'data-modal-source' ) || 'general_modal';
                openProPopup( sourceKey );
            } );

        $( document ).on( 'click', '.npwc-pro-popup-close, .npwc-pro-popup-dismiss', function( e ) {
            e.preventDefault();
            e.stopPropagation();
            closeProPopup();
        } );

        $( document ).on( 'click', '#npwc-pro-popup-overlay', function( e ) {
            if ( e.target === this ) {
                closeProPopup();
            }
        } );
    } );

    /* 
    * How to setup
    */
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

    /* 
    * Pro Product Page
    */
    var targetRow = jQuery( 'input#woocommerce_nowpayments_single_product_icon' ).closest( 'tr' );
    var newRow = jQuery( 
        `<tr valign="top">
            <th scope="row" class="titledesc">
                <label for="new_field">Pro Product Page Preview</label>
            </th>
            <td class="forminp">
                <a href="https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=plugin-npwc&utm_medium=integration-page&utm_campaign=pro-product-page-preview" target="_blank">
                    <img src="${npwc.images}/product-page.gif" width="400px" />
                </a>
            </td>
        </tr>` );
    targetRow.after( newRow );

    /* 
    * Pro Product Icons
    */
    var targetRow = jQuery( 'input#woocommerce_nowpayments_products_icons' ).closest( 'tr' );
    var newRow = jQuery( 
        `<tr valign="top">
            <th scope="row" class="titledesc">
                <label for="new_field">Pro Product Page Preview</label>
            </th>
            <td class="forminp">
                <a href="https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=plugin-npwc&utm_medium=integration-page&utm_campaign=pro-shop-page-preview" target="_blank">
                    <img src="${npwc.images}/shop-page.gif" width="400px" />
                </a>
            </td>
        </tr>` );
    targetRow.after( newRow );

    /* 
    * Pro Demo
    */
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

    /* 
    * Sidebar layout (Unlock Pro Features + Help).
    */
    var $settingsTable = jQuery( 'table.form-table' );
    if ( $settingsTable.length && jQuery( 'input#woocommerce_nowpayments_enabled' ).length ) {
        $settingsTable.wrap( '<div class="npwc-settings-grid"><div class="npwc-settings-main"></div></div>' );
        var $grid = jQuery( '.npwc-settings-grid' );
        var sidebarHtml = `
            <aside class="npwc-sidebar">
                <div class="npwc-card npwc-pro-card">
                    <div class="npwc-pro-header">
                        <h3>Unlock Pro Features</h3>
                        <span class="npwc-pro-badge">PRO</span>
                    </div>
                    <p>Take your crypto checkout to the next level with these powerful features:</p>
                    <ul class="npwc-pro-features">
                        <li><span class="dashicons dashicons-yes"></span>Attractive crypto icons on Shop & Product pages</li>
                        <li><span class="dashicons dashicons-yes"></span>Crypto pricing on Shop & Product pages</li>
                        <li><span class="dashicons dashicons-yes"></span>WooCommerce Subscriptions Support</li>
                        <li><span class="dashicons dashicons-yes"></span>HPOS & block-based checkout compatible</li>
                        <li><span class="dashicons dashicons-yes"></span>Restrict products & set minimum checkout amount</li>
                        <li><span class="dashicons dashicons-yes"></span>Specify custom order status after successful payment</li>
                    </ul>
                    <a href="https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=plugin-npwc&utm_medium=side-banner&utm_campaign=upgrade-to-pro" target="_blank" class="shine-button npwc-btn-primary">Upgrade to Pro</a>
                </div>
                <div class="npwc-card">
                    <h3>Need Help?</h3>
                    <p>Check out our documentation or contact support if you have any questions.</p>
                    <a href="https://coderpress.co/docs/nowpayments-for-woocommerce/?utm_source=plugin-npwc&utm_medium=side-banner&utm_campaign=view-documentation" target="_blank" class="npwc-btn-secondary">View Documentation</a>
                </div>
            </aside>`;

        $grid.append( sidebarHtml );
    }

} )( jQuery );

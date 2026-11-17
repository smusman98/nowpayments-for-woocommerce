/* Scripts for NOWPayments free plugin */

( function( $ ) {
	var proBaseUrl = 'https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=plugin-npwc&utm_medium=modal&utm_campaign=upgrade-to-pro';
	var currentModalSource = 'general_modal';
	var settingsUiInitialized = false;
	var settingsObserver = null;

	function getTrackedProUrl( sourceKey ) {
		return proBaseUrl + '&utm_content=' + encodeURIComponent( sourceKey || 'general_modal' );
	}

	function isNowpaymentsSettingsFormPresent() {
		return $( 'input#woocommerce_nowpayments_enabled' ).length > 0;
	}

	function ensureProPopup() {
		if ( $( '#npwc-pro-popup-overlay' ).length ) {
			return;
		}

		$( 'body' ).append(
			`<div id="npwc-pro-popup-overlay" class="npwc-pro-popup-overlay" style="display:none;">
				<div class="npwc-pro-popup-content" role="dialog" aria-modal="true" aria-label="Unlock Pro Access">
					<button type="button" class="npwc-pro-popup-close" aria-label="Close">
						<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
					</button>
					<div class="npwc-pro-popup-inner">
						<div class="npwc-pro-popup-rocket" aria-hidden="true">🚀</div>
						<p class="npwc-pro-popup-eyebrow">NOWPayments Pro</p>
						<h2>Unlock Your Pro Access</h2>
						<p>Upgrade for advanced checkout features, icons, subscriptions, and priority support.</p>
						<p class="npwc-pro-popup-offer">Limited-time intro pricing</p>
						<div class="npwc-pro-popup-actions">
							<a href="${getTrackedProUrl( currentModalSource )}" target="_blank" rel="noopener noreferrer" class="npwc-pro-popup-upgrade npwc-pro-upgrade-link">Upgrade to Pro</a>
							<button type="button" class="npwc-pro-popup-dismiss">No thanks, maybe later</button>
						</div>
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

	function openProPopup( sourceKey ) {
		currentModalSource = sourceKey || 'general_modal';
		$( '.npwc-pro-upgrade-link' ).attr( 'href', getTrackedProUrl( currentModalSource ) );
		$( '#npwc-pro-popup-overlay' ).css( 'display', 'flex' );
		$( 'body' ).addClass( 'npwc-no-scroll' );
	}

	function closeProPopup() {
		$( '#npwc-pro-popup-overlay' ).hide();
		$( 'body' ).removeClass( 'npwc-no-scroll' );
	}

	function bindProFeatureHandlers() {
		if ( $( 'body' ).data( 'npwcProHandlersBound' ) ) {
			return;
		}

		$( '#woocommerce_nowpayments_single_product_icon, #woocommerce_nowpayments_products_icons, #woocommerce_nowpayments_subscription' )
			.prop( 'checked', false );

		var $proRows = $(
			'#woocommerce_nowpayments_single_product_icon, #woocommerce_nowpayments_products_icons, #woocommerce_nowpayments_subscription'
		).closest( 'tr' );
		$proRows.addClass( 'npwc-pro-trigger-row' );
		$( '#woocommerce_nowpayments_single_product_icon' ).closest( 'tr' ).attr( 'data-modal-source', 'product-page-modal' );
		$( '#woocommerce_nowpayments_products_icons' ).closest( 'tr' ).attr( 'data-modal-source', 'shop-page-modal' );
		$( '#woocommerce_nowpayments_subscription' ).closest( 'tr' ).attr( 'data-modal-source', 'woocommerce-subscription-modal' );

		$( '#woocommerce_nowpayments_single_product_icon, #woocommerce_nowpayments_products_icons, #woocommerce_nowpayments_subscription' )
			.off( 'mousedown.npwc click.npwc' )
			.on( 'mousedown.npwc', function( e ) {
				e.preventDefault();
				e.stopPropagation();
				return false;
			} )
			.on( 'click.npwc', function( e ) {
				e.preventDefault();
				e.stopPropagation();
				$( this ).prop( 'checked', false );
				var sourceKey = $( this ).closest( 'tr' ).attr( 'data-modal-source' ) || 'general_modal';
				openProPopup( sourceKey );
				return false;
			} );

		$( 'body' ).data( 'npwcProHandlersBound', true );
	}

	function markCredentialSectionRow( $fieldRow, envModifier ) {
		if ( ! $fieldRow.length ) {
			return;
		}
		$fieldRow.addClass( 'npwc-settings-env npwc-settings-env--' + envModifier );

		var $titleRow = $fieldRow.prevAll( 'tr.wc-settings-sub-title' ).first();
		if ( $titleRow.length && ! $titleRow.hasClass( 'npwc-settings-env--' + envModifier ) ) {
			$titleRow.addClass( 'npwc-settings-env npwc-settings-env--' + envModifier );
		}
	}

	function tagEnvironmentRows() {
		markCredentialSectionRow( $( '#woocommerce_nowpayments_sandbox_api_key' ).closest( 'tr' ), 'sandbox' );
		markCredentialSectionRow( $( '#woocommerce_nowpayments_sandbox_ipn_key' ).closest( 'tr' ), 'sandbox' );
		markCredentialSectionRow( $( '#woocommerce_nowpayments_live_api_key' ).closest( 'tr' ), 'live' );
		markCredentialSectionRow( $( '#woocommerce_nowpayments_live_ipn_key' ).closest( 'tr' ), 'live' );

	}

	function tagGatedSettingsRows() {
		var $enabled = $( '#woocommerce_nowpayments_enabled' );
		if ( ! $enabled.length ) {
			return;
		}

		var $enabledRow = $enabled.closest( 'tr' );
		$enabledRow.addClass( 'npwc-gateway-enabled-row' ).removeClass( 'npwc-gateway-settings-row' );

		var enabledFound = false;

		$( '.npwc-settings-main table.form-table' ).each( function () {
			var afterEnabled = enabledFound;

			$( this )
				.find( 'tr' )
				.each( function () {
					if ( this === $enabledRow[0] ) {
						afterEnabled = true;
						enabledFound = true;
						return;
					}
					if ( afterEnabled ) {
						$( this ).addClass( 'npwc-gateway-settings-row' );
					}
				} );
		} );
	}

	function wrapGatedSettingsInTbody() {
		var $enabled = $( '#woocommerce_nowpayments_enabled' );
		if ( ! $enabled.length ) {
			return;
		}

		$( '.npwc-settings-main table.form-table' ).each( function () {
			var $table = $( this );
			var $gatedRows = $table.find( 'tr.npwc-gateway-settings-row' ).filter( function () {
				return ! $( this ).closest( 'tbody.npwc-gateway-settings-body' ).length;
			} );

			if ( ! $gatedRows.length ) {
				return;
			}

			var $gatedBody = $table.children( 'tbody.npwc-gateway-settings-body' ).first();
			if ( ! $gatedBody.length ) {
				$gatedBody = $( '<tbody class="npwc-gateway-settings-body"></tbody>' );
				var $enabledRow = $enabled.closest( 'tr' );
				var $enabledTbody = $enabledRow.closest( 'tbody' );

				if ( $enabledTbody.length && $enabledTbody.closest( 'table' )[ 0 ] === $table[ 0 ] ) {
					$enabledTbody.after( $gatedBody );
				} else {
					$table.append( $gatedBody );
				}
			}

			$gatedRows.appendTo( $gatedBody );
		} );
	}

	function ensureSubmitPlacement() {
		var $main = $( '.npwc-settings-main' );
		if ( ! $main.length ) {
			return;
		}

		var $submit = $main.children( 'p.submit' ).first();
		if ( ! $submit.length ) {
			$submit = $main.find( 'p.submit' ).first();
		}

		var $tables = $main.children( 'table.form-table' );
		if ( ! $submit.length || ! $tables.length ) {
			return;
		}

		$tables.last().after( $submit );
	}

	function syncGatewayEnabledUi() {
		var $enabled = $( '#woocommerce_nowpayments_enabled' );
		if ( ! $enabled.length ) {
			return;
		}

		tagGatedSettingsRows();
		wrapGatedSettingsInTbody();
		ensureSubmitPlacement();

		var isOn = $enabled.is( ':checked' );
		// Visibility is handled in admin.css via this body class.
		$( 'body' ).toggleClass( 'npwc-gateway-disabled', ! isOn );

		if ( isOn ) {
			syncApiModeUi();
		}
	}

	function bindGatewayEnabledHandlers() {
		if ( ! $( 'body' ).data( 'npwcGatewayEnabledBound' ) ) {
			$( document ).on( 'change', '#woocommerce_nowpayments_enabled', syncGatewayEnabledUi );
			$( 'body' ).data( 'npwcGatewayEnabledBound', true );
		}
		syncGatewayEnabledUi();
	}

	/**
	 * WooCommerce prints settings messages (errors, etc.) inside the form, before the gateway header. Keep them above the grid.
	 */
	function keepNoticesAboveGrid( $form, $grid ) {
		$form.children( 'div.updated, div.error, div.notice' ).each( function () {
			if ( $grid[ 0 ].compareDocumentPosition( this ) & Node.DOCUMENT_POSITION_FOLLOWING ) {
				$grid.before( this );
			}
		} );
	}

	function repairSettingsLayout() {
		var $grid = $( '.npwc-settings-grid' );
		var $form = $( 'input#woocommerce_nowpayments_enabled' ).closest( 'form' );
		if ( ! $grid.length || ! $form.length ) {
			return;
		}

		var $main = $grid.children( '.npwc-settings-main' );
		if ( ! $main.length ) {
			return;
		}

		keepNoticesAboveGrid( $form, $grid );

		var $submit = $form.find( 'p.submit' ).first();
		if ( $submit.length && ! $submit.closest( '.npwc-settings-main' ).length ) {
			$submit.appendTo( $main );
		}
		ensureSubmitPlacement();

		var $header = $main.children( 'h2.wc-admin-header' ).first();
		if ( ! $header.length ) {
			return;
		}

		var $intro = $grid.children( '.npwc-settings-intro' );
		if ( ! $intro.length ) {
			$intro = $( '<div class="npwc-settings-intro"></div>' );
			$grid.prepend( $intro );
			$grid.removeClass( 'npwc-settings-grid--no-intro' );
		}

		var $desc = $header.next( 'p' );
		$intro.append( $header );
		if ( $desc.length ) {
			$intro.append( $desc );
		}
	}

	function buildSettingsLayout() {
		if ( ! isNowpaymentsSettingsFormPresent() ) {
			return;
		}

		if ( $( '.npwc-settings-grid' ).length ) {
			repairSettingsLayout();
			return;
		}

		var $settingsTable = $( 'input#woocommerce_nowpayments_enabled' ).closest( 'table.form-table' );
		var $form = $settingsTable.closest( 'form' );
		if ( ! $form.length ) {
			return;
		}

		var $grid = $( '<div class="npwc-settings-grid"></div>' );
		var $main = $( '<div class="npwc-settings-main"></div>' );
		var $intro = $( '<div class="npwc-settings-intro"></div>' );
		var $header = $form.find( 'h2.wc-admin-header' ).first();

		// Insert where the gateway settings start, so WooCommerce notices stay on top.
		var $anchor = $header.length && $header.parent().is( $form ) ? $header : $settingsTable;
		if ( $anchor.parent().is( $form ) ) {
			$anchor.before( $grid );
		} else {
			$form.prepend( $grid );
		}

		if ( $header.length ) {
			var $desc = $header.next( 'p' );
			$intro.append( $header );
			if ( $desc.length && $desc.parent().is( $form ) ) {
				$intro.append( $desc );
			}
			$grid.append( $intro );
		} else {
			$grid.addClass( 'npwc-settings-grid--no-intro' );
		}

		$grid.append( $main );

		$form.children( 'table.form-table' ).appendTo( $main );
		$form.find( 'p.submit' ).appendTo( $main );

		$grid.append(
			`<aside class="npwc-sidebar">
				<div class="npwc-card">
					<h3>Need Help?</h3>
					<p>Check out our documentation or contact support if you have any questions.</p>
					<a href="https://coderpress.co/docs/nowpayments-for-woocommerce/?utm_source=plugin-npwc&utm_medium=side-banner&utm_campaign=view-documentation" target="_blank" class="npwc-btn-secondary">View Documentation</a>
				</div>
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
			</aside>`
		);
	}

	function syncApiModeUi() {
		var $radios = $( 'input[name="woocommerce_nowpayments_api_mode"]' );
		if ( ! $radios.length ) {
			return;
		}

		tagEnvironmentRows();

		// Live/Sandbox credential rows are hidden in admin.css via this body class.
		var mode = $radios.filter( ':checked' ).val() || 'live';
		$( 'body' )
			.addClass( 'npwc-nowpayments-settings-page' )
			.removeClass( 'npwc-active-mode-live npwc-active-mode-sandbox' )
			.addClass( 'npwc-active-mode-' + mode );
	}

	function bindApiModeHandlers() {
		if ( $( 'body' ).data( 'npwcApiModeBound' ) ) {
			return;
		}

		$( document ).on( 'change', 'input[name="woocommerce_nowpayments_api_mode"]', syncApiModeUi );
		$( 'body' ).data( 'npwcApiModeBound', true );
		syncApiModeUi();
	}

	function insertRowAfter( $anchorRow, rowClass, html ) {
		if ( ! $anchorRow.length || $( '.' + rowClass ).length ) {
			return;
		}
		$anchorRow.after( $( html ).addClass( rowClass ) );
	}

	function initNowpaymentsSettingsUi() {
		if ( settingsUiInitialized || ! isNowpaymentsSettingsFormPresent() ) {
			return settingsUiInitialized;
		}

		var imagesBase = ( window.npwc && window.npwc.images ) ? window.npwc.images : '';

		var $singleProductRow = $( 'input#woocommerce_nowpayments_single_product_icon' ).closest( 'tr' );
		insertRowAfter(
			$singleProductRow,
			'npwc-row-product-preview',
			`<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="new_field">Pro Product Page Preview</label>
				</th>
				<td class="forminp">
					<a href="https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=plugin-npwc&utm_medium=integration-page&utm_campaign=pro-product-page-preview" target="_blank">
						<img src="${imagesBase}product-page.gif" width="400" alt="" />
					</a>
				</td>
			</tr>`
		);

		var $shopIconsRow = $( 'input#woocommerce_nowpayments_products_icons' ).closest( 'tr' );
		insertRowAfter(
			$shopIconsRow,
			'npwc-row-shop-preview',
			`<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="new_field">Pro Shop Page Preview</label>
				</th>
				<td class="forminp">
					<a href="https://coderpress.co/products/nowpayments-for-woocommerce/?utm_source=plugin-npwc&utm_medium=integration-page&utm_campaign=pro-shop-page-preview" target="_blank">
						<img src="${imagesBase}shop-page.gif" width="400" alt="" />
					</a>
				</td>
			</tr>`
		);

		buildSettingsLayout();
		repairSettingsLayout();

		bindGatewayEnabledHandlers();
		bindApiModeHandlers();
		bindProFeatureHandlers();
		settingsUiInitialized = true;

		if ( settingsObserver ) {
			settingsObserver.disconnect();
			settingsObserver = null;
		}

		return true;
	}

	function scheduleSettingsUiInit() {
		var attempts = 0;
		var maxAttempts = 80;

		function tick() {
			if ( initNowpaymentsSettingsUi() || attempts >= maxAttempts ) {
				return;
			}
			attempts += 1;
			window.setTimeout( tick, 100 );
		}

		tick();

		if ( ! settingsObserver && typeof MutationObserver !== 'undefined' ) {
			settingsObserver = new MutationObserver( function() {
				initNowpaymentsSettingsUi();
			} );
			settingsObserver.observe( document.body, { childList: true, subtree: true } );
		}
	}

	function bindWebhookCopyHandler() {
		if ( $( 'body' ).data( 'npwcWebhookCopyBound' ) ) {
			return;
		}

		$( document ).on( 'click', '.npwc-copy-webhook-url', function( e ) {
			e.preventDefault();

			var $btn = $( this );
			var $input = $btn.closest( '.npwc-webhook-url-field' ).find( 'input' );
			var text = ( $input.val() || '' ).trim();

			if ( ! text ) {
				return;
			}

			function onCopied() {
				var copiedLabel = $btn.attr( 'data-copied-label' ) || 'Copied!';
				var defaultTitle = $btn.attr( 'data-default-title' ) || $btn.attr( 'title' ) || 'Copy';
				var defaultAria = $btn.attr( 'data-default-aria' ) || $btn.attr( 'aria-label' ) || defaultTitle;

				$btn.attr( {
					'data-default-title': defaultTitle,
					'data-default-aria': defaultAria,
					title: copiedLabel,
					'aria-label': copiedLabel
				} ).addClass( 'npwc-copy-done' );

				window.clearTimeout( $btn.data( 'npwcCopyTimeout' ) );
				$btn.data( 'npwcCopyTimeout', window.setTimeout( function() {
					$btn.removeClass( 'npwc-copy-done' ).attr( {
						title: defaultTitle,
						'aria-label': defaultAria
					} );
				}, 2000 ) );
			}

			if ( typeof window.wcSetClipboard === 'function' ) {
				window.wcSetClipboard( text, $btn );
				onCopied();
				return;
			}

			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( text ).then( onCopied );
				return;
			}

			$input.trigger( 'focus' ).trigger( 'select' );
			try {
				document.execCommand( 'copy' );
				onCopied();
			} catch ( err ) {
				// Leave selection for manual copy.
			}
		} );

		$( 'body' ).data( 'npwcWebhookCopyBound', true );
	}

	$( document ).ready( function() {
		ensureProPopup();
		bindGatewayEnabledHandlers();
		bindApiModeHandlers();
		bindWebhookCopyHandler();

		$( document ).on( 'click', '.npwc-pro-trigger-row, .npwc-pro-badge', function( e ) {
			if ( $( e.target ).closest( 'a' ).length ) {
				return;
			}

			e.preventDefault();
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

		scheduleSettingsUiInit();
	} );
}( jQuery ) );

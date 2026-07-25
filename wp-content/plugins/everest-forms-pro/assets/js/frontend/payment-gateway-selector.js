/**
 * Show/hide payment field blocks based on Payment Gateway field selection.
 *
 * @since 1.9.15
 */
( function ( $ ) {
	'use strict';

	var panelMap = {
		stripe: '.evf-field-credit-card',
		square: '.evf-field-square-payment',
		authorize_net: '.evf-field-authorize-net',
		paypal: '.evf-payment-gateway-hint[data-evf-gateway-hint="paypal"]',
		mollie: '.evf-payment-gateway-hint[data-evf-gateway-hint="mollie"]',
		razorpay: '.evf-payment-gateway-hint[data-evf-gateway-hint="razorpay"]',
	};

	function getSelectedGatewaySlug( $ctrl ) {
		var $r = $ctrl.find( 'input.evf-payment-gateway-radio:not(.evf-payment-gateway-radio--off):checked' );
		var slug = $r.data( 'evf-gateway' );
		if ( ! slug ) {
			slug = $r.attr( 'data-evf-gateway' );
		}
		return String( slug || '' ).replace( /-/g, '_' );
	}

	function activateStripeCreditCardTab( $form ) {
		var $tabsWrap = $form.find( '.everest-forms-stripe-gateways-tabs' );
		if ( ! $tabsWrap.length ) {
			return;
		}

		var $stripeTab = $tabsWrap.find( '.evf-tab[data-gateway="stripe"]' );
		if ( ! $stripeTab.length ) {
			return;
		}

		var activeTab = $stripeTab.data( 'tab' );
		$tabsWrap.find( 'a.active' ).removeClass( 'active' );
		$stripeTab.find( 'a.label' ).addClass( 'active' );

		var $gatewayWrap = $tabsWrap.closest( '.everest-forms-payment-gateway' );
		$gatewayWrap.find( '.everest-forms-stripe-gateways-contents > div' ).hide();
		$gatewayWrap.find( 'div.tab_content_' + activeTab ).show();
	}

	/**
	 * Hide every gateway-specific block in this form (then applySelection shows the active one).
	 */
	function hideAllGatewayPanels( $form ) {
		var key, sel, $targets;
		for ( key in panelMap ) {
			if ( ! Object.prototype.hasOwnProperty.call( panelMap, key ) ) {
				continue;
			}
			sel = panelMap[ key ];
			$targets = $form.find( sel );
			if ( ! $targets.length ) {
				continue;
			}
			$targets.each( function () {
				var $el = $( this );
				if ( $el.closest( '.evf-payment-gateway-hints' ).length ) {
					$el.hide();
					return;
				}
				var $wrap = $el.closest( '.evf-field' );
				if ( $wrap.length ) {
					$wrap.hide();
				} else {
					$el.hide();
				}
			} );
		}
	}

	function toggleAuthorizeNetProxyRequired( $form, enable ) {
		var $proxy = $form.find( '.evf-payment-gateway-selector-authorize-net-proxy' );
		if ( ! $proxy.length ) {
			return;
		}
		$proxy
			.find(
				'input.evf-field-authorize-net-card-number, select.evf-field-authorize-net-expiration-month, select.evf-field-authorize-net-expiration-year, input.evf-field-authorize-net-card-code'
			)
			.each( function () {
				if ( enable ) {
					$( this ).attr( 'required', 'required' );
				} else {
					$( this ).removeAttr( 'required' );
				}
			} );
	}

	function updateCardVisualState( $form, slug ) {
		var $grid = $form.find( '.evf-pgw-grid' );
		if ( ! $grid.length ) {
			return;
		}
		$grid.find( '.evf-pgw-card' ).removeClass( 'evf-pgw-card--selected' );
		if ( slug ) {
			$grid.find( '.evf-pgw-card input[data-evf-gateway="' + slug.replace( /_/g, '_' ) + '"]' ).closest( '.evf-pgw-card' ).addClass( 'evf-pgw-card--selected' );
		}
	}

	function applySelection( $form, slug ) {
		hideAllGatewayPanels( $form );
		updateCardVisualState( $form, slug );

		var key;

		for ( key in panelMap ) {
			if ( ! Object.prototype.hasOwnProperty.call( panelMap, key ) ) {
				continue;
			}
			var sel = panelMap[ key ];
			var $targets = $form.find( sel );
			if ( ! $targets.length ) {
				continue;
			}
			var show = !! slug && key === slug;
			$targets.each( function () {
				var $el = $( this );
				if ( $el.closest( '.evf-payment-gateway-hints' ).length ) {
					$el.toggle( show );
					return;
				}
				var $wrap = $el.closest( '.evf-field' );
				if ( $wrap.length ) {
					$wrap.toggle( show );
				} else {
					$el.toggle( show );
				}
			} );
		}

		var $stripeTabs = $form.find( '.everest-forms-stripe-gateways-tabs' );
		if ( $stripeTabs.length && 'stripe' === slug ) {
			$stripeTabs.closest( '.evf-field-credit-card' ).show();
			activateStripeCreditCardTab( $form );
		}

		if ( 'stripe' === slug ) {
			var $stripeGateway = $form.find( '.everest-forms-gateway[data-gateway="stripe"]' );
			if ( $stripeGateway.length ) {
				$stripeGateway.closest( '.evf-field' ).show();
			}
			$( document ).trigger( 'evf_stripe_gateway_selected', [ $form ] );
		}

		if ( 'square' === slug ) {
			var $squareGateway = $form.find( '.everest-forms-gateway[data-gateway="square"]' );
			if ( $squareGateway.length ) {
				$squareGateway.closest( '.evf-field' ).show();
			}
			$( document ).trigger( 'evf_square_gateway_selected', [ $form ] );
		}

		toggleAuthorizeNetProxyRequired( $form, 'authorize_net' === slug );

		$( document ).trigger( 'evf_payment_gateway_changed', [ $form, slug ] );
	}

	$( function () {
		$( 'form.everest-form, form.everest-forms' ).each( function () {
			var $form = $( this );
			var $ctrl = $form.find( '.evf-payment-gateway-selector-inputs' );
			if ( ! $ctrl.length ) {
				return;
			}

			// Keep custom card UI and radio state always in sync.
			$form.on( 'click', '.evf-pgw-card', function () {
				var $radio = $( this ).find( 'input.evf-payment-gateway-radio:not(.evf-payment-gateway-radio--off)' ).first();
				if ( $radio.length && ! $radio.prop( 'checked' ) ) {
					$radio.prop( 'checked', true ).trigger( 'change' );
				}
			} );

			function onChange() {
				var $radios = $ctrl.find( 'input.evf-payment-gateway-radio:not(.evf-payment-gateway-radio--off)' );
				if ( 1 === $radios.length && ! $radios.filter( ':checked' ).length ) {
					$radios.prop( 'checked', true ).trigger( 'change' );
					return;
				}
				var $r = $radios.filter( ':checked' );
				if ( ! $r.length ) {
					applySelection( $form, '' );
					return;
				}
				applySelection( $form, getSelectedGatewaySlug( $ctrl ) );

				// Clear "required" state immediately after a valid selection.
				if ( $form.data( 'validator' ) ) {
					$form.data( 'validator' ).element( $r.get( 0 ) );
				}
				$r.closest( '.evf-field' ).removeClass( 'everest-forms-invalid everest-forms-invalid-required-field' );
				$r.closest( '.evf-field' ).find( 'label.evf-error' ).remove();
			}

			$ctrl.on( 'change', 'input.evf-payment-gateway-radio:not(.evf-payment-gateway-radio--off)', onChange );
			onChange();
			$( window ).on( 'load', onChange );
		} );
	} );
}( jQuery ) );

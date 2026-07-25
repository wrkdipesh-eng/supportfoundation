/**
 * Script to handle payment options for Square.
 *
 * @since 1.7.5
 */

"use strict";

jQuery(function ($) {

	/**
	 * Parse a displayed/stored price into a JS number, locale-safe.
	 *
	 * Uses the site's configured currency separators (window.evf_settings) so a
	 * thousands-separated value like "5,001.00" (or "5.001,00" in EU locales) is
	 * read correctly instead of becoming NaN. Falls back to a "right-most
	 * separator is the decimal mark" heuristic when settings are unavailable.
	 *
	 * @param {string|number} raw Raw price value.
	 * @return {number} Parsed amount, or 0 when it cannot be parsed.
	 */
	function evfSquareParseAmount( raw ) {
		if ( 'number' === typeof raw ) {
			return isNaN( raw ) ? 0 : raw;
		}
		if ( null === raw || undefined === raw ) {
			return 0;
		}

		var s = String( raw ).trim();
		if ( '' === s ) {
			return 0;
		}

		var settings    = window.evf_settings || {};
		var thousandSep = settings.currency_thousands;
		var decimalSep  = settings.currency_decimal;

		if ( decimalSep ) {
			// Trust the configured format the price was rendered with.
			if ( thousandSep ) {
				s = s.split( thousandSep ).join( '' );
			}
			if ( '.' !== decimalSep ) {
				s = s.split( decimalSep ).join( '.' );
			}
			s = s.replace( /[^0-9.\-]/g, '' );
		} else {
			// Fallback: strip currency symbols, treat the right-most separator as decimal.
			s = s.replace( /[^0-9.,\-]/g, '' );
			var lastComma = s.lastIndexOf( ',' );
			var lastDot   = s.lastIndexOf( '.' );
			if ( -1 !== lastComma && -1 !== lastDot ) {
				s = ( lastComma > lastDot ) ? s.replace( /\./g, '' ).replace( /,/g, '.' ) : s.replace( /,/g, '' );
			} else if ( -1 !== lastComma ) {
				s = s.replace( /,/g, '.' );
			}
		}

		// Defensive: collapse any leftover separators down to a single decimal point.
		var firstDot = s.indexOf( '.' );
		if ( -1 !== firstDot ) {
			s = s.slice( 0, firstDot + 1 ) + s.slice( firstDot + 1 ).replace( /\./g, '' );
		}

		var n = parseFloat( s );
		return isNaN( n ) ? 0 : n;
	}


	function evfSquareGatewaySelectorSlug( $form ) {
		var $r = $form.find( '.evf-payment-gateway-selector-inputs input.evf-payment-gateway-radio:checked' );
		var slug = $r.data( 'evf-gateway' );
		if ( ! slug ) {
			slug = $r.attr( 'data-evf-gateway' );
		}
		return String( slug || '' ).replace( /-/g, '_' );
	}

	function evfSquareCardErrors( formId ) {
		var $byId = $( '#card-errors-square-' + formId );
		if ( $byId.length ) {
			return $byId;
		}
		return $( '#evf-form-' + formId ).find( '#card-errors' ).first();
	}

	function evfSquareFormId( $form ) {
		var idAttr = String( $form.attr( 'id' ) || '' );
		if ( 0 === idAttr.indexOf( 'evf-form-' ) ) {
			return idAttr.replace( 'evf-form-', '' );
		}
		var fromGateway = $form.find( ".everest-forms-gateway[data-gateway='square']" ).first().data( 'form-id' );
		return String( fromGateway || '' );
	}

	function evfSquareIdempotencyKey() {
		if ( window.crypto && typeof window.crypto.randomUUID === 'function' ) {
			return window.crypto.randomUUID();
		}
		var bytes = new Uint8Array( 16 );
		window.crypto.getRandomValues( bytes );
		return Array.from( bytes, function( b ) {
			return ( '0' + b.toString( 16 ) ).slice( -2 );
		} ).join( '' );
	}

	function evfSquareClearPaymentInProgress( $form ) {
		$form.removeData( 'evfSquarePaymentInProgress' );
	}

	var $squareForms = $( "form.everest-form, form.everest-forms" ).filter( function () {
		return $( this ).find( ".everest-forms-gateway[data-gateway='square']" ).length > 0;
	} );

	if ( 0 === $squareForms.length ) {
		return;
	}

	var square_payment = {
		/**
		* Initialization.
		*/
		init: function ( $form ) {
			square_payment.ready( $form );
		},
		/**
		* Document Ready
		*/
		ready: function ( $form ) {
			square_payment.loadSquareCreditCard( $form );
		},

		/**
		* Load credit card
		*/
		loadSquareCreditCard : async function( $form ) {
			let formId 			= evfSquareFormId( $form );
			let cardContainer 	= '#everest_forms_square_gateway_' + formId;
			const appId			= evf_square_payment_obj.app_id;
			const locationId 	= evf_square_payment_obj.location_id;
			var $gatewaySelector = $form.find( '.evf-payment-gateway-selector-inputs' );
			var isSelectorControlled = $gatewaySelector.length > 0;
			var $cardContainer = $( cardContainer );

			if ( ! formId || ! $cardContainer.length || ! window.Square || ! window.Square.payments ) {
				return;
			}

			if ( ! appId || ! locationId ) {
				return;
			}

			// Do not initialize Square when payment method selector is present but Square is not selected.
			if ( isSelectorControlled && 'square' !== evfSquareGatewaySelectorSlug( $form ) ) {
				return;
			}

			// To prevent the duplicate initialization of the Square payment card.
			if ( $cardContainer.data( 'initialized' ) || $form.data( 'evfSquareInitInProgress' ) ) {
				return;
			}
			$form.data( 'evfSquareInitInProgress', true );

			// Use pre-warmed card promise if available (started on page load) so attach is near-instant.
			var prewarmed = evfSquarePrewarmedCards[ formId ] || null;
			delete evfSquarePrewarmedCards[ formId ];

			let payments, card;
			try {
				payments = window.Square.payments( appId, locationId );
				card = prewarmed ? await prewarmed : await payments.card();
				await card.attach( cardContainer );
				$( '.sq-card-message' ).hide();
				$cardContainer.data( 'initialized', true );
			} catch ( e ) {
				$form.removeData( 'evfSquareInitInProgress' );
				return;
			}
			$form.removeData( 'evfSquareInitInProgress' );

			$form.off( 'everest_forms_frontend_payment_before_success_message.evfSquare' ).on( 'everest_forms_frontend_payment_before_success_message.evfSquare', async function( e, xhrData = null ) {

				// If a payment method field (gateway selector) is present, only process when Square is actively chosen.
				var $gatewaySelector = $form.find( '.evf-payment-gateway-selector-inputs' );
				if ( $gatewaySelector.length && 'square' !== evfSquareGatewaySelectorSlug( $form ) ) {
					return;
				}

				if ( $form.data( 'evfSquarePaymentInProgress' ) ) {
					return;
				}
				$form.data( 'evfSquarePaymentInProgress', true );

				const idempotencyKey = evfSquareIdempotencyKey();

				var paymentMethod = $( "#evf-form-" + formId ).find( ".everest-forms-gateway[data-gateway='square']" ).data('gateway');

				if ( 'square' !== paymentMethod ) {
					evfSquareClearPaymentInProgress( $form );
					return;
				}

				e.preventDefault();

				if ( 'submit' === e.type ) {
					evfSquareClearPaymentInProgress( $form );
					return;
				}

				var $mountedElement = $( '#everest_forms_square_gateway_' + formId );
				if ( 'none' === $mountedElement.closest( '.evf-field' ).css( 'display' ) ) {
					evfSquareClearPaymentInProgress( $form );
					return;
				}

				// Subscription flow.
				if ( window.evfSquareRecurringForms && window.evfSquareRecurringForms[ formId ] ) {
					var $checkedPlan = $form.find( '.evf-field-payment-subscription-plan input.evf-payment-price:checked' );
					if ( $checkedPlan.length ) {
						await square_payment.createSquareSubscription( $form, card, formId, xhrData, idempotencyKey );
						return;
					}
				}

				// One-time payment flow.
				var paymentItems = square_payment.getPaymentItems( $( '#evf-form-' + formId ) );

				var totalAmount = 0;
				paymentItems.items.map( function( item ) {
					totalAmount += item.price * item.quantity;
				} );

				if ( 0 === totalAmount ) {
					evfSquareClearPaymentInProgress( $form );
					$( '#evf-form-' + formId )[0].submit();
					return;
				}

				var paymentDetails = {
					data    : $( '#evf-form-' + formId ).serializeArray(),
					form_id : formId,
					total   : totalAmount
				};
				await square_payment.handlePaymentMethodSubmission( e, card, idempotencyKey, paymentDetails, xhrData, formId, locationId );
			});
		},
		handlePaymentMethodSubmission : async function( event, paymentMethod, idempotencyKey, data, xhrData, formId, locationId ) {
			var $form = $( '#evf-form-' + formId );
			event.preventDefault();
			try {
			  const token = await square_payment.tokenize( paymentMethod, formId );
			  await square_payment.createPayment( token, idempotencyKey, data, xhrData, formId, locationId );
			} catch ( e ) {
				square_payment.removeEntryAfterCardDeclined( xhrData );
				evfSquareCardErrors( formId ).html( e.message ).show();
				$( '#evf-submit-' + formId ).attr( 'disabled', false ).html( 'Submit' );
				evfSquareClearPaymentInProgress( $form );
			}
		},
		// Get payments items
		getPaymentItems: function( form ) {
			var paymentItems = [];

			// Payment quantities.
			var $paymentQuantities = $( form ).find( 'input.evf-payment-quantity:visible' );
			var paymentQuantities = [];
			$.each( $paymentQuantities, function( index, paymentQuantity ) {
				var id       = $( paymentQuantity ).attr( 'id' );
				var quantity = $( paymentQuantity ).val();
				var mapField = $( paymentQuantity ).data( 'map_field' );

				quantity = '' === quantity ? 1 : quantity;

				paymentQuantities[ mapField ] = {
					id: id,
					quantity: Number( quantity )
				};
			} );

			var coupon = $( form ).find( '.everest-forms-coupons input' ),
				coupon_discount = coupon.attr( 'data-discount' ),
				is_percent = coupon.attr( 'data-discount_percent' ),
				coupon_map_field = coupon.attr( 'data-discount_map_field' ),
				applied_coupons_data = [],
				$appliedCouponsDataField = $( form ).find( '.applied-coupons-data' );

			if ( $appliedCouponsDataField.length ) {
				try {
					applied_coupons_data = JSON.parse( $appliedCouponsDataField.val() || '[]' );
				} catch ( e ) {
					applied_coupons_data = [];
				}
			}

			var $singleItems = $( form ).find( 'div.evf-field-payment-single' ).filter( function() {
				return $( this ).is(":not([style*='display: none'])") || $( this ).hasClass( 'evf-field-hidden' );
			} );
			var singleItems = $.map( $singleItems, function( singleItem ) {
				var id       = $( singleItem ).attr( 'data-field-id' );
				var label    = $( singleItem ).find( '.evf-field-label' ).text().trim();
				var quantity = ( undefined === paymentQuantities[ id ] ) ? 1 : paymentQuantities[ id ].quantity;
				var price    = $( singleItem ).find('input.evf-payment-price').val();

				price = evfSquareParseAmount( price );

				price = ( '1' === is_percent && id === coupon_map_field ) ? price * ( 100 - coupon_discount ) / 100 : price;

				return {
					field_id: String( id ),
					label: label,
					price: Number( price ),
					quantity: Number( quantity )
				};
			} );
			paymentItems = paymentItems.concat( singleItems );

			var $sliderItems = $( form ).find( 'div.evf-field-range-slider' ).filter( function() {
				return $( this ).is(":not([style*='display: none'])") && $( this ).find('input.evf-payment-price').length > 0;
			} );
			var sliderItems = $.map( $sliderItems, function( sliderItem ) {
				var id       = $( sliderItem ).attr( 'data-field-id' );
				var label    = $( sliderItem ).find( '.evf-field-label' ).text().trim();
				var quantity = ( undefined === paymentQuantities[ id ] ) ? 1 : paymentQuantities[ id ].quantity;
				var price    = $( sliderItem ).find('input.evf-payment-price').val();

				price = evfSquareParseAmount( price );

				price = ( '1' === is_percent && id === coupon_map_field ) ? price * ( 100 - coupon_discount ) / 100 : price;

				return {
					field_id: String( id ),
					label: label,
					price: Number( price ),
					quantity: Number( quantity )
				};
			} );
			paymentItems = paymentItems.concat( sliderItems );

			var $paymentPrices = $( form ).find( "div.evf-field:not([style*='display: none']) input.evf-payment-price").filter( ':not(.evf-payment-quantity)' ).filter( ':checked' );
			var paymentPrices  = $.map( $paymentPrices, function( paymentPrice ) {
				var id       = $( paymentPrice ).parents( 'div.evf-field' ).first().attr( 'data-field-id' );
				var quantity = ( undefined === paymentQuantities[ id ] ) ?  1 : paymentQuantities[ id ].quantity;
				var price    = $( paymentPrice ).data( 'amount' );

				var $label   = $( paymentPrice ).siblings( '.everest-forms-field-label-inline' ).first();

				if ( ! $label.length ) {
					$label = $( paymentPrice ).siblings( '.everest-forms-image-choices-label' ).first();
				}

				price = evfSquareParseAmount( price );

				price = ( '1' === is_percent && id === coupon_map_field ) ? price * ( 100 - coupon_discount ) / 100 : price;

				return {
					field_id: String( id ),
					label: $label.text(),
					price: Number( price ),
					quantity: quantity
				};
			} );
			paymentItems = paymentItems.concat( paymentPrices );

			if ( applied_coupons_data.length ) {
				$.each( applied_coupons_data, function( index, applied_coupon ) {
					var discount_type = applied_coupon.discount_type || '';
					var amount = parseFloat( applied_coupon.amount ) || 0;
					var map_field = applied_coupon.map_field || '';

					// Field-specific percent coupon.
					if ( 'percent' === discount_type && '' !== map_field ) {
						paymentItems = $.map( paymentItems, function( item ) {
							if ( String( item.field_id ) === String( map_field ) ) {
								item.price = item.price * ( 100 - amount ) / 100;
							}
							return item;
						} );
						return;
					}

					// Cart-wide coupon.
					if ( '' === map_field ) {
						var total = 0,
							title = paymentItems.map( function( item ) {
								total += item.price * item.quantity;
								return item.label;
							} ),
							total_after_discount = 0;

						if ( 'percent' === discount_type ) {
							total_after_discount = total * ( 100 - amount ) / 100;
						} else {
							total_after_discount = total - amount;
						}

						if ( total_after_discount < 0 ) {
							total_after_discount = 0;
						}

						paymentItems = [{
							label: title.join( ', ' ),
							price: Number( total_after_discount ),
							quantity: 1
						}];
					}
				} );
			} else if ( undefined !== coupon_discount && coupon_discount > 0 && ( undefined === coupon_map_field || '' === coupon_map_field ) ) {
				var total = 0,
					title = paymentItems.map( function( item ) {
						total += item.price * item.quantity;
						return item.label;
					} ),
					total_after_discount = 0;

				if ( '1' === is_percent ) {
					total_after_discount = total * ( 100 - coupon_discount ) / 100;
				} else {
					total_after_discount = total - coupon_discount;
				}

				if ( total_after_discount < 0 ) {
					total_after_discount = 0;
				}

				paymentItems = [{
					label: title.join( ', ' ),
					price: Number( total_after_discount ),
					quantity: 1
				}];
			}

			var payment = {
				items: paymentItems
			};

			var $paymentTotal = $(form).find('.evf-field-payment-total');

			if (0 !== $paymentTotal.length) {
				var hiddenField = $paymentTotal.find('input[type="hidden"]');

				if (hiddenField.length) {
					payment['total'] = evfSquareParseAmount( hiddenField.val() );
				} else {
					var hiddenTextField = $paymentTotal.find('input[type="text"]');

					if (hiddenTextField.length && hiddenTextField.attr('readonly')) {
						payment['total'] = evfSquareParseAmount( hiddenTextField.val() );
					}
				}
			}

			return payment;
	},
	createPayment : async function( token, idempotencyKey, data, xhrData, formId, locationId ) {
		const payment_data = {
			location_id 	: locationId,
			source_id		: token,
			idempotency_key : idempotencyKey,
			action 			: 'everest_forms_square_payment_credit_card',
			payment_data	: data,
			security 		: evf_square_payment_obj.security
			};

			var $form = $( '#evf-form-' + formId );

			$.ajax({
				url : evf_square_payment_obj.ajax_url,
				method: 'POST',
				data : payment_data,
				success : function ( response ) {

					if ( ! xhrData ) {
						return;
					}

					if( response.success ){
						var data_to_update = {
							action : 'evf_square_update_entry_square_payment_status',
							security : evf_square_payment_obj.security,
							entry_id : xhrData.entry_id,
							payment_data : response.data
						}

						$.ajax({
							url: evf_square_payment_obj.ajax_url,
							method : 'POST',
							data : data_to_update,
							success : function ( response ){
								$form.css( 'display', 'block' );
								var redirect_url = ( xhrData && xhrData.redirect_url && 'undefined' !== xhrData.redirect_url ) ? xhrData.redirect_url : '';
								if ( redirect_url ) {
									window.location = redirect_url;
									return;
								}

								let pdf_download_message = '';
								let quiz_reporting = '';
								if(xhrData.form_id !== undefined && xhrData.entry_id !== undefined && xhrData.pdf_download == true){
									pdf_download_message = '<br><small><a href="/?page=evf-entries-pdf&form_id='+ xhrData.form_id+'&entry_id='+ xhrData.entry_id+'">' + xhrData.pdf_download_message + '</a></small>';
								}

								if( xhrData.quiz_result_shown == true){
									quiz_reporting = xhrData.quiz_reporting;
								}

								$form.html( '<div class="everest-forms-notice everest-forms-notice--success" role="alert">' + xhrData.message + pdf_download_message + '</div>' + quiz_reporting ).focus();
								evfSquareClearPaymentInProgress( $form );
							},
							// Without this, a failed status-update AJAX leaves the button stuck on "Processing".
							error : function () {
								$( '#evf-submit-' + formId ).attr( 'disabled', false ).html( 'Submit' );
								evfSquareClearPaymentInProgress( $form );
							}
						})

					} else {
						if( '0' === response ){
							evfSquareClearPaymentInProgress( $form );
							return;
						}
						$form.html( '<div class="everest-forms-notice everest-forms-notice--error" role="alert">'+ square_payment.getErrorMessage( response ) +'</div>' ).focus();
						$( '#evf-submit-' + formId ).attr( 'disabled', false ).html( 'Submit' );
						evfSquareClearPaymentInProgress( $form );
					}
				},
				error: function() {
					$form.html( '<div class="everest-forms-notice everest-forms-notice--error" role="alert">An error occurred. Please try again.</div>' ).focus();
					$( '#evf-submit-' + formId ).attr( 'disabled', false ).html( 'Submit' );
					evfSquareClearPaymentInProgress( $form );
				}
			});
		},
		// Extract a human-readable error from an AJAX error response, regardless of shape.
		// Square API errors arrive as data: [{ detail }]; guard/validation errors as data: { message }.
		getErrorMessage : function( response ) {
			var fallback = 'Payment could not be completed. Please try again.';
			if ( ! response || ! response.data ) {
				return fallback;
			}
			var data = response.data;
			if ( Array.isArray( data ) ) {
				return ( data[0] && ( data[0].detail || data[0].message ) ) || fallback;
			}
			if ( 'object' === typeof data ) {
				return data.message || data.detail || fallback;
			}
			if ( 'string' === typeof data && data ) {
				return data;
			}
			return fallback;
		},
		tokenize : async function( paymentMethod, formId ) {
			const tokenResult = await paymentMethod.tokenize();
			if ( tokenResult.status === 'OK' ) {
				return tokenResult.token;
			} else {
				if ( tokenResult.errors ) {
					const errorMessage = tokenResult.errors[0].message;
					throw new Error( errorMessage );
				}
			}
		},
		//remove entries after credit card is declined
		removeEntryAfterCardDeclined : function( xhrData ){
			var all_data = {
				entry_id : xhrData.entry_id,
				form_id : xhrData.form_id,
				action : 'everest_forms_square_payment_delete_entry_after_failed',
				security : evf_square_payment_obj.security
			}
			$.ajax({
				url : evf_square_payment_obj.ajax_url,
				method: 'POST',
				data : all_data,
				success : function( response ){
					// do nothing
				}
			})
		},

		createSquareSubscription: async function( $form, card, formId, xhrData, idempotencyKey ) {
			var $checkedPlan   = $form.find( '.evf-field-payment-subscription-plan input.evf-payment-price:checked' );
			var fieldId        = $checkedPlan.closest( '[data-field-id]' ).data( 'field-id' );
			var choiceKey      = $checkedPlan.val();

			var token;
			try {
				token = await square_payment.tokenize( card, formId );
			} catch ( tokenErr ) {
				evfSquareCardErrors( formId ).html( tokenErr.message ).show();
				$( '#evf-submit-' + formId ).attr( 'disabled', false ).html( 'Submit' );
				evfSquareClearPaymentInProgress( $form );
				return;
			}

			// Extract customer fields directly from DOM.
			var customerGivenName  = '';
			var customerFamilyName = '';
			var customerEmail      = '';
			var customerPhone      = '';

			var $emailInput = $form.find( 'input[type="email"]:visible' ).first();
			if ( $emailInput.length ) { customerEmail = $emailInput.val(); }

			var $firstNameInput = $form.find( '.evf-field-first-name input:visible, .evf-field-name input[name*="[first]"]:visible' ).first();
			if ( $firstNameInput.length ) { customerGivenName = $firstNameInput.val(); }

			var $lastNameInput = $form.find( '.evf-field-last-name input:visible, .evf-field-name input[name*="[last]"]:visible' ).first();
			if ( $lastNameInput.length ) { customerFamilyName = $lastNameInput.val(); }

			var $phoneInput = $form.find( 'input[type="tel"]:visible' ).first();
			if ( $phoneInput.length ) { customerPhone = $phoneInput.val(); }

			$.ajax( {
				url:  evf_square_payment_obj.ajax_url,
				type: 'POST',
				data: {
					action:               'evf_square_create_subscription',
					security:             evf_square_payment_obj.security,
					form_id:              formId,
					entry_id:             xhrData ? xhrData.entry_id : '',
					field_id:             fieldId,
					choice_key:           choiceKey,
					source_id:            token,
					idempotency_key:      String( idempotencyKey ),
					location_id:          evf_square_payment_obj.location_id,
					customer_given_name:  customerGivenName,
					customer_family_name: customerFamilyName,
					customer_email:       customerEmail,
					customer_phone:       customerPhone
				},
				success: function( response ) {
					if ( response.success ) {
						var $formWrap = $form.closest( '.everest-forms' ).length
							? $form.closest( '.everest-forms' )
							: $form;
						var redirect_url = ( xhrData && xhrData.redirect_url && 'undefined' !== xhrData.redirect_url ) ? xhrData.redirect_url : '';
						if ( redirect_url ) {
							window.location = redirect_url;
							return;
						}
						$formWrap.html( '<div class="everest-forms-notice everest-forms-notice--success" role="alert">' + ( xhrData ? xhrData.message : '' ) + '</div>' ).focus();
						evfSquareClearPaymentInProgress( $form );
					} else {
						var msg = response.data && response.data.message
							? response.data.message
							: 'Subscription failed. Please try again.';
						evfSquareCardErrors( formId ).html( msg ).show();
						$( '#evf-submit-' + formId ).attr( 'disabled', false ).html( 'Submit' );
						evfSquareClearPaymentInProgress( $form );
					}
				},
				error: function() {
					evfSquareCardErrors( formId ).html( 'An error occurred. Please try again.' ).show();
					$( '#evf-submit-' + formId ).attr( 'disabled', false ).html( 'Submit' );
					evfSquareClearPaymentInProgress( $form );
				}
			} );
		}
   }

	// Pre-warm card instances for gateway-selector forms so the network round-trip
	// to Square completes before the user clicks, making card.attach() near-instant.
	var evfSquarePrewarmedCards = {};

	function evfSquarePrimeCard( $form ) {
		if ( ! window.Square || ! window.Square.payments ) {
			return;
		}
		var fid        = evfSquareFormId( $form );
		var appId      = evf_square_payment_obj.app_id;
		var locationId = evf_square_payment_obj.location_id;
		if ( ! fid || ! appId || ! locationId ) {
			return;
		}
		try {
			var payments = window.Square.payments( appId, locationId );
			evfSquarePrewarmedCards[ fid ] = payments.card().catch( function () {
				delete evfSquarePrewarmedCards[ fid ];
			} );
		} catch ( e ) {
			// invalid credentials — will surface properly on actual init
		}
	}

	$squareForms.each( function () {
		var $containingForm = $( this );
		if ( $containingForm.find( '.evf-payment-gateway-selector-inputs' ).length ) {
			evfSquarePrimeCard( $containingForm );
			$( document ).on( 'evf_square_gateway_selected', function ( e, $form ) {
				if ( $containingForm.is( $form ) && 'square' === evfSquareGatewaySelectorSlug( $form ) ) {
					square_payment.init( $containingForm );
				}
			} );
			return;
		}

		square_payment.init( $containingForm );
	} );
});

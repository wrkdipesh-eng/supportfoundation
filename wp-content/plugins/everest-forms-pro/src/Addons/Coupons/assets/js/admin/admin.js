/**
 * EverestFormsCouponAdmin JS
 */
 ( function ($, params, data ) {
	params = everest_forms_coupons_params;
	data   = everest_forms_coupons_params.currency_info;

	var EverestFormsCouponAdmin = {
		init: function () {
			$( document ).on( 'input', '.everest-forms-field-option-row-button_text input', function() {
				var val = $( this ).val();
				var fieldId = $( this ).parent().attr( 'data-field-id' );
				$builder.find( '#everest-forms-field-' + fieldId + ' .evf-coupon-apply' ).html( val );
			});

		   // Document ready.
		   $(document).ready(EverestFormsCouponAdmin.ready);

		   // Build UI Actions.
		   EverestFormsCouponAdmin.bindUIActions();

		},

		ready: function() {

			// Coupon Start Date.
			$('.evf-coupon-start-date').addClass('flatpickr-field').flatpickr({
				disableMobile : true,
				onChange      : function(selectedDates, dateStr, instance) {
					$('.evf-coupon-start-date').val(dateStr);
				},
				onOpen: function(selectedDates, dateStr, instance) {
					instance.set('maxDate', $('.evf-coupon-end-date').val());
				},
			});

			// Coupon End Date.
			$('.evf-coupon-end-date').addClass('flatpickr-field').flatpickr({
				disableMobile : true,
				onChange      : function(selectedDates, dateStr, instance) {
					$('.evf-coupon-end-date').val(dateStr);
				},
				onOpen: function(selectedDates, dateStr, instance) {
					instance.set('minDate', $('.evf-coupon-start-date').val());
				},
			});

			// Apply Select2 on Applicable forms.
			$( '.evf-enhanced-select').select2({ "width": "40%" });

			// Coupons form field error message.
			$('.evf-field').each(function () {
				var $this = $(this);

				if (0 < $this.find('label.evf-error').length) {
					$this.addClass('everest-forms-invalid evf-has-error');
				}
			});

			$(document).on('change', '#filter-by-coupon-status', function () {
				var status = $(this).val();
				var url = new URL(window.location.href);

				if (status !== '') {
					url.searchParams.set('coupon_status', status);
				} else {
					url.searchParams.delete('coupon_status');
				}

				window.location.href = url.toString();
			});
		},

		bindUIActions: function() {
			$(document.body).on( 'focusout', '.evf-coupon-code', function () {
				var code = $(this).val();
				$( this ).val(  code.replace(/[^A-Za-z0-9]/g, '') );
			});

			$(document.body).on(
                "click",
                "#everest-forms-coupons__create-btn",
                function(event) {
                    EverestFormsCouponAdmin.coupon_option(event);
                }
            );

			$(document).ready(function() {
				var $paginationDiv = $('.tablenav-pages.one-page').first();
				var $bulkActionDiv = $('.tablenav.top');

				if ($('.coupons').length > 0) {
					$paginationDiv.add($bulkActionDiv).wrapAll('<div class="coupon-pagination__wrapper"></div>');
					$('#everest-forms-coupons__bulk-pagination').append($('.coupon-pagination__wrapper'));
				}

				if ($('.no-pages').length > 0) {
					$('.coupon-pagination__wrapper').hide();
				} else {
					$('.coupon-pagination__wrapper').show();
				}

				if($('.everest-forms_page_evf-coupons').length>0){
					$('#show-settings-link').hide();
				}

				$('#evf-coupon__screen-options').on('click', function() {
					if ($('#screen-meta').css('display') === 'none') {
						$('#screen-meta').css('display', 'block');
					} else {
						$('#screen-meta').css('display', 'none');
					}

					if ($('#screen-options-wrap').css('display') === 'none') {
						$('#screen-options-wrap').css('display', 'block');
					} else {
						$('#screen-options-wrap').css('display', 'none');
					}
				});

			});

			$(document).on('keydown', '#search_input-search-input', function(event) {
				if (event.key === 'Enter') {
					event.preventDefault();
					var searchValue = $(this).val();
					var $form = $(this).closest('form');
					$form.submit();
				}
			});

			$(document).ready(function() {
				$('input[name="coupons[discount_type]"]').each(function() {
				  if ($(this).is(':checked')) {
					$(this).closest('label').addClass('everest-forms-coupon-discount-active');
				  }
				});

				$(document.body).on('change', 'input[name="coupons[discount_type]"]', function() {
				  $('.evf-coupon-discount__type').removeClass('everest-forms-coupon-discount-active');
				  $(this).closest('label').addClass('everest-forms-coupon-discount-active');
				});
			  });


			$(document.body).on( 'change', '.evf-coupon-discount-amount ,input[name="coupons[discount_type]"]', function () {
				var amount       = $( '.evf-coupon-discount-amount' ).val();
				var discountType = $( 'input[name="coupons[discount_type]"]:checked' ).val();

				if( 'percent' === discountType ) {
					var sanitized_percent = parseFloat( amount.replace(/[^\d\.]/g, '').replace(/\.(([^\.]*)\.)*/g, '.$2') );

					if( ! isNaN(sanitized_percent) ) {
						if( /\./.test( sanitized_percent ) && 2 < sanitized_percent.toString().split( '.' )[1].length ) {
							$( '.evf-coupon-discount-amount' ).val( sanitized_percent.toFixed( 2 ).toString() + '%' );
						} else {
							$( '.evf-coupon-discount-amount' ).val( sanitized_percent.toString() + '%' );
						}
					} else {
						$( '.evf-coupon-discount-amount' ).val( '' );
					}
				} else {
					var sanitized_amount  = EverestFormsCouponAdmin.amountSanitize( amount ),
						formatted_amount  = EverestFormsCouponAdmin.amountFormat( sanitized_amount );

					if( 0 < parseFloat(formatted_amount) ) {
						var currency_symbol = $("<textarea/>").html( data.currency_symbol ).val();

						if ( 'right' === data.currency_symbol_pos ) {
							formatted_amount = formatted_amount + ' ' + currency_symbol;
						} else {
							formatted_amount = currency_symbol + ' ' + formatted_amount;
						}

						$( '.evf-coupon-discount-amount' ).val( formatted_amount );
					} else {
						$( '.evf-coupon-discount-amount' ).val( '' );

					}
				}
			});
		},

		/**
		 * Sanitize amount and convert to standard format for calculations.
		 *
		 */
		 amountSanitize: function( amount ) {
			amount = amount.replace( /[^0-9.,]/g, '' );

			if ( ',' === data.currency_decimal && ( -1 !== amount.indexOf( data.currency_decimal ) ) ) {
				if ( '.' === data.currency_thousands && -1 !== amount.indexOf( data.currency_thousands ) ) {;
					amount = amount.replace( data.currency_thousands, '' );
				} else if ( '' === data.currency_thousands && -1 !== amount.indexOf( '.' ) ) {
					amount = amount.replace( '.', '' );
				}
				amount = amount.replace( data.currency_decimal, '.' );
			} else if ( ',' === data.currency_thousands && ( -1 !== amount.indexOf( data.currency_thousands ) ) ) {
				amount = amount.replace( data.currency_thousands, '' );
			}

			return EverestFormsCouponAdmin.numberFormat( amount, 2, '.', '' );
		},

		/**
		 * Format amount.
		 *
		 */
		amountFormat: function( amount ) {
			amount = String( amount );

			// Format the amount.
			if ( ',' === data.currency_decimal && ( -1 !== amount.indexOf( data.currency_decimal ) ) ) {
				var sepFound = amount.indexOf( data.currency_decimal );
					whole    = amount.substr( 0, sepFound );
					part     = amount.substr( sepFound+1, amount.strlen - 1 );
					amount   = whole + '.' + part;
			}

			// Strip ',' from the amount (if set as the thousands separator).
			if ( ',' === data.currency_thousands && ( -1 !== amount.indexOf( data.currency_thousands ) ) ) {
				amount = amount.replace( ',', '' );
			}

			if ( ! amount ) {
				amount = 0;
			}

			return EverestFormsCouponAdmin.numberFormat( amount, 2, data.currency_decimal, data.currency_thousands );
		},

		/**
		 * Format number.
		 *
		 * @link http://locutus.io/php/number_format/
		 */
		 numberFormat: function ( number, decimals, decimalSep, thousandsSep ) {
			number   = (number + '').replace(/[^0-9+\-Ee.]/g, '');
			var n    = ! isFinite( +number ) ? 0 : +number;
			var prec = ! isFinite( +decimals ) ? 0 : Math.abs(decimals);
			var sep  = ( 'undefined' === typeof thousandsSep ) ? ',' : thousandsSep;
			var dec  = ( 'undefined' === typeof decimalSep ) ? '.' : decimalSep;
			var s    = '';

			var toFixedFix = function ( n, prec ) {
				var k = Math.pow( 10, prec );
				return '' + ( Math.round(n * k) / k ).toFixed( prec )
			};

			// @todo: for IE parseFloat(0.55).toFixed(0) = 0;
			s = ( prec ? toFixedFix( n, prec ) : '' + Math.round(n) ).split( '.' );
			if ( s[0].length > 3 ) {
				s[0] = s[0].replace( /\B(?=(?:\d{3})+(?!\d))/g, sep )
			}
			if ( (s[1] || '' ).length < prec ) {
				s[1]  = s[1] || '';
				s[1] += new Array( prec - s[1].length + 1 ).join( '0' );
			}

			return s.join( dec )
		},

		coupon_option: function (event) {
			event.preventDefault();

			$target = $(event.target);

			$.dialog({
				title: 'Choose Coupon Type',
				theme: 'jconfirm-modern jconfirm-everest-forms-coupon__left ',
				backgroundDismiss: false,
				scrollToPreviousElement: false,
				content: '' +
					'<form class="everest-forms-coupons__form">' +
						'<div class="everest-forms-coupons__create-coupon" id="everest-forms-coupons-create__single_coupon">' +
							'<input id="create_single_coupon" type="radio" name="coupon-type" value="single-coupon"> <span class="coupon-option__text">Add a Single Coupon</span>' +
							'<p class="everest-forms-coupon__description">Create one coupon at a time, perfect for when you need just a single discount code.</p>' +
						'</div>' +
						'<div class="everest-forms-coupons__create-coupon" id="everest-forms-coupons-create__bulk_coupon">' +
							'<label>' +
								'<input id="create_bulk_coupon" type="radio" name="coupon-type" value="bulk-coupon"> <span class="coupon-option__text">Add Coupons in Bulk</span>' +
							'<p class="everest-forms-coupon__description">Generate multiple coupons at once, ideal for campaigns or large-scale promotions.</p>' +
						'</div>' +
						'</form>' +
						'<div class="everest-forms-coupon__submit">' +
							'<button id="create-coupon" type="submit" class="btn everest-forms-btn everest-forms-btn-primary">Continue</button>' +
						'</div>',

						onContentReady: function () {
							var selectedCouponType = '';

							this.$content.find('input[name="coupon-type"]').on('change', function () {
								$('.everest-forms-coupons__create-coupon').removeClass('everest-forms-coupon__active');

								selectedCouponType = $('input[name="coupon-type"]:checked').val();

								$(this).closest('.everest-forms-coupons__create-coupon').addClass('everest-forms-coupon__active');
							});

							this.$content.find('#everest-forms-coupons-create__single_coupon').on('click', function () {
								$('#everest-forms-coupons-create__bulk_coupon').removeClass('everest-forms-coupon__active');
								$('#everest-forms-coupons-create__bulk_coupon').find('#create_bulk_coupon').prop('checked', false);

								$('#everest-forms-coupons-create__single_coupon').addClass('everest-forms-coupon__active');
								$('#everest-forms-coupons-create__single_coupon').find('#create_single_coupon').prop('checked', true);

								selectedCouponType = $('input[name="coupon-type"]:checked').val();

								$(this).closest('.everest-forms-coupons__create-coupon').addClass('everest-forms-coupon__active');
							});

							this.$content.find('#everest-forms-coupons-create__bulk_coupon').on('click', function () {
								$('#everest-forms-coupons-create__single_coupon').removeClass('everest-forms-coupon__active');
								$('#everest-forms-coupons-create__single_coupon').find('#create_single_coupon').prop('checked', false);

								$('#everest-forms-coupons-create__bulk_coupon').addClass('everest-forms-coupon__active');
								$('#everest-forms-coupons-create__bulk_coupon').find('#create_bulk_coupon').prop('checked', true);

								selectedCouponType = $('input[name="coupon-type"]:checked').val();

								$(this).closest('.everest-forms-coupons__create-coupon').addClass('everest-forms-coupon__active');
							});

							this.$content.find('#create-coupon').on('click', function () {
								if (selectedCouponType) {
									var redirectUrls = {
										'single-coupon': params.admin_url + 'admin.php?page=evf-coupons&create-coupon=0',
										'bulk-coupon': params.admin_url + 'admin.php?page=evf-coupons&create-bulk-coupon=0'
									};

									window.location.href = redirectUrls[selectedCouponType];
									this.close();
								} else {
									alert('Please select a coupon generation type');
								}
							});
						}

			});
		},
	};

	// Initialize.
	EverestFormsCouponAdmin.init(jQuery);

	// End of popup modal.
})(jQuery);

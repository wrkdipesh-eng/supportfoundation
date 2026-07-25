/**
 * EverestFormsSaveAndContinue JS
 */
 ( function ($) {
	var EverestFormsCoupons = {
		init: function () {
			var form = $("form.everest-form");

			form.each(function (i, v) {
				$(document).ready(function () {

					// Forms.
					var formTuple = $(v),
						form_id = formTuple.attr( 'data-formid' ),
						btn = formTuple.find( '.evf-coupon-apply' );

					//Validate Coupon.
					btn.on( 'click', function (e) {

						$this = $(this);

						e.preventDefault();

						var $form = $this.closest( 'form' );
						var $couponInput = $this.prev( '.everest-forms-coupons-wrapper' ).find( '.evf-payment-coupon' );
						var $appliedField = $form.find( '.applied-coupon-codes' );
						var $appliedCodesField = $form.find('.applied-coupon-codes');
						var $appliedCouponsDataField = $form.find('.applied-coupons-data');

						var $couponField = $this.closest('.evf-field');
						var couponFieldId = $couponField.attr('data-field-id');
						var $appliedCouponsList = $couponField.find('.evf-applied-coupons-list, .evf-coupon-error-message-container');

						EverestFormsCoupons.clearCouponErrors( $couponField );

						if ( ! $appliedCouponsList.length ) {
							$this.closest('.everest-forms-coupons').after('<div class="evf-applied-coupons-list"></div>');
							$appliedCouponsList = $couponField.find('.evf-applied-coupons-list');
						}

						if( '' === $couponInput.val() ) {
							return;
						}

						if ( ! $appliedCodesField.length ) {
							$form.append('<input type="hidden" name="applied_coupon_codes" class="applied-coupon-codes" value="[]">');
							$appliedCodesField = $form.find('.applied-coupon-codes');
						}

						if ( ! $appliedCouponsDataField.length ) {
							$form.append('<input type="hidden" name="applied_coupons_data" class="applied-coupons-data" value="[]">');
							$appliedCouponsDataField = $form.find('.applied-coupons-data');
						}

						var applied_coupons = $appliedCodesField.val() || '[]',
							applied_coupons_array = JSON.parse( applied_coupons ),
							coupon_code = $.trim( $couponInput.val() ).toLowerCase(),
							applied_coupons_data = $appliedCouponsDataField.val() || '[]',
							applied_coupons_data_array = JSON.parse( applied_coupons_data );

						if ( applied_coupons_array.includes( coupon_code ) ) {
							$this.closest( '.everest-forms-coupons' ).siblings( '.evf-coupon-error' ).remove();
							$(
							'<div class="evf-error-box">' +
								'<span class="evf-error-text">' + everest_forms_coupons_params.i18n_already_applied + '</span></div>'
							).insertAfter($this.closest('.everest-forms-coupons'));
							return;
						}

						// $this.prop( 'disabled', true );

						// Change the text to user defined property.
						$this.html( 'applying' );
						$this.prop( 'disabled', true );

						// $this.closest('.everest-forms-coupons' ).find( 'input' ).prop( 'readonly', true );

						$this.closest( '.everest-forms-coupons' ).siblings( '.evf-error' ).remove();
						$this.closest( '.everest-forms-coupons' ).siblings( '.evf-coupon-error' ).remove();

						var data = [];

						// Add action intend for ajax_form_submission endpoint.
						data.push( {
							name: "action",
							value: "everest_forms_coupons_apply",
						} );

						var $form = $this.closest('.everest-form');

						var total = EverestFormsCoupons.evfCalculateTotal($form);

						if ( Number(total) <= 0 ) {
							$('<div class="evf-error-box">' +
								'<span class="evf-error-text">' + everest_forms_coupons_params.i18n_select_option_first + '</span></div>'
							).insertAfter($this.closest('.everest-forms-coupons'));

							$this.prop("disabled", false);
							$this.html($this.attr('data-text'));
							return;
						}

						data.push( {
							name: "form_id",
							value: form_id,
						} );

						data.push( {
							name: "coupon_code",
							value: $this.prev( '.everest-forms-coupons-wrapper' ).find( '.evf-payment-coupon' ).val()
						} );

						data.push( {
							name: "coupon_id",
							value: $this.closest( '.evf-field' ).attr( 'data-field-id' )
						} );

						data.push( {
							name: "nonce",
							value: everest_forms_coupons_params.nonce,
						} );

						// Fire the ajax request.
						$.ajax( {
							url: everest_forms_coupons_params.ajax_url,
							type: "POST",
							data: data,
						} )
							.done( function (xhr, textStatus, errorThrown ) {
								if ( true == xhr.success ) {
									var type = xhr.data.discount_type,
										amount = xhr.data.amount,
										minimum_purchase = xhr.data.minimum_purchase,
										currency_symbol = xhr.data.currency_symbol,
										currentCouponCode = xhr.data.coupon_code,
										isStackable = xhr.data.stackable;

									var normalizedIsStackable =
										true === isStackable ||
										1 === isStackable ||
										'1' === isStackable ||
										'yes' === isStackable ||
										'on' === isStackable ||
										'true' === String(isStackable).toLowerCase();

									var hasAppliedCoupons = applied_coupons_data_array.length > 0;
									var hasNonStackableApplied = applied_coupons_data_array.some(function(coupon) {
										var couponStackable = coupon && (
											true === coupon.stackable ||
											1 === coupon.stackable ||
											'1' === coupon.stackable ||
											'yes' === coupon.stackable ||
											'on' === coupon.stackable ||
											'true' === String(coupon.stackable).toLowerCase()
										);

										return ! couponStackable;
									});

									// Stackable rules:
									// 1. If any previously applied coupon is non-stackable, do not allow next coupon.
									// 2. If previous applied coupons are stackable but current coupon is non-stackable, do not allow it.
									// 3. Only allow multiple coupons when all applied + current coupons are stackable.
									if ( hasNonStackableApplied ) {
										$this.closest( '.everest-forms-coupons' ).siblings( '.evf-coupon-error' ).remove();
										$(
										'<div class="evf-error-box">' +
											'<span class="evf-error-text">' + everest_forms_coupons_params.i18n_cannot_stack + '</span></div>'
										).insertAfter($this.closest('.everest-forms-coupons'));
										$this.html( $this.attr( 'data-text') );
										return;
									}

									if ( hasAppliedCoupons && ! normalizedIsStackable ) {
										$(
										'<div class="evf-error-box">' +
											'<span class="evf-error-text">' + everest_forms_coupons_params.i18n_not_stack_able + '</span></div>'
										).insertAfter($this.closest('.everest-forms-coupons'));
										$this.html( $this.attr( 'data-text') );
										return;
									}

									var $totalField = $('.evf-field-payment-total');
									var currentTotal;

									if ($totalField.length) {
										var rawValue = $totalField.find('input.evf-payment-total').val();

										currentTotal = parseFloat(rawValue.replace(/[^0-9.]/g, ''));
									} else {
										currentTotal = EverestFormsCoupons.evfCalculateTotal($this.closest('form'));
									}


									if ( minimum_purchase && currentTotal < Number( minimum_purchase ) ) {
										$( '<div class="evf-error-box">' +
											'<span class="evf-error-text">Minimum purchase of ' + currency_symbol + minimum_purchase + ' required</span></div>'
										).insertAfter($this.closest('.everest-forms-coupons'));
										$this.prop("disabled", false);
										$this.html($this.attr('data-text'));

										return;
									}

									if( 'fixed' === type ) {
										var cleaned = amount
											.replace(/&#\d+;/g, '')
											.replace(/[^\d.,]/g, '');

										if (cleaned.indexOf(',') > cleaned.indexOf('.')) {
											cleaned = cleaned.replace(/\./g, '').replace(',', '.');
										} else {
											cleaned = cleaned.replace(/,/g, '');
										}

										var discount_amount_sanitized = parseFloat(cleaned) || 0;
										var discount_amount_sanitized = parseFloat(amount.match(/\;[\d|.\,]+/)[0].replace(';', ''));
										$this.closest('.everest-forms-coupons').find( 'input' ).attr( 'data-discount', discount_amount_sanitized ).attr( 'data-minimum-purchase', minimum_purchase ).addClass('evf-coupon-applied');
									} else {
										if( '' === xhr.data.map_field ) {
											var discount_percent_sanitized = amount.replace( '%', '');
											$this.closest('.everest-forms-coupons').find( 'input' ).attr( 'data-discount', discount_percent_sanitized )
											.attr( 'data-discount_percent', '1' ).attr( 'data-minimum-purchase', minimum_purchase ).addClass('evf-coupon-applied');
										} else {
											var total_product_amount = '0';
											var discount_percent_sanitized = amount.replace( '%', ''),
												map_field =  xhr.data.map_field,
												field = $this.closest( 'form' ).find( 'div[data-field-id="' + map_field + '"] .evf-payment-price:enabled, div[data-field-id="' + map_field + '"] .evf-single-item-price-hidden input[type="hidden"]:enabled');
											if ( 'text' === field.attr( 'type' ) || 'hidden' === field.attr( 'type' ) ) {
												total_product_amount = field.val();
											} else if ( 'radio' === field.attr( 'type' ) ) {
												var checkedField = field.filter( ':checked' );
												if ( checkedField.length ) {
													total_product_amount = checkedField.data( 'amount' );
												}
											} else if( field.closest( '.evf-field' ).is( '.evf-field-payment-checkbox' ) ) {
												total_product_amount = 0;
												field.closest( '.evf-field' ).find( '.evf-payment-price:checked' ).each( function() {
													amount = $( this ).attr( 'data-amount' );
													if ( ! isNaN( parseFloat( amount ) ) ) {
														map_field_amount = parseFloat( amount );
														total_product_amount += map_field_amount;
													}
												} );
												total_product_amount = total_product_amount.toString();
											} else if ( field.is( 'select' ) && field.find( 'option:selected' ).length > 0 ) {
												total_product_amount = field.find( 'option:selected' ).data( 'amount' );
											} else if ( 'number' === field.attr( 'type' ) ) {
												if( field.data( 'enable_payment_slider' ) ) {
													total_product_amount = field.val();
												}
											}
											var total_product_amount_number = total_product_amount.match(/[\d|.\,]+/)[0],
												total_product_amount_sanitized = total_product_amount_number.replace(',', '');
											if ( $this.closest( 'form' ).find( '.evf-payment-quantity' ).length ) {
												$this.closest( 'form' ).find( '.evf-payment-quantity' ).each(function( index, el ) {
													if ( 0 <= $( el).val() ) {
														if( map_field === $( el).attr( 'data-map_field' ) ) {
															total_product_amount_sanitized = total_product_amount_sanitized * $( el ).val();
														}
													}
												} );
											}
											$this.closest('.everest-forms-coupons').find( 'input' ).attr( 'data-discount', discount_percent_sanitized )
											.attr( 'data-discount_percent', '1' ).attr( 'data-minimum-purchase', minimum_purchase ).addClass('evf-coupon-applied')
											.attr( 'data-discount_map_field', map_field );
										}
									}
									var returnedCouponCode = $.trim( xhr.data.coupon_code || '') ;

									$(
									'<div class="evf-applied-coupon" data-code="' + returnedCouponCode + '" data-field-id="' + couponFieldId + '">' +

										'<span class="evf-coupon-name">' + returnedCouponCode + '</span>' +

										'<span class="evf-coupon-discount">' + xhr.data.message + '</span>' +

										'<span class="evf-remove-applied-coupon" title="Remove coupon" role="button" tabindex="0" data-code="' + returnedCouponCode + '" data-field-id="' + couponFieldId + '">&times;</span>' +

									'</div>'
									).appendTo(
										$this
											.closest('.everest-forms-coupons')
											.closest('.evf-field-payment-coupon')
											.find('.evf-coupon-error-message-container')
									);

									EverestFormsCoupons.toggleCouponErrorMessageContainer($this.closest('form'));

									if ( returnedCouponCode && ! applied_coupons_array.includes(returnedCouponCode) ) {
										applied_coupons_array.push(returnedCouponCode);
										$appliedField.val(JSON.stringify(applied_coupons_array));
									}

									var couponDataExists = applied_coupons_data_array.some(function(coupon) {
										return coupon && coupon.code === returnedCouponCode;
									});

									if ( returnedCouponCode && ! couponDataExists ) {
										var match = amount.match(/\;[\d|.\,]+/);

										var tempAmount = match
										? parseFloat(match[0].replace(';', ''))
										: amount.replace('%', '');
										applied_coupons_data_array.push({
											code: returnedCouponCode,
											stackable: normalizedIsStackable,
											field_id: couponFieldId,
											discount_type: type,
											amount: tempAmount,
											minimum_purchase: minimum_purchase,
											map_field: xhr.data.map_field || '',
											coupon_input_field: couponFieldId
										});
										$appliedCouponsDataField.val(JSON.stringify(applied_coupons_data_array));
									}

									$couponInput.attr('data-applied-code', returnedCouponCode);

									$this.closest( 'form' ).find( '.evf-payment-price:first' ).trigger( 'change' );

									// $this.css( 'display', 'none' );
								} else {
									$( '<div class="evf-error-box">' +
										'<span class="evf-error-text">' + xhr.data.message + '</span></div>'
									).insertAfter($this.closest('.everest-forms-coupons'));
								}
							})
							.fail(function () {
								$( '<label class="evf-coupon-error">Something went wrong' ).insertAfter( $this.closest( '.everest-forms-coupons' ) );
							})
							.always(function (xhr) {
								$this.prop( "disabled", false );
								$this.prev( '.everest-forms-coupons-wrapper' ).find( '.evf-payment-coupon' ).val( '')
								$this.html( $this.attr( 'data-text') );
							});
					});
				});
			});

			$( document ).on( 'click', '.evf-clear-coupon', function() {
				$( this ).closest( '.evf-field' ).removeClass( 'everest-forms-invalid' );
				$( this ).closest( '.evf-field' ).find( 'label.evf-error' ).remove();
				$( this ).closest( '.evf-field' ).find( '.evf-coupon-apply' ).css( 'display', 'block' );
				$( this ).closest( '.evf-field' ).find( '.evf-payment-coupon' ).removeAttr( 'data-discount' ).removeAttr( 'data-discount_percent' ).removeAttr( 'data-discount_map_field' ).removeClass( 'evf-coupon-applied' );
				$( this ).closest( '.evf-field' ).find( '.evf-payment-price' ).trigger("input").trigger( 'change' );
				$( this ).closest( '.evf-field' ).find( '.everest-forms-coupons input' ).prop( 'readonly', false );
				$( this ).closest( '.evf-field' ).find( 'input' ).val( '' ).focus();
				$( this ).closest( '.evf-field' ).find( '.evf-coupon-amount' ).remove();
				$( this ).closest( '.evf-field' ).find( '.evf-coupon-error' ).remove();
				$( this ).closest( 'form' ).find( '.evf-payment-price:first' ).trigger( 'change' );
			});

			$(document).on('click', '.evf-remove-applied-coupon', function () {
				var $removeBtn = $(this);
				var couponCode = $.trim($removeBtn.attr('data-code') || '').toLowerCase();
				var fieldId = String($removeBtn.attr('data-field-id') || '');
				var $field = $removeBtn.closest('.evf-field');
				var $form = $removeBtn.closest('form');
				var $couponMessage = $removeBtn.closest('.evf-applied-coupon');

				var $appliedCodesField = $form.find('.applied-coupon-codes');
				var $appliedCouponsDataField = $form.find('.applied-coupons-data');

				var appliedCodesArray = [];
				var appliedCouponsDataArray = [];

				try {
					appliedCodesArray = JSON.parse($appliedCodesField.val() || '[]');
				} catch (e) {
					appliedCodesArray = [];
				}

				try {
					appliedCouponsDataArray = JSON.parse($appliedCouponsDataField.val() || '[]');
				} catch (e) {
					appliedCouponsDataArray = [];
				}

				appliedCouponsDataArray = appliedCouponsDataArray.filter(function (coupon) {
					if (!coupon) return false;

					var savedCode = $.trim(coupon.code || '').toLowerCase();
					var savedFieldId = String(coupon.field_id || '');

					return !(savedCode === couponCode && savedFieldId === fieldId);
				});

				appliedCodesArray = appliedCouponsDataArray.map(function (coupon) {
					return $.trim(coupon.code || '').toLowerCase();
				});

				$appliedCodesField.val(JSON.stringify(appliedCodesArray));
				$appliedCouponsDataField.val(JSON.stringify(appliedCouponsDataArray));

				$couponMessage.remove();

				EverestFormsCoupons.reapplyCoupons($form);
			});
		},
		clearCouponErrors: function( $scope ) {
			$scope.find('.evf-error-box, .evf-coupon-error').remove();
		},
		toggleCouponErrorMessageContainer: function( $form ) {
			$form.find('.evf-coupon-error-message-container, .evf-applied-coupons-list').each(function() {
				var $container = $(this);
				var count = $container.find('.evf-applied-coupon').length;

				if (count === 0) {
					$container.addClass('everest-forms-hidden');
				} else {
					$container.removeClass('everest-forms-hidden');
				}
			});
		},
		evfCalculateTotal: function( $form ) {
			var total = 0;

			// Single items
			$form.find('div.evf-field-payment-single').filter(function () {
				return $(this).is(":not([style*='display: none'])") || $(this).hasClass('evf-field-hidden');
			}).each(function () {
				var id = $(this).attr('data-field-id');
				var quantity = 1;

				var $quantityField = $form.find('.evf-payment-quantity[data-map_field="' + id + '"]:visible');
				if ($quantityField.length) {
					quantity = '' === $quantityField.val() ? 1 : Number($quantityField.val());
				}

				var price = $(this).find('input.evf-payment-price').val();

				if (typeof price === 'number') {
					price = '' === price ? 0 : String(price).replace(',', '.').match(/[\d|.]+/)[0];
				} else if (typeof price === 'string') {
					price = '' === price ? 0 : price.replace(',', '.').match(/[\d|.]+/)[0];
				}

				price = Number(price || 0);
				total += price * quantity;
			});

			// Checkbox / multiple items
			$form.find("div.evf-field:not([style*='display: none']) input.evf-payment-price")
				.filter(':not(.evf-payment-quantity)')
				.filter(':checked')
				.each(function () {
					var id = $(this).parents('div.evf-field').first().attr('data-field-id');
					var quantity = 1;

					var $quantityField = $form.find('.evf-payment-quantity[data-map_field="' + id + '"]:visible');
					if ($quantityField.length) {
						quantity = '' === $quantityField.val() ? 1 : Number($quantityField.val());
					}

					var price = $(this).data('amount');

					if (typeof price === 'number') {
						price = '' === price ? 0 : String(price).replace(',', '.').match(/[\d|.]+/)[0];
					} else if (typeof price === 'string') {
						price = '' === price ? 0 : String(price).replace(',', '').match(/[\d|.]+/)[0];
					}

					price = Number(price || 0);
					total += price * quantity;
				});

			// Slider items
			$form.find('div.evf-field-range-slider').filter(function () {
				return $(this).is(":not([style*='display: none'])") && $(this).find('input.evf-payment-price').length > 0;
			}).each(function () {
				var id = $(this).attr('data-field-id');
				var quantity = 1;

				var $quantityField = $form.find('.evf-payment-quantity[data-map_field="' + id + '"]:visible');
				if ($quantityField.length) {
					quantity = '' === $quantityField.val() ? 1 : Number($quantityField.val());
				}

				var price = $(this).find('input.evf-payment-price').val();

				if (typeof price === 'number') {
					price = '' === price ? 0 : String(price).replace(',', '.').match(/[\d|.]+/)[0];
				} else if (typeof price === 'string') {
					price = '' === price ? 0 : price.replace(',', '.').match(/[\d|.]+/)[0];
				}

				price = Number(price || 0);
				total += price * quantity;
			});

			return total;
		},
		getMapFieldAmount: function($form, map_field) {
			var total_product_amount = 0;
			var field = $form.find(
				'div[data-field-id="' + map_field + '"] .evf-payment-price:enabled, ' +
				'div[data-field-id="' + map_field + '"] .evf-single-item-price-hidden input[type="hidden"]:enabled'
			);

			if (!field.length) {
				return 0;
			}

			if ('text' === field.attr('type') || 'hidden' === field.attr('type')) {
				total_product_amount = field.val();
			} else if ('radio' === field.attr('type') && field.is(':checked')) {
				total_product_amount = field.data('amount');
			} else if (field.closest('.evf-field').is('.evf-field-payment-checkbox')) {
				total_product_amount = 0;
				field.closest('.evf-field').find('.evf-payment-price:checked').each(function() {
					var amount = $(this).attr('data-amount');
					if (!isNaN(parseFloat(amount))) {
						total_product_amount += parseFloat(amount);
					}
				});
			} else if (field.is('select') && field.find('option:selected').length > 0) {
				total_product_amount = field.find('option:selected').data('amount');
			} else if ('number' === field.attr('type')) {
				total_product_amount = field.val();
			}

			total_product_amount = parseFloat(String(total_product_amount || '0').replace(',', '')) || 0;

			if ($form.find('.evf-payment-quantity').length) {
				$form.find('.evf-payment-quantity').each(function(index, el) {
					if (map_field === $(el).attr('data-map_field')) {
						var qty = parseFloat($(el).val()) || 1;
						total_product_amount *= qty;
					}
				});
			}

			return total_product_amount;
		},
		reapplyCoupons: function($form) {
			var $appliedCouponsDataField = $form.find('.applied-coupons-data');
			var appliedCouponsDataArray = [];

			try {
				appliedCouponsDataArray = JSON.parse($appliedCouponsDataField.val() || '[]');
			} catch (e) {
				appliedCouponsDataArray = [];
			}

			$form.find('.evf-payment-coupon').each(function() {
				$(this)
					.removeAttr('data-applied-code')
					.removeAttr('data-discount')
					.removeAttr('data-discount_percent')
					.removeAttr('data-discount_map_field')
					.removeAttr('data-minimum-purchase')
					.removeClass('evf-coupon-applied');
			});

			appliedCouponsDataArray.forEach(function(coupon, index) {
				var $couponField = $form.find('.evf-field[data-field-id="' + coupon.field_id + '"]');
				var $couponInput = $couponField.find('.evf-payment-coupon');

				if (!$couponInput.length) {
					return;
				}

				$couponInput.addClass('evf-coupon-applied');

				if (index === 0) {
					$couponInput.attr('data-applied-code', coupon.code);
				}

				$couponInput.attr('data-minimum-purchase', coupon.minimum_purchase || 0);

				// Render the coupon in the DOM if it doesn't exist.
				var returnedCouponCode = $.trim(coupon.code || '').toLowerCase();
				var couponFieldId = coupon.field_id;
				var $container = $couponField.find('.evf-coupon-error-message-container, .evf-applied-coupons-list');
				var $existingCoupon = $container.find('.evf-applied-coupon[data-code="' + returnedCouponCode + '"]');

				if ($container.length && !$existingCoupon.length) {
					var displayAmount = coupon.amount;
					if (coupon.discount_type === 'fixed') {
						var currency_symbol = (typeof everest_forms_coupons_params !== 'undefined') ? everest_forms_coupons_params.currency_symbol : '$';
						displayAmount = currency_symbol + displayAmount;
					} else {
						displayAmount = displayAmount + '%';
					}

					$('<div class="evf-applied-coupon" data-code="' + returnedCouponCode + '" data-field-id="' + couponFieldId + '">' +
						'<span class="evf-coupon-name">' + returnedCouponCode + '</span>' +
						'<span class="evf-coupon-discount">-' + displayAmount + '</span>' +
						'<span class="evf-remove-applied-coupon" title="Remove coupon" role="button" tabindex="0" data-code="' + returnedCouponCode + '" data-field-id="' + couponFieldId + '">&times;</span>' +
					'</div>').appendTo($container);
				}

				if (coupon.discount_type === 'fixed') {
					var fixedAmount = parseFloat(String(coupon.amount).match(/[\d.,]+/)[0].replace(',', '')) || 0;
					$couponInput.attr('data-discount', fixedAmount);
				} else {
					var percentAmount = String(coupon.amount).replace('%', '');
					$couponInput
						.attr('data-discount', percentAmount)
						.attr('data-discount_percent', '1');

					if (coupon.map_field) {
						$couponInput.attr('data-discount_map_field', coupon.map_field);
					}
				}
			});

			EverestFormsCoupons.toggleCouponErrorMessageContainer($form);

			$form.find('.evf-payment-price:first').trigger('change');
		},
	};

	// Initialize.
	EverestFormsCoupons.init(jQuery);
	window.EverestFormsCoupons = EverestFormsCoupons;

	// End of popup modal.
})(jQuery);

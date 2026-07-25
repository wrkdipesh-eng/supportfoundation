/* global everest_forms_params */
jQuery( function ( $ ) {

	// everest_forms_params is required to continue, ensure the object exists.
	if ( typeof everest_forms_params === 'undefined' ) {
		return false;
	}

	var getEnhancedSelectFormatString = function() {
		return {
			'language': {
				noResults: function() {
					return everest_forms_params.i18n_no_countries;
				}
			}
		};
	};

	var everest_forms_pro = {
		init: function() {
			$( document ).ready( everest_forms_pro.ready );

			var hidden_type = $('.evf-single-item-price-hidden input[type=hidden]').length;
			var user_defined_type = $('.evf-field-payment-single input[type=text]').length;
			var single_amount = $('.evf-single-item-price').find("span").length;

			if ( hidden_type !== 0 && user_defined_type == 0 && single_amount == 0 ) {
					$('.evf-single-item-price-hidden input[type=hidden]').closest('.evf-field-payment-single').hide();
					$('.evf-field-payment-total').hide();
			} else {
					$('.evf-single-item-price-hidden input[type=hidden]').closest('.evf-field-payment-single').hide();
					$('.evf-field-payment-total').show();
			}

			this.init_range_sliders();
			this.load_validation();
			everest_forms_pro.bindUIActions();
			this.init_privacy_policy_fields();
			this.init_field_visibility();
			this.init_colorpicker();

			if ( 1 === $( 'form.everest-form' ).data('keyboard_friendly_form') ) {
				this.makeKeyboardAccessible();
			}
			this.init_lookupField();
		},

		ready: function() {
			everest_forms_pro.loadPayments();
			if(everest_forms_pro_params.isProFieldCodeRequired){
				everest_forms_pro.loadPhoneField();
				everest_forms_pro.loadCountryFlags();
			}

			var $form = $('.everest-form');

			if ( $form.length ) {
				everest_forms_pro.buildPaymentSummary( $form );
			}

			everest_forms_pro.popupform();
			everest_forms_pro.selectUnselectAllOption();
			everest_forms_pro.CleartheForm();
			everest_forms_pro.selectstatecountrywise();

		},
		CleartheForm:function(){
			$('.evf-reset-button').on('click',function(event){
				event.preventDefault();
				var $this = $(this);
				$(document).trigger("evf_frontend_reset_button");
				var form_id =   $('#' + $this.closest('form').attr('id'));
					form_id.find("label.evf-error").remove();
					form_id.find('.evf-field-rating .everest-forms-field-rating-container label').removeClass('selected');
					form_id.find('.evf-field-yes-no .everest-forms-field-yes-no-container label').removeClass('active');
					form_id.find("div").removeClass('everest-forms-invalid');
					form_id.find('.evf-signature-input').val('');
					var signaturepad_id = form_id.find('div.everest_form_signature_canvas-wrap').children().attr('id');
					if (typeof signaturepad_id !== 'undefined'){
						const signaturePad = new SignaturePad(document.getElementById(signaturepad_id));
						signaturePad.clear();
					}
					if ( typeof tinyMCE !== 'undefined' ) {
						$.each(tinyMCE.editors, function(index, editor){
							editor.setContent('');
							});
					}

				if ('button' == $this.attr('data-type')){
					form_id.trigger("reset");
					$(':input', form_id)
					.not(':button, :submit, :reset, :hidden')
					.val('')
					.removeAttr('checked')
					.removeAttr('selected');
					form_id.find('textarea').html('');
					form_id.find('input[type=tel]').attr('value','');
					form_id.find('input[type=tel]').siblings().attr('value','');

				} else {
					form_id.trigger("reset");
				}

				everest_forms_pro.updateProgress();
			});
		},
		selectstatecountrywise:function(){
			$(document.body).on('change', '.evf-field-address-country', function () {
				var form_id =  $(this).closest('.everest-forms').find('form').data('formid');
				var country = $(this).val();
				var field_id = $(this).parent().parent().parent().attr('data-field-id');
				if ( $('span.evf-'+form_id+'-field_'+field_id+'-state input').length > 0 ){
					var placeholder =$('span.evf-'+form_id+'-field_'+field_id+'-state input').attr('placeholder');
				} else if ( $('span.evf-'+form_id+'-field_'+field_id+'-state select').length > 0){
					var placeholder = $('span.evf-'+form_id+'-field_'+field_id+'-state select').attr('placeholder');
				}

				$.ajax({
					url:evf_state_drop_down_params.ajax_url,
					type:"POST",
					data:{
						action:'everest_forms_update_state_field',
						security: evf_state_drop_down_params.ajax_everest_forms_state_nonce,
						form_id : form_id,
						country : country,
					},
					success:function (response) {
					if (response.success === true) {
						var current_states =  response.data.states[country];
						var required =$('span.evf-'+form_id+'-field_'+field_id+'-state input').prop('required');
						if (current_states && !Array.isArray(current_states)) {
						$.each(response.data.states, function(index, value) {
							if(index === country){
								$('span.evf-'+form_id+'-field_'+field_id+'-state select').remove();
								$('span.evf-'+form_id+'-field_'+field_id+'-state input').remove();

								if (typeof placeholder !== "undefined") {
									var placeholder_option = '<option class="placeholder" value="" selected="" disabled="">'+ placeholder +'</option>';
									var select_state_html = '<select id="evf-'+form_id+'-field_'+field_id+'-state" class="evf-field-address-state"  name="everest_forms[form_fields]['+field_id+'][state]" placeholder="'+placeholder+'" >'+placeholder_option+'</select>';
								} else{
									var select_state_html = '<select id="evf-'+form_id+'-field_'+field_id+'-state" class="evf-field-address-state"  name="everest_forms[form_fields]['+field_id+'][state]" ></select>';
								}

								$('span.evf-'+form_id+'-field_'+field_id+'-state').append(select_state_html);
								$.each(value, function(key, val) {
								 	$('span.evf-'+form_id+'-field_'+field_id+'-state select').append('<option value="' + key + '">' + val + '</option>');
								});
							}
						});
						} else {
							$('span.evf-'+form_id+'-field_'+field_id+'-state select').remove();
							$('span.evf-'+form_id+'-field_'+field_id+'-state input').remove();

							if (typeof placeholder !== "undefined") {
								if (required){
									var input_state_html = '<input id="evf-' + form_id + '-field_' + field_id + '-state" type="text" class="evf-field-address-state" name="everest_forms[form_fields][' + field_id + '][state]" required>';
								} else{
									var input_state_html = '<input id="evf-'+form_id+'-field_'+field_id+'-state" type="text" class="evf-field-address-state"  name="everest_forms[form_fields]['+field_id+'][state]" >';
								}

							} else{
								if (required){
									var input_state_html = '<input id="evf-' + form_id + '-field_' + field_id + '-state" type="text" class="evf-field-address-state" name="everest_forms[form_fields][' + field_id + '][state]" required>';
								} else{
									var input_state_html = '<input id="evf-'+form_id+'-field_'+field_id+'-state" type="text" class="evf-field-address-state"  name="everest_forms[form_fields]['+field_id+'][state]" " >';
								}
							}

							$('span.evf-'+form_id+'-field_'+field_id+'-state').append(input_state_html);
						}
					}
					}
			});
			});
			everest_forms_pro.updateProgress();
		},
		init_field_visibility:function(){
			$( '.everest-form' ).on( 'submit', function() {
				$( this ).find( 'input[data-field-visibilty="yes"], select[data-field-visibilty="yes"], textarea[data-field-visibilty="yes"]' ).each( function() {
					$( this ).removeAttr( 'disabled' );
				} );
			} );
		},
		init_privacy_policy_fields: function() {
			$( document.body ).on( 'click', '.evf-privacy-policy-local-page-link', function ( e ) {
				e.preventDefault();

				var page_id = $( this ).data( 'page-id' );

				if ( '' !== page_id ) {
					var $field = $( this ).closest( '.evf-field' );

					if ( $( this ).hasClass( 'evf-page-expanded' ) ) {
						$( this ).removeClass( 'evf-page-expanded' );
						$field.find( '.evf-privacy-policy-local-page-content-' + page_id ).slideUp();
					} else {
						$field.find( '.evf-privacy-policy-local-page-content:visible' ).slideUp();
						$field.find( '.evf-privacy-policy-local-page-content-' + page_id ).slideDown();
						$field.find( '.evf-privacy-policy-local-page-link' ).removeClass( 'evf-page-expanded' );
						$( this ).addClass( 'evf-page-expanded' );
					}
				}
			});
		},
		makeKeyboardAccessible: function() {
			var evf_form   = $( 'form.everest-form' );
			var evf_fields = evf_form.find('div.evf-field').not( '.evf-field-hidden' );

			evf_fields.first().find('[name^=everest_forms]').first().trigger('focus');

			$('body').on("keydown", function (e) {
				if ( e.ctrlKey || e.metaKey ) {
					if( 13 === e.which ) {
						e.preventDefault();
						evf_form.submit();
					}
				}

				if(evf_fields.last().find('[name^=everest_forms]').last().is(':focus')) {
					if( 9 === e.which ) {
						e.preventDefault();
						$( 'form.everest-form' ).find('[name^=everest_forms]').trigger('focus');
					}
				}else if($( 'form.everest-form' ).find('.everest-forms-submit-button.button.evf-submit ').is(':focus')) {
					if( 9 === e.which ) {
						e.preventDefault();
						evf_fields.first().find('[name^=everest_forms]').first().trigger('focus');
					}
				}
			});
		},

		/**
		 * Initialize Range Slider Fields.
		 *
		 * @since 1.3.3
		 */
		init_range_sliders: function() {
			// Show Range Slider Fields.
			$( '.evf-field.evf-field-range-slider' ).show();

			if ( $().ionRangeSlider && $( '.evf-field-range-slider' ).length ) {
				// Initialize range slider.
				$( '.evf-field-range-slider .evf-field-primary-input' ).ionRangeSlider({
					onFinish: function( elements ) {
						var $field = elements.input.closest( '.evf-field' );

						everest_forms_pro.setPrefixPostfixTexts( null, null, $field );
					}
				})

				// Slider value change handler.
				.on( 'change', function() {
					var new_value = $( this ).val();

					// Update slider input.
					$( this ).closest( '.evf-field-range-slider' ).find( '.evf-slider-input' ).val( new_value );

					// Update slider handle/highlight/track color.
					everest_forms_pro.setSliderColors( $( this ).closest( '.evf-field' ) );
				});

				// Slider input value change handler.
				$( '.evf-field-range-slider .evf-slider-input' )
				.on( 'input', function() {
					var new_value = $( this ).val(),
						$field = $( this ).closest( '.evf-field' );

					$( this ).closest( '.evf-field-range-slider' ).find( '.evf-field-primary-input' ).data( 'ionRangeSlider' ).update({ from: new_value });
					everest_forms_pro.setPrefixPostfixTexts( null, null, $field );
				});

				// Slider Reset icon handler.
				$( '.evf-field-range-slider .evf-range-slider-reset-icon' ).on( 'click', function( e ) {
					var $field = $( this ).closest( '.evf-field' ),
						default_value = $field.find( '.evf-field-primary-input' ).data( 'default' );

					// Update slider to default value.
					$field.find( '.evf-field-primary-input' ).data( 'ionRangeSlider' ).update({ from: default_value });

					// Update slider handle color.
					everest_forms_pro.setSliderColors( $( this ).closest( '.evf-field' ) );
					everest_forms_pro.setPrefixPostfixTexts( null, null, $field );
				});

				// Setup sliders according to the options.
				$( '.evf-field.evf-field-range-slider' ).each( function() {
					var $field = $( this ).closest( '.evf-field' );

					// Set slider handle/highlight/track color.
					everest_forms_pro.setSliderColors( this );

					// Use text prefix/postfix.
					everest_forms_pro.setPrefixPostfixTexts( null, null, $field );
				});
			}
		},

		/**
		 * Sets configured texts as prefix and postfix for a Range Slider field.
		 *
		 * @since 1.3.3
		 */
		setPrefixPostfixTexts: function ( field_id, form_id, $field ) {
			var provided_selector_params = ( field_id && '' !== field_id && form_id && '' !== form_id ),
				provided_field = ( null !== $field && undefined !== $field );

			if ( provided_selector_params || provided_field ) {
				var $field = provided_field ? $field : $( '#evf-' + form_id + '-field_' + field_id + '-container' ),
					$primary_input = $field.find( '.evf-field-primary-input' ),
					use_text_prefix_postfix = $primary_input.data( 'use-text-prefix-postfix' ),
					prefix_text = $primary_input.data( 'prefix-text' ),
					postfix_text = $primary_input.data( 'postfix-text' );

				if ( true === use_text_prefix_postfix ) {
					$field.find( 'span.irs-min' ).html( prefix_text );
					$field.find( 'span.irs-max' ).html( postfix_text );
				}
			}
		},

		/**
		 * Sets colors for a Range Slider field's handle, highlight and track.
		 *
		 * @since 1.3.3
		 */
		setSliderColors: function ( element ) {
			var $primary_input = $( element ).find( '.evf-field-primary-input' ),
				highlight_color = $primary_input.data( 'highlight_color' ),
				track_color = $primary_input.data( 'track_color' );

			everest_forms_pro.setSliderHandleColor( element );
			$( element ).find( '.irs-bar' ).css( 'background', highlight_color );
			$( element ).find( '.irs-line' ).css( 'background', track_color );
		},

		/**
		 * Set a Range Slider field's handle color.
		 *
		 * @since 1.3.3
		 */
		setSliderHandleColor: function ( element ) {
			if ( element ) {
				var $field = $( element ),
					field_id = $field.attr( 'id' ),
					skin = $field.find( '.evf-field-primary-input' ).data( 'skin' ),
					handle_color = $field.find( '.evf-field-primary-input' ).data( 'handle_color' ),
					style = '';

				switch ( skin ) {
					case 'flat':
						$field.find( '.irs-handle i' ).first().css( 'background-color', handle_color );
						$field.find( '.irs-single' ).css( 'background-color', handle_color );
						style = '#' + field_id +' .irs-single:before { border-top-color: ' + handle_color + '!important; }';
						break;

					case 'big':
						$field.find( '.irs-single' ).css( 'background-color', handle_color );
						$field.find( '.irs-single' ).css( 'background', handle_color );
						$field.find( '.irs-handle' ).css( 'background-color', handle_color );
						$field.find( '.irs-handle' ).css( 'background', handle_color );
						break;

					case 'modern':
						$field.find( '.irs-handle i' ).css( 'background', handle_color );
						$field.find( '.irs-single' ).css( 'background-color', handle_color );
						style = '#' + field_id +' .irs-single:before { border-top-color: ' + handle_color + '!important; }';
						break;

					case 'sharp':
						$field.find( '.irs-handle' ).css( 'background-color', handle_color );
						$field.find( '.irs-handle i' ).first().css( 'border-top-color', handle_color );
						$field.find( '.irs-single' ).css( 'background-color', handle_color );
						style = '#' + field_id +' .irs-single:before { border-top-color: ' + handle_color + '!important; }';
						break;

					case 'round':
						$field.find( '.irs-handle' ).css( 'border-color', handle_color );
						$field.find( '.irs-single' ).css( 'background-color', handle_color );
						style = '#' + field_id +' .irs-single:before { border-top-color: ' + handle_color + '!important; }';
						break;

					case 'square':
						$field.find( '.irs-handle' ).css( 'border-color', handle_color );
						$field.find( '.irs-single' ).css( 'background-color', handle_color );
						style = '#' + field_id +' .irs-single:before { border-top-color: ' + handle_color + '!important; }';
						break;
				}

				$( 'body' ).find( '.evf-range-slider-handle-style-tag-' + field_id ).remove();
				$( 'body' ).append( '<style class="evf-range-slider-handle-style-tag-' + field_id + '" >' + style + '</style>' );
			}
		},

		load_validation: function() {
			if ( typeof $.fn.validate === 'undefined' ) {
				return false;
			}

			// Validate method for file extensions.
			$.validator.addMethod( 'extension', function( value, element, param ) {
				param = typeof param === 'string' ? param.replace( /,/g, '|' ) : 'png|jpe?g|gif';
				return this.optional( element ) || value.match( new RegExp( '\\.(' + param + ')$', 'i' ) );
			}, everest_forms_params.i18n_messages_fileextension );

			// Validate method for file size.
			$.validator.addMethod( 'maxsize', function( value, element, param ) {
				var maxSize = param,
					optionalValue = this.optional( element ),
					i, len, file;

				if ( optionalValue ) {
					return optionalValue;
				}

				if ( element.files && element.files.length ) {
					i = 0;
					len = element.files.length;
					for ( ; i < len; i++ ) {
						file = element.files[i];
						if ( file.size > maxSize ) {
							return false;
						}
					}
				}

				return true;
			}, everest_forms_params.i18n_messages_filesize );

			// Validate default Phone Field.
			if(everest_forms_pro_params.isProFieldCodeRequired) {
				$.validator.addMethod( 'phone-field', function( value, element ) {
				if ( value.match( /[^\d()\-+\s]/ ) ) {
					return false;
				}
				return this.optional( element ) || value.replace( /[^\d]/g, '' ).length > 0;
			}, everest_forms_params.i18n_messages_phone );

			// Validate Smart Phone Field.
			if ( 'undefined' !== typeof $.fn.intlTelInput ) {
				$.validator.addMethod( 'smart-phone-field', function( value, element ) {
					if ( value.match( /[^\d()\-+\s]/ ) ) {
						return false;
					}
					return this.optional( element ) || $( element ).intlTelInput( 'isValidNumber' );
				}, everest_forms_params.i18n_messages_phone );
			}
		}
		},

		/**
		 * Payments: Do various payment-related tasks on load.
		 */
		loadPayments: function() {
			// Update Total field(s) with latest calculation.
			$( '.evf-payment-total' ).each( function( index, el ) {
				everest_forms_pro.amountTotal( this );
			} );

			// Update Subtotal fields(s) with latest calculation.
			$( '.evf-payment-subtotal' ).each( function( index, el ) {
				everest_forms_pro.amountSubtotal( this );
			} );
		},

		/**
		 * Load phone field.
		 *
		 * @since 1.2.9
		 */
		loadPhoneField: function() {
			var inputOptions = {};

			// Only continue if intlTelInput library exists.
			if ( typeof $.fn.intlTelInput === 'undefined' ) {
				return false;
			}

			// Determine the country by IP if storing user details is enabled.
			if ( 'yes' !== everest_forms_params.disable_user_details ) {
				inputOptions.geoIpLookup = everest_forms_pro.currentIpToCountry;
			}

			// Try an alternative solution if storing user details is disabled.
			if ( 'yes' === everest_forms_params.disable_user_details ) {
				var lang = this.getFirstBrowserLanguage(),
					countryCode = lang.indexOf( '-' ) > -1 ? lang.split( '-' ).pop() : '';
			}

			// Make sure the library recognizes browser country code to avoid console error.
			if ( countryCode ) {
				var countryData = window.intlTelInputGlobals.getCountryData();

				countryData = countryData.filter( function( country ) {
					return country.iso2 === countryCode.toLowerCase();
				} );
				countryCode = countryData.length ? countryCode : '';
			}

			// Set default country.
			inputOptions.initialCountry = 'yes' === everest_forms_params.disable_user_details && countryCode ? countryCode : 'auto';

			$( '.evf-smart-phone-field' ).each( function( i, el ) {
				var $el = $( el );

				// Hidden input allows to include country code into submitted data.
				inputOptions.hiddenInput = $el.closest( '.evf-field-phone' ).data( 'field-id' );
				inputOptions.utilsScript = everest_forms_pro_params.plugin_url + 'assets/js/intlTelInput/utils.js';

				$el.intlTelInput( inputOptions );

				// Change name of the phone field.
				var field_name     = $el.attr( 'name' ),
					field_new_name = field_name + '[phone_field]';

				$el.attr( 'name', field_new_name );
				$el.blur( function() {
					if ( $el.intlTelInput( 'isValidNumber' ) ) {
						$el.siblings( 'input[type="hidden"]' ).val( $el.intlTelInput( 'getNumber' ) );
					}
				} );
			} );
		},
		loadCountryFlags: function() {
			// Only continue if SelectWoo library exists.
			if ( 'undefined' !== typeof $.fn.selectWoo ) {
				$.fn.selectWoo.amd.define( 'evfCountrySelectionAdapter', [
					'select2/utils',
					'select2/selection/single',
				], function ( Utils, SingleSelection ) {
					var adapter = SingleSelection;
					adapter.prototype.update = function ( data ) {
						if ( 0 === data.length ) {
							this.clear();
							return;
						}
						var selection = data[0];
						var $rendered = this.$selection.find( '.select2-selection__rendered' );
						var formatted = this.display( selection, $rendered );
						$rendered.empty().append( formatted );
						$rendered.prop( 'title', selection.title || selection.text );
					};
					return adapter;
				} );

				$( 'select.evf-country-flag-selector:visible' ).each( function() {
					var select2_args = $.extend({
						placeholder: $( this ).attr( 'placeholder' ) || '',
						selectionAdapter: $.fn.selectWoo.amd.require( 'evfCountrySelectionAdapter' ),
						templateResult: everest_forms_pro.getFormattedCountryFlags,
						templateSelection: everest_forms_pro.getFormattedCountryFlags,
					}, getEnhancedSelectFormatString() );

					$( this ).selectWoo( select2_args );
				});
			}
		},
		popupform:function () {

			$(document).on("click",".evf-close-popup",function(e){
				e.preventDefault();
				var classes = $.map($(this)[0].classList, function (cls, i) {
					if (cls.indexOf("evf-close-popup-") === 0) {
					var popup_id = cls.replace("evf-close-popup-", "");
					$("body").first().css("overflow", "");
					$(".everest-forms-modal-" + popup_id).each(function () {
						 $(this).hide();
					});
				}
			});
		});
			$(document).on("click",".everest-forms-modal-link",function(e){
				e.preventDefault();
				var classes = $.map($(this)[0].classList, function (cls, i) {
					if (cls.indexOf("everest-forms-modal-link-") === 0) {
						$("body").first().css("overflow", "hidden");
					var popup_id = cls.replace("everest-forms-modal-link-", "");
					$(".everest-forms-modal-" + popup_id ).each(function (e) {
						 $(this).show();
					});
				}
			});
		});

		$(document).on("click",".evf-model",function(e){
			e.stopPropagation();
		});

		$(document).on("click",".everest-forms-modal ",function(e){
			$(this).hide();
		});
		},

		selectUnselectAllOption: function() {
			// Adding select all and unselect all buttons for enhanced selection
			if($('select').hasClass('evf-enhanced')) {
				$.fn.select2.amd.require([
					'select2/utils',
					'select2/dropdown',
					'select2/dropdown/attachBody'
				], function (Utils, Dropdown, AttachBody) {
					function SelectAll() {}
					SelectAll.prototype.render = function (decorated) {
						var $rendered = decorated.call(this);
						var self = this;

						var $selectAll = $('<a/>').addClass('evf-btn-select-all').text( everest_forms_params.i18n_select_all );
						var $unselectAll = $('<a/>').addClass('evf-btn-unselect-all').text( everest_forms_params.i18n_unselect_all );
						var $btnContainer = $("<div>")
						.append($selectAll)
						.append($unselectAll)
						.addClass('evf-btn-container');

						var $dropdown = $rendered.find('.select2-dropdown');

						$dropdown.prepend($btnContainer);

						$selectAll.on('click', function (e) {
							var $options = self.$element.find('option').not(':disabled');
							var values = [];

							$options.each(function () {
								values.push($(this).val());
							});
							self.$element.val(values);
							self.$element.trigger("change");
							self.trigger("close");
						});

						$unselectAll.on('click', function (e) {
							self.$element.val([]);
							self.$element.trigger("change");
							self.trigger("close");
						});

						return $rendered;
					};
					var select2_args = $.extend({
						dropdownAdapter: Utils.Decorate(
							Utils.Decorate(
								Dropdown,
								AttachBody
							),
							SelectAll
						),
						width: '100%'
					}, getEnhancedSelectFormatString() );

					$("select.evf-enhanced[select_all_unselect_all=true]").selectWoo( select2_args );
				});
			}

			// Adding select all and unselect all buttons for enhanced selection
			var $all_simple_multiselect = $("select[select_all_unselect_all=true]").not('.evf-enhanced');
			$all_simple_multiselect.each(function () {
				var $this = $(this);
				var $id   = "evf_select_btn_container_"+$this.parent().data('field-id');

				$this.before('<div class="evf-btn-container" id="'+$id+'"><a class="evf-btn-select-all">Select All</a><a class="evf-btn-unselect-all">Unselect All</a></div>');
				$(document.body).find('#'+$id).find('.evf-btn-select-all').on('click', function () {
					$this.find('option').prop('selected', true).trigger('change');
				});

				$(document.body).find('#'+$id).find('.evf-btn-unselect-all').on('click', function () {
					$this.find('option').prop('selected', false).trigger('change');
				});
			});
	},

		/**
		 * Get formatted country flags.
		 *
		 * @param {object} country Country object.
		 */
		getFormattedCountryFlags: function( country ) {
			if ( ! country.id ) {
				return country.text;
			}
			return $( '<div class="iti__flag-box"><div class="iti__flag iti__' + country.id.toLowerCase() + '"></div></div><span class="iti__country-name">' + country.text + '</span>' );
		},

		/**
		 * Get user browser preferred language.
		 *
		 * @since 1.2.9
		 *
		 * @returns {String} Language code.
		 */
		getFirstBrowserLanguage: function() {
			var nav = window.navigator,
				browserLanguagePropertyKeys = [ 'language', 'browserLanguage', 'systemLanguage', 'userLanguage' ],
				i,
				language;

			// Support for HTML 5.1 "navigator.languages".
			if ( Array.isArray( nav.languages ) ) {
				for ( i = 0; i < nav.languages.length; i++ ) {
					language = nav.languages[ i ];
					if ( language && language.length ) {
						return language;
					}
				}
			}

			// Support for other well known properties in browsers.
			for ( i = 0; i < browserLanguagePropertyKeys.length; i++ ) {
				language = nav[ browserLanguagePropertyKeys[ i ] ];
				if ( language && language.length ) {
					return language;
				}
			}

			return '';
		},

		/**
		 * Asynchronously fetches country code using current IP
		 * and executes a callback with the relevant country code.
		 *
		 * @since 1.2.9
		 *
		 * @param {Function} callback Executes once the fetch is completed.
		 */
		currentIpToCountry: function( callback ) {
			$.get( 'https://ipapi.co/json' ).always( function( resp ) {
				var countryCode = ( resp && resp.country ) ? resp.country : '';

				if ( ! countryCode ) {
					var lang = everest_forms_pro.getFirstBrowserLanguage();
					countryCode = lang.indexOf( '-' ) > -1 ? lang.split( '-' ).pop() : '';
				}

				callback( countryCode );
			} );
		},

		/**
		 * Element bindings.
		 */
		bindUIActions: function() {

			// Payments: Update Total field(s) when latest calculation.
			$( document ).on( 'change input', '.evf-payment-price', function() {
				everest_forms_pro.amountTotal(this, true);
				everest_forms_pro.amountSubtotal( this, true );
			} );

			$( document.body ).on( 'conditional_show conditional_hide', function( e, fieldWrapper ) {
				var payment_field = $( fieldWrapper ).find( '.evf-payment-price' );

				if ( $( payment_field ).length ) {
					everest_forms_pro.amountTotal( payment_field, true );
					everest_forms_pro.amountSubtotal( payment_field, true );
				}
			} );

			// Payments: Restrict user input payment fields
			$( document ).on( 'input', '.evf-payment-user-input', function() {
				var $this  = $( this ),
					amount = $this.val();
				$this.val( amount.replace( /[^0-9.,]/g, '' ) );
			} );

			// Payments: Sanitize/format user input amounts
			$( document ).on( 'focusout', '.evf-payment-user-input', function() {
				var $this     = $(this),
					amount    = $this.val(),
					sanitized = everest_forms_pro.amountSanitize( amount ),
					formatted = everest_forms_pro.amountFormat( sanitized );
				$this.val( formatted );
			} );
			if(everest_forms_pro_params.isProFieldCodeRequired) {

			// Rating field: hover effect.
			$( '.everest-forms-field-rating' ).hover(
				function() {
					$( this ).parent().find( '.everest-forms-field-rating' ).removeClass( 'selected hover' );
					$( this ).prevAll().addBack().addClass( 'hover' );
				},
				function() {
					$( this ).parent().find( '.everest-forms-field-rating' ).removeClass( 'selected hover' );
					$( this ).parent().find( 'input:checked' ).parent().prevAll().addBack().addClass( 'selected' );
				}
			);

			// Rating field: toggle.
			$( document ).on( 'change', '.everest-forms-field-rating input', function() {
				var $this  = $( this ),
					$wrap  = $this.closest( '.everest-forms-field-rating-container' ),
					$items = $wrap.find( '.everest-forms-field-rating' );

				$items.removeClass( 'hover selected' );
				$this.parent().prevAll().addBack().addClass( 'selected' );
			} );

			// Rating field: preselect the selected rating.
			$( document ).ready( function () {
				$( '.everest-forms-field-rating input:checked' ).trigger( 'change' );
			} );
		}

			$( document ).on( 'click', '.toggle-password', function() {
				var $this = $(this),
					input = $( $this.attr( 'toggle' ) );

				$this.toggleClass( 'dashicons-visibility' );

				if ( 'password' === input.attr( 'type' ) ) {
					input.attr( 'type', 'text' );
				} else {
					input.attr( 'type', 'password' );
				}
			});

			$( document ).on( 'change input focusout', 'form.everest-form', function() {
				everest_forms_pro.updateProgress();
			});

			$( document ).find( '.evf-signature-canvas' ).on( 'mouseup mousedown', function () {
				everest_forms_pro.updateProgress();
			} );

			if (typeof tinyMCE !== 'undefined') {
					$.each(tinyMCE.editors, function(index, editor) {
						editor.on('keyup', function() {
								everest_forms_pro.updateProgress();
					});
				});
			}

			$( document ).on( 'click', '.evf-signature-reset', function( event ) {
				event.preventDefault();
				everest_forms_pro.updateProgress();
			} );
		},

		/**
		 * Payments: Calculate total.
		 */
		amountTotal: function( el, validate ) {
			validate = validate || false;

			var $is_evf_form 			= $( el ).closest( '.everest-form' );
			var $is_woocommerce_cart 	= $( el ).closest( '.cart' );

			if ( $is_woocommerce_cart.length > 0  ) {
				var $form = $is_woocommerce_cart;
			}else{
				var $form = $is_evf_form;
			}

			var total                = 0,
				totalFormatted       = 0,
				totalFormattedSymbol = 0,
				totalDiscount        = 0,
				currency             = everest_forms_pro.getCurrency();

			$form.find( '.evf-field .evf-payment-price:enabled, .evf-field .evf-single-item-price-hidden input[type="hidden"]:enabled' ).each( function( index, el ) {
				var $this = $(this),
				amount =0;
				if ( 'text' === $this.attr( 'type' ) || 'hidden' === $this.attr( 'type' ) ) {
					amount = $this.val();
				} else if ( ( 'radio' === $this.attr( 'type' ) || 'checkbox' === $this.attr( 'type' ) ) && $this.is( ':checked' ) ) {
					amount = $this.data('amount');
				} else if ( $this.is( 'select' ) && $this.find( 'option:selected' ).length > 0 ) {
					amount = $this.find( 'option:selected' ).data( 'amount' );
				} else if ( 'number' === $this.attr( 'type' ) ) {
					if( $this.data( 'enable_payment_slider' ) ) {
						amount = $this.val();
					}
				}

				if ( ! everest_forms_pro.empty( amount ) ) {
					amount = everest_forms_pro.amountSanitize( amount );
					total  = Number( total ) + Number( amount );
				}
			});

			if ( $form.find( '.evf-payment-quantity' ).length ) {
				$form.find( '.evf-payment-quantity' ).each(function( index, el ) {
					if ( '' !== $( el ).val() ) {
						form_id       = $form.data( 'formid' );
						map_field_id  = $(el).attr('data-map_field');
						$mapped_field = $( '#evf-' + form_id + '-field_' + map_field_id );

						if ( ( $mapped_field.is( '.evf-payment-price' ) && $mapped_field.is( ':enabled' ) ) || $mapped_field.find( '.evf-payment-price' ).is( ':enabled' ) ) {
							var map_field_amount = everest_forms_pro.amountSanitize( $mapped_field.val() ),
								quantity = parseFloat($(el).val()) || 1,
								amount;

							if(  'hidden' !== $(el) && false === $(el).is(':visible') ) {
								total = total - map_field_amount;
								map_field_amount = 0;
							}

							if ( $mapped_field.closest( '.evf-field' ).is( '.evf-field-payment-multiple' ) ) {
								if ( $mapped_field.find( '.evf-payment-price:checked' ).length ) {
									amount = $mapped_field.find( '.evf-payment-price:checked' ).attr( 'data-amount' );

									if ( ! isNaN( parseFloat( amount ) ) ) {
										if (typeof amount === 'string' && everest_forms_pro.getCurrency().code === 'EUR') {
											map_field_amount = parseFloat(amount.replace(',', '.'));
										} else {
											map_field_amount = parseFloat(amount.replace(',', ''));
										}
										total = total - map_field_amount;
										total += quantity * map_field_amount;
									}
								}
							} else if ( $mapped_field.closest( '.evf-field' ).is( '.evf-field-payment-checkbox' ) ) {
								$mapped_field.find( '.evf-payment-price:checked' ).each( function() {
									amount = $( this ).attr( 'data-amount' );

									if ( ! isNaN( parseFloat( amount ) ) ) {
										if (typeof amount === 'string' && everest_forms_pro.getCurrency().code === 'EUR') {
											map_field_amount = parseFloat(amount.replace(',', '.'));
										} else {
											map_field_amount = parseFloat(amount.replace(',', ''));
										}
										total -= map_field_amount;
										total += quantity * map_field_amount;
									}
								} );
							} else {
								if ( ! isNaN( parseFloat( map_field_amount ) ) ) {
									map_field_amount = parseFloat( map_field_amount );
									total -= map_field_amount;
									total += quantity * map_field_amount;
								}
							}
						}
					}
				} );
			}

			var count = 0;
			var originalTotal = total;

			$( document ).find( '.evf-error-box' ).remove();
			$form.find('.everest-forms-coupons input.evf-coupon-applied').each(function () {
				var $appliedCouponsDataField = $form.find('.applied-coupons-data');
				var appliedCouponsDataArray = [];

				if( 0 === count ) {
					totalDiscount = 0;

					try {
						appliedCouponsDataArray = JSON.parse($appliedCouponsDataField.val() || '[]');
					} catch (e) {
						appliedCouponsDataArray = [];
					}

					var validCoupons = [];
					var removedAny = false;
					appliedCouponsDataArray.forEach(function(coupon) {
						if (!coupon) return;
						var min = parseFloat(coupon.minimum_purchase) || 0;
						if (originalTotal > 0 && originalTotal < min) {
							var code = String(coupon.code || '').toLowerCase();
							$form.find('.evf-applied-coupon[data-code="' + code + '"]').remove();
							$form.find('.evf-payment-coupon[data-applied-code="' + code + '"]')
								.removeAttr('data-applied-code')
								.removeAttr('data-discount')
								.removeAttr('data-discount_percent')
								.removeAttr('data-discount_map_field')
								.removeAttr('data-minimum-purchase')
								.removeClass('evf-coupon-applied');
							removedAny = true;
						} else {
							validCoupons.push(coupon);
						}
					});

					if (removedAny) {
						appliedCouponsDataArray = validCoupons;
						$appliedCouponsDataField.val(JSON.stringify(appliedCouponsDataArray));
						var codes = validCoupons.map(function(c) { return String(c.code || '').toLowerCase(); });
						$form.find('.applied-coupon-codes').val(JSON.stringify(codes));
					}

					appliedCouponsDataArray.forEach(function(coupon) {
						if (!coupon) {
							return;
						}

						var discountType = String(coupon.discount_type || '').toLowerCase();
						var discountAmount = parseFloat(coupon.amount) || 0;
						var mapField = coupon.map_field || '';

						if (mapField !== '') {
							var total_product_amount = 0;
							var field = $form.find(
								'div[data-field-id="' + mapField + '"] .evf-payment-price:enabled, ' +
								'div[data-field-id="' + mapField + '"] .evf-single-item-price-hidden input[type="hidden"]:enabled'
							);

							var amount, map_field_amount;

							if ('text' === field.attr('type') || 'hidden' === field.attr('type')) {
								total_product_amount = field.val();
							} else if ( 'radio' === field.attr( 'type' ) ) {
								var checkedField = field.filter( ':checked' );
								if ( checkedField.length ) {
									total_product_amount = checkedField.data( 'amount' );
								}
							} else if (field.closest('.evf-field').is('.evf-field-payment-checkbox')) {
								total_product_amount = 0;
								field.closest('.evf-field').find('.evf-payment-price:checked').each(function() {
									amount = $(this).attr('data-amount');
									if (!isNaN(parseFloat(amount))) {
										map_field_amount = parseFloat(amount);
										total_product_amount += map_field_amount;
									}
								});
							} else if (field.is('select') && field.find('option:selected').length > 0) {
								total_product_amount = field.find('option:selected').data('amount');
							} else if ('number' === field.attr('type')) {
								if (field.data('enable_payment_slider')) {
									total_product_amount = field.val();
								}
							}

							total_product_amount = parseFloat(total_product_amount) || 0;

							if ($form.find('.evf-payment-quantity').length) {
								$form.find('.evf-payment-quantity').each(function(index, el) {
									if ('' !== $(el).val()) {
										if (mapField === $(el).attr('data-map_field')) {
											total_product_amount = total_product_amount * parseFloat($(el).val());
										}
									}
								});
							}

							if ('percent' === discountType) {
								totalDiscount += (total_product_amount * discountAmount / 100);
							} else if ('fixed' === discountType) {
								totalDiscount += Math.min(discountAmount, total_product_amount);
							}
						} else {
							if ('percent' === discountType) {
								totalDiscount += (originalTotal * discountAmount / 100);
							} else if ('fixed' === discountType) {
								totalDiscount += discountAmount;
							}
						}
					});

					if ( 'undefined' !== typeof EverestFormsCoupons && 'function' === typeof EverestFormsCoupons.toggleCouponErrorMessageContainer ) {
						EverestFormsCoupons.toggleCouponErrorMessageContainer($form);
					}

					total = originalTotal - totalDiscount;

					if (total < 0) {
						total = 0;
					}
				}
				count++;
			});

			totalFormatted = everest_forms_pro.amountFormat( total );

			if ( 'left' === currency.symbol_pos ) {
				totalFormattedSymbol = currency.symbol + ' ' + totalFormatted;
			} else {
				totalFormattedSymbol = totalFormatted + ' ' + currency.symbol;
			}

			$form.find( '.evf-payment-total' ).each( function() {
				if ( 'hidden' === $( this ).attr( 'type' ) || 'text' === $( this ).attr( 'type' ) ) {
					$( this ).val( totalFormattedSymbol );
					if ( 'text' === $( this ).attr( 'type' ) && validate && $form.data( 'validator' ) ) {
						$( this ).valid();
					}
				} else {
					$( this ).text( totalFormattedSymbol );
				}
			} );

			var discountFormatted = everest_forms_pro.amountFormat( totalDiscount );
			var discountFormattedSymbol = '';

			if ( 'left' === currency.symbol_pos ) {
				discountFormattedSymbol = currency.symbol + ' ' + discountFormatted;
			} else {
				discountFormattedSymbol = discountFormatted + ' ' + currency.symbol;
			}

			var subtotalValue = originalTotal;
			var subtotalFormatted = everest_forms_pro.amountFormat(subtotalValue);

			var subtotalFormattedSymbol = '';
			if ('left' === currency.symbol_pos) {
				subtotalFormattedSymbol = currency.symbol + ' ' + subtotalFormatted;
			} else {
				subtotalFormattedSymbol = subtotalFormatted + ' ' + currency.symbol;
			}

			everest_forms_pro.buildPaymentSummary( $form, totalFormattedSymbol, discountFormattedSymbol, subtotalFormattedSymbol );
		},
		buildPaymentSummary: function( $form, totalFormattedSymbol, discountFormattedSymbol, subtotalFormattedSymbol ) {
			var $summary = $form.find( '.evf-payment_summary_component' );

			if ( ! $summary.length ) {
				return;
			}

			var currency  = everest_forms_pro.getCurrency();
			var $tbody    = $summary.find( '.everest-forms-payment-summary-items' );
			var $fallback = $summary.find( '.evf_payment_summary_fallback' );

			var has_items = false;

			$tbody.empty();

			function formatAmount( amount ) {
				amount = everest_forms_pro.amountSanitize( amount );
				amount = everest_forms_pro.amountFormat( amount );

				if ( 'left' === currency.symbol_pos ) {
					return currency.symbol + ' ' + amount;
				}

				return amount + ' ' + currency.symbol;
			}

			function getFieldLabel( $field ) {
				var label = $.trim( $field.find( '> label, .evf-label' ).first().text() );

				if ( ! label ) {
					label = 'Payment Item';
				}

				return label.replace( '*', '' ).trim();
			}

			function appendRow( item_name, price, qty ) {
				var line_total = Number( price ) * Number( qty );

				$tbody.append(
					'<tr class="everest-forms-payment-summary-item">' +
						'<td class="everest-forms-payment-summary-item-name">' + item_name + '</td>' +
						'<td class="everest-forms-payment-summary-item-amount">' + formatAmount( price ) + '</td>' +
						'<td class="everest-forms-payment-summary-item-quantity">' + qty + '</td>' +
						'<td class="everest-forms-payment-summary-item-total">' + formatAmount( line_total ) + '</td>' +
					'</tr>'
				);

				has_items = true;
			}

			$form.find( '.evf-field' ).each( function() {
				var $field   = $( this );
				var field_id = $field.data( 'field-id' );
				var quantity = 1;

				if (
					! $field.hasClass( 'evf-field-payment-single' ) &&
					! $field.hasClass( 'evf-field-payment-multiple' ) &&
					! $field.hasClass( 'evf-field-payment-checkbox' ) &&
					! $field.hasClass( 'evf-field-payment-subscription-plan' )
				) {
					return;
				}

				if ( ! $field.is( ':visible' ) ) {
					return;
				}

				var $qty_field = $form.find( '.evf-payment-quantity[data-map_field="' + field_id + '"]' );

				if ( $qty_field.length && '' !== $qty_field.val() ) {
					if ( 'hidden' === $qty_field.attr( 'type' ) || false === $qty_field.is( ':visible' ) ) {
						quantity = 0;
					} else {
						quantity = parseFloat( $qty_field.val() );
					}
				}

				if ( quantity <= 0 ) {
					return;
				}

				if ( $field.hasClass( 'evf-field-payment-single' ) ) {
					var $input = $field.find( '.evf-payment-price:enabled, .evf-single-item-price-hidden input[type="hidden"]:enabled' ).first();
					var amount = 0;

					if ( $input.length ) {
						if ( 'text' === $input.attr( 'type' ) || 'hidden' === $input.attr( 'type' ) ) {
							amount = $input.val();
						} else if ( 'number' === $input.attr( 'type' ) && $input.data( 'enable_payment_slider' ) ) {
							amount = $input.val();
						}
					}

					if ( ! everest_forms_pro.empty( amount ) && Number( everest_forms_pro.amountSanitize( amount ) ) > 0 ) {
						appendRow(
							getFieldLabel( $field ),
							everest_forms_pro.amountSanitize( amount ),
							quantity
						);
					}
				}

				if (
					$field.hasClass( 'evf-field-payment-multiple' ) ||
					$field.hasClass( 'evf-field-payment-subscription-plan' )
				) {
					var $checked = $field.find( '.evf-payment-price:checked:enabled' );

					if ( $checked.length ) {
						var checked_amount = $checked.attr( 'data-amount' );
						var checked_label = $.trim( $field.find('label[for="' + $checked.attr('id') + '"]').text() ) || getFieldLabel( $field );

						if ( ! everest_forms_pro.empty( checked_amount ) ) {
							appendRow(
								checked_label,
								everest_forms_pro.amountSanitize( checked_amount ),
								quantity
							);
						}
					}

					var $select = $field.find( 'select.evf-payment-price:enabled' );
					if ( $select.length && $select.find( 'option:selected' ).length ) {
						var $selected = $select.find( 'option:selected' );
						var selected_amount = $selected.data( 'amount' );
						var selected_label  = $.trim( $selected.text() );

						if ( $selected.val() && ! everest_forms_pro.empty( selected_amount ) ) {
							appendRow(
								selected_label,
								everest_forms_pro.amountSanitize( selected_amount ),
								quantity
							);
						}
					}
				}

				if ( $field.hasClass( 'evf-field-payment-checkbox' ) ) {
					var total_amount = 0;

					$field.find('.evf-payment-price:checked:enabled').each(function() {
						var amount = $(this).attr('data-amount');

						if (!everest_forms_pro.empty(amount)) {
							total_amount += Number(everest_forms_pro.amountSanitize(amount));
						}
					});

					if (total_amount > 0) {
						appendRow(
							getFieldLabel($field),
							total_amount,
							quantity
						);
					}
				}
			} );

			if ( has_items ) {
				$summary.find( '.evf_table' ).show();
				$fallback.hide();
			} else {
				$summary.find( '.evf_table' ).hide();
				$fallback.show();
			}

			var final_total = totalFormattedSymbol;

			if ( everest_forms_pro.empty( final_total ) ) {
				var calculated_total = 0;

				$tbody.find( '.everest-forms-payment-summary-item' ).each( function() {
					var row_total = $( this ).find( '.everest-forms-payment-summary-item-total' ).text();

					if ( ! everest_forms_pro.empty( row_total ) ) {
						row_total = everest_forms_pro.amountSanitize( row_total );
						calculated_total = Number( calculated_total ) + Number( row_total );
					}
				} );

				calculated_total = everest_forms_pro.amountFormat( calculated_total );

				if ( 'left' === currency.symbol_pos ) {
					final_total = currency.symbol + ' ' + calculated_total;
				} else {
					final_total = calculated_total + ' ' + currency.symbol;
				}
			}

			if (
				subtotalFormattedSymbol &&
				0 < Number( everest_forms_pro.amountSanitize( subtotalFormattedSymbol ) )
			) {
				$tbody.append(
					'<tr class="everest-forms-payment-summary-subtotal">' +
						'<td colspan="3" class="item_right">Subtotal</td>' +
						'<td class="evf-payment-summary-subtotal-amount">' + subtotalFormattedSymbol + '</td>' +
					'</tr>'
				);
			}

			if (
				discountFormattedSymbol &&
				0 < Number( everest_forms_pro.amountSanitize( discountFormattedSymbol ) )
			) {
				$tbody.append(
					'<tr class="everest-forms-payment-summary-discount">' +
						'<td colspan="3" class="item_right">' + everest_forms_pro_params.i18n_discount + '</td>' +
						'<td class="evf-payment-summary-discount-amount">- ' + discountFormattedSymbol + '</td>' +
					'</tr>'
				);
			}

			$summary.find( '.everest-forms-payment-summary-item-final-amount' ).html( final_total );
		},
		/**
		 * Payments: Calculate subtotal.
		 */
		amountSubtotal: function( el, validate ) {
			var $form = $( el ).closest( '.everest-form' );

			$form.find( '.evf-payment-subtotal' ).each(function() {
				validate = validate || false;

				var subtotal                = 0,
					subtotalFormatted       = 0,
					subtotalFormattedSymbol = 0,
					map_field_id            = '',
					currency                = everest_forms_pro.getCurrency();

				var $subtotal_field = $( this ),
					$quantity_field = $form.find( '.evf-payment-quantity[data-map_field="' + $( this ).data( 'map_field' ) + '"]' );

				if ( $subtotal_field.data( 'map_field' ) ) {
					var form_id       = $form.data( 'formid' ),
						map_field_id  = $subtotal_field.attr( 'data-map_field' ),
						$mapped_field = $( '#evf-' + form_id + '-field_' + map_field_id );

					if (
						( $mapped_field.is( '.evf-payment-price' ) && $mapped_field.is( ':enabled' ) ) ||
						$mapped_field.find( '.evf-payment-price' ).is( ':enabled' )
					) {
						var map_field_amount = $mapped_field.val().replace( ',', '.' ),
							quantity         = 1,
							amount           = 0;

						if ( $quantity_field.length && '' !== $quantity_field.val() ) {
							if ( 'hidden' === $quantity_field.attr( 'type' ) || false === $quantity_field.is( ':visible' ) ) {
								map_field_amount = 0;
								quantity = 0;
							} else {
								quantity = parseFloat( $quantity_field.val() );
							}
						}

						if ( $mapped_field.closest( '.evf-field' ).is( '.evf-field-payment-multiple' ) ) {
							if ( $mapped_field.find( '.evf-payment-price:checked' ).length ) {
								amount = $mapped_field.find( '.evf-payment-price:checked' ).attr( 'data-amount' );

								if ( ! isNaN( parseFloat( amount ) ) ) {
									if ( typeof amount === 'string' && everest_forms_pro.getCurrency().code === 'EUR' ) {
										map_field_amount = parseFloat( amount.replace( ',', '.' ) );
									} else {
										map_field_amount = parseFloat( amount.replace( ',', '' ) );
									}

									subtotal = quantity * map_field_amount;
								}
							}
						} else if ( $mapped_field.closest( '.evf-field' ).is( '.evf-field-payment-checkbox' ) ) {
							$mapped_field.find( '.evf-payment-price:checked' ).each(function() {
								amount = $( this ).attr( 'data-amount' );

								if ( ! isNaN( parseFloat( amount ) ) ) {
									if ( typeof amount === 'string' && everest_forms_pro.getCurrency().code === 'EUR' ) {
										map_field_amount = parseFloat( amount.replace( ',', '.' ) );
									} else {
										map_field_amount = parseFloat( amount.replace( ',', '' ) );
									}

									subtotal += quantity * map_field_amount;
								}
							});
						} else {
							if ( ! isNaN( parseFloat( map_field_amount ) ) ) {
								map_field_amount = parseFloat( map_field_amount );
								subtotal = quantity * map_field_amount;
							}
						}
					}
				}

				subtotalFormatted = everest_forms_pro.amountFormat( subtotal );

				if ( 'left' === currency.symbol_pos ) {
					subtotalFormattedSymbol = currency.symbol + ' ' + subtotalFormatted;
				} else {
					subtotalFormattedSymbol = subtotalFormatted + ' ' + currency.symbol;
				}

				if ( '' !== map_field_id ) {
					var $target_subtotal_field = $form.find( '.evf-field-payment-subtotal' ).find( '.evf-payment-subtotal[data-map_field="' + map_field_id + '"]' );
					$target_subtotal_field.val( subtotalFormattedSymbol );
					$target_subtotal_field.prev( 'div.evf-payment-subtotal' ).text( subtotalFormattedSymbol );
				}
			});
		},

		/**
		 * Sanitize amount and convert to standard format for calculations.
		 */
		amountSanitize: function(amount) {
			var currency = everest_forms_pro.getCurrency();
				amount   = amount.toString().replace(/[^0-9.,]/g,'');

			if ( ',' === currency.decimal_sep && ( -1 !== amount.indexOf(currency.decimal_sep) ) ) {
				if ( '.' === currency.thousands_sep && -1 !== amount.indexOf(currency.thousands_sep) ) {
					amount = amount.replace(currency.thousands_sep,'');
				} else if( '' === currency.thousands_sep && -1 !== amount.indexOf('.') ) {
					amount = amount.replace('.','');
				}
				amount = amount.replace(currency.decimal_sep,'.');
			} else if ( ',' === currency.thousands_sep && ( -1 !== amount.indexOf(currency.thousands_sep) ) ) {
				amount = amount.replace(currency.thousands_sep,'');
			}

			return everest_forms_pro.numberFormat( amount, 2, '.', '' );
		},

		/**
		 * Format amount.
		 */
		amountFormat: function(amount) {
			var currency = everest_forms_pro.getCurrency();
			amount = String(amount);

			// Format the amount
			if ( ',' === currency.decimal_sep  && ( -1 !== amount.indexOf(currency.decimal_sep) ) ) {
				var sepFound = amount.indexOf(currency.decimal_sep),
					whole    = amount.substr(0, sepFound),
					part     = amount.substr(sepFound+1, amount.strlen-1);

				amount = whole + '.' + part;
			}

			// Strip comma(,) from the amount(if it is set as the thousands separator).
			if ( currency.thousands_sep === ',' && ( amount.indexOf(currency.thousands_sep) !== -1 ) ) {
				amount = amount.replace(',','');
			}

			if ( everest_forms_pro.empty( amount ) ) {
				amount = 0;
			}

			return everest_forms_pro.numberFormat( amount, 2, currency.decimal_sep, currency.thousands_sep );
		},

		/**
		 * Get site currency settings.
		 */
		getCurrency: function() {
			var currency = {
				code: 'USD',
				thousands_sep: ',',
				decimal_sep: '.',
				symbol: '$',
				symbol_pos: 'left'
			};

			// Backwards compatibility.
			if ( 'undefined' !== typeof evf_settings.currency_code ) {
				currency.code = evf_settings.currency_code;
			}

			if ( 'undefined' !== typeof evf_settings.currency_thousands ) {
				currency.thousands_sep = evf_settings.currency_thousands;
			}

			if ( 'undefined' !== typeof evf_settings.currency_decimal ) {
				currency.decimal_sep = evf_settings.currency_decimal;
			}

			if ( 'undefined' !== typeof evf_settings.currency_symbol ) {
				currency.symbol = evf_settings.currency_symbol;
			}

			if ( 'undefined' !== typeof evf_settings.currency_symbol_pos ) {
				currency.symbol_pos = evf_settings.currency_symbol_pos;
			}

			return currency;
		},

		/**
		 * Format number.
		 * @link http://locutus.io/php/number_format/
		 */
		numberFormat: function (number, decimals, decimalSep, thousandsSep) {
			number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
			var n = !isFinite(+number) ? 0 : +number;
			var prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
			var sep = (typeof thousandsSep === 'undefined') ? ',' : thousandsSep;
			var dec = (typeof decimalSep === 'undefined') ? '.' : decimalSep;
			var s;
			var toFixedFix = function (n, prec) {
				var k = Math.pow(10, prec);

				return '' + (Math.round(n * k) / k).toFixed(prec)
			};

			// @TODO: for IE parseFloat(0.55).toFixed(0) = 0;
			s = ( prec ? toFixedFix( n, prec ) : '' + Math.round(n) ).split('.');
			if (s[0].length > 3) {
				s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
			}

			if ((s[1] || '').length < prec) {
				s[1] = s[1] || '';
				s[1] += new Array(prec - s[1].length + 1).join('0');
			}

			return s.join(dec);
		},

		/**
		 * Empty check similar to PHP.
		 *
		 * @link http://locutus.io/php/empty/
		 */
		empty: function(mixedVar) {
			var undef;
			var key;
			var i;
			var len;
			var emptyValues = [undef, null, false, 0, '', '0'];

			for (i = 0, len = emptyValues.length; i < len; i++) {
				if (mixedVar === emptyValues[i]) {
					return true
				}
			}

			if ( 'object' === typeof mixedVar ) {
				for ( key in mixedVar ) {
					if ( mixedVar.hasOwnProperty(key) ) {
						return false;
					}
				}

				return true;
			}

			return false;
		},

		/**
		 * Color Field.
		 */
		init_colorpicker: function () {
			$( '.evf-color-picker' ).each( function() {
				var colorInputContainer = $( this ),
					colorInput = $( this ).find( '.evf-cp-input' );

				$( this ).find( '.evf-color-picker-bg' ).css( 'height', $( this ).find( 'input[type=text]' ).css( 'height' ) );

				colorInput.on( 'input', function() {
					$(this).parent( '.evf-color-picker-bg' ).css({
						backgroundColor: $(this).val()
					});
					colorInputContainer.find( 'input[type=text]' ).val( $( this ).val() );
					colorInput.attr( 'value', $( this ).val());
				});

				$(this).find( 'input[type=text]' ).on( 'input change paste', function() {
					var colorCode = $( this ).val();

					if( /^#/.test( colorCode ) ) {
						colorCode = colorCode.replace(/^#[^0-9A-F]/gi, "").substring( 0, 7 );
					} else {
						colorCode = colorCode.replace(/[^0-9A-F]/gi, "").substring( 0, 6 );
					}

					colorCode  = colorCode.length && ! /^#/.test( colorCode ) ? '#' + colorCode : colorCode;

					$( this ).val( colorCode );

					if( 4 === colorCode.length || 7 === colorCode.length ) {
						$( this ).prev( '.evf-color-picker-bg' ).css( 'background', colorCode );
						$( this ).prev( '.evf-color-picker-bg' ).removeClass( 'no-color' );
					} else {
						$( this ).prev( '.evf-color-picker-bg' ).prop( 'style' ).removeProperty( 'background' );
						$( this ).prev( '.evf-color-picker-bg' ).addClass( 'no-color' );
					}

					$.validator.addMethod( 'hexColorCode', function( value, element, param ) {
						var val_len  = value.length;
						return this.optional( element ) || 4 === val_len || 7 === val_len && ( val_len && /(^#[0-9A-F]{6}$)|(^#[0-9A-F]{3}$)/i.test( value ) );
					}, everest_forms_params.i18n_color_code );

					jQuery( this ).rules( 'add', { hexColorCode: [] });

				});
			});
		},

		/**
		 * Lookup field.
		 */
		init_lookupField:function(){
			$(document).on('change', '.evf-lookup', function (e) {
				var $this = $(this),
				$form = $this.closest('.everest-form'),
				formID = $form.attr('data-formid'),
				allFields = $form.find('.evf-frontend-row').find('.evf-frontend-grid').find('.input-text');
				var el = $form.find('.evf-submit-container ').find('.evf-submit')[0];
				allFields.push(el);
				var myObj = {};
				myObj.filterby = [];
				allFields.each(function(index, el) {
					var field_id = $(el).attr('id');
					if ('undefined' !== typeof $(el).data('filter-by') && '' != $(el).data('filter-by')) {
						myObj.filterby[field_id] = [$(el).data('filter-by').split(' '), $(el).data('lookup-field-name')];
					}
				});
				var fields = myObj.filterby;
				var searchKey = $this.attr('id').split('_')[1];
				var lookupFormID = $this.data('lookup-form-id');
				var lookupFormField = $this.data('lookup-field-name');
				var filterArr = {};

				for(var fieldID in fields){
					var doFilterCount = 0;
					var fieldValue = {};
					var field = fields[fieldID];
					if(field[0].includes(searchKey)){
						for (var key of field[0]) {
							id = 'evf-'+formID+'-field_'+key;
							var elValue = $(document).find('#'+id).val();
							var lookupField = field[1];
							if(null !== elValue){
								++doFilterCount;
								fieldValue[key]={elValue,lookupField};

							}
						}
					}
					if(doFilterCount === field[0].length){
						filterArr[fieldID]= fieldValue;
					}
				}
				if(filterArr.length < 0){
					return;
				}
				$.ajax({
					url:evf_lookup_field_params.ajax_url,
					type:"POST",
					data:{
						action:'everest_forms_lookup_field_filters',
						security:evf_lookup_field_params.ajax_everest_forms_lookup_nonce,
						lookupformid:lookupFormID,
						lookupformfield:lookupFormField,
						filterArr:filterArr,
					},
					beforeSend: function () {
						$(document).find('.everest-forms-submit-button').prop('disabled', true);
					},
					success:function (res) {
						$(document).find('.everest-forms-submit-button').prop('disabled', false);
						if(res.success === true){
							for(var key in res.data){
								displayFormat = $(document).find("#"+key).data('display-format');
								isMultiSelect = $(document).find("#"+key).attr('multiple');
								switch (displayFormat) {
									case 'dropdown':
										if(isMultiSelect === 'multiple'){
											var html = '';
										}else{
											var html = '<option value="">Choose value</option>';
										}
										var arrayValue = res.data[key];
										var datas = [];
										for(var val of arrayValue){
											for(var data of JSON.parse(val)){
												if(datas.includes(data)){
													continue;
												}
												datas.push(data);
											}
										}
										for(var data of datas){
											html +='<option value="'+data+'">'+data+'</option>';
										}
										$(document).find('#'+key).html(html);
										break;
								}
							}
						}
					}
				});
			});
		},

		/**
		 * Update form filling progress on the Progress field.
		 */
		updateProgress: function() {
			if( $( document ).find( '.evf-field-progress' ).length ) {
				var $form         = $( 'form.everest-form' ),
					$form_id      = $form.data( 'formid' );
					$fields       = $form.find( '.evf-field-container' ).find( '.evf-field' ).not( '.evf-field-progress, .evf-field-payment-single, .evf-field-payment-total, .evf-field-payment-subtotal, .evf-field-hidden, .evf-field-title, .evf-field-html, .evf-field-divider, .evf-field-reset, .evf-field-captcha' ),
					$field_values = [];

				if( $( document ).find( '.evf-field-payment-single' ).length ) {
					$( document ).find( '.evf-field-payment-single' ).each( function () {
						$single_item_field = $( this );
						if( $single_item_field.find( 'input.evf-payment-price' ).hasClass( 'evf-payment-user-input' ) ) {
							$fields.push( $single_item_field[0] );
						}
					} );
				}

				$fields.each( function ( ) {
					var $field 		   = $( this ),
						$field_classes = $field.attr( 'class' ),
						$field_id      = $field.data( 'field-id' ),
						$field_type    = $field_classes.match( /evf-field-(.*) form-row/i )
						$field_value   = '';

						if( null !== $field_type ) {
							$field_type = $field_type[1];
						} else {
							$field_type = 'text';
						}

					switch (  $field_type ) {
						case 'checkbox':
						case 'radio':
						case 'payment-multiple':
						case 'payment-checkbox':
						case 'rating':
						case 'yes-no':
						case 'likert':
						case 'scale-rating':
							if( ! $field.hasClass( 'evf-has-error' ) ) {
								$field.find( '[name^=everest_forms]' ).each( function() {
									if($(this).is(':checked')) {
										$field_value = $(this).val();
									}
								});

							}
						break;

						case 'image-upload':
						case 'file-upload':
							if( ! $field.hasClass( 'evf-has-error' ) ) {
								$field_value = $field.find( `#everest-forms-${ $form_id }-field_${ $field_id }` ).val();
							}
							break;

						case 'signature':
							if( ! $field.hasClass( 'evf-has-error' ) ) {
								var $value = $field.find( `#evf-signature-img-input-${ $field_id }` ).val();

								if( $value ) {
									$field_value = $value;
								}
							}
							break;

						case 'wysiwyg':
							if( ! $field.hasClass( 'evf-has-error' ) ) {
								$field_value = tinyMCE.get( `evf-${ $form_id }-field_${ $field_id }` ).getContent();
							}
							break;

						case 'credit-card':
							if( ! $field.hasClass( 'evf-has-error' ) ) {
								if ( ! $( '#evf-form-' + $form_id ).find( ".everest-forms-gateway[data-gateway='stripe']").hasClass('StripeElement--empty') && $( '#evf-form-' + $form_id ).find( '.evf-field-credit-card' ).is(':visible') ){
									$field_value = 'not empty';
								}
							}
							break;

						case 'range-slider':
							if( ! $field.hasClass( 'evf-has-error' ) ) {
								var $value = $( `#evf-slider-input-${ $field_id }` ).val();
								if( parseFloat( $value ) ) {
									var $field_value = $( `#evf-slider-input-${ $field_id }` ).val();
								}
							}
							break;

						case 'payment-single':
							if( ! $field.hasClass( 'evf-has-error' ) ) {
								var $value = $( `#evf-${ $form_id }-field_${ $field_id }` ).val();

								if( parseFloat( $value ) ) {
									var $field_value = $( `#evf-${ $form_id }-field_${ $field_id }` ).val();
								}
							}
							break;

						case 'privacy-policy':
							if( ! $field.hasClass( 'evf-has-error' ) ) {
								if( $( `#evf-${ $form_id }-field_${ $field_id }` ).is(':checked') ) {
									$field_value = $( `#evf-${ $form_id }-field_${ $field_id }` ).val();
								}
							}
						break;

						case 'payment-total':
						case 'payment-subtotal':
						case 'hidden':
						case 'progress':
							break;

						default:
							if( ! $field.hasClass( 'evf-has-error' ) ) {
								$field_value = $( `#evf-${ $form_id }-field_${ $field_id }` ).val();
							}
							break;
					}
					$field_values.push( $field_value );
				} );

				var $total_fields  = $field_values.length,
					$completed_fields = 0;

				$.each( $field_values, function ( index, value ) {
					if( value ) {
						$completed_fields++;
					}
				} );

				var $completed_percentage = Math.ceil( ( $completed_fields / $total_fields ) * 100 );

				$( document ).find( '.evf-field-progress' ).find( 'progress.evf-progress, input.input-text' )
				.val( $completed_percentage )
				.next( '.evf-progress-percentage' ).text( `${  $completed_percentage }%` );

				if(  99 < parseInt( $completed_percentage ) ) {
					$( document ).find( '.evf-field-progress' ).find( 'progress' ).addClass( 'evf-progress-success' );
				} else {
					$( document ).find( '.evf-field-progress' ).find( 'progress' ).removeClass( 'evf-progress-success' );
				}

			}
		}
	};

	everest_forms_pro.init(jQuery);
});

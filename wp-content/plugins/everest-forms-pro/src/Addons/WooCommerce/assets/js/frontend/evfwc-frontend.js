jQuery( function ( $ ) {
	var $form = $( ".cart" );

	// List messages to show for required fields. Use name of the field as key.
	var error_messages = {};
	$( '.evf-field' ).each( function() {
			var field_id      = $( this ).data( 'field-id' );
			var error_message = $( this ).data( 'required-field-message' );
			var key           = 'everest_forms[form_fields][' + field_id + ']'; // Name of the input field is used as a key.

			if ( $( this ).is( '.evf-field-image-upload' ) ) {
				key = $(this).find( '.dropzone-input' ).attr('name');
			}else if ( $( this ).is( '.evf-field-file-upload' ) ) {
				key = $(this).find( '.dropzone-input' ).attr('name');
			}else if ( $( this ).is( '.evf-field-checkbox, .evf-field-payment-checkbox' ) ) {
				key = key + '[]';
			}else if ( $( this ).is( '.evf-field-email' ) ) {
				// For when the confirm is disabled.
				key = 'everest_forms[form_fields][' + field_id + ']';
				error_messages[ key ] = {
					required: error_message, // Set message using 'required' key to avoid conflicts with other validations.
				};

				// For when the confirm is enabled.
				key = 'everest_forms[form_fields][' + field_id + '][primary]';
				error_messages[ key ] = {
					required: error_message, // Set message using 'required' key to avoid conflicts with other validations.
				};
				key = 'everest_forms[form_fields][' + field_id + '][secondary]';
				error_messages[ key ] = {
					required: error_message, // Set message using 'required' key to avoid conflicts with other validations.
				};
				error_message = null;
			}
			/**
			 * Check if the error message has been already set (null value in error_message variable
			 * should indicate that the message has already been set).
			 */
			if ( error_message ) {
				error_messages[ key ] = {
					required: error_message, // Set message using 'required' key to avoid conflicts with other validations.
				};
			}
		});

	$form.validate({
		messages: error_messages,
		ignore: '',
		errorClass: 'evf-error',
		validClass: 'evf-valid',
		errorPlacement: function( error, element ) {
			if ( element.closest( '.evf-field' ).is( '.evf-field-privacy-policy' ) ) {
				element.closest( '.evf-field' ).append( error );
			} else if ( element.closest( '.evf-field' ).is( '.evf-field-range-slider' ) ) {
				if ( element.closest( '.evf-field' ).find( '.evf-field-description' ).length ) {
					element.closest( '.evf-field' ).find( '.evf-field-description' ).before( error );
				} else {
					element.closest( '.evf-field' ).append( error );
				}
			} else if ( element.closest( '.evf-field' ).is( '.evf-field-scale-rating' ) ) {
				element.closest( '.evf-field' ).find( '.everest-forms-field-scale-rating' ).after( error );
			} else if ( 'radio' === element.attr( 'type' ) || 'checkbox' === element.attr( 'type' ) ) {
					element.closest( '.evf-field-checkbox' ).find( 'label.evf-error' ).remove();
					element.parent().parent().parent().append( error );
			} else if ( element.is( 'select' ) && element.attr( 'class' ).match( /date-month|date-day|date-year/ ) ) {
				if ( element.parent().find( 'label.evf-error:visible' ).length === 0 ) {
					element.parent().find( 'select:last' ).after( error );
				}
			} else if ( element.is( 'select' ) && element.hasClass( 'evf-enhanced-select' ) ) {
				if ( element.parent().find( 'label.evf-error:visible' ).length === 0 ) {
					element.parent().find( '.select2' ).after( error );
				}
			} else if ( element.hasClass( 'evf-smart-phone-field' ) || element.hasClass( 'everest-forms-field-password-primary' ) || element.hasClass( 'everest-forms-field-password-secondary' ) ) {
				if( element.parents('span.input-wrapper').length ) {
					element.parents('span.input-wrapper').after( error );
				} else {
					element.parent().after( error );
				}
			} else {
				if( element.parents('span.input-wrapper').length ) {
					element.parents('span.input-wrapper').after( error );
				} else {
					error.insertAfter( element );
				}
			}
		},
		highlight: function( element, errorClass, validClass ) {
			var $element  = $( element ),
							$parent   = $element.closest( '.form-row' ),
							inputName = $element.attr( 'name' );

			if ( $element.attr( 'type' ) === 'radio' || $element.attr( 'type' ) === 'checkbox' ) {
				$parent.find( 'input[name="' + inputName + '"]' ).addClass( errorClass ).removeClass( validClass );
			} else {
				$element.addClass( errorClass ).removeClass( validClass );
			}
			$parent.removeClass( 'everest-forms-validated' ).addClass( 'everest-forms-invalid evf-has-error' );
		},
		unhighlight: function( element, errorClass, validClass ) {
			var $element  = $( element ),
			$parent   = $element.closest( '.form-row' ),
			inputName = $element.attr( 'name' );

			if ( $element.attr( 'type' ) === 'radio' || $element.attr( 'type' ) === 'checkbox' ) {
				$parent.find( 'input[name="' + inputName + '"]' ).addClass( validClass ).removeClass( errorClass );
			} else {
				$element.addClass( validClass ).removeClass( errorClass );
			}

			$parent.removeClass( 'everest-forms-invalid evf-has-error' );
		},
		onkeyup: function( element, event ) {
			// This code is copied from JQuery Validate 'onkeyup' method with only one change: 'everest-forms-novalidate-onkeyup' class check.
			var excludedKeys = [ 16, 17, 18, 20, 35, 36, 37, 38, 39, 40, 45, 144, 225 ];

			// Disable onkeyup validation for some elements (e.g. remote calls).
			if ( $( element ).hasClass( 'everest-forms-novalidate-onkeyup' ) ) {
				return;
			}

			if ( 9 === event.which && '' === this.elementValue( element ) || -1 !== $.inArray( event.keyCode, excludedKeys ) ) {
				return;
			} else if ( element.name in this.submitted || element.name in this.invalid ) {
				this.element( element );
			}
		},
		onfocusout: function( element ) {
			// This code is copied from JQuery Validate 'onfocusout' method with only one change: 'everest-forms-novalidate-onkeyup' class check.
			var validate = false;

			// Empty value error handling for elements with onkeyup validation disabled.
			if ( $( element ).hasClass( 'everest-forms-novalidate-onkeyup' ) && ! element.value ) {
				validate = true;
			}

			if ( ! this.checkable( element ) && ( element.name in this.submitted || ! this.optional( element ) ) ) {
				validate = true;
			}

			if ( validate ) {
				this.element( element );
			}
		},
		onclick: function( element ) {
			var validate = false,
				type = ( element || {} ).type,
				$el = $( element );

			if ( 'checkbox' === type ) {
				$el.closest( '.evf-field-checkbox' ).find( 'label.evf-error' ).remove();
				validate = true;
			} else if ( ! 'select-multiple' === type ) {
				$( element ).valid();
			}

			if ( validate ) {
				this.element( element );
			}
		},
	});

	//Validation by pass for wc quantity field.
	var qty_max = $( document ).find( '[name="quantity"]' );
	if( qty_max.attr( 'max' ) === "" ){
		qty_max.removeAttr('max');
	}
})

/* global wp, pwsL10n */
( function( $ ) {
	'use strict';

	var evf_password_validation = {
		$document: $( document ),

		// Can define the blacklist here.
		blacklist: [],

		// Begin event listening routine.
		init: function() {
            evf_password_validation.$document.ready( function() {
				// For strong password validation.
				$( '*[data-strength="password_validation"]' ).closest( 'form' ).find( '.everest-forms-submit-button' ).attr( 'disabled', true );
				evf_password_validation.$document.on( 'keyup change', '*[data-strength="password_validation"]', function () {
                    evf_password_validation.password_functon( $( this ) );
				});
			});
		},
        
        // Returns the html for initial password validation rules.
		strng_txt_init: function( id ) {
			return '<div class="everest-forms-field-row" id="pass-strength-result-' + id + '"><div class="everest-forms-pass-strength password-strong"><div class="strong-password-message"> ' + everest_forms_password_params.strong + '</div><div class="everest-forms-password-fields-wrap"><ul><li class = "lowercase"><label><span class="evr-dot"></span> ' + everest_forms_password_params.one_lowercase + '</label></li><li class="uppercase"><label><span class="evr-dot"></span> ' + everest_forms_password_params.one_uppercase + '</label></li><li class="number"><label><span class="evr-dot"></span> ' + everest_forms_password_params.one_number + '</label></li><li class="spec-char"><label><span class="evr-dot"></span> ' + everest_forms_password_params.one_special_character + '</label></li><li class="min-length"><label><span class="evr-dot"></span> ' + everest_forms_password_params.min_length + '</label></li></ul></div></div></div>';
		},

		// Maps the events to the right functions to be able to check passwords.
		password_functon: function( el ) {
			var $this  = el,
				el_id  = $this.attr( 'id' ),
				data   = $this.data( 'strength' ),
				status = $( '#pass-strength-result-' + el_id );

			if ( 1 !== status.length ) {
				var $el_to_append = $this.closest( '.everest-forms-field-row' ).length ? $this.closest( '.everest-forms-field-row' ) : $this.closest( '.evf-field-password-input' );
			    var to_append = evf_password_validation.strng_txt_init( el_id );

				if ( 0 === $this.val().length ) {
					return 0;
				}

				if ( $el_to_append.next().is( $( '.everest-forms-field-sublabel' ) ) ) {
					// Append after the input control.
					$el_to_append.next().after( to_append );
				} else {
					// Append after the sublabel.
					$el_to_append.after( to_append );
				}

				status = $( '#password-strength' + el_id );
			}

			// Delegate the data to decide how to manip field, and the element itself with the password.
			evf_password_validation.strength_level( $this.val(), status, data );
		},

		// Manipulates the password strength div element based on the password strength passed.
		strength_level: function ( password, el, data ) {			
			var flag = evf_password_validation.validate_password( password, el );			
			var form = el.closest( 'form' );
			if(flag) {
				form.find( '.everest-forms-submit-button' ).attr( 'disabled', false );
				el.find( '.strong-password-message' ).show();
				el.find( '.everest-forms-password-fields-wrap' ).hide();
			} else {
				form.find( '.everest-forms-submit-button' ).attr( 'disabled', true );
				el.find( '.strong-password-message' ).hide();
				el.find( '.everest-forms-password-fields-wrap' ).show();
			}
		},

		//Validation logic.
		validate_password: function ( password, el ) {
			var flag = true;
			if( 0 === password.length ) {
				el.find( 'li label' ).removeClass( 'strong' );
				el.find( 'li [type="checkbox"]' ).prop( 'checked', false );
				return 0; //no Characters.
			}
			if( password.match( /[0-9]/ ) ) {
				el.find( 'li.number label' ).addClass( 'strong' );
			} else {
				el.find( 'li.number label' ).removeClass('strong' );
				flag = false;
			}
			if( password.match( /[a-z]/ ) ) {
				el.find( 'li.lowercase label' ).addClass('strong' );
			} else {
				el.find( 'li.lowercase label' ).removeClass('strong' );
				flag = false;
			}
			if( password.match( /[A-Z]/ ) ) {
				el.find( 'li.uppercase label' ).addClass('strong' );
			} else {
				el.find( 'li.uppercase label' ).removeClass( 'strong' );
				flag = false;
			}
			if( password.match( /[!@#$%\^&*(){}[\]<>?/|\-]/ ) ) {
				el.find( 'li.spec-char label' ).addClass( 'strong' );
			} else {
				el.find( 'li.spec-char label' ).removeClass( 'strong' );
				flag = false;
			}
			if( password.length >= 8 ) {
				el.find( 'li.min-length label' ).addClass( 'strong' );
			} else {
				el.find( 'li.min-length label' ).removeClass( 'strong' );
				flag = false;
			}
			return flag;
		}
    }

    evf_password_validation.init();
})( jQuery );

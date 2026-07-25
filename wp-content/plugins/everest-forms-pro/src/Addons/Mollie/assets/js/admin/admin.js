/**
 * EverestFormsMollie Admin JS
 */
(function ($) {
	var EverestFormsMollie = {
        /**
         * Initialization.
         */
		init: function () {
			EverestFormsMollie.bindUIActions();
			$(document).ready(EverestFormsMollie.ready);
		},

		/**
		 * Document Ready
		 */
		ready: function () {
            EverestFormsMollie.changePaymentMode($('#everest_forms_mollie_test_mode'));
            EverestFormsMollie.enableDisableMollieSettings( $('#everest-forms-panel-field-paymentsmollie-enable_mollie') );
			EverestFormsMollie.enableDisableMollieRecurringSettings( $('#everest-forms-panel-field-mollie-enable_mollie_recurring') );
		},

		/**
		 * Element bindings
		 */
		bindUIActions: function () {
            $('#everest_forms_mollie_test_mode').on('click', function () {
                EverestFormsMollie.changePaymentMode($(this));
            });

            $( document ).on( 'click', '#everest-forms-panel-field-paymentsmollie-enable_mollie', function () {
                EverestFormsMollie.hideAndShowGatewayOption( $(this) );
                EverestFormsMollie.enableDisableMollieSettings( $(this) );
            } );

			$(document).on('click', '#everest-forms-panel-field-mollie-enable_mollie_recurring', function() {
				EverestFormsMollie.enableDisableMollieRecurringSettings($(this));
			});
		},

        /**
         *
		 * @param {jQuery} $this Checkbox element.
         */
        changePaymentMode: function ($this) {
            if($this.is(':checked')) {
                $('#everest_forms_mollie_live_api_key').closest('.everest-forms-global-settings').hide();
                $('#everest_forms_mollie_test_api_key').closest('.everest-forms-global-settings').show();
            } else {
                $('#everest_forms_mollie_test_api_key').closest('.everest-forms-global-settings').hide();
                $('#everest_forms_mollie_live_api_key').closest('.everest-forms-global-settings').show();
            }
        },

        /**
         *
		 * @param {jQuery} $this Checkbox element.
         */
        hideAndShowGatewayOption: function ( $this ) {
            if ($this.is( ':checked' )) {
                $this.parents( '.evf-content-mollie-settings' ).find( '.evf-mollie-gateway-option' ).show();
            } else {
                $this.parents( '.evf-content-mollie-settings' ).find( '.evf-content-mollie-settings' ).hide();
            }
        },

        /**
         *
		 * @param {jQuery} $this Checkbox element.
         */
        enableDisableMollieSettings: function ( $this ) {
            if ($this.is( ':checked' )) {
				$('.evf-mollie-gateway-conditional').removeClass('everest-forms-hidden');
				$('.evf-mollie-gateway-additional-settings-wrap').removeClass('everest-forms-hidden');
			} else {
				$('.evf-mollie-gateway-additional-settings-wrap').addClass('everest-forms-hidden');
				$('.evf-mollie-gateway-conditional').addClass('everest-forms-hidden');
			}
        },

		/**
         *
		 * @param {jQuery} $this Checkbox element.
         */
        enableDisableMollieRecurringSettings: function ( $this ) {
            if ($this.is( ':checked' )) {
				$('.evf-mollie-gateway-recurring-wrap').removeClass('everest-forms-hidden');
			} else {
				$('.evf-mollie-gateway-recurring-wrap').addClass('everest-forms-hidden');
			}
        },
	};
	EverestFormsMollie.init();
})(jQuery);

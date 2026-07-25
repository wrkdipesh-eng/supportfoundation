/**
 * EverestFormsBrevo JS
 */

(function($) {

    var EverestFormsBrevo = {

        /**
         * Start the engine.
		 *
		 * @since 1.9.3
         */
        init: function() {
            EverestFormsBrevo.bindUIActions();
        },

        /**
         * Element bindings.
		 *
		 * @since 1.9.3
         */
        bindUIActions: function() {
            $(document).on('change', '.evf-brevo-double-optin', function() {

                if($(this).prop('checked')) {
                    $(this).closest('.evf-connection-block').find('.everest-forms-panel-field.double-optin__wrapper').removeClass('everest-forms-hidden');
                } else {
                    $(this).closest('.evf-connection-block').find('.everest-forms-panel-field.double-optin__wrapper').addClass('everest-forms-hidden');
                }
            });

            $(document).on('ready', function(){
                if($('.evf-brevo-double-optin').prop('checked')){
                    $('.evf-brevo-double-optin').closest('.evf-connection-block').find('.everest-forms-panel-field.double-optin__wrapper').removeClass('everest-forms-hidden');
                } else {
                    $('.evf-brevo-double-optin').closest('.evf-connection-block').find('.everest-forms-panel-field.double-optin__wrapper').addClass('everest-forms-hidden');
                }
            });
        }

    };

    EverestFormsBrevo.init();

})(jQuery);

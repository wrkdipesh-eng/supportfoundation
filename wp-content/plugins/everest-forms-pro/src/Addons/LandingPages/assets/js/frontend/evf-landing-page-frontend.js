/**
 * EverestFormsLandingPage JS
 */

(function($) {

    var EverestFormsLandingPage = {

       /**
        * Start the engine.
        */
        init: function() {
            $(document).ready( EverestFormsLandingPage.ready );
            $( '#everest-forms-panel-field-settings-everest_forms_form_landing_page_enable_evf_footer' ).on( 'change', EverestFormsLandingPage.showHideBranding );
        },

        ready: function() {
	        EverestFormsLandingPage.showHideBranding.call($('#everest-forms-panel-field-settings-everest_forms_form_landing_page_enable_evf_footer'));
        },

        showHideBranding: function() {

            var $this = $( this ),
                $footerBranding = $( '#everest-forms-panel-field-settings-everest_forms_form_landing_page_enable_branding-wrap' );

            if ( $this.is( ':checked' ) ) {
                $footerBranding.show();
            } else {
                $footerBranding.hide();
            }
        },
    }
    EverestFormsLandingPage.init();
})(jQuery);

/**
 * EverestFormsToolsExportEntry JS
 */
 ( function($) {
	var EverestFormsToolsExportEntry = {

	   /**
		* Start the engine.
		*/
		init: function() {

		   // Document ready
		   $( document ).ready( EverestFormsToolsExportEntry.ready);
		   // Build UI Actions
		   EverestFormsToolsExportEntry.bindUIActions();

		},

		ready: function() {
			// Setup export entries.
			$( document.body ).on( 'change', '.evf-tools-export-entries-select', function () {
				var form_id = $( this ).find( 'option:selected' ).val(),
					form_data = new FormData();

				form_data.append( 'form_id', form_id );
				form_data.append( 'action', 'everest_forms_export_entry_action' );
				form_data.append( 'security', evf_tools_export_entries_params.ajax_tool_export_entries_nonce );

				$.ajax({
					url: evf_email_params.ajax_url,
					cache: false,
					contentType: false,
					processData: false,
					data: form_data,
					type: 'POST',
					beforeSend: function () {
						$( '<i class="evf-loading evf-loading-active"></i>' ).insertAfter( '.evf-tools-export-entries-select' );
					},
					complete: function( response ) {
						if ( response.responseJSON.success ) {
							$('.evf-tools-export-entries-options').html( response.responseJSON.data.html );
							$('.evf-tools-export-entries-options').removeClass( 'everest-forms-hidden' );
							$('.evf-tools-entries-export-options-search-fiels-select').trigger( 'evf-enhanced-select-init' );
							$( '#evf-tools-entries-export-date-range' ).flatpickr( {
								mode: "range",
								dateFormat: "Y-m-d",
							} );
						}
						$( '.evf-tools-export-entries-select' ).next( '.evf-loading.evf-loading-active' ).remove();
					}
				});
			});
		},

		bindUIActions: function() {
		}
	}
	EverestFormsToolsExportEntry.init();
})(jQuery);

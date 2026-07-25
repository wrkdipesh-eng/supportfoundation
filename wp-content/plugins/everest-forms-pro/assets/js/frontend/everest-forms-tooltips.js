( function ( $ ) {
	'use strict';

	var EverestFormsTooltip = {
		init: function () {
			$( EverestFormsTooltip.ready );
			EverestFormsTooltip.initTooltips();
		},
		ready: function () {
			$( document )
				.find( 'label.everest-forms-tooltip' )
				.each( function () {
					if ( $( this ).has( 'abbr' ).length > 0 ) {
						$( this )
							.prev( 'span' )
							.css( 'display', 'inline-block' )
							.insertBefore( $( this ).find( 'abbr' ) );
					} else {
						$( this ).append(
							$( this )
								.prev( 'span' )
								.css( 'display', 'inline-block' )
							);
					}
				} );
		},

		/**
		 * Initialize everest-forms form area tooltips.
		 *
		 * @since 1.0.0
		 */
		initTooltips: function () {
			if ( typeof jQuery.fn.tooltipster === 'undefined' ) {
				return;
			}
			$( '.everest-forms-help-tooltip' ).tooltipster( {
				contentAsHTML: true,
				position: 'right',
				animation: 'fade',
				delay: 200,
				maxWidth: 300,
				multiple: true,
				interactive: true,
				debug: false,
				IEmin: 11,
				trigger:'custom',
				triggerOpen: {
				  mouseenter: true,
				  click: true,
				  tap: true
				},
				triggerClose: {
				  mouseleave: true,
				  click: true,
				  tap: true
				},
			} );
		},
	};

	// Init.
	EverestFormsTooltip.init();
} )( jQuery );

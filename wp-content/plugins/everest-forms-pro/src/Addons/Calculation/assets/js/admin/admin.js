/**
 * EverestFormsCalculations JS
 */
 ( function ($) {
	var EverestFormsCalculations = {

		init: function() {
			EverestFormsCalculations.bindUIActions();
			$( document.body ).on( 'evf_after_field_append', function( e, drag_id ){
				EverestFormsCalculations.appendCodeMirrorOnDrag( drag_id );
			} );
			$( document ).ready( EverestFormsCalculations.ready );
		},

		/**
		 * Document Ready
		 */
		ready: function() {
			// On Ready
			$(document).on( 'change', '.everest-forms-field-option-row-field_calculation input', function() {
				if ( $( this ).is(':checked') ) {
					$( this ).parent().closest('.everest-forms-field-option-row-field_calculation').siblings('.evf-field-calculation-container').fadeIn('slow');
				} else {
					$( this ).parent().closest('.everest-forms-field-option-row-field_calculation').siblings('.evf-field-calculation-container').fadeOut('slow');
				}
			})

			$( document ).find('.everest-forms-field-option-row-calculation_field textarea' ).each( function(e) {
				EverestFormsCalculations.checkFormula(e, this);
			});

			// Validate formula
			$( document ).on( 'click', '.everest-forms-pro-validate-formula', function( e ){
				e.preventDefault();
				EverestFormsCalculations.validateFormula( $( this ) );
			})

			$( document ).on('click', '.smart-tag-field', function(e) {
				e.preventDefault();
				EverestFormsCalculations.addSmartTagIntoCodeMirror( $( this ) );
			});

			$( document ).on('click', '.evf-calculation-shortcut-container', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$(this).closest('.evf-calculation-shortcut-container').siblings('.evf-calculation-shortcut-list').slideToggle('fast');
			});

			$( document ).on( 'click', '.evf-calculation-shortcut-item', function( e ){
				e.preventDefault();
				EverestFormsCalculations.addOperator( $( this ) );
			});
		},

		/**
		 * Validation of formula.
		 */
		validateFormula : function ( $el ){
			var spinner = '<i class="evf-loading evf-loading-active"></i>';
			$el.append(spinner);

		$buttonGrandParent = $el.parent().parent();
		const fieldId = $buttonGrandParent.closest('.everest-forms-field-option-row').data('field-id'),
		data = {
			action : 'everest_forms_pro_calculations_validate_formula',
			form_id : $( '#everest-forms-builder-form').data('id'),
			code : EverestFormsCalculations.getCode( fieldId ),
			field_id: fieldId,
			nonce : everest_forms_calculations_params.ajax_nonce,
		}
			  $.post( everest_forms_calculations_params.ajax_url, data)
				.done( function( res ) {
					if( res.success ){
						$el.parent().find( '.everest-forms-pro-validate-formula-status' ).text(everest_forms_calculations_params.formula_valid_message).css('color','green');
					}else{
						$el.parent().find( '.everest-forms-pro-validate-formula-status' ).text('');
						$.alert({
							title: everest_forms_calculations_params.syntax_error_title,
							content: res.data[0],
							icon: 'dashicons dashicons-info',
							type: 'red',
							buttons: {
								ok: {
									text: evf_data.i18n_ok,
									btnClass: 'btn-confirm',
									keys: [ 'enter' ]
								}
							}
						});
					}
				})
				.fail( function(xhr, textStatus ){

				})
				.always ( function() {
					$el.find('.evf-loading').remove();
				})
		},
		/**
		 * Get code.
		 */
		getCode: function ( fieldId ){
			code = $('#everest-forms-field-option-' + fieldId + '-calculation_field').val();
			return code;
		},

		/**
		 * Element bindings
		 */
		bindUIActions: function() {

			// Save and Continue Toggler
			$( document ).on( 'change', '.evf-content-calculations-settings .evf-toggle-switch input', function(e) {
				EverestFormsCalculations.toggleContent(e, this);
			});

			$(document).on('click', function(e) {
				if (!$(e.target).closest('.evf-calculation-shortcut-container').length) {
					$('.evf-calculation-shortcut-list').slideUp('fast');
				}
			});

			EverestFormsCalculations.initializeCodeMirror();
    },

		/**
		 *  Append codemirror on field drag.
		 */
		appendCodeMirrorOnDrag: function( dragged_el_id = '' ) {

			const allowedField = [ 'number', 'payment-single' ];
			var containerId = '';
			if( dragged_el_id ){
				const containerType = $( document ).find( '#' + dragged_el_id ).data('field-type');
				if( ! allowedField.includes( containerType ) ){
					return;
				}
				containerId = $( document ).find( '#' + dragged_el_id ).data('field-id');
			}


			// Save and Continue Toggler
			$( document ).on( 'change', '.evf-content-calculations-settings .evf-toggle-switch input', function(e) {
				EverestFormsCalculations.toggleContent(e, this);
			});

			$(document).on('click', function(e) {
				if (!$(e.target).closest('.evf-calculation-shortcut-container').length) {
					$('.evf-calculation-shortcut-list').slideUp('fast');
				}
			});

			EverestFormsCalculations.initializeCodeMirror( containerId );
        },

		/**
		 *  Initialize CodeMirror.
		 */
		initializeCodeMirror : function ( containerId = ''){
			var $codeMirrorContainer = $('.everest-forms-field-option-row-calculation_field textarea');

			$codeMirrorContainer.each( ( i, el ) => {
				$el = $( el );
				const elementId = $el.parent().data('field-id');

				if( containerId != ''){
					if( containerId != elementId ){
						return;
					}
				}

				if ($el.length && typeof wp.CodeMirror !== 'undefined') {

					var customCodeEditor = wp.CodeMirror.fromTextArea($el[0], {
						indentUnit: 4,
						indentWithTabs: true,
						inputStyle: "contenteditable",
						lineNumbers: true,
						lineWrapping: true,
						styleActiveLine: true,
						continueComments: true,
						extraKeys: {
							"Ctrl-/": "toggleComment",
							"Cmd-/": "toggleComment",
							"Alt-F": "findPersistent",
							"Ctrl-F": "findPersistent",
							"Cmd-F": "findPersistent",
							"Enter": function( cm ) {
								var cursor = cm.getCursor();
								var token = cm.getTokenAt(cursor);
								var currentWord = token.string;
								var list = EverestFormsCalculations.getSuggestionList();
								var match = list.find( item => item.text === currentWord );

								if (match) {
									cm.replaceRange(match.replacement, { line: cursor.line, ch: token.start }, { line: cursor.line, ch: token.end });
									var tabSize = cm.getOption('indentUnit');
									cm.setCursor({ line: cursor.line + 1, ch: tabSize });
									return;
								}

								cm.execCommand('newlineAndIndent');
							}
						},
						direction: "ltr",
						gutters: [],
						lint: true,
						autoCloseBrackets: true,
						autoCloseTags: true,
						matchTags: {
							bothTags: true
						},
						tabSize: 2,
						mode: 'text/x-php',
						hintOptions: {
							hint: function(editor) {
								return EverestFormsCalculations.getAutocompleteData(editor);
							},
							completeSingle: false
						},
						autoRefresh:true,
					});
				}

				customCodeEditor.on('inputRead', function(editor, change, event) {
					// Trigger autocomplete only for specific input types or changes
					if (change.text[0] !== "") {
						editor.showHint({ completeSingle: false });
						if (event?.key === 'Enter' || event?.key === 'Tab') {
							editor.execCommand( 'autocomplete' );
						}
					}
				});


				customCodeEditor.on('change', function () {
					var $codeMirrorWrapper = $( customCodeEditor.display.wrapper );

					$codeMirrorWrapper.parent().find('textarea').html(
						customCodeEditor.getValue()
						.replace(/<\s*script/gi, '')
						.replace(/<\?php/gi, '')
						.replace(/\s+on\w+\s*=/gi, ' ')
						.replace(/\\n/g, '\n')
						.replace(/\\s/g, ' ')
						.replace(/\\t/g, '\t')
					);
				});

			})


		},
		/**
		 * Get code mirror instance.
		 */
		getCodeMirrorInstance : function( $el ){
			try {
				return $el.closest( '.everest-forms-field-option-row-calculation_field' ).find( '.CodeMirror' )[ 0 ].CodeMirror;
			} catch ( e ) {
				return null;
			}
		},
		/**
		 * Get suggestion list.
		 */
        getSuggestionList: function() {
			var functionArray = everest_forms_calculations_params.functionName.map( (fn) => ( { text : fn, displayText : fn, replacement : fn + '( )'}) );
			var acceptFun = [
                { text: 'if', displayText: 'if', replacement: 'if ( ):\n\nelse: \n\nendif;' },
                { text: 'elseif', displayText: 'elseif', replacement: 'elseif( ) : \n\n' },
                { text: 'else', displayText: 'else', replacement: 'else :' }
            ]
            return [...functionArray, ...acceptFun ];
        },

		/**
		 *
		 * Get auto complete data.
		 */
        getAutocompleteData: function(editor) {
            var cursor = editor.getCursor();
            var token = editor.getTokenAt(cursor);
            var start = token.start;
            var end = token.end;
            var currentWord = token.string;

            var list = EverestFormsCalculations.getSuggestionList();
            var filteredList = list.filter(item => item.text.startsWith(currentWord));

            return {
                list: filteredList.length ? filteredList : [],
                from: { line: cursor.line, ch: start },
                to: { line: cursor.line, ch: end },
                completeSingle: false
            };
        },

		/**
		 * Add Operator.
		 */
		addOperator: function( el ) {
			var editor = EverestFormsCalculations.getCodeMirrorInstance($(el));

			if ( !editor ) {
				return;
			}

			var curPos = editor.getCursor();
			var line = curPos.line;
			var ch = curPos.ch;

			// List of suggestions
			var listOfSuggestion = {
				'if():': 'if ( ):\n\nelse: \n\nendif;',
				'elseif():' : 'elseif( ) : \n\n',
				'else:' : 'else :\n\n',
			};

			var newText = $(el).html().trim();

			// Transform formula into new version.
			EverestFormsCalculations.transformFormulaIntoNewVersion( editor );

			if (listOfSuggestion.hasOwnProperty(newText)) {
				var replacementText = listOfSuggestion[newText];
				editor.replaceRange(replacementText, { line: line, ch: ch });
			} else {
				editor.replaceRange(newText, { line: line, ch: ch });
			}

			el.parent().slideUp('fast');

		},

		/**
		 * This function transform old version of formula into new version of formula.
		 */
		transformFormulaIntoNewVersion : function( editor ){
			var formula = editor.getValue();
			let regex   = /\{field_id="([^"]+)"\}/g;

			var ids 	= formula.match(regex);

			if ( ids ) {
				if( formula.match(/\^/) ){
					$.alert({
						title: everest_forms_calculations_params.operator_warning_title,
						content: everest_forms_calculations_params.operator_warning,
						icon: 'dashicons dashicons-info',
						type: 'red',
						buttons: {
							ok: {
								text: evf_data.i18n_ok,
								btnClass: 'btn-confirm',
								keys: [ 'enter' ]
							}
						}
					});
				}
				ids.map((id, index)=> {
					let fullMatch = id.match(/\{field_id="([^"]+)"\}/);
					var field_id_number = fullMatch[1].split('_')[1].replace('"','').split('-')[1];
					var fieldId_2 = '$FIELD_' + field_id_number;
					formula = formula.replace(id, fieldId_2);
				})
				editor.setValue( formula )
			}
		},

		/**
		 * Formula checker.
		 */
		checkFormula: function( e, el) {
			var value = el.value;
			if( value.match(/[\*\-\+\/\^]{2}/)) {
                $(el).siblings('.evf-calculation-formula-checker').html( 'Formula is invalid.').addClass('invalid').removeClass('valid');
                return;

            }
			if( value.match(/\}[\d]+|[\d]+\{/)) {
				$(el).siblings('.evf-calculation-formula-checker').html( 'Formula is invalid.').addClass('invalid').removeClass('valid');
				return;
			}

			var expression = value.replace(/(\{.*?\})/g, 50, value).trim();
			if( expression == '') {
				$(el).siblings('.evf-calculation-formula-checker').html( 'Please enter formula.').removeClass('valid').removeClass('invalid');
			}
			try {
				math.evaluate(expression);
				$(el).siblings('.evf-calculation-formula-checker').html( 'Formula is valid.').addClass('valid').removeClass('invalid');
			} catch (e) {
				$(el).siblings('.evf-calculation-formula-checker').html( 'Formula is invalid.').addClass('invalid').removeClass('valid');
			}
		},

		/**
		 * Toggle Save and Continue.
		 */
		toggleContent: function( e, el ) {
			var $this = $( el ),
			value = $this.prop('checked');
			if ( value === false ) {
				$this.closest('.evf-content-post-submissions-settings').find('.post-submissions-disable-message').remove();
				$this.closest('.evf-content-section-title').siblings('.evf-content-section-body').addClass('everest-forms-hidden');
				$('<p class="calculations-disable-message everest-forms-notice everest-forms-notice-info">' + everest_forms_calculations_params.i18n_disable_message + '</p>').insertAfter( $this.closest('.evf-content-section-title' ));
				EverestFormsCalculations.removeEmail();
			} else if( value === true ){
				$this.closest('.evf-content-section-title').siblings('.evf-content-section-body').removeClass('everest-forms-hidden');
				$this.closest('.evf-content-calculations-settings').find('.calculations-disable-message').remove();
				EverestFormsCalculations.addEmail();
			}
		},
		/**
		 * Add smart tag into codemirror.
		 */
		addSmartTagIntoCodeMirror : function ( $el ){
			var field_id   	= $el.data('field_id'),
				field_label	= $el.text(),
				type       	= $el.data('type'),
				$parent    	= $el.parent().parent().parent();

				var editor = EverestFormsCalculations.getCodeMirrorInstance( $parent );

					if( ! editor ){
						return;
					}

					if ( field_id !== 'fullname' && field_id !== 'email' && field_id !== 'subject' && field_id !== 'message' && 'other' !== type ) {
						field_label = field_label.split(/[\s-_]/);
						for(var i = 0 ; i < field_label.length ; i++){
							if ( i === 0 ) {
								field_label[i] = field_label[i].charAt(0).toLowerCase() + field_label[i].substr(1);
							} else {
								field_label[i] = field_label[i].charAt(0).toUpperCase() + field_label[i].substr(1);
							}
						}
						field_label = field_label.join('');
						field_id = '$FIELD_' + field_id;
					} else {
						field_id = '$FIELD_' + field_id;
					}
					var curPos = editor.getCursor();
					var line = curPos.line;
					var ch = curPos.ch;

					// Transform formula into new version.
					EverestFormsCalculations.transformFormulaIntoNewVersion( editor );

					if ( 'field' === type ) {
						editor.replaceRange( field_id, { line: line, ch: ch });
					}
		}
	}
	EverestFormsCalculations.init(jQuery);
})( jQuery );

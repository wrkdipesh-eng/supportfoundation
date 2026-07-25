"use strict";

/**
 * Everest Forms Calculations.
 *
 * @since 1.0.0
 */
var EverestFormsCalculations = window.EverestFormsCalculations || ( function( document, window, $ ) {

		/**
		 * Math functions.
		 *
		 * @since 1.7.8
		 *
		 * @type {Object}
		 */
		let allowedFunctions = {};

		const app = {

		/**
		 * Math functions.
		 *
		 * @since 1.7.8
		 *
		 * @type {Object}
		 */
		mathInnerFunctions: {},

			init: function() {
				$( document ).ready( app.ready );
				app.initModules();
			},

			/**
			 * Document Ready
			 */
			ready: function() {

				// On Ready
				$(document).on(
					"keyup change",
					".evf-frontend-row .evf-calculation-field",
					function (e) {
						app.calculate(e, this);
					}
				);

				$(document).on( 'keyup change', '.evf-frontend-row .evf-field select,.evf-frontend-row .evf-field textarea, .evf-frontend-row .evf-field, .evf-field input-text,.evf-field .evf-field-radio', function(e) {
					$(document).find( '.evf-frontend-row .evf-calculation-field.formula-field').each(function() {
						$(this).prev('.evf-payment-total').addClass('evf-payment-total-calculation').removeClass('evf-payment-total');
						app.runCalculation(this, true);
					});
				});

				// Initialize total with calculations.
				$(document).find( '.evf-frontend-row .evf-calculation-field.formula-field').each(function() {
					$(this).prev('.evf-payment-total').addClass('evf-payment-total-calculation').removeClass('evf-payment-total');
					app.runCalculation(this, true);
				});
			},

			calculate: function (e, el) {
				var $this = $(el);
				$(document).find( '.evf-frontend-row .evf-calculation-field.formula-field').each(function() {
					// Ensuring a valid field ID.
					if(undefined !== $this.parents('.evf-field').attr('data-field-id') && undefined !== $(this).attr('calculation_formula')){
						if( ! $this.hasClass('.formula-field') && $(this).attr('calculation_formula').match($this.parents('.evf-field').attr('data-field-id'))) {
							app.runCalculation(this);
						}
					}
				});
			},

			/**
			 * Run calculation.
			 */
			runCalculation: function(el, init=false) {
				var $this 					= $(el);
				var form_id 				= $this.closest('form.everest-form').attr('data-formid');
				var formula 				= $this.attr('calculation_formula');
				var decimal_places 			= $this.attr('decimal_places');
				decimal_places 				= undefined === decimal_places ? 2 : decimal_places;


				if (typeof formula === 'undefined' || formula === null) {
					return;
				}

				var complexCalculationIds = formula.match(/\$FIELD_(\d+)/g);
				var simpleCalc 			  = formula.match(/\$_RETURN/g);
				// for complex calculation
				if ( complexCalculationIds !== null || simpleCalc !== null ) {
					app.initFormulaCalculation(el, init);
					return;
				}

				// Simple calculation
				if( undefined !== formula ) {
					var matches = Array.from(formula.matchAll(/(\{.*?\})/g));
					if( undefined !== matches && '' !== matches && null !== matches ) {
						matches.map( function( m, i)  {
							var field_id = m[0].match(/\".*?\_(.*?)\"/)[1];
							var field_value = $this.closest('form.everest-form').find('input#evf-' + form_id + "-field_" + field_id).val();
							if (undefined === field_value) {
								var $field = $this
									.closest("form.everest-form")
									.find(
										'.evf-field[data-field-id="' +
											field_id +
											'"]'
									);
								if (
									$field.hasClass("evf-field-payment-multiple") ||
									$field.hasClass("evf-field-payment-checkbox")
								) {
									if (
										$field.find(".evf-payment-price:checked")
											.length
									) {
										var total = 0;
										$field
											.find(".evf-payment-price:checked")
											.each(function () {
												var amount =
													$(this).attr("data-amount");

												if (!isNaN(parseFloat(amount))) {
													var map_field_amount =
													parseFloat(amount.replace(/,/g, ''));
													total += map_field_amount;
												}
											});
										field_value = total;
									}
								}
							}
							var style = $this
								.closest("form.everest-form")
								.find("input#evf-" + form_id + "-field_" + field_id)
								.parents(".evf-field")
								.attr("style");
							if (
								undefined === field_value ||
								"" === field_value ||
								(undefined !== style &&
									style.match(/display\:\s+none/))
							) {
								field_value = 0;
							}

							formula = formula.replace(m[0], app.amountSanitize(field_value));
						})
					}
				}

				try {
					formula = formula.replace( ',', '.' );
					if($this.parents('div.evf-field').find('div').hasClass( 'evf-single-item-price' ) ) {
						formula = formula.replace(/currency.symbol/, '');
						var currency = app.getCurrency();
						var totalFormatted = math.evaluate(formula);
						totalFormatted = app.amountFormat(totalFormatted);
						var totalFormattedSymbol = '';

						if ( 'left' === currency.symbol_pos ) {
							totalFormattedSymbol = currency.symbol + ' ' + totalFormatted;
						} else {
							totalFormattedSymbol = totalFormatted + ' ' + currency.symbol;
						}

						$this.val(totalFormatted);
						$this.prev().find('span').html(totalFormattedSymbol);
						$this.trigger('input')
					} else if( $this.parents('.evf-field').hasClass('evf-field-payment-single')) {
						var totalFormatted = math.evaluate(formula);
						totalFormatted = app.amountFormat(totalFormatted);
						$this.val( totalFormatted );
						$this.trigger('input')
						$this.trigger('focusout')
					} else {
						$this.val(math.round(math.evaluate(formula), decimal_places));
						$this.trigger('input')
					}
					if(!init) {
						$this.trigger('change');
					}
				} catch (e) {
					// Error occur
				}
			},

			/**
			 * Formula initialization.
			 */
			initFormulaCalculation : function ( el, init ){
				var $el     		= $( el );
				var formula 		= $el.attr('calculation_formula');
				var formId 			= $el.closest('form.everest-form').attr('data-formid');
				var decimal_places 	= $el.attr('decimal_places');
				const fieldId 		= $el.closest( '.evf-field' ).data('field-id');


				let codeFunction = '';

				codeFunction = `
								// Define functions object.
								const $${evf_calculations_obj.functionsArrayName} = allowedFunctions;

								// Define inner functions object.
								const $${evf_calculations_obj.innerFunctionsArrayName} = EverestFormsCalculations.mathInnerFunctions;

								// Define result variable.
								let $${evf_calculations_obj.result} = '';

								// Form fields with value.
								${EverestFormsCalculations.getFormFieldsWithValue( formId, $el )}

								try {
									// Formula to calculate.
									${formula}
								} catch (error) {

								}

								return $${evf_calculations_obj.result};
							`;

							const resultFunction = new Function( 'allowedFunctions', 'EverestFormsCalculations', codeFunction );

							const result = resultFunction( allowedFunctions, EverestFormsCalculations );

							if( typeof result == 'undefined' ){
								return;
							}

							var totalFormatted = '';
							var totalFormattedSymbol = '';

							if($el.parents('div.evf-field').find('div').hasClass( 'evf-single-item-price' ) ){
								var currency = app.getCurrency();
								var totalFormatted = app.amountFormat(result);
								var totalFormattedSymbol = '';

								if ( 'left' === currency.symbol_pos ) {
									totalFormattedSymbol = currency.symbol + ' ' + totalFormatted;
								} else {
									totalFormattedSymbol = totalFormatted + ' ' + currency.symbol;
								}

								$el.val( app.parseToFloat( totalFormatted, decimal_places ) );
								$el.prev().find('span').html(totalFormattedSymbol);
								$el.trigger( 'input' );
							} else if( $el.parents('.evf-field').hasClass('evf-field-payment-single')) {
								totalFormatted = app.amountFormat( result );
								$el.val( app.parseToFloat( totalFormatted, decimal_places ) );
								$el.trigger( 'input' )
								$el.trigger( 'focusout' )
							} else{
								$( '#evf-'+ formId +'-field_' + fieldId ).val( app.parseToFloat( result, decimal_places ) ).trigger( 'input' );
							}

							if( !init ) {
								$this.trigger('change');
							}
			},

			/**
			 * get form fields with value.
			*/
			getFormFieldsWithValue : function( formId, $el ){
				let formFields = evf_calculations_obj.formFields;
				let variableName,
					fieldVariable = [];

				Object.entries( formFields ).forEach( ( [fieldId, details ])=>{
					var $field = $el
					.closest("form.everest-form")
					.find(
						'.evf-field[data-field-id="' +
						fieldId +
							'"]'
					);
					let fieldArr = fieldId.split('-');
					variableName = '$FIELD_' + fieldArr[1];

					let fieldData = $("#evf-" + formId + "-field_" + fieldId).val();
					fieldData = fieldData != '' ? fieldData : 0;

					var excludeField = [
										'credit-card',
										'square-payment',
										'authorize-net',
										'textarea',
										'privacy-policy',
										'signature',
										'address',
										'phone',
										'date-time',
										'wysiwyg',
										'color',
										'country',
										'yes-no',
										'likert',
										'yes-no',
										'image-upload',
										'file-upload',
										'scale-rating',
									];

					if( excludeField.includes( details.type )) return;

					fieldData = (fieldData === '' || fieldData === undefined || fieldData === null) ? 0 : fieldData;
					switch ( details.type ) {
						case 'text':
						case 'url':
						case 'email':
						case 'first-name':
						case 'last-name':
						case 'name':
								if( app.isNumeric(fieldData) ){
									fieldVariable.push( `${variableName} = ${fieldData}` );
								}else{
									fieldVariable.push( `${variableName} = '${fieldData}'` );
								}
							break;
						case 'payment-multiple':
						case 'payment-checkbox':
							var total = 0;
							if (
								$field.find(".evf-payment-price:checked")
									.length
							) {
								$field
									.find(".evf-payment-price:checked")
									.each(function () {
										var amount =
											$(this).attr("data-amount");

										if (!isNaN(parseFloat(amount))) {
											var map_field_amount =
											parseFloat(amount.replace(/,/g, ''));
											total += map_field_amount;
										}
									});
								fieldData = total;
								fieldVariable.push( `${variableName} = ${fieldData}` );
							}else{
								fieldVariable.push( `${variableName} = ${fieldData}` );
							}
						break;
						case 'radio' :
							fieldData = $field.find('.input-text:checked').val() ?? 0;

							if( app.isNumeric(fieldData) ){
								fieldVariable.push( `${variableName} = ${fieldData}` );
							}else{
								fieldVariable.push( `${variableName} = '${fieldData}'` );
							}
							break;
						case 'select':
							fieldData = $field.find( '#evf-' + formId + '-field_' + fieldId ).val() ?? 0;
							if( app.isNumeric(fieldData) ){
								fieldVariable.push( `${variableName} = ${fieldData}` );
							}else{
								fieldVariable.push( `${variableName} = '${fieldData}'` );
							}
							break;
						default:
							if( app.isNumeric(fieldData) ){
								fieldData = fieldData ?? 0;
								fieldVariable.push( `${variableName} = ${ app.parseToFloat( fieldData )}` );
							}else{
								fieldData = fieldData ?? '';
								fieldVariable.push( `${variableName} = '${fieldData}'` );
							}
						break;
					}
				})

				return 'const ' + fieldVariable.join( ',\n' ) + ';';
			},

			/**
			 * Sanitize amount and convert to standard format for calculations.
			*/
			amountSanitize: function(amount) {
				var currency = app.getCurrency();
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

				return app.numberFormat( amount, 2, '.', '' );
			},

			/**
			 * Format amount.
			 */
			amountFormat: function(amount) {
				var currency = app.getCurrency();
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

				if ( app.empty( amount ) ) {
					amount = 0;
				}

				return app.numberFormat( amount, 2, currency.decimal_sep, currency.thousands_sep );
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
			 * Parses a string into a floating-point number with specified precision.
			 *
			 */
			parseToFloat :function(str, precision = 14) {

				if ( ! [ 0, '0' ].includes( str ) && app.isEmpty( str ) ) {
					return '';
				}


				precision = precision || precision === 0 ? precision : 14;

				if ( app.isNumeric( str ) ) {
					return Number(parseFloat(str).toFixed(precision));
				}

				var str = str.toString();

				const currency = app.getCurrency();
				const currencySymbol = currency.symbol.replace('$', '\\$');
				const leftSymbol = currency.symbol_pos === 'left' ? currencySymbol + '\\s*' : '';
				const rightSymbol = currency.symbol_pos === 'right' ? '\\s*' + currencySymbol : '';

				const amountPattern =
				`(-?${ leftSymbol }(\\d+)([${ currency.thousands_sep }]?\\d{3})*([${ currency.decimal_sep }]\\d*)?(${ rightSymbol }))` +
				`|(-?(\\d+)([,]?\\d{3})*([.]?\\d*)?)`;

				const regex = new RegExp(amountPattern, 'g');
				const matches = str.match(regex);

				const found = matches?.find( ( item ) => item !== '' );

				if ( !found ) {
					return '';
				}


				if ( found.includes( currencySymbol ) ) {
					return app.amountSanitize(found);
				}

				const cleanedNumber = found.replace(/[^0-9.-]/g, '');

				return Number(parseFloat(cleanedNumber).toFixed(precision));
			},

			/**
			 * Check is empty.
			*
			*/
			isEmpty : function( val ) {
				if ( typeof val === 'object' ) {
					return Object.keys( val ).length === 0;
			}

			return [ undefined, null, false, 0, '', '0' ].includes( val );
		},

		/**
		 * Check if argument is numeric.
		 *
		 */
		isNumeric: function( num ) {
			return ! isNaN( parseFloat( num ) ) && isFinite( num );
		},

		/**
		 * Init modules.
		 *
		 * @since 1.7.8
		 */
		initModules : function() {
			const functions = './calc-default-function' + evf_calculations_obj.suffix +'.js',
					innerFunc = './calc-inner-functions' + evf_calculations_obj.suffix +'.js'

			Promise.all( [
				import( functions ),
				import( innerFunc ),
			] )
				.then( ( [ moduleFunc, innerFuncModule ] ) => {
						allowedFunctions = moduleFunc.default();
					app.mathInnerFunctions = innerFuncModule.default();
				} );
		}
		};

	return app;
}( document, window, jQuery ) );

// Initialize.
EverestFormsCalculations.init();

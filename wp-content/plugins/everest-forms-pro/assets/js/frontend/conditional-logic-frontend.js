/**
 * EverestFormsConditionalFrontend JS
 */
(function($) {

	var EverestFormsConditionalFrontend = {

		/**
		 * Start the engine.
		 */
		init: function() {
			$(document).ready(EverestFormsConditionalFrontend.ready);

			EverestFormsConditionalFrontend.bindUIActions();

			$(document)
			.find('.evf-frontend-row[conditional_rules*="conditional_logic_status"]')
			.each(function () {
				if (
					undefined !== $(this).attr("conditional_rules") &&
					"" !== $(this).attr("conditional_rules")
				) {
					var conditions = JSON.parse(
						$(this).attr("conditional_rules")
					);
				}

				if (undefined !== conditions) {
					$(this)
						.find(
							".evf-field input, .evf-field select, .evf-field textarea , .evf-field h3"
						)
						.each(function () {
							if (
								undefined !== $(this).attr("conditional_rules") &&
								"" !== $(this).attr("conditional_rules")
							) {
								var field_conditions = JSON.parse(
									$(this).attr("conditional_rules")
								);

								if (undefined !== field_conditions) {
									var updated_condtions = [];
									Object.keys(
										conditions.conditionals
									).forEach((i) => {
										var main_logic = [];
										Object.keys(
											conditions.conditionals[i]
										).forEach((j) => {
											main_logic.push(
												conditions.conditionals[i][
													j
												]
											);
											var field = $(this)
												.closest("form")
												.find(
													'[data-field-id="' +
														conditions
															.conditionals[
															i
														][j]["field"] +
														'"]'
												);
											if (
												!field.hasClass(
													"everest-forms-trigger-conditional"
												)
											) {
												field.addClass(
													"everest-forms-trigger-conditional"
												);
											}
										});
										Object.keys(
											field_conditions.conditionals
										).forEach((k) => {
											var sub_logic = [];
											Object.keys(
												field_conditions
													.conditionals[k]
											).forEach((l) => {
												/** Check conditional option for row and field. If its not same, we have to make field conditional logic option(show/hide) similar to row */
												if( field_conditions.conditional_option !==  conditions.conditional_option ) {
													if( 'greater_than' === field_conditions.conditionals[k][l].operator ) {
														field_conditions.conditionals[k][l].value = parseFloat(field_conditions.conditionals[k][l].value) + 0.000001;
													} else if ( 'less_than' === field_conditions.conditionals[k][l].operator ) {
														field_conditions.conditionals[k][l].value = parseFloat(field_conditions.conditionals[k][l].value) - 0.000001;
													}
													field_conditions.conditionals[k][l] = everest_forms_multi_part.swicthConditions( field_conditions.conditionals[k][l] );
													field_conditions.conditional_option =  conditions.conditional_option;
												}
												sub_logic.push(
													field_conditions
														.conditionals[k][l]
												);
											});
											updated_condtions.push(
												main_logic.concat(sub_logic)
											);
										});
									});
									field_conditions.conditionals =
										updated_condtions;
									$(this).attr(
										"conditional_rules",
										JSON.stringify(field_conditions)
									);
								} else {
									$(this).attr(
										"conditional_rules",
										JSON.stringify(conditions)
									);
								}
							} else {
								$(this).attr(
									"conditional_rules",
									JSON.stringify(conditions)
								);
							}
						});
				}
			});

			$(document).find('form.everest-form input,form.everest-form select,form.everest-form textarea').trigger("input").trigger('change');
		},

		ready: function() {
			$('.everest-form').each(function() {
				EverestFormsConditionalFrontend.conditionalLogicAction($(this));
			});
		},

		bindUIActions: function() {
			$(document).on('change keyup ', '.everest-forms-trigger-conditional input, .everest-forms-trigger-conditional select, .everest-forms-trigger-conditional textarea', function() {
				EverestFormsConditionalFrontend.conditionalLogicAction($(this));
			});

			$(document).on('click', '.everest-forms-part-next, .everest-forms-part-prev', function () {
				setTimeout(function () {
					var $currentPart = $('.everest-forms-part:visible');
					$currentPart.find('.evf-field').each(function () {
						var $field = $(this);
						EverestFormsConditionalFrontend.conditionalLogicAction($field);
					});
				}, 50);
			});


			$(document).on('input', '.evf-payment-price', function() {
				setTimeout(() => {
					EverestFormsConditionalFrontend.conditionalLogicAction($(this));
				}, 1);
			});
		},
		formatDate: function(date) {
			let d = new Date(date),
				month = (d.getMonth() + 1).toString().padStart(2, '0'),
				day = d.getDate().toString().padStart(2, '0'),
				year = d.getFullYear(),
				hrs = d.getHours().toString().padStart(2, '0'),
				min = d.getMinutes().toString().padStart(2, '0');

			return `${year}-${month}-${day} ${hrs}:${min}`;
		},
		conditionalLogicAction: function(el) {
			var $this = $(el),
				$form = $this.closest('.everest-form'),
				formID = $form.attr('data-formid'),
				allFields = $form.find('.evf-frontend-row').find('.evf-frontend-grid').find('.input-text');
				var el = $form.find('.evf-submit-container ').find('.evf-submit')[0];
				allFields.push(el);
				var myObj = {};
			myObj.conditional_rules = [];

			allFields.each(function(index, el) {
				var field_id = $(this).attr('id');
				if ('undefined' !== typeof $(this).attr('conditional_rules') && '' != $(this).attr('conditional_rules')) {
					if (EverestFormsConditionalFrontend.IsJsonString($(this).attr('conditional_rules')) === true) {
						myObj.conditional_rules[field_id] = JSON.parse($(this).attr('conditional_rules'));
					}
				}

			});
			var fields = myObj.conditional_rules;
			for (var fieldID in fields) {
				var field = fields[fieldID].conditionals,
					action = fields[fieldID].conditional_option,
					field_required = fields[fieldID].required,
					matchCondition = false;
				// Groups
				for (var groupID in field) {

					var group = field[groupID],
						conditionGroup = true,
						rule = [];
					// Rules
					for (var ruleID in group) {
						if(Array.isArray(group)) {
							group.forEach(function(fieldConditionalGroup) {
								rule = fieldConditionalGroup;
							});
						} else {
							rule = group[ruleID];
						}

						var val = '',
							conditionalRuleMatch = false,
							left = '',
							right = '';

						if ( ! rule.field ) {
							continue;
						}
						var targetInput = $( '[conditional_id="' + group[ruleID].field + '"]' );
						if ( 0 === targetInput.length ) {
							continue;
						}
						var type = targetInput.get(0).type;
						if ( 'empty' === rule.operator || 'not_empty' === rule.operator ) {
							rule.value = '';
							if ( type === 'radio' || type === 'checkbox' || type === 'payment-multiple' || type === 'payment-checkbox' || type === 'rating' || type === 'likert' || type === 'scale-rating' ) {
								$check = $form.find( '#evf-'+formID+'-field_'+group[ruleID].field+'-container input:checked' );
								if ( $check.length ) {
									$.each($check, function() {
										var value = $( this ).val();
										left = value;
									});
								}
							} else {
								left = $form.find( '#evf-'+formID+'-field_'+group[ruleID].field ).val();
								if ( ! left  ) {
									left = '';
								}
							}
						} else {
							if ( "radio" === type || "checkbox" === type ) {
								var checked = $form.find( '#evf-' + formID + '-field_' + group[ruleID].field + '-container input:checked' );

								if ( checked.length ) {
									$.each( checked, function() {
										var value = $( this ).val();
										if ( 'checkbox' === type ) {
											if ( rule.value === value ) {
												left = value;
											}
										} else {
											left = value;
										}
									});
								}
							} else if ( "select-multiple" === type ) {
								var selected = $form.find( '#evf-' + formID + '-field_' + group[ruleID].field + '-container select option:selected' );

								if ( selected.length ) {
									$.each( selected, function() {
										var value = $( this ).val();
										if ( rule.value === value ) {
											left = value;
										}
									});
								}
							} else if ( "tel" === type ) {
								left = targetInput.val().replace(/[()-\s]/g,'');
							} else if ( "hidden" === type ) {
								left = targetInput.val();
							} else {
								left = targetInput.val();
							}
						}
						right = group[ruleID].value;
						const timeFormat = [ 'time' ];
						//Converting the 12hr into 24 for date-time.
						if( targetInput.get(0).hasAttribute('data-date-time') && 'date-time' === targetInput.get(0).getAttribute('data-date-time')) {
							left = EverestFormsConditionalFrontend.convertTo24HourFormat(left);
						}

						switch (rule.operator) {
							case 'is':
								if ( targetInput.get(0).hasAttribute('data-date-time') && timeFormat.includes( targetInput.get(0).getAttribute('data-date-time') ) ) {
									if ( 'time' === targetInput.get(0).getAttribute('data-date-time') ) {
										left = EverestFormsConditionalFrontend.convertTo24Hour( left );
									}
								}
								conditionalRuleMatch = (left === right);
								break;
							case 'is_not':
								if ( targetInput.get(0).hasAttribute('data-date-time') && timeFormat.includes( targetInput.get(0).getAttribute('data-date-time') ) ) {
									if ( 'time' === targetInput.get(0).getAttribute('data-date-time') ) {
										left = '' === left ? EverestFormsConditionalFrontend.timeToSeconds( '12:00' ) : EverestFormsConditionalFrontend.timeToSeconds( left );
										right = EverestFormsConditionalFrontend.timeToSeconds( right );
									}
								}
								conditionalRuleMatch = (left !== right);
								break;
							case 'empty':
								conditionalRuleMatch = ( left.length === 0 );
								break;
							case 'not_empty':
								conditionalRuleMatch = ( left.length > 0 );
								break;
							case 'greater_than' :
								if ( targetInput.get(0).hasAttribute('data-date-time') && timeFormat.includes( targetInput.get(0).getAttribute('data-date-time') ) ) {
									if ( 'time' === targetInput.get(0).getAttribute('data-date-time') ) {
										left = '' === left ? '12:00' : EverestFormsConditionalFrontend.timeToSeconds( left );
										right = EverestFormsConditionalFrontend.timeToSeconds(right);
										conditionalRuleMatch = ( '' !== left ) && ( left > right );
									}
								}else{
									const date = new Date(left);
									if ( ! isNaN( date.getTime() ) ) {
										left = EverestFormsConditionalFrontend.formatDate( date );
										right = EverestFormsConditionalFrontend.formatDate( right );
										conditionalRuleMatch = ( '' !== left ) && ( new Date( left ).getTime() > new Date( right ).getTime() );
									}else{
										left      = left.replace( /[^0-9.]/g, '' );
										conditionalRuleMatch = ( '' !== left ) && ( EverestFormsConditionalFrontend.convertToFlot( left ) > EverestFormsConditionalFrontend.convertToFlot( right ) );
									}
								}
								break;
							case 'less_than' :
								if ( targetInput.get(0).hasAttribute('data-date-time') && timeFormat.includes( targetInput.get(0).getAttribute('data-date-time') ) ) {
									if ( 'time' === targetInput.get(0).getAttribute('data-date-time') ) {
										left = '' === left ? '12:00' : EverestFormsConditionalFrontend.timeToSeconds( left );
										right = EverestFormsConditionalFrontend.timeToSeconds(right);
										conditionalRuleMatch = ( '' !== left ) && ( left < right );
									}
								}else{
									const dateLess = new Date(left);
									if ( ! isNaN( dateLess.getTime() ) ) {
										left = EverestFormsConditionalFrontend.formatDate( dateLess );
										right = EverestFormsConditionalFrontend.formatDate( right );
										conditionalRuleMatch = ( '' !== left ) && ( new Date( left ).getTime() < new Date( right ).getTime() );
									}else{
										left      = left.replace( /[^0-9.]/g, '' );
										conditionalRuleMatch = ( '' !== left ) && ( EverestFormsConditionalFrontend.convertToFlot( left ) < EverestFormsConditionalFrontend.convertToFlot( right ) );
									}
								}
								break;
							case 'between' :
								if (left === right) {
									conditionalRuleMatch = true;
								} else if (left && right) {
									const parseDateRange = (dateRange) => {
										const [start, end] = dateRange.split(" to ").map((date) => new Date(date));
										return { start: EverestFormsConditionalFrontend.formatDate(start), end: EverestFormsConditionalFrontend.formatDate(end) };
									};

									const isDateInRange = (date, rangeStart, rangeEnd) => {
										const time = new Date(date).getTime();
										return time >= new Date(rangeStart).getTime() && time <= new Date(rangeEnd).getTime();
									};

									if (left.includes("to")) {
										const { start: startLeftDate, end: endLeftDate } = parseDateRange(left);
										const { start: startRightDate, end: endRightDate } = parseDateRange(right);
										conditionalRuleMatch = isDateInRange(startLeftDate, startRightDate, endRightDate) && isDateInRange(endLeftDate, startRightDate, endRightDate);
									} else {
										const selectedDate = EverestFormsConditionalFrontend.formatDate(new Date(left));
										const { start: startRightDate, end: endRightDate } = parseDateRange(right);
										conditionalRuleMatch = isDateInRange(selectedDate, startRightDate, endRightDate);
									}
								}
								break;

							case 'multiple':
									if (left === right) {
										conditionalRuleMatch = true;
									} else {
										var right_dates = right.split(', ');
										var selected_dates = left.split(', ');

										var selected_dates = selected_dates.map(date => {
											const dateObj = new Date(date);
											return EverestFormsConditionalFrontend.formatDate(dateObj);
										});

										var right_dates = right_dates.map(date => {
											const dateObj = new Date(date);
											return EverestFormsConditionalFrontend.formatDate(dateObj);
										});

										var allMatch = selected_dates.every(date => right_dates.includes(date));

										if (allMatch && selected_dates.length === right_dates.length) {
											conditionalRuleMatch = true;
										}
									}
									break;
						}

						if (!conditionalRuleMatch) {
							conditionGroup = false;
						break;
						}

					}

					if (conditionGroup) {
						matchCondition = true;
					}

				}
				var submitButton = $('form.everest-form').find('.evf-submit-container ').find('button[id="' + fieldID + '"]');
				if ( $('.evf-field-container').find('.everest-forms-part').length ) {
					if ( submitButton.length > 0 && '""' !== submitButton.attr('conditional_rules') ){
						setTimeout( function() {
							if ( ( matchCondition && action === 'disable') ) {
								submitButton.triggerHandler( 'evf-conditional-logic-submit', 'disable' );
							} else if ( (matchCondition && action === 'hide') || (!matchCondition && action === 'show') ) {
								submitButton.triggerHandler( 'evf-conditional-logic-submit', 'hide' );
							} else {
								submitButton.triggerHandler( 'evf-conditional-logic-submit', 'show' );
							}
						}, 1 );
					}
				} else {
					if ( submitButton.length > 0 && '""' !== submitButton.attr('conditional_rules') ){
						if ( ( matchCondition && action === 'disable') ) {
							submitButton.prop('disabled', true);
						} else if ( (matchCondition && action === 'hide') || (!matchCondition && action === 'show') ) {
							submitButton.prop('disabled', false);
							submitButton.closest(".evf-submit-container").hide();
							submitButton.closest(".evf-submit-container").find( 'button' ).attr( 'disabled', 'disabled' );
						} else {
							submitButton.prop('disabled', false);
							submitButton.closest(".evf-submit-container").show();
							submitButton.closest(".evf-submit-container").find( 'button' ).removeAttr( 'disabled' );
						}
					}
				}

				var single_field = $('form.everest-form').find('.evf-frontend-row').find('.evf-frontend-grid').find('.input-text[id="' + fieldID + '"]');
				if ((matchCondition && action === 'hide') || (!matchCondition && action !== 'hide')) {
					if (undefined != single_field.attr('data-field-type') && 'repeater-fields' == single_field.attr('data-field-type')){
						if ('repeater-fields' == single_field.closest(".evf-field").parent().parent().attr('data-field-type') ){
							single_field.closest(".evf-field").parent().parent().hide();
							single_field.closest(".evf-field").parent().parent().children().find('.evf-field input, .evf-field select, .evf-field textarea').attr('disabled', 'disabled');
							single_field.closest(".evf-field").parent().parent().children().find('.evf-field input, .evf-field select, .evf-field textarea').removeAttr('required');
						}
					}else if('conversational-forms' == single_field.closest(".evf-field").parent().attr('data-field-type')){
						single_field.closest(".evf-field").parent().hide();
						single_field.closest(".evf-field").parent().attr('id','');
						single_field.closest(".evf-field").addClass('everest-convesational-condtional-logic')
						var id = $("[id^='evf-conversational-content']").length;
						$(".evf-honeypot-container , .evf-submit-container").appendTo(
							$("#evf-conversational-content" + id).find("div.evf-field:not([style*='display: none'])")
					   );
					   single_field.closest(".evf-field").find( 'input, select' ).attr( 'disabled', 'disabled' );
						single_field.prop("required", false);
					} else {
						single_field.closest(".evf-field").hide();
						single_field.closest(".evf-field").find( 'input, select' ).attr( 'disabled', 'disabled' );
						single_field.prop("required", false);
					}
					if(action === 'hide') {
						single_field.val('');
					}
					$( document.body ).trigger('conditional_hide', [single_field.closest(".evf-field")]);
				} else {
					if (undefined != single_field.attr('data-field-type') && 'repeater-fields' == single_field.attr('data-field-type')){
						if ('repeater-fields' == single_field.closest(".evf-field").parent().parent().attr('data-field-type') ){
							single_field.closest(".evf-field").parent().parent().show();
							single_field.closest(".evf-field").parent().parent().children().find('.evf-field input, .evf-field select, .evf-field textarea').removeAttr('disabled');
							single_field.closest(".evf-field") .parent().parent().children().find('.validate-required input, .validate-required select, .validate-required textarea').prop('required', true);
						}
					}else if('conversational-forms' == single_field.closest(".evf-field").parent().attr('data-field-type')){
						single_field.closest(".evf-field").parent().show();
						var dataField = single_field.closest(".everest-convesational-condtional-logic").parent().attr('data-field-id');
						single_field.closest(".everest-convesational-condtional-logic").parent().attr('id',dataField);
						var id = $("[id^='evf-conversational-content']").length;
						$(".evf-honeypot-container , .evf-submit-container").appendTo(
							$("#" + dataField).find("div.evf-field:not([style*='display: none'])")
					   );
					   single_field.closest(".evf-field").find( 'input, select' ).removeAttr( 'disabled' );

					} else {
						single_field.closest(".evf-field").show();
						if (typeof single_field.closest(".evf-field").find( 'input, select' ).attr('data-field-visibilty') === 'undefined'){
							single_field.closest(".evf-field").find( 'input, select' ).removeAttr( 'disabled' );
						}
					}

					if (!single_field.closest(".evf-field").hasClass("evf-field-credit-card") &&
                        single_field.closest(".evf-field").find("input:visible, select:visible, textarea:visible").length > 0 &&
                        single_field.closest(".evf-field").hasClass("validate-required") && ! single_field.is( '.evf-conditional-logic-holder' ) && 'conversational-forms' !== single_field.closest(".evf-field").parent().attr('data-field-type') && !single_field.closest(".evf-field").hasClass("evf-field-range-slider") ) {
                        single_field.closest(".evf-field").find('input, select').attr('required', 'required');
                    }

					if ( '1' === field_required && ! single_field.is( '.evf-conditional-logic-holder' ) ) {
						single_field.attr( 'required', 'required' );
					}
					$( document.body ).trigger('conditional_show', [single_field.closest(".evf-field")]);
				}
			}
		},

		/**
		 * Switch Operators for conditional logic.
		 *
		 * @param {array} conditions
		 * @returns
		 */
		swicthConditions: function( conditions ) {
			var operators = {
				'is': 'is_not',
				'is_not': 'is',
				'empty': 'not_empty',
				'not_empty': 'empty',
				'greater_than': 'less_than',
				'less_than': 'greater_than'
			};
			conditions.operator = operators[ conditions.operator];
			return conditions;
		},

		IsJsonString: function(str) {
			try {
				JSON.parse(str);
			} catch (e) {
				return false;
			}
			return true;
		},

		convertToFlot: function ( val ) {
			return ( parseFloat( val ) || 0 );
		},
		convertTo24Hour: function(time12hr) {
			var time = time12hr.match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i);
			if (!time) return time12hr;

			var hours = parseInt(time[1], 10);
			var minutes = time[2];
			var period = time[3];

			if (period === 'AM' && hours === 12) {
				hours = 0;
			} else if (period === 'PM' && hours !== 12) {
				hours += 12;
			}

			var hours24 = ('0' + hours).slice(-2);
			return hours24 + ':' + minutes;
		},
		convertTo24HourFormat:function (timeStr) {
			if('' === timeStr) {
				return '';
			}

			// Split into parts
			const parts = timeStr.split(' ');

			if(parts.length === 1) {
				return parts;
			}

			// Extract date, time, and period
			const datePart = parts[0];
			const timePart = parts[1];
			const period = parts[2]?.toUpperCase();

			// Split hours and minutes
			const [hours, minutes] = timePart.split(':').map(Number);

			// Convert to 24-hour format
			let hours24 = hours;
			if (period === 'PM' && hours !== 12) {
			  hours24 += 12;
			} else if (period === 'AM' && hours === 12) {
			  hours24 = 0;
			}

  			return `${datePart} ${hours24.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
		},
		timeToSeconds: function(time) {
			var timeMatch = time.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)?$/i);

			if (!timeMatch) {
				return null;
			}


			var hours = parseInt(timeMatch[1], 10);
			var minutes = parseInt(timeMatch[2], 10);
			var period = timeMatch[3] ? timeMatch[3].toUpperCase() : null;

			if (period === "AM" && hours === 12) {
				hours = 0;
			} else if (period === "PM" && hours !== 12) {
				hours += 12;
			}

			return hours * 3600 + minutes * 60;
		}

	};

	EverestFormsConditionalFrontend.init();

})(jQuery);

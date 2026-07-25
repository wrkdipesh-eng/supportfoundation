/**
 * EverestFormsConditional JS
 */

(function ($) {
	var EverestFormsConditionalLogic = {
		/**
		 * Start the engine.
		 */
		init: function () {
			// Document ready
			$(document).ready(EverestFormsConditionalLogic.ready);
			EverestFormsConditionalLogic.bindUIActions();

			var conditional_cb = $(
				'.everest-forms-field-option-row-conditional_logic_status input[type="checkbox"]'
			);
			conditional_cb.each(function (index, el) {
				if ($(this).is(":checked")) {
					var conditional_content = $(this)
						.closest(
							".everest-forms-field-option-row-conditional_logic_status"
						)
						.next(".evf-field-conditional-container");
					conditional_content.fadeIn("slow");
					conditional_content
						.find(".evf-field-conditional-field-select")
						.trigger("change");
					conditional_content
						.find(".evf-field-conditional-condition")
						.trigger("change");
				}
			});

			var conditional_cb_email = $(
				".evf_conditional_logic_container"
			).find('input[type="checkbox"]');

			conditional_cb_email.each(function (index, el) {
				if ($(this).is(":checked")) {
					var conditional_content = $(this)
						.closest(".evf_conditional_logic_container")
						.siblings(".evf-field-conditional-container");
					//For confirmation settings.
					if (conditional_content.length === 0) {
						var conditional_content = $(this)
							.closest(".evf_conditional_logic_container")
							.siblings(".evf-content-confirmation-settings")
							.find(".evf-field-conditional-container");
					}

					conditional_content.fadeIn("slow");
					conditional_content
						.find(".evf-field-conditional-field-select")
						.trigger("change");
					conditional_content
						.find(".evf-field-conditional-condition")
						.trigger("change");
				}
			});

			EverestFormsConditionalLogic.updateRowsConditionalFields();
		},

		/**
		 * Element bindings.
		 *
		 */
		bindUIActions: function () {
			var previous;
			var previous_param;

			$(document).on(
				"change",
				".everest-forms-field-option-group-inner .everest-forms-field-option-row-conditional_logic_status input",
				function (e) {
					EverestFormsConditionalLogic.enableConditionLogic(this, e);
				}
			);
			$(document).on(
				"change",
				"#everest-forms-panel-field-settingsemail-evf_send_confirmation_email",
				function (e) {
					EverestFormsConditionalLogic.showConditionalOnEmail(
						this,
						e
					);
				}
			);
			$(document).on(
				"change",
				".evf-field-conditional-field-select",
				function (e) {
					EverestFormsConditionalLogic.conditionalFlatpickr(this, e);
				}
			);
			$(document).on(
				"change",
				".everest-forms-panel-field input",
				function (e) {
					EverestFormsConditionalLogic.enableConditionLogic(this, e);
				}
			);
			$(document)
				.on(
					"click",
					".evf-field-conditional-field-select",
					function (e) {
						previous = this.value;
						previous_param = $(this)
							.siblings()
							.not(".evf-field-conditional-condition")
							.val();
					}
				)
				.on(
					"change",
					".evf-field-conditional-field-select",
					function (e) {
						EverestFormsConditionalLogic.inputType(
							this,
							e,
							previous,
							previous_param
						);
					}
				);
			$(document).on(
				"keyup",
				".everest-forms-field-option-row-label",
				function (e) {
					EverestFormsConditionalLogic.LiveField(this, e);
				}
			);
			$(document).on("click", ".conditonal-rule-add", function (e) {
				EverestFormsConditionalLogic.ruleAdd(this, e);
			});
			$(document).on("click", ".conditonal-rule-remove", function (e) {
				EverestFormsConditionalLogic.ruleRemove(this, e);
			});
			$(document).on("click", ".conditonal-logic-remove", function (e) {
				EverestFormsConditionalLogic.logicRemove(this, e);
			});
			$(document).on("click", ".conditonal-group-add", function (e) {
				EverestFormsConditionalLogic.GroupAdd(this, e);
			});
			$(document).on("click", ".conditonal-logic-add", function (e) {
				EverestFormsConditionalLogic.LogicAdd(this, e);
			});
			$(document).on(
				"click",
				".confirmation-conditonal-logic-add",
				function (e) {
					EverestFormsConditionalLogic.ConfirmationLogicAdd(this, e);
				}
			);
			$(document).on(
				"change",
				".evf-field-conditional-condition",
				function (e) {
					EverestFormsConditionalLogic.conditionCheck(this, e);
				}
			);

			$(document).on(
				"evf_field_drop_complete evf_before_field_deleted evf_sort_update_complete",
				function () {
					EverestFormsConditionalLogic.updateRowsConditionalFields();
				}
			);

			$(document).on(
				"focus",
				"select.evf-field-conditional-input",
				function () {
					const field_type = $(this)
							.prev()
							.prev("select.evf-field-conditional-field-select")
							.find("option:selected")
							.data("field_type"),
						field_id = $(this)
							.prev()
							.prev("select.evf-field-conditional-field-select")
							.find("option:selected")
							.data("field_id"),
						$this = $(this);

					if (
						"select" === field_type ||
						"radio" === field_type ||
						"checkbox" === field_type ||
						"payment-multiple" === field_type ||
						"payment-checkbox" === field_type
					) {
						const choicesList = $(
								'.everest-forms-field-option-row .evf-choices-list[data-field-id="' +
									field_id +
									'"]'
							),
							options = choicesList.find("li");

						if (options.length) {
							let optionValues = new Map();

							options.each(function () {
								let key = $(this).data("key");
								optionValues.set(
									key,
									$(this)
										.find(
											"div.evf-choice-list-input input:first"
										)
										.val()
										.trim()
								);
							});

							$this.find("option").not(":first").remove();

							for (let [key, value] of optionValues) {
								let optionElement = "";
								if (
									"payment-multiple" === field_type ||
									"payment-checkbox" === field_type
								) {
									optionElement = $("<option>")
										.val(key)
										.text(value);
								} else {
									optionElement = $("<option>")
										.val(value)
										.text(value);
								}

								if (optionElement) {
									$this.append(optionElement);
								}
							}
						}
					}
				}
			);

			/**
			 * Trigger DateTime Flatpickr
			 *
			 * @since 1.9.3
			 */
			$( document.body ).on( 'evf_after_field_append', function( e, drag_id ){
				const fieldId = $( '#' + drag_id).data( 'field-id' );

				if ( ! $('#everest-forms-field-option-' + fieldId + ' input[value="date-time"]').length ) {
					return;
				}
				EverestFormsConditionalLogic.triggerDateTime();
			});
		},
		showConditionalOnEmail: function (el, e) {
			jQuery(
				"#everest-forms-panel-field-settingsemail-conditional_logic_status-wrap"
			).show();
		},

		conditionCheck: function (el, e) {
			var $this = $(el);

			if ("empty" === $this.val() || "not_empty" === $this.val()) {
				$this.siblings(".evf-field-conditional-input").val("");
				$this
					.siblings(".evf-field-conditional-input")
					.prop("disabled", true);
			} else {
				$this
					.siblings(".evf-field-conditional-input")
					.prop("disabled", false);
			}
		},

		/**
		 * Show hide Conditional Logic Fields.
		 *
		 */
		enableConditionLogic: function (el, $this) {
			var $this = $(el);
			if ($this.is(":checked")) {
				$this
					.closest(".evf_conditional_logic_container")
					.siblings(".evf-field-conditional-container")
					.fadeIn("slow");
				$this
					.closest(".evf_conditional_logic_container")
					.siblings(".confirmation-conditonal-logic-add-wrapper")
					.removeClass("everest-forms-hidden");

				$this
					.closest(".evf_conditional_logic_container")
					.siblings(".evf-field-conditional-container")
					.find(".evf-field-conditional-field-select")
					.trigger("change");
			} else {
				$this
					.closest(".evf_conditional_logic_container")
					.siblings(".evf-field-conditional-container")
					.fadeOut("slow");
				$this
					.closest(".evf_conditional_logic_container")
					.siblings(".confirmation-conditonal-logic-add-wrapper")
					.addClass("everest-forms-hidden");
			}
		},

		/**
		 * Live Change of fields.
		 */
		LiveField: function (el, e) {
			var selected_field_id = $(
				".everest-forms-field-option:visible"
			).data("field-id");
			var select_fields = $(".evf-field-conditional-field-select");
			var modified_option = select_fields.find(
				"[data-field_id=" + selected_field_id + "]"
			);

			modified_option.text($(el).children("input").val());
		},

		renderSelectionPayment: function ($this, panel_name, conditional) {
			// Refresh the form, remove previous options then rerender.
			var email_connection_id = $this
				.closest(".evf-field-conditional-container")
				.attr("data-connection_id");
			var payment_val =
					"undefined" !== typeof conditional &&
					"undefined" !== typeof conditional[group] &&
					"undefined" !== typeof conditional[group][key] &&
					"undefined" !== typeof conditional[group][key].value
						? conditional[group][key].value
						: "",
				select_options = { completed: "Completed", failed: "Failed" },
				to_append = "";

			for (index in select_options) {
				var selected_attrib = "";

				if (payment_val === index)
					selected_attrib = ' selected="selected"';

				// Begin appending the options.
				to_append +=
					'<option value="' +
					index +
					'"' +
					selected_attrib +
					">" +
					select_options[index] +
					"</option>";
			}

			$this.parent().find(".evf-field-conditional-input").remove();
			$this
				.parent()
				.append(
					"" +
						'<select class="evf-field-conditional-input" name="' +
						panel_name +
						"[" +
						email_connection_id +
						"][conditionals][" +
						group +
						"][" +
						key +
						'][value]" >' +
						"<option>---Select Option---</option>" +
						to_append +
						"</select>"
				);
		},

		inputType: function (el, e, previous, previous_param) {
			e.preventDefault();
			var $this = $(el),
				confirmation = $(el).hasClass("for-confirmation"), //For to indentify the it is from confirmation condtional logic.
				panel_source = $this.attr("data-panel-source"),
				selected_option_id = $this.find(":selected").data("field_id"),
				selected_field_id = $this
					.closest(".evf-field-conditional-wrapper")
					.data("field-id");
			selected_f_id =
				"field" === panel_source
					? selected_field_id.replace("-", "_")
					: "";

			// Revert the button conditions to fresh.
			$this
				.parent()
				.parent()
				.find("a")
				.prop("disabled", false)
				.removeClass("everest-forms-disabled");
			$(".conditonal-group-add")
				.prop("disabled", false)
				.removeClass("everest-forms-disabled");
			if (
				"undefined" !==
					typeof window[
						"evf_field_integration_data_" + selected_f_id
					] &&
				"field" === panel_source
			) {
				var logic = JSON.parse(
						window["evf_field_integration_data_" + selected_f_id]
					),
					conditional =
						"undefined" !== typeof logic ? logic.conditionals : "";
			} else if (
				"settings" === panel_source ||
				"payments" === panel_source
			) {
				//Handling for confirmation form
				var connection_id = $this
						.closest(
							confirmation
								? ".evf-field-confirmation-conditional-container"
								: ".evf-field-conditional-container"
						)
						.attr("data-connection_id"),
					logic =
						"undefined" !==
						typeof window[
							"evf_" +
								panel_source +
								"_" +
								$this.attr("data-source") +
								"_conditional_data_" +
								connection_id
						]
							? JSON.parse(
									window[
										"evf_" +
											panel_source +
											"_" +
											$this.attr("data-source") +
											"_conditional_data_" +
											connection_id
									]
							  )
							: "",
					conditional =
						"undefined" !== typeof logic ? logic.conditionals : "";
			}

			var all_li = $this
				.closest(".evf-field-conditional-wrapper")
				.children("li");
			var all_li_id = [];

			all_li.each(function (index, el) {
				var el_id = $(el).attr("data-key");

				all_li_id.push(el_id);
				var max = Math.max.apply(Math, all_li_id);
				$this
					.closest(".evf-field-conditional-wrapper")
					.attr("data-next-id", max + 1);
			});

			(key = $this.closest("li").data("key")),
				(group = $this
					.closest(".evf-field-conditional-wrapper")
					.attr("data-group")),
				($container = $(
					".everest-forms-panel-sidebar .everest-forms-tab-content"
				)
					.find(
						'.everest-forms-field-option[data-field-id="' +
							selected_option_id +
							'"]'
					)
					.first()),
				(options = $container
					.find(
						".everest-forms-field-option-group-inner .everest-forms-field-option-row-choices ul"
					)
					.find("input.label")),
				(selected_option_type = $this
					.find(":selected")
					.data("field_type"));

			if ("field" === panel_source) {
				var panel_name = "form_fields[" + selected_field_id + "]";
			} else if ("payments" === panel_source) {
				var gateway = $this
					.closest(".evf-field-conditional-container")
					.siblings(".evf_conditional_logic_container")
					.find("input[type=checkbox]")
					.attr("data-panel-source");
				var panel_name = "payments[" + gateway + "]";
			} else {
				var panel_name = "settings[" + $this.attr("data-source") + "]";
			}

			var logic = "";
			var logic_id = "";
			if ("submission_redirection" === $this.attr("data-source")) {
				logic_id = $this
					.parents(".evf-field-conditional-wrapper")
					.attr("data-rule");

				if (undefined === conditional["rules"]) {
					conditional = { rules: { 1: conditional } };
				}
				switch (selected_option_type) {
					default:
						$this
							.parent()
							.find(".evf-field-conditional-input")
							.remove();
						var input_val =
							"undefined" !== typeof conditional &&
							"undefined" !== typeof conditional["rules"] &&
							"undefined" !==
								typeof conditional["rules"][logic_id] &&
							"undefined" !==
								typeof conditional["rules"][logic_id][group] &&
							"undefined" !==
								typeof conditional["rules"][logic_id][group][
									key
								] &&
							"undefined" !==
								typeof conditional["rules"][logic_id][group][
									key
								].value
								? conditional["rules"][logic_id][group][key]
										.value
								: "";
						var db_selected_field =
							"undefined" !== typeof conditional &&
							"undefined" !== typeof conditional["rules"] &&
							"undefined" !==
								typeof conditional["rules"][logic_id] &&
							"undefined" !==
								typeof conditional["rules"][logic_id][group] &&
							"undefined" !==
								typeof conditional["rules"][logic_id][group][
									key
								] &&
							"undefined" !==
								typeof conditional["rules"][logic_id][group][
									key
								].field
								? conditional["rules"][logic_id][group][key]
										.field
								: "";
						var current_selected_field = $this
							.find(":selected")
							.attr("data-field_id");
						if (db_selected_field != current_selected_field) {
							input_val = "";
						}

						if (panel_source === "field") {
							$this
								.parent()
								.append(
									'<input class="evf-field-conditional-input" name="' +
										panel_name +
										"[conditionals][rules][" +
										logic_id +
										"][" +
										group +
										"][" +
										key +
										'][value]" type="text" value="' +
										input_val +
										'" />'
								);
						} else if (
							panel_source === "settings" ||
							panel_source === "payments"
						) {
							var email_connection_id = $this
								.closest(
									confirmation
										? ".evf-field-confirmation-conditional-container"
										: ".evf-field-conditional-container"
								)
								.attr("data-connection_id");
							$this
								.parent()
								.append(
									'<input class="evf-field-conditional-input" name="' +
										panel_name +
										"[" +
										email_connection_id +
										"][conditionals][rules][" +
										logic_id +
										"][" +
										group +
										"][" +
										key +
										'][value]" type="text" value="' +
										input_val +
										'" />'
								);
						}

						break;

					case "checkbox":
					case "radio":
					case "select":
						$this
							.parent()
							.find(".evf-field-conditional-input")
							.remove();

						if (panel_source === "field") {
							$this
								.parent()
								.append(
									'<select class="evf-field-conditional-input" name="' +
										panel_name +
										"[conditionals][rules][" +
										logic_id +
										"][" +
										group +
										"][" +
										key +
										'][value]" ><option>---Select Option---</option></select>'
								);
						} else if (
							"settings" === panel_source ||
							"payments" === panel_source
						) {
							var email_connection_id = $this
								.closest(
									confirmation
										? ".evf-field-confirmation-conditional-container"
										: ".evf-field-conditional-container"
								)
								.attr("data-connection_id");

							$this
								.parent()
								.append(
									'<select class="evf-field-conditional-input" name="' +
										panel_name +
										"[" +
										email_connection_id +
										"][conditionals][rules][" +
										logic_id +
										"][" +
										group +
										"][" +
										key +
										'][value]" ><option>---Select Option---</option></select>'
								);
						}

						var multiple_val =
							"undefined" !== typeof conditional &&
							"undefined" !==
								typeof conditional["rules"][logic_id] &&
							"undefined" !==
								typeof conditional["rules"][logic_id][group][
									key
								] &&
							"undefined" !==
								typeof conditional["rules"][logic_id][group][
									key
								].value
								? conditional["rules"][logic_id][group][key]
										.value
								: "";
						$(options).each(function (index, el) {
							var value = $(el).val(),
								selected = "";

							if (conditional && value === multiple_val) {
								selected = "selected";
							}

							$this
								.parent()
								.find(".evf-field-conditional-input")
								.append(
									'<option value="' +
										value +
										'" ' +
										selected +
										">" +
										value +
										"</option>"
								);
						});

						break;

					case "payment":
						// Rescinds control , returns it if needed.
						$this
							.parent()
							.siblings()
							.prop("disabled", true)
							.addClass("everest-forms-disabled");
						$(".conditonal-group-add")
							.prop("disabled", true)
							.addClass("everest-forms-disabled");
						var div_container = $this.closest(
							".evf-field-conditional-container"
						);

						if (
							1 <
							div_container.find(".evf-conditional-group").length
						) {
							$.confirm({
								title: evf_conditional_rules.i18n_remove_rule,
								content:
									evf_conditional_rules.i18n_remove_rule_message,
								type: "red",
								closeIcon: false,
								icon: "dashicons dashicons-warning",
								escapeKey: "cancel",
								backgroundDismiss: false,
								buttons: {
									confirm: {
										text: "OK",
										btnClass: "btn-red",
										action: function () {
											var rows_to_chuck = $this
												.closest(
													".evf-field-conditional-wrapper"
												)
												.siblings()
												.not(".evf-field-logic")
												.not(".everest-forms-disabled")
												.not(
													".everest-forms-border-container-title"
												);

											rows_to_chuck.remove();
											$this
												.closest(
													".evf-conditional-group"
												)
												.siblings()
												.remove();
											$this
												.closest(
													".evf-field-conditional-wrapper"
												)
												.append(
													'<span class="conditional_or">OR</span>'
												);
											EverestFormsConditionalLogic.renderSelectionPayment(
												$this,
												panel_name,
												conditional
											);
										},
									},
									cancel: function () {
										// Controls are returned here.
										$this.val(previous).change();
										$this
											.siblings()
											.not(
												".evf-field-conditional-condition"
											)
											.val(previous_param)
											.change();
										$this
											.parent()
											.siblings()
											.prop("disabled", false)
											.removeClass(
												"everest-forms-disabled"
											);
										$(".conditonal-group-add")
											.prop("disabled", false)
											.removeClass(
												"everest-forms-disabled"
											);
									},
								},
							});
						} else {
							var email_connection_id = $this
								.closest(".evf-field-conditional-container")
								.attr("data-connection_id");
							var payment_val =
									"undefined" !== typeof conditional &&
									"undefined" !==
										typeof conditional["rules"][rule_id][
											group
										] &&
									"undefined" !==
										typeof conditional["rules"][rule_id][
											group
										][key] &&
									"undefined" !==
										typeof conditional["rules"][rule_id][
											group
										][key].value
										? conditional["rules"][rule_id][group][
												key
										  ].value
										: "",
								select_options = {
									completed: "Completed",
									failed: "Failed",
								},
								to_append = "";

							for (index in select_options) {
								var selected_attrib = "";

								if (payment_val === index)
									selected_attrib = ' selected="selected"';

								// Begin appending the options.
								to_append +=
									'<option value="' +
									index +
									'"' +
									selected_attrib +
									">" +
									select_options[index] +
									"</option>";
							}

							$this
								.parent()
								.find(".evf-field-conditional-input")
								.remove();
							$this
								.parent()
								.append(
									"" +
										'<select class="evf-field-conditional-input" name="' +
										panel_name +
										"[" +
										email_connection_id +
										"][conditionals][rules][" +
										logic_id +
										"][" +
										group +
										"][" +
										key +
										'][value]" >' +
										"<option>---Select Option---</option>" +
										to_append +
										"</select>"
								);
						}

						break;

					case "payment-multiple":
					case "payment-checkbox":
						$this
							.parent()
							.find(".evf-field-conditional-input")
							.remove();

						if (panel_source === "field") {
							$this
								.parent()
								.append(
									'<select class="evf-field-conditional-input" name="' +
										panel_name +
										"[conditionals][rules][" +
										rules_id +
										"][" +
										group +
										"][" +
										key +
										'][value]" ><option>---Select Option---</option></select>'
								);
						} else if (
							panel_source === "settings" ||
							panel_source === "payments"
						) {
							var email_connection_id = $this
								.closest(".evf-field-conditional-container")
								.attr("data-connection_id");
							$this
								.parent()
								.append(
									'<select class="evf-field-conditional-input" name="' +
										panel_name +
										"[" +
										email_connection_id +
										"][conditionals][rules][" +
										rules_id +
										"][" +
										group +
										"][" +
										key +
										'][value]" ><option>---Select Option---</option></select>'
								);
						}

						var multiple_val =
							"undefined" !== typeof conditional &&
							"undefined" !== typeof conditional[group] &&
							"undefined" !== typeof conditional[group][key] &&
							"undefined" !== typeof conditional[group][key].value
								? conditional[group][key].value
								: "";

						$(options).each(function (index, el) {
							var cnt = index + 1;
							var label = $(el).val(),
								value = $(el).attr("data-key"),
								selected = "";
							if (conditional && value === multiple_val) {
								selected = "selected";
							}
							$this
								.parent()
								.find(".evf-field-conditional-input")
								.append(
									'<option value="' +
										value +
										'" ' +
										selected +
										">" +
										label +
										"</option>"
								);
						});

						break;

					case "country":
						$this
							.parent()
							.find(".evf-field-conditional-input")
							.remove();

						if (panel_source === "field") {
							$this
								.parent()
								.append(
									'<select class="evf-field-conditional-input" name="' +
										panel_name +
										"[conditionals][rules][" +
										logic_id +
										"][" +
										group +
										"][" +
										key +
										'][value]"></select>'
								);
						} else if (
							panel_source === "settings" ||
							panel_source === "payments"
						) {
							var email_connection_id = $this
								.closest(".evf-field-conditional-container")
								.attr("data-connection_id");
							$this
								.parent()
								.append(
									'<select class="evf-field-conditional-input" name="' +
										panel_name +
										"[" +
										email_connection_id +
										"][conditionals][rules][" +
										logic_id +
										"][" +
										group +
										"][" +
										key +
										'][value]"></select>'
								);
						}

						options = $container
							.find(
								".everest-forms-field-option-group-advanced .everest-forms-field-option-row-default select"
							)
							.children();
						var country_val =
							"undefined" !== typeof conditional &&
							"undefined" !==
								typeof conditional["rules"][logic_id] &&
							"undefined" !==
								typeof conditional["rules"][logic_id][group][
									key
								] &&
							"undefined" !==
								typeof conditional["rules"][logic_id][group][
									key
								].value
								? conditional["rules"][logic_id][group][key]
										.value
								: "";
						$(options).each(function (index, el) {
							var value = $(el).val(),
								selected = "";

							if (conditional && value === country_val) {
								selected = "selected";
							}
							$this
								.parent()
								.find(".evf-field-conditional-input")
								.append(
									'<option value="' +
										value +
										'" ' +
										selected +
										">" +
										$(el).text() +
										"</option>"
								);
						});

						break;

						case 'date-time':
							$this.parent().find('.evf-field-conditional-input').remove();
							var input_val              = ( 'undefined' !== typeof conditional && 'undefined' !== typeof conditional['rules']&& 'undefined' !== typeof conditional['rules'][logic_id]
								&& 'undefined' !== typeof conditional['rules'][logic_id][group] && 'undefined' !== typeof conditional['rules'][logic_id][group][key] && 'undefined' !== typeof conditional['rules'][logic_id][group][key].value ) ? conditional['rules'][logic_id][group][key].value : '';
							var db_selected_field      = ( 'undefined' !== typeof conditional && 'undefined' !== typeof conditional['rules'] && 'undefined' !== typeof conditional['rules'][logic_id]
								&& 'undefined' !== typeof conditional['rules'][logic_id][group] && 'undefined' !== typeof conditional['rules'][logic_id][group][key] && 'undefined' !== typeof conditional['rules'][logic_id][group][key].field ) ? conditional['rules'][logic_id][group][key].field : '';
							var current_selected_field = $this.find(':selected').attr('data-field_id');
							var current_selected_field_value = $( document).find( '#everest-forms-field-option-' + current_selected_field +'-datetime_format').val();

							if ( db_selected_field != current_selected_field ) {
								input_val = '';
							}

						if (panel_source === "field") {
							$this
								.parent()
								.append(
									'<input class="evf-field-conditional-input" name="' +
										panel_name +
										"[conditionals][rules][" +
										logic_id +
										"][" +
										group +
										"][" +
										key +
										'][value]" type="text" value="' +
										input_val +
										'" />'
								);
						} else if (
							panel_source === "settings" ||
							panel_source === "payments"
						) {
							var email_connection_id = $this
								.closest(".evf-field-conditional-container")
								.attr("data-connection_id");
							$this
								.parent()
								.append(
									'<input class="evf-field-conditional-input" name="' +
										panel_name +
										"[" +
										email_connection_id +
										"][conditionals][rules][" +
										logic_id +
										"][" +
										group +
										"][" +
										key +
										'][value]" type="text" value="' +
										input_val +
										'" />'
								);
						}

							var flatpickrConfig = {};

							if ( "time" === current_selected_field_value ) {
								flatpickrConfig = {
									enableTime: true,
									noCalendar: true,
									dateFormat: "H:i",
								};
							} else if ( "date-time" === current_selected_field_value ) {
								flatpickrConfig = {
									enableTime: true,
								};
							}
							var date_time_flatpickr_obj = $this.parent().find('.evf-field-conditional-input').flatpickr( flatpickrConfig );

						$this
							.parent()
							.find(".evf-field-conditional-condition")
							.on("change", function (e) {
								var date_time_operator = $this
									.parent()
									.find(".evf-field-conditional-condition")
									.val();

								if ("between" === date_time_operator) {
									var selected_dates =
										input_val.split(" to ");
									var start_date = selected_dates[0]
										? selected_dates[0]
										: "";
									var end_date = selected_dates[1]
										? selected_dates[1]
										: "";

									date_time_flatpickr_obj.setDate(
										[start_date, end_date],
										true
									);
									date_time_flatpickr_obj.set({
										mode: "range",
									});
								} else if ("multiple" === date_time_operator) {
									var selected_dates = input_val
										.split(", ")
										.map((date) => date.split(" ")[0]);
									date_time_flatpickr_obj.setDate(
										selected_dates,
										true
									);
									date_time_flatpickr_obj.set({
										mode: "multiple",
									});
								}else {
									if ( 'date' === current_selected_field_value ) {
										date_time_flatpickr_obj.set({
											mode: 'single',
											enableTime: current_selected_field_value !== "date",
											noCalendar: current_selected_field_value === "time"
										});
									}
								}
							});
							break;
				}
			} else {
				switch (selected_option_type) {
					default:
						$this
							.parent()
							.find(".evf-field-conditional-input")
							.remove();
						var input_val =
							"undefined" !== typeof conditional &&
							"undefined" !== typeof conditional[group] &&
							"undefined" !== typeof conditional[group][key] &&
							"undefined" !== typeof conditional[group][key].value
								? conditional[group][key].value
								: "";
						var db_selected_field =
							"undefined" !== typeof conditional &&
							"undefined" !== typeof conditional[group] &&
							"undefined" !== typeof conditional[group][key] &&
							"undefined" !== typeof conditional[group][key].field
								? conditional[group][key].field
								: "";
						var current_selected_field = $this
							.find(":selected")
							.attr("data-field_id");

						if (db_selected_field != current_selected_field) {
							input_val = "";
						}

						if (panel_source === "field") {
							$this
								.parent()
								.append(
									'<input class="evf-field-conditional-input" name="' +
										panel_name +
										"[conditionals][" +
										group +
										"][" +
										key +
										'][value]" type="text" value="' +
										input_val +
										'" />'
								);
						} else if (
							panel_source === "settings" ||
							panel_source === "payments"
						) {
							var email_connection_id = $this
								.closest(".evf-field-conditional-container")
								.attr("data-connection_id");
							$this
								.parent()
								.append(
									'<input class="evf-field-conditional-input" name="' +
										panel_name +
										"[" +
										email_connection_id +
										"][conditionals][" +
										group +
										"][" +
										key +
										'][value]" type="text" value="' +
										input_val +
										'" />'
								);
						}

						break;

					case "checkbox":
					case "radio":
					case "select":
						$this
							.parent()
							.find(".evf-field-conditional-input")
							.remove();

						if (panel_source === "field") {
							$this
								.parent()
								.append(
									'<select class="evf-field-conditional-input" name="' +
										panel_name +
										"[conditionals][" +
										group +
										"][" +
										key +
										'][value]" ><option>---Select Option---</option></select>'
								);
						} else if (
							"settings" === panel_source ||
							"payments" === panel_source
						) {
							var email_connection_id = $this
								.closest(".evf-field-conditional-container")
								.attr("data-connection_id");

							$this
								.parent()
								.append(
									'<select class="evf-field-conditional-input" name="' +
										panel_name +
										"[" +
										email_connection_id +
										"][conditionals][" +
										group +
										"][" +
										key +
										'][value]" ><option>---Select Option---</option></select>'
								);
						}

						var multiple_val =
							"undefined" !== typeof conditional &&
							"undefined" !== typeof conditional[group] &&
							"undefined" !== typeof conditional[group][key] &&
							"undefined" !== typeof conditional[group][key].value
								? conditional[group][key].value
								: "";
						$(options).each(function (index, el) {
							var value = $(el).val(),
								selected = "";

							if (conditional && value === multiple_val) {
								selected = "selected";
							}

							$this
								.parent()
								.find(".evf-field-conditional-input")
								.append(
									'<option value="' +
										value +
										'" ' +
										selected +
										">" +
										value +
										"</option>"
								);
						});

						break;

					case "payment":
						// Rescinds control , returns it if needed.
						$this
							.parent()
							.siblings()
							.prop("disabled", true)
							.addClass("everest-forms-disabled");
						$(".conditonal-group-add")
							.prop("disabled", true)
							.addClass("everest-forms-disabled");
						var div_container = $this.closest(
							".evf-field-conditional-container"
						);

						if (
							1 <
							div_container.find(".evf-conditional-group").length
						) {
							$.confirm({
								title: evf_conditional_rules.i18n_remove_rule,
								content:
									evf_conditional_rules.i18n_remove_rule_message,
								type: "red",
								closeIcon: false,
								icon: "dashicons dashicons-warning",
								escapeKey: "cancel",
								backgroundDismiss: false,
								buttons: {
									confirm: {
										text: "OK",
										btnClass: "btn-red",
										action: function () {
											var rows_to_chuck = $this
												.closest(
													".evf-field-conditional-wrapper"
												)
												.siblings()
												.not(".evf-field-logic")
												.not(".everest-forms-disabled")
												.not(
													".everest-forms-border-container-title"
												);

											rows_to_chuck.remove();
											$this
												.closest(
													".evf-conditional-group"
												)
												.siblings()
												.remove();
											$this
												.closest(
													".evf-field-conditional-wrapper"
												)
												.append(
													'<span class="conditional_or">OR</span>'
												);
											EverestFormsConditionalLogic.renderSelectionPayment(
												$this,
												panel_name,
												conditional
											);
										},
									},
									cancel: function () {
										// Controls are returned here.
										$this.val(previous).change();
										$this
											.siblings()
											.not(
												".evf-field-conditional-condition"
											)
											.val(previous_param)
											.change();
										$this
											.parent()
											.siblings()
											.prop("disabled", false)
											.removeClass(
												"everest-forms-disabled"
											);
										$(".conditonal-group-add")
											.prop("disabled", false)
											.removeClass(
												"everest-forms-disabled"
											);
									},
								},
							});
						} else {
							EverestFormsConditionalLogic.renderSelectionPayment(
								$this,
								panel_name,
								conditional
							);
						}

						break;

					case "payment-multiple":
					case "payment-checkbox":
						$this
							.parent()
							.find(".evf-field-conditional-input")
							.remove();

						if (panel_source === "field") {
							$this
								.parent()
								.append(
									'<select class="evf-field-conditional-input" name="' +
										panel_name +
										"[conditionals][" +
										group +
										"][" +
										key +
										'][value]" ><option>---Select Option---</option></select>'
								);
						} else if (
							panel_source === "settings" ||
							panel_source === "payments"
						) {
							var email_connection_id = $this
								.closest(".evf-field-conditional-container")
								.attr("data-connection_id");
							$this
								.parent()
								.append(
									'<select class="evf-field-conditional-input" name="' +
										panel_name +
										"[" +
										email_connection_id +
										"][conditionals][" +
										group +
										"][" +
										key +
										'][value]" ><option>---Select Option---</option></select>'
								);
						}

						var multiple_val =
							"undefined" !== typeof conditional &&
							"undefined" !== typeof conditional[group] &&
							"undefined" !== typeof conditional[group][key] &&
							"undefined" !== typeof conditional[group][key].value
								? conditional[group][key].value
								: "";

						$(options).each(function (index, el) {
							var cnt = index + 1;
							var label = $(el).val(),
								value = $(el).attr("data-key"),
								selected = "";
							if (conditional && value === multiple_val) {
								selected = "selected";
							}
							$this
								.parent()
								.find(".evf-field-conditional-input")
								.append(
									'<option value="' +
										value +
										'" ' +
										selected +
										">" +
										label +
										"</option>"
								);
						});

						break;

					case "country":
						$this
							.parent()
							.find(".evf-field-conditional-input")
							.remove();

						if (panel_source === "field") {
							$this
								.parent()
								.append(
									'<select class="evf-field-conditional-input" name="' +
										panel_name +
										"[conditionals][" +
										group +
										"][" +
										key +
										'][value]"></select>'
								);
						} else if (
							panel_source === "settings" ||
							panel_source === "payments"
						) {
							var email_connection_id = $this
								.closest(".evf-field-conditional-container")
								.attr("data-connection_id");
							$this
								.parent()
								.append(
									'<select class="evf-field-conditional-input" name="' +
										panel_name +
										"[" +
										email_connection_id +
										"][conditionals][" +
										group +
										"][" +
										key +
										'][value]"></select>'
								);
						}

						options = $container
							.find(
								".everest-forms-field-option-group-advanced .everest-forms-field-option-row-default select"
							)
							.children();
						var country_val =
							"undefined" !== typeof conditional &&
							"undefined" !== typeof conditional[group] &&
							"undefined" !== typeof conditional[group][key] &&
							"undefined" !== typeof conditional[group][key].value
								? conditional[group][key].value
								: "";
						$(options).each(function (index, el) {
							var value = $(el).val(),
								selected = "";

							if (conditional && value === country_val) {
								selected = "selected";
							}
							$this
								.parent()
								.find(".evf-field-conditional-input")
								.append(
									'<option value="' +
										value +
										'" ' +
										selected +
										">" +
										$(el).text() +
										"</option>"
								);
						});

						break;

						case 'date-time':
							$this.parent().find('.evf-field-conditional-input').remove();
							var input_val              = ( 'undefined' !== typeof conditional && 'undefined' !== typeof conditional[group] && 'undefined' !== typeof conditional[group][key] && 'undefined' !== typeof conditional[group][key].value ) ? conditional[group][key].value : '';
							var db_selected_field      = ( 'undefined' !== typeof conditional && 'undefined' !== typeof conditional[group] && 'undefined' !== typeof conditional[group][key] && 'undefined' !== typeof conditional[group][key].field ) ? conditional[group][key].field : '';
							var current_selected_field = $this.find(':selected').attr('data-field_id');
							var current_selected_field_value = $( document).find( '#everest-forms-field-option-' + current_selected_field +'-datetime_format').val();

						if (db_selected_field != current_selected_field) {
							input_val = "";
						}

						if (panel_source === "field") {
							$this
								.parent()
								.append(
									'<input class="evf-field-conditional-input" name="' +
										panel_name +
										"[conditionals][" +
										group +
										"][" +
										key +
										'][value]" type="text" value="' +
										input_val +
										'" />'
								);
						} else if (
							panel_source === "settings" ||
							panel_source === "payments"
						) {
							var email_connection_id = $this
								.closest(".evf-field-conditional-container")
								.attr("data-connection_id");
							$this
								.parent()
								.append(
									'<input class="evf-field-conditional-input" name="' +
										panel_name +
										"[" +
										email_connection_id +
										"][conditionals][" +
										group +
										"][" +
										key +
										'][value]" type="text" value="' +
										input_val +
										'" />'
								);
						}

							var flatpickrConfig = {};

							if ( "time" === current_selected_field_value ) {
								flatpickrConfig = {
									enableTime: true,
									noCalendar: true,
									dateFormat: "H:i",
								};
							}else if ( "date-time" === current_selected_field_value ) {
								flatpickrConfig = {
									enableTime: true,
								};
							}

							var date_time_flatpickr_obj = $this.parent().find('.evf-field-conditional-input').flatpickr( flatpickrConfig );

						$this
							.parent()
							.find(".evf-field-conditional-condition")
							.on("change", function (e) {
								var date_time_operator = $this
									.parent()
									.find(".evf-field-conditional-condition")
									.val();

								if ("between" === date_time_operator) {
									var selected_dates =
										input_val.split(" to ");
									var start_date = selected_dates[0]
										? selected_dates[0]
										: "";
									var end_date = selected_dates[1]
										? selected_dates[1]
										: "";

									date_time_flatpickr_obj.setDate(
										[start_date, end_date],
										true
									);
									date_time_flatpickr_obj.set({
										mode: "range",
									});
								} else if ("multiple" === date_time_operator) {
									var selected_dates = input_val
										.split(", ")
										.map((date) => date.split(" ")[0]);
									date_time_flatpickr_obj.setDate(
										selected_dates,
										true
									);
									date_time_flatpickr_obj.set({
										mode: "multiple",
									});
								} else {
									if ( 'date' === current_selected_field_value ) {
										date_time_flatpickr_obj.set({
											mode: 'single',
										});
									}
								}
							});
						break;
				}
			}
		},

		ruleAdd: function (el, e) {
			e.preventDefault();
			var $this = $(el),
				panel_source = $this
					.siblings(".evf-form-group")
					.children(".evf-field-conditional-field-select")
					.attr("data-panel-source"),
				connection_id = $this
					.closest(".evf-field-conditional-container")
					.attr("data-connection_id"),
				clone = $this.closest("li").clone();
			clone.find("input").val("");
			clone.find("option:selected").prop("selected", false);
			var ul = $this.closest(".evf-field-conditional-wrapper");
			var field_id = ul.data("field-id");
			var next_id = ul.attr("data-next-id");
			var current_group = ul.attr("data-group");
			var logic = "";
			if (undefined !== ul.attr("data-rule")) {
				logic = "[rules][" + ul.attr("data-rule") + "]";
			}
			if (panel_source === "field") {
				var panel_name = "form_fields[" + field_id + "]";
			} else if (panel_source === "payments") {
				var gateway = $this
					.closest(".evf-field-conditional-container")
					.siblings(".evf_conditional_logic_container")
					.find("input[type=checkbox]")
					.attr("data-panel-source");
				var panel_name = "payments[" + gateway + "][connection_1]";
			} else {
				var panel_name =
					"settings[" +
					$this
						.closest(".evf-field-conditional-container")
						.children(".evf-field-conditional-wrapper")
						.first()
						.find(".evf-field-conditional-field-select")
						.attr("data-source") +
					"][" +
					connection_id +
					"]";
			}

			clone.attr("data-key", next_id);
			clone
				.find(".evf-field-conditional-field-select")
				.attr(
					"name",
					"" +
						panel_name +
						"[conditionals]" +
						logic +
						"[" +
						current_group +
						"][" +
						next_id +
						"][field]"
				);
			clone
				.find(".evf-field-conditional-condition")
				.attr(
					"name",
					"" +
						panel_name +
						"[conditionals]" +
						logic +
						"[" +
						current_group +
						"][" +
						next_id +
						"][operator]"
				);
			clone
				.find(".evf-field-conditional-input")
				.attr(
					"name",
					"" +
						panel_name +
						"[conditionals]" +
						logic +
						"[" +
						current_group +
						"][" +
						next_id +
						"][input_choice]"
				);
			$this.closest("li").after(clone);
			next_id++;
			$this
				.closest(".evf-field-conditional-wrapper")
				.attr("data-next-id", next_id);
			$(clone)
				.children(".evf-form-group")
				.children(".evf-field-conditional-field-select")
				.trigger("change");
		},

		ruleRemove: function (el, e) {
			e.preventDefault();
			var $this = $(el),
				container = $this
					.closest(".evf-field-conditional-wrapper")
					.closest(".evf-field-conditional-container"),
				ul = $this.closest(".evf-field-conditional-wrapper");

			if (2 > container.find("li").length) {
				$.alert({
					title: false,
					content: evf_data.i18n_row_locked_msg,
					icon: "dashicons dashicons-info",
					type: "blue",
					buttons: {
						ok: {
							text: evf_data.i18n_ok,
							btnClass: "btn-confirm",
							keys: ["enter"],
						},
					},
				});
			} else {
				if (ul.find("li").length < 2) {
					$this.closest("ul").remove();
				} else {
					$this.closest("li").remove();
				}
			}
		},

		logicRemove: function (el, e) {
			e.preventDefault();
			var $this = $(el),
				container = $this
					.parents(".evf-field-conditional-container")
					.siblings(".evf-field-conditional-container");

			if (0 === container.length) {
				$.alert({
					title: false,
					content: evf_data.i18n_row_locked_msg,
					icon: "dashicons dashicons-info",
					type: "blue",
					buttons: {
						ok: {
							text: evf_data.i18n_ok,
							btnClass: "btn-confirm",
							keys: ["enter"],
						},
					},
				});
			} else {
				$this.parents(".evf-field-conditional-container").remove();
			}
		},

		GroupAdd: function (el, e) {
			e.preventDefault();
			var $this = $(el),
				rule_id = $this
					.closest(".evf-field-conditional-container")
					.attr("data-rule"),
				panel_source = $this.attr("data-panel-source"),
				connection_id = $this
					.closest(".evf-field-conditional-container")
					.attr("data-connection_id"),
				lastGroup = $this
					.siblings(".evf-field-conditional-wrapper")
					.first(),
				clone = lastGroup.clone();
			var field_id = lastGroup.data("field-id");

			if (panel_source === "field") {
				var panel_name = "form_fields[" + field_id + "]";
			} else if (panel_source === "payments") {
				var gateway = $this
					.closest(".evf-field-conditional-container")
					.siblings(".evf_conditional_logic_container")
					.find("input[type=checkbox]")
					.attr("data-panel-source");
				var panel_name = "payments[" + gateway + "][connection_1]";
			} else {
				var panel_name =
					"settings[" +
					$this
						.closest(".evf-field-conditional-container")
						.children(".evf-field-conditional-wrapper")
						.first()
						.find(".evf-field-conditional-field-select")
						.attr("data-source") +
					"][" +
					connection_id +
					"]";
			}

			clone.find(".evf-conditional-group").not(":first").remove();
			clone.find("input").val("");
			clone.find("option:selected").prop("selected", false);
			clone.attr("data-next-id", 2);
			var next_id = clone.attr("data-next-id");
			var prevGroup = $this
				.siblings(".evf-field-conditional-wrapper")
				.last()
				.attr("data-group");
			var logic = "";
			if (undefined !== rule_id) {
				logic = "[rules][" + rule_id + "]";
			}
			clone.attr("data-group", parseInt(prevGroup) + 1);
			var group = clone.data("group");
			clone.find("li").attr("data-key", 1);
			clone
				.find(".evf-field-conditional-field-select")
				.attr(
					"name",
					"" +
						panel_name +
						"[conditionals]" +
						logic +
						"[" +
						group +
						"][" +
						(next_id - 1) +
						"][field]"
				);
			clone
				.find(".evf-field-conditional-condition")
				.attr(
					"name",
					"" +
						panel_name +
						"[conditionals]" +
						logic +
						"[" +
						group +
						"][" +
						(next_id - 1) +
						"][operator]"
				);
			clone
				.find(".evf-field-conditional-input")
				.attr(
					"name",
					"" +
						panel_name +
						"[conditionals]" +
						logic +
						"[" +
						group +
						"][" +
						(next_id - 1) +
						"][input_choice]"
				);
			var cloned = clone.insertBefore($this);
			cloned.find(".conditional_or").remove();
			cloned.append('<span class="conditional_or">OR</span>');
			$(cloned)
				.children(".evf-conditional-group")
				.children(".evf-form-group")
				.children(".evf-field-conditional-field-select")
				.trigger("change");
		},

		LogicAdd: function (el, e) {
			e.preventDefault();
			var $this = $(el),
				rule = $this.parents(".evf-field-conditional-container"),
				rule_id = rule
					.siblings(".evf-field-conditional-container")
					.last()
					.find(".evf-field-conditional-wrapper")
					.attr("data-rule"),
				clone = rule.clone();
			console.log(rule);

			if (undefined === rule_id) {
				rule_id = 1;
			}
			if (
				rule_id <
				$this
					.parents(".evf-field-conditional-container")
					.find(".evf-field-conditional-wrapper")
					.attr("data-rule")
			) {
				rule_id = $this
					.parents(".evf-field-conditional-container")
					.find(".evf-field-conditional-wrapper")
					.attr("data-rule");
			}

			rule_id++;

			clone.find("input").val("");
			clone.find("option:selected").prop("selected", false);

			clone.find(".evf-field-conditional-wrapper:gt(0)").remove();
			clone.find("input").val("");
			clone
				.find(".evf-field-conditional-wrapper")
				.attr("data-rule", rule_id);
			clone.find("input, select").each(function () {
				$(this).attr(
					"name",
					$(this)
						.attr("name")
						.replace(/rules\]\[\d+\]/, "rules][" + rule_id + "]")
				);
			});
			rule.find("a.button.button-small.conditonal-rule-add").remove();
			clone
				.find(".everest-forms-conditional-field-settings-custom_page")
				.hide();
			clone
				.find(".everest-forms-conditional-field-settings-external_url")
				.hide();
			clone.insertAfter(rule);
		},
		ConfirmationLogicAdd: function (el, e) {
			e.preventDefault();

			var $this = $(el),
				rule = $this
					.closest(".confirmation-conditonal-logic-add-wrapper")
					.prev(".evf-confirmation-wrap"),
				rule_id = rule
					.find(".evf-field-conditional-wrapper")
					.last()
					.attr("data-rule"),
				clone = rule.clone();

			if (undefined === rule_id) {
				rule_id = 1;
			}
			if (
				rule_id <
				$this
					.parents(".evf-field-conditional-container")
					.find(".evf-field-conditional-wrapper")
					.attr("data-rule")
			) {
				rule_id = $this
					.parents(".evf-field-conditional-container")
					.find(".evf-field-conditional-wrapper")
					.attr("data-rule");
			}

			rule_id++;

			clone.find("option:selected").prop("selected", false);

			clone.find(".evf-field-conditional-wrapper:gt(0)").remove();
			clone.find(".evf-field-conditional-input").val("");
			clone
				.find(".evf-field-conditional-wrapper")
				.attr("data-rule", rule_id);
			clone.find("input, select, label, textarea").each(function () {
				var $el = $(this);

				var old_name = $el.attr("name");
				if (old_name) {
					$el.attr(
					"name",
					old_name.replace(/\[rules\]\[\d+\]/, "[rules][" + rule_id + "]")
					);
				}

				var old_id = $el.attr("id");
				if (old_id) {
					$el.attr(
					"id",
					old_id.replace(/everest-forms-panel-field-\d+-settings/, "everest-forms-panel-field-" + rule_id + "-settings")
					);
				}

				var old_for = $el.attr("for");
				if (old_for) {
					$el.attr(
					"for",
					old_for.replace(/everest-forms-panel-field-\d+-settings/, "everest-forms-panel-field-" + rule_id + "-settings")
					);
				}
				});

			rule.find("a.button.button-small.conditonal-rule-add").remove();
			clone
				.find(".everest-forms-conditional-field-settings-custom_page")
				.hide();
			clone
				.find(".everest-forms-conditional-field-settings-external_url")
				.hide();
			clone.insertAfter(rule);

			var addedRedirectedTo = clone.find('.confirmation-redirect-to');
			window.pageType( addedRedirectedTo, $( addedRedirectedTo ).val() );

			$(".evf-delete-conditional-confirmation").removeClass(
				"everest-forms-hidden"
			);
			EverestFormsConditionalLogic.multipleConditionalLogicConfirmation();
		},

		updateRowsConditionalFields: function () {
			// To be done later.
		},

		/**
		 * Initiates the flatpickr while using the conditional logic for date-time field.
		 *
		 * @since 1.9.3
		 * */
		conditionalFlatpickr: function (el) {
			var $this = $(el);
			var conditional_class = $this.parent();
			var conditional_condition = $(conditional_class).find('.evf-field-conditional-condition').find('option:selected').val();
			var field_type = $this.find('option:selected').data('field_type');
			var current_selected_field = $this.find(':selected').attr('data-field_id');
			var current_selected_field_value = $( document).find( '#everest-forms-field-option-' + current_selected_field +'-datetime_format').val();

			if ("date-time" !== field_type) {
				$(conditional_class)
					.find(".evf-field-conditional-condition")
					.find('option[value="between"]')
					.addClass("everest-forms-hidden");
				$(conditional_class)
					.find(".evf-field-conditional-condition")
					.find('option[value="multiple"]')
					.addClass("everest-forms-hidden");
				if ("between" === conditional_condition) {
					$(conditional_class)
						.find(".evf-field-conditional-condition")
						.val("is");
				}
			}else{
				if ( 'time' === current_selected_field_value ) {
					$(conditional_class).find('.evf-field-conditional-condition').find('option[value="between"]').addClass('everest-forms-hidden');
					$(conditional_class).find('.evf-field-conditional-condition').find('option[value="multiple"]').addClass('everest-forms-hidden');
				}else{
					$(conditional_class).find('.evf-field-conditional-condition').find('option[value="between"]').removeClass('everest-forms-hidden');
					$(conditional_class).find('.evf-field-conditional-condition').find('option[value="multiple"]').removeClass('everest-forms-hidden');
				}
			}
		},
		triggerDateTime: function( ){
			$( document ).find( '.everest-forms-field-option-row-datetime_format').each( function() {

				const $this = $( this );
				const selectElement = $this.children('select');;
				const fieldId = $this.data('field-id');

				if ( selectElement.length > 0 ) {
					selectElement.on( 'change', function() {
						$( document ).find( '.evf-field-conditional-field-select' ).each( function() {
							const selectedFieldId = $( this ).find( ':selected' ).data( 'field_id' );
							if ( selectedFieldId === fieldId ) {
								$( this ).trigger( 'change' );
							}
						});
					})
				}
			});
		},
		multipleConditionalLogicConfirmation: function() {
			$('.evf-edit-confirm-icon').on('click', function(e){
				e.preventDefault();
				var titleWrap = $(this).closest('.confirmation-title');
				$(this).addClass('everest-forms-hidden');
				$(titleWrap).find('.show-title').addClass('everest-forms-hidden');
				$(titleWrap).find('.edit-title').removeClass('everest-forms-hidden');
				$(titleWrap).find('.evf-confirmation-edit-ok').removeClass('everest-forms-hidden');
			});

			$('.evf-confirmation-edit-ok').on('click', function(e){
				e.preventDefault();
				var titleWrap = $(this).closest('.confirmation-title');
				$(this).addClass('everest-forms-hidden');
				$(titleWrap).find('.show-title').removeClass('everest-forms-hidden');
				$(titleWrap).find('.edit-title').addClass('everest-forms-hidden');
				$(titleWrap).find('.evf-edit-confirm-icon').removeClass('everest-forms-hidden');

			});
		}
	};

	EverestFormsConditionalLogic.init();
	EverestFormsConditionalLogic.triggerDateTime();
})(jQuery);

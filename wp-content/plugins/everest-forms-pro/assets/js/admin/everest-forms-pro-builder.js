/**
 * EverestFormsProBuilder JS
 */
(function ($, params, data) {
	var EverestFormsProBuilder = {
		init: function () {
			EverestFormsProBuilder.bindUIActions();
			EverestFormsProBuilder.checkEnabledPayments();
			EverestFormsProBuilder.showHideAllowedDeniedDomain();
			EverestFormsProBuilder.conditionalConfirmation();
			EverestFormsProBuilder.editFormConfirmation();
			EverestFormsProBuilder.bindPaymentsDeprecationNotice();
			EverestFormsProBuilder.bindIntegrationInstaller();

			$(function () {
				// Core ships form-builder.min.js without refreshFieldMapSelectsInContainer; polyfill so
				// payment gateway (and anything else) can rebuild field-map selects from the live canvas.
				if (
					typeof EVFPanelBuilder !== 'undefined' &&
					typeof EVFPanelBuilder.refreshFieldMapSelectsInContainer !==
						'function'
				) {
					EVFPanelBuilder.refreshFieldMapSelectsInContainer =
						EverestFormsProBuilder.refreshFieldMapSelectsInContainer;
				}

				if (params.isProFieldCodeRequired) {
					EverestFormsProBuilder.bindPrivacyPolicyActions();
				}
				EverestFormsProBuilder.initializeRangeSliderFields();
				EverestFormsProBuilder.ValidateUnique();
				EverestFormsProBuilder.fieldVisibility();

				$(document.body).on(
					'evf_field_drop_complete',
					function (e, field_type, dragged_field_id) {
						// Set defaults in privacy policy field.
						if (
							'privacy-policy' === field_type &&
							params.isProFieldCodeRequired
						) {
							var consent_message = params.i18n_privacy_policy_consent_message;
							$('#everest-forms-field-' + dragged_field_id)
								.find('.evf-privacy-policy-consent-message')
								.html(consent_message);
							$('#everest-forms-field-option-' + dragged_field_id)
								.find('.evf-privacy-policy-consent-message')
								.val(consent_message);
							$(
								'.everest-forms-field-options #everest-forms-field-option-row-' +
									dragged_field_id +
									'-required',
							)
								.find('input')
								.click();
						}

						// Process the new field data if it is a Range Slider field.
						if ('range-slider' === field_type) {
							// Initialize the dropped field as an Range Slider field.
							EverestFormsProBuilder.initializeRangeSliderField(
								dragged_field_id,
							);

							// Show slider input by default.
							$('#everest-forms-field-option-' + dragged_field_id)
								.find('.evf-show-slider-input')
								.attr('checked', 'checked');
							$('#everest-forms-field-' + dragged_field_id)
								.find('.evf-slider-input-wrapper')
								.show();
						}

						if ('file-upload' === field_type) {
							var draggedFieldElement = $(
								'#everest-forms-add-fields-' + field_type,
							);
							draggedFieldElement.removeClass('evf-one-time-draggable-field');
						}

						if ('payment-gateway-selector' === field_type) {
							EverestFormsProBuilder.syncPaymentGatewaySelectorOptions(
								dragged_field_id,
							);
							EverestFormsProBuilder.updatePaymentGatewaySelectorPreview(
								dragged_field_id,
							);
							EverestFormsProBuilder.syncSubscriptionPlanSidebarLock();
							EverestFormsProBuilder.syncMolliePgwAccordionVisibility();
							EverestFormsProBuilder.refreshFieldMapSelectsInContainer(
								'#everest-forms-field-option-' + dragged_field_id,
								dragged_field_id,
							);
							// Payments tab must refresh whether the form is saved; AJAX-reload so PHP-rendered sections update too.
							window.setTimeout(function () {
								var hasPgw = $builder.find( '.everest-forms-field-payment-gateway-selector' ).length > 0;
								EverestFormsProBuilder.refreshPaymentsPanel( hasPgw );
							}, 0);
						}

						// By default check all countries.
						if ('country' === field_type && params.isProFieldCodeRequired) {
							$(
								'#everest-forms-field-option-row-' +
									dragged_field_id +
									'-default',
							)
								.find('select.evf-select2-multiple > option')
								.prop('selected', true);
						}
						$('#everest-forms-field-option-' + dragged_field_id)
							.find('.everest-forms-field-option-row-tooltip_description')
							.css('display', 'none');

						if ('email' === field_type) {
							EverestFormsProBuilder.showHideAllowedDeniedDomain();
						}
					},
				);

				$(document.body).on('evf_after_field_append', function (e, element_id) {
					EverestFormsProBuilder.syncSubscriptionPlanSidebarLock();
					EverestFormsProBuilder.syncMolliePgwAccordionVisibility(element_id);
				});

				$(document.body).on(
					'evf_before_field_deleted',
					function (e, element_id) {
						var $field = $('#everest-forms-field-' + element_id);
						var field_type = $field.attr('data-field-type');
						if ('payment-gateway-selector' === field_type) {
							// Removing Payment Gateway: AJAX-reload payments panel so PHP-rendered sections update.
							window.setTimeout(function () {
								EverestFormsProBuilder.syncSubscriptionPlanSidebarLock();
								// After deletion the gateway-selector field is gone from the canvas.
								EverestFormsProBuilder.refreshPaymentsPanel( false );
							}, 0);
							return;
						}
						if ('payment-subscription-plan' === field_type) {
							window.setTimeout(function () {
								EverestFormsProBuilder.syncMolliePgwAccordionVisibility();
							}, 0);
						}
					},
				);

				$(document.body).on(
					'evf_render_node_complete',
					function (e, field_type, new_key, clonedField, clonedOption) {
						// Process the cloned data if it is a Range Slider field.
						if ('range-slider' === field_type) {
							var html = '';

							clonedField.find('.irs').remove();
							clonedField.find('.evf-slider .evf-range-slider-preview').hide();

							// Purify Handle Color Picker.
							html = clonedOption
								.find(
									'.everest-forms-field-option-row-handle_color .wp-picker-input-wrap label',
								)
								.html();
							clonedOption
								.find(
									'.everest-forms-field-option-row-handle_color .wp-picker-container',
								)
								.remove();
							clonedOption
								.find('.everest-forms-field-option-row-handle_color')
								.append(html);

							// Purify Highlight Color Picker.
							html = clonedOption
								.find(
									'.everest-forms-field-option-row-highlight_color .wp-picker-input-wrap label',
								)
								.html();
							clonedOption
								.find(
									'.everest-forms-field-option-row-highlight_color .wp-picker-container',
								)
								.remove();
							clonedOption
								.find('.everest-forms-field-option-row-highlight_color')
								.append(html);

							// Purify Track Color Picker.
							html = clonedOption
								.find(
									'.everest-forms-field-option-row-track_color .wp-picker-input-wrap label',
								)
								.html();
							clonedOption
								.find(
									'.everest-forms-field-option-row-track_color .wp-picker-container',
								)
								.remove();
							clonedOption
								.find('.everest-forms-field-option-row-track_color')
								.append(html);

							EverestFormsProBuilder.initializeRangeSliderField(new_key);
						}

						if ('payment-gateway-selector' === field_type) {
							EverestFormsProBuilder.refreshFieldMapSelectsInContainer(
								'#everest-forms-field-option-' + new_key,
								new_key,
							);
							EverestFormsProBuilder.syncPaymentGatewaySelectorOptions(new_key);
							EverestFormsProBuilder.updatePaymentGatewaySelectorPreview(
								new_key,
							);
							window.setTimeout(function () {
								EverestFormsProBuilder.reloadPaymentsTabFieldMaps();
							}, 0);
						}
					},
				);

				// Show notice on initial load if Payments tab is active.
				if (
					'payments' ===
					(new URLSearchParams(window.location.search).get('tab') || '')
				) {
					EverestFormsProBuilder.renderPaymentsDeprecationNotice();
				}

				if (
					$(document)
						.find('#everest-forms-panel-field-settings-enable_webhook')
						.prop('checked')
				) {
					$(document)
						.find(
							'.everest-forms-panel-field.evf-field-webhook-headers-container.everest-forms-border-container',
						)
						.removeClass('everest-forms-hidden');
					$(document)
						.find('.evf-section-webhooks-add-new')
						.removeClass('everest-forms-hidden');
				} else {
					$(document)
						.find(
							'.everest-forms-panel-field.evf-field-webhook-headers-container.everest-forms-border-container',
						)
						.addClass('everest-forms-hidden');
					$(document)
						.find('.evf-section-webhooks-add-new')
						.addClass('everest-forms-hidden');
				}
				// Enable Webhook.
				$('#everest-forms-panel-field-settings-enable_webhook').on(
					'click',
					function (e) {
						if ($(this).prop('checked')) {
							$(document)
								.find('#everest-forms-panel-field-settings-webhook_url-wrap')
								.removeClass('everest-forms-hidden');
							$(document)
								.find('#everest-forms-panel-field-settings-webhook_method-wrap')
								.removeClass('everest-forms-hidden');
							$(document)
								.find('#everest-forms-panel-field-settings-webhook_format-wrap')
								.removeClass('everest-forms-hidden');
							$(document)
								.find('#everest-forms-panel-field-settings-with_header-wrap')
								.removeClass('everest-forms-hidden');
							var elements = $(document).find(
								'.everest-forms-panel-field.evf-field-webhook-headers-container.everest-forms-border-container',
							);
							$.each(elements, function (key, value) {
								$(value).removeClass('everest-forms-hidden');
							});
							$(document)
								.find('.evf-section-webhooks-add-new')
								.removeClass('everest-forms-hidden');
						} else {
							$(document)
								.find('#everest-forms-panel-field-settings-webhook_url-wrap')
								.addClass('everest-forms-hidden');
							$(document)
								.find('#everest-forms-panel-field-settings-webhook_method-wrap')
								.addClass('everest-forms-hidden');
							$(document)
								.find('#everest-forms-panel-field-settings-webhook_format-wrap')
								.addClass('everest-forms-hidden');
							$(document)
								.find('#everest-forms-panel-field-settings-with_header-wrap')
								.addClass('everest-forms-hidden');
							var elements = $(document).find(
								'.everest-forms-panel-field.evf-field-webhook-headers-container.everest-forms-border-container',
							);
							$.each(elements, function (key, value) {
								$(value).addClass('everest-forms-hidden');
							});
							$(document)
								.find('.evf-section-webhooks-add-new')
								.addClass('everest-forms-hidden');
						}
					},
				);

				$('#evf-add-web-hooks').on('click', function () {
					var $this = $(this);
					EverestFormsProBuilder.add_webhooks($this);
				});

				$(document).on('click', '.evf-remove-webhook', function () {
					var webhook_section = $(this).closest('.evf-webhook-section');

					webhook_section.remove();
				});
				// Landing Page.
				if (
					$(
						'#everest-forms-panel-field-settings-everest_forms_enable_form_landing_pages',
					).prop('checked')
				) {
					$(document)
						.find('#evf-content-form-landing-pages-settings-body')
						.show();
					$(document)
						.find('#everest-forms-form-landing-page-preview-button')
						.parent()
						.show();
					$(document)
						.find(
							'#everest-forms-panel-field-settings-everest_forms_form_landing_page_enable_branding-wrap',
						)
						.show();

					if (
						$(
							'#everest-forms-panel-field-settings-everest_forms_form_landing_page_enable_evf_footer',
						).is(':checked')
					) {
						$('#evf-landing-page-form-footer-content').show();
					} else {
						$('#evf-landing-page-form-footer-content').hide();
					}

					if (
						$(
							'#everest-forms-panel-field-settings-everest_forms_form_landing_page_enable_form_header',
						).is(':checked')
					) {
						$('#evf-landing-page-form-header-content').show();
					} else {
						$('#evf-landing-page-form-header-content').hide();
					}
				} else {
					$(document)
						.find('.evf-content-form-landing-pages-settings-body')
						.hide();
					$(document)
						.find('#everest-forms-form-landing-page-preview-button')
						.parent()
						.hide();
				}

				// Enable Landing Page.
				$(document).on(
					'click',
					'#everest-forms-panel-field-settings-everest_forms_enable_form_landing_pages',
					function () {
						var $this = $(this);
						if ($this.is(':checked')) {
							$(document)
								.find('.evf-content-form-landing-pages-settings-body')
								.show();
							$(document)
								.find('#everest-forms-form-landing-page-preview-button')
								.parent()
								.show();
							$(document)
								.find(
									'#everest-forms-panel-field-settings-everest_forms_form_landing_page_enable_branding-wrap',
								)
								.show();

							if (
								$(
									'#everest-forms-panel-field-settings-everest_forms_form_landing_page_enable_form_header',
								).is(':checked')
							) {
								$(
									'#everest-forms-panel-field-settings-everest_forms_form_landing_page_form_page_title-wrap',
								).show();
							} else {
								$(
									'#everest-forms-panel-field-settings-everest_forms_form_landing_page_form_page_title-wrap',
								).hide();
							}

							if (
								$(
									'#everest-forms-panel-field-settings-everest_forms_form_landing_page_enable_form_header',
								).is(':checked')
							) {
								$('#evf-landing-page-form-header-content').show();
							} else {
								$('#evf-landing-page-form-header-content').hide();
							}

							if (
								$(
									'#everest-forms-panel-field-settings-everest_forms_form_landing_page_enable_evf_footer',
								).is(':checked')
							) {
								$('#evf-landing-page-form-footer-content').show();
							} else {
								$('#evf-landing-page-form-footer-content').hide();
							}
						} else {
							$(document)
								.find('.evf-content-form-landing-pages-settings-body')
								.hide();
							$(document)
								.find('#everest-forms-form-landing-page-preview-button')
								.parent()
								.hide();
							$(document)
								.find(
									'#everest-forms-panel-field-settings-everest_forms_form_landing_page_enable_branding-wrap',
								)
								.hide();
						}
					},
				);

				// landing page Header.
				$(document).on(
					'click',
					'#everest-forms-panel-field-settings-everest_forms_form_landing_page_enable_form_header',
					function () {
						if (
							$(
								'#everest-forms-panel-field-settings-everest_forms_form_landing_page_enable_form_header',
							).is(':checked')
						) {
							$('#evf-landing-page-form-header-content').show();
						} else {
							$('#evf-landing-page-form-header-content').hide();
						}
					},
				);

				// landing page footer.
				$(document).on(
					'click',
					'#everest-forms-panel-field-settings-everest_forms_form_landing_page_enable_evf_footer',
					function () {
						if (
							$(
								'#everest-forms-panel-field-settings-everest_forms_form_landing_page_enable_evf_footer',
							).is(':checked')
						) {
							$('#evf-landing-page-form-footer-content').show();
						} else {
							$('#evf-landing-page-form-footer-content').hide();
						}
					},
				);

				//Change the slug on form save.
				$(document).on('everest_forms_save_data', function (e, data) {
					if (typeof data.landing_pages === 'undefined') {
						return;
					}
					$('#everest-forms-form-landing-page-preview-button').prop(
						'href',
						data.landing_pages.url,
					);
				});

				// Webhook Headers.
				$(document).ready(function () {
					var wrapper = $(document)
						.find(
							'.everest-forms-panel-field.evf-field-webhook-headers-container.everest-forms-border-container:first',
						)
						.find('.evf-webhook-headers-wrapper');

					var customHeaderKeyPlaceholder = wrapper
						.find('div.evf-webhook-header-template')
						.data('custom-header-key-placeholder');
					var webhookHeaderTemaplate = wrapper
						.find('div.evf-webhook-header-template')
						.clone()
						.removeClass('evf-webhook-header-template everest-forms-hidden')
						.removeAttr('data-custom-header-key-placeholder')
						.addClass('evf-webhook-header');

					$(document).on('click', '.evf-add-webhook-header-btn', function (e) {
						e.preventDefault();
						var $this = $(this);
						var header_wrapper = $this.closest(
							'.evf-field-webhook-headers-container',
						);
						header_wrapper
							.find('div.evf-webhook-header:first')
							.find('.evf-remove-webhook-header-btn')
							.removeClass('everest-forms-hidden');
						var clonedHeaderItem = webhookHeaderTemaplate.clone();

						if (clonedHeaderItem.length <= 0) {
							var headerWrapperTemplate = header_wrapper
								.find('div.evf-webhook-header-template')
								.clone()
								.removeClass('evf-webhook-header-template everest-forms-hidden')
								.removeAttr('data-custom-header-key-placeholder')
								.addClass('evf-webhook-header');
							var headerClonedItem = headerWrapperTemplate.clone();
							headerClonedItem.insertAfter(
								$this.parents('div.evf-webhook-header'),
							);
						}
						clonedHeaderItem.insertAfter(
							$this.parents('div.evf-webhook-header'),
						);
					});

					$(document).on(
						'click',
						'.evf-remove-webhook-header-btn',
						function (e) {
							e.preventDefault();

							var header_wrapper = $(this).closest(
								'.evf-webhook-headers-wrapper',
							);
							if (2 === header_wrapper.find('.evf-webhook-header').length) {
								header_wrapper
									.find('div.evf-webhook-header')
									.find('.evf-remove-webhook-header-btn')
									.addClass('everest-forms-hidden');
							}
							$(this).parents('div.evf-webhook-header').remove();
						},
					);

					$(document).on('change', '.evf-webhook-header-key', function () {
						if ('add-custom-header-key' === $(this).val()) {
							$(this).replaceWith(
								'<span class="evf-webhook-custom-header"><input type="text" placeholder="' +
									customHeaderKeyPlaceholder +
									'" class="evf-webhook-header-key"></span>',
							);
						}
					});

					$(document).on(
						'click',
						'#everest-forms-panel-field-settings-enable_webhook',
						function () {
							if ($(this).is(':checked')) {
								$(document)
									.find('.evf-enable-webhook-toggle')
									.addClass('everest-forms-hidden');
							} else {
								$(document)
									.find('.evf-enable-webhook-toggle')
									.removeClass('everest-forms-hidden');
							}
						},
					);

					// Add tooltips on load.
					$(document)
						.find('.everest-forms-field-option-row-show_tooltip')
						.each(function () {
							var $id = $(this).data('field-id');
							if (
								$(`#everest-forms-field-option-${$id}-show_tooltip`).is(
									':checked',
								)
							) {
								EverestFormsProBuilder.initTooltips($id);
							}
						});
				});

				// For Row Options ( Tab Clicks ).
				$(document.body).on(
					'click',
					'.everest-forms-fields-tab a',
					function () {
						var id = $(this).attr('id');

						if ('row-options' !== id) {
							$('#field-options').show();
							$('.everest-forms-row-options .everest-forms-row-option').each(
								function () {
									$(this).hide();
								},
							);
							$('#row-options').hide().removeClass('active');
						}
					},
				);

				// For switch field.
				$(document.body).on('evf-init-switch-field-options', function () {
					$('.everest-forms-row-options .everest-forms-row-option').each(
						function () {
							$(this).hide();
						},
					);
					$('#field-options').show().addClass('active');
					$('#row-options').hide().removeClass('active');
				});

				// Hide Row Options.
				$(document)
					.find('.everest-forms-row-option')
					.each(function () {
						$(this).hide();
						$('#row-options').hide();
					});

				// For Row Settings Clicks
				$(document).on(
					'click',
					'.evf-toggle-row .dashicons-admin-settings',
					function () {
						var row_id = $(this).closest('.evf-admin-row').attr('data-row-id');
						$('.everest-forms-row-options .everest-forms-row-option').each(
							function () {
								$(this).hide();
							},
						);
						$('#field-options, #multi-part-options').hide();
						$(
							'.everest-forms-field-options, .everest-forms-multi-part-options',
						).hide();
						$('.everest-forms-row-options').show();
						$(document)
							.find(
								'.everest-forms-row-options #everest-forms-row-option-row_' +
									row_id,
							)
							.show();
						$('#row-options').show().trigger('click').addClass('active');
					},
				);

				//Dynamic Reset Button Text
				$(document).on(
					'input',
					'.everest-forms-field-option-row-button_text input',
					function () {
						var val = $(this).val();
						var fieldId = $(this).parent().attr('data-field-id');
						$builder
							.find('#everest-forms-field-' + fieldId + ' .evf-reset-button')
							.html(val);
					},
				);
			});

			$(document).on(
				'click',
				'.everest-forms-field.everest-forms-field-address.active ',
				function () {
					var $this = $(this);
					var id = $this.data('field-id');

					$('#everest-forms-field-option-' + id + '-country_list').on(
						'select2:select',
						function (e) {
							var data = e.params.data;
							var value = data.id;
							var label = data.text;

							// Add.
							var option =
								'<option value="' + value + '">' + label + '</option>';
							$(
								'#everest-forms-field-option-' + id + '-country_default',
							).append(option);
						},
					);

					//Remove.
					$('#everest-forms-field-option-' + id + '-country_list').on(
						'select2:unselect',
						function (e) {
							var data = e.params.data;
							var value = data.id;
							var label = data.text;
							$('#everest-forms-field-option-' + id + '-country_default')
								.find('option')
								.each(function (index, option) {
									var $option = $(option);
									var $option_id = $option.val();
									if ($option_id === value) {
										$option.remove();
									}
								});
						},
					);

					//Add all countries list.
					$(document).on('click', '.evf-select2-select-all-btn', function (e) {
						$(
							'#everest-forms-field-option-' + id + '-country_default option',
						).remove();
						$('#everest-forms-field-option-' + id + '-country_default').append(
							'<option value=""></option>',
						);
						$('#everest-forms-field-option-' + id + '-country_list')
							.find('option')
							.each(function (index, option) {
								var $option = $(option);
								var $option_val = $option.val();
								var $option_text = $option.text();
								var default_country_option =
									'<option value="' +
									$option_val +
									'">' +
									$option_text +
									'</option>';
								$(
									'#everest-forms-field-option-' + id + '-country_default',
								).append(default_country_option);
							});
					});

					//Remove all the countries list.
					$(document).on(
						'click',
						'.evf-select2-unselect-all-btn',
						function (e) {
							$(
								'#everest-forms-field-option-' + id + '-country_default option',
							).remove();
							$(
								'#everest-forms-field-option-' + id + '-country_default',
							).append('<option value=""></option>');
						},
					);
				},
			);
		},

		bindPaymentsDeprecationNotice: function () {
			$(document).on('click', '.evf-panel-payments-button', function () {
				EverestFormsProBuilder.renderPaymentsDeprecationNotice();
			});

			$(document).on('click', '.evf-payment-gateway-selector-preview-note a', function (e) {
				e.stopPropagation();
				window.open($(this).attr('href'), '_blank', 'noopener,noreferrer');
				return false;
			});
		},

		renderPaymentsDeprecationNotice: function () {
			var $panel = $('#everest-forms-panel-payments');
			var $content = $panel.find('.everest-forms-panel-content').first();

			$('#evf-payments-deprecation-notice').remove();

			if (!$content.length) {
				return;
			}

			$content.prepend(
				'<div id="evf-payments-deprecation-notice" class="notice notice-warning inline" style="margin: 0 0 12px;">' +
					'<p>Everest Forms has introduced a new <strong>Payment Gateway</strong> field, which is now available for use. This Payments tab will be deprecated soon. <a href="https://docs.everestforms.net/?post_type=docs&p=5896" target="_blank">Click here for the complete guide</a>.</p>' +
					'</div>',
			);
		},
		/**
		 * All actions related to field validation as unique.
		 */
		ValidateUnique: function () {
			$(document).on(
				'change',
				'.everest-forms-field-option-row-no_duplicates input',
				function (event) {
					var id = $(this).parent().parent().parent().data('field-id');

					$('#everest-forms-field-' + id).toggleClass('validate_message');

					// Toggle "Unique Validation Message" option.
					if ($(event.target).prop('checked')) {
						$(
							'#everest-forms-field-option-row-' + id + '-validate_message',
						).show();
					} else {
						$(
							'#everest-forms-field-option-row-' + id + '-validate_message',
						).hide();
					}
				},
			);
		},
		/**
		 * Field Visibility
		 *
		 */
		fieldVisibility: function () {
			$(document).on('change', '.field_visibility_hidden ', function (event) {
				var id = $(this).parent().parent().parent().data('field-id');
				if ($(this).is(':checked')) {
					$(
						'#everest-forms-field-option-' + id + '-readonly_field_visibility',
					).prop('checked', false);
				}
			});

			$(document).on('change', '.field_visibility_readonly ', function (event) {
				var id = $(this).parent().parent().parent().data('field-id');
				if ($(this).is(':checked')) {
					$(
						'#everest-forms-field-option-' + id + '-hidden_field_visibility',
					).prop('checked', false);
				}
			});
		},
		/**
		 *
		 * @since 1.7.0
		 */
		bindPrivacyPolicyActions: function () {
			// Consent message change handler.
			$(document.body).on(
				'input',
				'.everest-forms-field-option .evf-privacy-policy-consent-message',
				function (e) {
					var new_message = EverestFormsProBuilder.processSyntaxes(
						$(this).val(),
					);

					// Update with the new processed consent message.
					$('.everest-forms-field.active')
						.find('.evf-privacy-policy-consent-message')
						.html(new_message);
				},
			);

			// Local page add handler.
			$(document.body).on(
				'click',
				'.everest-forms-field-option .evf-add-local-privacy-policy-page',
				function (e) {
					var new_message = $(
							'.everest-forms-field-option:visible .evf-privacy-policy-consent-message',
						).val(),
						selected_page_id = $(
							'.everest-forms-field-option:visible .evf-select-local-privacy-policy-page',
						).val(),
						selected_page_title = $(
							'.everest-forms-field-option:visible .evf-select-local-privacy-policy-page option:selected',
						).html();

					// Append a hyperlink syntax containing the selected page to the consent message.
					if (selected_page_id) {
						new_message +=
							'[' +
							selected_page_title +
							'](?page_id=' +
							selected_page_id +
							')';

						// Update with the new consent message.
						$(
							'.everest-forms-field-option:visible .evf-privacy-policy-consent-message',
						).val(new_message);
						new_message = EverestFormsProBuilder.processSyntaxes(new_message);
						$('.everest-forms-field.active')
							.find('.evf-privacy-policy-consent-message')
							.html(new_message);
					}
				},
			);

			// Custom page add handler.
			$(document.body).on(
				'click',
				'.everest-forms-field-option .evf-privacy-policy-add-custom-url',
				function (e) {
					var new_message = $(
							'.everest-forms-field-option:visible .evf-privacy-policy-consent-message',
						).val(),
						label = $(
							'.everest-forms-field-option:visible .evf-privacy-policy-custom-link-label',
						)
							.val()
							.trim(),
						url = $(
							'.everest-forms-field-option:visible .evf-privacy-policy-custom-link-url',
						)
							.val()
							.trim();

					// Prepend `http` protocol in the url.
					if (url.search('http') < 0) {
						url = 'http://' + url;
					}

					// Append a hyperlink syntax containing the custom URL to the consent message.
					if ('' !== url) {
						new_message += '[' + label + '](' + url + ')';

						// Update with the new consent message.
						$(
							'.everest-forms-field-option:visible .evf-privacy-policy-consent-message',
						).val(new_message);
						new_message = EverestFormsProBuilder.processSyntaxes(new_message);
						$('.everest-forms-field.active')
							.find('.evf-privacy-policy-consent-message')
							.html(new_message);

						// Empty the input fields.
						$(
							'.everest-forms-field-option:visible .evf-privacy-policy-custom-link-label',
						).val('');
						$(
							'.everest-forms-field-option:visible .evf-privacy-policy-custom-link-url',
						).val('');
					}
				},
			);
		},

		/**
		 * Initialize All Range Slider Fields.
		 *
		 * @since 1.3.3
		 */
		initializeRangeSliderFields: function () {
			// Min value change handler.
			$(document.body).on(
				'change',
				'.everest-forms-field-option .evf-range-slider-skin',
				EverestFormsProBuilder.updateRangeSliderBasicOptions,
			);

			// Min value change handler.
			$(document.body).on(
				'input',
				'.everest-forms-field-option .everest-forms-field-option-row-min_value .evf-input-number',
				EverestFormsProBuilder.updateRangeSliderBasicOptions,
			);

			// Max value change handler.
			$(document.body).on(
				'input',
				'.everest-forms-field-option .everest-forms-field-option-row-max_value .evf-input-number',
				EverestFormsProBuilder.updateRangeSliderBasicOptions,
			);

			// Min/Max values change handler (This is for the two column Min/Max option UI).
			$(document.body).on(
				'focusout',
				'.everest-forms-field-option .everest-forms-field-option-row-min_max_values .evf-input-number',
				EverestFormsProBuilder.updateRangeSliderBasicOptions,
			);

			// Show Grid option change handler.
			$(document.body).on(
				'change',
				'.everest-forms-field-option .evf-range-slider-show-grid',
				EverestFormsProBuilder.updateRangeSliderBasicOptions,
			);

			// Show slider prefix/postfix option change handler.
			$(document.body).on(
				'change',
				'.everest-forms-field-option .evf-show-slider-prefix-postfix',
				EverestFormsProBuilder.updateRangeSliderBasicOptions,
			);

			// Default value option change handler.
			$(document.body).on(
				'focusout',
				'.everest-forms-field-option .everest-forms-field-option-row-default_value input',
				EverestFormsProBuilder.updateRangeSliderBasicOptions,
			);

			// Use Text Prefix/Postfix option change handler (checkbox).
			$(document.body).on(
				'change',
				'.everest-forms-field-option .evf-use-text-prefix-postfix',
				EverestFormsProBuilder.updateRangeSliderBasicOptions,
			);

			// Text Prefix/Postfix option change handler (input).
			$(document.body).on(
				'input',
				'.everest-forms-field-option .evf-input-prefix-text, .everest-forms-field-option .evf-input-postfix-text',
				EverestFormsProBuilder.updateRangeSliderBasicOptions,
			);

			// Slider input visibility option change handler.
			$(document.body).on(
				'change',
				'.everest-forms-field-option .evf-show-slider-input',
				EverestFormsProBuilder.updateRangeSliderBasicOptions,
			);

			// Payment Slider visibility option change handler.
			$(document.body).on(
				'change',
				'.everest-forms-field-option .evf-enable-payment-slider',
				EverestFormsProBuilder.updateRangeSliderBasicOptions,
			);

			// Initialize Range Slider Fields.
			$('.everest-forms-field.everest-forms-field-range-slider').each(
				function (e) {
					var field_id = $(this).data('field-id');
					EverestFormsProBuilder.initializeRangeSliderField(field_id);
				},
			);
		},

		/**
		 * Process syntaxes in a text.
		 *
		 * @since 1.7.0
		 *
		 * @param {string} text Text to be processed.
		 * @param {bool}   escape_html Whether to escape all the htmls before processing or not.
		 *
		 * @return {string} Processed text.
		 */
		processSyntaxes: function (text) {
			text = text.replace(/^\s+/g, '');
			text = EverestFormsProBuilder.processHyperlinkSyntax(text);
			text = EverestFormsProBuilder.process_italic_syntax(text);
			text = EverestFormsProBuilder.process_bold_syntax(text);
			text = EverestFormsProBuilder.process_underline_syntax(text);
			text = EverestFormsProBuilder.process_new_lines(text);
			return text;
		},

		/**
		 * Process hyperlink syntaxes in a text.
		 * The syntax used for hyperlink is: [Link Label](Link URL)
		 * Example: [Google Search Page](https://google.com)
		 *
		 * @since 1.7.0
		 *
		 * @param {string} text Text to process.
		 *
		 * @return {string} Processed text.
		 */
		processHyperlinkSyntax: function (text) {
			var regex = new RegExp(/(\[[^\[\]]*\])(\([^\(\)]*\))/g);

			// Process all the hyperlink syntax.
			while ((matches = regex.exec(text))) {
				var matched_string = matches[0];
				var label = matches[1];
				var link = matches[2];

				// Trim brackets.
				label = label.substring(1, label.length - 1);
				link = link.substring(1, link.length - 1);

				// Proceed only if label or link is not empty.
				if ('' !== label || '' !== link) {
					// Use hash(#) if the link is empty.
					if ('' === link) {
						link = '#';
					}

					// Use link as label if it's empty.
					if ('' === label) {
						label = link;
					}

					// Insert hyperlink html.
					var html = '<a href="' + link + '">' + label + '</a>';
					text = text.replace(matched_string, html);
				} else {
					// If both label and link are empty then replace it with empty string.
					text = text.replace(matched_string, '');
				}
			}
			return text;
		},

		/**
		 * Process italic syntaxes in a text.
		 * The syntax used for italic text is: `text`
		 * Just wrap the text with back tick characters. To escape a backtick insert a backslash(\) before the character like "\`".
		 *
		 * @since 1.7.0
		 *
		 * @param {string} text Text to process.
		 *
		 * @return {string} Processed text.
		 */
		process_italic_syntax: function (text) {
			var regex = new RegExp(/`[^`]+`/g);
			text = text.split('\\`').join('<&&&&&>'); // To preserve an escaped special character '`'.

			while ((matches = regex.exec(text))) {
				var matched_string = matches[0];
				var label = matched_string
					.trim()
					.substring(1, matched_string.length - 1);
				var html = '<i>' + label + '</i>';
				text = text.replace(matched_string, html);
			}
			return text.split('<&&&&&>').join('`');
		},

		/**
		 * Process bold syntaxes in a text.
		 * The syntax used for bold text is: *text*
		 * Just wrap the text with asterisk characters. To escape an asterisk insert a backslash(\) before the character like "\*".
		 *
		 * @since 1.7.0
		 *
		 * @param {string} text Text to process.
		 *
		 * @return {string} Processed text.
		 */
		process_bold_syntax: function (text) {
			var regex = new RegExp(/\*[^*]+\*/g);
			text = text.split('\\*').join('<&&&&&>'); // To preserve an escaped special character '*'.

			while ((matches = regex.exec(text))) {
				var matched_string = matches[0];
				var label = matched_string
					.trim()
					.substring(1, matched_string.length - 1);
				var html = '<b>' + label + '</b>';
				text = text.replace(matched_string, html);
			}
			return text.split('<&&&&&>').join('*');
		},

		/**
		 * Process underline syntaxes in a text.
		 * The syntax used for bold text is: __text__
		 * Wrap the text with double underscore characters. To escape an underscore insert a backslash(\) before the character like "\_".
		 *
		 * @since 1.7.0
		 *
		 * @param {string} text Text to process.
		 *
		 * @return {string} Processed text.
		 */
		process_underline_syntax: function (text) {
			var regex = new RegExp(/__[^_]+__/g);
			text = text.split('\\_').join('<&&&&&>'); // To preserve an escaped special character '_'.

			while ((matches = regex.exec(text))) {
				var matched_string = matches[0];
				var label = matched_string
					.trim()
					.substring(2, matched_string.length - 2);
				var html = '<u>' + label + '</u>';
				text = text.replace(matched_string, html);
			}
			return text.split('<&&&&&>').join('_');
		},

		/**
		 * It replaces `\n` characters with `<br/>` tag because new line `\n` character is not supported in html.
		 *
		 * @since 1.7.0
		 *
		 * @param {string} text
		 *
		 * @return {string} Processed text.
		 */
		process_new_lines: function (text) {
			//Ref: https://stackoverflow.com/questions/1144783/how-to-replace-all-occurrences-of-a-string
			return text.split('\n').join('<br/>');
		},

		/**
		 * Initialize a Range Slider Field.
		 *
		 * @since 1.3.3
		 */
		initializeRangeSliderField: function (field_id) {
			var $field = $('#everest-forms-field-' + field_id);

			// Initialize the field as an IonRangeSlider field.
			$field.find('.evf-range-slider-preview').ionRangeSlider();

			// Slider handle/highlight/track color change handler.
			EverestFormsProBuilder.initializeSliderHandleColorOption(field_id);
			EverestFormsProBuilder.initializeSliderHighlightColorOption(field_id);
			EverestFormsProBuilder.initializeSliderTrackColorOption(field_id);
			EverestFormsProBuilder.updateRangeSliderBasicOptions(field_id);
			EverestFormsProBuilder.updateRangeSliderColors(field_id);
		},

		/**
		 * Initialize Range Slider Handle Color Picker.
		 *
		 * @since 1.3.3
		 */
		initializeSliderHandleColorOption: function (field_id) {
			var $field_options_container = $(
				'#everest-forms-field-option-' + field_id,
			);

			// Initialize color picker for Handle Color option.
			$field_options_container
				.find('.evf-range-slider-handle-color')
				.wpColorPicker({
					change: function (event, ui) {
						var new_color = $(event.target).val(),
							field_id = $(this)
								.closest('.everest-forms-field-option-row')
								.data('field-id'),
							current_skin = $field_options_container
								.find('.evf-range-slider-skin')
								.val();

						EverestFormsProBuilder.setSliderHandleColor(
							field_id,
							new_color,
							current_skin,
						);
					},
				});
		},

		/**
		 * Initialize Range Slider Highlight Color Picker.
		 *
		 * @since 1.3.3
		 */
		initializeSliderHighlightColorOption: function (field_id) {
			var $field = $('#everest-forms-field-' + field_id),
				$field_options_container = $('#everest-forms-field-option-' + field_id);

			$field_options_container
				.find('.evf-range-slider-highlight-color')
				.wpColorPicker({
					change: function (event, ui) {
						var new_color = $(event.target).val();

						if (new_color) {
							$field.find('.irs-bar').css('background', new_color);
						}
					},
				});
		},

		/**
		 * Initialize Range Slider Track Color Picker.
		 *
		 * @since 1.3.3
		 */
		initializeSliderTrackColorOption: function (field_id) {
			var $field = $('#everest-forms-field-' + field_id),
				$field_options_container = $('#everest-forms-field-option-' + field_id);

			$field_options_container
				.find('.evf-range-slider-track-color')
				.wpColorPicker({
					change: function (event, ui) {
						var new_color = $(event.target).val();

						if (new_color) {
							$field.find('.irs-line').css('background', new_color);
						}
					},
				});
		},

		/**
		 * Update a Range Slider field's Handle/Highlight/Track colors.
		 *
		 * @since 1.3.3
		 */
		updateRangeSliderColors: function (field_id) {
			var $field = $('#everest-forms-field-' + field_id),
				$field_options_container = $('#everest-forms-field-option-' + field_id),
				skin = $field_options_container.find('.evf-range-slider-skin').val(),
				handle_color = $field_options_container
					.find('.evf-range-slider-handle-color')
					.val(),
				highlight_color = $field_options_container
					.find('.evf-range-slider-highlight-color')
					.val(),
				track_color = $field_options_container
					.find('.evf-range-slider-track-color')
					.val();

			// Set handle color for the Slider field.
			EverestFormsProBuilder.setSliderHandleColor(field_id, handle_color, skin);

			// Set Current Highlight Color.
			$field.find('.irs-bar').css('background', highlight_color);

			// Set Current Track Color.
			$field.find('.irs-line').css('background', track_color);
		},

		/**
		 * Set a Range Slider's handle color.
		 *
		 * @since 1.3.3
		 */
		setSliderHandleColor: function (field_id, color, skin) {
			if ('' !== field_id) {
				var $field = $('#everest-forms-field-' + field_id),
					style = '';

				switch (skin) {
					case 'flat':
						$field.find('.irs-handle i').first().css('background-color', color);
						$field.find('.irs-single').css('background-color', color);
						style =
							'#everest-forms-field-' +
							field_id +
							' .irs-single:before { border-top-color: ' +
							color +
							'!important; }';
						break;

					case 'big':
						$field.find('.irs-single').css('background-color', color);
						$field.find('.irs-single').css('background', color);
						$field.find('.irs-handle').css('background-color', color);
						$field.find('.irs-handle').css('background', color);
						break;

					case 'modern':
						$field.find('.irs-handle i').css('background', color);
						$field.find('.irs-single').css('background-color', color);
						style =
							'#everest-forms-field-' +
							field_id +
							' .irs-single:before { border-top-color: ' +
							color +
							'!important; }';
						break;

					case 'sharp':
						$field.find('.irs-handle').css('background-color', color);
						$field.find('.irs-handle i').first().css('border-top-color', color);
						$field.find('.irs-single').css('background-color', color);
						style =
							'#everest-forms-field-' +
							field_id +
							' .irs-single:before { border-top-color: ' +
							color +
							'!important; }';
						break;

					case 'round':
						$field.find('.irs-handle').css('border-color', color);
						$field.find('.irs-single').css('background-color', color);
						style =
							'#everest-forms-field-' +
							field_id +
							' .irs-single:before { border-top-color: ' +
							color +
							'!important; }';
						break;

					case 'square':
						$field.find('.irs-handle').css('border-color', color);
						$field.find('.irs-single').css('background-color', color);
						style =
							'#everest-forms-field-' +
							field_id +
							' .irs-single:before { border-top-color: ' +
							color +
							'!important; }';
						break;
				}

				$('body')
					.find('.evf-range-slider-handle-style-tag-' + field_id)
					.remove();
				$('body').append(
					'<style class="evf-range-slider-handle-style-tag-' +
						field_id +
						'" >' +
						style +
						'</style>',
				);
			}
		},

		/**
		 * Update a Range Slider field's basic options like min/max value, default value, step, show/hide prefix/postfix, skin, grid etc.
		 * The options like handle color, highlight color and track color are not handled by this function as it needs a unique approach.
		 *
		 * @since 1.3.3
		 */
		updateRangeSliderBasicOptions: function (field_id) {
			var field_id =
					'string' === typeof field_id
						? field_id
						: $('.everest-forms-field-option:visible').data('field-id'),
				$field = $('#everest-forms-field-' + field_id);

			if ('range-slider' !== $field.attr('data-field-type')) {
				return;
			}

			var $field_option_section = $('#everest-forms-field-option-' + field_id),
				min_value = parseFloat(
					$('#everest-forms-field-option-' + field_id + '-min_value').val(),
				),
				max_value = parseFloat(
					$('#everest-forms-field-option-' + field_id + '-max_value').val(),
				),
				new_skin = $('#everest-forms-field-option-' + field_id + '-skin').val(),
				default_value = parseFloat(
					$('#everest-forms-field-option-' + field_id + '-default_value').val(),
				),
				show_grid_option = $(
					'#everest-forms-field-option-' + field_id + '-show_grid',
				).is(':checked'),
				show_prefix_postfix_option = $(
					'#everest-forms-field-option-' + field_id + '-show_prefix_postfix',
				).is(':checked'),
				is_text_prefix_postfix_enabled = $(
					'#everest-forms-field-option-' +
						field_id +
						'-use_text_prefix_postfix',
				).is(':checked'),
				show_slider_input = $(
					'#everest-forms-field-option-' + field_id + '-show_slider_input',
				).is(':checked'),
				enable_payment_slider = $(
					'#everest-forms-field-option-' + field_id + '-enable_payment_slider',
				).is(':checked'),
				slider_options = {};

			if (max_value > min_value) {
				if ('' !== min_value) {
					slider_options.min = min_value;
				}
				if ('' !== max_value) {
					slider_options.max = max_value;
				}
			} else {
				$('#everest-forms-field-option-' + field_id + '-max_value').val(
					min_value + 1,
				);
			}
			$('#everest-forms-field-option-' + field_id + '-max_value').attr(
				'min',
				min_value + 1,
			);

			if ('' !== new_skin) {
				slider_options.skin = new_skin;
			} else {
				slider_options.skin = 'round';
			}
			if ('' !== new_skin) {
				slider_options.skin = new_skin;
			}
			if (show_grid_option) {
				slider_options.grid = true;
			} else {
				slider_options.grid = false;
			}
			if (show_prefix_postfix_option) {
				slider_options.hide_min_max = false;
				$(
					'#everest-forms-field-option-row-' +
						field_id +
						'-use_text_prefix_postfix',
				).show();
				$('#everest-forms-field-option-' + field_id)
					.find('.evf-prefix-postfix-warning-message')
					.show();
			} else {
				slider_options.hide_min_max = true;
				$(
					'#everest-forms-field-option-row-' +
						field_id +
						'-use_text_prefix_postfix',
				).hide();
				$('#everest-forms-field-option-' + field_id)
					.find('.evf-prefix-postfix-warning-message')
					.hide();
			}
			if ('' !== default_value) {
				slider_options.from = default_value;
			}

			// Slider input visibility update.
			if (show_slider_input) {
				$field.find('.evf-slider-input-wrapper').show();
			} else {
				$field.find('.evf-slider-input-wrapper').hide();
			}

			// Update the slider field with the specified options.
			$field
				.find('input.evf-range-slider-preview')
				.data('ionRangeSlider')
				.update(slider_options);
			$field.find('.evf-slider-input').val(default_value);

			// Set prefix/postfix texts.
			if (show_prefix_postfix_option && is_text_prefix_postfix_enabled) {
				var prefix_text = $field_option_section
						.find('.evf-input-prefix-text')
						.val(),
					postfix_text = $field_option_section
						.find('.evf-input-postfix-text')
						.val();

				// Update Use Text Prefix/Postfix option.
				$field.find('span.irs-min').html(prefix_text);
				$field.find('span.irs-max').html(postfix_text);
				$field_option_section
					.find('.evf-range-slider-prefix-postfix-texts')
					.show();
			} else {
				// Update Use Text Prefix/Postfix option.
				$field_option_section
					.find('.evf-range-slider-prefix-postfix-texts')
					.hide();
			}

			// Show/Hide prefix/postfix warning message.
			if (
				!show_prefix_postfix_option ||
				(show_prefix_postfix_option && is_text_prefix_postfix_enabled)
			) {
				$field_option_section
					.find('.evf-prefix-postfix-warning-message')
					.hide();
			} else {
				$field_option_section
					.find('.evf-prefix-postfix-warning-message')
					.show();
			}

			// Update Range Slider Colors.
			EverestFormsProBuilder.updateRangeSliderColors(field_id);

			// Payment slider visibility update.
			if (enable_payment_slider) {
				$field.find('.evf-enable-payment-slider').show();
				$field
					.find('.evf-range-slider-preview')
					.attr('data-enable-payment-slider', true);
			} else {
				$field.find('.evf-enable-payment-slider').hide();
				$field
					.find('.evf-range-slider-preview')
					.attr('data-enable-payment-slider', false);
			}
		},

		/**
		 * Live update payment gateway selector preview.
		 *
		 * @param {string} field_id Field ID.
		 */
		updatePaymentGatewaySelectorPreview: function (field_id) {
			var $field = $('#everest-forms-field-' + field_id);

			if ('payment-gateway-selector' !== $field.attr('data-field-type')) {
				return;
			}

			var $previewWrap = $field.find(
					'.evf-payment-gateway-selector-preview-wrap',
				),
				$fieldOptions = $('#everest-forms-field-option-' + field_id),
				$allGateways = $fieldOptions.find(
					'.everest-forms-field-option-row-payment_gateway_choice input[type="checkbox"][name*="[allowed_gateways]"]',
				),
				$checkedGateways = $allGateways.filter(':enabled:checked'),
				chooseOneNotice =
					params.i18n_pgw_selector_choose_one ||
					'Enable a payment gateway in the field options to start accepting payments.',
				noAddonMsg =
					params.i18n_pgw_selector_no_addon ||
					"You haven't enabled any payment gateway add-ons yet. Enable a payment gateway add-on to get started.",
				pgwLogoUrls = params.pgw_logo_urls || {},
				previewHtml = '';

			if (!$previewWrap.length) {
				return;
			}

			// PayPal: show if "Use Global" on (with global creds) OR per-form email filled.
			$checkedGateways = $checkedGateways.filter(function () {
				if ('paypal' !== $(this).val()) {
					return true;
				}
				var $useGlobal     = $('#everest-forms-panel-field-paypal-use_global_setting');
				var useGlobal      = $useGlobal.length ? $useGlobal.is(':checked') : false;
				var hasGlobalCreds = (params.pgw_connected_gateways || []).indexOf('paypal') >= 0;
				// Live DOM value is most reliable when called synchronously from input event.
				var $emailInput = $( '#everest-forms-panel-field-paypal-paypal_email' );
				var emailVal    = $emailInput.length ? $emailInput.val().trim() : '';
				// Fallback: data attrs set by input handlers (covers async calls).
				if ( ! emailVal ) {
					emailVal = $field.attr( 'data-paypal-current-email' ) || '';
				}
				if ( ! emailVal ) {
					var $pgwPanel = $fieldOptions.find( '.evf-pgw-paypal-settings' );
					emailVal = $pgwPanel.length ? ( $pgwPanel.attr( 'data-paypal-current-email' ) || '' ) : '';
				}
				return (useGlobal && hasGlobalCreds) || emailVal.trim() !== '';
			});

			if (!$checkedGateways.length) {
				var notice = $allGateways.length > 0 ? chooseOneNotice : noAddonMsg;
				$previewWrap.html(
					'<p class="description evf-payment-gateway-selector-preview-note" style="margin:8px 0;color:#5a5c63;">' +
						notice +
						'</p>',
				);
				return;
			}

			previewHtml += '<div class="evf-pgw-grid" style="pointer-events:none;">';
			$checkedGateways.each(function () {
				var slug = $(this).val(),
					logoUrl = pgwLogoUrls[slug] || '',
					logoHtml = logoUrl
						? '<img src="' +
							logoUrl +
							'" alt="' +
							slug +
							'" class="evf-pgw-logo-img" />'
						: slug;
				previewHtml +=
					'<div class="evf-pgw-logo-tile"><span class="evf-pgw-logo evf-pgw-logo--' +
					slug +
					'">' +
					logoHtml +
					'</span></div>';
			});
			previewHtml += '</div>';

			$previewWrap.html(previewHtml);
		},

		/**
		 * Rebuild field-map selects under a field options panel from fields on the builder canvas.
		 * Mirrors EVFPanelBuilder.refreshFieldMapSelectsInContainer (free) for sites loading form-builder.min.js only.
		 *
		 * @param {string|jQuery} container      Root element, e.g. #everest-forms-field-option-{id}.
		 * @param {string}        excludeFieldId Optional data-field-id to skip (the payment gateway field itself).
		 */
		refreshFieldMapSelectsInContainer: function (container, excludeFieldId) {
			var $container = container instanceof jQuery ? container : $(container);

			if (!$container.length) {
				return;
			}

			var exclude = excludeFieldId ? String(excludeFieldId) : '';
			var $canvasFields = $('#everest-forms-builder').find(
				'.evf-admin-field-wrapper .everest-forms-field',
			);

			$container
				.find('select.everest-forms-field-map-select')
				.each(function () {
					var $select = $(this);
					var allowedAttr = $select.attr('data-field-map-allowed');

					if (!allowedAttr) {
						return;
					}

					var fieldAllowed = allowedAttr.split(/\s+/).filter(Boolean);
					var currentVal = $select.val();
					var placeholder = $select.attr('data-field-map-placeholder');

					if ($select.hasClass('select2-hidden-accessible')) {
						try {
							$select.selectWoo('destroy');
						} catch (err) {
							// Ignore if selectWoo was not initialized.
						}
						$select.removeClass('enhanced');
					}

					$select.empty();

					if (placeholder) {
						$select.append(
							$('<option></option>').attr('value', '').text(placeholder),
						);
					} else {
						$select.append($('<option></option>').attr('value', ''));
					}

					$canvasFields.each(function () {
						var $f = $(this);
						var ft = $f.attr('data-field-type');
						var fid = String($f.attr('data-field-id') || '');

						if (!ft || !fid) {
							return;
						}
						if (exclude && fid === exclude) {
							return;
						}
						if (
							fieldAllowed.indexOf(ft) === -1 &&
							fieldAllowed.indexOf('all-fields') === -1
						) {
							return;
						}

						var lbl = $f.find('.label-title .text').first().text();

						if (!lbl) {
							lbl = '#' + fid;
						}

						$select.append($('<option></option>').attr('value', fid).text(lbl));
					});

					if (currentVal) {
						var match = false;

						$select.find('option').each(function () {
							if ($(this).val() === String(currentVal)) {
								match = true;
								return false;
							}
						});
						if (match) {
							$select.val(currentVal);
						}
					}
				});

			$(document.body).trigger('evf-enhanced-select-init');
		},

		/**
		 * Check whether a payment gateway is enabled in builder Payments settings.
		 *
		 * @param {string} slug Gateway slug.
		 * @return {boolean}
		 */
		isGatewayEnabledInBuilder: function (slug) {
			// PayPal supports per-form credentials — enabled when addon is active, not just when globally connected.
			var perFormGateways = ['paypal'];
			if (perFormGateways.indexOf(slug) !== -1) {
				var addonActive = params.pgw_addon_active_gateways || [];
				return addonActive.indexOf(slug) !== -1;
			}
			var connected = params.pgw_connected_gateways || [];
			return connected.indexOf(slug) !== -1;
		},

		/**
		 * Sync Payment Gateway selector options with current Payments tab state.
		 *
		 * @param {string} field_id Field ID.
		 */
		syncPaymentGatewaySelectorOptions: function (field_id) {
			var $field = $('#everest-forms-field-' + field_id);
			if ('payment-gateway-selector' !== $field.attr('data-field-type')) {
				return;
			}

			var $fieldOptions = $('#everest-forms-field-option-' + field_id),
				$checkboxes = $fieldOptions.find(
					'.everest-forms-field-option-row-payment_gateway_choice input[type="checkbox"][name*="[allowed_gateways]"]',
				);

			if (!$checkboxes.length) {
				return;
			}

			$checkboxes.each(function () {
				var $checkbox = $(this),
					slug      = $checkbox.val(),
					is_enabled = EverestFormsProBuilder.isGatewayEnabledInBuilder(slug);

				$checkbox.prop('disabled', !is_enabled);
				$checkbox.closest('label').css('opacity', is_enabled ? '1' : '0.55');

				if (!is_enabled) {
					$checkbox.prop('checked', false);
				}

				// Sync accordion chevron + panel visibility based on checked state.
				var isChecked = $checkbox.is(':checked');
				var $item     = $checkbox.closest('.evf-pgw-builder-item');
				var $chevron  = $item.find('.evf-pgw-builder-chevron');
				var $panel    = $item.find('.evf-pgw-builder-panel');
				if ( isChecked ) {
					$chevron.css( { visibility: '', 'pointer-events': '' } );
					$panel.show();
				} else {
					$item.removeClass('evf-pgw-builder-item--open');
					$chevron.attr('aria-expanded', 'false').css( { visibility: 'hidden', 'pointer-events': 'none' } );
					$panel.hide();
				}
			});

			EverestFormsProBuilder.updatePaymentGatewaySelectorPreview(field_id);
		},

		/**
		 * Re-sync Payments tab with the current builder canvas (field-map dropdowns, toggles, sidebar locks).
		 * Does not persist the form or reload the whole builder.
		 */
		reloadPaymentsTabFieldMaps: function () {
			var $panel = $('#everest-forms-panel-payments');

			if ($panel.length) {
				EverestFormsProBuilder.refreshFieldMapSelectsInContainer($panel);
			}

			EverestFormsProBuilder.checkEnabledPayments();

			if (
				typeof EVFPanelBuilder !== 'undefined' &&
				EVFPanelBuilder.syncPaymentMethodDependentFields
			) {
				EVFPanelBuilder.syncPaymentMethodDependentFields();
			}
		},

		/**
		 * Element bindings
		 */
		bindUIActions: function () {
			$builder = $('#everest-forms-builder');

			// Rangel Slider live changes in Quantity Field.
			$builder.on(
				'change',
				'.everest-forms-field-option-row-enable_payment_slider input',
				function (event) {
					var $this = $(this),
						value = $this.val(),
						id = $this.parent().data('field-id'),
						$quantity_field_setting = $('.everest-forms-field-options');
					var label = $quantity_field_setting
						.find('#everest-forms-field-option-row-' + id + '-label input')
						.val();

					$quantity_field_setting
						.find('.everest-forms-field-option-row-map_field  select')
						.each(function (index, element) {
							var $element = $(element);
							if ($(event.target).is(':checked')) {
								var option =
									'<option value="' + id + '">' + label + '</option>';
								$element.append(option);
							} else {
								$element.find('option').each(function (index, option) {
									var $option = $(option);
									if (id === $option.val()) {
										$option.remove();
									}
								});
							}
						});
				},
			);

			// Live label changes of payment items in Quantity Field.
			$builder.on(
				'input',
				'.everest-forms-field-option-row-label input',
				function () {
					var $this = $(this);
					value = $this.val();
					(id = $this.parent().data('field-id')),
						($quantity_field_setting = $('.everest-forms-field-options'));
					$quantity_field_setting
						.find(
							'.everest-forms-field-option-row-map_field  select option[value="' +
								id +
								'"]',
						)
						.text(value);
				},
			);

			$builder.on(
				'change',
				'.everest-forms-field-option-row-payment_gateway_choice input[type="checkbox"]',
				function () {
					var id = $(this)
						.closest('.everest-forms-field-option-row')
						.data('field-id');

					// Show/hide PayPal accordion chevron based on toggle state.
					if ( $(this).val() === 'paypal' ) {
						var $item     = $(this).closest('.evf-pgw-builder-item');
						var $chevron  = $item.find('.evf-pgw-builder-chevron:not(.evf-pgw-builder-chevron--hidden)');
						var $panel    = $item.find('.evf-pgw-builder-panel');
						var isChecked = $(this).is(':checked');

						if ( isChecked ) {
							$chevron.css( { visibility: '', 'pointer-events': '' } );
							$panel.show();
							// Open accordion only if warning would show (user needs to configure).
							var $pgwPanel  = $item.find('.evf-pgw-paypal-settings');
							var $globalCb  = $pgwPanel.find('#everest-forms-panel-field-paypal-use_global_setting');
							var useGlobal  = $globalCb.length ? $globalCb.is(':checked') : false;
							var $emailInput = $pgwPanel.find('#everest-forms-panel-field-paypal-paypal_email');
							var email      = $emailInput.length ? $emailInput.val().trim() : '';
							if ( !useGlobal && email === '' && !$item.hasClass('evf-pgw-builder-item--open') ) {
								$chevron.trigger('click');
							}
						} else {
							// Close accordion.
							$item.removeClass('evf-pgw-builder-item--open');
							$chevron.attr('aria-expanded', 'false').css( { visibility: 'hidden', 'pointer-events': 'none' } );
							$panel.hide();
						}
					}

					EverestFormsProBuilder.updatePaymentGatewaySelectorPreview(id);
					EverestFormsProBuilder.checkEnabledPayments();
					if (
						typeof EVFPanelBuilder !== 'undefined' &&
						EVFPanelBuilder.syncPaymentMethodDependentFields
					) {
						EVFPanelBuilder.syncPaymentMethodDependentFields();
					}
				},
			);

			$(document.body).on('evf_pgw_sort_stop', function (e, field_id) {
				EverestFormsProBuilder.updatePaymentGatewaySelectorPreview(field_id);
			});

			// PayPal per-form credential changes trigger preview refresh via custom event.
			$(document.body).on('evf_pgw_refresh_preview', function (e, field_id) {
				EverestFormsProBuilder.updatePaymentGatewaySelectorPreview(field_id);
			});

			// PayPal email: update preview in real time (text input — not caught by the checkbox handler).
			$(document).on(
				'input change',
				'#everest-forms-panel-field-paypal-paypal_email',
				function () {
					var emailVal = $( this ).val().trim();
					var $row     = $( this ).closest( '.everest-forms-field-option-row' );
					var fieldId  = $row.data( 'field-id' );
					if ( fieldId ) {
						// Write current email to ALL storage locations before preview reads them.
						var $field = $( '#everest-forms-field-' + fieldId );
						$field.attr( 'data-paypal-current-email', emailVal );
						$( this ).closest( '.evf-pgw-paypal-settings' ).attr( 'data-paypal-current-email', emailVal );
						EverestFormsProBuilder.updatePaymentGatewaySelectorPreview( fieldId );
					}
				}
			);

			$builder.on(
				'click',
				'.everest-forms-field[data-field-type="payment-gateway-selector"]',
				function () {
					var id = $(this).data('field-id');
					setTimeout(function () {
						EverestFormsProBuilder.syncPaymentGatewaySelectorOptions(id);
						EverestFormsProBuilder.updatePaymentGatewaySelectorPreview(id);
					}, 0);
				},
			);

			$builder
				.find(
					'.everest-forms-field[data-field-type="payment-gateway-selector"]',
				)
				.each(function () {
					EverestFormsProBuilder.syncPaymentGatewaySelectorOptions(
						$(this).data('field-id'),
					);
				});
			EverestFormsProBuilder.syncSubscriptionPlanSidebarLock();
			EverestFormsProBuilder.syncMolliePgwAccordionVisibility();

			// Real-time updates for Password Strength Meter option.
			$builder.on(
				'change',
				'.everest-forms-field-option-row-password_strength input',
				function (e) {
					$(this)
						.parent()
						.parent()
						.parent()
						.find('.everest-forms-inner-options')
						.toggleClass('everest-forms-visible everest-forms-hidden');
				},
			);

			// Real-time updates for Dropbox upload destination path.
			$builder.on(
				'change',
				'#everest-forms-panel-field-settings-dropbox_enabled-wrap input',
				function (e) {
					var $this = $(this);

					if (!$this.is(':checked')) {
						$(
							'#everest-forms-panel-field-settings-dropbox_destination_path-wrap',
						).addClass('everest-forms-hidden');
						$(
							'#everest-forms-panel-field-settings-enable_send_pdf_dropbox-wrap',
						).addClass('everest-forms-hidden');
					} else {
						$(
							'#everest-forms-panel-field-settings-dropbox_destination_path-wrap',
						).removeClass('everest-forms-hidden');
						$(
							'#everest-forms-panel-field-settings-enable_send_pdf_dropbox-wrap',
						).removeClass('everest-forms-hidden');
					}
				},
			);

			// Real-time updates for Google Drive upload destination path.
			$builder.on(
				'change',
				'#everest-forms-panel-field-settings-google_drive_enabled-wrap input',
				function (e) {
					var $this = $(this);

					if (!$this.is(':checked')) {
						$(
							'#everest-forms-panel-field-settings-google_drive_destination_path-wrap',
						).addClass('everest-forms-hidden');
						$(
							'#everest-forms-panel-field-settings-enable_send_pdf_google_drive-wrap',
						).addClass('everest-forms-hidden');
					} else {
						$(
							'#everest-forms-panel-field-settings-google_drive_destination_path-wrap',
						).removeClass('everest-forms-hidden');
						$(
							'#everest-forms-panel-field-settings-enable_send_pdf_google_drive-wrap',
						).removeClass('everest-forms-hidden');
					}
				},
			);

			// Real-time updates for OneDrive upload destination path.
			$builder.on(
				'change',
				'#everest-forms-panel-field-settings-onedrive_enabled-wrap input',
				function (e) {
					var $this = $(this);

					if (!$this.is(':checked')) {
						$(
							'#everest-forms-panel-field-settings-onedrive_destination_path-wrap',
						).addClass('everest-forms-hidden');
						$(
							'#everest-forms-panel-field-settings-enable_send_pdf_onedrive-wrap',
						).addClass('everest-forms-hidden');
					} else {
						$(
							'#everest-forms-panel-field-settings-onedrive_destination_path-wrap',
						).removeClass('everest-forms-hidden');
						$(
							'#everest-forms-panel-field-settings-enable_send_pdf_onedrive-wrap',
						).removeClass('everest-forms-hidden');
					}
				},
			);

			// Real-time updates for Amazon S3 upload destination path.
			$builder.on(
				'change',
				'#everest-forms-panel-field-settings-amazon_s3_enabled-wrap input',
				function (e) {
					var $this = $(this);

					if (!$this.is(':checked')) {
						$(
							'#everest-forms-panel-field-settings-amazon_s3_destination_path-wrap',
						).addClass('everest-forms-hidden');
						$(
							'#everest-forms-panel-field-settings-enable_send_pdf_amazon_s3-wrap',
						).addClass('everest-forms-hidden');
					} else {
						$(
							'#everest-forms-panel-field-settings-amazon_s3_destination_path-wrap',
						).removeClass('everest-forms-hidden');
						$(
							'#everest-forms-panel-field-settings-enable_send_pdf_amazon_s3-wrap',
						).removeClass('everest-forms-hidden');
					}
				},
			);

			// Real-time updates for Auto Address Field.
			$builder.on(
				'change',
				'.everest-forms-field-option-row-address_style select',
				function (event) {
					var id = $(this).parent().data('field-id');
					// Toggle "AutoComplete Address" option.
					if ('address' === $(this).val()) {
						$(
							'#everest-forms-field-option-row-' + id + '-autocomplete_address',
						).show();
					} else {
						$(
							'#everest-forms-field-option-row-' + id + '-autocomplete_address',
						).hide();
					}
				},
			);

			// Divider Field type selector changes.
			$builder.on(
				'change',
				'.everest-forms-field-option-row-divider_type select',
				function () {
					var new_hr_class = $(this).val();
					var field_id = $(this).parent().attr('data-field-id');
					$('#everest-forms-field-' + field_id)
						.find('hr')
						.removeAttr('class');
					$('#everest-forms-field-' + field_id)
						.find('hr')
						.addClass('evf-divider ' + new_hr_class);
				},
			);

			// Required Indicators
			var requiredSelect = $(
				'#everest-forms-panel-field-settings-required_indicators option:selected',
			).val();

			if ('asterisk' == requiredSelect || 'text' == requiredSelect) {
				$('#everest-forms-panel-field-settings-custom_text-wrap').hide();
			} else {
				$('#everest-forms-panel-field-settings-custom_text-wrap').show();
			}

			$builder.on(
				'change',
				'#everest-forms-panel-field-settings-required_indicators',
				function () {
					if ('asterisk' == this.value || 'text' == this.value) {
						$('#everest-forms-panel-field-settings-custom_text-wrap').hide();
						if ('text' === this.value) {
							$builder
								.find('span.required')
								.html(everest_forms_builder.i18n_required);
						} else {
							$builder.find('span.required').html('*');
						}
					} else {
						$('#everest-forms-panel-field-settings-custom_text-wrap').show();
						$builder
							.find('span.required')
							.html(
								$builder
									.find('#everest-forms-panel-field-settings-custom_text')
									.val(),
							);
					}
				},
			);

			$builder.on(
				'input',
				'#everest-forms-panel-field-settings-custom_text',
				function () {
					var value = $(this).val();
					if ('' === value) {
						$builder
							.find('span.required')
							.html(everest_forms_builder.i18n_required);
					} else {
						$builder.find('span.required').html(value);
					}
				},
			);

			// Initialize multiple select2.
			$builder.on(
				'click',
				' .everest-forms-field-country, .everest-forms-field-address ',
				function () {
					$(this).trigger('evf-enhanced-select-init');
				},
			);
			if (params.isProFieldCodeRequired) {
				// Rating point validation error tips.
				$(document.body)
					.on('blur', '.evf-number-of-stars[type=number]', function () {
						$('.evf_error_tip').fadeOut('100', function () {
							$(this).remove();
						});
					})

					.on(
						'change click',
						'.evf-number-of-stars[type=number]',
						function (e) {
							var number_of_stars = parseInt($(this).val(), 10);

							if (number_of_stars > 100) {
								$(this).val('100');
								EverestFormsProBuilder.livePreviewNumberOfRating($(this));
							}
						},
					)

					.on('keyup click', '.evf-number-of-stars[type=number]', function () {
						var number_of_stars = parseInt($(this).val(), 10);

						if (number_of_stars > 100) {
							$(document.body).triggerHandler('evf_add_error_tip', [
								$(this),
								'i18n_field_rating_greater_than_max_value_error',
								params,
							]);
						} else {
							$(document.body).triggerHandler('evf_remove_error_tip', [
								$(this),
								'i18n_field_rating_greater_than_max_value_error',
							]);
						}
					});

				// Live effect for Rating field Number of Stars option.
				$(document).on(
					'keyup mouseup',
					'.everest-forms-field-option-row-number_of_stars input',
					function () {
						EverestFormsProBuilder.livePreviewNumberOfRating(this);
					},
				);

				// Live effect for Rating field icon option.
				$(document).on(
					'change',
					'.everest-forms-field-option-row-rating-icon input[type=radio]',
					function () {
						var $this = $(this),
							value = $this.val(),
							id = $this.parent().data('field-id'),
							icon_color = $('#everest-forms-field-' + id + ' .rating-icon')
								.find('svg')
								.first()
								.css('fill');
						($icons = $('#everest-forms-field-' + id + ' .rating-icon')),
							(iconClass =
								'<svg width="32" height="32" viewBox="0 0 32 32" style="fill:' +
								icon_color +
								'"><path d="M20.33 11.45L16 2.69l-4.33 8.76L2 12.86l7 6.82-1.65 9.64L16 24.77l8.65 4.55L23 19.68l7-6.82-9.67-1.41z"/></svg>');
						if ('heart' === value) {
							iconClass =
								'<svg width="32" height="32" viewBox="0 0 32 32" style="fill:' +
								icon_color +
								'"><path d="M27.66 16.94L16 28 4.34 16.94a7.31 7.31 0 0 1 0-10.72A8.21 8.21 0 0 1 10 4a6.5 6.5 0 0 1 5 2l1 1s.88-.89 1-1a6.5 6.5 0 0 1 5-2 8.21 8.21 0 0 1 5.66 2.22 7.31 7.31 0 0 1 0 10.72z"/></svg>';
						} else if ('thumb' === value) {
							iconClass =
								'<svg width="32" height="32" viewBox="0 0 32 32" style="fill:' +
								icon_color +
								'"><path d="M30 14.88a3.42 3.42 0 0 0-3.36-3.36h-4.85l.14-.42a2.42 2.42 0 0 1 .2-.39c.08-.14.14-.24.17-.31.21-.4.37-.72.48-1a7.39 7.39 0 0 0 .33-1.05A5.71 5.71 0 0 0 23 4a3.48 3.48 0 0 0-3-2 1.61 1.61 0 0 0-1.43.89C18.34 3.13 17 7 17 7a5.44 5.44 0 0 1-1 2c-.57.75-2.6 3-3.2 3.71s-1.05 1-1.33 1C10 13.74 10 15.71 10 16v9c0 .3 0 2.2 1.52 2.2a12.7 12.7 0 0 1 2.76.77A15.6 15.6 0 0 0 21 30a8.9 8.9 0 0 0 5.74-1.92C30 25 30 15.88 30 14.88zM5 14a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0v-7a3 3 0 0 0-3-3zm0 11a1 1 0 1 1 1-1 1 1 0 0 1-1 1z"/></svg>';
						} else if ('smiley' === value) {
							iconClass =
								'<svg width="32" height="32" viewBox="0 0 32 32" style="fill:' +
								icon_color +
								'"><path d="M16 2a14 14 0 1 0 14 14A14 14 0 0 0 16 2zm4 8a2 2 0 1 1-2 2 2 2 0 0 1 2-2zm-8 0a2 2 0 1 1-2 2 2 2 0 0 1 2-2zm4 14a9.23 9.23 0 0 1-8.16-4.89l1.32-.71a7.76 7.76 0 0 0 13.68 0l1.32.71A9.23 9.23 0 0 1 16 24z"/></svg>';
						} else if ('bulb' === value) {
							iconClass =
								'<svg width="32" height="32" viewBox="0 0 32 32" style="fill:' +
								icon_color +
								'"><path d="M16 2.25A9.76 9.76 0 0 0 6.25 12c0 3.21 2 5.68 3.52 7.48A6.28 6.28 0 0 1 11.25 23a.76.76 0 0 0 .75.75h8a.74.74 0 0 0 .74-.64 10 10 0 0 1 1.53-3.69c.24-.35.49-.7.75-1.06 1.28-1.77 2.73-3.79 2.73-6.36A9.76 9.76 0 0 0 16 2.25zM20 25.25h-8a.75.75 0 0 0 0 1.5h8a.75.75 0 0 0 0-1.5zM19 28.25h-6a.75.75 0 0 0 0 1.5h6a.75.75 0 0 0 0-1.5z"/></svg>';
						}

						$icons.html(iconClass);
					},
				);

				// Live effect for Rating field icon color option.
				$(
					'.everest-forms-field-option-row-icon_color input.colorpicker',
				).wpColorPicker({
					change: function (event) {
						var $this = $(this),
							value = $this.val(),
							id = $this
								.closest('.everest-forms-field-option-row')
								.data('field-id'),
							$icons = $('#everest-forms-field-' + id + ' .rating-icon svg');

						$icons.css('fill', value);
					},
				});
			}
			$(document).on(
				'input',
				'.everest-forms-field-map-table .key-source',
				function () {
					EverestFormsProBuilder.updateUserMetakey(this);
				},
			);

			$(document).on('click', '.everest-forms-addable-list .add', function () {
				EverestFormsProBuilder.userMetaAdd(this);
			});

			$(document).on(
				'click',
				'.everest-forms-addable-list .remove',
				function () {
					EverestFormsProBuilder.userMetaRemove(this);
				},
			);

			$(document).on(
				'change',
				'.everest-forms-field-option-row-show_hide_password input',
				function () {
					var id = $(this).parent().data('field-id');

					if (this.checked) {
						$('#everest-forms-field-' + id)
							.find('.toggle-password')
							.removeClass('everest-forms-hidden');
					} else {
						$('#everest-forms-field-' + id)
							.find('.toggle-password')
							.addClass('everest-forms-hidden');
					}
				},
			);

			$builder.on('change', '.evf-provider-options input', function () {
				if ($(this).is(':checked')) {
					$('.double-optin-template').show();
					$('.double-optin-redirection-url').show();
				} else {
					$('.double-optin-template').hide();
					$('.double-optin-redirection-url').hide();
				}
			});

			// Enable disable payments (include all gateway toggles so Subscription Plan lock stays in sync).
			$builder.on(
				'change',
				[
					'#everest-forms-panel-field-paymentsstripe-enable_stripe',
					'#everest-forms-panel-field-paymentsstripe-enable_credit_card',
					'#everest-forms-panel-field-paymentsstripe-enable_ideal',
					'#everest-forms-panel-field-stripe-recurring',
					'#everest-forms-panel-field-paypal-recurring',
					'#everest-forms-panel-field-paypal-enable_paypal',
					'#everest-forms-panel-field-stripe-syncfields',
					'input[name="payments[stripe][syncfields]"]',
					'#everest-forms-panel-field-paypal-use_global_setting',
					'#everest-forms-panel-field-authorize_net-enable_authorize_net',
					'#everest-forms-panel-field-paymentsrazorpay-enable_razorpay',
					'#everest-forms-panel-field-razorpay-enable_razorpay',
					'input[name*="[razorpay]"][name*="enable_razorpay"]',
					'#everest-forms-panel-field-square-enable_square',
					'#everest-forms-panel-field-paymentsmollie-enable_mollie',
				].join(','),
				function () {
					EverestFormsProBuilder.checkEnabledPayments();
					if (
						typeof EverestFormsStripeAdmin !== 'undefined' &&
						EverestFormsStripeAdmin.syncCreditCardSidebarState
					) {
						EverestFormsStripeAdmin.syncCreditCardSidebarState();
					}
				},
			);

			//Enable disable stripe recurring payment.
			$builder.on(
				'change',
				'#everest-forms-panel-field-paymentsstripe-enable_stripe',
				function () {
					EverestFormsProBuilder.stripeRecurringPayment();
				},
			);

			// Show hide allowed or denied domains in whitelist domain.
			$builder.on(
				'change',
				'.everest-forms-field-option-row.everest-forms-field-option-row-whitelist_domain select',
				function () {
					EverestFormsProBuilder.showHideAllowedDeniedDomain($(this).parent());
				},
			);

			// Real-time updates for Single Item field "Item Price" option
			$builder.on(
				'input',
				'.everest-forms-field-option-row-item_price input',
				function (e) {
					var $this = $(this),
						value = $this.val(),
						id = $this.parent().data('field-id'),
						sanitized = EverestFormsProBuilder.amountSanitize(value),
						formatted = EverestFormsProBuilder.amountFormat(sanitized),
						singleItem;

					if ('right' === data.currency_symbol_pos) {
						singleItem = formatted + ' ' + data.currency_symbol;
					} else {
						singleItem = data.currency_symbol + ' ' + formatted;
					}

					$('#everest-forms-field-' + id)
						.find('.widefat')
						.val(formatted);
					$('#everest-forms-field-' + id)
						.find('.price')
						.text(singleItem);
				},
			);

			// Real-time updates for Hidden Field "Default Value" option.
			$builder.on(
				'input',
				'.everest-forms-field-option-row-default_value input',
				function (e) {
					var $this = $(this),
						value = $this.val(),
						id = $this.parent().data('field-id');

					if ('hidden' === $('#everest-forms-field-' + id).data('field-type')) {
						$('#everest-forms-field-' + id)
							.find('input')
							.val(value);
					}
				},
			);

			$builder.on(
				'click',
				'.everest-forms-field-option-row-default_value .evf-smart-tag-lists .evf-others .smart-tag-field',
				function (e) {
					var $this = $(this)
							.parents('div.everest-forms-field-option-row-default_value')
							.find('input'),
						value = $this.val(),
						id = $this.parent().data('field-id');
					value = value + '{' + $(this).data('field_id') + '}';

					if ('hidden' === $('#everest-forms-field-' + id).data('field-type')) {
						$('#everest-forms-field-' + id)
							.find('input')
							.val(value);
					}
				},
			);

			// Restrict user money input fields.
			$builder.on('input', '.evf-money-input', function (event) {
				var $this = $(this),
					amount = $this.val(),
					start = $this[0].selectionStart,
					end = $this[0].selectionEnd;
				$this.val(amount.replace(/[^0-9.,]/g, ''));
				$this[0].setSelectionRange(start, end);
			});

			// Format user money input fields.
			$builder.on('focusout', '.evf-money-input', function (event) {
				var $this = $(this),
					amount = $this.val(),
					sanitized = EverestFormsProBuilder.amountSanitize(amount),
					formatted = EverestFormsProBuilder.amountFormat(sanitized);
				$this.val(formatted);
			});

			// Check Invalid Interval.
			$builder.on(
				'input',
				'#everest-forms-panel-field-stripe-interval_count',
				function () {
					var value = $(this).val();

					if (value == undefined || value == '') {
						return;
					} else if (value < 1) {
						$(this).val(1);
					} else if ($.isNumeric(value) == false) {
						$(this).val(1);
					} else if (
						$('#everest-forms-panel-field-stripe-period').val() == 'year' &&
						value > 1
					) {
						$(this).val(1);
					}
				},
			);

			$builder.on(
				'change',
				'#everest-forms-panel-field-stripe-period',
				function () {
					if ($(this).val() == 'year') {
						$('#everest-forms-panel-field-stripe-interval_count').val(1);
					}
				},
			);

			$builder.on(
				'change',
				'.everest-forms-field-option-row-max_file_number input',
				function () {
					var val = $(this).val();
					if (undefined === val || val <= 1) {
						val = 1;
						$(this).val(val);
					}

					var fieldId = $(this).parent().attr('data-field-id');
					var limit_message = $builder
						.find('#everest-forms-field-option-' + fieldId + '-limit_message')
						.val();

					$builder
						.find('#everest-forms-field-option-' + fieldId + '-limit_message')
						.val(limit_message.replace(/[0-9]+/, val));
					$builder
						.find('#everest-forms-field-' + fieldId)
						.find('span.everest-forms-upload-hint')
						.html(limit_message.replace(/[0-9]+/, val));
				},
			);

			// Enable Webhook.
			$(document).on(
				'click',
				'#everest-forms-panel-field-settings-enable_webhook',
				function () {
					if ($(this).prop('checked')) {
						$(document)
							.find('#everest-forms-panel-field-settings-webhook_url-wrap')
							.removeClass('everest-forms-hidden');
						$(document)
							.find('#everest-forms-panel-field-settings-webhook_method-wrap')
							.removeClass('everest-forms-hidden');
						$(document)
							.find('#everest-forms-panel-field-settings-webhook_format-wrap')
							.removeClass('everest-forms-hidden');
						$(document)
							.find('#everest-forms-panel-field-settings-with_header-wrap')
							.removeClass('everest-forms-hidden');

						if (
							$(this).prop('checked') &&
							'yes' ===
								$(document)
									.find('#everest-forms-panel-field-settings-with_header')
									.val()
						) {
							$(document)
								.find(
									'.everest-forms-panel-field.evf-field-webhook-headers-container.everest-forms-border-container',
								)
								.removeClass('everest-forms-hidden');
						}
					} else {
						$(document)
							.find('#everest-forms-panel-field-settings-webhook_url-wrap')
							.addClass('everest-forms-hidden');
						$(document)
							.find('#everest-forms-panel-field-settings-webhook_method-wrap')
							.addClass('everest-forms-hidden');
						$(document)
							.find('#everest-forms-panel-field-settings-webhook_format-wrap')
							.addClass('everest-forms-hidden');
						$(document)
							.find('#everest-forms-panel-field-settings-with_header-wrap')
							.addClass('everest-forms-hidden');
						$(document)
							.find(
								'.everest-forms-panel-field.evf-field-webhook-headers-container.everest-forms-border-container',
							)
							.addClass('everest-forms-hidden');
					}
				},
			);

			$(document).on(
				'change',
				'#everest-forms-panel-field-settings-with_header',
				function () {
					if (
						$(document)
							.find('#everest-forms-panel-field-settings-enable_webhook')
							.prop('checked') &&
						'yes' === $(this).val()
					) {
						$(document)
							.find(
								'.everest-forms-panel-field.evf-field-webhook-headers-container.everest-forms-border-container',
							)
							.removeClass('everest-forms-hidden');
					} else {
						$(document)
							.find(
								'.everest-forms-panel-field.evf-field-webhook-headers-container.everest-forms-border-container',
							)
							.addClass('everest-forms-hidden');
					}
				},
			);

			// Set WebHook Request Headers key-Value pair.
			$(document).on('everest_forms_save_args', function (event, form_data) {
				$(document)
					.find(
						'.everest-forms-panel-field.evf-field-webhook-headers-container.everest-forms-border-container',
					)
					.filter(function () {
						$container = $(this);

						var $id = $(this).data('id');
						if ($id === undefined) {
							return;
						}
						var webhook_headers = {};
						$container.find('div.evf-webhook-header').filter(function () {
							var $this = $(this);
							if (
								$this.find('.evf-webhook-header-value').val() &&
								$this.find('.evf-webhook-header-key').val()
							) {
								webhook_headers[$this.find('.evf-webhook-header-key').val()] =
									$this.find('.evf-webhook-header-value').val();
							}

							return webhook_headers;
						});

						if (Object.keys(webhook_headers).length > 0) {
							form_data.push({
								name: 'settings[webhooks][' + $id + '][webhook_headers]',
								value: JSON.stringify(webhook_headers),
							});
						}
					});
			});
			$builder.on(
				'input',
				'.everest-forms-field-option-row-upload_message input',
				function () {
					var val = $(this).val();
					var fieldId = $(this).parent().attr('data-field-id');
					$builder
						.find(
							'#everest-forms-field-' +
								fieldId +
								' .everest-forms-upload-title',
						)
						.html(val);
				},
			);

			$builder.on(
				'input',
				'.everest-forms-field-option-row-limit_message input',
				function () {
					var val = $(this).val();
					var fieldId = $(this).parent().attr('data-field-id');
					$builder
						.find(
							'#everest-forms-field-' + fieldId + ' .everest-forms-upload-hint',
						)
						.html(val);
				},
			);

			$builder
				.find(
					'#everest-forms-panel-field-paymentsstripe-enable_stripe-wrap input',
				)
				.click(function () {
					if ($(this).prop('checked')) {
						$(this)
							.closest(
								'#everest-forms-panel-field-paymentsstripe-enable_stripe-wrap',
							)
							.siblings('.evf-stripe-gateway-option')
							.removeClass('everest-forms-hidden');
					} else {
						$(this)
							.closest(
								'#everest-forms-panel-field-paymentsstripe-enable_stripe-wrap',
							)
							.siblings('.evf-stripe-gateway-option')
							.addClass('everest-forms-hidden');
					}
				});

			$builder
				.find(
					'#everest-forms-panel-field-paymentsstripe-enable_credit_card-wrap input',
				)
				.click(function () {
					if ($(this).prop('checked')) {
						$(this)
							.closest(
								'#everest-forms-panel-field-paymentsstripe-enable_credit_card-wrap',
							)
							.siblings('.evf-stripe-gateway-reoccuring')
							.removeClass('everest-forms-hidden');
					} else {
						$(this)
							.closest(
								'#everest-forms-panel-field-paymentsstripe-enable_credit_card-wrap',
							)
							.siblings('.evf-stripe-gateway-reoccuring')
							.addClass('everest-forms-hidden');
					}
				});

			$builder
				.find(
					'#everest-forms-panel-field-paymentsstripe-enable_credit_card-wrap input, #everest-forms-panel-field-paymentsstripe-enable_ideal-wrap input',
				)
				.click(function () {
					if ($(this).prop('checked')) {
						$(this)
							.closest('.evf-stripe-gateway-option')
							.find('.evf-stripe-gateway-conditional')
							.removeClass('everest-forms-hidden');
					} else {
						if (
							false ===
								$(
									'#everest-forms-panel-field-paymentsstripe-enable_credit_card-wrap input:last',
								).prop('checked') &&
							false ===
								$(
									'#everest-forms-panel-field-paymentsstripe-enable_ideal-wrap input:last',
								).prop('checked')
						) {
							$(this)
								.closest('.evf-stripe-gateway-option')
								.find('.evf-stripe-gateway-conditional')
								.addClass('everest-forms-hidden');
						}
					}
				});

			$builder
				.find('.everest-forms-field-option-row-show_tooltip input')
				.each(function () {
					var $this = $(this),
						$id = $this
							.closest('.everest-forms-field-option-row-show_tooltip')
							.data('field-id'),
						$toggledField = $('#everest-forms-field-option-' + $id);
					if ($this.is(':checked')) {
						$toggledField
							.find('.everest-forms-field-option-row-tooltip_description')
							.css('display', 'block');
					} else {
						$toggledField
							.find('.everest-forms-field-option-row-tooltip_description')
							.css('display', 'none');
					}
				});

			// Real-time updates for "Enable Tooltip" field option.
			$(document).on(
				'change',
				'.everest-forms-field-option-row-show_tooltip input',
				function () {
					var $this = $(this),
						$id = $this
							.closest('.everest-forms-field-option-row-show_tooltip')
							.data('field-id'),
						$toggledField = $('#everest-forms-field-option-' + $id);

					if ($this.is(':checked')) {
						EverestFormsProBuilder.initTooltips($id);
						$toggledField
							.find('.everest-forms-field-option-row-tooltip_description')
							.css('display', 'block');
					} else {
						$('#everest-forms-field-' + $id)
							.find('.everest-forms-help-tooltip')
							.remove();
						$toggledField
							.find('.everest-forms-field-option-row-tooltip_description')
							.css('display', 'none');
					}
				},
			);

			// Real-time updates for "Tooltip Description" field option.
			$(document).on(
				'input',
				'.everest-forms-field-option-row-tooltip_description textarea',
				function () {
					var $this = $(this),
						$id = $this
							.closest('.everest-forms-field-option-row-tooltip_description')
							.data('field-id'),
						value = $this.val().replace(/\n/g, '<br>');

					EverestFormsProBuilder.initTooltips($id);

					// Update tooltip content.
					$('#everest-forms-field-' + $id)
						.find('.everest-forms-help-tooltip')
						.tooltipster('content', value);
				},
			);

			// Default color for Color field.
			$(document).on('click', '.everest-forms-field-color', function () {
				var fieldContainer = $(this),
					defaultColorField = $(
						'input#everest-forms-field-option-' +
							$(this).data('field-id') +
							'-default.evf-colorpicker',
					);
				defaultColorField.wpColorPicker({
					change: function (event) {
						var $this = $(this),
							value = $this.val();
						fieldContainer
							.find('.evf-color-picker-bg')
							.css('background', value);
					},
				});
			});

			//Lookup form field setting.
			EverestFormsProBuilder.lookup_filed_settings();
		},

		/**
		 * Initialize EverestForms form area tooltips.
		 *
		 * @since 1.0.0
		 */
		initTooltips: function (id) {
			var content = $(
				'#everest-forms-field-option-' + id + '-tooltip_description',
			).val();
			$('#everest-forms-field-' + id)
				.find('.everest-forms-help-tooltip')
				.remove();

			if (undefined !== content) {
				$('#everest-forms-field-' + id)
					.find('.label-title .text')
					.after(
						'<span class="dashicons dashicons-editor-help everest-forms-help-tooltip" title="' +
							content +
							'"></span>',
					);
			}

			// Init tooltipster.
			if (typeof jQuery.fn.tooltipster !== 'undefined') {
				$('#everest-forms-field-' + id)
					.find('.everest-forms-help-tooltip')
					.tooltipster({
						contentAsHTML: true,
						position: 'right',
						maxWidth: 300,
						multiple: true,
						interactive: true,
						debug: false,
						IEmin: 11,
					});
			}
		},

		/**
		 * Sanitize amount and convert to standard format for calculations.
		 *
		 * @since 1.3.0
		 */
		amountSanitize: function (amount) {
			amount = amount.replace(/[^0-9.,]/g, '');

			if (
				',' === data.currency_decimal &&
				-1 !== amount.indexOf(data.currency_decimal)
			) {
				if (
					'.' === data.currency_thousands &&
					-1 !== amount.indexOf(data.currency_thousands)
				) {
					amount = amount.replace(data.currency_thousands, '');
				} else if (
					'' === data.currency_thousands &&
					-1 !== amount.indexOf('.')
				) {
					amount = amount.replace('.', '');
				}
				amount = amount.replace(data.currency_decimal, '.');
			} else if (
				',' === data.currency_thousands &&
				-1 !== amount.indexOf(data.currency_thousands)
			) {
				amount = amount.replace(data.currency_thousands, '');
			}

			return EverestFormsProBuilder.numberFormat(amount, 2, '.', '');
		},

		/**
		 * Format amount.
		 *
		 * @since 1.3.0
		 */
		amountFormat: function (amount) {
			amount = String(amount);

			// Format the amount.
			if (
				',' === data.currency_decimal &&
				-1 !== amount.indexOf(data.currency_decimal)
			) {
				var sepFound = amount.indexOf(data.currency_decimal);
				whole = amount.substr(0, sepFound);
				part = amount.substr(sepFound + 1, amount.strlen - 1);
				amount = whole + '.' + part;
			}

			// Strip ',' from the amount (if set as the thousands separator).
			if (
				',' === data.currency_thousands &&
				-1 !== amount.indexOf(data.currency_thousands)
			) {
				amount = amount.replace(',', '');
			}

			if (!amount) {
				amount = 0;
			}

			return EverestFormsProBuilder.numberFormat(
				amount,
				2,
				data.currency_decimal,
				data.currency_thousands,
			);
		},

		/**
		 * Format number.
		 *
		 * @link http://locutus.io/php/number_format/
		 * @since 1.3.0
		 */
		numberFormat: function (number, decimals, decimalSep, thousandsSep) {
			number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
			var n = !isFinite(+number) ? 0 : +number;
			var prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
			var sep = 'undefined' === typeof thousandsSep ? ',' : thousandsSep;
			var dec = 'undefined' === typeof decimalSep ? '.' : decimalSep;
			var s = '';

			var toFixedFix = function (n, prec) {
				var k = Math.pow(10, prec);
				return '' + (Math.round(n * k) / k).toFixed(prec);
			};

			// @todo: for IE parseFloat(0.55).toFixed(0) = 0;
			s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
			if (s[0].length > 3) {
				s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
			}
			if ((s[1] || '').length < prec) {
				s[1] = s[1] || '';
				s[1] += new Array(prec - s[1].length + 1).join('0');
			}

			if ('Euro' == data.currency_name) {
				s[0] = s[0].replace('.', '');
			}

			return s.join(dec);
		},

		livePreviewNumberOfRating: function (el) {
			var $this = $(el),
				value = $this.val();
			if (value.length == 0 || value <= 0) {
				value = 1;
			}
			var id = $this.parent().data('field-id'),
				icons = $('#everest-forms-field-' + id + ' .rating-icon').first();
			if (value <= 100) {
				$('#everest-forms-field-' + id + ' .rating-icon').remove();
				for (var $i = 1; $i <= value; $i++) {
					$('#everest-forms-field-' + id + '').append(icons.clone());
				}
			}
		},

		// Field user meta map table, update user meta key source
		updateUserMetakey: function (el) {
			var $this = $(el);
			(value = $this.val()),
				($destination = $this.parent().parent().find('.key-destination')),
				(name = $destination.data('name'));

			if (value) {
				$destination.attr(
					'name',
					name.replace('{source}', value.replace(/[^0-9a-zA-Z_-]/gi, '')),
				);
			}
		},

		userMetaAdd: function (el) {
			var $this = $(el),
				$row = $this.closest('li'),
				li_length = $this.closest('ul').find('li').length,
				choice = $row.clone().insertAfter($row);

			choice.find('input').val('');
			choice.find('select :selected').prop('selected', false);
			choice.find('.key-destination').attr('name', '');
			if ('undefined' !== typeof $this.closest('ul').attr('data-tax')) {
				var tax = $this.closest('ul').attr('data-tax');
				if ('tax' === tax) {
					next_id = li_length + 1;
					choice
						.find('.key')
						.find('.everest-forms-' + tax + '-map-select')
						.attr('name', 'settings[post_tax_' + tax + '][' + next_id + ']');
					choice
						.find('.field')
						.find('.everest-forms-' + tax + '-field-map-options')
						.attr(
							'name',
							'settings[post_tax_' + tax + '_value][' + next_id + ']',
						);
				} else if ('custom_field' === tax || 'add_custom_field' === tax) {
					next_id = li_length + 1;
					var source = $this.closest('ul').attr('data-source'),
						connection_id = $this.closest('ul').attr('data-connection_id'),
						field_value = tax + '_value';
					choice
						.find('.key')
						.find('.everest-forms-' + tax + '-map-select')
						.attr(
							'name',
							'integrations[' +
								source +
								'][' +
								connection_id +
								'][' +
								tax +
								'][' +
								next_id +
								']',
						);
					choice
						.find('.field')
						.find('.everest-forms-' + tax + '-map-options')
						.attr(
							'name',
							'integrations[' +
								source +
								'][' +
								connection_id +
								'][' +
								field_value +
								'][' +
								next_id +
								']',
						);
				}
			}
		},

		userMetaRemove: function (el) {
			var $this = $(el),
				$row = $this.closest('li'),
				$ul = $this.closest('ul'),
				total = $ul.find('li').length;

			if (total > '1') {
				$row.remove();
			}
		},

		/**
		 * AJAX-reload the Payments panel content so PHP-rendered sections
		 * (e.g. legacy PayPal credential visibility) reflect the current canvas
		 * state without requiring a full page reload or form save.
		 *
		 * @param {boolean} hasGatewaySelector Whether a payment-gateway-selector field is on the canvas.
		 */
		refreshPaymentsPanel: function ( hasGatewaySelector ) {
			var formId = evf_data.form_id;
			if ( ! formId ) {
				return;
			}

			$.ajax({
				url:    evf_data.ajax_url,
				type:   'POST',
				data:   {
					action:               'evf_refresh_payments_panel',
					nonce:                evf_data.evf_save_form,
					form_id:              formId,
					has_gateway_selector: hasGatewaySelector ? 1 : 0,
				},
				success: function ( response ) {
					if ( ! response.success || ! response.data || ! response.data.html ) {
						return;
					}
					var $panel = $( '#everest-forms-panel-payments .everest-forms-panel-content' );
					if ( $panel.length ) {
						$panel.html( response.data.html );
						EverestFormsProBuilder.reloadPaymentsTabFieldMaps();
						EverestFormsProBuilder.checkEnabledPayments();
					}
				},
			});
		},

		/**
		 * Mirror PHP form_has_payment_gateway_selector_field: hide/show legacy PayPal
		 * credential fields in the Payments tab based on whether a gateway selector
		 * field currently exists on the canvas.  Called on every gateway change so the
		 * Payments tab stays in sync without a page reload.
		 */
		syncLegacyPaypalCredentialVisibility: function () {
			var hasPgw = $builder.find( '.everest-forms-field-payment-gateway-selector' ).length > 0;
			var $legacySection = $( '.evf-content-paypal-settings' );
			if ( ! $legacySection.length ) {
				return;
			}
			var $useGlobalWrap = $legacySection.find( '#everest-forms-panel-field-paypal-use_global_setting-wrap' );
			var $innerContent  = $legacySection.find( '#everest-forms-paypal-inner-settings-content' );

			if ( hasPgw ) {
				$useGlobalWrap.hide();
				$innerContent.hide();
			} else {
				$useGlobalWrap.show();
				var isGlobalOn = $legacySection.find( '#everest-forms-panel-field-paypal-use_global_setting' ).is( ':checked' );
				if ( isGlobalOn ) {
					$innerContent.hide();
				} else {
					$innerContent.show();
				}
			}
		},

		/**
		 * Check for enabled payment on payment section
		 *
		 * @since 1.3.0
		 */
		isGatewayActiveViaSelector: function (slug) {
			return (
				$builder.find(
					'input[type="checkbox"][name*="[allowed_gateways]"][value="' +
						slug +
						'"]:enabled:checked',
				).length > 0
			);
		},

		isStripeActiveViaSelector: function () {
			return EverestFormsProBuilder.isGatewayActiveViaSelector('stripe');
		},

		/**
		 * Whether any main payment gateway toggle is on in the Payments tab.
		 * Aligns with EVFPanelBuilder.isAnyPaymentEnabled() in form-builder.js.
		 *
		 * @since 1.9.15
		 * @return {boolean}
		 */
		isAnyPaymentsTabGatewayEnabled: function () {
			if (
				typeof EVFPanelBuilder !== 'undefined' &&
				EVFPanelBuilder.isAnyPaymentEnabled
			) {
				return !!EVFPanelBuilder.isAnyPaymentEnabled();
			}
			var selectors = [
				'#everest-forms-panel-field-paymentsstripe-enable_stripe',
				'#everest-forms-panel-field-paypal-enable_paypal',
				'#everest-forms-panel-field-paymentsrazorpay-enable_razorpay',
				'#everest-forms-panel-field-razorpay-enable_razorpay',
				'input[name*="[razorpay]"][name*="enable_razorpay"]',
				'#everest-forms-panel-field-authorize_net-enable_authorize_net',
				'#everest-forms-panel-field-square-enable_square',
				'#everest-forms-panel-field-paymentsmollie-enable_mollie',
			];
			var i;
			for (i = 0; i < selectors.length; i++) {
				var $el = $(selectors[i]);
				if ($el.length && $el.is(':checked')) {
					return true;
				}
			}
			return false;
		},

		/**
		 * Show or hide the Mollie accordion in Payment Gateway settings when a Subscription Plan field is on the form.
		 *
		 * @since 1.9.16
		 * @param {string} [element_id] Optional appended field element id from evf_after_field_append.
		 */
		syncMolliePgwAccordionVisibility: function (element_id) {
			var $builderEl = $('#everest-forms-builder');
			if (!$builderEl.length) {
				return;
			}
			var hasPlan =
				$builderEl.find('.everest-forms-field-payment-subscription-plan')
					.length > 0;
			$builderEl
				.find('.evf-pgw-builder-row[data-gateway="mollie"]')
				.each(function () {
					var $row = $(this),
						$item = $row.closest('.evf-pgw-builder-item'),
						$chevron = $row.find('.evf-pgw-builder-chevron');
					if (hasPlan) {
						$chevron.removeClass('evf-pgw-builder-chevron--hidden');
					} else {
						$item.removeClass('evf-pgw-builder-item--open');
						$chevron
							.addClass('evf-pgw-builder-chevron--hidden')
							.attr('aria-expanded', 'false');
					}
				});
			if (element_id && hasPlan) {
				var $appended = $('#' + element_id);
				if (!$appended.length) {
					$appended = $('#everest-forms-field-' + element_id);
				}
				if ('payment-subscription-plan' === $appended.attr('data-field-type')) {
					EverestFormsProBuilder.reloadPaymentsTabFieldMaps();
				}
			}
		},

		/**
		 * Lock or unlock the Subscription Plan sidebar item.
		 *
		 * Other conditions (unchanged): Payment Gateway field on the canvas, or Stripe / PayPal
		 * recurring in the Payments tab, clears `enable-payment-subscription-plan`.
		 *
		 * Payments tab main gateways: if any (Stripe, PayPal, Authorize.Net, Razorpay, Square,
		 * Mollie) is enabled, applies `enable-payment-subscription-plan`. When all are off,
		 * removes that class (unless returned earlier for gateway selector / recurring).
		 *
		 * @since 1.9.15
		 */
		syncSubscriptionPlanSidebarLock: function () {
			var $btn = $('#everest-forms-add-fields-payment-subscription-plan');
			if (!$btn.length) {
				return;
			}
			var $wrap = $('#everest-forms-builder');
			if (!$wrap.length) {
				return;
			}
			var hasGatewaySelector =
				$wrap.find('.everest-forms-field-payment-gateway-selector').length > 0;
			if (hasGatewaySelector) {
				$btn.removeClass('enable-payment-subscription-plan');
				return;
			}
			var stripeRecurring =
				$('#everest-forms-panel-field-paymentsstripe-enable_stripe').prop(
					'checked',
				) && $('#everest-forms-panel-field-stripe-recurring').prop('checked');
			var paypalRecurring =
				$('#everest-forms-panel-field-paypal-enable_paypal').prop('checked') &&
				$('#everest-forms-panel-field-paypal-recurring').prop('checked');
			if (stripeRecurring || paypalRecurring) {
				$btn.removeClass('enable-payment-subscription-plan');
				return;
			}
			if (EverestFormsProBuilder.isAnyPaymentsTabGatewayEnabled()) {
				$btn.addClass('enable-payment-subscription-plan');
				return;
			}
			$btn.removeClass('enable-payment-subscription-plan');
		},

		/**
		 * Show or hide Stripe customer sync map rows for each UI block that contains them.
		 *
		 * The Payments tab and the Payment Gateway field accordion both output the same
		 * `payments[stripe][syncfields]` control with duplicate element IDs; visibility must
		 * be driven by the checkbox inside each block, not a single global `#...-syncfields`.
		 */
		syncStripeSyncFieldsVisibility: function () {
			$builder
				.find('.evf-pgw-stripe-sync-fields, .evf-stripe-gateway-reoccuring')
				.each(function () {
					var $block = $(this);
					var $toggle = $block.find(
						'input[type="checkbox"][name="payments[stripe][syncfields]"]',
					);
					if (!$toggle.length) {
						return;
					}
					$block
						.find('.evf-stripe-sync-sub-fields')
						.toggle($toggle.is(':checked'));
				});
		},

		checkEnabledPayments: function () {
			var $creditCard = $('#everest-forms-add-fields-credit-card');
			var hasPgwOnCanvas =
				$('#everest-forms-builder').find(
					'.everest-forms-field-payment-gateway-selector',
				).length > 0;

			if ($creditCard.length) {
				// Legacy Credit Card field conflicts with Payment Gateway selector; PGW defaults
				// also check allowed_gateways on drop — do not treat that as “Enable Stripe” for sidebar.
				if (hasPgwOnCanvas) {
					$creditCard.addClass('enable-stripe-model');
				} else {
					var $enableStripe = $(
						'#everest-forms-panel-field-paymentsstripe-enable_stripe',
					);
					var $enableCreditCard = $(
						'#everest-forms-panel-field-paymentsstripe-enable_credit_card',
					);
					var hasCreditCardOnCanvas =
						$('#everest-forms-builder').find(
							'.everest-forms-field-credit-card',
						).length > 0;
					var stripeActive =
						$enableStripe.length && $enableStripe.is(':checked');
					var creditCardActive =
						!$enableCreditCard.length ||
						$enableCreditCard.is(':checked') ||
						(hasCreditCardOnCanvas && stripeActive);

					if (stripeActive && creditCardActive) {
						$creditCard.removeClass('enable-stripe-model');
					} else {
						$creditCard.addClass('enable-stripe-model');
					}
				}

				if (
					typeof EVFPanelBuilder !== 'undefined' &&
					EVFPanelBuilder.refreshRegisteredSidebarFieldDraggable
				) {
					EVFPanelBuilder.refreshRegisteredSidebarFieldDraggable($creditCard);
				}
			}

			// Sync legacy PayPal credential section visibility with gateway selector canvas state.
			EverestFormsProBuilder.syncLegacyPaypalCredentialVisibility();

			// Enable disable Enable PayPal Standard.
			if ( $('#everest-forms-panel-field-paypal-enable_paypal').prop('checked') ) {
				$('#everest-forms-paypal-inner-settings').show();
			} else {
				$('#everest-forms-paypal-inner-settings').hide();
			}
			if (
				$('#everest-forms-panel-field-paypal-use_global_setting').prop(
					'checked',
				)
			) {
				$('#everest-forms-paypal-inner-settings-content').hide();
			} else {
				$('#everest-forms-paypal-inner-settings-content').show();
			}

			// Enable disable stripe recurring subscription payments (Payments tab recurring requires Enable Stripe).
			if (
				$('#everest-forms-panel-field-paymentsstripe-enable_stripe').prop(
					'checked',
				) &&
				$('#everest-forms-panel-field-stripe-recurring').prop('checked')
			) {
				$('#everest-forms-panel-field-stripe-plan_name-wrap').show();
				$('#everest-forms-panel-field-stripe-interval_count-wrap').show();
				$('#everest-forms-panel-field-stripe-period-wrap').show();
				$('#everest-forms-panel-field-stripe-customer_email-wrap').show();
			} else {
				$('#everest-forms-panel-field-stripe-plan_name-wrap').hide();
				$('#everest-forms-panel-field-stripe-interval_count-wrap').hide();
				$('#everest-forms-panel-field-stripe-period-wrap').hide();
				$('#everest-forms-panel-field-stripe-customer_email-wrap').hide();
			}

			// Stripe "sync fields" sub-rows: scoped per block (Payments tab + Payment Gateway accordion
			// both render the same field names; duplicate IDs made $('#...stripe-syncfields') unreliable).
			EverestFormsProBuilder.syncStripeSyncFieldsVisibility();

			// Enable disable paypal recurring subscription payments (Payments tab recurring requires Enable PayPal).
			if (
				$('#everest-forms-panel-field-paypal-enable_paypal').prop('checked') &&
				$('#everest-forms-panel-field-paypal-recurring').prop('checked')
			) {
				$('#everest-forms-paypal-recurring-payment').show();
			} else {
				$('#everest-forms-paypal-recurring-payment').hide();
			}

			EverestFormsProBuilder.syncSubscriptionPlanSidebarLock();
		},

		/**
		 * Show hide allowed or denied domains in whitelist domain.
		 *
		 * @param {object} whitelist_domain - A Jquery object representing an HTML div element for whitelist_domain.
		 */
		showHideAllowedDeniedDomain: function (whitelist_domain) {
			const whitelist_domains = $builder.find(
				'.everest-forms-field-option-row.everest-forms-field-option-row-whitelist_domain',
			);
			if (whitelist_domains.length) {
				$(whitelist_domains).each(function () {
					const $this =
						undefined !== whitelist_domain ? whitelist_domain : $(this);
					const select_whitelist = $this.find('select');
					const allowed_domains = $this.next(
						'.everest-forms-field-option-row-allowed_domains',
					);
					const denied_domains = allowed_domains.next(
						'.everest-forms-field-option-row-denied_domains',
					);
					if ('allow' === select_whitelist.val()) {
						denied_domains.hide();
						allowed_domains.show();
					} else {
						allowed_domains.hide();
						denied_domains.show();
					}
				});
			}
		},
		/**
		 *Lookup Field Settings.
		 *
		 * @since 1.6.7.1
		 */
		lookup_filed_settings: function () {
			$(document).on('change', '.evf-lookup-form-name-select', function (e) {
				var $this = $(this),
					field_id = $($this).closest('div').attr('data-field-id'),
					field_name =
						'#everest-forms-field-option-' + field_id + '-lookup_field_name';
				$(field_name).prop('disabled', true);
				$.ajax({
					url: everest_forms_builder.ajax_url,
					type: 'POST',
					data: {
						action: 'evf_field_name_list_for_lookup',
						security: everest_forms_builder.evf_lookup_field_nonce,
						formID: $this.val(),
					},
					success: function (res) {
						if (res.success === true) {
							var html = '';
							$(field_name).prop('disabled', false);
							for (var key in res.data) {
								html +=
									'<option value="' + key + '">' + res.data[key] + '</option>';
							}
							$(field_name).html(html);
						}
					},
				});
			});
			$(document.body).on(
				'evf_after_field_append',
				EverestFormsProBuilder.checkNewLookupFieldAdd,
			);
			$(document.body).on(
				'evf_before_field_deleted',
				EverestFormsProBuilder.checkFieldLookupDelete,
			);
			$(document).on(
				'keyup',
				'.everest-forms-field-option-row-label',
				function (e) {
					EverestFormsProBuilder.LiveFilterField(this, e);
				},
			);
			$(document).on('click', '.everest-forms-field-lookup', function (e) {
				EverestFormsProBuilder.LiveFilterField(this, e);
			});
		},
		checkNewLookupFieldAdd: function (e, element_id) {
			var $current_field = $('#' + element_id),
				elType = $($current_field).data('field-type');
			if (elType === 'lookup') {
				$(document.body).trigger('evf-enhanced-select-init');
				EverestFormsProBuilder.livelookupFieldChange('add', $current_field);
			}
		},
		checkFieldLookupDelete: function (e, element_id) {
			var $current_field = $('#everest-forms-field-' + element_id);
			elType = $($current_field).data('field-type');
			if (elType === 'lookup') {
				EverestFormsProBuilder.livelookupFieldChange('remove', $current_field);
			}
		},
		livelookupFieldChange: function (action, $field) {
			var $filter_by_field = $('.evf-lookup-filter-by-field-select'),
				element_id = $field.attr('data-field-id'),
				label = $field.find('label.label-title span.text').text();

			if ('add' === action) {
				var option =
					'<option value="' + element_id + '">' + label + '</option>';
				$filter_by_field.append(option);
			} else if ('remove' === action) {
				$filter_by_field.find('option').each(function (index, option) {
					var $option = $(option);
					if (element_id === $option.val()) {
						$option.remove();
					}
				});
			}
		},
		LiveFilterField: function (el, e) {
			var selected_field_id = $('.everest-forms-field-option:visible').data(
				'field-id',
			);
			var select_fields = $('.evf-lookup-filter-by-field-select');
			select_fields.find('option').each(function (index, option) {
				var $option = $(option);
				if (e.type === 'keyup') {
					if (selected_field_id === $option.val()) {
						$option.text($(el).children('input').val());
					}
				}
				if (e.type === 'click') {
					if (selected_field_id === $option.val()) {
						$option.hide();
					} else {
						$option.show();
					}
				}
			});
		},
		add_webhooks: function ($this) {
			$.confirm({
				title: everest_forms_builder.webhook_title,
				content:
					'<input type="text" placeholder="Enter the webhook title key" class="evf-web-hooks-name" name="evf_web_hooks_name" required />',
				buttons: {
					confirm: {
						text: everest_forms_builder.webhook_confirm_btn_text,
						theme: 'jconfirm-modern jconfirm-everest-forms-left',
						backgroundDismiss: false,
						btnClass: 'everest-forms-btn everest-forms-btn-primary',
						action: function () {
							var webhook_title_value = this.$content
								.find('.evf-web-hooks-name')
								.val();

							if ('' === webhook_title_value) {
								this.setContentAppend(
									everest_forms_builder.webhook_title_required,
								);
								return;
							}
							$.ajax({
								url: everest_forms_builder.ajax_url,
								type: 'POST',
								data: {
									security: everest_forms_builder.evf_webhook_nonce,
									action: 'everest_forms_pro_add_webhooks',
									next_id: webhook_title_value,
								},
								success: (res) => {
									this.$content.find('.evf-web-hooks-name').val('');
									$this
										.closest('.evf-section-webhooks-add-new')
										.after(res.data.content);
								},
							});
						},
					},
				},
			});
		},
		/**
		 * Enable disable the stripe recurring payment setting.
		 *
		 * @since 1.7.9
		 * */
		stripeRecurringPayment: function () {
			var enable_stripe_toggle = $builder.find(
				'#everest-forms-panel-field-paymentsstripe-enable_stripe',
			);

			if (
				enable_stripe_toggle.prop('checked') ||
				EverestFormsProBuilder.isStripeActiveViaSelector()
			) {
				$builder
					.find('.evf-stripe-gateway-reoccuring')
					.removeClass('everest-forms-hidden');
			} else {
				$builder
					.find('.evf-stripe-gateway-reoccuring')
					.addClass('everest-forms-hidden');
			}
		},
		conditionalConfirmation: function () {
			$(document).on(
				'click',
				'.evf-delete-conditional-confirmation',
				function (e) {
					e.preventDefault();
					$outerWrapper = $(this).closest('.evf-confirmation-wrap');
					$outerWrapper.remove();

					var total_conditional_confirmation = $(document).find(
						'.evf-custom-confirmation-wrap',
					);

					//Hide the delete if only one setting exist.
					if (total_conditional_confirmation.length === 1) {
						$('.evf-delete-conditional-confirmation').addClass(
							'everest-forms-hidden',
						);
					}
				},
			);

			$('.evf-confirmation-edit-ok').on('click', function (e) {
				e.preventDefault();
				var titleWrap = $(this).closest('.confirmation-title');
				$(this).addClass('everest-forms-hidden');
				$(titleWrap).find('.show-title').removeClass('everest-forms-hidden');
				$(titleWrap).find('.edit-title').addClass('everest-forms-hidden');
				$(titleWrap)
					.find('.evf-edit-confirm-icon')
					.removeClass('everest-forms-hidden');
			});

			$(document).on('change', '.evf-confirm-edit-title-input', function () {
				var value = $(this).val();
				var titleWrap = $(this).closest('.confirmation-title');

				$(titleWrap).find('.show-title').text(value);
			});

			$(document).on('change', '.evf-confirmationn-active-toggle', function () {
				var outerWrap = $(this).closest('.evf-confirmation-wrap');

				if ($(this).is(':checked')) {
					outerWrap.find('.active-status').removeClass('everest-forms-hidden');
					outerWrap.find('.inactive-status').addClass('everest-forms-hidden');
				} else {
					outerWrap
						.find('.inactive-status')
						.removeClass('everest-forms-hidden');
					outerWrap.find('.active-status').addClass('everest-forms-hidden');
				}
			});

			$(document).on(
				'click',
				'.evf-close-custom-confirm-settings',
				function (e) {
					e.preventDefault();

					var outerWrap = $(this).closest('.evf-confirmation-wrap');

					outerWrap
						.find(
							'.evf-conditional-confirmation-body, .evf-conditional-confirmation-active-toggle, .evf-field-confirmation-conditional-container',
						)
						.toggle();

					if (
						!outerWrap
							.find('.evf-delete-conditional-confirmation')
							.hasClass('everest-forms-hidden')
					) {
						outerWrap.find('.evf-delete-conditional-confirmation').toggle();
					}
				},
			);
		},
		bindIntegrationInstaller: function() {
			// Handle clicks on non-active integration items in the sidebar.
			$('body').on('click', '.evf-addon-install-trigger', function(e) {
				e.preventDefault();
				EverestFormsProBuilder.openAddonInstallDialog($(this), e);
			});

			// Handle clicks on catalog items in the content area.
			$('body').on('click keypress', '.evf-addon-catalog-item', function(e) {
				if (e.type === 'keypress' && e.which !== 13) { return; }
				e.preventDefault();
				EverestFormsProBuilder.openAddonInstallDialog($(this), e);
			});

			// If directly loaded on integrations tab, activate the first connected integration.
			if ($('#everest-forms-panel-integrations').hasClass('active')) {
				var $firstConn = $('#everest-forms-panel-integrations')
					.find('.everest-forms-panel-sidebar a')
					.not('.evf-addon-install-trigger')
					.not('.upgrade-addons-settings')
					.first();
				if ($firstConn.length) {
					$firstConn.trigger('click');
				}
			}
		},

		openAddonInstallDialog: function($el, event) {
			if (typeof wp === 'undefined' || typeof wp.updates === 'undefined') { return; }

			var name      = $el.data('name') || '';
			var slug      = $el.data('slug') || '';
			var status    = $el.data('status') || 'not-installed';
			var addonsUrl = $el.data('addons-url') || '';
			var strings   = typeof evf_setup_params !== 'undefined' ? evf_setup_params : {};

			if (!slug) { return; }

			var isInactive = status === 'inactive';
			var title      = isInactive ? 'Addon Activation Required' : 'Addon Installation Required';
			var message    = isInactive
				? 'Please activate ' + name + ' addon to use this integration.'
				: 'Please install ' + name + ' addon to use this integration.';
			var btnText    = isInactive ? 'Activate' : 'Install Addon';

			$.alert({
				title:                   title,
				theme:                   'jconfirm-modern jconfirm-everest-forms',
				icon:                    'dashicons dashicons-lock',
				backgroundDismiss:       false,
				scrollToPreviousElement: false,
				content:                 message,
				type:                    'blue',
				buttons: {
					confirm: {
						text:     btnText,
						btnClass: 'btn-confirm',
						keys:     ['enter'],
						action:   function() {
							EverestFormsProBuilder.runAddonInstall(name, slug, addonsUrl, strings);
						},
					},
				},
			});
		},

		runAddonInstall: function(name, slug, addonsUrl, strings) {
			var progressAlert = $.alert({
				title:                   'Activating...',
				theme:                   'jconfirm-modern jconfirm-everest-forms',
				icon:                    'dashicons dashicons-admin-plugins',
				backgroundDismiss:       false,
				scrollToPreviousElement: false,
				content:                 strings.installing_message || 'Please wait while the addon is being activated.',
				type:                    'blue',
				buttons:                 false,
			});

			wp.updates.maybeRequestFilesystemCredentials({});
			wp.updates.queue.push({
				action: 'everest_forms_install_extension',
				data: { page: pagenow, name: name, slug: slug },
			});

			$(document).off('.evf-catalog-install');
			$(document).on(
				'wp-plugin-install-success.evf-catalog-install wp-plugin-install-error.evf-catalog-install',
				function(event, response) {
					if (response.slug !== slug) { return; }
					$(document).off('.evf-catalog-install');
					progressAlert.close();

					var hasError = typeof response.errorMessage !== 'undefined' && response.errorMessage.length > 0;
					if (hasError) {
						var errLink = addonsUrl ? ' <a href="' + addonsUrl + '" target="_blank">Visit Addons page</a>' : '';
						$.alert({
							title:   strings.download_failed || 'Download Failed',
							content: response.errorMessage + errLink,
							icon:    'dashicons dashicons-lock',
							type:    'red',
							theme:   'jconfirm-modern jconfirm-everest-forms',
						});
					} else {
						$.confirm({
							title:                   strings.install_confirmation_title || 'Installation Successful.',
							theme:                   'jconfirm-modern jconfirm-everest-forms',
							icon:                    'dashicons dashicons-lock',
							backgroundDismiss:       false,
							scrollToPreviousElement: false,
							content:                 strings.install_confirmation_message || 'Addon installed and activated. Please reload to start using the integration.',
							type:                    'green',
							buttons: {
								confirm: {
									text:     strings.save_changes_text || 'Save & Reload',
									btnClass: 'btn-warning',
									action:   function() {
										$('.everest-forms-save-button').trigger('click');
										location.reload();
									},
								},
								cancel: {
									text:     strings.reload_text || 'Just Reload',
									btnClass: 'btn-warning',
									action:   function() { location.reload(); },
								},
							},
						});
					}
				}
			);

			wp.updates.queueChecker();
		},
		editFormConfirmation: function() {
			$('.evf-edit-confirm-icon').on('click', function(e){
				e.preventDefault();
				var titleWrap = $(this).closest('.confirmation-title');
				$(this).addClass('everest-forms-hidden');
				$(titleWrap).find('.show-title').addClass('everest-forms-hidden');
				$(titleWrap).find('.edit-title').removeClass('everest-forms-hidden');
				$(titleWrap)
					.find('.evf-confirmation-edit-ok')
					.removeClass('everest-forms-hidden');
			});
		},
	};

	EverestFormsProBuilder.init(jQuery);
})(jQuery, everest_forms_builder, evf_data);

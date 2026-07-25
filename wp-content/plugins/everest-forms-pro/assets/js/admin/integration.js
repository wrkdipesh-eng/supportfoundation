/**
 * EverestFormsIntegration JS
 * global evfp_params
 */
(function ($) {
	var s;
	var EverestFormsIntegration = {
		settings: {
			form: $('#everest-forms-builder-form'),
			spinner: '<i class="evf-loading evf-loading-active" />',
		},

		/**
		 * Start the engine.
		 *
		 */
		init: function () {
			s = this.settings;
			// Document ready
			$(document).ready(EverestFormsIntegration.ready);
			if ($('.evf-connection-list-table tbody tr').length === 0) {
				$('.toggle-switch').removeClass('connected');
			}

			$('.everest-forms-active-connections-list li')
				.first()
				.addClass('active-user');
			$('.evf-provider-connections div').first().addClass('active-connection');

			EverestFormsIntegration.bindUIActions();

			var conditional_cb = $('.evf-provider-conditional input');
			conditional_cb.each(function (index, el) {
				if ($(this).is(':checked')) {
					EverestFormsIntegration.getAllAvailableFields(
						$(this).closest('.evf-provider-conditional'),
					);
					var conditional_content = $(this)
						.parent('p')
						.siblings('.evf-conditional-container');
					conditional_content.fadeIn('slow');
					conditional_content
						.find('.evf-conditional-field-select')
						.trigger('change');
				}
			});
		},
		ready: function () {
			s.formID = $('#everest-forms-builder-form').data('id');
			$('.evf-provider-tags-select').select2({ width: '100%' });

			$('#everest-forms-builder').on(
				'input',
				'.everest-forms-field-option-row-label input',
				function () {
					var $this = $(this),
						value = $this.val(),
						id = $this.parent().data('field-id'),
						$evf_integration_Setting = $('.everest-forms-panel-content');

					$evf_integration_Setting
						.find('.evf-provider-fields  select')
						.each(function (index, element) {
							var $list_type = '';
							var $element = $(element);
							$element.find('option').each(function (index, option) {
								var $option = $(option);
								$list_type = $option.val().split('.').pop().replace('', '');
							});
							$evf_integration_Setting
								.find(
									'.evf-provider-fields select option[value="' +
										id +
										'.value.' +
										$list_type +
										'"]',
								)
								.text(value);
						});

					// Custom Fields Live effect
					$evf_integration_Setting
						.find(
							'.evf-provider-custom-fields select.everest-forms-custom_field-map-options',
						)
						.each(function (index, element) {
							var $element = $(element);
							var $list_type = $element
								.find('option')
								.last()
								.val()
								.split('.')
								.pop()
								.replace('', '');
							$evf_integration_Setting
								.find(
									'.evf-provider-custom-fields select.everest-forms-custom_field-map-options option[value="' +
										id +
										'.value.' +
										$list_type +
										'"]',
								)
								.text(value);
						});
				},
			);
		},

		/**
		 * Element bindings.
		 *
		 */
		bindUIActions: function () {
			$(document.body).on(
				'evf_after_field_append',
				EverestFormsIntegration.checkIntegrationFieldAdd,
			);
			$(document.body).on(
				'evf_before_field_deleted',
				EverestFormsIntegration.checkIntegrationDelete,
			);

			$(document).on(
				'click',
				'.everest-forms-integration-connect-account',
				function (e) {
					EverestFormsIntegration.connect(this, e);
				},
			);
			$(document).on(
				'click',
				'.everest-forms-integration-verify-account',
				function (e) {
					EverestFormsIntegration.verify(this, e);
				},
			);
			$(document).on(
				'click',
				'.everest-forms-integration-disconnect-account',
				function (e) {
					EverestFormsIntegration.disconnect(this, e);
				},
			);
			$(document).on('click', '.everest-forms-connections-add', function (e) {
				EverestFormsIntegration.connectionAdd(this, e);
			});
			$(document).on(
				'click',
				'.everest-forms-source-account-add button',
				function (e) {
					EverestFormsIntegration.accountAdd(this, e);
				},
			);
			$(document).on('change', '.evf-provider-accounts select', function (e) {
				EverestFormsIntegration.accountSelect(this, e);
			});
			$(document).on('change', '.evf-provider-lists select', function (e) {
				EverestFormsIntegration.accountListSelect(this, e);
			});

			$(document).on('change', '#evf-airtable-schema', function (e) {
				EverestFormsIntegration.schemaListSelect(this, e);
			});

			$(document).on(
				'click',
				'.everest-forms-active-connections-list li a',
				function (e) {
					EverestFormsIntegration.selectActiveAccount(this, e);
				},
			);
			$(document).on('click', '.toggle-remove', function (e) {
				EverestFormsIntegration.removeAccount(this, e);
			});
			$(document).on(
				'change',
				'.evf-provider-conditional .evf-enable-conditional-logic',
				function (e) {
					EverestFormsIntegration.enableConditionLogic(this, e);
				},
			);
			$(document).on('change', '.evf-conditional-field-select', function (e) {
				EverestFormsIntegration.inputType(this, e);
			});
			$(document).on('click', '.add_new_custom_field', function (e) {
				EverestFormsIntegration.addNewCustomField(this, e);
			});
			$(document).on('click', '.add_new_tag', function (e) {
				//EverestFormsIntegration.addNewTags(this,e);
			});

			// Get trello access token
			$(document).on('click', '.evf-get-trello-token', function (e) {
				e.preventDefault();
				$url = $('.evf-trello-get-url').data('get_access_token_url');
				$apiKey = $('.evf-trello-get-url').val();
				if ('' !== $apiKey) {
					window.open($url + $apiKey, '_blank');
				} else {
					$.alert({
						title: false,
						content: 'Trello API key is required.',
						icon: 'dashicons dashicons-info',
						type: 'red',
						buttons: {
							confirm: {
								text: evfp_params.i18n_ok,
								btnClass: 'btn-confirm',
								keys: ['enter'],
							},
						},
					});
				}
			});

			// Open URI in a popup window.
			$(document).on(
				'click',
				'.everest-forms-integration-open-window',
				function (e) {
					e.preventDefault();


					var $detail = $(this).closest('.integration-connection-detail');

					if ('zoho' === $(this).data('source')) {

						if ('' === $detail.find('input.everest_forms_zoho_client_id').val()) {
							$.alert({
								title: false,
								content: 'To get the Access Code. Zoho Client ID is required',
								icon: 'dashicons dashicons-info',
								type: 'orange',
								buttons: {
									confirm: {
										text: evfp_params.i18n_ok,
										btnClass: 'btn-confirm',
										keys: ['enter'],
									},
								},
							});
							return false;
						}

						var url = $(this).attr('href'),

							client_id = $detail.find('.everest_forms_zoho_client_id').val(),
							old_client_id = url.match(/client_id\=(.*?)\&/)[1];

						url = url.replace(old_client_id, client_id);
						$(this).attr('href', url);

						$detail
							.find('.evf-connection-form')
							.parent('form')
							.find('.everest-forms-hidden')
							.removeClass('everest-forms-hidden');
						$detail
							.find('.everest-forms-source-account-add.evf-connection-block')
							.find('.everest-forms-hidden')
							.removeClass('everest-forms-hidden');
					}

					if ('salesforce' === $(this).data('source')) {
						var query = new URLSearchParams();

						query.append(
							'client_id',
							$(this)
								.siblings('.evf-connection-form')
								.find('input[data-name="client_id"]')
								.val(),
						);

						query.append(
							'client_secret',
							$(this)
								.siblings('.evf-connection-form')
								.find('input[data-name="client_secret"]')
								.val(),
						);

						query.append('response_type', 'code');

						query.append('scope', 'api refresh_token');

						query.append('prompt', 'login consent');

						query.append('redirect_uri', $(this).data('redirect-uri'));

						var url =
							$(this).attr('href').replace('/?.*$/', '') +
							'?' +
							query.toString();

						$(this).attr('href', url);
						$(this)
							.siblings('.evf-salesforce-account')
							.removeClass('everest-forms-hidden');
						$(this)
							.siblings('.evf-salesforce-connect')
							.css({ display: 'block' });
					}

					if ('constant_contact' === $(this).data('source')) {

						if (
							'' ===
							$detail.find('input.everest_forms_constant_contact_client_id').val()
						) {
							$.alert({
								title: false,
								content:
									'To get the Access Code. Constant Contact Client ID is required',
								icon: 'dashicons dashicons-info',
								type: 'orange',
								buttons: {
									confirm: {
										text: evfp_params.i18n_ok,
										btnClass: 'btn-confirm',
										keys: ['enter'],
									},
								},
							});
							return false;
						}

						var url = $(this).attr('href'),

							client_id = $detail.find('.everest_forms_constant_contact_client_id').val(),
							old_client_id = url.match(/client_id\=(.*?)\&/)[1];

						url = url.replace(old_client_id, client_id);
						$(this).attr('href', url);

						$detail
							.find('.evf-connection-form')
							.parent('form')
							.find('.everest-forms-hidden')
							.removeClass('everest-forms-hidden');
						$detail
							.find('.everest-forms-source-account-add.evf-connection-block')
							.find('.everest-forms-hidden')
							.removeClass('everest-forms-hidden');
					}

					if ('hubspot' === $(this).data('source')) {

						if (
							'' ===
							$detail.find('input.everest_forms_hubspot_client_id').val()
						) {
							$.alert({
								title: false,
								content: 'To get the Access Code. HubSpot Client ID is required',
								icon: 'dashicons dashicons-info',
								type: 'orange',
								buttons: {
									confirm: {
										text: evfp_params.i18n_ok,
										btnClass: 'btn-confirm',
										keys: ['enter'],
									},
								},
							});
							return false;
						}

						var url = $(this).attr('href'),

							client_id = $detail.find('.everest_forms_hubspot_client_id').val(),
							old_client_id = url.match(/client_id\=(.*?)\&/)[1];

						url = url.replace(old_client_id, client_id);
						$(this).attr('href', url);

						$detail
							.find('.evf-connection-form')
							.parent('form')
							.find('.everest-forms-hidden')
							.removeClass('everest-forms-hidden');
						$detail
							.find('.everest-forms-source-account-add.evf-connection-block')
							.find('.everest-forms-hidden')
							.removeClass('everest-forms-hidden');
					}

					if ('onedrive' === $(this).data('source')) {

						if (
							'' === $detail.find('input.everest_forms_onedrive_client_id').val() ||
							'' === $detail.find('input.everest_forms_onedrive_tenant_id').val()
						) {
							$.alert({
								title: false,
								content:
									'To get the Access Code. OneDrive Tenant ID and Client ID are required.',
								icon: 'dashicons dashicons-info',
								type: 'orange',
								buttons: {
									confirm: {
										text: evfp_params.i18n_ok,
										btnClass: 'btn-confirm',
										keys: ['enter'],
									},
								},
							});
							return false;
						}

						var url = $(this).attr('href'),
							
							client_id = $detail.find('.everest_forms_onedrive_client_id').val(),
							old_client_id = url.match(/client_id\=(.*?)\&/)[1],
							tenant_id = $detail.find('.everest_forms_onedrive_tenant_id').val(),
							old_tenant_id = url.match(/com\/(.*?)\/oauth2/)[1];

						url = url.replace(old_client_id, client_id);
						url = url.replace(old_tenant_id, tenant_id);

						$(this).attr('href', url);

						$detail
							.find('.evf-connection-form')
							.parent('form')
							.find('.hidden')
							.removeClass('hidden');
						$detail
							.find('.evf-connection-form')
							.parent('form')
							.find('.everest-forms-integration-connect-account')
							.show();
					}

					if ('aweber' === $(this).data('source')) {

						if (
							'' ===
							$detail.find('input.everest_forms_aweber_client_id').val()
						) {
							$.alert({
								title: false,
								content:
									'To get the Access Code. AWeber Client ID is required',
								icon: 'dashicons dashicons-info',
								type: 'orange',
								buttons: {
									confirm: {
										text: evfp_params.i18n_ok,
										btnClass: 'btn-confirm',
										keys: ['enter'],
									},
								},
							});
							return false;
						}

						var url = $(this).attr('href'),

							client_id = $detail.find('.everest_forms_aweber_client_id').val(),
							old_client_id = url.match(/client_id\=(.*?)\&/)[1];

						url = url.replace(old_client_id, client_id);
						$(this).attr('href', url);

						$detail
							.find('.evf-connection-form')
							.parent('form')
							.find('.everest-forms-hidden')
							.removeClass('everest-forms-hidden');
						$detail
							.find('.everest-forms-source-account-add.evf-connection-block')
							.find('.everest-forms-hidden')
							.removeClass('everest-forms-hidden');
					}

					if ('cleverreach' === $(this).data('source')) {

						if ('' === $detail.find('.evf_cr_client_id').val()) {
							$.alert({
								title: false,
								content:
									'To get the Access Code. CleverReach Client ID is required',
								icon: 'dashicons dashicons-info',
								type: 'orange',
								buttons: {
									confirm: {
										text: evfp_params.i18n_ok,
										btnClass: 'btn-confirm',
										keys: ['enter'],
									},
								},
							});
							return false;
						}

						var url = $(this).attr('href'),

							client_id = $detail.find('.evf_cr_client_id').val(),
							old_client_id = url.match(/client_id\=(.*?)\&/)[1];

						url = url.replace(old_client_id, client_id);
						$(this).attr('href', url);

						$detail
							.find('.evf-connection-form')
							.parent('form')
							.find('.everest-forms-hidden')
							.removeClass('everest-forms-hidden');
						$detail
							.find('.everest-forms-source-account-add.evf-connection-block')
							.find('.everest-forms-hidden')
							.removeClass('everest-forms-hidden');
					}

					if ('amocrm' === $(this).data('source')) {

						if (
							'' ===
							$detail.find('input.everest_forms_amocrm_client_id').val()
						) {
							$.alert({
								title: false,
								content:
									'To get the Access Code. amoCRM Integration ID is required',
								icon: 'dashicons dashicons-info',
								type: 'orange',
								buttons: {
									confirm: {
										text: evfp_params.i18n_ok,
										btnClass: 'btn-confirm',
										keys: ['enter'],
									},
								},
							});
							return false;
						}

						var url = $(this).attr('href'),

							client_id = $detail.find('.everest_forms_amocrm_client_id').val(),
							old_client_id = url.match(/client_id\=(.*?)\&/)[1];

						url = url.replace(old_client_id, client_id);

						$(this).attr('href', url);

						$detail
							.find('.evf-connection-form')
							.parent('form')
							.find('.everest-forms-hidden')
							.removeClass('everest-forms-hidden');
						$detail
							.find('.everest-forms-source-account-add.evf-connection-block')
							.find('.everest-forms-hidden')
							.removeClass('everest-forms-hidden');
					}


					$detail.find('.evf-connection-form').show();

					if ('google_sheets' === $(this).data('source')) {
						$detail
							.find('.evf-connection-form')
							.parent('form')
							.find('.hidden')
							.css('margin-top', '20px')
							.removeClass('hidden');
						$detail
							.find(
								'.evf-connection-form :input:not(".everest_forms_google_sheets_client_id,.everest_forms_google_sheets_client_secret")',
							)
							.val('');
					}

					if ('dropbox' === $(this).data('source')) {
						$detail
							.find('.evf-connection-form')
							.parent('form')
							.find('.hidden')
							.css('margin-top', '20px')
							.removeClass('hidden');
						$detail
							.find(
								'.evf-connection-form :input:not(".everest_forms_dropbox_auth_code")',
							)
							.val('');
					}

					if ('google_drive' === $(this).data('source')) {
						$detail
							.find('.evf-connection-form')
							.parent('form')
							.find('.hidden')
							.css('margin-top', '20px')
							.removeClass('hidden');
						$detail
							.find(
								'.evf-connection-form :input:not(".everest_forms_google_drive_client_id,.everest_forms_google_drive_client_secret")',
							)
							.val('');
					}

					if ('google_calendar' === $(this).data('source')) {
						$detail
							.find('.evf-connection-form')
							.parent('form')
							.find('.hidden')
							.css('margin-top', '20px')
							.removeClass('hidden');
						$detail
							.find(
								'.evf-connection-form :input:not(".everest_forms_google_calendar_client_id,.everest_forms_google_calendar_client_secret")',
							)
							.val('');
					}

					$(this).hide();
					EverestFormsIntegration.inputToggle($(this), 'enable');
					var newWindow = window.open(
						$(this).attr('href'),
						'name',
						'width=450,height=600',
					);

					// Puts focus on the newWindow.
					if (window.focus) {
						newWindow.focus();
					}
				},
			);

			$(document).on(
				'change',
				'#everest_forms_google_sheets_client_id',
				function () {
					var client_id = $(this).val();
					if (client_id && undefined !== client_id) {
						var target = $(
							'.everest-forms-integration-open-window[data-source="google_sheets"]',
						);
						var url = target.attr('href');
						var old_client_id = url.match(/client_id\=(.*?)\&/)[1];
						url = url.replace(old_client_id, client_id);
						target.attr('href', url);
					}
				},
			);

			$(document).on(
				'change',
				'#everest_forms_google_drive_client_id',
				function () {
					var client_id = $(this).val();
					if (client_id && undefined !== client_id) {
						var target = $(
							'.everest-forms-integration-open-window[data-source="google_drive"]',
						);
						var url = target.attr('href');
						var old_client_id = url.match(/client_id\=(.*?)\&/)[1];
						url = url.replace(old_client_id, client_id);
						target.attr('href', url);
					}
				},
			);
			$(document).on(
				'change',
				'#everest_forms_google_calendar_client_id',
				function () {
					var client_id = $(this).val();
					if (client_id && undefined !== client_id) {
						var target = $(
							'.everest-forms-integration-open-window[data-source="google_calendar"]',
						);
						var url = target.attr('href');
						var old_client_id = url.match(/client_id\=(.*?)\&/)[1];
						url = url.replace(old_client_id, client_id);
						target.attr('href', url);
					}
				},
			);

			$(document).on('change', '.everest_forms_zoho_client_id', function () {
				var client_id = $(this).val();
				if (client_id && undefined !== client_id) {

					var target = $(this)
						.closest('.integration-connection-detail')
						.find('.everest-forms-integration-open-window[data-source="zoho"]');
					var url = target.attr('href');
					var old_client_id = url.match(/client_id\=(.*?)\&/)[1];
					url = url.replace(old_client_id, client_id);
					target.attr('href', url);
				}
			});

			$(document).on('change', '.everest_forms_zoho_account_url', function () {
				var account_url = $(this).val();
				if (account_url && undefined !== account_url) {

					var target = $(this)
						.closest('.integration-connection-detail')
						.find('.everest-forms-integration-open-window[data-source="zoho"]');
					var url = target.attr('href');

					url = url.replace(
						url.match(/accounts\.zoho\.(.*?)\/oauth/)[1],
						account_url.split('https://accounts.zoho.')[1],
					);
					target.attr('href', url);
				}
			});
		},

		liveFieldChange: function (action, $field) {
			var $evf_integration_Setting = $('.everest-forms-panel-content'),
				field_type = $field.attr('data-field-type'),
				element_id = $field.attr('data-field-id'),
				label = $field.find('label.label-title span.text').text();

			$evf_integration_Setting
				.find('.evf-provider-fields  select')
				.each(function (index, element) {
					var $element = $(element);
					if ('add' === action) {
						if (
							($element.attr('name').match(/email\]$/) &&
								field_type === 'email') ||
							null === $element.attr('name').match(/email\]$/)
						) {
							var $list_type = '';
							$element.find('option').each(function (index, option) {
								var $option = $(option);
								$list_type = $option.val().split('.').pop().replace('', '');
							});
							$list_type =
								$element.attr('name').match(/vote\]$/) &&
								field_type === 'yes-no'
									? 'vote'
									: $list_type;
							$list_type =
								$element.attr('name').match(/attachment\]$/) &&
								(field_type === 'file-upload' || field_type === 'image-upload')
									? 'attachment'
									: $list_type;
							$list_type =
								$element.attr('name').match(/ic_address\]$/) &&
								field_type === 'address'
									? 'ic_address'
									: $list_type;
							var option =
								'<option value="' +
								element_id +
								'.value.' +
								$list_type +
								'">' +
								label +
								'</option>';
							$element.append(option);
						}
					} else if ('remove' === action) {
						$element.find('option').each(function (index, option) {
							var $option = $(option);
							var $option_id = $option.val().split('.');
							if (element_id === $option_id[0]) {
								$option.remove();
							}
						});
					}
				});

			// Custom Fields Live effect
			$evf_integration_Setting
				.find(
					'.evf-provider-custom-fields select.everest-forms-custom_field-map-options',
				)
				.each(function (index, element) {
					var $element = $(element);
					if ('add' === action) {
						var $list_type = $element
							.find('option')
							.last()
							.val()
							.split('.')
							.pop()
							.replace('', '');
						var option =
							'<option value="' +
							element_id +
							'.value.' +
							$list_type +
							'">' +
							label +
							'</option>';
						$element.append(option);
					} else if ('remove' === action) {
						$element.find('option').each(function (index, option) {
							var $option = $(option);
							var $list_type = $option
								.last()
								.val()
								.split('.')
								.pop()
								.replace('', '');
							var $element_id = element_id + '.value.' + $list_type;
							if ($element_id === $option.val()) {
								$option.remove();
							}
						});
					}
				});
		},

		checkIntegrationFieldAdd: function (e, element_id) {
			var $current_field = $('#' + element_id),
				$evf_integration_Setting = $('.everest-forms-panel-content'),
				field_type = $current_field.attr('data-field-type');

			EverestFormsIntegration.liveFieldChange('add', $current_field);
		},

		checkIntegrationDelete: function (e, element_id) {
			var $current_field = $('#everest-forms-field-' + element_id),
				$evf_integration_Setting = $('.everest-forms-panel-content'),
				field_type = $current_field.attr('data-field-type');

			EverestFormsIntegration.liveFieldChange('remove', $current_field);
		},
		addNewTags: function (el, e) {
			e.preventDefault();
			var $this = $(el),
				connection_id = $this
					.closest('.evf-provider-connection')
					.data('connection_id'),
				source = $this.closest('.evf-provider-connection').data('provider'),
				$output_1 = '<div class="abc"><h4>New Tags to Add</h4>';
			$output_2 =
				'<div class="input-section"><input type="text" class="widefat" name="integrations[' +
				source +
				'][' +
				connection_id +
				'][tag][new]"><p>Enter new taf name(s). Comma-seperated list of tags is accepted.</p></div></div>';
			$output = $output_1 + $output_2;
			$this.before($output);
			$this.hide();
		},

		/**
		 * Adding new custom field.
		 */
		addNewCustomField: function (el, e) {
			e.preventDefault();
			var $this = $(el),
				connection_id = $this
					.closest('.evf-provider-connection')
					.data('connection_id'),
				source = $this.closest('.evf-provider-connection').data('provider'),
				clone = $this
					.closest('ul')
					.clone()
					.attr('data-tax', 'add_custom_field');
			$(clone).find('ul li:not(:first-child)').remove();
			$(clone)
				.find('.custom-field-select')
				.replaceWith(
					'<input class="everest-forms-add_custom_field-map-select" type="text" class="widefat" name="integrations[' +
						source +
						'][' +
						connection_id +
						'][add_custom_field][1]">',
				);
			$(clone).find('select').find('option').removeAttr('selected');
			$(clone)
				.find('.custom_field-value-select')
				.attr('class', 'everest-forms-add_custom_field-map-options');
			$(clone)
				.find('.custom_field-value-select')
				.attr(
					'name',
					'integrations[' +
						source +
						'][' +
						connection_id +
						'][add_custom_field_value][1]',
				);
			$this.closest('ul').after(clone);
			$this.closest('ul').after('<h4>Add new Custom Field</h4>');
			$(clone).find('.add_new_custom_field').remove();
			$this.remove();
		},

		/**
		 * Connect integration account.
		 */
		connect: function (el, e) {
			e.preventDefault();
			var $this = $(el),
				$parent = $this.closest('.integration-connection-detail'),
				apikey = $parent.find('.evf-apikey').val(),
				label = $parent.find('.evf-nickname').val(),
				data = {
					action: 'everest_forms_integration_connect',
					apikey: apikey,
					label: label,
					source: $this.data('source'),
					security: evfp_params.ajax_nonce,
				};

			if ('trello' === $this.data('source')) {
				data.access_token = $parent.find('.evf-trello-access-token').val();
			}

			if ('icontact' === $this.data('source')) {
				data.ic_email = $parent.find('.evf-icontact-email').val();
				data.ic_password = $parent.find('.evf-icontact-password').val();
				data.ic_account_id = $parent.find('.evf-icontact-account-id').val();
				data.ic_folder_id = $parent.find('.evf-icontact-folder-id').val();
			}

			if ('cleverreach' === $this.data('source')) {
				data.client_id = $parent.find('.evf_cr_client_id').val();
				data.client_secret = $parent.find('.evf_cr_client_secret').val();
				data.label = $parent.find('.evf_cr_label').val();
				data.auth_code = $parent.find('.evf_cr_access_code').val();
			}

			if ('activecampaign' === $this.data('source')) {
				data.apiurl = $parent.find('.evf-apiurl').val();
			}

			if ('campaign_monitor' === $this.data('source')) {
				data.client_id = $parent.find('.evf-clientid').val();
			}

			if ('dropbox' === $this.data('source')) {
				data.everest_forms_dropbox_auth_code = $parent
					.find('.everest_forms_dropbox_auth_code')
					.val();
			}

			if ('google_drive' === $this.data('source')) {
				data.everest_forms_google_drive_auth_code = $parent
					.find('.everest_forms_google_drive_auth_code')
					.val();
			}
			if ('google_calendar' === $this.data('source')) {
				data.everest_forms_google_calendar_auth_code = $parent
					.find('.everest_forms_google_calendar_auth_code')
					.val();
			}

			if ('salesforce' === $this.data('source')) {
				data.everest_forms_salesforce_client_id = $parent
					.find('.everest_forms_salesforce_consuemr_key')
					.val();
				data.everest_forms_salesforce_client_secret = $parent
					.find('.everest_forms_salesforce_consumer_secret')
					.val();
				data.everest_forms_salesforce_account_name = $parent
					.find('.everest_forms_salesforce_account_name')
					.val();
				data.everest_forms_salesforce_access_code = $parent
					.find('.everest_forms_salesforce_auth_code')
					.val();
			}

			if ('google_sheets' === $this.data('source')) {
				data.everest_forms_google_sheets_auth_code = $parent
					.find('.everest_forms_google_sheets_auth_code')
					.val();
				data.everest_forms_google_sheets_client_id = $parent
					.find('.everest_forms_google_sheets_client_id')
					.val();
				data.everest_forms_google_sheets_client_secret = $parent
					.find('.everest_forms_google_sheets_client_secret')
					.val();
			}

			if ('google_drive' === $this.data('source')) {
				data.everest_forms_google_drive_auth_code = $parent
					.find('.everest_forms_google_drive_auth_code')
					.val();
				data.everest_forms_google_drive_client_id = $parent
					.find('.everest_forms_google_drive_client_id')
					.val();
				data.everest_forms_google_drive_client_secret = $parent
					.find('.everest_forms_google_drive_client_secret')
					.val();
			}
			if ('google_calendar' === $this.data('source')) {
				data.everest_forms_google_calendar_auth_code = $parent
					.find('.everest_forms_google_calendar_auth_code')
					.val();
				data.everest_forms_google_calendar_client_id = $parent
					.find('.everest_forms_google_calendar_client_id')
					.val();
				data.everest_forms_google_calendar_client_secret = $parent
					.find('.everest_forms_google_calendar_client_secret')
					.val();
			}

			if ('sms_notifications' === $this.data('source')) {
				data.everest_forms_sms_notifications_client_number = $parent
					.find('.everest_forms_sms_notifications_client_number')
					.val();
				data.everest_forms_sms_notifications_client_id = $parent
					.find('.everest_forms_sms_notifications_client_id')
					.val();
				data.everest_forms_sms_notifications_client_auth = $parent
					.find('.everest_forms_sms_notifications_client_auth')
					.val();
			}

			if ('click_send' === $this.data('source')) {
				data.everest_forms_click_send_client_phone_number = $parent
					.find('.everest_forms_click_send_client_phone_number')
					.val();
				data.everest_forms_sms_notifications_client_username = $parent
					.find('.everest_forms_click_send_client_username')
					.val();
				data.everest_forms_sms_notifications_client_api = $parent
					.find('.everest_forms_click_send_client_api')
					.val();
			}

			if ('zoho' === $this.data('source')) {
				data.client_id = $parent.find('.everest_forms_zoho_client_id').val();
				data.client_secret = $parent.find('.everest_forms_zoho_client_secret').val();
				data.account_url = $parent.find('.everest_forms_zoho_account_url').val();
				data.auth_code = $parent.find('.everest_forms_zoho_auth_code').val();
				data.label = $parent.find('.everest_forms_zoho_label').val();
			}

			if ('amocrm' === $this.data('source')) {
				data.secret_key = $parent.find('.everest_forms_amocrm_secret_key').val();
				data.client_id = $parent.find('.everest_forms_amocrm_client_id').val();
				data.access_code = $parent.find('.everest_forms_amocrm_access_code').val();
				data.referer_url = $parent.find('.everest_forms_amocrm_referer_url').val();
				data.label = $parent.find('.everest_forms_amocrm_label').val();
			}

			if ('constant_contact' === $this.data('source')) {
				data.client_id = $parent.find('.everest_forms_constant_contact_client_id').val();
				data.client_secret = $parent
					.find('.everest_forms_constant_contact_client_secret')
					.val();
				data.auth_code = $parent.find('.everest_forms_constant_contact_auth_code').val();
				data.label = $parent.find('.everest_forms_constant_contact_label').val();
			}

			if ('hubspot' === $this.data('source')) {
				data.client_id = $parent.find('.everest_forms_hubspot_client_id').val();
				data.client_secret = $parent.find('.everest_forms_hubspot_client_secret').val();
				data.auth_code = $parent.find('.everest_forms_hubspot_auth_code').val();
				data.label = $parent.find('.everest_forms_hubspot_label').val();
			}

			if ('onedrive' === $this.data('source')) {
				data.everest_forms_onedrive_client_id = $parent
					.find('.everest_forms_onedrive_client_id')
					.val();
				data.everest_forms_onedrive_client_secret = $parent
					.find('.everest_forms_onedrive_client_secret')
					.val();
				data.everest_forms_onedrive_tenant_id = $parent
					.find('.everest_forms_onedrive_tenant_id')
					.val();
				data.everest_forms_onedrive_auth_code = $parent
					.find('.everest_forms_onedrive_auth_code')
					.val();
			}

			if ('amazon_s3' === $this.data('source')) {
				data.everest_forms_amazon_s3_access_key = $parent
					.find('.everest_forms_amazon_s3_access_key')
					.val();
				data.everest_forms_amazon_s3_secret_key = $parent
					.find('.everest_forms_amazon_s3_secret_key')
					.val();
				data.everest_forms_amazon_s3_region = $parent
					.find('.everest_forms_amazon_s3_region')
					.val();
				data.everest_forms_amazon_s3_bucket = $parent
					.find('.everest_forms_amazon_s3_bucket')
					.val();
			}

			if ('onepagecrm' === $this.data('source')) {
				data.apiuserid = $parent.find('.evf-apiuserid').val();
			}

			if ('telegram' === $this.data('source')) {
				data.apichatId = $parent.find('.evf-chatid').val();
			}

			if ('aweber' === $this.data('source')) {
				data.client_id = $parent.find('.everest_forms_aweber_client_id').val();
				data.client_secret = $parent.find('.everest_forms_aweber_client_secret').val();
				data.auth_code = $parent.find('.everest_forms_aweber_auth_code').val();
				data.label = $parent.find('.everest_forms_aweber_label').val();
			}

			EverestFormsIntegration.inputToggle($this, 'disable');

			$.ajax({
				url: evfp_params.ajax_url,
				data: data,
				type: 'POST',
				success: function (response) {
					if (response.success) {
						$parent.find('.evf-connection-form :input').val('');

						if ('trello' === $this.data('source')) {
							$parent.find('.evf-trello-get-url').val('');
						}
						if ('zoho' === $this.data('source')) {
							$parent
								.find('.evf-connection-form')
								.find('.everest_forms_zoho_account_url')
								.val('https://accounts.zoho.com');
							$parent
								.find('.evf-connection-form')
								.find(
									'.everest_forms_zoho_client_secret, .everest_forms_zoho_label, .everest_forms_zoho_auth_code',
								)
								.parent()
								.addClass('everest-forms-hidden');
							$parent
								.find(
									'.everest-forms-integration-connect-account[data-source="zoho"]',
								)
								.parent()
								.addClass('everest-forms-hidden');
							$parent
								.find(
									'.everest-forms-integration-open-window[data-source="zoho"]',
								)
								.show();
						}

						EverestFormsIntegration.inputToggle($this, 'enable');
						$parent
							.find('.evf-connection-list tbody')
							.append(response.data.html);
						$parent.find('.integration-status').addClass('connected');

						if ('constant_contact' === $this.data('source')) {
							$parent
								.find('.evf-connection-form')
								.find(
									'.everest_forms_constant_contact_client_secret, .everest_forms_constant_contact_label, .everest_forms_constant_contact_auth_code',
								)
								.parent()
								.addClass('everest-forms-hidden');
							$parent
								.find(
									'.everest-forms-integration-connect-account[data-source="constant_contact"]',
								)
								.parent()
								.addClass('everest-forms-hidden');
							$parent
								.find(
									'.everest-forms-integration-open-window[data-source="constant_contact"]',
								)
								.show();
						}

						if ('hubspot' === $this.data('source')) {
							$parent
								.find('.evf-connection-form')
								.find(
									'.everest_forms_hubspot_client_secret, .everest_forms_hubspot_label, .everest_forms_hubspot_auth_code',
								)
								.parent()
								.addClass('everest-forms-hidden');
							$parent
								.find(
									'.everest-forms-integration-connect-account[data-source="hubspot"]',
								)
								.parent()
								.addClass('everest-forms-hidden');
							$parent
								.find(
									'.everest-forms-integration-open-window[data-source="hubspot"]',
								)
								.show();
						}

						if ('onedrive' === $this.data('source')) {
							$parent.find('.evf-connection-form').show();
							$parent
								.find('.evf-connection-form')
								.next('.everest-forms-integration-connect-account')
								.hide();
						}

						if ('aweber' === $this.data('source')) {
							$parent
								.find('.evf-connection-form')
								.find(
									'.everest_forms_aweber_client_secret, .everest_forms_aweber_label, .everest_forms_aweber_auth_code',
								)
								.parent()
								.addClass('everest-forms-hidden');
							$parent
								.find(
									'.everest-forms-integration-connect-account[data-source="aweber"]',
								)
								.parent()
								.addClass('everest-forms-hidden');
							$parent
								.find(
									'.everest-forms-integration-open-window[data-source="aweber"]',
								)
								.show();
						}

						if ('amocrm' === $this.data('source')) {
							$parent
								.find('.evf-connection-form')
								.find(
									'.everest_forms_amocrm_secret_key, .everest_forms_amocrm_label, .everest_forms_amocrm_access_code, .everest_forms_amocrm_referer_url',
								)
								.parent()
								.addClass('everest-forms-hidden');
							$parent
								.find(
									'.everest-forms-integration-connect-account[data-source="amocrm"]',
								)
								.parent()
								.addClass('everest-forms-hidden');
							$parent
								.find(
									'.everest-forms-integration-open-window[data-source="amocrm"]',
								)
								.show();
						}

						if (response.data.button) {
							$parent.find('.evf-connection-form').hide();
							$parent
								.find('.evf-account-connect')
								.find('a.everest-forms-btn')
								.show()
								.attr('href', '#')
								.removeClass(
									'everest-forms-btn-primary everest-forms-integration-open-window everest-forms-integration-connect-account',
								)
								.addClass(
									'everest-forms-btn-secondary everest-forms-integration-disconnect-account',
								)
								.text(response.data.button);
						}
						if (response.data.description) {
							$parent
								.find('.evf-account-connect')
								.find('p')
								.text(response.data.description);
						}
					} else {
						EverestFormsIntegration.inputToggle($this, 'enable');
						var msg = evfp_params.provider_auth_error;
						if (response.data.error_msg) {
							msg += '\r\n' + response.data.error_msg; // jshint ignore:line
						}
						$.alert({
							title: false,
							content: msg,
							icon: 'dashicons dashicons-info',
							type: 'orange',
							buttons: {
								confirm: {
									text: evfp_params.i18n_ok,
									btnClass: 'btn-confirm',
									keys: ['enter'],
								},
							},
						});
					}
				},
			});
		},

		/**
		 * Disconnect integration account.
		 */
		disconnect: function (el, e) {
			e.preventDefault();
			var $this = $(el),
				$parent = $this.closest('.integration-connection-detail'),
				apikey = $parent.find('.evf-apikey').val(),
				label = $parent.find('.evf-nickname').val(),
				data = {
					action: 'everest_forms_integration_disconnect',
					key: $this.data('key'),
					source: $this.data('source'),
					security: evfp_params.ajax_nonce,
				};

			$.confirm({
				title: false,
				content: 'Are you sure you want to delete this connection?',
				backgroundDismiss: false,
				closeIcon: false,
				icon: 'dashicons dashicons-info',
				type: 'orange',
				buttons: {
					confirm: {
						text: evfp_params.i18n_ok,
						btnClass: 'btn-confirm',
						keys: ['enter'],
						action: function () {
							$.post(evfp_params.ajax_url, data, function (res) {
								if (res.success) {
									if ('google_sheets' === $this.data('source')) {
										window.location.reload();
									}

									if ('google_drive' === $this.data('source')) {
										window.location.reload();
									}

									if (
										'sms_notifications' === $this.data('source') ||
										'click_send' === $this.data('source')
									) {
										window.location.reload();
									}

									$parent
										.find('.integration-status span')
										.removeClass('connected')
										.addClass('disconnected')
										.text('');

									if ('onedrive' === $this.data('source')) {
										$parent.find('.evf-connection-form').show();
										$parent
											.find('.evf-connection-form')
											.next('.everest-forms-integration-connect-account')
											.hide();
										$parent
											.find('.evf-connection-form')
											.find('label')
											.addClass('hidden');
										$parent
											.find('.evf-connection-form')
											.find(
												'.everest_forms_onedrive_client_id, .everest_forms_onedrive_tenant_id',
											)
											.parent('label')
											.removeClass('hidden');
									}

									if ('amazon_s3' === $this.data('source')) {
										$parent.find('.evf-connection-form').show();
										$parent
											.find('.evf-account-connect')
											.find('a.everest-forms-btn')
											.removeClass('everest-forms-integration-open-window')
											.addClass('everest-forms-integration-connect-account');
									}

									if (res.data.remove) {
										$this.parent().parent().remove();
									} else if (res.data.oauth && res.data.button) {
										$parent
											.find('.evf-account-connect')
											.find('a.everest-forms-btn')
											.attr('href', res.data.oauth)
											.removeClass(
												'everest-forms-btn-secondary everest-forms-integration-disconnect-account',
											)
											.addClass(
												'everest-forms-btn-primary everest-forms-integration-open-window',
											)
											.text(res.data.button);
									}

									if (res.data.description) {
										if (
											'amazon_s3' === $this.data('source') ||
											'onedrive' === $this.data('source')
										) {
											$parent
												.find('.evf-account-connect')
												.find('p')
												.html(res.data.description);
										} else {
											$parent
												.find('.evf-account-connect')
												.find('p')
												.text(res.data.description);
										}
									}
								} else {
									console.log(res);
								}
							}).fail(function (xhr) {
								console.log(xhr.responseText);
							});
						},
					},
					cancel: {
						text: evfp_params.i18n_cancel,
						keys: ['esc'],
					},
				},
			});
		},

		connectionAdd: function (el, e) {
			e.preventDefault();

			var $this = $(el),
				source = $this.data('source'),
				$connections = $this.closest('.everest-forms-panel-sidebar-content'),
				$container = $this.parent(),
				type = $this.data('type'),
				namePrompt = evfp_params.i18n_prompt_connection,
				nameField =
					'<input autofocus="" type="text" id="provider-connection-name" placeholder="' +
					evfp_params.i18n_prompt_placeholder +
					'">',
				nameError = '<p class="error">' + evfp_params.i18n_error_name + '</p>',
				modalContent = namePrompt + nameField + nameError;

			modalContent = modalContent.replace(/%type%/g, type);
			$.confirm({
				title: false,
				content: modalContent,
				icon: 'dashicons dashicons-info',
				type: 'blue',
				backgroundDismiss: false,
				closeIcon: false,
				buttons: {
					confirm: {
						text: evfp_params.i18n_ok,
						btnClass: 'btn-confirm',
						keys: ['enter'],
						action: function () {
							var input = this.$content.find('input#provider-connection-name');
							var error = this.$content.find('.error');
							if (input.val() === '') {
								error.show();
								return false;
							} else {
								var name = input.val();

								// Disable button
								EverestFormsIntegration.inputToggle($this, 'disable');

								// Fire AJAX
								var data = {
									action: 'everest_forms_new_connection_add_' + source,
									source: source,
									name: name,
									id: s.form.data('id'),
									security: evfp_params.ajax_nonce,
								};
								$.ajax({
									url: evfp_params.ajax_url,
									data: data,
									type: 'POST',

									success: function (response) {
										EverestFormsIntegration.inputToggle($this, 'enable');
										$('.everest-form-add-connection-notice').remove();
										$('.everest-forms-google-spread-sheet-message').remove();
										$connections
											.find('.evf-panel-content-section-' + source)
											.find('.evf-provider-connections')
											.append(response.data.html);
										$connections
											.find('.evf-provider-connection')
											.removeClass('active-connection');
										$connections
											.find('.evf-provider-connection')
											.last()
											.addClass('active-connection');
										$this
											.parent()
											.find('.everest-forms-active-connections-list li')
											.removeClass('active-user');
										$this
											.closest('.everest-forms-active-connections.active')
											.children('.everest-forms-active-connections-list')
											.removeClass('empty-list');
										$this
											.parent()
											.find('.everest-forms-active-connections-list')
											.append(
												'<li class="active-user" data-connection-id= "' +
													response.data.connection_id +
													'"><a class="user-nickname" href="#">' +
													name +
													'</a><a href="#"><span class="toggle-remove">Remove</span></a></li>',
											);
										$('.everest-forms-panel-sidebar-section-' + source)
											.siblings('.everest-forms-active-connections.active')
											.children('.everest-forms-active-connections-list')
											.children('.active-user')
											.children('.user-nickname')
											.trigger('click');
										var $connection = $connections.find(
											'.evf-panel-content-section-' +
												source +
												' .evf-provider-connections .evf-provider-connection:last',
										);
										if (
											$connection.find('.evf-provider-accounts option:selected')
										) {
											$connection
												.find('.evf-provider-accounts option:first')
												.prop('selected', true);
											$connection
												.find('.evf-provider-accounts select')
												.trigger('change');
										}
									},
								});
							}
						},
					},
					cancel: {
						text: evfp_params.i18n_cancel,
					},
				},
			});
		},

		/**
		 * Add and authorize Integration account.
		 *
		 */
		accountAdd: function (el, e) {
			e.preventDefault();

			var $this = $(el),
				source = $this.data('source'),
				$connection = $this.closest('.evf-provider-connection'),
				$container = $this.parent(),
				$fields = $container.find(':input'),
				errors = EverestFormsIntegration.requiredCheck($fields, $container);
			// Disable button
			EverestFormsIntegration.inputToggle($this, 'disable');

			// Bail if we have any errors
			if (errors) {
				$this.prop('disabled', false).find('i').remove();
				return false;
			}

			// Fire AJAX
			data = {
				action: 'everest_forms_add_account_form_' + source,
				source: source,
				connection_id: $connection.data('connection_id'),
				data: EverestFormsIntegration.fakeSerialize($fields),
				security: evfp_params.ajax_nonce,
			};

			$.ajax({
				url: evfp_params.ajax_url,
				data: data,
				type: 'POST',
				success: function (response) {
					EverestFormsIntegration.inputToggle($this, 'enable');

					if (response.success) {
						$container.nextAll('.evf-connection-block').remove();
						$container.nextAll('.evf-conditional-block').remove();
						$container.after(response.data.html);
						$container.slideUp();
						$connection.find('.evf-provider-accounts select').trigger('change');
					} else {
						EverestFormsIntegration.errorDisplay(
							response.data.error,
							$container,
						);
					}
				},
			});
		},

		/**
		 * Selecting a provider account
		 *
		 */
		accountSelect: function (el, e) {
			e.preventDefault();

			var $this = $(el),
				$connection = $this.closest('.evf-provider-connection'),
				$container = $this.parent(),
				source = $connection.data('provider');

			// Disable select, show loading
			EverestFormsIntegration.inputToggle($this, 'disable');

			// Remove any blocks that might exist as we prep for new account
			$container.nextAll('.evf-connection-block').remove();
			$container.nextAll('.evf-conditional-block').remove();

			if (!$this.val()) {
				// User selected to option to add new account
				$connection.find('.everest-forms-source-account-add input').val('');
				$('.everest-form-add-connection-notice').remove();
				$connection.find('.everest-forms-source-account-add').slideDown();
				EverestFormsIntegration.inputToggle($this, 'enable');
			} else {
				$connection.find('.everest-forms-source-account-add').slideUp();

				// Fire AJAX
				data = {
					action: 'everest_forms_account_select_' + source,
					source: source,
					connection_id: $connection.data('connection_id'),
					account_id: $this.find(':selected').val(),
					security: evfp_params.ajax_nonce,
				};
				$.ajax({
					url: evfp_params.ajax_url,
					data: data,
					type: 'POST',

					success: function (response) {
						if (response.success) {
							EverestFormsIntegration.inputToggle($this, 'enable');
							$container.after(response.data.html);
							// Process first list found
							$connection
								.find('.evf-provider-lists option:first')
								.prop('selected', true);
							$connection.find('.evf-provider-lists select').trigger('change');
						} else {
							EverestFormsIntegration.inputToggle($this, 'enable');
							$('.evf-alert-danger').remove();
							$('.evf-provider-connection.active-connection').append(
								'<p class="evf-alert-danger evf-alert everest-forms-error-msg">' +
									response.data.error +
									'</p>',
							);
						}
					},
				});
			}
		},
		schemaListSelect: function (el, e) {
			e.preventDefault();
			var $this = $(el),
				$connection = $this.closest('.evf-provider-connection'),
				$container = $this.parent(),
				source = $connection.data('provider');

			if ('airtable' != source) {
				return;
			}
			EverestFormsIntegration.inputToggle($this, 'disable');
			// Remove any blocks that might exist as we prep for new account
			$container.nextAll('.evf-connection-block').remove();
			$container.nextAll('.evf-conditional-block').remove();

			data = {
				action: 'everest_forms_airtable_schema_' + source,
				source: source,
				connection_id: $connection.data('connection_id'),
				account_id: $connection
					.find('.evf-provider-accounts option:selected')
					.val(),
				schema_id: $this.find(':selected').val(),
				list_id: $connection.find('.evf-provider-lists select').val(),
				security: evfp_params.ajax_nonce,
				form_id: s.formID,
			};

			$.ajax({
				url: evfp_params.ajax_url,
				data: data,
				type: 'POST',

				success: function (response) {
					EverestFormsIntegration.inputToggle($this, 'enable');
					$('.everest-forms-google-spread-sheet-message').remove();
					$('.everest-forms-error-msg').remove();
					$container.after(response.data.html);
				},
			});
		},

		/**
		 * Selecting a provider account list.
		 *
		 */
		accountListSelect: function (el, e) {
			e.preventDefault();
			var $this = $(el),
				$connection = $this.closest('.evf-provider-connection'),
				$container = $this.parent(),
				source = $connection.data('provider');

			EverestFormsIntegration.inputToggle($this, 'disable');
			// Remove any blocks that might exist as we prep for new account
			$container.nextAll('.evf-connection-block').remove();
			$container.nextAll('.evf-conditional-block').remove();

			data = {
				action: 'everest_forms_account_list_select_' + source,
				source: source,
				connection_id: $connection.data('connection_id'),
				account_id: $connection
					.find('.evf-provider-accounts option:selected')
					.val(),
				list_id: $this.find(':selected').val(),
				security: evfp_params.ajax_nonce,
				form_id: s.formID,
			};

			$.ajax({
				url: evfp_params.ajax_url,
				data: data,
				type: 'POST',

				success: function (response) {
					EverestFormsIntegration.inputToggle($this, 'enable');
					$('.everest-forms-google-spread-sheet-message').remove();
					$container.after(response.data.html);
					// Process first list found
					$connection.find('#evf-airtable-schema').trigger('change');
					$connection
						.find('.evf-provider-tags .evf-provider-tags-select')
						.select2({ width: '100%' });
				},
			});
		},

		selectActiveAccount: function (el, e) {
			e.preventDefault();

			var $this = $(el),
				connection_id = $this.parent().data('connection-id'),
				active_block = $('.evf-provider-connections').find(
					'[data-connection_id="' + connection_id + '"]',
				),
				lengthOfActiveBlock = $(active_block).length;

			$('.evf-provider-connections')
				.find('.evf-provider-connection')
				.removeClass('active-connection');
			$this.parent().siblings().removeClass('active-user');
			$this.parent().addClass('active-user');

			if (lengthOfActiveBlock) {
				$(active_block).addClass('active-connection');
			}
		},

		removeAccount: function (el, e) {
			e.preventDefault();

			var $this = $(el),
				connection_id = $this.parent().parent().data('connection-id'),
				active_block = $('.evf-provider-connections').find(
					'[data-connection_id="' + connection_id + '"]',
				),
				lengthOfActiveBlock = $(active_block).length,
				closestConnection = $this.closest(
					'.everest-forms-active-connections-list',
				),
				checkConnection;
			$.confirm({
				title: false,
				content: 'Are you sure you want to delete this connection?',
				backgroundDismiss: false,
				closeIcon: false,
				icon: 'dashicons dashicons-info',
				type: 'orange',
				buttons: {
					confirm: {
						text: evfp_params.i18n_ok,
						btnClass: 'btn-confirm',
						keys: ['enter'],
						action: function () {
							if (lengthOfActiveBlock) {
								var toBeRemoved = $this.parent().parent();
								(active_block_after = $('.evf-provider-connections').find(
									'[data-connection_id="' + connection_id + '"]',
								)),
									(lengthOfActiveBlockAfter = $(active_block).length);
								if (toBeRemoved.prev().length) {
									toBeRemoved
										.prev()
										.children('.user-nickname')
										.trigger('click');
								} else {
									toBeRemoved
										.next()
										.children('.user-nickname')
										.trigger('click');
								}
								$(active_block).remove();
								toBeRemoved.remove();
								checkConnection = $('.everest-forms-active-connections.active')
									.children('.everest-forms-active-connections-list')
									.children();
								if (0 === checkConnection.length) {
									closestConnection.addClass('empty-list');
									$('.evf-provider-connections').html(
										'<div class="everest-form-add-connection-notice">Please add a Connection.</div>',
									);
								}
							}
						},
					},
					cancel: {
						text: evfp_params.i18n_cancel,
					},
				},
			});
		},

		/**
		 * Show hide Conditional Logic Fields.
		 *
		 */
		enableConditionLogic: function (el, e) {
			var $this = $(el);
			if ($this.is(':checked')) {
				EverestFormsIntegration.getAllAvailableFields(
					$this.closest('.evf-provider-conditional'),
				);
				$this.parent().siblings('.evf-conditional-container').fadeIn('slow');
				$this
					.parent()
					.siblings('.evf-conditional-container')
					.find('.evf-conditional-field-select')
					.trigger('change');
			} else {
				$this.parent().siblings('.evf-conditional-container').fadeOut('slow');
			}
		},

		inputType: function (el, e) {
			e.preventDefault();
			var $this = $(el),
				selected_option_id = $this.find(':selected').data('field_id'),
				connection_id = $this.parent().parent().data('con_id'),
				source = $this.parent().parent().data('source'),
				$container = $(
					'.everest-forms-panel-sidebar .everest-forms-tab-content',
				)
					.find(
						'.everest-forms-field-option[data-field-id="' +
							selected_option_id +
							'"]',
					)
					.first(),
				options = $container
					.find(
						'.everest-forms-field-option-group-inner .everest-forms-field-option-row-choices ul',
					)
					.find('input.label'),
				conditional =
					'undefined' != typeof evf_integration_data &&
					'undefined' != typeof evf_integration_data[source] &&
					'undefined' != typeof evf_integration_data[source][connection_id]
						? evf_integration_data[source][connection_id]['conditional_logic']
						: '',
				selected_option_type = $this.find(':selected').data('field_type');

			if ('zapier' === source) {
				connection_id = 'zapier_connection';
			}

			switch (selected_option_type) {
				default:
					$this.parent().find('.evf-conditional-input').remove();
					var input_val =
						conditional && 'undefined' !== typeof conditional.input_choice
							? conditional.input_choice
							: '';
					$this
						.parent()
						.append(
							'<input class="evf-conditional-input" type="text" name="integrations[' +
								source +
								'][' +
								connection_id +
								'][conditional_logic][input_choice]" value="' +
								input_val +
								'"></input>',
						);

					break;

				case 'checkbox':
				case 'radio':
				case 'select':
					$this.parent().find('.evf-conditional-input').remove();
					$this
						.parent()
						.append(
							'<select class="evf-conditional-input" name="integrations[' +
								source +
								'][' +
								connection_id +
								'][conditional_logic][multiple_choice]"></select>',
						);
					$(options).each(function (index, el) {
						var value = $(el).val(),
							selected = '';
						if (conditional && value === conditional.multiple_choice) {
							selected = 'selected';
						}
						$this
							.parent()
							.find('.evf-conditional-input')
							.append(
								'<option value="' +
									value +
									'" ' +
									selected +
									'>' +
									value +
									'</option>',
							);
					});

					break;

				case 'country':
					$this.parent().find('.evf-conditional-input').remove();
					$this
						.parent()
						.append(
							'<select class="evf-conditional-input" name="integrations[' +
								source +
								'][' +
								connection_id +
								'][conditional_logic][country_choice]"></select>',
						);
					options = $container
						.find(
							'.everest-forms-field-option-group-advanced .everest-forms-field-option-row-default select',
						)
						.children();

					$(options).each(function (index, el) {
						var value = $(el).val(),
							selected = '';
						if (conditional && value === conditional.country_choice) {
							selected = 'selected';
						}
						$this
							.parent()
							.find('.evf-conditional-input')
							.append(
								'<option value="' +
									value +
									'" ' +
									selected +
									'>' +
									$(el).text() +
									'</option>',
							);
					});

					break;
			}
		},

		getAllAvailableFields: function (el) {
			var connection_id = $(el)
					.children('.evf-conditional-container')
					.data('con_id'),
				source = $(el).children('.evf-conditional-container').data('source');
			$(el)
				.parent()
				.find(
					'.evf-conditional-container .evf-conditional-wrapper .evf-conditional-field-select',
				)
				.empty();
			$('.evf-admin-row .evf-admin-grid .everest-forms-field').each(
				function () {
					var field_type = $(this).data('field-type'),
						conditional =
							'undefined' != typeof evf_integration_data &&
							'undefined' != typeof evf_integration_data[source] &&
							'undefined' != typeof evf_integration_data[source][connection_id]
								? evf_integration_data[source][connection_id][
										'conditional_logic'
									]
								: '',
						field_id = $(this).data('field-id'),
						field_label = $(this).find('.label-title span').first().text(),
						selected = '',
						field_to_be_restricted = [];
					field_to_be_restricted = [
						'html',
						'title',
						'address',
						'image-upload',
						'file-upload',
						'payment-multiple',
						'payment-single',
						'payment-checkbox',
						'payment-total',
						'payment-subtotal',
					];
					if (conditional && field_id === conditional.field_select) {
						selected = 'selected';
					}
					if ($.inArray(field_type, field_to_be_restricted) === -1) {
						$(el)
							.parent()
							.find(
								'.evf-conditional-container .evf-conditional-wrapper .evf-conditional-field-select',
							)
							.append(
								'<option class="evf-conditional-fields" data-field_type="' +
									field_type +
									'" data-field_id="' +
									field_id +
									'" value="' +
									field_id +
									'" ' +
									selected +
									'>' +
									field_label +
									'</option>',
							);
					}
				},
			);
		},

		/**
		 * Toggle input with loading indicator.
		 *
		 */
		inputToggle: function (el, status) {
			var $this = $(el);
			if (status == 'enable') {
				if ($this.is('select')) {
					$this.prop('disabled', false).next('i').remove();
				} else {
					$this.prop('disabled', false).find('i').remove();
				}
			} else if (status == 'disable') {
				if ($this.is('select')) {
					$this.prop('disabled', true).after(s.spinner);
				} else {
					$this.prop('disabled', true).prepend(s.spinner);
				}
			}
		},

		/**
		 * Display error.
		 *
		 */
		errorDisplay: function (msg, location) {
			location.find('.everest-forms-error-msg').remove();
			location
				.find('.new-account-title')
				.after(
					'<p class="evf-alert-danger evf-alert everest-forms-error-msg">' +
						msg +
						'</p>',
				);
		},

		/**
		 * Check for required fields.
		 *
		 */
		requiredCheck: function (fields, location) {
			var error = false;

			// Remove any previous errors
			location.find('.evf-alert-required').remove();

			// Loop through input fields and check for values
			fields.each(function (index, el) {
				if (
					$(el).hasClass('everest-forms-required') &&
					$(el).val().length === 0
				) {
					$(el).addClass('everest-forms-error');
					error = true;
				} else {
					$(el).removeClass('everest-forms-error');
				}
			});
			if (error) {
				location.find('.everest-forms-error-msg').remove();
				location
					.find('.new-account-title')
					.after(
						'<p class="evf-alert-danger evf-alert evf-alert-required">' +
							evfp_params.required_field +
							'</p>',
					);
			}
			return error;
		},

		/**
		 * Psuedo serializing. Fake it until you make it.
		 *
		 */
		fakeSerialize: function (els) {
			var fields = els.clone();

			fields.each(function (index, el) {
				if ($(el).data('name')) {
					$(el).attr('name', $(el).data('name'));
				}
			});
			return fields.serialize();
		},
	};
	EverestFormsIntegration.init();
})(jQuery);

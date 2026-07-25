(function ($) {
	var EverestFormsUR = {
		init: function () {
			EverestFormsUR.bindUIActions();
			$(document).ready(EverestFormsUR.ready);
		},

		ready: function () {
			$('#everest-forms-builder').on(
				'input',
				'.everest-forms-field-option-row-label input',
				function () {
					var $this = $(this),
						value = $this.val(),
						id = $this.parent().data('field-id'),
						$ur_setting_container = $(
							'.evf-content-user-registration-settings',
						);

					$ur_setting_container
						.find(
							'.everest-forms-panel-field-select select option[value="' +
								id +
								'"]',
						)
						.text(value);
				},
			);
		},

		removeInitStyles: function () {
			$('#evf-social-login-init-visibility').remove();
		},

		bindUIActions: function () {
			$(document.body).on(
				'evf_after_field_append',
				EverestFormsUR.checkUserRegistrationFieldAdd,
			);
			$(document.body).on(
				'evf_before_field_deleted',
				EverestFormsUR.checkUserRegistrationFieldDelete,
			);
			$(document).on(
				'change',
				'.evf-content-user-registration-settings .evf-toggle-switch input',
				function (e) {
					EverestFormsUR.toggleContent(e, this);
				},
			);

			$('#everest_forms_social_setting_enable_social_registration').on(
				'change',
				function () {
					EverestFormsUR.removeInitStyles();
					EverestFormsUR.showSocialLoginsContainer($(this));
				},
			);

			$('#everest_forms_social_setting_enable_google_connect').on(
				'change',
				function () {
					EverestFormsUR.removeInitStyles();
					EverestFormsUR.showProviderFields($(this));
				},
			);

			$('#everest_forms_social_setting_enable_facebook_connect').on(
				'change',
				function () {
					EverestFormsUR.removeInitStyles();
					EverestFormsUR.showProviderFields($(this));
				},
			);

			$('#everest_forms_social_setting_enable_linkedin_connect').on(
				'change',
				function () {
					EverestFormsUR.removeInitStyles();
					EverestFormsUR.showProviderFields($(this));
				},
			);
		},

		showSocialLoginsContainer: function ($this) {
			var $accordionWrapper = $('.everest-forms-accordion-wrapper');
			var $multiLogin = $(
				'#everest_forms_social_setting_multi_social_login',
			).closest('.everest-forms-global-settings');
			var $defaultRole = $(
				'#everest_forms_social_setting_default_user_role',
			).closest('.everest-forms-global-settings');

			if ($this.is(':checked')) {
				$accordionWrapper.show();
				$multiLogin.show();
				$defaultRole.show();
			} else {
				$accordionWrapper.hide();
				$multiLogin.hide();
				$defaultRole.hide();
			}
		},

		showProviderFields: function ($this) {
			var $accordionContent = $this.closest(
				'.everest-forms-accordion-content-inner',
			);
			var $fields = $accordionContent
				.find('.everest-forms-global-settings')
				.not(':first');

			if ($this.is(':checked')) {
				$fields.show();
			} else {
				$fields.hide();
			}
		},

		toggleContent: function (e, el) {
			var $this = $(el),
				value = $this.prop('checked');

			if (value === false) {
				$this
					.closest('.evf-content-user-registration-settings')
					.find('.user-registration-disable-message')
					.remove();
				$this
					.closest('.evf-content-section-title')
					.siblings('.evf-content-section-body')
					.addClass('everest-forms-hidden');
				$(
					'<p class="user-registration-disable-message everest-forms-notice everest-forms-notice-info">' +
						evf_user_registration_params.i18n_disabled_message +
						'</p>',
				).insertAfter($this.closest('.evf-content-section-title'));
			} else if (value === true) {
				$this
					.closest('.evf-content-section-title')
					.siblings('.evf-content-section-body')
					.removeClass('everest-forms-hidden');
				$this
					.closest('.evf-content-user-registration-settings')
					.find('.user-registration-disable-message')
					.remove();
			}
		},

		checkUserRegistrationFieldAdd: function (e, element_id) {
			var $current_field = $('#' + element_id),
				$ur_setting_container = $('.evf-content-user-registration-settings'),
				field_type = $current_field.attr('data-field-type');

			if ('email' === field_type) {
				$ur_setting_container
					.find('.user-registration-disable-message')
					.remove();
				$ur_setting_container
					.find('.evf-toggle-section .evf-toggle-switch input')
					.prop('disabled', false);
				$(
					'<p class="user-registration-disable-message everest-forms-notice everest-forms-notice-info">' +
						evf_user_registration_params.i18n_disabled_message +
						'</p>',
				).insertAfter(
					$ur_setting_container
						.find('.evf-toggle-section .evf-toggle-switch input')
						.closest('.evf-content-section-title'),
				);
			}

			EverestFormsUR.liveFieldChange('add', $current_field);
		},

		checkUserRegistrationFieldDelete: function (e, element_id) {
			var $current_field = $('#everest-forms-field-' + element_id),
				$ur_setting_container = $('.evf-content-user-registration-settings'),
				field_type = $current_field.attr('data-field-type'),
				email_field_count = $('.everest-forms-field-wrap').find(
					'.everest-forms-field-email',
				).length;

			if ('email' === field_type && 1 >= email_field_count) {
				$ur_setting_container
					.find('.evf-toggle-section .evf-toggle-switch input')
					.prop('checked', false);
				$ur_setting_container
					.find('.evf-toggle-section .evf-toggle-switch input')
					.prop('disabled', true);
				$ur_setting_container
					.find('.user-registration-disable-message')
					.remove();
				$(
					'<p class="user-registration-disable-message everest-forms-notice everest-forms-notice-info">' +
						evf_user_registration_params.i18n_disabled_message +
						'</p>',
				).insertAfter(
					$ur_setting_container
						.find('.evf-toggle-section .evf-toggle-switch input')
						.closest('.evf-content-section-title'),
				);
				$ur_setting_container
					.find('.evf-content-section-body')
					.addClass('everest-forms-hidden');
			}

			EverestFormsUR.liveFieldChange('remove', $current_field);
		},

		liveFieldChange: function (action, $field) {
			var $ur_setting_container = $('.evf-content-user-registration-settings'),
				field_type = $field.attr('data-field-type'),
				element_id = $field.attr('data-field-id'),
				label = $field.find('label.label-title span.text').text();

			$ur_setting_container
				.find(
					'.everest-forms-panel-field-select select.everest-forms-field-map-select, .everest-forms-panel-field-meta select.everest-forms-field-map-select',
				)
				.each(function (index, element) {
					var $element = $(element),
						field_allowed = $element.attr('data-field-map-allowed').split(' ');

					if ('add' === action) {
						if (
							-1 !== field_allowed.indexOf(field_type) ||
							-1 !== field_allowed.indexOf('all-fields')
						) {
							var option =
								'<option value="' + element_id + '">' + label + '</option>';
							$element.append(option);
						}
					} else if ('remove' === action) {
						$element.find('option').each(function (index, option) {
							var $option = $(option);
							if (element_id === $option.val()) {
								$option.remove();
							}
						});
					}
				});
		},
	};

	EverestFormsUR.init(jQuery);
})(jQuery);

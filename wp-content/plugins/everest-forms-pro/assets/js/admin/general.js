(function ($) {
	var EverestFormsGeneralSettings = {
		init: function () {
			$(document).ready(EverestFormsGeneralSettings.ready);
			EverestFormsGeneralSettings.bindUIActions();
		},

		ready: function () {
			EverestFormsGeneralSettings.toggleScheduledDelete();
			EverestFormsGeneralSettings.toggleAdminApproval();
			EverestFormsGeneralSettings.toggleRazorpayFields();
		},

		isToggleEnabled: function (id) {
			var $input = $('#' + id);
			if (!$input.length) {
				return false;
			}
			return (
				$input.prop('checked') ||
				$input.val() === '1' ||
				$input
					.closest('.evf-toggle-switch')
					.find('input[type="hidden"]')
					.val() === '1'
			);
		},

		toggleScheduledDelete: function () {
			var enabled = EverestFormsGeneralSettings.isToggleEnabled(
				'everest_forms_scheduled_entry_delete',
			);
			$('#everest_forms_scheduled_entry_delete_time')
				.closest('.everest-forms-global-settings')
				[enabled ? 'show' : 'hide']();
		},

		toggleAdminApproval: function () {
			var approvalEnabled = EverestFormsGeneralSettings.isToggleEnabled(
				'everest_forms_admin_approval_entries_enable',
			);
			var pendingDeleteEnabled = EverestFormsGeneralSettings.isToggleEnabled(
				'everest_forms_admin_approval_entries_pending_delete',
			);
			var notificationVal = $(
				'#everest_forms_admin_approval_entries_email_notification:checked',
			).val();

			var approvalFields = [
				'#everest_forms_admin_approval_entries_email_notification',
				'#everest_forms_admin_approval_entries_email_subject',
				'#everest_forms_admin_approval_entries_email_body',
				'#everest_forms_admin_approval_entries_email_as_raw_html',
				'#everest_forms_admin_approval_entries_pending_delete',
			];

			$.each(approvalFields, function (i, fieldId) {
				$(fieldId)
					.closest('.everest-forms-global-settings')
					[approvalEnabled ? 'show' : 'hide']();
			});

			if (approvalEnabled && pendingDeleteEnabled) {
				$('#everest_forms_admin_approval_entries_waiting_days')
					.closest('.everest-forms-global-settings')
					.show();
			} else {
				$('#everest_forms_admin_approval_entries_waiting_days')
					.closest('.everest-forms-global-settings')
					.hide();
			}

			if (approvalEnabled && 'custom_email' === notificationVal) {
				$('#everest_forms_admin_approval_entries_custom_email')
					.closest('.everest-forms-global-settings')
					.show();
			} else {
				$('#everest_forms_admin_approval_entries_custom_email')
					.closest('.everest-forms-global-settings')
					.hide();
			}
		},

		bindUIActions: function () {
			$(document).on(
				'click',
				'#everest_forms_scheduled_entry_delete',
				function () {
					EverestFormsGeneralSettings.toggleScheduledDelete();
				},
			);

			$(document).on(
				'click change',
				'#everest_forms_admin_approval_entries_enable',
				function () {
					EverestFormsGeneralSettings.toggleAdminApproval();
				},
			);

			$(document).on(
				'click change',
				'#everest_forms_admin_approval_entries_pending_delete',
				function () {
					EverestFormsGeneralSettings.toggleAdminApproval();
				},
			);

			$(document).on(
				'click change',
				'#everest_forms_admin_approval_entries_email_notification',
				function () {
					EverestFormsGeneralSettings.toggleAdminApproval();
				},
			);

			$(document.body).on(
				'change',
				'#everest_forms_razorpay_test_mode',
				function () {
					EverestFormsGeneralSettings.toggleRazorpayFields();
				},
			);
		},

		toggleRazorpayFields: function () {
			var isTestMode = $('#everest_forms_razorpay_test_mode').is(':checked');

			var testFields = [
				'#everest_forms_razorpay_test_publishable_key',
				'#everest_forms_razorpay_test_secret_key',
			];

			var liveFields = [
				'#everest_forms_razorpay_live_publishable_key',
				'#everest_forms_razorpay_live_secret_key',
			];

			$.each(testFields, function (i, fieldId) {
				$(fieldId)
					.closest('.everest-forms-global-settings')
					[isTestMode ? 'show' : 'hide']();
			});

			$.each(liveFields, function (i, fieldId) {
				$(fieldId)
					.closest('.everest-forms-global-settings')
					[isTestMode ? 'hide' : 'show']();
			});
		},
	};

	EverestFormsGeneralSettings.init();
})(jQuery);

/**
 * Script to handle admin options for Square.
 *
 * @since 1.7.5
 */

"use strict";

jQuery( function ( $ ) {

    var evf_square_payment = {
		init: function () {
			$(window).on("load", evf_square_payment.ready);
		},
		ready: function () {
			evf_square_payment.bindUiSquarePaymentAction();
			evf_square_payment.toggleSquareFields();

			if (
				"" ===
				$("#everest-forms-add-fields-square-payment").data("field-plan")
			) {
				evf_square_payment.enableDisableSquareField(
					$("#everest-forms-panel-field-square-enable_square"),
				);
			}
		},
		bindUiSquarePaymentAction: function () {
			$(document).on(
				"change",
				"#everest-forms-panel-field-square-enable_square",
				function () {
					evf_square_payment.enableDisableSquareField($(this));
				},
			);
			$(document).on(
				"change",
				"#everest_forms_pro_square_test_mode",
				function () {
					evf_square_payment.toggleSquareFields();
				},
			);
		},
		toggleSquareFields: function () {
			var isTestMode = $("#everest_forms_pro_square_test_mode").is(
				":checked",
			);

			var testFields = [
				"#everest_forms_square_test_app_id",
				"#everest_forms_square_test_access_token",
				"#everest_forms_square_test_location_id",
			];

			var liveFields = [
				"#everest_forms_square_live_app_id",
				"#everest_forms_square_live_access_token",
				"#everest_forms_square_live_location_id",
			];

			if (isTestMode) {
				$.each(testFields, function (index, fieldId) {
					$(fieldId).closest(".everest-forms-global-settings").show();
				});
				$.each(liveFields, function (index, fieldId) {
					$(fieldId).closest(".everest-forms-global-settings").hide();
				});
			} else {
				$.each(testFields, function (index, fieldId) {
					$(fieldId).closest(".everest-forms-global-settings").hide();
				});
				$.each(liveFields, function (index, fieldId) {
					$(fieldId).closest(".everest-forms-global-settings").show();
				});
			}
		},
		enableDisableSquareField: function ($this) {
			if ($this.is(":checked")) {
				$("#everest-forms-add-fields-square-payment").removeClass(
					"enable-square-model",
				);
				$(
					".evf-square-payment-sync-field, .evf-square-gateway-conditional",
				).removeClass( "everest-forms-hidden" ).show();
			} else {
				$("#everest-forms-add-fields-square-payment").addClass(
					"enable-square-model",
				);
				$(
					".evf-square-payment-sync-field, .evf-square-gateway-conditional",
				).hide();
			}
		},
	};

    evf_square_payment.init();

});

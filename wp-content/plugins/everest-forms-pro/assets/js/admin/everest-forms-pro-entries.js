(function ($, data) {
	var everest_forms_entries = {
		formData: [],
		$headerEnd: $(".wp-header-end"),
		$entryForm: $("#everest-forms-edit-entry-form"),
		$submitBtn: $("#everest-forms-edit-entry-update"),

		/**
		 * Initialize.
		 *
		 * 1.3.5
		 */
		init: function () {
			this.saveFormData();

			// Update the editable entry.
			this.$submitBtn.on("click", this.processEntryUpdate);

			// Prompt about changes not saved.
			$(window).on("beforeunload", this.unloadConfirmation);

			// Trigger entry stars and read state.
			$(document.body).on(
				"click",
				"#everest-forms-entries-list .wp-list-table .indicator-star",
				this.markEntryStarred,
			);
			$(document.body).on(
				"click",
				"#everest-forms-entries-list .wp-list-table .indicator-read, #everest-forms-entries-list .wp-list-table .evf-entry-read-unread",
				this.markEntryReadUnread,
			);

			// Show/Hide Columns Adjustment.
			$(document.body).on(
				"click",
				"#evf-manage-columns",
				this.showHideColumns,
			);

			// Print Entry.
			$(document.body).on(
				"click",
				".everest-forms-print-entry",
				function (e) {
					e.preventDefault();
					window.print();
				},
			);

			// Resend Notifications via AJAX.
			$(document.body).on(
				"click",
				".evf-resend-notification",
				this.resendNotification,
			);
		},

		/**
		 * Init jQuery.BlockUI
		 */
		block: function () {
			$("#everest-forms-entry-fields").block({
				message: null,
				overlayCSS: {
					background: "#fff",
					opacity: 0.7,
				},
			});
		},

		/**
		 * Remove jQuery.BlockUI
		 */
		unblock: function () {
			$("#everest-forms-entry-fields").unblock();
		},

		/**
		 * Sync TinyMCE / wp_editor content into textareas before serialize().
		 * Required when save uses AJAX instead of a real form submit.
		 */
		syncTinyMCEEditors: function () {
			if (
				typeof window.tinyMCE !== "undefined" &&
				window.tinyMCE.triggerSave
			) {
				window.tinyMCE.triggerSave();
			}
		},

		/**
		 * Store form data into memory.
		 *
		 * 1.3.5
		 */
		saveFormData: function () {
			this.syncTinyMCEEditors();
			this.formData = this.$entryForm.serialize();
		},

		/**
		 * Process entry update.
		 *
		 * 1.3.5
		 *
		 * @param {Event} event Event interface.
		 */
		processEntryUpdate: function (event) {
			event.preventDefault();

			everest_forms_entries.syncTinyMCEEditors();

			// Check if validation process is complete.
			if (!everest_forms_entries.$entryForm.valid()) {
				return;
			}

			everest_forms_entries.block();
			everest_forms_entries.$submitBtn.prop("disabled", true);
			$.ajax({
				type: "POST",
				url: data.ajax_url,
				data: {
					action: "everest_forms_pro_update_entry",
					form_id: everest_forms_entries.$entryForm.data("formid"),
					data: everest_forms_entries.$entryForm.serialize(),
					security: data.entry_update_nonce,
				},
				success: function (response) {
					if (response && response.data && response.data.message) {
						wp.updates.addAdminNotice({
							id: "edit-entry-notice",
							message: response.data.message,
							className: response.success
								? "notice-success is-dismissible"
								: "notice-error is-dismissible",
						});
					}

					if (
						response &&
						!response.success &&
						response.data &&
						response.data.field_id
					) {
						var form_id = response.data.form_id;

						var $container = $(
							"#evf-" +
								form_id +
								"-field_" +
								response.data.field_id +
								"-container",
						);

						$container.next(".evf-error").remove();

						$(
							'<span class="evf-error">' +
								response.data.message +
								"</span>",
						).insertAfter($container);
					}

					if (response && response.success) {
						everest_forms_entries.saveFormData();
					}
				},
				error: function () {
					wp.updates.addAdminNotice({
						id: "edit-entry-notice",
						message: data.entry_save_failed_msg,
						className: "notice-error is-dismissible",
					});
				},
				complete: function () {
					everest_forms_entries.unblock();
					everest_forms_entries.$submitBtn.prop("disabled", false);
				},
			});
		},

		/**
		 * Prompt about changes not saved.
		 *
		 * 1.3.5
		 *
		 * @param {Event} event Event object.
		 */
		unloadConfirmation: function (event) {
			everest_forms_entries.syncTinyMCEEditors();
			if (
				everest_forms_entries.formData !==
				everest_forms_entries.$entryForm.serialize()
			) {
				event.returnValue = data.unload_confirmation_msg;
				window.event.returnValue = data.unload_confirmation_msg;
				return data.unload_confirmation_msg;
			}
		},

		/**
		 * Mark entry as starred.
		 *
		 * 1.3.5
		 *
		 * @param {Event} event Event object.
		 */
		markEntryStarred: function (event) {
			event.preventDefault();

			var $this = $(this),
				task = "",
				total = Number(
					$("#everest-forms-entries-list .starred-count").text(),
				);

			if ($this.hasClass("star")) {
				total++;
				task = "star";
				$this.attr("title", data.entry_unstar);
			} else {
				total--;
				task = "unstar";
				$this.attr("title", data.entry_star);
			}

			$this.toggleClass("star unstar");

			$("#everest-forms-entries-list .starred-count").text(total);

			$.post(data.ajax_url, {
				task: task,
				nonce: data.nonce,
				entry_id: $this.data("id"),
				action: "everest_forms_entry_star",
			});
		},

		/**
		 * Mark entry as read/unread.
		 *
		 * 1.3.5
		 *
		 * @param {Event} event Event object.
		 */
		markEntryReadUnread: function (event) {
			event.preventDefault();

			var $this = $(this),
				task = "",
				total = Number(
					$("#everest-forms-entries-list .unread-count").text(),
				),
				count = parseInt(
					$("#toplevel_page_everest-forms span.unread-count")
						.html()
						.replace(/[^0-9]+/g, ""),
					10,
				);

			if (count < 1) {
				count = 0;
			}

			if ($this.hasClass("read")) {
				count--;
				total--;
				task = "read";
				$this.attr("title", data.entry_unread);

				if ($this.hasClass("evf-entry-read-unread")) {
					$this.text("Mark as Unread");
				}

				var $statusCell = $this.closest("tr").find(".column-status");
				if ($statusCell.length) {
					$statusCell.html(
						'<span class="evf-badge evf-badge-read">Read</span>',
					);
				}

				var $unreadLink = $('.subsubsub a[href*="status=unread"]');
				if ($unreadLink.length) {
					var unreadCount = $unreadLink
						.find(".count")
						.text()
						.match(/\d+/);
					if (unreadCount) {
						var newUnreadCount = Math.max(
							0,
							parseInt(unreadCount[0]) - 1,
						);
						$unreadLink
							.find(".count")
							.html("(" + newUnreadCount + ")");
					}
				}

				var $readLink = $('.subsubsub a[href*="status=read"]');
				if ($readLink.length) {
					var readCount = $readLink
						.find(".count")
						.text()
						.match(/\d+/);
					if (readCount) {
						var newReadCount = parseInt(readCount[0]) + 1;
						$readLink.find(".count").html("(" + newReadCount + ")");
					}
				}
			} else {
				count++;
				total++;
				task = "unread";
				$this.attr("title", data.entry_read);

				if ($this.hasClass("evf-entry-read-unread")) {
					$this.text("Mark as Read");
				}

				var $statusCell = $this.closest("tr").find(".column-status");
				if ($statusCell.length) {
					$statusCell.html(
						'<span class="evf-badge evf-badge-unread">Unread</span>',
					);
				}

				var $unreadLink = $('.subsubsub a[href*="status=unread"]');
				if ($unreadLink.length) {
					var unreadCount = $unreadLink
						.find(".count")
						.text()
						.match(/\d+/);
					if (unreadCount) {
						var newUnreadCount = parseInt(unreadCount[0]) + 1;
						$unreadLink
							.find(".count")
							.html("(" + newUnreadCount + ")");
					}
				}

				var $readLink = $('.subsubsub a[href*="status=read"]');
				if ($readLink.length) {
					var readCount = $readLink
						.find(".count")
						.text()
						.match(/\d+/);
					if (readCount) {
						var newReadCount = Math.max(
							0,
							parseInt(readCount[0]) - 1,
						);
						$readLink.find(".count").html("(" + newReadCount + ")");
					}
				}
			}

			$this.toggleClass("read unread");
			$this.closest("tr").toggleClass("read unread");

			$("#everest-forms-entries-list .unread-count").text(total);
			$("#toplevel_page_everest-forms span.unread-count").html(
				' <span class="unread-count">' + count + "</span>",
			);

			$.post(data.ajax_url, {
				task: task,
				nonce: data.nonce,
				entry_id: $this.data("id"),
				action: "everest_forms_entry_read",
			});
		},

		/**
		 * Columns Adjustment Dialogue.
		 *
		 * 1.4.4
		 *
		 * @param {Event} event Event object.
		 */
		showHideColumns: function (event) {
			event.preventDefault();
			// Initialization of form data to add security and action in the field.
			var form_data = new FormData();
			var form_id = $(this).attr("data-evf-form_id");
			form_data.append("action", "everest_forms_get_columns");
			form_data.append("security", evf_entries_params.ajax_entries_nonce);
			form_data.append("evf_entries_form_id", form_id);

			// Popup Modal box to show list of Active and Inactive Columns.
			$.confirm({
				title: evf_entries_params.i18n_adjust_entries_columns_title,
				closeIcon: true,
				content: function () {
					var self = this;
					self.setContentAppend(
						'<p class="description">' +
							evf_entries_params.i18n_adjust_entries_columns_description +
							"</p>",
					);
					return $.post({
						url: evf_entries_params.ajax_url,
						dataType: "json",
						cache: false,
						contentType: false,
						processData: false,
						data: form_data,
						complete: function (response) {
							var responseResult = response.responseJSON;
							var result = "";
							result +=
								'<div class="wrapper_entries"><form class="evf_entries_setting_form"><div class="evf-row"><div class="evf-col-6"><label><strong>' +
								evf_entries_params.i18n_entries_active_column_name +
								'</strong></label><div><ul class="evf_entries_column_adjustment_sortableList" id="evf_entries_active_columns">';

							// Loop through all active columns to add to the active column.
							$.each(
								responseResult.active_columns,
								function (index, value) {
									result +=
										'<li id="' +
										index +
										'"><input type="hidden" name="evf_entries_active_columns[' +
										index +
										']" value="' +
										value +
										'"><label>' +
										value +
										"</label</li>";
								},
							);

							result +=
								'</ul></div></div><div class="evf-col-6"><label><strong>' +
								evf_entries_params.i18n_entries_inactive_column_name +
								"</strong></label>" +
								'<div><div><ul class="evf_entries_column_adjustment_sortableList" id="evf_entries_inactive_columns">';

							// Loop through all inactive columns to add to the inactive column.
							$.each(
								responseResult.inactive_columns,
								function (index, value) {
									result +=
										'<li id="' +
										index +
										'"><input type="hidden" name="evf_entries_inactive_columns[' +
										index +
										']" value="' +
										value +
										'"><label>' +
										value +
										"</label></li>";
								},
							);

							result +=
								'</ul><input type="hidden" id="evf_entries_form_id" name="evf_entries_form_id" value="' +
								responseResult.evf_entries_form_id +
								'"></div></div></div></form</div>';
							self.setContentAppend(result);
						},
					});
				},
				useBootstrap: false,
				escapeKey: true,
				theme: "modern",
				boxWidth: "800px",
				buttons: {
					formSubmit: {
						text: evf_entries_params.i18n_entries_save,
						btnClass: "btn-blue evf_entries_save_action",
						action: function () {
							var form_data = new FormData();
							var form_inputs = $(
								"#evf_entries_active_columns li :input",
							).serializeArray();

							form_data.append(
								"action",
								"everest_forms_set_columns",
							);
							form_data.append(
								"security",
								evf_entries_params.ajax_entries_nonce,
							);
							form_data.append(
								"evf_entries_form_id",
								$("#evf_entries_form_id").val(),
							);
							$.each(form_inputs, function (i, field_value) {
								form_data.append(
									field_value.name,
									field_value.value,
								);
							});

							$.post({
								url: evf_entries_params.ajax_url,
								dataType: "json",
								cache: false,
								contentType: false,
								processData: false,
								data: form_data,
								beforeSend: function () {
									var spinner =
										'<i class="evf-loading evf-loading-active"></i>';
									$(".evf_entries_save_action")
										.closest(".evf_entries_save_action")
										.append(spinner);
								},
								complete: function (response) {
									location.reload();
									return true;
								},
							});
						},
					},
					cancel: {
						text: evf_entries_params.i18n_entries_cancel,
					},
				},
				onContentReady: function () {
					// Update inout name for Active/Inactive columns.
					$(
						"#evf_entries_active_columns, #evf_entries_inactive_columns",
					)
						.sortable({
							connectWith:
								".evf_entries_column_adjustment_sortableList",
							update: function (e, ui) {
								if (
									$(e.target).attr("id") ==
									"evf_entries_active_columns"
								) {
									ui.item
										.find("input")
										.attr(
											"name",
											ui.item
												.find("input")
												.attr("name")
												.replace(
													"evf_entries_inactive_columns",
													"evf_entries_active_columns",
												),
										);
								} else {
									ui.item
										.find("input")
										.attr(
											"name",
											ui.item
												.find("input")
												.attr("name")
												.replace(
													"evf_entries_active_columns",
													"evf_entries_inactive_columns",
												),
										);
								}
							},
						})
						.disableSelection();
				},
			});
		},

		/**
		 * Resend entry notifications via AJAX.
		 */
		resendNotification: function (e) {
			e.preventDefault();

			var $btn    = $(this),
				entryId = $btn.data("entry-id"),
				formId  = $btn.data("form-id"),
				nonce   = $btn.data("nonce");

			$btn.prop("disabled", true);

			$.ajax({
				type: "POST",
				url: data.ajax_url,
				data: {
					action:   "evf_resend_notification",
					entry_id: entryId,
					form_id:  formId,
					nonce:    nonce,
				},
				success: function (response) {
					var message = response.data && response.data.message
						? response.data.message
						: (response.success ? data.resend_notification_success : data.resend_notification_error);

					if (typeof window.evfShowToast === "function") {
						window.evfShowToast(message, response.success ? "success" : "error");
					} else {
						wp.updates.addAdminNotice({
							id:        "resend-notification-notice",
							message:   message,
							className: response.success ? "notice-success is-dismissible" : "notice-error is-dismissible",
						});
					}
				},
				error: function () {
					if (typeof window.evfShowToast === "function") {
						window.evfShowToast(data.resend_notification_error, "error");
					} else {
						wp.updates.addAdminNotice({
							id:        "resend-notification-notice",
							message:   data.resend_notification_error,
							className: "notice-error is-dismissible",
						});
					}
				},
				complete: function () {
					$btn.prop("disabled", false);
				},
			});
		},
	};

	everest_forms_entries.init();
})(jQuery, everest_forms_entries);

/**
 * Payment Gateway Selector — builder panel JS.
 * Handles sortable drag-to-reorder and accordion expand/collapse.
 */
(function ($) {
	function ensureCredsPopupFlipStyles() {
		if (document.getElementById('evf-pgw-creds-popup-flip-styles')) {
			return;
		}

		var style = document.createElement('style');
		style.id = 'evf-pgw-creds-popup-flip-styles';
		style.textContent =
			'.evf-pgw-creds-popup--below{top:calc(100% + 4px)!important;bottom:auto!important}' +
			'.evf-pgw-creds-popup--below::after{top:-6px!important;bottom:auto!important;border-right:0!important;border-bottom:0!important;border-left:1px solid #ede9fe!important;border-top:1px solid #ede9fe!important}';
		document.head.appendChild(style);
	}

	/**
	 * Remove any open credentials popup and detach listeners.
	 */
	function removeCredsPopup() {
		$('.evf-pgw-creds-popup').remove();
		$(window).off('.evfPgwCredsPopup');
	}

	/**
	 * Flip popup below if not enough space above.
	 *
	 * @param {jQuery} $item The .evf-pgw-builder-item element.
	 * @param {jQuery} $popup The .evf-pgw-creds-popup element.
	 */
	function positionCredsPopup($item, $popup) {
		if (!$item.length || !$popup.length) {
			return;
		}

		var itemEl = $item.get(0);
		if (!itemEl || !itemEl.getBoundingClientRect) {
			return;
		}

		var itemRect = itemEl.getBoundingClientRect();
		var popupH = $popup.outerHeight() || 0;
		var viewportH =
			window.innerHeight || document.documentElement.clientHeight || 0;
		var gutter = 12;

		var spaceAbove = itemRect.top;
		var spaceBelow = viewportH - itemRect.bottom;
		var shouldBeBelow =
			spaceAbove < popupH + gutter && spaceBelow >= spaceAbove;

		$popup.toggleClass('evf-pgw-creds-popup--below', shouldBeBelow);

		var top = shouldBeBelow ? itemRect.bottom + 4 : itemRect.top - popupH - 4;

		$popup.css({
			position: 'fixed',
			top: top,
			left: itemRect.left,
			right: 'auto',
			bottom: 'auto',
			width: itemRect.width,
		});
	}

	/**
	 * Init jQuery UI sortable on a single gateway list element.
	 * Safe to call multiple times — skips already-initialized lists.
	 *
	 * @param {jQuery} $list The .evf-pgw-builder-list element.
	 */
	/**
	 * Initialize tooltipster on help icons inside payment gateway accordion fields.
	 *
	 * @param {jQuery} [$scope] Optional container; defaults to accordion fields in the builder.
	 */
	function initPgwAccordionTooltips($scope) {
		var $root = $scope && $scope.length ? $scope : $('.evf-pgw-accordion-fields');

		if (typeof $.fn.tooltipster === 'undefined') {
			return;
		}

		$root
			.find('.everest-forms-help-tooltip:not(.tooltipstered)')
			.each(function () {
				$(this).tooltipster({
					contentAsHTML: true,
					position: 'right',
					maxWidth: 300,
					multiple: true,
					interactive: true,
					debug: false,
					trigger: 'custom',
					triggerOpen: {
						mouseenter: true,
						click: true,
						tap: true,
					},
					triggerClose: {
						mouseleave: true,
						click: true,
						tap: true,
					},
				});
			});
	}

	/**
	 * Settings link for credentials popups (opens Payment settings in a new tab).
	 *
	 * @param {string} href  Settings URL.
	 * @param {string} label Link text.
	 * @return {jQuery}
	 */
	function createPgwSettingsLink(href, label) {
		return $('<a class="evf-pgw-creds-popup-link"></a>')
			.attr({
				href: href,
				target: '_blank',
				rel: 'noopener noreferrer',
			})
			.text(label);
	}

	function initPgwSortable($list) {
		if (!$list.length || $list.data('ui-sortable')) {
			return;
		}
		$list.sortable({
			items: '.evf-pgw-builder-item:not(.evf-pgw-builder-item--disabled)',
			handle: '.evf-pgw-builder-drag',
			axis: 'y',
			tolerance: 'pointer',
			cursor: 'grabbing',
			placeholder: 'evf-pgw-builder-placeholder',
			start: function (e, ui) {
				ui.placeholder.height(ui.item.outerHeight());
			},
			stop: function () {
				var fieldId = $(this).attr('id').replace('evf-pgw-sortable-', '');
				$(document.body).trigger('evf_pgw_sort_stop', [fieldId]);
			},
		});
	}

	$(function () {
		ensureCredsPopupFlipStyles();

		// Init on lists already present in the DOM (saved forms on page load).
		$('.evf-pgw-builder-list').each(function () {
			initPgwSortable($(this));
		});

		initPgwAccordionTooltips();

		// Init on the list inserted when a new field is dropped onto the canvas.
		$(document.body).on(
			'evf_field_drop_complete',
			function (e, field_type, field_id) {
				if ('payment-gateway-selector' !== field_type) {
					return;
				}
				initPgwSortable($('#evf-pgw-sortable-' + field_id));
				initPgwAccordionTooltips(
					$('#everest-forms-field-option-' + field_id),
				);
			},
		);

		// Disabled gateway toggle click → show credentials popup.
		$(document).on(
			'click',
			'.evf-pgw-builder-item--disabled .evf-pgw-builder-toggle',
			function (e) {
				var $item = $(this).closest('.evf-pgw-builder-item--disabled');
				var slug = $item.find('.evf-pgw-builder-row').data('gateway');
				var name = $item.find('.evf-pgw-builder-name').text().trim();
				var urls =
					typeof everest_forms_builder !== 'undefined' &&
					everest_forms_builder.pgw_settings_urls
						? everest_forms_builder.pgw_settings_urls
						: {};
				var settingsUrl =
					urls[slug] || 'admin.php?page=evf-settings&tab=payment';
				var msgTpl =
					typeof everest_forms_builder !== 'undefined' &&
					everest_forms_builder.i18n_pgw_fill_creds
						? everest_forms_builder.i18n_pgw_fill_creds
						: 'Configure %s credentials to enable payments.';
				var goToText =
					typeof everest_forms_builder !== 'undefined' &&
					everest_forms_builder.i18n_pgw_go_to_settings
						? everest_forms_builder.i18n_pgw_go_to_settings
						: 'Go to Payment Settings';
				var msg = msgTpl.replace(
					'%s',
					'<strong>' + $('<span>').text(name).html() + '</strong>',
				);

				removeCredsPopup();

				var $popup = $(
					'<div class="evf-pgw-creds-popup" role="tooltip"></div>',
				);
				$popup.append(
					$(
						'<button type="button" class="evf-pgw-creds-popup-close" aria-label="Close"></button>',
					).html('&times;'),
				);
				$popup.append($('<p class="evf-pgw-creds-popup-msg"></p>').html(msg));
				$popup.append(createPgwSettingsLink(settingsUrl, goToText + ' \u2192'));

				$('body').append($popup);

				// Position after layout is ready (run twice to survive late font/layout shifts).
				window.requestAnimationFrame(function () {
					positionCredsPopup($item, $popup);
					window.setTimeout(function () {
						positionCredsPopup($item, $popup);
					}, 0);
				});

				// Reposition on scroll/resize while open.
				$(window).on(
					'resize.evfPgwCredsPopup scroll.evfPgwCredsPopup',
					function () {
						positionCredsPopup($item, $popup);
					},
				);

				e.stopPropagation();
			},
		);

		// Close popup on outside click or close button.
		$(document).on('click', '.evf-pgw-creds-popup-close', function (e) {
			e.stopPropagation();
			removeCredsPopup();
		});

		$(document).on('click', function (e) {
			if (
				!$(e.target).closest('.evf-pgw-builder-toggle, .evf-pgw-creds-popup')
					.length
			) {
				removeCredsPopup();
			}
		});

		// ── PayPal helpers ──────────────────────────────────────────────────────

		// Sync accordion open/close to match warning visibility.
		// Warning shows when: Use Global is off AND email is empty.
		// Open accordion when warning shows; close when warning hides.
		function syncPaypalAccordionToWarning($panel) {
			var $item      = $panel.closest('.evf-pgw-builder-item');
			var $pgwToggle = $item.find('.evf-pgw-builder-toggle input[type="checkbox"]');
			// Only manage accordion when PayPal is enabled.
			if (!$pgwToggle.is(':checked')) {
				return;
			}
			var $chevron   = $item.find('.evf-pgw-builder-chevron:not(.evf-pgw-builder-chevron--hidden)');
			var $globalCb  = $panel.find('#everest-forms-panel-field-paypal-use_global_setting');
			var useGlobal  = $globalCb.length ? $globalCb.is(':checked') : false;
			var $email     = $panel.find('#everest-forms-panel-field-paypal-paypal_email');
			var email      = $email.length ? $email.val().trim() : '';
			var warningShows = !useGlobal && email === '';

			if (warningShows) {
				if (!$item.hasClass('evf-pgw-builder-item--open')) {
					$chevron.trigger('click');
				}
			} else {
				if ($item.hasClass('evf-pgw-builder-item--open')) {
					$item.removeClass('evf-pgw-builder-item--open');
					$chevron.attr('aria-expanded', 'false');
				}
			}
		}

		function syncPaypalEmailWarning($panel, useGlobal) {
			var $warning = $panel.find('.evf-pgw-paypal-email-warning');
			if (!$warning.length) {
				return;
			}
			if (useGlobal) {
				$warning.hide();
			} else {
				var $email = $panel.find(
					'#everest-forms-panel-field-paypal-paypal_email',
				);
				if ($email.val().trim() === '') {
					$warning.css('display', 'flex');
				} else {
					$warning.hide();
				}
			}
		}

		function triggerPgwPreviewFromContext($el) {
			var $container = $el.closest('[id^="everest-forms-field-option-"]');
			if ($container.length) {
				var fieldId = $container
					.attr('id')
					.replace('everest-forms-field-option-', '');
				if (fieldId) {
					// Sync current email to canvas field attr before triggering.
					var $email = $('#everest-forms-panel-field-paypal-paypal_email');
					var emailVal = $email.length ? $email.val().trim() : '';
					$('#everest-forms-field-' + fieldId).attr(
						'data-paypal-current-email',
						emailVal,
					);
					$(document.body).trigger('evf_pgw_refresh_preview', [fieldId]);
				}
			}
		}

		// PayPal accordion: "Use Global PayPal Settings" toggle hides/shows dependent fields.
		function syncPaypalGlobalToggle($checkbox) {
			var $panel = $checkbox.closest('.evf-pgw-paypal-settings');
			if (!$panel.length) {
				return;
			}
			var $fields = $panel.find('.evf-pgw-paypal-conditional-fields');
			var useGlobal = $checkbox.is(':checked');
			if (useGlobal) {
				$fields.hide();
			} else {
				$fields.show();
			}
			// Sync saved email to canvas so preview filter can read it.
			var $email = $panel.find(
				'#everest-forms-panel-field-paypal-paypal_email',
			);
			var emailVal = $email.length ? $email.val().trim() : '';
			$panel.attr('data-paypal-current-email', emailVal);
			syncPaypalEmailWarning($panel, useGlobal);
			triggerPgwPreviewFromContext($checkbox);
		}

		function initPaypalAccordionGlobalToggles() {
			$('.evf-pgw-paypal-settings').each(function () {
				var $cb = $(this)
					.find('#everest-forms-panel-field-paypal-use_global_setting')
					.first();
				if ($cb.length) {
					syncPaypalGlobalToggle($cb);
				}
			});
		}

		// Init on page load (saved forms) + after gateway list changes.
		initPaypalAccordionGlobalToggles();
		$(document.body).on('evf_pgw_sort_stop', function () {
			initPaypalAccordionGlobalToggles();
		});

		// "Use Global PayPal Settings" change: block enabling when no global creds, show popup.
		$(document).on(
			'change',
			'#everest-forms-panel-field-paypal-use_global_setting',
			function () {
				var $cb = $(this);
				var $panel = $cb.closest('.evf-pgw-paypal-settings');
				if (!$panel.length) {
					return;
				}

				var hasGlobalCreds = 1 == $panel.data('paypal-has-global-creds');

				if ($cb.is(':checked') && !hasGlobalCreds) {
					// Revert the check reliably.
					$cb.prop('checked', false);
					$cb.siblings('input[type="hidden"]').val('0');

					var settingsUrl =
						typeof everest_forms_builder !== 'undefined' &&
						everest_forms_builder.pgw_settings_urls
							? everest_forms_builder.pgw_settings_urls.paypal ||
								'admin.php?page=evf-settings&tab=payment'
							: 'admin.php?page=evf-settings&tab=payment';
					var goToText =
						typeof everest_forms_builder !== 'undefined' &&
						everest_forms_builder.i18n_pgw_go_to_settings
							? everest_forms_builder.i18n_pgw_go_to_settings
							: 'Go to Payment Settings';

					removeCredsPopup();

					var $anchor = $cb.closest('.evf-pgw-builder-item');
					var $popup = $(
						'<div class="evf-pgw-creds-popup" role="tooltip"></div>',
					);
					$popup.append(
						$(
							'<button type="button" class="evf-pgw-creds-popup-close" aria-label="Close"></button>',
						).html('&times;'),
					);
					$popup.append(
						$('<p class="evf-pgw-creds-popup-msg"></p>').html(
							'Configure <strong>PayPal</strong> credentials to <strong>Use Global PayPal settings</strong>.',
						),
					);
					$popup.append(createPgwSettingsLink(settingsUrl, goToText + ' →'));

					$('body').append($popup);
					window.requestAnimationFrame(function () {
						positionCredsPopup($anchor, $popup);
						window.setTimeout(function () {
							positionCredsPopup($anchor, $popup);
						}, 0);
					});
					$(window).on(
						'resize.evfPgwCredsPopup scroll.evfPgwCredsPopup',
						function () {
							positionCredsPopup($anchor, $popup);
						},
					);
					return;
				}

				syncPaypalGlobalToggle($cb);
			},
		);

		// React to PayPal email input → update warning + preview.
		$(document).on(
			'input change',
			'#everest-forms-panel-field-paypal-paypal_email',
			function () {
				var $panel = $(this).closest('.evf-pgw-paypal-settings');
				var emailVal = $(this).val().trim();
				// Store on panel and on every PGS canvas field for reliable cross-context reading.
				$panel.attr('data-paypal-current-email', emailVal);
				$(
					'.everest-forms-field[data-field-type="payment-gateway-selector"]',
				).attr('data-paypal-current-email', emailVal);
				var $toggle = $panel.find(
					'#everest-forms-panel-field-paypal-use_global_setting',
				);
				var useGlobal = $toggle.length ? $toggle.is(':checked') : false;
				syncPaypalEmailWarning($panel, useGlobal);
				syncPaypalAccordionToWarning($panel);
				triggerPgwPreviewFromContext($(this));
			},
		);

		// Accordion: click chevron to expand / collapse the panel.
		// Panel visibility is driven by the --open CSS class (display:none !important / display:block !important).
		// Only one panel may be open at a time — opening a new one closes any other open sibling.
		$(document).on('click', '.evf-pgw-builder-chevron', function () {
			if ($(this).hasClass('evf-pgw-builder-chevron--hidden')) {
				return;
			}
			var $item = $(this).closest('.evf-pgw-builder-item');
			var $list = $item.closest('.evf-pgw-builder-list');
			var isOpen = $item.hasClass('evf-pgw-builder-item--open');

			// Close all other open items in the same list.
			$list
				.find('.evf-pgw-builder-item--open')
				.not($item)
				.each(function () {
					$(this).removeClass('evf-pgw-builder-item--open');
					$(this)
						.find('.evf-pgw-builder-chevron')
						.attr('aria-expanded', 'false');
				});

			$item.toggleClass('evf-pgw-builder-item--open', !isOpen);
			$(this).attr('aria-expanded', String(!isOpen));

			if (!isOpen) {
				initPgwAccordionTooltips($item.find('.evf-pgw-accordion-fields'));
			}

			$item.find('.evf-pgw-paypal-settings').each(function () {
				var $panel = $(this);
				var $cb = $panel
					.find('#everest-forms-panel-field-paypal-use_global_setting')
					.first();
				if ($cb.length) {
					syncPaypalGlobalToggle($cb);
				} else {
					// No toggle (use_global hidden) — still sync warning for email state.
					var $email = $panel.find(
						'#everest-forms-panel-field-paypal-paypal_email',
					);
					syncPaypalEmailWarning($panel, false);
					if ($email.length) {
						triggerPgwPreviewFromContext($email);
					}
				}
			});
		});
	});
})(jQuery);

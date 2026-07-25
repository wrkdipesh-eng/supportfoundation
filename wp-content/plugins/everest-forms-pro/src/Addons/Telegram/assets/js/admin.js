/**
 * EverestFormsTelegram JS
 */
(function ($) {
    var EverestFormsTelegram = {
        init: function () {
            EverestFormsTelegram.bindUIActions();
        },

        /**
         * Element bindings
         */
        bindUIActions: function () {
            // Telegram Toggler
            $(document).on(
                "change",
                ".evf-content-telegram-settings .evf-toggle-switch input",
                function (e) {
                    EverestFormsTelegram.toggleContent(e, this);
                }
            );
        },

        /**
         * Toggle Telegram.
         */
        toggleContent: function (e, el) {
            var $this = $(el),
                value = $this.prop('checked');

            if (false === value) {
                $this.val('');
                $this.closest('.evf-content-telegram-settings').find('.telegram-message').remove();
                $this.closest('.evf-content-telegram-title').siblings('.evf-content-telegram-settings-inner').addClass('everest-forms-hidden');
                $('<p class="telegram-disable-message everest-forms-notice everest-forms-notice-info">' + everest_forms_telegram.telegram_disable_message + '</p>').insertAfter($this.closest('.evf-content-telegram-title'));
            } else if (true === value) {
                $this.val('1');
                $this.closest('.evf-content-section-title').siblings('.evf-content-telegram-settings-inner').removeClass('everest-forms-hidden');
                $this.closest('.evf-content-telegram-settings').find('.telegram-disable-message').remove();
            }
        }
    };

    EverestFormsTelegram.init();
})(jQuery);

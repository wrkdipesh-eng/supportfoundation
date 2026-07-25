/**
 * EverestFormsSlack JS
 */
(function ($) {
    var EverestFormsSlack = {
        init: function () {
            EverestFormsSlack.bindUIActions();
        },

        /**
         * Element bindings
         */
        bindUIActions: function () {
            // Slack Toggler
            $(document).on(
                "change",
                ".evf-content-slack-settings .evf-toggle-switch input",
                function (e) {
                    EverestFormsSlack.toggleContent(e, this);
                }
            );
        },

        /**
         * Toggle Slack.
         */
        toggleContent: function (e, el) {
            var $this = $(el),
                value = $this.prop('checked');

            if (false === value) {
                $this.val('');
                $this.closest('.evf-content-slack-settings').find('.slack-message').remove();
                $this.closest('.evf-content-slack-title').siblings('.evf-content-slack-settings-inner').addClass('everest-forms-hidden');
                $('<p class="slack-disable-message everest-forms-notice everest-forms-notice-info">' + everest_forms_slack.slack_disable_message + '</p>').insertAfter($this.closest('.evf-content-slack-title'));
            } else if (true === value) {
                $this.val('1');
                $this.closest('.evf-content-section-title').siblings('.evf-content-slack-settings-inner').removeClass('everest-forms-hidden');
                $this.closest('.evf-content-slack-settings').find('.slack-disable-message').remove();
            }
        }
    };

    EverestFormsSlack.init();
})(jQuery);

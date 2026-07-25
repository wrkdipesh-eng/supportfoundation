/**
 * Script to handle mailpoet.
 *
 * @since 1.0.0
 */
(function ($) {
  const EVFMailPoet = {
    /**
     * Initialization.
     */
    init: function ($) {
      $(document).ready(EVFMailPoet.ready);
    },
    /**
     * Document ready.
     */
    ready: function () {
      EVFMailPoet.bindAdminActions();
    },
    /**
     * Element Binding.
     */
    bindAdminActions: function () {
      $(document.body).on(
        "evf_after_field_append",
        EVFMailPoet.checkMailPoetFieldAdd
      );
      $(document.body).on(
        "evf_before_field_deleted",
        EVFMailPoet.checkMailPoetFieldDelete
      );
      $(document).on(
        "click",
        ".everest-forms-integration-connect-mailpoet",
        EVFMailPoet.connect_mailpoet
      );
      var enabler = $(document).find(
        "#everest-forms-panel-field-settings-enable_mailpoet"
      );
      EVFMailPoet.mailpoetTogger(enabler);
      $(document).on(
        "change",
        "#everest-forms-panel-field-settings-enable_mailpoet",
        function () {
          EVFMailPoet.mailpoetTogger($(this));
        }
      );
    },

    connect_mailpoet: function (e) {
      e.preventDefault();
      var $this = $(this),
        form = $this.closest(".evf-mailpoet-connection-form"),
        mailpoetCheckbox = form.find(".evf_enable_mailpoet"),
        optionValue = false;
      if (mailpoetCheckbox.is(":checked")) {
        optionValue = true;
      }
      var data = {
        action: "everest_forms_mailpoet_connection_action",
        option_value: optionValue,
        security: evf_admin_mailpoet.mailpoet_connection_nonce
      };
      $.ajax({
        url: evf_admin_mailpoet.ajax_url,
        data: data,
        type: "POST",
        beforeSend: function () {
          var spinner = '<i class="evf-loading evf-loading-active"></i>';
          $this.append(spinner);
        },
        success: function (response) {
          $this.find(".evf-loading").remove();
          if (response.success) {
            $(document)
              .find(".integration-status span")
              .text(response.data.status);
          }
        }
      });
    },
    mailpoetTogger: function ($this) {
      var mailpoetSettings = $(document).find(".evf-mailpoet-settings");
      if ($this.is(":checked")) {
        $(mailpoetSettings).show();
      } else {
        $(mailpoetSettings).hide();
      }
    },
    checkMailPoetFieldAdd: function (e, element_id) {
      var $current_field = $("#" + element_id);
      EVFMailPoet.liveFieldChange("add", $current_field);
    },
    checkMailPoetFieldDelete: function (e, element_id) {
      var $current_field = $("#everest-forms-field-" + element_id);
      EVFMailPoet.liveFieldChange("remove", $current_field);
    },
    liveFieldChange: function (action, $field) {
      var $mp_setting_container = $(".evf-mailpoet-builder-wrapper"),
        field_type = $field.attr("data-field-type"),
        element_id = $field.attr("data-field-id"),
        label = $field.find("label.label-title span.text").text();
      elements = $mp_setting_container.find(".everest-forms-field-map-select");

      elements.each(function (index, element) {
        var $element = $(element);
        var field_allowed = $element.data("supported-field-type").split(" ");
        if ("add" === action) {
          if (field_allowed.includes(field_type)) {
            var option =
              '<option value="' + element_id + '">' + label + "</option>";
            $element.append(option);
          }
        } else if ("remove" === action) {
          $element.find("option").each(function (index, option) {
            var $option = $(option);
            if (element_id === $option.val()) {
              $option.remove();
            }
          });
        }
      });
    }
  };
  EVFMailPoet.init($);
})(jQuery);

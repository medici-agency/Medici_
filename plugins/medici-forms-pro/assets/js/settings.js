/**
 * Medici Forms - Settings Page JavaScript
 *
 * @package MediciForms
 * @version 1.0.0
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    // Initialize color pickers
    $(".medici-color-picker").wpColorPicker();

    // Conditional field visibility
    function toggleRecaptchaFields() {
      const isEnabled = $('input[name*="[enable_recaptcha]"]').is(":checked");
      const $fields = $(
        'input[name*="[recaptcha_site_key]"], input[name*="[recaptcha_secret_key]"], select[name*="[recaptcha_version]"], input[name*="[recaptcha_threshold]"]'
      ).closest("tr");

      if (isEnabled) {
        $fields.slideDown();
      } else {
        $fields.slideUp();
      }
    }

    function toggleWebhookFields() {
      const isEnabled = $('input[name*="[webhook_enabled]"]').is(":checked");
      const $fields = $(
        'input[name*="[webhook_url]"], select[name*="[webhook_method]"], textarea[name*="[webhook_headers]"]'
      ).closest("tr");

      if (isEnabled) {
        $fields.slideDown();
      } else {
        $fields.slideUp();
      }
    }

    // Initial state
    toggleRecaptchaFields();
    toggleWebhookFields();

    // On change
    $('input[name*="[enable_recaptcha]"]').on("change", toggleRecaptchaFields);
    $('input[name*="[webhook_enabled]"]').on("change", toggleWebhookFields);
  });
})(jQuery);

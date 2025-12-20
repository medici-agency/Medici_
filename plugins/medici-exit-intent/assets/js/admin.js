/**
 * Medici Exit-Intent Pro - Admin JavaScript
 *
 * @package Jexi
 * @version 1.0.0
 */

(function ($) {
  "use strict";

  const admin = window.jexiAdmin || {};

  /**
   * Initialize
   */
  function init() {
    initColorPickers();
    initToggleDependencies();
    initTriggerOptions();
    initExportImport();
    initPreview();
    loadQuickStats();
    initEmojiPicker();
  }

  /**
   * Initialize color pickers
   */
  function initColorPickers() {
    $(".jexi-color-picker").wpColorPicker();
  }

  /**
   * Initialize toggle dependencies
   */
  function initToggleDependencies() {
    // reCAPTCHA fields
    const recaptchaToggle = $("#jexi_recaptcha_enabled");
    const recaptchaFields = $(".jexi-recaptcha-fields");

    function toggleRecaptcha() {
      recaptchaFields.toggle(recaptchaToggle.is(":checked"));
    }

    recaptchaToggle.on("change", toggleRecaptcha);
    toggleRecaptcha();

    // Provider fields
    const providerSelect = $("#jexi_email_provider");
    const providerFields = $("[class*=jexi-provider-]");

    function toggleProvider() {
      const provider = providerSelect.val();
      providerFields.hide();
      $(".jexi-provider-" + provider).show();
    }

    providerSelect.on("change", toggleProvider);
    toggleProvider();
  }

  /**
   * Initialize trigger type options
   */
  function initTriggerOptions() {
    const triggerSelect = $("#jexi_trigger_type");
    const triggerOptions = $(".jexi-trigger-option");

    function toggleTriggerOptions() {
      const trigger = triggerSelect.val();
      triggerOptions.hide();
      $('[data-trigger="' + trigger + '"]').show();
    }

    triggerSelect.on("change", toggleTriggerOptions);
    toggleTriggerOptions();
  }

  /**
   * Initialize export/import
   */
  function initExportImport() {
    // Export
    $("#jexi-export-settings").on("click", function () {
      $.ajax({
        url: admin.ajaxUrl,
        type: "POST",
        data: {
          action: "jexi_export",
          nonce: admin.nonce,
        },
        success: function (response) {
          if (response.success) {
            downloadJson(response.data, "jexi-settings.json");
          }
        },
      });
    });

    // Import
    $("#jexi-import-settings").on("click", function () {
      const input = $('<input type="file" accept=".json">');

      input.on("change", function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (e) {
          if (confirm(admin.i18n.confirm)) {
            $.ajax({
              url: admin.ajaxUrl,
              type: "POST",
              data: {
                action: "jexi_import",
                nonce: admin.nonce,
                settings: e.target.result,
              },
              success: function (response) {
                if (response.success) {
                  alert(admin.i18n.saved);
                  location.reload();
                } else {
                  alert(admin.i18n.error);
                }
              },
            });
          }
        };
        reader.readAsText(file);
      });

      input.trigger("click");
    });

    // Reset
    $("#jexi-reset-settings").on("click", function () {
      if (confirm(admin.i18n.confirm)) {
        $.ajax({
          url: admin.ajaxUrl,
          type: "POST",
          data: {
            action: "jexi_reset",
            nonce: admin.nonce,
          },
          success: function (response) {
            if (response.success) {
              location.reload();
            }
          },
        });
      }
    });
  }

  /**
   * Download JSON file
   */
  function downloadJson(data, filename) {
    const blob = new Blob([JSON.stringify(data, null, 2)], {
      type: "application/json",
    });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
  }

  /**
   * Initialize preview
   */
  function initPreview() {
    $("#jexi-preview-popup").on("click", function () {
      // Open preview in new window
      window.open(
        admin.siteUrl +
          "?jexi_preview=1&_wpnonce=" +
          encodeURIComponent(admin.nonce),
        "jexi_preview",
        "width=800,height=600"
      );
    });
  }

  /**
   * Load quick stats
   */
  function loadQuickStats() {
    const statsContainer = $("#jexi-quick-stats");
    if (!statsContainer.length) return;

    $.ajax({
      url: admin.restUrl + "stats",
      method: "GET",
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", admin.restNonce);
      },
      success: function (data) {
        const html = `
          <div class="jexi-stats">
            <div class="jexi-stat">
              <div class="jexi-stat__value">${data.totals?.views || 0}</div>
              <div class="jexi-stat__label">Views</div>
            </div>
            <div class="jexi-stat">
              <div class="jexi-stat__value">${data.totals?.submits || 0}</div>
              <div class="jexi-stat__label">Leads</div>
            </div>
            <div class="jexi-stat">
              <div class="jexi-stat__value">${data.conversion_rate || 0}%</div>
              <div class="jexi-stat__label">Conv.</div>
            </div>
          </div>
        `;
        statsContainer.html(html);
      },
      error: function () {
        statsContainer.html("<p>Unable to load stats</p>");
      },
    });
  }

  /**
   * Initialize emoji picker
   */
  function initEmojiPicker() {
    $(".jexi-emoji-preview__item").on("click", function () {
      const emoji = $(this).text();
      $("#jexi_icon").val(emoji);
    });
  }

  /**
   * Debug log export
   */
  $("#jexi-export-log").on("click", function () {
    $.ajax({
      url: admin.ajaxUrl,
      type: "POST",
      data: {
        action: "jexi_export_log",
        nonce: admin.nonce,
      },
      success: function (response) {
        if (response.success) {
          const blob = new Blob([response.data], { type: "text/plain" });
          const url = URL.createObjectURL(blob);
          const a = document.createElement("a");
          a.href = url;
          a.download = "jexi-debug.log";
          a.click();
          URL.revokeObjectURL(url);
        }
      },
    });
  });

  /**
   * Clear debug log
   */
  $("#jexi-clear-log").on("click", function () {
    if (confirm(admin.i18n.confirm)) {
      $.ajax({
        url: admin.ajaxUrl,
        type: "POST",
        data: {
          action: "jexi_clear_log",
          nonce: admin.nonce,
        },
        success: function () {
          location.reload();
        },
      });
    }
  });

  // Initialize on DOM ready
  $(document).ready(init);
})(jQuery);

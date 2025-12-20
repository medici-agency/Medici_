/**
 * Medici Cookie Notice - Consent Logs Admin
 *
 * @package Medici_Cookie_Notice
 * @since 1.3.0
 */

/* global mcnLogsData, jQuery */

(function ($) {
  "use strict";

  const MCNLogs = {
    data: null,

    init() {
      this.data = mcnLogsData || {};
      this.bindEvents();
    },

    bindEvents() {
      // View log details
      $(document).on("click", ".mcn-view-log", (e) => {
        e.preventDefault();
        const logId = $(e.currentTarget).data("id");
        this.viewLogDetails(logId);
      });

      // Close modal
      $(document).on("click", ".mcn-modal-close", () => {
        this.closeModal();
      });

      // Close modal on backdrop click
      $(document).on("click", ".mcn-modal", (e) => {
        if ($(e.target).hasClass("mcn-modal")) {
          this.closeModal();
        }
      });

      // Close modal on ESC
      $(document).on("keydown", (e) => {
        if (e.key === "Escape") {
          this.closeModal();
        }
      });

      // AJAX delete
      $(document).on("click", ".mcn-delete-log-ajax", (e) => {
        e.preventDefault();

        if (!confirm(this.data.i18n.confirm_delete)) return;

        const logId = $(e.currentTarget).data("id");
        this.deleteLog(logId);
      });
    },

    viewLogDetails(logId) {
      const $modal = $("#mcn-log-details-modal");
      const $content = $("#mcn-log-details-content");

      $content.html("<p>" + (this.data.i18n.loading || "Loading...") + "</p>");
      $modal.show();

      $.ajax({
        url: this.data.ajax_url,
        type: "POST",
        data: {
          action: "mcn_get_log_details",
          nonce: this.data.nonce,
          log_id: logId,
        },
        success: (response) => {
          if (response.success) {
            $content.html(response.data.html);
          } else {
            $content.html("<p>Error loading details</p>");
          }
        },
        error: () => {
          $content.html("<p>Error loading details</p>");
        },
      });
    },

    closeModal() {
      $("#mcn-log-details-modal").hide();
    },

    deleteLog(logId) {
      $.ajax({
        url: this.data.ajax_url,
        type: "POST",
        data: {
          action: "mcn_delete_log",
          nonce: this.data.nonce,
          log_id: logId,
        },
        success: (response) => {
          if (response.success) {
            // Remove row from table
            $('tr[data-log-id="' + logId + '"]').fadeOut(() => {
              $(this).remove();
            });
          } else {
            alert(response.data.message || "Error deleting log");
          }
        },
      });
    },
  };

  $(document).ready(() => {
    MCNLogs.init();
  });
})(jQuery);

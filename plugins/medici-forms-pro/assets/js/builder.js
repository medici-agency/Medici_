/**
 * Medici Forms - Form Builder JavaScript
 *
 * @package MediciForms
 * @version 1.0.0
 */

(function ($) {
  "use strict";

  /**
   * Form Builder
   */
  const MediciFormsBuilder = {
    /**
     * Initialize
     */
    init: function () {
      this.bindEvents();
      this.initSortable();
      this.initTabs();
    },

    /**
     * Bind events
     */
    bindEvents: function () {
      // Add field
      $(document).on("click", ".mf-add-field", this.addField.bind(this));

      // Remove field
      $(document).on("click", ".mf-remove-field", this.removeField.bind(this));

      // Toggle field options
      $(document).on("click", ".mf-toggle-field", this.toggleField);

      // Duplicate field
      $(document).on(
        "click",
        ".mf-duplicate-field",
        this.duplicateField.bind(this)
      );

      // Update field label in header
      $(document).on("input", ".mf-field-label-input", this.updateFieldLabel);

      // Copy shortcode
      $(document).on("click", "#mf-copy-shortcode", this.copyShortcode);
      $(document).on("click", ".mf-shortcode-copy", this.copyShortcode);
    },

    /**
     * Initialize sortable
     */
    initSortable: function () {
      $(".mf-fields-list").sortable({
        handle: ".mf-field-drag",
        placeholder: "ui-sortable-placeholder",
        opacity: 0.7,
        update: function () {
          MediciFormsBuilder.updateFieldIndexes();
        },
      });
    },

    /**
     * Initialize tabs
     */
    initTabs: function () {
      $(".mf-tab-button").on("click", function () {
        const tab = $(this).data("tab");

        // Update buttons
        $(".mf-tab-button").removeClass("active");
        $(this).addClass("active");

        // Update content
        $(".mf-tab-content").removeClass("active");
        $('.mf-tab-content[data-tab="' + tab + '"]').addClass("active");
      });
    },

    /**
     * Add new field
     */
    addField: function (e) {
      e.preventDefault();

      const $button = $(e.currentTarget);
      const type = $button.data("type");
      const $list = $(".mf-fields-list");
      const index = $list.find(".mf-field-item").length;

      // Remove empty state
      $list.find(".mf-empty-state").remove();

      // AJAX request to get field HTML
      $.ajax({
        url: mediciFormsBuilder.ajaxUrl,
        type: "POST",
        data: {
          action: "medici_forms_add_field",
          nonce: mediciFormsBuilder.nonce,
          type: type,
          index: index,
        },
        success: function (response) {
          if (response.success) {
            const $field = $(response.data.html);
            $list.append($field);

            // Open field options
            $field.find(".mf-field-content").slideDown();
            $field.find(".mf-toggle-field .dashicons").removeClass("dashicons-arrow-down-alt2").addClass("dashicons-arrow-up-alt2");

            // Focus on label input
            $field.find(".mf-field-label-input").focus();
          }
        },
      });
    },

    /**
     * Remove field
     */
    removeField: function (e) {
      e.preventDefault();
      e.stopPropagation();

      if (!confirm(mediciFormsBuilder.i18n.confirmDelete)) {
        return;
      }

      const $field = $(e.currentTarget).closest(".mf-field-item");
      $field.slideUp(200, function () {
        $(this).remove();
        MediciFormsBuilder.updateFieldIndexes();

        // Show empty state if no fields
        if ($(".mf-fields-list .mf-field-item").length === 0) {
          $(".mf-fields-list").html(
            '<div class="mf-empty-state">' +
              '<span class="dashicons dashicons-plus-alt2"></span>' +
              "<p>Додайте поля з панелі зліва</p>" +
              "</div>"
          );
        }
      });
    },

    /**
     * Toggle field options
     */
    toggleField: function (e) {
      e.preventDefault();
      e.stopPropagation();

      const $field = $(this).closest(".mf-field-item");
      const $content = $field.find(".mf-field-content");
      const $icon = $(this).find(".dashicons");

      $content.slideToggle(200);
      $icon.toggleClass("dashicons-arrow-down-alt2 dashicons-arrow-up-alt2");
    },

    /**
     * Duplicate field
     */
    duplicateField: function (e) {
      e.preventDefault();
      e.stopPropagation();

      const $field = $(e.currentTarget).closest(".mf-field-item");
      const $clone = $field.clone();
      const $list = $(".mf-fields-list");

      // Generate new ID
      const newId =
        "field_" + Math.random().toString(16).substring(2, 10);
      $clone.find('input[name*="[id]"]').val(newId);

      // Update label
      const currentLabel = $clone.find(".mf-field-label-input").val();
      $clone.find(".mf-field-label-input").val(currentLabel + " (копія)");
      $clone.find(".mf-field-label").text(currentLabel + " (копія)");

      // Close options
      $clone.find(".mf-field-content").hide();
      $clone
        .find(".mf-toggle-field .dashicons")
        .removeClass("dashicons-arrow-up-alt2")
        .addClass("dashicons-arrow-down-alt2");

      // Insert after current field
      $field.after($clone);

      // Update indexes
      this.updateFieldIndexes();
    },

    /**
     * Update field label in header
     */
    updateFieldLabel: function () {
      const label = $(this).val();
      const $field = $(this).closest(".mf-field-item");
      $field.find(".mf-field-label").text(label);
    },

    /**
     * Update field indexes after reordering
     */
    updateFieldIndexes: function () {
      $(".mf-fields-list .mf-field-item").each(function (index) {
        $(this).attr("data-index", index);

        // Update all input names
        $(this)
          .find("input, select, textarea")
          .each(function () {
            const name = $(this).attr("name");
            if (name) {
              const newName = name.replace(
                /medici_form_fields\[\d+\]/,
                "medici_form_fields[" + index + "]"
              );
              $(this).attr("name", newName);
            }
          });
      });
    },

    /**
     * Copy shortcode to clipboard
     */
    copyShortcode: function (e) {
      e.preventDefault();

      const $code = $("#mf-shortcode-code");
      const text = $code.length ? $code.text() : $(this).text();

      navigator.clipboard.writeText(text).then(function () {
        const $btn = $("#mf-copy-shortcode");
        const originalText = $btn.text();
        $btn.text("Скопійовано!");
        setTimeout(function () {
          $btn.text(originalText);
        }, 2000);
      });
    },
  };

  // Initialize on DOM ready
  $(document).ready(function () {
    MediciFormsBuilder.init();
  });
})(jQuery);

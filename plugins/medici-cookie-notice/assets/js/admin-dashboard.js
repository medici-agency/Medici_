/**
 * Medici Cookie Notice - Admin Dashboard
 *
 * @package Medici_Cookie_Notice
 * @since 1.3.0
 */

/* global Chart, mcnDashboardData, jQuery */

(function ($) {
  "use strict";

  const MCNDashboard = {
    charts: {},
    data: null,

    init() {
      this.data = mcnDashboardData || {};

      if (this.data.error) {
        console.error("MCN Dashboard: Data error");
        return;
      }

      this.initCharts();
      this.bindEvents();
    },

    initCharts() {
      this.initConsentActivityChart();
      this.initCategoryChart();
      this.initGeoChart();
      this.initStatusChart();
    },

    initConsentActivityChart() {
      const ctx = document.getElementById("mcn-consent-activity-chart");
      if (!ctx) return;

      const dailyStats = this.data.daily_stats || [];
      const labels = dailyStats.map((d) => d.date_recorded);
      const i18n = this.data.i18n || {};

      this.charts.activity = new Chart(ctx, {
        type: "line",
        data: {
          labels: labels,
          datasets: [
            {
              label: i18n.accepted || "Accepted",
              data: dailyStats.map((d) => parseInt(d.accepted_all) || 0),
              borderColor: "#10b981",
              backgroundColor: "rgba(16, 185, 129, 0.1)",
              fill: true,
              tension: 0.3,
            },
            {
              label: i18n.rejected || "Rejected",
              data: dailyStats.map((d) => parseInt(d.rejected_all) || 0),
              borderColor: "#ef4444",
              backgroundColor: "rgba(239, 68, 68, 0.1)",
              fill: true,
              tension: 0.3,
            },
            {
              label: i18n.customized || "Customized",
              data: dailyStats.map((d) => parseInt(d.customized) || 0),
              borderColor: "#6b7280",
              backgroundColor: "rgba(107, 114, 128, 0.1)",
              fill: true,
              tension: 0.3,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: "top",
            },
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0,
              },
            },
          },
        },
      });
    },

    initCategoryChart() {
      const ctx = document.getElementById("mcn-category-chart");
      if (!ctx) return;

      const catRates = this.data.cat_rates || {};
      const i18n = this.data.i18n || {};

      this.charts.category = new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: [
            i18n.analytics || "Analytics",
            i18n.marketing || "Marketing",
            i18n.preferences || "Preferences",
          ],
          datasets: [
            {
              data: [
                catRates.analytics || 0,
                catRates.marketing || 0,
                catRates.preferences || 0,
              ],
              backgroundColor: ["#10b981", "#f59e0b", "#3b82f6"],
              borderWidth: 0,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false,
            },
          },
        },
      });
    },

    initGeoChart() {
      const ctx = document.getElementById("mcn-geo-chart");
      if (!ctx) return;

      const geoStats = this.data.geo_stats || {};
      const i18n = this.data.i18n || {};

      this.charts.geo = new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: [
            i18n.eu || "EU (GDPR)",
            i18n.us || "USA",
            i18n.other || "Other",
          ],
          datasets: [
            {
              data: [
                geoStats.eu?.count || 0,
                geoStats.us?.count || 0,
                geoStats.other?.count || 0,
              ],
              backgroundColor: ["#3b82f6", "#ef4444", "#6b7280"],
              borderWidth: 0,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false,
            },
          },
        },
      });
    },

    initStatusChart() {
      const ctx = document.getElementById("mcn-status-chart");
      if (!ctx) return;

      const summary = this.data.summary || {};
      const i18n = this.data.i18n || {};

      this.charts.status = new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: [
            i18n.accepted || "Accepted",
            i18n.rejected || "Rejected",
            i18n.customized || "Customized",
          ],
          datasets: [
            {
              data: [
                summary.accepted_all || 0,
                summary.rejected_all || 0,
                summary.customized || 0,
              ],
              backgroundColor: ["#10b981", "#ef4444", "#6b7280"],
              borderWidth: 0,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: "bottom",
            },
          },
        },
      });
    },

    bindEvents() {
      // Period change
      $("#mcn-period").on("change", (e) => {
        this.updatePeriod(parseInt(e.target.value));
      });

      // Export CSV
      $("#mcn-export-csv").on("click", (e) => {
        e.preventDefault();
        this.exportCSV();
      });

      // Clear cache
      $("#mcn-clear-cache").on("click", (e) => {
        e.preventDefault();
        this.clearCache();
      });

      // Test banner
      $("#mcn-test-banner").on("click", (e) => {
        e.preventDefault();
        // Clear consent cookie and redirect to homepage
        document.cookie =
          "mcn_consent=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        window.open("/", "_blank");
      });
    },

    updatePeriod(days) {
      const $btn = $("#mcn-period");
      $btn.prop("disabled", true);

      $.ajax({
        url: this.data.ajax_url,
        type: "POST",
        data: {
          action: "mcn_get_dashboard_data",
          nonce: this.data.nonce,
          days: days,
        },
        success: (response) => {
          if (response.success) {
            this.data.daily_stats = response.data.daily_stats;
            this.data.summary = response.data.summary;
            this.data.rates = response.data.rates;
            this.data.cat_rates = response.data.cat_rates;
            this.data.geo_stats = response.data.geo_stats;
            this.data.comparison = response.data.comparison;

            this.updateCharts();
            this.updateStats();
          }
        },
        complete: () => {
          $btn.prop("disabled", false);
        },
      });
    },

    updateCharts() {
      // Destroy and reinitialize charts
      Object.values(this.charts).forEach((chart) => {
        if (chart) chart.destroy();
      });

      this.initCharts();
    },

    updateStats() {
      const summary = this.data.summary || {};
      const rates = this.data.rates || {};

      $("#stat-total").text(summary.total_visitors?.toLocaleString() || 0);
      $("#stat-accepted").text(summary.accepted_all?.toLocaleString() || 0);
      $("#stat-rejected").text(summary.rejected_all?.toLocaleString() || 0);
      $("#stat-custom").text(summary.customized?.toLocaleString() || 0);
      $("#stat-overall").text((rates.overall_rate || 0) + "%");
    },

    exportCSV() {
      const days = parseInt($("#mcn-period").val()) || 30;

      $.ajax({
        url: this.data.ajax_url,
        type: "POST",
        data: {
          action: "mcn_export_analytics",
          nonce: this.data.nonce,
          days: days,
        },
        success: (response) => {
          if (response.success) {
            this.downloadCSV(response.data.csv, response.data.filename);
          } else {
            alert("Export failed");
          }
        },
      });
    },

    downloadCSV(csv, filename) {
      const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
      const link = document.createElement("a");
      link.href = URL.createObjectURL(blob);
      link.download = filename;
      link.click();
    },

    clearCache() {
      if (!confirm("Clear all plugin caches?")) return;

      $.ajax({
        url: this.data.ajax_url,
        type: "POST",
        data: {
          action: "mcn_clear_cache",
          nonce: this.data.nonce,
        },
        success: (response) => {
          if (response.success) {
            alert("Cache cleared successfully");
          }
        },
      });
    },
  };

  $(document).ready(() => {
    MCNDashboard.init();
  });
})(jQuery);

/**
 * Medici Exit-Intent Pro - Popup Handler
 *
 * @package Jexi
 * @version 1.0.0
 */

(function () {
  "use strict";

  // Configuration (injected by PHP)
  const config = window.jexiConfig || {};

  // State
  let isOpen = false;
  let hasTriggered = false;

  // DOM elements
  let popup = null;
  let form = null;
  let message = null;
  let submitBtn = null;

  /**
   * Initialize popup
   */
  function init() {
    popup = document.getElementById("jexi-popup");
    if (!popup) {
      log("Popup element not found");
      return;
    }

    form = document.getElementById("jexi-form");
    message = popup.querySelector(".jexi-popup__message");
    submitBtn = popup.querySelector(".jexi-popup__submit");

    // Check if already seen (cookie)
    if (getCookie("jexi_seen")) {
      log("Popup already seen (cookie)");
      return;
    }

    // Check screen width
    if (window.innerWidth < (config.minScreenWidth || 1024)) {
      log("Screen too narrow");
      return;
    }

    // Set up trigger
    setupTrigger();

    // Set up event listeners
    setupEventListeners();

    log("Popup initialized", config);
  }

  /**
   * Setup trigger based on configuration
   */
  function setupTrigger() {
    const triggerType = config.triggerType || "exit";
    const delay = (config.delaySeconds || 2) * 1000;

    setTimeout(() => {
      switch (triggerType) {
        case "exit":
          setupExitIntent();
          break;
        case "scroll":
          setupScrollTrigger();
          break;
        case "time":
          setupTimeTrigger();
          break;
        case "inactive":
          setupInactivityTrigger();
          break;
      }
    }, delay);
  }

  /**
   * Setup exit intent detection (using bioEp)
   */
  function setupExitIntent() {
    if (typeof bioEp === "undefined") {
      log("bioEp library not found");
      return;
    }

    bioEp.init({
      showOnDelay: false,
      delay: 0,
      showOnTimeout: false,
      cookieExp: 0, // We manage cookies ourselves
      onPopup: function () {
        if (!hasTriggered) {
          showPopup();
        }
      },
    });

    log("Exit intent trigger ready");
  }

  /**
   * Setup scroll percentage trigger
   */
  function setupScrollTrigger() {
    const targetPercent = config.scrollPercent || 50;

    function checkScroll() {
      if (hasTriggered) return;

      const scrollTop = window.scrollY;
      const docHeight =
        document.documentElement.scrollHeight - window.innerHeight;
      const scrollPercent = (scrollTop / docHeight) * 100;

      if (scrollPercent >= targetPercent) {
        showPopup();
        window.removeEventListener("scroll", checkScroll);
      }
    }

    window.addEventListener("scroll", checkScroll, { passive: true });
    log("Scroll trigger ready at " + targetPercent + "%");
  }

  /**
   * Setup time on page trigger
   */
  function setupTimeTrigger() {
    const seconds = config.timeSeconds || 30;

    setTimeout(() => {
      if (!hasTriggered) {
        showPopup();
      }
    }, seconds * 1000);

    log("Time trigger ready at " + seconds + "s");
  }

  /**
   * Setup inactivity trigger
   */
  function setupInactivityTrigger() {
    const seconds = config.inactiveSeconds || 15;
    let timer = null;

    function resetTimer() {
      clearTimeout(timer);
      timer = setTimeout(() => {
        if (!hasTriggered) {
          showPopup();
        }
      }, seconds * 1000);
    }

    ["mousemove", "keydown", "scroll", "touchstart"].forEach((event) => {
      document.addEventListener(event, resetTimer, { passive: true });
    });

    resetTimer();
    log("Inactivity trigger ready at " + seconds + "s");
  }

  /**
   * Setup event listeners
   */
  function setupEventListeners() {
    // Close buttons
    popup.querySelectorAll("[data-jexi-close]").forEach((el) => {
      el.addEventListener("click", closePopup);
    });

    // Escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && isOpen) {
        closePopup();
      }
    });

    // Form submission
    if (form) {
      form.addEventListener("submit", handleSubmit);
    }

    // Manual triggers
    document.querySelectorAll("[data-jexi-trigger]").forEach((el) => {
      el.addEventListener("click", showPopup);
    });
  }

  /**
   * Show popup
   */
  function showPopup() {
    if (isOpen || hasTriggered) return;

    popup.hidden = false;
    popup.offsetHeight; // Force reflow

    requestAnimationFrame(() => {
      popup.classList.add("is-visible");
    });

    isOpen = true;
    hasTriggered = true;

    // Focus first input
    const firstInput = form?.querySelector("input:not([type=hidden])");
    if (firstInput) {
      setTimeout(() => firstInput.focus(), 300);
    }

    // Track view
    trackEvent("view");

    // Set cookie
    setCookie("jexi_seen", "1", config.cookieDays || 30);

    log("Popup shown");
  }

  /**
   * Close popup
   */
  function closePopup() {
    if (!isOpen) return;

    popup.classList.remove("is-visible");

    setTimeout(() => {
      popup.hidden = true;
    }, 300);

    isOpen = false;

    // Track close
    trackEvent("close");

    log("Popup closed");
  }

  /**
   * Handle form submission
   */
  async function handleSubmit(e) {
    e.preventDefault();

    if (!form) return;

    // Get form data
    const formData = new FormData(form);
    formData.append("action", "jexi_submit");
    formData.append("page_url", window.location.href);
    formData.append("referrer", document.referrer);

    // Honeypot check
    if (config.honeypot && formData.get("website")) {
      log("Honeypot triggered");
      return;
    }

    // UI feedback
    submitBtn.disabled = true;
    submitBtn.classList.add("is-loading");
    showMessage("", "");

    try {
      const response = await fetch(config.ajaxUrl, {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      });

      const result = await response.json();

      if (result.success) {
        showMessage(result.data.message || config.i18n.success, "success");
        form.reset();
        trackEvent("submit", { email: formData.get("email") });

        // Close after success
        setTimeout(closePopup, 2000);
      } else {
        showMessage(result.data?.message || config.i18n.error, "error");
      }
    } catch (error) {
      log("Submit error:", error);
      showMessage(config.i18n.error, "error");
    } finally {
      submitBtn.disabled = false;
      submitBtn.classList.remove("is-loading");
    }
  }

  /**
   * Show message
   */
  function showMessage(text, type) {
    if (!message) return;

    message.textContent = text;
    message.className = "jexi-popup__message";

    if (type) {
      message.classList.add("is-" + type);
    }
  }

  /**
   * Track analytics event
   */
  function trackEvent(event, data = {}) {
    if (!config.ajaxUrl) return;

    const formData = new FormData();
    formData.append("action", "jexi_track");
    formData.append("nonce", config.nonce);
    formData.append("event", event);
    formData.append("data", JSON.stringify(data));

    // Use sendBeacon for reliability
    if (navigator.sendBeacon) {
      navigator.sendBeacon(config.ajaxUrl, formData);
    } else {
      fetch(config.ajaxUrl, {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        keepalive: true,
      });
    }
  }

  /**
   * Cookie utilities
   */
  function setCookie(name, value, days) {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie =
      name +
      "=" +
      encodeURIComponent(value) +
      "; expires=" +
      expires +
      "; path=/; SameSite=Lax";
  }

  function getCookie(name) {
    const value = "; " + document.cookie;
    const parts = value.split("; " + name + "=");
    if (parts.length === 2) {
      return decodeURIComponent(parts.pop().split(";").shift());
    }
    return null;
  }

  /**
   * Debug logging
   */
  function log(...args) {
    if (config.debug) {
      console.log("[Jexi]", ...args);
    }
  }

  // Initialize when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }

  // Expose API for external use
  window.Jexi = {
    show: showPopup,
    hide: closePopup,
    isOpen: () => isOpen,
  };
})();

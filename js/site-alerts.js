(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * Attaches the alerts JavaScript
   *
   * @type {{attach: Drupal.behaviors.siteAlerts.attach}}
   */
  Drupal.behaviors.siteAlerts = {
    attach: function (context, settings) {
      const alertElement = once('siteAlerts', document.getElementById('site-alerts'), context);
      if (alertElement.length > 0) {
        alertElement.forEach(renderAlert);
      }
    }
  };

  /**
   * Handles rendering the actual alert
   *
   * @param {HTMLElement} value
   * @param {int} index
   */
  function renderAlert(value, index) {
    if (drupalSettings.site_alerts !== 'undefined') {
      const settingsObj = drupalSettings.site_alerts;

      // Get settings
      const message     = settingsObj.message,
            background  = settingsObj.style.background,
            text        = settingsObj.style.text,
            scheduled   = settingsObj.scheduled,
            pages       = settingsObj.pages;

      let schedulePasses = true;
      let pagesPasses = true;

      // Check scheduling first
      if (Object.keys(scheduled).length !== 0) {
        let current = moment.now();

        // Start date
        if (scheduled.start !== '') {
          if (!(current >= moment.unix(scheduled.start))) {
            schedulePasses = false;
          }
        }

        // End date
        if (scheduled.end !== '') {
          if (!(current <= (moment.unix(scheduled.end)))) {
            schedulePasses = false;
          }
        }
      }

      // Check pages second
      if (Object.keys(pages).length !== 0) {
        let returnValue = false;
        let pagesToEvaluate = Array.isArray(pages.pages) ? pages.pages : pages.pages.split(',');
        let currentPath = getCurrentPathAndQuery();
        pagesToEvaluate.forEach(page => {

          // Wildcard pages
          if (page.indexOf("*") !== -1) {
            if (currentPath.indexOf(page.toLowerCase().replace("*", "")) !== -1) {
              returnValue = true;
            }
          } else {
            // Normal pages
            if (currentPath === page.toLowerCase()) {
              returnValue = true;
            }
          }
        });

        if (pages.show_hide === 'hide' && !returnValue) {
          pagesPasses = true;
        }
        if (pages.show_hide === 'hide' && returnValue) {
          pagesPasses = false;
        }
        if (pages.show_hide === 'show' && returnValue) {
          pagesPasses = true;
        }
        if (pages.show_hide === 'show' && !returnValue) {
          pagesPasses = false;
        }
      }

      if (schedulePasses && pagesPasses) {
        value.innerHTML = `
          <div class="sitewide-alert alert" role="alert" style="background-color: ${background}; color: ${text};">
            ${message}
          </div>
        `;
      }
    }
  }

  /**
   * Returns the current path including any query params
   *
   * @returns {string}
   */
  function getCurrentPathAndQuery() {
    if (window.location.search !== '') {
      return window.location.pathname.toLowerCase() + window.location.search;
    }
    return window.location.pathname.toLowerCase();
  }

})(Drupal, drupalSettings);
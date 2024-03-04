(function (Drupal, drupalSettings, once) {
  'use strict';

  const _alertSessionKey = 'siteAlertsSessionKey';

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
        dismissible = settingsObj.dismissable,
        sticky      = settingsObj.sticky,
        background  = settingsObj.style.background,
        text        = settingsObj.style.text,
        scheduled   = settingsObj.scheduled,
        pages       = settingsObj.pages;

      let dismissMarkup = '';
      let sessionToken = '';
      if (dismissible) {
        dismissMarkup = `
          <style>
            .sitewide-alert button::before,
            .sitewide-alert button::after {
              background: ${text};
            }
          </style>
          <button type="button" id="alertDismiss" aria-label="Close notification bar"></button>
        `;

        // Create message token based on message without special characters
        let messageToken = message;
        if (messageToken !== "") {
          messageToken = messageToken.replace(/[^a-z0-9]/gi, "");
        }

        // Build session token to force visibility of the message if
        // any of the configuration changes.
        sessionToken = [
          messageToken,
          dismissible,
          sticky,
          background,
          text,
          scheduled,
          pages
        ].join('|');
        sessionToken = btoa(sessionToken);
      }

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
        if (dismissible) {
          if (!checkSessionToken(sessionToken)) {
            return;
          }
        }

        value.innerHTML = `
<style>
.sitewide-alert {
    background: ${background};
    color: ${text};
}
.sitewide-alert h1,
.sitewide-alert h2,
.sitewide-alert h3,
.sitewide-alert h4,
.sitewide-alert h5,
.sitewide-alert h6 {
    color: ${text};
}
.sitewide-alert a {
    color: ${text};
    text-decoration: underline;
}
</style>
          <div class="sitewide-alert" role="alert">
            ${message}
            ${dismissMarkup}
          </div>
          <div class="sitewide-alert-shim"></div>
        `;

        if (sticky) {
          adjustSiteHeader();
          window.addEventListener('resize', Drupal.debounce(adjustSiteHeader, 250, false));
        }

        if (dismissible) {
          document.getElementById('alertDismiss').addEventListener('click', function() {
            document.getElementById('site-alerts').remove();
            window.localStorage.setItem(_alertSessionKey, sessionToken);
            window.removeEventListener('resize', adjustSiteHeader);
            adjustSiteHeader();
          });
        }
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

  /**
   * Checks if a session token is set and compares the value
   *
   * @param {string} sessionKey
   * @returns {boolean}
   */
  function checkSessionToken(sessionKey) {
    let activeSession = window.localStorage.getItem(_alertSessionKey);
    return !(activeSession && (activeSession === sessionKey));
  }

  /**
   * Adjusts the position of the site header to account for the alert
   */
  function adjustSiteHeader() {
    const header = document.querySelector('header');
    const headerChildren = document.createTreeWalker(
      header,
      NodeFilter.SHOW_ELEMENT,
      { acceptNode: function(node) { return NodeFilter.FILTER_ACCEPT; } }
    );
    const headerChildrenNode = headerChildren.nextNode();
    const shim = document.querySelector('.sitewide-alert-shim');
    const alertElement = document.querySelector('.sitewide-alert');

    if (header) {
      const headerStyles = getComputedStyle(header);
      const headerChildrenStyles = getComputedStyle(headerChildrenNode);
      if (headerStyles.position === 'fixed') {
        if (alertElement) {
          header.style.top = alertElement.offsetHeight + 'px';
          shim.style.height = alertElement.offsetHeight + 'px';
        } else {
          header.style.top = '0px';
        }
      } else if(headerChildrenStyles.position === 'fixed') {
        if (alertElement) {
          headerChildrenNode.style.top = alertElement.offsetHeight + 'px';
          shim.style.height = alertElement.offsetHeight + 'px';
        } else {
          headerChildrenNode.style.top = '0px';
        }
      }
    }
  }

})(Drupal, drupalSettings, once);

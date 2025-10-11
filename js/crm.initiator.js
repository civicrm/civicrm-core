// https://civicrm.org/licensing
(function($, CRM, _, undefined) {

  CRM.initiators = CRM.initiators || {};
  CRM.initiators.UNKNOWN = function(resp) {
    CRM.alert(ts('Unrecognized OAuth flow: ' + resp.flow));
  }
  ;
  CRM.initiators.query = function(resp) {
    window.location = resp.url;
  };
  CRM.initiators.web_message = function(resp) {
    // const popup = window.open(resp.url, 'child', 'width=600,height=600');
    const popup = CRM.openWindow(resp.url, 'child', {
      width: '50%', minWidth: 400,
      height: '50%', minHeight: 400,
      center: true
    });
    const callback = (e) => {
      if (e.origin === resp.response_origin) {
        window.removeEventListener("message", callback);
        popup.close();
        window.location = resp.continue_url + (resp.continue_url.indexOf('?') < 0 ? '?' : '&') + $.param(e.data);
      }
    };
    window.addEventListener("message", callback);
  };

  CRM.initiator = function initiator(params) {
    const initiatorUrl = params.url;
    const unsavedChanges = CRM.utils.initialValueChanged($('form[data-warn-changes=true]:visible'));

    if (unsavedChanges) {
      CRM.alert(
        '<p>' + ts('Please save changes first.') + '</p>',
        ts('Unsaved Changes')
      );
    }
    else {
      $.get(initiatorUrl).then(function onInitiate(resp) {
          const handler = CRM.initiators[resp.response_mode] || CRM.initiators.UNKNOWN;
          handler(resp);
        }
      );
    }

    return false;
  };

  /**
   * Open a new popup window with smart parameter handling.
   *
   * FIXME: This probably belongs somewhere like Common.js
   *
   * WARNING: Avoid "params" from external data. Serializer is weakly unguarded.
   *
   * @param {string} [url] - The URL to open.
   * @param {string} [target]
   * @param {object} [params] - Optional window parameters.
   * @param {string|number} [params.width] - Width (px or percentage string like "80%").
   * @param {string|number} [params.height] - Height (px or percentage string like "80%").
   * @param {string|number} [params.minWidth] - Minimum width (px or percentage).
   * @param {string|number} [params.maxWidth] - Maximum width (px or percentage).
   * @param {string|number} [params.minHeight] - Minimum height (px or percentage).
   * @param {string|number} [params.maxHeight] - Maximum height (px or percentage).
   * @param {boolean} [params.center=false] - Whether to center the popup.
   * @param {...any} [params.*] - Any other window features (e.g., scrollbars, resizable).
   *
   * @returns {Window|null} A reference to the new window.
   */
  CRM.openWindow = function openWindow(url, target, params = {}) {
    const parseSize = (val, relativeTo) => {
      if (val == null) return undefined;
      if (typeof val === "string" && val.endsWith("%")) {
        return Math.round((parseFloat(val) / 100) * relativeTo);
      }
      return parseInt(val);
    };

    const constrainedSize = (requested, min, max, relativeTo) => {
      requested = parseSize(requested || min || max, relativeTo);
      min = parseSize(min, relativeTo);
      max = parseSize(max, relativeTo);
      if (min) requested = Math.max(requested, min);
      if (max) requested = Math.min(requested, max);
      return requested;
    };

    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    params.width = constrainedSize(params.width, params.minWidth, params.maxWidth, viewportWidth);
    params.height = constrainedSize(params.height, params.minHeight, params.maxHeight, viewportHeight);

    if (params.center && params.width) {
      params.left = Math.max(0, (viewportWidth - params.width) / 2);
    }
    if (params.center && params.height) {
      params.top = Math.max(0, (viewportHeight - params.height) / 2);
    }

    // Convert params object to a serialized string
    const excludes = ['minWidth', 'maxWidth', 'minHeight', 'maxHeight', 'center'];
    const features = Object.entries(params)
      .map(([key, value]) => {
        if (value === undefined || value === null || _.includes(excludes, key)) return null;
        return `${key}=${typeof value === "boolean" ? (value ? "yes" : "no") : value}`;
      })
      .filter(Boolean)
      .join(",");

    return window.open(url, target, features);
  };

}(CRM.$, CRM, CRM._));

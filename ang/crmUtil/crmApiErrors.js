// https://civicrm.org/licensing
(function (angular, $) {
  "use strict";

  /**
   * Turns Civi\Api4\Generic\Result error data into CRM.alert() calls.
   *
   * Shared by the `af` module (afForm.component.js) and `crmSearchTasks`
   * (crmSearchBatchRunner.component.js) via `crmUtil`, so the mapping from
   * api4 errors to an alert only exists in one place.
   */
  angular.module('crmUtil').factory('crmApiErrors', function() {
    const ts = CRM.ts();

    // CRM.alert() only recognizes a handful of `type` values; Psr\Log\LogLevel has more.
    const LEVEL_TO_ALERT_TYPE = {
      emergency: 'error',
      alert: 'error',
      critical: 'error',
      error: 'error',
      warning: 'warning',
      notice: 'info',
      info: 'info',
      debug: 'info'
    };

    // Most-to-least severe.
    const LEVEL_ORDER = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    /**
     * Normalize one error into `{message, title, level, code}`, regardless of which of the
     * currently-in-use shapes it arrived in:
     *  - Civi\Api4\Generic\Error::jsonSerialize() -> {message, code, title, level, id}
     *  - CRM_Api4_Page_AJAX exception shape -> {error_message, error_code}
     *
     * @param {Object} error
     * @return {{message: string, title: string, level: string, code: (string|number)}}
     */
    function normalizeError(error) {
      if (!error) {
        return {message: ts('Unknown error'), title: '', level: 'error', code: 0};
      }
      return {
        message: error.message || error.error_message || ts('Unknown error'),
        title: error.title || '',
        level: error.level || 'error',
        code: error.code || error.error_code || 0
      };
    }

    /**
     * @param {Array|Object} errors An array of raw errors, or a single raw error.
     * @return {Array}
     */
    function normalizeErrors(errors) {
      const list = angular.isArray(errors) ? errors : [errors];
      return list.map(normalizeError);
    }

    /**
     * @param {string} level A Psr\Log\LogLevel string.
     * @return {string} A type recognized by CRM.alert().
     */
    function mapLevelToAlertType(level) {
      return LEVEL_TO_ALERT_TYPE[level] || 'error';
    }

    /**
     * Combine normalized errors into one alert-ready {message, title}, most severe first.
     *
     * @param {Array} errors Normalized errors (see normalizeErrors()).
     * @return {{message: string, title: string}}
     */
    function buildAlertMessage(errors) {
      const sorted = errors.slice().sort((a, b) => {
        const indexA = LEVEL_ORDER.indexOf(a.level);
        const indexB = LEVEL_ORDER.indexOf(b.level);
        return (indexA === -1 ? LEVEL_ORDER.length : indexA) - (indexB === -1 ? LEVEL_ORDER.length : indexB);
      });
      const message = sorted.map((error) => error.message).join('<br>');
      const title = sorted.length > 1 ? ts('Please resolve these issues') : ((sorted[0] && sorted[0].title) || ts('Validation errors'));
      return {message: message, title: title};
    }

    /**
     * Normalize raw error(s) from an api4 response and show them via CRM.alert().
     *
     * @param {Array|Object} errors Raw error(s), e.g. `response.errors`, or a single caught error.
     * @param {string} [maxErrorLevel] e.g. `response.max_error_level`. Defaults to the most severe
     *   level found amongst `errors`.
     */
    function showErrors(errors, maxErrorLevel) {
      const normalized = normalizeErrors(errors);
      const built = buildAlertMessage(normalized);
      const level = maxErrorLevel || (normalized[0] && normalized[0].level) || 'error';
      CRM.alert(built.message, built.title, mapLevelToAlertType(level));
    }

    return {
      normalizeError: normalizeError,
      normalizeErrors: normalizeErrors,
      mapLevelToAlertType: mapLevelToAlertType,
      buildAlertMessage: buildAlertMessage,
      showErrors: showErrors
    };
  });

})(angular, CRM.$);

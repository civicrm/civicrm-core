(function (CRM, angular) {

  CRM.afform = CRM.afform || {};

  /**
   * Parse expressions used for conditionals/tokens
   *
   * Expressions should be encoded using JSON
   *
   * They may:
   * - be boolean arrays @see afForm.evaluateBooleanArray
   * - use tokens for referencing form data values @see afForm.replaceTokens
   * - @todo use Symfony Expression Language for bespoke logic
   *
   * Historically they may have been stored as angularjs-ish
   * expressions
   *
   * @param {string|Array} expression
   * @returns string|Array
   */
  CRM.afform.parseExpression = (expression) => {
    if (typeof expression === 'string') {
      if (expression.startsWith('(') && expression.endsWith(')')) {
        expression = expression.slice(1, -1);
      }
      expression = expression.replace(/&quot;/g, '"').trim();
      expression = JSON.parse(expression);
    }

    expression = replaceAngularTerms(expression);
    return expression;
  };

  /**
   * Backwards compatibility for angularjs-ish expressions
   *
   * @see FormDataModel::replaceAngularTerms
   */
  const replaceAngularTerms = (expression) => {
    if (Array.isArray(expression)) {
      return expression.map((item) => replaceAngularTerms(item));
    }
    if (typeof expression === 'string') {
      if (expression.includes('[fields]') || expression.includes('[joins]')) {
        expression = expression
          .replaceAll('[', '.')
          .replaceAll(']', '')
          .replace('.joins.', '.')
          .replace('.fields.', '.');
        expression = `[${expression}]`;
      }
      // see above only arrays and strings
      try {
        const parsed = JSON.parse(expression);
        if (Array.isArray(parsed) || typeof parsed === 'string' || typeof parsed === 'boolean') {
          expression = parsed;
          // recurse into arrays/strings
          expression = replaceAngularTerms(expression);
        }
        else {
          // numeric string or similar, leave as is
        }
      }
      catch (e) {
        // not JSON, no worries
      }
    }
    return expression;
  };

})(CRM, angular);

(function (CRM, angular) {

  CRM.afform = CRM.afform || {};

  /**
   * Parse expressions used for conditionals/tokens
   *
   * Expressions should be strings or JSON-encoded arrays
   *
   * They may:
   * - be boolean arrays @see afForm.evaluateBooleanArray
   * - use tokens for referencing form data values @see afForm.replaceTokens
   * - @todo use Symfony Expression Language for bespoke logic
   *
   * Historically they may have been stored as angularjs-ish
   * expressions and/or wrapped in ()
   *
   * @param string expression
   * @returns {Array|string}
   */
  CRM.afform.parseExpression = (expression) => {
    if (expression.startsWith('(') && expression.endsWith(')')) {
      expression = expression.slice(1, -1);
    }
    expression = expression.replace(/&quot;/g, '"').trim();
    try {
      const parsed = JSON.parse(expression);
      if (Array.isArray(parsed)) {
        expression = parsed;
      }
    }
    catch (e) {
      // not JSON array, leave as is
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
      // extract double-encoded JSON sub-arrays/strings/booleans - but avoid overextracting
      // e.g. '123' =x=> 123
      try {
        const parsed = JSON.parse(expression);
        if (Array.isArray(parsed) || typeof parsed === 'string' || typeof parsed === 'boolean') {
          expression = parsed;
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

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
   * Replace angular terms from stored expressions
   *
   * Data references previously stored in quoteless javascript array notation
   * are converted to our token format:
   * - Individual1[0][fields][first_name] => [Individual1.0.first_name]
   * - Individual1[1][joins][Email][0][location_type_id] => [Individual1.0.Email.0.location_type_id:name]
   *
   * Quotations are removed from string literals:
   * - "John" => John
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
      // check for double encoded arrays
      try {
        expression = JSON.parse(expression);
        expression = replaceAngularTerms(expression);
      }
      catch (e) {
        // no worries leave as is
      }
    }
    return expression;
  };

})(CRM, angular);

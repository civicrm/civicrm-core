<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Query;

/**
 * Numeric sql expression
 */
class SqlEquation extends SqlExpression {

  /**
   * @var array
   */
  protected $args = [];

  /**
   * @var string[]
   */
  public static $arithmeticOperators = [
    '+',
    '-',
    '*',
    '/',
  ];

  /**
   * @var string[]
   */
  public static $comparisonOperators = [
    '<=',
    '>=',
    '<',
    '>',
    '=',
    '!=',
    '<=>',
    'IS NOT',
    'IS',
    'BETWEEN',
    'AND',
  ];

  protected function initialize() {
    $arg = trim(substr($this->expr, strpos($this->expr, '(') + 1, -1));
    $permitted = ['SqlField', 'SqlString', 'SqlNumber', 'SqlNull'];
    $operators = array_merge(self::$arithmeticOperators, self::$comparisonOperators);
    while (strlen($arg)) {
      $this->args = array_merge($this->args, $this->captureExpressions($arg, $permitted, 1));
      $op = $this->captureKeyword($operators, $arg);
      if ($op) {
        $this->args[] = $op;
      }
    }
  }

  /**
   * Render the expression for insertion into the sql query
   *
   * @param array $fieldList
   * @return string
   */
  public function render(array $fieldList): string {
    $output = [];
    foreach ($this->args as $arg) {
      // Just an operator
      if (is_string($arg)) {
        $output[] = $arg;
      }
      // Surround fields with COALESCE to handle null values
      elseif (is_a($arg, SqlField::class)) {
        $output[] = 'COALESCE(' . $arg->render($fieldList) . ', 0)';
      }
      else {
        $output[] = $arg->render($fieldList);
      }
    }
    return '(' . implode(' ', $output) . ')';
  }

  /**
   * Returns the alias to use for SELECT AS.
   *
   * @return string
   */
  public function getAlias(): string {
    return $this->alias ?? \CRM_Utils_String::munge(trim($this->expr, ' ()'), '_', 256);
  }

  /**
   * Change $dataType according to operator used in equation
   *
   * @see \Civi\Api4\Utils\FormattingUtil::formatOutputValues
   * @param string $value
   * @param string $dataType
   * @return string
   */
  public function formatOutputValue($value, &$dataType) {
    foreach (self::$comparisonOperators as $op) {
      if (strpos($this->expr, " $op ")) {
        $dataType = 'Boolean';
      }
    }
    foreach (self::$arithmeticOperators as $op) {
      if (strpos($this->expr, " $op ")) {
        $dataType = 'Float';
      }
    }
    return $value;
  }

  public static function getTitle(): string {
    return ts('Equation');
  }

}

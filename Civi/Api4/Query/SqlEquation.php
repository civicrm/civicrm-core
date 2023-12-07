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
    '%',
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
   * Get the arguments and operators passed to this sql expression.
   *
   * For each item in the returned array, if it's an array, it's a value; if it's a string, it's an operator.
   *
   * @return array
   */
  public function getArgs(): array {
    return $this->args;
  }

  /**
   * Render the expression for insertion into the sql query
   *
   * @param \Civi\Api4\Query\Api4Query $query
   * @param bool $includeAlias
   * @return string
   */
  public function render(Api4Query $query, bool $includeAlias = FALSE): string {
    $output = [];
    foreach ($this->args as $i => $arg) {
      // Just an operator
      if ($this->getOperatorType($arg)) {
        $output[] = $arg;
      }
      // Surround fields with COALESCE to prevent null values when using arithmetic operators
      elseif (is_a($arg, SqlField::class) && (
          $this->getOperatorType($this->args[$i - 1] ?? NULL) === 'arithmetic' ||
          $this->getOperatorType($this->args[$i + 1] ?? NULL) === 'arithmetic'
        )
      ) {
        $output[] = 'COALESCE(' . $arg->render($query) . ', 0)';
      }
      else {
        $output[] = $arg->render($query);
      }
    }
    return '(' . implode(' ', $output) . ')' . ($includeAlias ? " AS `{$this->getAlias()}`" : '');
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
   * Check if an item is an operator and if so what category it belongs to
   *
   * @param $item
   * @return string|null
   */
  protected function getOperatorType($item): ?string {
    if (!is_string($item)) {
      return NULL;
    }
    if (in_array($item, self::$arithmeticOperators, TRUE)) {
      return 'arithmetic';
    }
    if (in_array($item, self::$comparisonOperators, TRUE)) {
      return 'comparison';
    }
    return NULL;
  }

  /**
   * Change $dataType according to operator used in equation
   *
   * @see \Civi\Api4\Utils\FormattingUtil::formatOutputValues
   * @param string|null $dataType
   * @param array $values
   */
  public function formatOutputValue(?string &$dataType, array &$values) {
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
  }

  public static function getTitle(): string {
    return ts('Equation');
  }

}

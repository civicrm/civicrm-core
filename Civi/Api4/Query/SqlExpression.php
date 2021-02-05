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
 * Base class for SqlColumn, SqlString, SqlBool, and SqlFunction classes.
 *
 * These are used to validate and format sql expressions in Api4 select queries.
 *
 * @package Civi\Api4\Query
 */
abstract class SqlExpression {

  /**
   * @var array
   */
  protected $fields = [];

  /**
   * The SELECT alias (if null it will be calculated by getAlias)
   * @var string|null
   */
  protected $alias;

  /**
   * The raw expression, minus the alias.
   * @var string
   */
  public $expr = '';

  /**
   * Whether or not pseudoconstant suffixes should be evaluated during output.
   *
   * @var bool
   * @see \Civi\Api4\Utils\FormattingUtil::formatOutputValues
   */
  public $supportsExpansion = FALSE;

  /**
   * SqlFunction constructor.
   * @param string $expr
   * @param string|null $alias
   */
  public function __construct(string $expr, $alias = NULL) {
    $this->expr = $expr;
    $this->alias = $alias;
    $this->initialize();
  }

  abstract protected function initialize();

  /**
   * Converts a string to a SqlExpression object.
   *
   * E.g. the expression "SUM(foo)" would return a SqlFunctionSUM object.
   *
   * @param string $expression
   * @param bool $parseAlias
   * @param array $mustBe
   * @param array $cantBe
   * @return SqlExpression
   * @throws \API_Exception
   */
  public static function convert(string $expression, $parseAlias = FALSE, $mustBe = [], $cantBe = ['SqlWild']) {
    $as = $parseAlias ? strrpos($expression, ' AS ') : FALSE;
    $expr = $as ? substr($expression, 0, $as) : $expression;
    $alias = $as ? \CRM_Utils_String::munge(substr($expression, $as + 4), '_', 256) : NULL;
    $bracketPos = strpos($expr, '(');
    $firstChar = substr($expr, 0, 1);
    $lastChar = substr($expr, -1);
    // If there are brackets but not the first character, we have a function
    if ($bracketPos && $lastChar === ')') {
      $fnName = substr($expr, 0, $bracketPos);
      if ($fnName !== strtoupper($fnName)) {
        throw new \API_Exception('Sql function must be uppercase.');
      }
      $className = 'SqlFunction' . $fnName;
    }
    // String expression
    elseif ($firstChar === $lastChar && in_array($firstChar, ['"', "'"], TRUE)) {
      $className = 'SqlString';
    }
    elseif ($expr === 'NULL') {
      $className = 'SqlNull';
    }
    elseif ($expr === '*') {
      $className = 'SqlWild';
    }
    elseif (is_numeric($expr)) {
      $className = 'SqlNumber';
    }
    // If none of the above, assume it's a field name
    else {
      $className = 'SqlField';
    }
    $className = __NAMESPACE__ . '\\' . $className;
    if (!class_exists($className)) {
      throw new \API_Exception('Unable to parse sql expression: ' . $expression);
    }
    $sqlExpression = new $className($expr, $alias);
    foreach ($cantBe as $cant) {
      if (is_a($sqlExpression, __NAMESPACE__ . '\\' . $cant)) {
        throw new \API_Exception('Illegal sql expression.');
      }
    }
    if ($mustBe) {
      foreach ($mustBe as $must) {
        if (is_a($sqlExpression, __NAMESPACE__ . '\\' . $must)) {
          return $sqlExpression;
        }
      }
      throw new \API_Exception('Illegal sql expression.');
    }
    return $sqlExpression;
  }

  /**
   * Returns the field names of all sql columns that are arguments to this expression.
   *
   * @return array
   */
  public function getFields(): array {
    return $this->fields;
  }

  /**
   * Renders expression to a sql string, replacing field names with column names.
   *
   * @param array $fieldList
   * @return string
   */
  abstract public function render(array $fieldList): string;

  /**
   * @return string
   */
  public function getExpr(): string {
    return $this->expr;
  }

  /**
   * Returns the alias to use for SELECT AS.
   *
   * @return string
   */
  public function getAlias(): string {
    return $this->alias ?? $this->fields[0] ?? \CRM_Utils_String::munge($this->expr, '_', 256);
  }

  /**
   * Returns the name of this sql expression class.
   *
   * @return string
   */
  public function getType(): string {
    $className = get_class($this);
    return substr($className, strrpos($className, '\\') + 1);
  }

}

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

use Civi\Api4\Utils\CoreUtil;

/**
 * Base class for SqlColumn, SqlString, SqlBool, and SqlFunction classes.
 *
 * These are used to validate and format sql expressions in Api4 select queries.
 *
 * @package Civi\Api4\Query
 */
abstract class SqlExpression {

  /**
   * Field names used in this expression
   * @var string[]
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
   * Data type output by this expression
   *
   * @var string
   */
  protected static $dataType;

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

  private static function munge($name, $char = '_', $len = 63) {
    // Replace all white space and non-alpha numeric with $char
    // we only use the ascii character set since mysql does not create table names / field names otherwise
    // CRM-11744
    $name = preg_replace('/[^a-zA-Z0-9_]+/', $char, trim($name));

    // If there are no ascii characters present.
    if (!strlen(trim($name, $char))) {
      $name = \CRM_Utils_String::createRandom($len, \CRM_Utils_String::ALPHANUMERIC);
    }

    if ($len) {
      // lets keep variable names short
      return substr($name, 0, $len);
    }
    else {
      return $name;
    }
  }

  /**
   * Converts a string to a SqlExpression object.
   *
   * E.g. the expression "SUM(foo)" would return a SqlFunctionSUM object.
   *
   * @param string $expression
   * @param bool $parseAlias
   * @param array $mustBe
   * @return SqlExpression
   * @throws \CRM_Core_Exception
   */
  public static function convert(string $expression, $parseAlias = FALSE, $mustBe = []) {
    $as = $parseAlias ? strrpos($expression, ' AS ') : FALSE;
    $expr = $as ? substr($expression, 0, $as) : $expression;
    $alias = $as ? self::munge(substr($expression, $as + 4), '_', 256) : NULL;
    $bracketPos = strpos($expr, '(');
    $firstChar = substr($expr, 0, 1);
    $lastChar = substr($expr, -1);
    // Statement surrounded by brackets is an equation
    if ($firstChar === '(' && $lastChar === ')') {
      $className = 'SqlEquation';
    }
    // If there are brackets but not the first character, we have a function
    elseif ($bracketPos && preg_match('/^\w+\(.*\)(:[a-z]+)?$/', $expr)) {
      $fnName = substr($expr, 0, $bracketPos);
      if ($fnName !== strtoupper($fnName)) {
        throw new \CRM_Core_Exception('Sql function must be uppercase.');
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
    elseif ($expr === 'TRUE' || $expr === 'FALSE') {
      $className = 'SqlBool';
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
      throw new \CRM_Core_Exception('Unable to parse sql expression: ' . $expression);
    }
    $sqlExpression = new $className($expr, $alias);
    if ($mustBe) {
      foreach ($mustBe as $must) {
        if (is_a($sqlExpression, __NAMESPACE__ . '\\' . $must)) {
          return $sqlExpression;
        }
      }
      throw new \CRM_Core_Exception('Illegal sql expression.');
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
   * @param \Civi\Api4\Query\Api4Query $query
   * @param bool $includeAlias
   * @return string
   */
  public function render(Api4Query $query, bool $includeAlias = FALSE): string {
    return $this->expr . ($includeAlias ? " AS `{$this->getAlias()}`" : '');
  }

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
    return CoreUtil::stripNamespace(get_class($this));
  }

  /**
   * Checks the name of this sql expression class.
   *
   * @param $type
   * @return bool
   */
  public function isType($type): bool {
    return $this->getType() === $type;
  }

  /**
   * Get value serialization method if any.
   */
  public function getSerialize(): ?int {
    return NULL;
  }

  /**
   * @return string
   */
  abstract public static function getTitle(): string;

  /**
   * @return string|NULL
   */
  public static function getDataType():? string {
    return static::$dataType;
  }

  /**
   * Shift a keyword off the beginning of the argument string and return it.
   *
   * @param array $keywords
   *   Whitelist of keywords
   * @param string $arg
   * @return mixed|null
   */
  protected function captureKeyword($keywords, &$arg) {
    foreach (array_filter($keywords, 'strlen') as $key) {
      // Match keyword followed by a space or eol
      if (strpos($arg, $key . ' ') === 0 || rtrim($arg) === $key) {
        $arg = ltrim(substr($arg, strlen($key)));
        return $key;
      }
    }
    return NULL;
  }

  /**
   * Shifts 0 or more expressions off the argument string and returns them
   *
   * @param string $arg
   * @param array $mustBe
   * @param int $max
   * @return SqlExpression[]
   * @throws \CRM_Core_Exception
   */
  protected function captureExpressions(string &$arg, array $mustBe, int $max) {
    $captured = [];
    $arg = ltrim($arg);
    while (strlen($arg)) {
      $item = $this->captureExpression($arg);
      $arg = ltrim(substr($arg, strlen($item)));
      $expr = self::convert($item, FALSE, $mustBe);
      $this->fields = array_merge($this->fields, $expr->getFields());
      $captured[] = $expr;
      // Keep going if we have a comma indicating another expression follows
      if (count($captured) < $max && substr($arg, 0, 1) === ',') {
        $arg = ltrim(substr($arg, 1));
      }
      else {
        break;
      }
    }
    return $captured;
  }

  /**
   * Scans the beginning of a string for an expression; stops when it hits delimiter
   *
   * @param $arg
   * @return string
   */
  protected function captureExpression($arg) {
    $isEscaped = $quote = NULL;
    $item = '';
    $quotes = ['"', "'"];
    $brackets = [
      ')' => '(',
    ];
    $enclosures = array_fill_keys($brackets, 0);
    foreach (str_split($arg) as $char) {
      if (!$isEscaped && in_array($char, $quotes, TRUE)) {
        // Open quotes - we'll ignore everything inside
        if (!$quote) {
          $quote = $char;
        }
        // Close quotes
        elseif ($char === $quote) {
          $quote = NULL;
        }
      }
      if (!$quote) {
        // Delineates end of expression
        if (($char == ',' || $char == ' ') && !array_filter($enclosures)) {
          return $item;
        }
        // Open brackets - we'll ignore delineators inside
        if (isset($enclosures[$char])) {
          $enclosures[$char]++;
        }
        // Close brackets
        if (isset($brackets[$char]) && $enclosures[$brackets[$char]]) {
          $enclosures[$brackets[$char]]--;
        }
      }
      $item .= $char;
      // We are escaping the next char if this is a backslash not preceded by an odd number of backslashes
      $isEscaped = $char === '\\' && ((strlen($item) - strlen(rtrim($item, '\\'))) % 2);
    }
    return $item;
  }

}

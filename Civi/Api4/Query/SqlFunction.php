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
 * Base class for all Sql functions.
 *
 * @package Civi\Api4\Query
 */
abstract class SqlFunction extends SqlExpression {

  /**
   * @var array[]
   */
  protected $args = [];

  /**
   * Used for categorizing functions in the UI
   *
   * @var string
   */
  protected static $category;

  const CATEGORY_AGGREGATE = 'aggregate',
    CATEGORY_COMPARISON = 'comparison',
    CATEGORY_DATE = 'date',
    CATEGORY_MATH = 'math',
    CATEGORY_STRING = 'string';

  /**
   * Parse the argument string into an array of function arguments
   */
  protected function initialize() {
    $arg = trim(substr($this->expr, strpos($this->expr, '(') + 1, -1));
    foreach ($this->getParams() as $idx => $param) {
      $prefix = NULL;
      $name = $param['name'] ?: ($idx + 1);
      // If this isn't the first param it needs to start with something;
      // either the name (e.g. "ORDER BY") if it has one, or a comma separating it from the previous param.
      $start = $param['name'] ?: ($idx ? ',' : NULL);
      if ($start) {
        $prefix = $this->captureKeyword([$start], $arg);
        // Supply api_default
        if (!$prefix && isset($param['api_default'])) {
          $this->args[$idx] = [
            'prefix' => [$start],
            'expr' => array_map([parent::class, 'convert'], $param['api_default']['expr']),
            'suffix' => [],
          ];
          continue;
        }
        if (!$prefix && !$param['optional']) {
          throw new \CRM_Core_Exception("Missing param $name for SQL function " . static::getName());
        }
      }
      elseif ($param['flag_before']) {
        $prefix = $this->captureKeyword(array_keys($param['flag_before']), $arg);
      }
      $this->args[$idx] = [
        'prefix' => (array) $prefix,
        'expr' => [],
        'suffix' => [],
      ];
      if ($param['max_expr'] && (!$param['name'] || $param['name'] === $prefix)) {
        $exprs = $this->captureExpressions($arg, $param['must_be'], $param['max_expr']);
        if (
          count($exprs) < $param['min_expr'] &&
          !(!$exprs && $param['optional'])
        ) {
          throw new \CRM_Core_Exception("Too few arguments to param $name for SQL function " . static::getName());
        }
        $this->args[$idx]['expr'] = $exprs;

        $this->args[$idx]['suffix'] = (array) $this->captureKeyword(array_keys($param['flag_after']), $arg);
      }
    }
    if (trim($arg)) {
      throw new \CRM_Core_Exception("Too many arguments given for SQL function " . static::getName());
    }
  }

  /**
   * Change $dataType according to output of function
   *
   * @see \Civi\Api4\Utils\FormattingUtil::formatOutputValues
   * @param string $value
   * @param string $dataType
   * @return string
   */
  public function formatOutputValue($value, &$dataType) {
    if (static::$dataType) {
      $dataType = static::$dataType;
    }
    return $value;
  }

  /**
   * Render the expression for insertion into the sql query
   *
   * @param Civi\Api4\Query\Api4SelectQuery $query
   * @return string
   */
  public function render(Api4SelectQuery $query): string {
    $output = '';
    foreach ($this->args as $arg) {
      $rendered = $this->renderArg($arg, $query);
      if (strlen($rendered)) {
        $output .= (strlen($output) ? ' ' : '') . $rendered;
      }
    }
    return $this->getName() . '(' . $output . ')';
  }

  /**
   * @param array $arg
   * @param Civi\Api4\Query\Api4SelectQuery $query
   * @return string
   */
  private function renderArg($arg, Api4SelectQuery $query): string {
    $rendered = implode(' ', $arg['prefix']);
    foreach ($arg['expr'] ?? [] as $idx => $expr) {
      if (strlen($rendered) || $idx) {
        $rendered .= $idx ? ', ' : ' ';
      }
      $rendered .= $expr->render($query);
    }
    if ($arg['suffix']) {
      $rendered .= (strlen($rendered) ? ' ' : '') . implode(' ', $arg['suffix']);
    }
    return $rendered;
  }

  /**
   * @inheritDoc
   */
  public function getAlias(): string {
    return $this->alias ?? $this->getName() . ':' . implode('_', $this->fields);
  }

  /**
   * Get the name of this sql function.
   * @return string
   */
  public static function getName(): string {
    $className = static::class;
    return substr($className, strrpos($className, 'SqlFunction') + 11);
  }

  /**
   * Get the param metadata for this sql function.
   * @return array
   */
  final public static function getParams(): array {
    $params = [];
    foreach (static::params() as $param) {
      // Merge in defaults to ensure each param has these properties
      $param += [
        'name' => NULL,
        'label' => ts('Select'),
        'min_expr' => 1,
        'max_expr' => 1,
        'flag_before' => [],
        'flag_after' => [],
        'optional' => FALSE,
        'must_be' => ['SqlField', 'SqlFunction', 'SqlString', 'SqlNumber', 'SqlNull'],
        'api_default' => NULL,
      ];
      if (!$param['max_expr']) {
        $param['must_be'] = [];
      }
      $params[] = $param;
    }
    return $params;
  }

  abstract protected static function params(): array;

  /**
   * Get the arguments passed to this sql function instance.
   * @return array{prefix: array, suffix: array, expr: SqlExpression}[]
   */
  public function getArgs(): array {
    return $this->args;
  }

  /**
   * @return string
   */
  public static function getCategory(): string {
    return static::$category;
  }

  /**
   * All functions return 'SqlFunction' as their type.
   *
   * To get the function name @see SqlFunction::getName()
   * @return string
   */
  public function getType(): string {
    return 'SqlFunction';
  }

  /**
   * @return string
   */
  abstract public static function getDescription(): string;

}

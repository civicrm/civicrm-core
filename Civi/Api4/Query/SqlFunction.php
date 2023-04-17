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
 * Base class for all APIv4 Sql function definitions.
 *
 * SqlFunction classes don't actually process data, SQL itself does the real work.
 * The role of each SqlFunction class is to:
 *
 * 1. Whitelist a standard SQL function, or define a custom one, for use by APIv4 (it doesn't allow any that don't have a SQLFunction class).
 * 2. Document what the function does and what arguments it accepts.
 * 3. Tell APIv4 how to treat the inputs and how to format the outputs.
 *
 * @package Civi\Api4\Query
 */
abstract class SqlFunction extends SqlExpression {

  /**
   * @var array[]
   */
  protected $args = [];

  /**
   * Pseudoconstant suffix (for functions with option lists)
   * @var string
   */
  private $suffix;

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
    $matches = [];
    // Capture function argument string and possible suffix
    preg_match('/[_A-Z]+\((.*)\)(:[a-z]+)?$/', $this->expr, $matches);
    $arg = $matches[1];
    $this->setSuffix($matches[2] ?? NULL);
    // Parse function arguments string, match to declared function params
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
   * Set $dataType and convert value by suffix
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
    if (isset($value) && $this->suffix && $this->suffix !== 'id') {
      $dataType = 'String';
      $option = $this->getOptions()[$value] ?? NULL;
      // Option contains an array of suffix keys
      if (is_array($option)) {
        return $option[$this->suffix] ?? NULL;
      }
      // Flat arrays are name/value pairs
      elseif ($this->suffix === 'label') {
        return $option;
      }
      elseif ($this->suffix === 'name') {
        return $value;
      }
      else {
        return NULL;
      }
    }
    return $value;
  }

  /**
   * Render the expression for insertion into the sql query
   *
   * @param \Civi\Api4\Query\Api4SelectQuery $query
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
    return $this->renderExpression($output);
  }

  /**
   * Render the final expression
   *
   * @param string $output
   * @return string
   */
  protected function renderExpression($output): string {
    return $this->getName() . '(' . $output . ')';
  }

  /**
   * @param array $arg
   * @param \Civi\Api4\Query\Api4SelectQuery $query
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
    return $this->alias ?? $this->getName() . ':' . implode('_', $this->fields) . ($this->suffix ? ':' . $this->suffix : '');
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
   * For functions which output a finite set of values,
   * this allows the API to treat it as pseudoconstant options.
   *
   * e.g. MONTH() only returns integers 1-12, which can be formatted like
   * [1 => January, 2 => February, etc.]
   *
   * @return array|null
   */
  public static function getOptions(): ?array {
    return NULL;
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
   * @param string|null $suffix
   */
  private function setSuffix(?string $suffix): void {
    $this->suffix = $suffix ?
      str_replace(':', '', $suffix) :
      NULL;
  }

  /**
   * @return string
   */
  abstract public static function getDescription(): string;

}

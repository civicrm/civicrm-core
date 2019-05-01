<?php

/**
 * Class CRM_Utils_SQL_BaseParamQuery
 *
 * Base class for query-building which handles parameter interpolation.
 */
class CRM_Utils_SQL_BaseParamQuery implements ArrayAccess {

  /**
   * Interpolate values as soon as they are passed in (where(), join(), etc).
   *
   * Default.
   *
   * Pro: Every clause has its own unique namespace for parameters.
   * Con: Probably slower.
   * Advice: Use this when aggregating SQL fragments from agents who
   *   maintained by different parties.
   */
  const INTERPOLATE_INPUT = 'in';

  /**
   * Interpolate values when rendering SQL output (toSQL()).
   *
   * Pro: Probably faster.
   * Con: Must maintain an aggregated list of all parameters.
   * Advice: Use this when you have control over the entire query.
   */
  const INTERPOLATE_OUTPUT = 'out';

  /**
   * Determine mode automatically. When the first attempt is made
   * to use input-interpolation (eg `where(..., array(...))`) or
   * output-interpolation (eg `param(...)`), the mode will be
   * set. Subsequent calls will be validated using the same mode.
   */
  const INTERPOLATE_AUTO = 'auto';

  /**
   * @var mixed
   */
  protected $mode = NULL;

  /**
   * @var array
   */
  protected $params = [];

  /**
   * Public to work-around PHP 5.3 limit.
   * @var bool
   */
  public $strict = NULL;

  /**
   * Enable (or disable) strict mode.
   *
   * In strict mode, unknown variables will generate exceptions.
   *
   * @param bool $strict
   * @return self
   */
  public function strict($strict = TRUE) {
    $this->strict = $strict;
    return $this;
  }

  /**
   * Given a string like "field_name = @value", replace "@value" with an escaped SQL string
   *
   * @param string $expr SQL expression
   * @param null|array $args a list of values to insert into the SQL expression; keys are prefix-coded:
   *   prefix '@' => escape SQL
   *   prefix '#' => literal number, skip escaping but do validation
   *   prefix '!' => literal, skip escaping and validation
   *   if a value is an array, then it will be imploded
   *
   * PHP NULL's will be treated as SQL NULL's. The PHP string "null" will be treated as a string.
   *
   * @param string $activeMode
   *
   * @return string
   */
  public function interpolate($expr, $args, $activeMode = self::INTERPOLATE_INPUT) {
    if ($args === NULL) {
      return $expr;
    }
    else {
      if ($this->mode === self::INTERPOLATE_AUTO) {
        $this->mode = $activeMode;
      }
      elseif ($activeMode !== $this->mode) {
        throw new RuntimeException("Cannot mix interpolation modes.");
      }

      $select = $this;
      return preg_replace_callback('/([#!@])([a-zA-Z0-9_]+)/', function($m) use ($select, $args) {
        if (isset($args[$m[2]])) {
          $values = $args[$m[2]];
        }
        elseif (isset($args[$m[1] . $m[2]])) {
          // Backward compat. Keys in $args look like "#myNumber" or "@myString".
          $values = $args[$m[1] . $m[2]];
        }
        elseif ($select->strict) {
          throw new CRM_Core_Exception('Cannot build query. Variable "' . $m[1] . $m[2] . '" is unknown.');
        }
        else {
          // Unrecognized variables are ignored. Mitigate risk of accidents.
          return $m[0];
        }
        $values = is_array($values) ? $values : [$values];
        switch ($m[1]) {
          case '@':
            $parts = array_map([$select, 'escapeString'], $values);
            return implode(', ', $parts);

          // TODO: ensure all uses of this un-escaped literal are safe
          case '!':
            return implode(', ', $values);

          case '#':
            foreach ($values as $valueKey => $value) {
              if ($value === NULL) {
                $values[$valueKey] = 'NULL';
              }
              elseif (!is_numeric($value)) {
                //throw new API_Exception("Failed encoding non-numeric value" . var_export(array($m[0] => $values), TRUE));
                throw new CRM_Core_Exception("Failed encoding non-numeric value (" . $m[0] . ")");
              }
            }
            return implode(', ', $values);

          default:
            throw new CRM_Core_Exception("Unrecognized prefix");
        }
      }, $expr);
    }
  }

  /**
   * @param string|NULL $value
   * @return string
   *   SQL expression, e.g. "it\'s great" (with-quotes) or NULL (without-quotes)
   */
  public function escapeString($value) {
    return $value === NULL ? 'NULL' : '"' . CRM_Core_DAO::escapeString($value) . '"';
  }

  /**
   * Set one (or multiple) parameters to interpolate into the query.
   *
   * @param array|string $keys
   *   Key name, or an array of key-value pairs.
   * @param null|mixed $value
   *   The new value of the parameter.
   *   Values may be strings, ints, or arrays thereof -- provided that the
   *   SQL query uses appropriate prefix (e.g. "@", "!", "#").
   * @return $this
   */
  public function param($keys, $value = NULL) {
    if ($this->mode === self::INTERPOLATE_AUTO) {
      $this->mode = self::INTERPOLATE_OUTPUT;
    }
    elseif ($this->mode !== self::INTERPOLATE_OUTPUT) {
      throw new RuntimeException("Select::param() only makes sense when interpolating on output.");
    }

    if (is_array($keys)) {
      foreach ($keys as $k => $v) {
        $this->params[$k] = $v;
      }
    }
    else {
      $this->params[$keys] = $value;
    }
    return $this;
  }

  /**
   * Has an offset been set.
   *
   * @param string $offset
   *
   * @return bool
   */
  public function offsetExists($offset) {
    return isset($this->params[$offset]);
  }

  /**
   * Get the value of a SQL parameter.
   *
   * @code
   *   $select['cid'] = 123;
   *   $select->where('contact.id = #cid');
   *   echo $select['cid'];
   * @endCode
   *
   * @param string $offset
   * @return mixed
   * @see param()
   * @see ArrayAccess::offsetGet
   */
  public function offsetGet($offset) {
    return $this->params[$offset];
  }

  /**
   * Set the value of a SQL parameter.
   *
   * @code
   *   $select['cid'] = 123;
   *   $select->where('contact.id = #cid');
   *   echo $select['cid'];
   * @endCode
   *
   * @param string $offset
   * @param mixed $value
   *   The new value of the parameter.
   *   Values may be strings, ints, or arrays thereof -- provided that the
   *   SQL query uses appropriate prefix (e.g. "@", "!", "#").
   * @see param()
   * @see ArrayAccess::offsetSet
   */
  public function offsetSet($offset, $value) {
    $this->param($offset, $value);
  }

  /**
   * Unset the value of a SQL parameter.
   *
   * @param string $offset
   * @see param()
   * @see ArrayAccess::offsetUnset
   */
  public function offsetUnset($offset) {
    unset($this->params[$offset]);
  }

}

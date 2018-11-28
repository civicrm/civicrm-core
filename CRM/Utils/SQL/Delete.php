<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Dear God Why Do I Have To Write This (Dumb SQL Builder)
 *
 * Usage:
 * @code
 * $del = CRM_Utils_SQL_Delete::from('civicrm_activity act')
 *     ->where('activity_type_id = #type', array('type' => 234))
 *     ->where('status_id IN (#statuses)', array('statuses' => array(1,2,3))
 *     ->where('subject like @subj', array('subj' => '%hello%'))
 *     ->where('!dynamicColumn = 1', array('dynamicColumn' => 'coalesce(is_active,0)'))
 *     ->where('!column = @value', array(
 *        'column' => $customField->column_name,
 *        'value' => $form['foo']
 *      ))
 * echo $del->toSQL();
 * @endcode
 *
 * Design principles:
 *  - Portable
 *    - No knowledge of the underlying SQL API (except for escaping -- CRM_Core_DAO::escapeString)
 *    - No knowledge of the underlying data model
 *  - SQL clauses correspond to PHP functions ($select->where("foo_id=123"))
 *  - Variable escaping is concise and controllable based on prefixes, eg
 *    - similar to Drupal's t()
 *    - use "@varname" to insert the escaped value
 *    - use "!varname" to insert raw (unescaped) values
 *    - use "#varname" to insert a numerical value (these are validated but not escaped)
 *    - to disable any preprocessing, simply omit the variable list
 *    - control characters (@!#) are mandatory in expressions but optional in arg-keys
 *  - Variables may be individual values or arrays; arrays are imploded with commas
 *  - Conditionals are AND'd; if you need OR's, do it yourself
 *  - Use classes/functions with documentation (rather than undocumented array-trees)
 *  - For any given string, interpolation is only performed once. After an interpolation,
 *    a string may never again be subjected to interpolation.
 *
 * The "interpolate-once" principle can be enforced by either interpolating on input
 * xor output. The notations for input and output interpolation are a bit different,
 * and they may not be mixed.
 *
 * @code
 * // Interpolate on input. Set params when using them.
 * $select->where('activity_type_id = #type', array(
 *   'type' => 234,
 * ));
 *
 * // Interpolate on output. Set params independently.
 * $select
 *     ->where('activity_type_id = #type')
 *     ->param('type', 234),
 * @endcode
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Utils_SQL_Delete extends CRM_Utils_SQL_BaseParamQuery {

  private $from;
  private $wheres = array();

  /**
   * Create a new DELETE query.
   *
   * @param string $from
   *   Table-name and optional alias.
   * @param array $options
   * @return CRM_Utils_SQL_Delete
   */
  public static function from($from, $options = array()) {
    return new self($from, $options);
  }

  /**
   * Create a new DELETE query.
   *
   * @param string $from
   *   Table-name and optional alias.
   * @param array $options
   */
  public function __construct($from, $options = array()) {
    $this->from = $from;
    $this->mode = isset($options['mode']) ? $options['mode'] : self::INTERPOLATE_AUTO;
  }

  /**
   * Make a new copy of this query.
   *
   * @return CRM_Utils_SQL_Delete
   */
  public function copy() {
    return clone $this;
  }

  /**
   * Merge something or other.
   *
   * @param CRM_Utils_SQL_Delete $other
   * @param array|NULL $parts
   *   ex: 'wheres'
   * @return CRM_Utils_SQL_Delete
   */
  public function merge($other, $parts = NULL) {
    if ($other === NULL) {
      return $this;
    }

    if ($this->mode === self::INTERPOLATE_AUTO) {
      $this->mode = $other->mode;
    }
    elseif ($other->mode === self::INTERPOLATE_AUTO) {
      // Noop.
    }
    elseif ($this->mode !== $other->mode) {
      // Mixing modes will lead to someone getting an expected substitution.
      throw new RuntimeException("Cannot merge queries that use different interpolation modes ({$this->mode} vs {$other->mode}).");
    }

    $arrayFields = array('wheres', 'params');
    foreach ($arrayFields as $f) {
      if ($parts === NULL || in_array($f, $parts)) {
        $this->{$f} = array_merge($this->{$f}, $other->{$f});
      }
    }

    $flatFields = array('from');
    foreach ($flatFields as $f) {
      if ($parts === NULL || in_array($f, $parts)) {
        if ($other->{$f} !== NULL) {
          $this->{$f} = $other->{$f};
        }
      }
    }

    return $this;
  }

  /**
   * Limit results by adding extra condition(s) to the WHERE clause
   *
   * @param string|array $exprs list of SQL expressions
   * @param null|array $args use NULL to disable interpolation; use an array of variables to enable
   * @return CRM_Utils_SQL_Delete
   */
  public function where($exprs, $args = NULL) {
    $exprs = (array) $exprs;
    foreach ($exprs as $expr) {
      $evaluatedExpr = $this->interpolate($expr, $args);
      $this->wheres[$evaluatedExpr] = $evaluatedExpr;
    }
    return $this;
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
   * @return \CRM_Utils_SQL_Delete
   */
  public function param($keys, $value = NULL) {
    // Why bother with an override? To provide better type-hinting in `@return`.
    return parent::param($keys, $value);
  }

  /**
   * @param array|NULL $parts
   *   List of fields to check (e.g. 'wheres').
   *   Defaults to all.
   * @return bool
   */
  public function isEmpty($parts = NULL) {
    $empty = TRUE;
    $fields = array(
      'from',
      'wheres',
    );
    if ($parts !== NULL) {
      $fields = array_intersect($fields, $parts);
    }
    foreach ($fields as $field) {
      if (!empty($this->{$field})) {
        $empty = FALSE;
      }
    }
    return $empty;
  }

  /**
   * @return string
   *   SQL statement
   */
  public function toSQL() {
    $sql = 'DELETE ';

    if ($this->from !== NULL) {
      $sql .= 'FROM ' . $this->from . "\n";
    }
    if ($this->wheres) {
      $sql .= 'WHERE (' . implode(') AND (', $this->wheres) . ")\n";
    }
    if ($this->mode === self::INTERPOLATE_OUTPUT) {
      $sql = $this->interpolate($sql, $this->params, self::INTERPOLATE_OUTPUT);
    }
    return $sql;
  }

  /**
   * Execute the query.
   *
   * To examine the results, use a function like `fetch()`, `fetchAll()`,
   * `fetchValue()`, or `fetchMap()`.
   *
   * @param string|NULL $daoName
   *   The return object should be an instance of this class.
   *   Ex: 'CRM_Contact_BAO_Contact'.
   * @param bool $i18nRewrite
   *   If the system has multilingual features, should the field/table
   *   names be rewritten?
   * @return CRM_Core_DAO
   * @see CRM_Core_DAO::executeQuery
   * @see CRM_Core_I18n_Schema::rewriteQuery
   */
  public function execute($daoName = NULL, $i18nRewrite = TRUE) {
    // Don't pass through $params. toSQL() handles interpolation.
    $params = array();

    // Don't pass through $abort, $trapException. Just use straight-up exceptions.
    $abort = TRUE;
    $trapException = FALSE;
    $errorScope = CRM_Core_TemporaryErrorScope::useException();

    // Don't pass through freeDAO. You can do it yourself.
    $freeDAO = FALSE;

    return CRM_Core_DAO::executeQuery($this->toSQL(), $params, $abort, $daoName,
      $freeDAO, $i18nRewrite, $trapException);
  }

}

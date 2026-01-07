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

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Utils\CoreUtil;
use Civi\Api4\Utils\FormattingUtil;

/**
 * A query `node` may be in one of three formats:
 *
 * * leaf: [$fieldName, $operator, $criteria]
 * * negated: ['NOT', $node]
 * * branch: ['OR|NOT', [$node, $node, ...]]
 *
 * For leaf operators,
 * @see CoreUtil::getOperators()
 */
abstract class Api4Query {

  const
    MAIN_TABLE_ALIAS = 'a',
    UNLIMITED = '18446744073709551615';

  /**
   * @var \CRM_Utils_SQL_Select
   */
  protected $query;

  /**
   * @var \Civi\Api4\Generic\AbstractQueryAction
   */
  protected $api;

  /**
   * @var array
   * [alias => expr][]
   */
  public $selectAliases = [];

  /**
   * @var array
   */
  protected $entityValues = [];

  /**
   * @var array[]
   */
  public $apiFieldSpec = [];

  /**
   * @param \Civi\Api4\Generic\AbstractQueryAction $api
   */
  public function __construct($api) {
    $this->api = $api;
  }

  abstract public function getField(string $expr):? array;

  /**
   * Builds main final sql statement after initialization.
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function getSql() {
    $this->buildSelectClause();
    $this->buildWhereClause();
    $this->buildOrderBy();
    $this->buildLimit();
    $this->buildGroupBy();
    $this->buildHavingClause();
    return $this->query->toSQL();
  }

  public function getResults(): array {
    $results = [];
    $sql = $this->getSql();
    $this->debug('sql', $sql);
    $query = \CRM_Core_DAO::executeQuery($sql);
    while ($query->fetch()) {
      $result = [];
      foreach ($this->selectAliases as $alias => $expr) {
        $returnName = $alias;
        $alias = str_replace(['.', ' '], '_', $alias);
        $result[$returnName] = property_exists($query, $alias) ? $query->$alias : NULL;
      }
      $results[] = $result;
    }
    return $results;
  }

  protected function isAggregateQuery() {
    if ($this->getGroupBy()) {
      return TRUE;
    }
    foreach ($this->getSelect() as $sql) {
      $classname = get_class(SqlExpression::convert($sql, TRUE));
      if (method_exists($classname, 'getCategory') && $classname::getCategory() === SqlFunction::CATEGORY_AGGREGATE) {
        return TRUE;
      }
    }
    return FALSE;
  }

  protected function isDistinctUnion(): bool {
    foreach ($this->api->getSets() as $set) {
      if ($set[0] === 'UNION DISTINCT') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Add LIMIT to query
   *
   * @throws \CRM_Core_Exception
   */
  protected function buildLimit() {
    if ($this->getLimit() || $this->getOffset()) {
      // If limit is 0, mysql will actually return 0 results. Instead set to maximum possible.
      $this->query->limit($this->getLimit() ?: self::UNLIMITED, $this->getOffset());
    }
  }

  /**
   * Add GROUP BY clause to query
   */
  protected function buildGroupBy() {
    foreach ($this->getGroupBy() as $item) {
      $this->query->groupBy($this->renderExpr($this->getExpression($item)));
    }
  }

  /**
   * @param string $path
   * @param array $field
   */
  public function addSpecField($path, $field) {
    // Only add field to spec if we have permission
    if ($this->getCheckPermissions() && !empty($field['permission']) && !\CRM_Core_Permission::check($field['permission'])) {
      $this->apiFieldSpec[$path] = FALSE;
      return;
    }
    $this->apiFieldSpec[$path] = $field + [
      'name' => $path,
      'path' => $path,
      'type' => 'Extra',
      'entity' => NULL,
      'implicit_join' => NULL,
      'explicit_join' => NULL,
    ];
  }

  /**
   * @param string $expr
   * @param array $allowedTypes
   * @return SqlExpression
   * @throws \CRM_Core_Exception
   */
  protected function getExpression(string $expr, $allowedTypes = NULL) {
    $sqlExpr = SqlExpression::convert($expr, FALSE, $allowedTypes);
    foreach ($sqlExpr->getFields() as $fieldName) {
      $this->getField($fieldName, TRUE);
    }
    return $sqlExpr;
  }

  /**
   * Recursively validate and transform a branch or leaf clause array to SQL.
   *
   * @param array $clause
   * @param string $type
   *   WHERE|HAVING|ON
   * @param int $depth
   * @return string SQL where clause
   *
   * @throws \CRM_Core_Exception
   * @uses composeClause() to generate the SQL etc.
   */
  public function treeWalkClauses($clause, $type, $depth = 0) {
    // Skip empty leaf.
    if (in_array($clause[0], ['AND', 'OR', 'NOT']) && empty($clause[1])) {
      return '';
    }
    switch ($clause[0]) {
      case 'OR':
      case 'AND':
        // handle branches
        if (count($clause[1]) === 1) {
          // a single set so AND|OR is immaterial
          return $this->treeWalkClauses($clause[1][0], $type, $depth + 1);
        }
        else {
          $sql_subclauses = [];
          foreach ($clause[1] as $subclause) {
            $sql_subclauses[] = $this->treeWalkClauses($subclause, $type, $depth + 1);
          }
          return '(' . implode("\n" . $clause[0] . ' ', $sql_subclauses) . ')';
        }

      case 'NOT':
        // If we get a group of clauses with no operator, assume AND
        if (!is_string($clause[1][0])) {
          $clause[1] = ['AND', $clause[1]];
        }
        return 'NOT (' . $this->treeWalkClauses($clause[1], $type, $depth + 1) . ')';

      default:
        if ($clause[1] === 'CONTAINS' || $clause[1] === 'NOT CONTAINS' || $clause[1] === 'CONTAINS ONE OF' || $clause[1] === 'NOT CONTAINS ONE OF') {
          if ($clause[1] === 'CONTAINS ONE OF') {
            $clause[1] = 'CONTAINS';
            $binding = ' OR ';
          }
          elseif ($clause[1] === 'NOT CONTAINS ONE OF') {
            $clause[1] = 'NOT CONTAINS';
            $binding = ' OR ';
          }
          else {
            $binding = ' AND ';
          }

          if (is_array($clause[2])) {
            // Not should be applied to the entire clause instead of each piece
            $not = '';
            if (str_starts_with($clause[1], 'NOT')) {
              $not = 'NOT';
              $clause[1] = substr($clause[1], 4);
            }
            $queryString = '';

            foreach ($clause[2] as $value) {
              $subclauses = $clause;
              // Strings must be quoted in ON clause
              if ($type === 'ON' && is_string($value)) {
                $value = "'$value'";
              }

              $subclauses[2] = $value;

              if ($queryString !== '') {
                $queryString .= $binding;
              }

              try {
                $queryString .= $this->composeClause($subclauses, $type, $depth);
              }
              // Silently ignore fields the user lacks permission to see
              catch (UnauthorizedException $e) {
                return '';
              }
            }

            return "$not($queryString)";
          }
        }

        try {
          return $this->composeClause($clause, $type, $depth);
        }
        // Silently ignore fields the user lacks permission to see
        catch (UnauthorizedException $e) {
          return '';
        }
    }
  }

  /**
   * Validate and transform a leaf clause array to SQL.
   * @param array $clause [$fieldName, $operator, $criteria, $isExpression]
   * @param string $type
   *   WHERE|HAVING|ON
   * @param int $depth
   * @return string SQL
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function composeClause(array $clause, string $type, int $depth) {
    $field = NULL;
    // Pad array for unary operators
    [$valueA, $operator, $valueB, $isBAnExpression] = array_pad($clause, 4, NULL);
    // Typical Api4 convention is [field, op, value], so $valueA is always parsed as an expression.
    // Is $valueB an expression? Defaults to FALSE in WHERE & HAVING clauses, TRUE in ON clauses.
    $isBAnExpression ??= ($type === 'ON');
    if (!in_array($operator, CoreUtil::getOperators(), TRUE)) {
      throw new \CRM_Core_Exception('Illegal operator');
    }
    $fieldAlias = NULL;

    // For HAVING, expr must be an item in the SELECT clause
    if ($type === 'HAVING') {
      // $valueA references a fieldName or alias
      if (isset($this->selectAliases[$valueA])) {
        $fieldAlias = $valueA;
        // Attempt to format if this is a real field
        if (isset($this->apiFieldSpec[$valueA]) && !$isBAnExpression) {
          $field = $this->getField($valueA);
          FormattingUtil::formatInputValue($valueB, $valueA, $field, $this->entityValues, $operator);
        }
      }
      // $valueA references a non-field expression like a function; convert to alias
      elseif (in_array($valueA, $this->selectAliases)) {
        $fieldAlias = array_search($valueA, $this->selectAliases);
      }
      // If either the having or select field contains a pseudoconstant suffix, match and perform substitution
      elseif (!$isBAnExpression) {
        [$fieldName] = explode(':', $valueA);
        foreach ($this->selectAliases as $selectAlias => $selectExpr) {
          [$selectField] = explode(':', $selectAlias);
          if ($selectAlias === $selectExpr && $fieldName === $selectField && isset($this->apiFieldSpec[$fieldName])) {
            $field = $this->getField($fieldName);
            FormattingUtil::formatInputValue($valueB, $valueA, $field, $this->entityValues, $operator);
            $fieldAlias = $selectAlias;
            break;
          }
        }
      }
      // Format a function in the HAVING clause
      if (isset($fieldAlias) && !isset($field)) {
        try {
          $exprA = $this->getExpression($this->selectAliases[$fieldAlias], ['SqlFunction']);
          $fauxField = [
            'name' => NULL,
            'data_type' => $exprA->getRenderedDataType($this),
          ];
          FormattingUtil::formatInputValue($valueB, NULL, $fauxField, $this->entityValues, $operator);
        }
        catch (\CRM_Core_Exception $e) {
          // Not a function
        }
      }
      if (!isset($fieldAlias)) {
        if (in_array($valueA, $this->getSelect())) {
          throw new UnauthorizedException("Unauthorized field '$valueA'");
        }
        else {
          throw new \CRM_Core_Exception("Invalid expression in HAVING clause: '$valueA'. Must use a value from SELECT clause.");
        }
      }
      $fieldAlias = '`' . $fieldAlias . '`';
      // Comparing two fields in the HAVING clause
      if ($isBAnExpression) {
        $targetField = isset($this->selectAliases[$valueB]) ? $valueB : array_search($valueB, $this->selectAliases);
        if (!$targetField) {
          throw new \CRM_Core_Exception("Invalid expression in HAVING clause: '$valueB'. Must use a value from SELECT clause.");
        }
        return sprintf('%s %s `%s`', $fieldAlias, $operator, $targetField);
      }
    }

    // Handle WHERE & ON clauses
    else {
      // $isBAnExpression is usually FALSE for WHERE clauses unless explicitly enabled by 4th param
      if (!$isBAnExpression) {
        // 1st param is always treated as a sql expression
        $exprA = $this->getExpression($valueA, ['SqlField', 'SqlFunction', 'SqlEquation']);
        if ($exprA->getType() === 'SqlField') {
          $fieldName = count($exprA->getFields()) === 1 ? $exprA->getFields()[0] : NULL;
          $field = $this->getField($fieldName, TRUE);
          FormattingUtil::formatInputValue($valueB, $fieldName, $field, $this->entityValues, $operator);
        }
        elseif ($exprA->getType() === 'SqlFunction') {
          // If $valueA uses a date extraction function and $valueB uses a relative date, add that function to $valueB, to compare apples with apples
          if ($exprA->getCategory() === SqlFunction::CATEGORY_PARTIAL_DATE && is_string($valueB) && $valueB !== '') {
            $valueB = FormattingUtil::formatDateValue('YmdHis', $valueB, $operator);
            // If the formatter didn't convert it to an array, add the function, otherwise no need as we're using BETWEEN
            if (is_string($valueB)) {
              $isBAnExpression = TRUE;
              // EXTRACT has an extra arg
              if ($exprA->getName() === 'EXTRACT') {
                $valueB = explode('FROM', $valueA)[0] . "FROM '$valueB')";
              }
              else {
                $valueB = $exprA->getName() . "('$valueB')";
              }
            }
          }
          else {
            $fauxField = [
              'name' => NULL,
              'data_type' => $exprA::getDataType(),
            ];
            FormattingUtil::formatInputValue($valueB, NULL, $fauxField, $this->entityValues, $operator);
          }
        }
        $fieldAlias = $exprA->render($this);
      }

      // $isBAnExpression is usually TRUE for ON clauses unless explicitly disabled by 4th param
      if ($isBAnExpression) {
        $exprA = $this->getExpression($valueA);
        $fieldName = count($exprA->getFields()) === 1 ? $exprA->getFields()[0] : NULL;
        $fieldAlias = $exprA->render($this);
        if (is_string($valueB)) {
          $exprB = $this->getExpression($valueB);
          // Format string input
          if ($exprA->getType() === 'SqlField' && $exprB->getType() === 'SqlString') {
            // Strip surrounding quotes
            $renderedB = substr($exprB->getExpr(), 1, -1);
            FormattingUtil::formatInputValue($renderedB, $fieldName, $this->apiFieldSpec[$fieldName], $this->entityValues, $operator);
            return $this->createSQLClause($fieldAlias, $operator, $renderedB, $this->apiFieldSpec[$fieldName], $depth);
          }
          else {
            $renderedB = $exprB->render($this);
            return sprintf('%s %s %s', $fieldAlias, $operator, $renderedB);
          }
        }
        elseif ($exprA->getType() === 'SqlField') {
          $field = $this->getField($fieldName);
          FormattingUtil::formatInputValue($valueB, $fieldName, $field, $this->entityValues, $operator);
        }
      }
    }

    $sqlClause = $this->createSQLClause($fieldAlias, $operator, $valueB, $field, $depth);
    if ($sqlClause === NULL) {
      throw new \CRM_Core_Exception("Invalid value in $type clause for '$valueA'");
    }
    return $sqlClause;
  }

  /**
   * @param string $fieldAlias
   * @param string $operator
   * @param mixed $value
   * @param array|null $field
   * @param int $depth
   * @return array|string|NULL
   * @throws \Exception
   */
  protected function createSQLClause($fieldAlias, $operator, $value, $field, int $depth) {
    if (!empty($field['operators']) && !in_array($operator, $field['operators'], TRUE)) {
      throw new \CRM_Core_Exception('Illegal operator for ' . $field['name']);
    }
    // Some fields use a callback to generate their sql
    if (!empty($field['sql_filters'])) {
      $sql = [];
      foreach ($field['sql_filters'] as $filter) {
        $clause = is_callable($filter) ? $filter($field, $fieldAlias, $operator, $value, $this, $depth) : NULL;
        if ($clause) {
          $sql[] = $clause;
        }
      }
      return $sql ? implode(' AND ', $sql) : NULL;
    }

    $original_operator = $operator;

    // The CONTAINS and NOT CONTAINS operators match a substring for strings.
    // For arrays & serialized fields, they only match a complete (not partial) string within the array.
    if ($operator === 'CONTAINS' || $operator === 'NOT CONTAINS') {
      $sep = \CRM_Core_DAO::VALUE_SEPARATOR;
      switch ($field['serialize'] ?? NULL) {

        case \CRM_Core_DAO::SERIALIZE_JSON:
          $operator = ($operator === 'CONTAINS') ? 'LIKE' : 'NOT LIKE';
          $value = '%"' . $value . '"%';
          // FIXME: Use this instead of the above hack once MIN_INSTALL_MYSQL_VER is bumped to 5.7.
          // return sprintf('JSON_SEARCH(%s, "one", "%s") IS NOT NULL', $fieldAlias, \CRM_Core_DAO::escapeString($value));
          break;

        case \CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND:
          $operator = ($operator === 'CONTAINS') ? 'LIKE' : 'NOT LIKE';
          // This is easy to query because the string is always bookended by separators.
          $value = '%' . $sep . $value . $sep . '%';
          break;

        case \CRM_Core_DAO::SERIALIZE_SEPARATOR_TRIMMED:
          $operator = ($operator === 'CONTAINS') ? 'REGEXP' : 'NOT REGEXP';
          // This is harder to query because there's no bookend.
          // Use regex to match string within separators or content boundary
          // Escaping regex per https://stackoverflow.com/questions/3782379/whats-the-best-way-to-escape-user-input-for-regular-expressions-in-mysql
          $value = "(^|$sep)" . preg_quote($value, '&') . "($sep|$)";
          break;

        case \CRM_Core_DAO::SERIALIZE_COMMA:
          $operator = ($operator === 'CONTAINS') ? 'REGEXP' : 'NOT REGEXP';
          // Match string within commas or content boundary
          // Escaping regex per https://stackoverflow.com/questions/3782379/whats-the-best-way-to-escape-user-input-for-regular-expressions-in-mysql
          $value = '(^|,)' . preg_quote($value, '&') . '(,|$)';
          break;

        default:
          $operator = ($operator === 'CONTAINS') ? 'LIKE' : 'NOT LIKE';
          $value = '%' . $value . '%';
          break;
      }
    }

    if ($operator === 'IS EMPTY' || $operator === 'IS NOT EMPTY') {
      // If field is not a string or number, this will pass through and use IS NULL/IS NOT NULL
      $operator = str_replace('EMPTY', 'NULL', $operator);
      // For strings & numbers, create an OR grouping of empty value OR null
      if (in_array($field['data_type'] ?? NULL, ['String', 'Integer', 'Float', 'Boolean'], TRUE)) {
        $emptyVal = ($field['data_type'] === 'String' || $field['serialize']) ? '""' : '0';
        $isEmptyClause = $operator === 'IS NULL' ? "= $emptyVal OR" : "<> $emptyVal AND";
        return "($fieldAlias $isEmptyClause $fieldAlias $operator)";
      }
    }

    if (!$value && ($operator === 'IN' || $operator === 'NOT IN')) {
      $value[] = FALSE;
    }

    if (is_bool($value)) {
      $value = (int) $value;
    }

    if ($operator == 'REGEXP' || $operator == 'NOT REGEXP' || $operator == 'REGEXP BINARY' || $operator == 'NOT REGEXP BINARY') {
      $sqlClause = sprintf('%s %s "%s"', (str_ends_with($operator, 'BINARY') ? 'CAST(' . $fieldAlias . ' AS BINARY)' : $fieldAlias), $operator, \CRM_Core_DAO::escapeString($value));
    }
    else {
      $sqlClause = \CRM_Core_DAO::createSQLFilter($fieldAlias, [$operator => $value]);
    }

    if ($original_operator === "NOT CONTAINS") {
      // For a "NOT CONTAINS", this adds an "OR IS NULL" clause - we want to know that a particular value is not present and don't care whether it has any other value
      return "(($sqlClause) OR $fieldAlias IS NULL)";
    }

    return $sqlClause;
  }

  /**
   * @return array
   */
  public function getSelect() {
    return $this->api->getSelect();
  }

  /**
   * @return array
   */
  public function getWhere() {
    return $this->api->getWhere();
  }

  /**
   * @return array
   */
  public function getHaving() {
    return $this->api->getHaving();
  }

  /**
   * @return array
   */
  public function getJoin() {
    return $this->api->getJoin();
  }

  /**
   * @return array
   */
  public function getGroupBy() {
    return $this->api->getGroupBy();
  }

  /**
   * @return array
   */
  public function getOrderBy() {
    return $this->api->getOrderBy();
  }

  /**
   * @return mixed
   */
  public function getLimit() {
    return $this->api->getLimit();
  }

  /**
   * @return mixed
   */
  public function getOffset() {
    return $this->api->getOffset();
  }

  /**
   * @return bool|string
   */
  public function getCheckPermissions() {
    return $this->api->getCheckPermissions();
  }

  /**
   * @return mixed
   */
  public function getApiParam($param) {
    return call_user_func([$this->api, 'get' . ucfirst($param)]);
  }

  /**
   * @return \CRM_Utils_SQL_Select
   */
  public function getQuery() {
    return $this->query;
  }

  /**
   * Add something to the api's debug output if debugging is enabled
   *
   * @param $key
   * @param $item
   */
  public function debug($key, $item) {
    if ($this->api->getDebug()) {
      $this->api->_debugOutput[$key][] = $item;
    }
  }

}

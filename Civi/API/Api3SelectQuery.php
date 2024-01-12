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
namespace Civi\API;

/**
 */
class Api3SelectQuery extends SelectQuery {

  protected $apiVersion = 3;

  /**
   * @inheritDoc
   */
  protected function buildWhereClause() {
    $filters = [];
    foreach ($this->where as $key => $value) {
      $table_name = NULL;
      $column_name = NULL;

      if (substr($key, 0, 7) == 'filter.') {
        // Legacy support for old filter syntax per the test contract.
        // (Convert the style to the later one & then deal with them).
        $filterArray = explode('.', $key);
        $value = [$filterArray[1] => $value];
        $key = 'filters';
      }

      // Legacy support for 'filter's construct.
      if ($key == 'filters') {
        foreach ($value as $filterKey => $filterValue) {
          if (substr($filterKey, -4, 4) == 'high') {
            $key = substr($filterKey, 0, -5);
            $value = ['<=' => $filterValue];
          }

          if (substr($filterKey, -3, 3) == 'low') {
            $key = substr($filterKey, 0, -4);
            $value = ['>=' => $filterValue];
          }

          if ($filterKey == 'is_current' || $filterKey == 'isCurrent') {
            // Is current is almost worth creating as a 'sql filter' in the DAO function since several entities have the concept.
            $todayStart = date('Ymd000000', strtotime('now'));
            $todayEnd = date('Ymd235959', strtotime('now'));
            $a = self::MAIN_TABLE_ALIAS;
            $this->query->where("($a.start_date <= '$todayStart' OR $a.start_date IS NULL)
              AND ($a.end_date >= '$todayEnd' OR $a.end_date IS NULL)
              AND a.is_active = 1");
          }
        }
      }
      // Ignore the "options" param if it is referring to api options and not a field in this entity
      if (
        $key === 'options' && is_array($value)
        && !in_array(\CRM_Utils_Array::first(array_keys($value)), \CRM_Core_DAO::acceptedSQLOperators())
      ) {
        continue;
      }
      $field = $this->getField($key);
      if ($field) {
        $key = $field['name'];
      }
      if (in_array($key, $this->entityFieldNames)) {
        $table_name = self::MAIN_TABLE_ALIAS;
        $column_name = $key;
      }
      elseif (($cf_id = \CRM_Core_BAO_CustomField::getKeyID($key)) != FALSE) {
        // If we check a custom field on 'IS NULL', it should also work when there is no
        // record in the custom value table, see CRM-20740.
        $side = empty($value['IS NULL']) ? 'INNER' : 'LEFT OUTER';
        [$table_name, $column_name] = $this->addCustomField($this->apiFieldSpec['custom_' . $cf_id], $side);
      }
      elseif (strpos($key, '.')) {
        $fkInfo = $this->addFkField($key, 'INNER');
        if ($fkInfo) {
          [$table_name, $column_name] = $fkInfo;
          $this->validateNestedInput($key, $value);
        }
      }
      // I don't know why I had to specifically exclude 0 as a key - wouldn't the others have caught it?
      // We normally silently ignore null values passed in - if people want IS_NULL they can use acceptedSqlOperator syntax.
      if ((!$table_name) || empty($key) || is_null($value)) {
        // No valid filter field. This might be a chained call or something.
        // Just ignore this for the $where_clause.
        continue;
      }
      $operator = is_array($value) ? \CRM_Utils_Array::first(array_keys($value)) : NULL;
      if (!in_array($operator, \CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
        $value = ['=' => $value];
      }
      $filters[$key] = \CRM_Core_DAO::createSQLFilter("{$table_name}.{$column_name}", $value);
    }
    // Support OR groups
    if (!empty($this->where['options']['or'])) {
      $orGroups = $this->where['options']['or'];
      if (is_string($orGroups)) {
        $orGroups = array_map('trim', explode(',', $orGroups));
      }
      if (!is_array(\CRM_Utils_Array::first($orGroups))) {
        $orGroups = [$orGroups];
      }
      foreach ($orGroups as $orGroup) {
        $orClause = [];
        foreach ($orGroup as $key) {
          if (!isset($filters[$key])) {
            throw new \CRM_Core_Exception("'$key' specified in OR group but not added to params");
          }
          $orClause[] = $filters[$key];
          unset($filters[$key]);
        }
        $this->query->where(implode(' OR ', $orClause));
      }
    }
    // Add the remaining params using AND
    foreach ($filters as $filter) {
      $this->query->where($filter);
    }
  }

  /**
   * @inheritDoc
   */
  protected function getFields() {
    require_once 'api/v3/Generic.php';
    // Call this function directly instead of using the api wrapper to force unique field names off
    $apiSpec = \civicrm_api3_generic_getfields([
      'entity' => $this->entity,
      'version' => 3,
      'params' => ['action' => 'get'],
    ], FALSE);
    return $apiSpec['values'];
  }

  /**
   * Fetch a field from the getFields list
   *
   * Searches by name, uniqueName, and api.aliases
   *
   * @param string $fieldName
   *   Field name.
   * @return NULL|mixed
   */
  protected function getField($fieldName) {
    if (!$fieldName) {
      return NULL;
    }
    if (isset($this->apiFieldSpec[$fieldName])) {
      return $this->apiFieldSpec[$fieldName];
    }
    foreach ($this->apiFieldSpec as $field) {
      if (
        $fieldName == ($field['uniqueName'] ?? NULL) ||
        array_search($fieldName, $field['api.aliases'] ?? []) !== FALSE
      ) {
        return $field;
      }
    }
    return NULL;
  }

}

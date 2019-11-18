<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Core_OptionGroup {
  public static $_values = [];
  public static $_cache = [];

  /**
   * $_domainIDGroups array maintains the list of option groups for whom
   * domainID is to be considered.
   * @var array
   */
  public static $_domainIDGroups = [
    'from_email_address',
    'grant_type',
  ];

  /**
   * @param CRM_Core_DAO $dao
   * @param bool $flip
   * @param bool $grouping
   * @param bool $localize
   * @param string $valueColumnName
   *
   * @return array
   */
  public static function &valuesCommon(
    $dao, $flip = FALSE, $grouping = FALSE,
    $localize = FALSE, $valueColumnName = 'label'
  ) {
    self::$_values = [];

    while ($dao->fetch()) {
      if ($flip) {
        if ($grouping) {
          self::$_values[$dao->value] = $dao->grouping;
        }
        else {
          self::$_values[$dao->{$valueColumnName}] = $dao->value;
        }
      }
      else {
        if ($grouping) {
          self::$_values[$dao->{$valueColumnName}] = $dao->grouping;
        }
        else {
          self::$_values[$dao->value] = $dao->{$valueColumnName};
        }
      }
    }
    if ($localize) {
      $i18n = CRM_Core_I18n::singleton();
      $i18n->localizeArray(self::$_values);
    }
    return self::$_values;
  }

  /**
   * This function retrieves all the values for the specific option group by name
   * this is primarily used to create various html based form elements
   * (radio, select, checkbox etc). OptionGroups for most cases have the
   * 'label' in the label column and the 'id' or 'name' in the value column
   *
   * @param string $name
   *   name of the option group.
   * @param bool $flip
   *   results are return in id => label format if false.
   *                            if true, the results are reversed
   * @param bool $grouping
   *   if true, return the value in 'grouping' column.
   * @param bool $localize
   *   if true, localize the results before returning.
   * @param string $condition
   *   add another condition to the sql query.
   * @param string $labelColumnName
   *   the column to use for 'label'.
   * @param bool $onlyActive
   *   return only the action option values.
   * @param bool $fresh
   *   ignore cache entries and go back to DB.
   * @param string $keyColumnName
   *   the column to use for 'key'.
   * @param string $orderBy
   *   the column to use for ordering.
   *
   * @return array
   *   The values as specified by the params
   */
  public static function &values(
    $name, $flip = FALSE, $grouping = FALSE,
    $localize = FALSE, $condition = NULL,
    $labelColumnName = 'label', $onlyActive = TRUE, $fresh = FALSE, $keyColumnName = 'value',
    $orderBy = 'weight'
  ) {
    $cache = CRM_Utils_Cache::singleton();
    $cacheKey = self::createCacheKey($name, $flip, $grouping, $localize, $condition, $labelColumnName, $onlyActive, $keyColumnName, $orderBy);

    if (!$fresh) {
      // Fetch from static var
      if (array_key_exists($cacheKey, self::$_cache)) {
        return self::$_cache[$cacheKey];
      }
      // Fetch from main cache
      self::$_cache[$cacheKey] = $cache->get($cacheKey);
      if (self::$_cache[$cacheKey] !== NULL) {
        return self::$_cache[$cacheKey];
      }
    }

    $query = "
SELECT  v.{$labelColumnName} as {$labelColumnName} ,v.{$keyColumnName} as value, v.grouping as grouping
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  v.option_group_id = g.id
  AND  g.name            = %1
  AND  g.is_active       = 1 ";

    if ($onlyActive) {
      $query .= " AND  v.is_active = 1 ";
      // Only show options for enabled components
      $componentClause = ' v.component_id IS NULL ';
      $enabledComponents = CRM_Core_Config::singleton()->enableComponents;
      if ($enabledComponents) {
        $enabledComponents = '"' . implode('","', $enabledComponents) . '"';
        $componentClause .= " OR v.component_id IN (SELECT id FROM civicrm_component WHERE name IN ($enabledComponents)) ";
      }
      $query .= " AND ($componentClause) ";
    }
    if (in_array($name, self::$_domainIDGroups)) {
      $query .= " AND v.domain_id = " . CRM_Core_Config::domainID();
    }

    if ($condition) {
      $query .= $condition;
    }

    $query .= " ORDER BY v.{$orderBy}";

    $p = [1 => [$name, 'String']];
    $dao = CRM_Core_DAO::executeQuery($query, $p);

    $var = self::valuesCommon($dao, $flip, $grouping, $localize, $labelColumnName);

    // call option value hook
    CRM_Utils_Hook::optionValues($var, $name);

    self::$_cache[$cacheKey] = $var;
    $cache->set($cacheKey, $var);

    return $var;
  }

  /**
   * Counterpart to values() which removes the item from the cache
   *
   * @param string $name
   * @param $flip
   * @param $grouping
   * @param $localize
   * @param $condition
   * @param string $labelColumnName
   * @param $onlyActive
   * @param string $keyColumnName
   */
  protected static function flushValues($name, $flip, $grouping, $localize, $condition, $labelColumnName, $onlyActive, $keyColumnName = 'value') {
    $cacheKey = self::createCacheKey($name, $flip, $grouping, $localize, $condition, $labelColumnName, $onlyActive, $keyColumnName);
    $cache = CRM_Utils_Cache::singleton();
    $cache->delete($cacheKey);
    unset(self::$_cache[$cacheKey]);
  }

  /**
   * @return string
   */
  protected static function createCacheKey($id) {
    $cacheKey = "CRM_OG_" . preg_replace('/[^a-zA-Z0-9]/', '', $id) . '_' . md5(serialize(func_get_args()));
    return $cacheKey;
  }

  /**
   * This function retrieves all the values for the specific option group by id.
   * this is primarily used to create various html based form elements
   * (radio, select, checkbox etc). OptionGroups for most cases have the
   * 'label' in the label column and the 'id' or 'name' in the value column
   *
   * @param int $id
   *   id of the option group.
   * @param bool $flip
   *   results are return in id => label format if false.
   *   if true, the results are reversed
   * @param bool $grouping
   *   if true, return the value in 'grouping' column.
   * @param bool $localize
   *   if true, localize the results before returning.
   * @param string $labelColumnName
   *   the column to use for 'label'.
   * @param bool $onlyActive
   * @param bool $fresh
   *
   * @return array
   *   Array of values as specified by the above params
   * @void
   */
  public static function &valuesByID($id, $flip = FALSE, $grouping = FALSE, $localize = FALSE, $labelColumnName = 'label', $onlyActive = TRUE, $fresh = FALSE) {
    $cacheKey = self::createCacheKey($id, $flip, $grouping, $localize, $labelColumnName, $onlyActive);

    $cache = CRM_Utils_Cache::singleton();
    if (!$fresh) {
      self::$_cache[$cacheKey] = $cache->get($cacheKey);
      if (self::$_cache[$cacheKey] !== NULL) {
        return self::$_cache[$cacheKey];
      }
    }
    $query = "
SELECT  v.{$labelColumnName} as {$labelColumnName} ,v.value as value, v.grouping as grouping
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  v.option_group_id = g.id
  AND  g.id              = %1
  AND  g.is_active       = 1
";
    if ($onlyActive) {
      $query .= " AND  v.is_active = 1 ";
    }
    $query .= " ORDER BY v.weight, v.label";

    $p = [1 => [$id, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $p);

    $var = self::valuesCommon($dao, $flip, $grouping, $localize, $labelColumnName);
    $cache->set($cacheKey, $var);

    return $var;
  }

  /**
   * Lookup titles OR ids for a set of option_value populated fields. The
   * retrieved value is assigned a new field name by id or id's by title
   * (each within a specified option_group).
   *
   * @param array $params
   *   Reference array of values submitted by the form. Based on.
   *   $flip, creates new elements in $params for each field in
   *   the $names array.
   *   If $flip = false, adds root field name => title
   *   If $flip = true, adds actual field name => id
   *
   * @param array $names
   *   Array of field names we want transformed.
   *   Array key = 'postName' (field name submitted by form in $params).
   *   Array value = array('newName' => $newName, 'groupName' => $groupName).
   *
   * @param bool $flip
   */
  public static function lookupValues(&$params, $names, $flip = FALSE) {
    foreach ($names as $postName => $value) {
      // See if $params field is in $names array (i.e. is a value that we need to lookup)
      if ($postalName = CRM_Utils_Array::value($postName, $params)) {
        $postValues = [];
        // params[$postName] may be a Ctrl+A separated value list
        if (is_string($postalName) &&
          strpos($postalName, CRM_Core_DAO::VALUE_SEPARATOR) == FALSE
        ) {
          // eliminate the ^A frm the beginning and end if present
          if (substr($postalName, 0, 1) == CRM_Core_DAO::VALUE_SEPARATOR) {
            $params[$postName] = substr($params[$postName], 1, -1);
          }
          $postValues = explode(CRM_Core_DAO::VALUE_SEPARATOR, $params[$postName]);
        }
        elseif (is_array($postalName)) {
          $postValues = $postalName;
        }
        $newValue = [];
        foreach ($postValues as $postValue) {
          if (!$postValue) {
            continue;
          }

          if ($flip) {
            $p = [1 => [$postValue, 'String']];
            $lookupBy = 'v.label= %1';
            $select = "v.value";
          }
          else {
            $p = [1 => [$postValue, 'Integer']];
            $lookupBy = 'v.value = %1';
            $select = "v.label";
          }

          $p[2] = [$value['groupName'], 'String'];
          $query = "
                        SELECT $select
                        FROM   civicrm_option_value v,
                               civicrm_option_group g
                        WHERE  v.option_group_id = g.id
                        AND    g.name            = %2
                        AND    $lookupBy";

          $newValue[] = CRM_Core_DAO::singleValueQuery($query, $p);
          $newValue = str_replace(',', '_', $newValue);
        }
        $params[$value['newName']] = implode(', ', $newValue);
      }
    }
  }

  /**
   * @deprecated - use CRM_Core_PseudoConstant::getLabel
   *
   * @param string $groupName
   * @param $value
   * @param bool $onlyActiveValue
   *
   * @return null
   */
  public static function getLabel($groupName, $value, $onlyActiveValue = TRUE) {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Core_PseudoConstant::getLabel');
    if (empty($groupName) ||
      empty($value)
    ) {
      return NULL;
    }

    $query = "
SELECT  v.label as label ,v.value as value
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  v.option_group_id = g.id
  AND  g.name            = %1
  AND  g.is_active       = 1
  AND  v.value           = %2
";
    if ($onlyActiveValue) {
      $query .= " AND  v.is_active = 1 ";
    }
    $p = [
      1 => [$groupName, 'String'],
      2 => [$value, 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $p);
    if ($dao->fetch()) {
      return $dao->label;
    }
    return NULL;
  }

  /**
   * @deprecated
   *
   * This function is not cached.
   *
   * @param string $groupName
   * @param $label
   * @param string $labelField
   * @param string $labelType
   * @param string $valueField
   *
   * @return null
   */
  public static function getValue(
    $groupName,
    $label,
    $labelField = 'label',
    $labelType = 'String',
    $valueField = 'value'
  ) {
    if (empty($label)) {
      return NULL;
    }

    CRM_Core_Error::deprecatedFunctionWarning('CRM_Core_PseudoConstant::getKey');

    $query = "
SELECT  v.label as label ,v.{$valueField} as value
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  v.option_group_id = g.id
  AND  g.name            = %1
  AND  v.is_active       = 1
  AND  g.is_active       = 1
  AND  v.$labelField     = %2
";

    $p = [
      1 => [$groupName, 'String'],
      2 => [$label, $labelType],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $p);
    if ($dao->fetch()) {
      return $dao->value;
    }
    return NULL;
  }

  /**
   * Get option_value.value from default option_value row for an option group
   *
   * @param string $groupName
   *   The name of the option group.
   *
   *
   * @return string
   *   the value from the row where is_default = true
   */
  public static function getDefaultValue($groupName) {
    if (empty($groupName)) {
      return NULL;
    }
    $query = "
SELECT v.value
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  v.option_group_id = g.id
  AND  g.name            = %1
  AND  v.is_active       = 1
  AND  g.is_active       = 1
  AND  v.is_default      = 1
";
    if (in_array($groupName, self::$_domainIDGroups)) {
      $query .= " AND v.domain_id = " . CRM_Core_Config::domainID();
    }

    $p = [1 => [$groupName, 'String']];
    return CRM_Core_DAO::singleValueQuery($query, $p);
  }

  /**
   * Creates a new option group with the passed in values.
   * @TODO: Should update the group if it already exists intelligently, so multi-lingual is
   * not messed up. Currently deletes the old group
   *
   * @param string $groupName
   *   The name of the option group - make sure there is no conflict.
   * @param array $values
   *   The associative array that has information on the option values.
   *                          the keys of this array are:
   *                          string 'title'       (required)
   *                          string 'value'       (required)
   *                          string 'name'        (optional)
   *                          string 'description' (optional)
   *                          int    'weight'      (optional) - the order in which the value are displayed
   *                          bool   'is_default'  (optional) - is this the default one to display when rendered in form
   *                          bool   'is_active'   (optional) - should this element be rendered
   * @param int $defaultID
   *   (reference) - the option value ID of the default element (if set) is returned else 'null'.
   * @param null $groupTitle
   *   The optional label of the option group else set to group name.
   *
   *
   * @return int
   *   the option group ID
   */
  public static function createAssoc($groupName, &$values, &$defaultID, $groupTitle = NULL) {
    self::deleteAssoc($groupName);
    if (!empty($values)) {
      $group = new CRM_Core_DAO_OptionGroup();
      $group->name = $groupName;
      $group->title = empty($groupTitle) ? $groupName : $groupTitle;
      $group->is_reserved = 1;
      $group->is_active = 1;
      $group->save();

      foreach ($values as $v) {
        $value = new CRM_Core_DAO_OptionValue();
        $value->option_group_id = $group->id;
        $value->label = $v['label'];
        $value->value = $v['value'];
        $value->name = CRM_Utils_Array::value('name', $v);
        $value->description = CRM_Utils_Array::value('description', $v);
        $value->weight = CRM_Utils_Array::value('weight', $v);
        $value->is_default = CRM_Utils_Array::value('is_default', $v);
        $value->is_active = CRM_Utils_Array::value('is_active', $v);
        $value->save();

        if ($value->is_default) {
          $defaultID = $value->id;
        }
      }
    }
    else {
      return $defaultID = 'null';
    }

    return $group->id;
  }

  /**
   * @param string $groupName
   * @param $values
   * @param bool $flip
   * @param string $field
   */
  public static function getAssoc($groupName, &$values, $flip = FALSE, $field = 'name') {
    $query = "
SELECT v.id as amount_id, v.value, v.label, v.name, v.description, v.weight
  FROM civicrm_option_group g,
       civicrm_option_value v
 WHERE g.id = v.option_group_id
   AND g.$field = %1
ORDER BY v.weight
";
    $params = [1 => [$groupName, 'String']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $fields = ['value', 'label', 'name', 'description', 'amount_id', 'weight'];
    if ($flip) {
      $values = [];
    }
    else {
      foreach ($fields as $field) {
        $values[$field] = [];
      }
    }
    $index = 1;

    while ($dao->fetch()) {
      if ($flip) {
        $value = [];
        foreach ($fields as $field) {
          $value[$field] = $dao->$field;
        }
        $values[$dao->amount_id] = $value;
      }
      else {
        foreach ($fields as $field) {
          $values[$field][$index] = $dao->$field;
        }
        $index++;
      }
    }
  }

  /**
   * @param string $groupName
   * @param string $operator
   */
  public static function deleteAssoc($groupName, $operator = "=") {
    $query = "
DELETE g, v
  FROM civicrm_option_group g,
       civicrm_option_value v
 WHERE g.id = v.option_group_id
   AND g.name {$operator} %1";

    $params = [1 => [$groupName, 'String']];

    $dao = CRM_Core_DAO::executeQuery($query, $params);
  }

  /**
   * @param string $groupName
   * @param $fieldValue
   * @param string $field
   * @param string $fieldType
   * @param bool $active
   * @param bool $localize
   *   if true, localize the results before returning.
   *
   * @return array
   */
  public static function getRowValues(
    $groupName, $fieldValue, $field = 'name',
    $fieldType = 'String', $active = TRUE, $localize = FALSE
  ) {
    $query = "
SELECT v.id, v.label, v.value, v.name, v.weight, v.description
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  v.option_group_id = g.id
  AND  g.name            = %1
  AND  g.is_active       = 1
  AND  v.$field          = %2
";

    if ($active) {
      $query .= " AND  v.is_active = 1";
    }

    $p = [
      1 => [$groupName, 'String'],
      2 => [$fieldValue, $fieldType],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $p);
    $row = [];

    if ($dao->fetch()) {
      foreach ([
        'id',
        'name',
        'value',
        'label',
        'weight',
        'description',
      ] as $fld) {
        $row[$fld] = $dao->$fld;
        if ($localize && in_array($fld, ['label', 'description'])) {
          $row[$fld] = ts($row[$fld]);
        }
      }
    }

    return $row;
  }

  /**
   * Wrapper for calling values with fresh set to true to empty the given value.
   *
   * Since there appears to be some inconsistency
   * (@todo remove inconsistency) around the pseudoconstant operations
   * (for example CRM_Contribution_Pseudoconstant::paymentInstrument doesn't specify isActive
   * which is part of the cache key
   * will do a couple of variations & aspire to someone cleaning it up later
   *
   * @param string $name
   * @param array $params
   */
  public static function flush($name, $params = []) {
    $defaults = [
      'flip' => FALSE,
      'grouping' => FALSE,
      'localize' => FALSE,
      'condition' => NULL,
      'labelColumnName' => 'label',
    ];

    $params = array_merge($defaults, $params);
    self::flushValues(
      $name,
      $params['flip'],
      $params['grouping'],
      $params['localize'],
      $params['condition'],
      $params['labelColumnName'],
      TRUE,
      TRUE
    );
    self::flushValues(
      $name,
      $params['flip'],
      $params['grouping'],
      $params['localize'],
      $params['condition'],
      $params['labelColumnName'],
      FALSE,
      TRUE
    );
  }

  /**
   * Flush all the places where option values are cached.
   *
   * Note that this is called from CRM_Core_PseudoConstant::flush() so we should resist
   * the intuitive urge to flush that class.
   */
  public static function flushAll() {
    self::$_values = [];
    self::$_cache = [];
    CRM_Utils_Cache::singleton()->flush();
  }

}

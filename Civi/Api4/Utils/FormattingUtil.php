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

namespace Civi\Api4\Utils;

use Civi\Api4\Query\SqlExpression;

require_once 'api/v3/utils.php';

class FormattingUtil {

  /**
   * @var string[]
   */
  public static $pseudoConstantContexts = [
    'name' => 'validate',
    'abbr' => 'abbreviate',
    'label' => 'get',
  ];

  /**
   * Massage values into the format the BAO expects for a write operation
   *
   * @param array $params
   * @param array $fields
   * @throws \CRM_Core_Exception
   */
  public static function formatWriteParams(&$params, $fields) {
    foreach ($fields as $name => $field) {
      if (!empty($params[$name])) {
        $value =& $params[$name];
        // Hack for null values -- see comment below
        if ($value === 'null') {
          $value = 'Null';
        }
        self::formatInputValue($value, $name, $field);
        // Ensure we have an array for serialized fields
        if (!empty($field['serialize']) && !is_array($value)) {
          $value = (array) $value;
        }
      }
      /*
       * Because of the wacky way that database values are saved we need to format
       * some of the values here. In this strange world the string 'null' is used to
       * unset values. If we encounter true null at this layer we change it to an empty string
       * and it will be converted to 'null' by CRM_Core_DAO::copyValues.
       *
       * If we encounter the string 'null' then we assume the user actually wants to
       * set the value to string null. However since the string null is reserved for
       * unsetting values we must change it. Another quirk of the DB_DataObject is
       * that it allows 'Null' to be set, but any other variation of string 'null'
       * will be converted to true null, e.g. 'nuLL', 'NUlL' etc. so we change it to
       * 'Null'.
       */
      elseif (array_key_exists($name, $params) && $params[$name] === NULL) {
        $params[$name] = '';
      }
    }

    \CRM_Utils_API_HTMLInputCoder::singleton()->encodeRow($params);
  }

  /**
   * Transform raw api input to appropriate format for use in a SQL query.
   *
   * This is used by read AND write actions (Get, Create, Update, Replace)
   *
   * @param $value
   * @param string|null $fieldPath
   * @param array $fieldSpec
   * @param array $params
   * @param string|null $operator (only for 'get' actions)
   * @param null $index (for recursive loops)
   * @throws \CRM_Core_Exception
   */
  public static function formatInputValue(&$value, ?string $fieldPath, array $fieldSpec, array $params = [], &$operator = NULL, $index = NULL) {
    // Evaluate pseudoconstant suffix
    $suffix = self::getSuffix($fieldPath);
    $fk = $fieldSpec['name'] == 'id' ? $fieldSpec['entity'] : $fieldSpec['fk_entity'] ?? NULL;

    // Handle special 'current_domain' option. See SpecFormatter::getOptions
    $currentDomain = ($fk === 'Domain' && in_array('current_domain', (array) $value, TRUE));
    if ($currentDomain) {
      // If the fieldName uses a suffix, convert
      $domainKey = $suffix ?: 'id';
      $domainValue = \CRM_Core_BAO_Domain::getDomain()->$domainKey;
      // If the value is an array, only convert the current_domain item
      if (is_array($value)) {
        foreach ($value as $idx => $val) {
          if ($val === 'current_domain') {
            $value[$idx] = $domainValue;
          }
        }
      }
      else {
        $value = $domainValue;
      }
    }

    // Convert option list suffix to value
    if ($suffix) {
      $options = self::getPseudoconstantList($fieldSpec, $fieldPath, $params, $operator ? 'get' : 'create');
      $value = self::replacePseudoconstant($options, $value, TRUE);
      return;
    }
    elseif (is_array($value)) {
      $i = 0;
      foreach ($value as &$val) {
        self::formatInputValue($val, $fieldPath, $fieldSpec, $params, $operator, $i++);
      }
      return;
    }

    // Special handling for 'current_user' and user lookups
    $exactMatch = [NULL, '=', '!=', '<>', 'IN', 'NOT IN', 'CONTAINS', 'NOT CONTAINS'];
    if (is_string($fk) && CoreUtil::isContact($fk) && in_array($operator, $exactMatch, TRUE)) {
      $value = self::resolveContactID($fieldSpec['name'], $value);
    }

    switch ($fieldSpec['data_type'] ?? NULL) {
      case 'Timestamp':
        $format = 'YmdHis';
        // Using `=` with a Y-m-d timestamp means we really want `BETWEEN` midnight and 11:59:59pm.
        if ($operator && is_string($value) && !array_key_exists($value, \CRM_Core_OptionGroup::values('relative_date_filters'))) {
          $isYmd = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value));
          if ($isYmd && in_array($operator, ['=', '!=', '<>'])) {
            $operator = $operator === '=' ? 'BETWEEN' : 'NOT BETWEEN';
            $dateFrom = self::formatDateValue($format, "$value 00:00:00");
            $dateTo = self::formatDateValue($format, "$value 23:59:59");
            $value = [self::formatDateValue($format, $dateFrom), self::formatDateValue($format, $dateTo)];
            break;
          }
        }
        $value = self::formatDateValue($format, $value, $operator, $index);
        break;

      case 'Date':
        $value = self::formatDateValue('Ymd', $value, $operator, $index);
        break;
    }

    $hic = \CRM_Utils_API_HTMLInputCoder::singleton();
    if (is_string($value) && $fieldPath && !$hic->isSkippedField($fieldSpec['name'])) {
      $value = $hic->encodeValue($value);
    }
  }

  /**
   * Parse date expressions.
   *
   * Expands relative date range expressions, modifying the sql operator if necessary
   *
   * @param $format
   * @param $value
   * @param $operator
   * @param $index
   * @return array|string
   */
  public static function formatDateValue($format, $value, &$operator = NULL, $index = NULL) {
    // Non-relative dates (or if no search operator)
    if (!$operator || !array_key_exists($value, \CRM_Core_OptionGroup::values('relative_date_filters'))) {
      return date($format, strtotime($value ?? ''));
    }
    if (isset($index) && !strstr($operator, 'BETWEEN')) {
      throw new \CRM_Core_Exception("Relative dates cannot be in an array using the $operator operator.");
    }
    [$dateFrom, $dateTo] = \CRM_Utils_Date::getFromTo($value);
    switch ($operator) {
      // Convert relative date filters to use BETWEEN/NOT BETWEEN operator
      case '=':
      case '!=':
      case '<>':
      case 'LIKE':
      case 'NOT LIKE':
        $operator = ($operator === '=' || $operator === 'LIKE') ? 'BETWEEN' : 'NOT BETWEEN';

        if (is_null($dateFrom) && !is_null($dateTo)) {
          $operator = ($operator === 'BETWEEN') ? '<=' : '>=';
          return self::formatDateValue($format, $dateTo);
        }
        elseif (!is_null($dateFrom) && is_null($dateTo)) {
          $operator = ($operator === 'BETWEEN') ? '>=' : '<=';
          return self::formatDateValue($format, $dateFrom);
        }
        else {
          return [self::formatDateValue($format, $dateFrom), self::formatDateValue($format, $dateTo)];
        }

        // Less-than or greater-than-equal-to comparisons use the lower value
      case '<':
      case '>=':
        return self::formatDateValue($format, $dateFrom);

      // Greater-than or less-than-equal-to comparisons use the higher value
      case '>':
      case '<=':
        return self::formatDateValue($format, $dateTo);

      // For BETWEEN expressions, we are already inside a loop of the 2 values, so give the lower value if index=0, higher value if index=1
      case 'BETWEEN':
      case 'NOT BETWEEN':
        return self::formatDateValue($format, $index ? $dateTo : $dateFrom);

      default:
        throw new \CRM_Core_Exception("Relative dates cannot be used with the $operator operator.");
    }
  }

  /**
   * Unserialize raw field values and convert to correct type
   *
   * @param array $records
   * @param array $fields
   * @param string $action
   * @param array $selectAliases
   * @throws \CRM_Core_Exception
   */
  public static function formatOutputValues(&$records, $fields, $action = 'get', $selectAliases = []) {
    $fieldExprs = [];
    foreach ($records as &$result) {
      $contactTypePaths = [];
      // Save an array of unprocessed values which are useful when replacing pseudocontants
      $rawValues = $result;
      foreach ($rawValues as $key => $value) {
        // Pseudoconstants haven't been replaced yet so strip suffixes from raw values
        if (strpos($key, ':') > strrpos($key, ')')) {
          [$fieldName] = explode(':', $key);
          $rawValues[$fieldName] = $value;
          unset($rawValues[$key]);
        }
      }
      foreach ($result as $key => $value) {
        // Skip values that have already been unset by `formatOutputValue` functions
        if (!array_key_exists($key, $result)) {
          continue;
        }
        // Use ??= to only convert each column once
        $fieldExprs[$key] ??= SqlExpression::convert($selectAliases[$key] ?? $key);
        $fieldExpr = $fieldExprs[$key];
        $fieldName = \CRM_Utils_Array::first($fieldExpr->getFields());
        $baseName = $fieldName ? \CRM_Utils_Array::first(explode(':', $fieldName)) : NULL;
        $field = $fields[$fieldName] ?? $fields[$baseName] ?? NULL;
        $dataType = $field['data_type'] ?? ($fieldName == 'id' ? 'Integer' : NULL);
        // Allow Sql Functions to alter the value and/or $dataType
        if (method_exists($fieldExpr, 'formatOutputValue') && is_string($value)) {
          $fieldExpr->formatOutputValue($dataType, $result, $key);
          $value = $result[$key];
        }
        if (!empty($field['output_formatters'])) {
          self::applyFormatters($result, $fieldExpr, $field, $value);
          $dataType = NULL;
        }
        // Evaluate pseudoconstant suffixes
        $suffix = self::getSuffix($fieldName);
        $fieldOptions = NULL;
        if (isset($value) && $suffix) {
          $fieldOptions = self::getPseudoconstantList($field, $fieldName, $rawValues, $action);
          $dataType = NULL;
        }
        // Store contact_type value before replacing pseudoconstant (e.g. transforming it to contact_type:label)
        // Used by self::contactFieldsToRemove below
        if ($value && isset($field['entity']) && $field['entity'] === 'Contact' && $field['name'] === 'contact_type') {
          $prefix = strrpos($fieldName, '.');
          $contactTypePaths[$prefix ? substr($fieldName, 0, $prefix + 1) : ''] = $value;
        }
        if ($fieldExpr->supportsExpansion) {
          if (!empty($field['serialize']) && is_string($value)) {
            $value = \CRM_Core_DAO::unSerializeField($value, $field['serialize']);
          }
          if (isset($fieldOptions)) {
            $value = self::replacePseudoconstant($fieldOptions, $value);
          }
        }
        $result[$key] = self::convertDataType($value, $dataType);
      }
      // Remove inapplicable contact fields
      foreach ($contactTypePaths as $prefix => $contactType) {
        \CRM_Utils_Array::remove($result, self::contactFieldsToRemove($contactType, $prefix));
      }
    }
  }

  /**
   * Get options associated with an entity field
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public static function getFieldOptions(array $field, array $values = [], bool $includeDisabled = FALSE, bool $checkPermissions = FALSE, ?int $userId = NULL): ?array {
    $fieldName = $field['name'];
    $entityName = $field['entity'];
    $customGroupName = CoreUtil::getCustomGroupName($entityName);
    if ($customGroupName) {
      $entityName = \CRM_Core_BAO_CustomGroup::getEntityForGroup($customGroupName);
      $fieldName = $customGroupName . '.' . $fieldName;
    }
    // TODO: Teach Civi::entity to return contact-type pseudo-entities
    elseif (CoreUtil::isContact($entityName)) {
      $entityName = 'Contact';
    }

    $entity = \Civi::entity($entityName);
    return $entity->getOptions($fieldName, $values, $includeDisabled, $checkPermissions, $userId);
  }

  /**
   * Retrieves pseudoconstant option list for a field.
   *
   * @param array $field
   * @param string $fieldAlias
   *   Field path plus pseudoconstant suffix, e.g. 'contact.employer_id.contact_sub_type:label'
   * @param array $values
   *   Other values for this object
   * @param string $action
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function getPseudoconstantList(array $field, string $fieldAlias, $values = [], $action = 'get') {
    $valueType = self::getSuffix($fieldAlias);
    // For create actions, only unique identifiers can be used.
    // For get actions any valid suffix is ok.
    if (!$valueType || ($action === 'create' && !isset(self::$pseudoConstantContexts[$valueType]))) {
      throw new \CRM_Core_Exception('Illegal expression');
    }
    $fieldPath = self::removeSuffix($fieldAlias);

    $entityValues = self::filterByPath($values, $fieldPath, $field['name']);
    try {
      $options = self::getFieldOptions($field, $entityValues, TRUE);
    }
    catch (\CRM_Core_Exception $e) {
      // Entity not in Civi (api-only) will use fallback below
    }
    // Fallback for option lists that only exist in the api but not in core
    $options ??= civicrm_api4($field['entity'], 'getFields', ['checkPermissions' => FALSE, 'action' => $action, 'loadOptions' => ['id', $valueType], 'where' => [['name', '=', $field['name']]]])[0]['options'] ?? NULL;

    $options = $options ? array_column($options, $valueType, 'id') : $options;
    if (is_array($options)) {
      return $options;
    }
    throw new \CRM_Core_Exception("No option list found for '{$field['name']}'");
  }

  /**
   * Replaces value (or an array of values) with options from a pseudoconstant list.
   *
   * The direction of lookup defaults to transforming ids to option values for api output;
   * for api input, set $reverse = TRUE to transform option values to ids.
   *
   * @param array $options
   * @param string|string[] $value
   * @param bool $reverse
   *   Is this a reverse lookup (for transforming input instead of output)
   * @return array|mixed|null
   */
  public static function replacePseudoconstant($options, $value, $reverse = FALSE) {
    $matches = [];
    foreach ((array) $value as $val) {
      if (!$reverse && isset($options[$val])) {
        $matches[] = $options[$val];
      }
      elseif ($reverse && array_search($val, $options) !== FALSE) {
        $matches[] = array_search($val, $options);
      }
    }
    return is_array($value) ? $matches : $matches[0] ?? NULL;
  }

  /**
   * Apply a field's output_formatters callback functions
   *
   * @param array $result
   * @param \Civi\Api4\Query\SqlExpression $fieldExpr
   * @param array $fieldDefn
   * @param mixed $value
   */
  private static function applyFormatters(array $result, SqlExpression $fieldExpr, array $fieldDefn, &$value): void {
    $fieldPath = \CRM_Utils_Array::first($fieldExpr->getFields());
    $row = self::filterByPath($result, $fieldPath, $fieldDefn['name']);

    // For aggregated array data, apply the formatter to each item
    if (is_array($value) && $fieldExpr->getType() === 'SqlFunction' && $fieldExpr::getCategory() === 'aggregate') {
      foreach ($value as $index => &$val) {
        $subRow = $row;
        foreach ($row as $rowKey => $rowValue) {
          if (is_array($rowValue) && array_key_exists($index, $rowValue)) {
            $subRow[$rowKey] = $rowValue[$index];
          }
        }
        self::applyFormatter($fieldDefn, $subRow, $val);
      }
    }
    else {
      self::applyFormatter($fieldDefn, $row, $value);
    }
  }

  private static function applyFormatter(array $fieldDefn, array $row, &$value): void {
    foreach ($fieldDefn['output_formatters'] as $formatter) {
      $formatter($value, $row, $fieldDefn);
    }
  }

  /**
   * @param mixed $value
   * @param string $dataType
   * @return mixed
   */
  public static function convertDataType($value, $dataType) {
    if (isset($value) && $dataType) {
      if (is_array($value)) {
        foreach ($value as $key => $val) {
          $value[$key] = self::convertDataType($val, $dataType);
        }
        return $value;
      }

      switch ($dataType) {
        case 'Boolean':
          return (bool) $value;

        case 'Integer':
          return (int) $value;

        case 'Money':
        case 'Float':
          return (float) $value;

        case 'Timestamp':
        case 'Date':
          // Convert mysql-style default to api-style default
          if (str_contains($value, 'CURRENT_TIMESTAMP')) {
            return 'now';
          }
          // Strip time from date-only fields
          if ($dataType === 'Date' && $value) {
            return substr($value, 0, 10);
          }
      }
    }
    return $value;
  }

  /**
   * Lists all field names (including suffixed variants) that should be removed for a given contact type.
   *
   * @param string $contactType
   *   Individual|Organization|Household
   * @param string $prefix
   *   Path at which these fields are found, e.g. "address.contact."
   * @return array
   */
  public static function contactFieldsToRemove($contactType, $prefix): array {
    if (!$contactType || !is_string($contactType)) {
      return [];
    }
    if (!isset(\Civi::$statics[__CLASS__][__FUNCTION__][$contactType])) {
      \Civi::$statics[__CLASS__][__FUNCTION__][$contactType] = [];
      foreach (\CRM_Contact_DAO_Contact::fields() as $field) {
        if (!empty($field['contactType']) && $field['contactType'] != $contactType) {
          \Civi::$statics[__CLASS__][__FUNCTION__][$contactType][] = $field['name'];
          // Include suffixed variants like prefix_id:label
          if (!empty($field['pseudoconstant'])) {
            foreach (array_keys(\CRM_Core_SelectValues::optionAttributes()) as $suffix) {
              \Civi::$statics[__CLASS__][__FUNCTION__][$contactType][] = $field['name'] . ':' . $suffix;
            }
          }
        }
      }
    }
    // Add prefix paths
    return array_map(function($name) use ($prefix) {
      return $prefix . $name;
    }, \Civi::$statics[__CLASS__][__FUNCTION__][$contactType]);
  }

  /**
   * Given a field belonging to either the main entity or a joined entity,
   * and a values array of [path => value], this returns all values which share the same root path.
   *
   * Note: Unlike CRM_Utils_Array::filterByPrefix this does not mutate the original array.
   *
   * Ex:
   * ```
   * $values = [
   *   'first_name' => 'a',
   *   'middle_name' => 'b',
   *   'related_contact.first_name' => 'c',
   *   'related_contact.last_name' => 'd',
   *   'activity.subject' => 'e',
   * ]
   * $fieldPath = 'related_contact.id'
   * $fieldName = 'id'
   *
   * filterByPrefix($values, $fieldPath, $fieldName)
   * returns [
   *   'first_name' => 'c',
   *   'last_name' => 'd',
   * ]
   * ```
   *
   * @param array $values
   * @param string $fieldPath
   * @param string $fieldName
   * @return array
   */
  public static function filterByPath(array $values, string $fieldPath, string $fieldName): array {
    $prefix = substr($fieldPath, 0, strrpos($fieldPath, $fieldName));
    return \CRM_Utils_Array::filterByPrefix($values, $prefix);
  }

  /**
   * A contact ID field passed in to the API may contain values such as "user_contact_id"
   *   which need to be resolved to the actual contact ID.
   * This function resolves those strings to the actual contact ID or throws an exception on "unknown user"
   *
   * @param string $fieldName
   * @param string|int|null $fieldValue
   *
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  public static function resolveContactID(string $fieldName, $fieldValue): ?int {
    // Special handling for 'current_user' and user lookups
    if (isset($fieldValue) && !is_numeric($fieldValue)) {
      // FIXME decouple from v3 API
      require_once 'api/v3/utils.php';
      $fieldValue = \_civicrm_api3_resolve_contactID($fieldValue);
      if ('unknown-user' === $fieldValue) {
        throw new \CRM_Core_Exception("\"{$fieldName}\" \"{$fieldValue}\" cannot be resolved to a contact ID", 2002, ['error_field' => $fieldName, 'type' => 'integer']);
      }
    }
    return $fieldValue;
  }

  /**
   * Returns the suffix from a given field name if it exists and matches known suffixes.
   *
   * @param string|null $fieldName
   *   The name of the field, potentially containing a suffix in the format ":suffix".
   * @return string|null
   *   The extracted suffix if found and recognized; otherwise, NULL.
   */
  public static function getSuffix(?string $fieldName): ?string {
    if (!$fieldName || !str_contains($fieldName, ':')) {
      return NULL;
    }

    $allSuffixes = array_keys(\CRM_Core_SelectValues::optionAttributes());
    foreach ($allSuffixes as $suffix) {
      if (str_ends_with($fieldName, ":$suffix")) {
        return $suffix;
      }
    }
    return NULL;
  }

  /**
   * Removes the suffix from a given field name, if a suffix is detected.
   *
   * @param string $fieldName The name of the field to process.
   * @return string The field name without its suffix, or the original field name if no suffix exists.
   */
  public static function removeSuffix(string $fieldName): string {
    $suffix = self::getSuffix($fieldName);
    if ($suffix) {
      return substr($fieldName, 0, -1 - strlen($suffix));
    }
    return $fieldName;
  }

}

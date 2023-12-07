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
   * @var string[]
   */
  public static $pseudoConstantSuffixes = ['name', 'abbr', 'label', 'color', 'description', 'icon', 'grouping', 'url'];

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
        if (!empty($field['serialize'] && !is_array($value))) {
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
    $suffix = str_replace(':', '', strstr(($fieldPath ?? ''), ':'));
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
    if ($fk === 'Contact' && isset($value) && !is_numeric($value)) {
      $value = \_civicrm_api3_resolve_contactID($value);
      if ('unknown-user' === $value) {
        throw new \CRM_Core_Exception("\"{$fieldSpec['name']}\" \"{$value}\" cannot be resolved to a contact ID", 2002, ['error_field' => $fieldSpec['name'], "type" => "integer"]);
      }
    }

    switch ($fieldSpec['data_type'] ?? NULL) {
      case 'Timestamp':
        $value = self::formatDateValue('YmdHis', $value, $operator, $index);
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
   * Unserialize raw DAO values and convert to correct type
   *
   * @param array $result
   * @param array $fields
   * @param string $action
   * @param array $selectAliases
   * @throws \CRM_Core_Exception
   */
  public static function formatOutputValues(&$result, $fields, $action = 'get', $selectAliases = []) {
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
      // Skip null values or values that have already been unset by `formatOutputValue` functions
      if (!isset($result[$key])) {
        continue;
      }
      $fieldExpr = SqlExpression::convert($selectAliases[$key] ?? $key);
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
      $suffix = strrpos(($fieldName ?? ''), ':');
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
    [$fieldPath, $valueType] = explode(':', $fieldAlias);
    $context = self::$pseudoConstantContexts[$valueType] ?? NULL;
    // For create actions, only unique identifiers can be used.
    // For get actions any valid suffix is ok.
    if (($action === 'create' && !$context) || !in_array($valueType, self::$pseudoConstantSuffixes, TRUE)) {
      throw new \CRM_Core_Exception('Illegal expression');
    }
    $baoName = $context ? CoreUtil::getBAOFromApiName($field['entity']) : NULL;
    // Use BAO::buildOptions if possible
    if ($baoName) {
      $fieldName = empty($field['custom_field_id']) ? $field['name'] : 'custom_' . $field['custom_field_id'];
      $options = $baoName::buildOptions($fieldName, $context, self::filterByPath($values, $fieldPath, $field['name']));
    }
    // Fallback for option lists that exist in the api but not the BAO
    if (!isset($options) || $options === FALSE) {
      $options = civicrm_api4($field['entity'], 'getFields', ['checkPermissions' => FALSE, 'action' => $action, 'loadOptions' => ['id', $valueType], 'where' => [['name', '=', $field['name']]]])[0]['options'] ?? NULL;
      $options = $options ? array_column($options, $valueType, 'id') : $options;
    }
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
  public static function contactFieldsToRemove($contactType, $prefix) {
    if (!isset(\Civi::$statics[__CLASS__][__FUNCTION__][$contactType])) {
      \Civi::$statics[__CLASS__][__FUNCTION__][$contactType] = [];
      foreach (\CRM_Contact_DAO_Contact::fields() as $field) {
        if (!empty($field['contactType']) && $field['contactType'] != $contactType) {
          \Civi::$statics[__CLASS__][__FUNCTION__][$contactType][] = $field['name'];
          // Include suffixed variants like prefix_id:label
          if (!empty($field['pseudoconstant'])) {
            foreach (self::$pseudoConstantSuffixes as $suffix) {
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

}

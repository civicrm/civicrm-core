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
  public static $pseudoConstantSuffixes = ['name', 'abbr', 'label', 'color', 'description', 'icon'];

  /**
   * Massage values into the format the BAO expects for a write operation
   *
   * @param array $params
   * @param array $fields
   * @throws \API_Exception
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
       * unset values. Hence if we encounter true null we change it to string 'null'.
       *
       * If we encounter the string 'null' then we assume the user actually wants to
       * set the value to string null. However since the string null is reserved for
       * unsetting values we must change it. Another quirk of the DB_DataObject is
       * that it allows 'Null' to be set, but any other variation of string 'null'
       * will be converted to true null, e.g. 'nuLL', 'NUlL' etc. so we change it to
       * 'Null'.
       */
      elseif (array_key_exists($name, $params) && $params[$name] === NULL) {
        $params[$name] = 'null';
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
   * @param string $fieldName
   * @param array $fieldSpec
   * @param string $operator (only for 'get' actions)
   * @param int $index (for recursive loops)
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public static function formatInputValue(&$value, $fieldName, $fieldSpec, &$operator = NULL, $index = NULL) {
    // Evaluate pseudoconstant suffix
    $suffix = strpos($fieldName, ':');
    if ($suffix) {
      $options = self::getPseudoconstantList($fieldSpec, $fieldName, [], $operator ? 'get' : 'create');
      $value = self::replacePseudoconstant($options, $value, TRUE);
      return;
    }
    elseif (is_array($value)) {
      $i = 0;
      foreach ($value as &$val) {
        self::formatInputValue($val, $fieldName, $fieldSpec, $operator, $i++);
      }
      return;
    }
    $fk = $fieldSpec['name'] == 'id' ? $fieldSpec['entity'] : $fieldSpec['fk_entity'] ?? NULL;

    if ($fk === 'Domain' && $value === 'current_domain') {
      $value = \CRM_Core_Config::domainID();
    }

    if ($fk === 'Contact' && !is_numeric($value)) {
      $value = \_civicrm_api3_resolve_contactID($value);
      if ('unknown-user' === $value) {
        throw new \API_Exception("\"{$fieldSpec['name']}\" \"{$value}\" cannot be resolved to a contact ID", 2002, ['error_field' => $fieldSpec['name'], "type" => "integer"]);
      }
    }

    switch ($fieldSpec['data_type'] ?? NULL) {
      case 'Timestamp':
        $value = self::formatDateValue('Y-m-d H:i:s', $value, $operator, $index);
        break;

      case 'Date':
        $value = self::formatDateValue('Ymd', $value, $operator, $index);
        break;
    }

    $hic = \CRM_Utils_API_HTMLInputCoder::singleton();
    if (is_string($value) && !$hic->isSkippedField($fieldSpec['name'])) {
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
  private static function formatDateValue($format, $value, &$operator = NULL, $index = NULL) {
    // Non-relative dates (or if no search operator)
    if (!$operator || !array_key_exists($value, \CRM_Core_OptionGroup::values('relative_date_filters'))) {
      return date($format, strtotime($value));
    }
    if (isset($index) && !strstr($operator, 'BETWEEN')) {
      throw new \API_Exception("Relative dates cannot be in an array using the $operator operator.");
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
        return [self::formatDateValue($format, $dateFrom), self::formatDateValue($format, $dateTo)];

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
        throw new \API_Exception("Relative dates cannot be used with the $operator operator.");
    }
  }

  /**
   * Unserialize raw DAO values and convert to correct type
   *
   * @param array $results
   * @param array $fields
   * @param string $action
   * @param array $selectAliases
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public static function formatOutputValues(&$results, $fields, $action = 'get', $selectAliases = []) {
    $fieldOptions = [];
    foreach ($results as &$result) {
      $contactTypePaths = [];
      foreach ($result as $key => $value) {
        $fieldExpr = SqlExpression::convert($selectAliases[$key] ?? $key);
        $fieldName = \CRM_Utils_Array::first($fieldExpr->getFields());
        $baseName = $fieldName ? \CRM_Utils_Array::first(explode(':', $fieldName)) : NULL;
        $field = $fields[$fieldName] ?? $fields[$baseName] ?? NULL;
        $dataType = $field['data_type'] ?? ($fieldName == 'id' ? 'Integer' : NULL);
        // Allow Sql Functions to do special formatting and/or alter the $dataType
        if (method_exists($fieldExpr, 'formatOutputValue') && is_string($value)) {
          $result[$key] = $value = $fieldExpr->formatOutputValue($value, $dataType);
        }
        if (!empty($field['output_formatters'])) {
          self::applyFormatters($result, $fieldName, $field, $value);
          $dataType = NULL;
        }
        // Evaluate pseudoconstant suffixes
        $suffix = strrpos($fieldName, ':');
        if ($suffix) {
          $fieldOptions[$fieldName] = $fieldOptions[$fieldName] ?? self::getPseudoconstantList($field, $fieldName, $result, $action);
          $dataType = NULL;
        }
        if ($fieldExpr->supportsExpansion) {
          if (!empty($field['serialize']) && is_string($value)) {
            $value = \CRM_Core_DAO::unSerializeField($value, $field['serialize']);
          }
          if (isset($fieldOptions[$fieldName])) {
            $value = self::replacePseudoconstant($fieldOptions[$fieldName], $value);
          }
        }
        // Keep track of contact types for self::contactFieldsToRemove
        if ($value && isset($field['entity']) && $field['entity'] === 'Contact' && $field['name'] === 'contact_type') {
          $prefix = strrpos($fieldName, '.');
          $contactTypePaths[$prefix ? substr($fieldName, 0, $prefix + 1) : ''] = $value;
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
   * Retrieves pseudoconstant option list for a field.
   *
   * @param array $field
   * @param string $fieldAlias
   *   Field path plus pseudoconstant suffix, e.g. 'contact.employer_id.contact_sub_type:label'
   * @param array $params
   *   Other values for this object
   * @param string $action
   * @return array
   * @throws \API_Exception
   */
  public static function getPseudoconstantList(array $field, string $fieldAlias, $params = [], $action = 'get') {
    [$fieldPath, $valueType] = explode(':', $fieldAlias);
    $context = self::$pseudoConstantContexts[$valueType] ?? NULL;
    // For create actions, only unique identifiers can be used.
    // For get actions any valid suffix is ok.
    if (($action === 'create' && !$context) || !in_array($valueType, self::$pseudoConstantSuffixes, TRUE)) {
      throw new \API_Exception('Illegal expression');
    }
    $baoName = $context ? CoreUtil::getBAOFromApiName($field['entity']) : NULL;
    // Use BAO::buildOptions if possible
    if ($baoName) {
      $fieldName = empty($field['custom_field_id']) ? $field['name'] : 'custom_' . $field['custom_field_id'];
      $options = $baoName::buildOptions($fieldName, $context, self::filterByPrefix($params, $fieldPath, $field['name']));
    }
    // Fallback for option lists that exist in the api but not the BAO
    if (!isset($options) || $options === FALSE) {
      $options = civicrm_api4($field['entity'], 'getFields', ['action' => $action, 'loadOptions' => ['id', $valueType], 'where' => [['name', '=', $field['name']]]])[0]['options'] ?? NULL;
      $options = $options ? array_column($options, $valueType, 'id') : $options;
    }
    if (is_array($options)) {
      return $options;
    }
    throw new \API_Exception("No option list found for '{$field['name']}'");
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
   * @param string $fieldPath
   * @param array $field
   * @param mixed $value
   */
  private static function applyFormatters(array $result, string $fieldPath, array $field, &$value) {
    $row = self::filterByPrefix($result, $fieldPath, $field['name']);

    foreach ($field['output_formatters'] as $formatter) {
      $formatter($value, $row, $field);
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
   * Works by filtering array keys to only include those with the same prefix as a given field,
   * stripping them of that prefix.
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
  public static function filterByPrefix(array $values, string $fieldPath, string $fieldName): array {
    $filtered = [];
    $prefix = substr($fieldPath, 0, strpos($fieldPath, $fieldName));
    foreach ($values as $key => $val) {
      if (!$prefix || strpos($key, $prefix) === 0) {
        $filtered[substr($key, strlen($prefix))] = $val;
      }
    }
    return $filtered;
  }

}

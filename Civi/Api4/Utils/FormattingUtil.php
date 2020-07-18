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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace Civi\Api4\Utils;

require_once 'api/v3/utils.php';

class FormattingUtil {

  public static $pseudoConstantContexts = [
    'name' => 'validate',
    'abbr' => 'abbreviate',
    'label' => 'get',
  ];

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
        self::formatInputValue($value, $name, $field, 'create');
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
   * @param string $action
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public static function formatInputValue(&$value, $fieldName, $fieldSpec, $action = 'get') {
    // Evaluate pseudoconstant suffix
    $suffix = strpos($fieldName, ':');
    if ($suffix) {
      $options = self::getPseudoconstantList($fieldSpec['entity'], $fieldSpec['name'], substr($fieldName, $suffix + 1), $action);
      $value = self::replacePseudoconstant($options, $value, TRUE);
      return;
    }
    elseif (is_array($value)) {
      foreach ($value as &$val) {
        self::formatInputValue($val, $fieldName, $fieldSpec, $action);
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
        $value = date('Y-m-d H:i:s', strtotime($value));
        break;

      case 'Date':
        $value = date('Ymd', strtotime($value));
        break;
    }

    $hic = \CRM_Utils_API_HTMLInputCoder::singleton();
    if (!$hic->isSkippedField($fieldSpec['name'])) {
      $value = $hic->encodeValue($value);
    }
  }

  /**
   * Unserialize raw DAO values and convert to correct type
   *
   * @param array $results
   * @param array $fields
   * @param string $entity
   * @param string $action
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public static function formatOutputValues(&$results, $fields, $entity, $action = 'get') {
    $fieldOptions = [];
    foreach ($results as &$result) {
      $contactTypePaths = [];
      foreach ($result as $fieldExpr => $value) {
        $field = $fields[$fieldExpr] ?? NULL;
        $dataType = $field['data_type'] ?? ($fieldExpr == 'id' ? 'Integer' : NULL);
        if ($field) {
          // Evaluate pseudoconstant suffixes
          $suffix = strrpos($fieldExpr, ':');
          if ($suffix) {
            $fieldName = empty($field['custom_field_id']) ? $field['name'] : 'custom_' . $field['custom_field_id'];
            $fieldOptions[$fieldExpr] = $fieldOptions[$fieldExpr] ?? self::getPseudoconstantList($field['entity'], $fieldName, substr($fieldExpr, $suffix + 1), $result, $action);
            $dataType = NULL;
          }
          if (!empty($field['serialize'])) {
            if (is_string($value)) {
              $value = \CRM_Core_DAO::unSerializeField($value, $field['serialize']);
            }
          }
          if (isset($fieldOptions[$fieldExpr])) {
            $value = self::replacePseudoconstant($fieldOptions[$fieldExpr], $value);
          }
          // Keep track of contact types for self::contactFieldsToRemove
          if ($value && isset($field['entity']) && $field['entity'] === 'Contact' && $field['name'] === 'contact_type') {
            $prefix = strrpos($fieldExpr, '.');
            $contactTypePaths[$prefix ? substr($fieldExpr, 0, $prefix + 1) : ''] = $value;
          }
        }
        $result[$fieldExpr] = self::convertDataType($value, $dataType);
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
   * @param string $entity
   *   Name of api entity
   * @param string $fieldName
   * @param string $valueType
   *   name|label|abbr from self::$pseudoConstantContexts
   * @param array $params
   *   Other values for this object
   * @param string $action
   * @return array
   * @throws \API_Exception
   */
  public static function getPseudoconstantList($entity, $fieldName, $valueType, $params = [], $action = 'get') {
    $context = self::$pseudoConstantContexts[$valueType] ?? NULL;
    // For create actions, only unique identifiers can be used.
    // For get actions any valid suffix is ok.
    if (($action === 'create' && !$context) || !in_array($valueType, self::$pseudoConstantSuffixes, TRUE)) {
      throw new \API_Exception('Illegal expression');
    }
    $baoName = $context ? CoreUtil::getBAOFromApiName($entity) : NULL;
    // Use BAO::buildOptions if possible
    if ($baoName) {
      $options = $baoName::buildOptions($fieldName, $context, $params);
    }
    // Fallback for option lists that exist in the api but not the BAO
    if (!isset($options) || $options === FALSE) {
      $options = civicrm_api4($entity, 'getFields', ['action' => $action, 'loadOptions' => ['id', $valueType], 'where' => [['name', '=', $fieldName]]])[0]['options'] ?? NULL;
      $options = $options ? array_column($options, $valueType, 'id') : $options;
    }
    if (is_array($options)) {
      return $options;
    }
    throw new \API_Exception("No option list found for '$fieldName'");
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

}

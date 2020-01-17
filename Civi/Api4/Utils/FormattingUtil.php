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
 * $Id$
 *
 */


namespace Civi\Api4\Utils;

require_once 'api/v3/utils.php';

class FormattingUtil {

  /**
   * Massage values into the format the BAO expects for a write operation
   *
   * @param $params
   * @param $entity
   * @param $fields
   * @throws \API_Exception
   */
  public static function formatWriteParams(&$params, $entity, $fields) {
    foreach ($fields as $name => $field) {
      if (!empty($params[$name])) {
        $value =& $params[$name];
        // Hack for null values -- see comment below
        if ($value === 'null') {
          $value = 'Null';
        }
        self::formatInputValue($value, $field, $entity);
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
   * @param $fieldSpec
   * @param string $entity
   *   Ex: 'Contact', 'Domain'
   * @throws \API_Exception
   */
  public static function formatInputValue(&$value, $fieldSpec, $entity) {
    if (is_array($value)) {
      foreach ($value as &$val) {
        self::formatInputValue($val, $fieldSpec, $entity);
      }
      return;
    }
    $fk = $fieldSpec['name'] == 'id' ? $entity : $fieldSpec['fk_entity'] ?? NULL;

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
   * @throws \CRM_Core_Exception
   */
  public static function formatOutputValues(&$results, $fields, $entity) {
    foreach ($results as &$result) {
      // Remove inapplicable contact fields
      if ($entity === 'Contact' && !empty($result['contact_type'])) {
        \CRM_Utils_Array::remove($result, self::contactFieldsToRemove($result['contact_type']));
      }
      foreach ($result as $field => $value) {
        $dataType = $fields[$field]['data_type'] ?? ($field == 'id' ? 'Integer' : NULL);
        if (!empty($fields[$field]['serialize'])) {
          if (is_string($value)) {
            $result[$field] = $value = \CRM_Core_DAO::unSerializeField($value, $fields[$field]['serialize']);
            foreach ($value as $key => $val) {
              $result[$field][$key] = self::convertDataType($val, $dataType);
            }
          }
        }
        else {
          $result[$field] = self::convertDataType($value, $dataType);
        }
      }
    }
  }

  /**
   * @param mixed $value
   * @param string $dataType
   * @return mixed
   */
  public static function convertDataType($value, $dataType) {
    if (isset($value)) {
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
   * @param string $contactType
   * @return array
   */
  public static function contactFieldsToRemove($contactType) {
    if (!isset(\Civi::$statics[__CLASS__][__FUNCTION__][$contactType])) {
      \Civi::$statics[__CLASS__][__FUNCTION__][$contactType] = [];
      foreach (\CRM_Contact_DAO_Contact::fields() as $field) {
        if (!empty($field['contactType']) && $field['contactType'] != $contactType) {
          \Civi::$statics[__CLASS__][__FUNCTION__][$contactType][] = $field['name'];
        }
      }
    }
    return \Civi::$statics[__CLASS__][__FUNCTION__][$contactType];
  }

}

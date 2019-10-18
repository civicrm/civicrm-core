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
 * $Id$
 *
 */


namespace Civi\Api4\Utils;

use CRM_Utils_Array as UtilsArray;

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
        FormattingUtil::formatValue($value, $field, $entity);
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
  public static function formatValue(&$value, $fieldSpec, $entity) {
    if (is_array($value)) {
      foreach ($value as &$val) {
        self::formatValue($val, $fieldSpec, $entity);
      }
      return;
    }
    $fk = UtilsArray::value('fk_entity', $fieldSpec);
    if ($fieldSpec['name'] == 'id') {
      $fk = $entity;
    }
    $dataType = UtilsArray::value('data_type', $fieldSpec);

    if ($fk === 'Domain' && $value === 'current_domain') {
      $value = \CRM_Core_Config::domainID();
    }

    if ($fk === 'Contact' && !is_numeric($value)) {
      $value = \_civicrm_api3_resolve_contactID($value);
      if ('unknown-user' === $value) {
        throw new \API_Exception("\"{$fieldSpec['name']}\" \"{$value}\" cannot be resolved to a contact ID", 2002, ['error_field' => $fieldSpec['name'], "type" => "integer"]);
      }
    }

    switch ($dataType) {
      case 'Timestamp':
        $value = date('Y-m-d H:i:s', strtotime($value));
        break;

      case 'Date':
        $value = date('Ymd', strtotime($value));
        break;
    }
  }

}

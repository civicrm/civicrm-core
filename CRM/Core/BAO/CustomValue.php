<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * Business objects for managing custom data values.
 *
 */
class CRM_Core_BAO_CustomValue extends CRM_Core_DAO {

  /**
   * Validate a value against a CustomField type
   *
   * @param string $type  The type of the data
   * @param string $value The data to be validated
   *
   * @return boolean True if the value is of the specified type
   * @access public
   * @static
   */
  public static function typecheck($type, $value) {
    switch ($type) {
      case 'Memo':
        return TRUE;

      case 'String':
        return CRM_Utils_Rule::string($value);

      case 'Int':
        return CRM_Utils_Rule::integer($value);

      case 'Float':
      case 'Money':
        return CRM_Utils_Rule::numeric($value);

      case 'Date':
        if (is_numeric($value)) {
          return CRM_Utils_Rule::dateTime($value);
        }
        else {
          return CRM_Utils_Rule::date($value);
        }
      case 'Boolean':
        return CRM_Utils_Rule::boolean($value);

      case 'ContactReference':
        return CRM_Utils_Rule::validContact($value);

      case 'StateProvince':

        //fix for multi select state, CRM-3437
        $valid = FALSE;
        $mulValues = explode(',', $value);
        foreach ($mulValues as $key => $state) {
          $valid = array_key_exists(strtolower(trim($state)),
            array_change_key_case(array_flip(CRM_Core_PseudoConstant::stateProvinceAbbreviation()), CASE_LOWER)
          ) || array_key_exists(strtolower(trim($state)),
            array_change_key_case(array_flip(CRM_Core_PseudoConstant::stateProvince()), CASE_LOWER)
          );
          if (!$valid) {
            break;
          }
        }
        return $valid;

      case 'Country':

        //fix multi select country, CRM-3437
        $valid = FALSE;
        $mulValues = explode(',', $value);
        foreach ($mulValues as $key => $country) {
          $valid = array_key_exists(strtolower(trim($country)),
            array_change_key_case(array_flip(CRM_Core_PseudoConstant::countryIsoCode()), CASE_LOWER)
          ) || array_key_exists(strtolower(trim($country)),
            array_change_key_case(array_flip(CRM_Core_PseudoConstant::country()), CASE_LOWER)
          );
          if (!$valid) {
            break;
          }
        }
        return $valid;

      case 'Link':
        return CRM_Utils_Rule::url($value);
    }
    return FALSE;
  }

  /**
   * given a 'civicrm' type string, return the mysql data store area
   *
   * @param string $type the civicrm type string
   *
   * @return the mysql data store placeholder
   * @access public
   * @static
   */
  public static function typeToField($type) {
    switch ($type) {
      case 'String':
      case 'File':
        return 'char_data';

      case 'Boolean':
      case 'Int':
      case 'StateProvince':
      case 'Country':
      case 'Auto-complete':
        return 'int_data';

      case 'Float':
        return 'float_data';

      case 'Money':
        return 'decimal_data';

      case 'Memo':
        return 'memo_data';

      case 'Date':
        return 'date_data';

      case 'Link':
        return 'char_data';

      default:
        return NULL;
    }
  }


  /**
   * @param $formValues
   */
  public static function fixFieldValueOfTypeMemo(&$formValues) {
    if (empty($formValues)) {
      return NULL;
    }
    foreach (array_keys($formValues) as $key) {
      if (substr($key, 0, 7) != 'custom_') {
        continue;
      }
      elseif (empty($formValues[$key])) {
        continue;
      }

      $htmlType = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_CustomField',
        substr($key, 7), 'html_type'
      );
      if (($htmlType == 'TextArea') &&
        !((substr($formValues[$key], 0, 1) == '%') ||
          (substr($formValues[$key], -1, 1) == '%')
        )
      ) {
        $formValues[$key] = '%' . $formValues[$key] . '%';
      }

    }
  }

  /**
   * Function to delet option value give an option value and custom group id
   *
   * @param int $customValueID custom value ID
   * @param int $customGroupID custom group ID
   *
   * @return void
   * @static
   */
  static function deleteCustomValue($customValueID, $customGroupID) {
    // first we need to find custom value table, from custom group ID
    $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupID, 'table_name');

    // delete custom value from corresponding custom value table
    $sql = "DELETE FROM {$tableName} WHERE id = {$customValueID}";
    CRM_Core_DAO::executeQuery($sql);

    CRM_Utils_Hook::custom('delete',
      $customGroupID,
      NULL,
      $customValueID
    );
  }
}

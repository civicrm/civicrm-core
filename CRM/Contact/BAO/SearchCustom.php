<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Contact_BAO_SearchCustom {

  /**
   * Get details.
   *
   * @param int $csID
   * @param int $ssID
   * @param int $gID
   *
   * @return array
   * @throws Exception
   */
  public static function details($csID, $ssID = NULL, $gID = NULL) {
    $error = array(NULL, NULL, NULL);

    if (!$csID &&
      !$ssID &&
      !$gID
    ) {
      return $error;
    }

    $customSearchID = $csID;
    $formValues = array();
    if ($ssID || $gID) {
      if ($gID) {
        $ssID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $gID, 'saved_search_id');
      }

      $formValues = CRM_Contact_BAO_SavedSearch::getFormValues($ssID);
      $customSearchID = CRM_Utils_Array::value('customSearchID',
        $formValues
      );
    }

    if (!$customSearchID) {
      return $error;
    }

    // check that the csid exists in the db along with the right file
    // and implements the right interface
    $customSearchClass = CRM_Core_OptionGroup::getLabel('custom_search',
      $customSearchID
    );
    if (!$customSearchClass) {
      return $error;
    }

    $ext = CRM_Extension_System::singleton()->getMapper();

    if (!$ext->isExtensionKey($customSearchClass)) {
      $customSearchFile = str_replace('_',
          DIRECTORY_SEPARATOR,
          $customSearchClass
        ) . '.php';
    }
    else {
      $customSearchFile = $ext->keyToPath($customSearchClass);
      $customSearchClass = $ext->keyToClass($customSearchClass);
    }

    $error = include_once $customSearchFile;
    if ($error == FALSE) {
      CRM_Core_Error::fatal('Custom search file: ' . $customSearchFile . ' does not exist. Please verify your custom search settings in CiviCRM administrative panel.');
    }

    return array($customSearchID, $customSearchClass, $formValues);
  }

  /**
   * @param int $csID
   * @param int $ssID
   *
   * @return mixed
   * @throws Exception
   */
  public static function customClass($csID, $ssID) {
    list($customSearchID, $customSearchClass, $formValues) = self::details($csID, $ssID);

    if (!$customSearchID) {
      CRM_Core_Error::fatal('Could not resolve custom search ID');
    }

    // instantiate the new class
    $customClass = new $customSearchClass($formValues);

    return $customClass;
  }

  /**
   * @param int $csID
   * @param int $ssID
   *
   * @return mixed
   */
  public static function contactIDSQL($csID, $ssID) {
    $customClass = self::customClass($csID, $ssID);
    return $customClass->contactIDs();
  }

  /**
   * @param $args
   *
   * @return array
   */
  public static function &buildFormValues($args) {
    $args = trim($args);

    $values = explode("\n", $args);
    $formValues = array();
    foreach ($values as $value) {
      list($n, $v) = CRM_Utils_System::explode('=', $value, 2);
      if (!empty($v)) {
        $formValues[$n] = $v;
      }
    }
    return $formValues;
  }

  /**
   * @param int $csID
   * @param int $ssID
   *
   * @return array
   */
  public static function fromWhereEmail($csID, $ssID) {
    $customClass = self::customClass($csID, $ssID);

    $from = $customClass->from();
    $where = $customClass->where();

    return array($from, $where);
  }

}

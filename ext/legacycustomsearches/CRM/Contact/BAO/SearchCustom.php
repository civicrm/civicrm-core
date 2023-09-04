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
    $error = [NULL, NULL, NULL];

    if (!$csID &&
      !$ssID &&
      !$gID
    ) {
      return $error;
    }

    $customSearchID = $csID;
    $formValues = [];
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
    $customSearchClass = civicrm_api3('OptionValue', 'getvalue', [
      'option_group_id' => 'custom_search',
      'return' => 'name',
      'value' => $customSearchID,
    ]);

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
      throw new CRM_Core_Exception('Custom search file: ' . $customSearchFile . ' does not exist. Please verify your custom search settings in CiviCRM administrative panel.');
    }

    return [$customSearchID, $customSearchClass, $formValues];
  }

  /**
   * @param int $csID
   * @param int $ssID
   *
   * @return CRM_Contact_Form_Search_Custom_Base
   * @throws CRM_Core_Exception
   */
  public static function customClass($csID, $ssID) {
    [$customSearchID, $customSearchClass, $formValues] = self::details($csID, $ssID);

    if (!$customSearchID) {
      throw new CRM_Core_Exception('Could not resolve custom search ID');
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
   * @param string $args
   *
   * @return array
   */
  public static function &buildFormValues($args) {
    $args = trim($args);

    $values = explode("\n", $args);
    $formValues = [];
    foreach ($values as $value) {
      [$n, $v] = CRM_Utils_System::explode('=', $value, 2);
      if (!empty($v)) {
        $formValues[$n] = $v;
      }
    }
    return $formValues;
  }

}

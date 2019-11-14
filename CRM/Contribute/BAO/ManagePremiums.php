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
 * @deprecated
 */
class CRM_Contribute_BAO_ManagePremiums extends CRM_Contribute_BAO_Product {

  /**
   * Class constructor.
   */
  public function __construct() {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Contribute_BAO_Product::construct');
    parent::__construct();
  }

  /**
   * Fetch object based on array of properties.
   *
   * @deprecated
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Contribute_BAO_Product
   */
  public static function retrieve(&$params, &$defaults) {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Contribute_BAO_Product::retrieve');
    return parent::retrieve($params, $defaults);
  }

  /**
   * Update the is_active flag in the db.
   *
   * @deprecated
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Contribute_BAO_Product::setIsActive');
    return parent::setIsActive($id, $is_active);
  }

  /**
   * Add a premium product to the database, and return it.
   *
   * @deprecated
   * @param array $params
   *   Reference array contains the values submitted by the form.
   * @param array $ids (deprecated)
   *   Reference array contains the id.
   *
   * @return CRM_Contribute_DAO_Product
   */
  public static function add(&$params, $ids) {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Contribute_BAO_Product::create');
    $id = CRM_Utils_Array::value('id', $params, CRM_Utils_Array::value('premium', $ids));
    if ($id) {
      $params['id'] = $id;
    }
    return parent::create($params);
  }

  /**
   * Delete premium Types.
   *
   * @deprecated
   * @param int $productID
   *
   * @throws \CRM_Core_Exception
   */
  public static function del($productID) {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Contribute_BAO_Product::del');
    return parent::del($productID);
  }

}

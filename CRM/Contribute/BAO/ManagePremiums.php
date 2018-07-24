<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
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

<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 */
class CRM_Contribute_BAO_ManagePremiums extends CRM_Contribute_DAO_Product {

  /**
   * Static holder for the default LT.
   */
  static $_defaultContributionType = NULL;

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Contribute_BAO_ManagePremium
   */
  public static function retrieve(&$params, &$defaults) {
    $premium = new CRM_Contribute_DAO_Product();
    $premium->copyValues($params);
    if ($premium->find(TRUE)) {
      $premium->product_name = $premium->name;
      CRM_Core_DAO::storeValues($premium, $defaults);
      return $premium;
    }
    return NULL;
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return Object
   *   DAO object on success, null otherwise
   */
  public static function setIsActive($id, $is_active) {
    if (!$is_active) {
      $dao = new CRM_Contribute_DAO_PremiumsProduct();
      $dao->product_id = $id;
      $dao->delete();
    }
    return CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Product', $id, 'is_active', $is_active);
  }

  /**
   * Add a premium product to the database, and return it.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   * @param array $ids
   *   Reference array contains the id.
   *
   * @return CRM_Contribute_DAO_Product
   */
  public static function add(&$params, &$ids) {
    $params = array_merge(array(
      'id' => CRM_Utils_Array::value('premium', $ids),
      'image' => '',
      'thumbnail' => '',
      'is_active' => 0,
      'is_deductible' => FALSE,
      'currency' => CRM_Core_Config::singleton()->defaultCurrency,
    ), $params);

    // Modify the submitted values for 'image' and 'thumbnail' so that we use
    // local URLs for these images when possible.
    $params['image'] = CRM_Utils_String::simplifyURL($params['image'], TRUE);
    $params['thumbnail'] = CRM_Utils_String::simplifyURL($params['thumbnail'], TRUE);

    // Save and return
    $premium = new CRM_Contribute_DAO_Product();
    $premium->copyValues($params);
    $premium->save();
    return $premium;
  }

  /**
   * Delete premium Types.
   *
   * @param int $productID
   */
  public static function del($productID) {
    //check dependencies
    $premiumsProduct = new CRM_Contribute_DAO_PremiumsProduct();
    $premiumsProduct->product_id = $productID;
    if ($premiumsProduct->find(TRUE)) {
      $session = CRM_Core_Session::singleton();
      $message .= ts('This Premium is being linked to <a href=\'%1\'>Online Contribution page</a>. Please remove it in order to delete this Premium.', array(1 => CRM_Utils_System::url('civicrm/admin/contribute', 'reset=1')), ts('Deletion Error'), 'error');
      CRM_Core_Session::setStatus($message);
      return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/contribute/managePremiums', 'reset=1&action=browse'));
    }

    //delete from financial Type table
    $premium = new CRM_Contribute_DAO_Product();
    $premium->id = $productID;
    $premium->delete();
  }

}

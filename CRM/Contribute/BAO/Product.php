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
 */
class CRM_Contribute_BAO_Product extends CRM_Contribute_DAO_Product {

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
   * @return CRM_Contribute_BAO_Product
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
   * @return bool
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
   *   Update parameters.
   *
   * @return CRM_Contribute_DAO_Product
   */
  public static function create($params) {
    $id = CRM_Utils_Array::value('id', $params);
    if (empty($id)) {
      $defaultParams = [
        'id' => $id,
        'image' => '',
        'thumbnail' => '',
        'is_active' => 0,
        'is_deductible' => FALSE,
        'currency' => CRM_Core_Config::singleton()->defaultCurrency,
      ];
      $params = array_merge($defaultParams, $params);
    }

    // Modify the submitted values for 'image' and 'thumbnail' so that we use
    // local URLs for these images when possible.
    if (isset($params['image'])) {
      $params['image'] = CRM_Utils_String::simplifyURL($params['image'], TRUE);
    }
    if (isset($params['thumbnail'])) {
      $params['thumbnail'] = CRM_Utils_String::simplifyURL($params['thumbnail'], TRUE);
    }

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
   *
   * @throws \CRM_Core_Exception
   */
  public static function del($productID) {
    //check dependencies
    $premiumsProduct = new CRM_Contribute_DAO_PremiumsProduct();
    $premiumsProduct->product_id = $productID;
    if ($premiumsProduct->find(TRUE)) {
      throw new CRM_Core_Exception('Cannot delete a Premium that is linked to a Contribution page');
    }
    // delete product
    $premium = new CRM_Contribute_DAO_Product();
    $premium->id = $productID;
    $premium->delete();
  }

}

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
class CRM_Contribute_BAO_Product extends CRM_Contribute_DAO_Product {

  /**
   * Static holder for the default LT.
   * @var int
   */
  public static $_defaultContributionType = NULL;

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve(&$params, &$defaults) {
    $premium = self::commonRetrieve(self::class, $params, $defaults);
    if ($premium) {
      $premium->product_name = $premium->name;
    }
    return $premium;
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
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
    $id = $params['id'] ?? NULL;
    $op = !empty($id) ? 'edit' : 'create';
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
    CRM_Utils_Hook::pre($op, 'Product', $id, $params);
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
    CRM_Utils_Hook::post($op, 'Product', $id, $premium);
    return $premium;
  }

  /**
   * Delete premium Types.
   *
   * @param int $productID
   * @deprecated
   * @throws \CRM_Core_Exception
   */
  public static function del($productID) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    static::deleteRecord(['id' => $productID]);
  }

}

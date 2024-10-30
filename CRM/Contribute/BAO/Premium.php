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
class CRM_Contribute_BAO_Premium extends CRM_Contribute_DAO_Premium {

  /**
   * Product information.
   *
   * @var array
   */
  private static $productInfo;

  /**
   * Retrieve DB object and copy to defaults array.
   *
   * @deprecated
   * @param array $params
   * @param array $defaults
   *
   * @return CRM_Contribute_DAO_Product|NULL
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve('CRM_Contribute_DAO_Product', $params, $defaults);
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Premium', $id, 'premiums_active ', $is_active);
  }

  /**
   * Delete financial Types.
   *
   * @param int $premiumID
   *
   * @deprecated
   */
  public static function del($premiumID) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    return static::deleteRecord(['id' => $premiumID]);
  }

  /**
   * Whitelist of possible values for the entity_table field
   *
   * @return array
   */
  public static function entityTables(): array {
    return [
      'civicrm_contribution_page' => ts('Contribution Page'),
    ];
  }

  /**
   * Build Premium Block im Contribution Pages.
   *
   * @deprecated since 5.69 will be removed around 5.75
   *
   * @param CRM_Core_Form $form
   * @param int $pageID
   * @param bool $formItems
   * @param int $selectedProductID
   * @param string $selectedOption
   */
  public static function buildPremiumBlock(&$form, $pageID, $formItems = FALSE, $selectedProductID = NULL, $selectedOption = NULL) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    $form->add('hidden', "selectProduct", $selectedProductID, ['id' => 'selectProduct']);

    $premiumDao = new CRM_Contribute_DAO_Premium();
    $premiumDao->entity_table = 'civicrm_contribution_page';
    $premiumDao->entity_id = $pageID;
    $premiumDao->premiums_active = 1;

    if ($premiumDao->find(TRUE)) {
      $premiumID = $premiumDao->id;
      $premiumBlock = [];
      CRM_Core_DAO::storeValues($premiumDao, $premiumBlock);

      CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes, CRM_Core_Action::ADD);
      $addWhere = "financial_type_id IN (0)";
      if (!empty($financialTypes)) {
        $addWhere = "financial_type_id IN (" . implode(',', array_keys($financialTypes)) . ")";
      }
      $addWhere = "{$addWhere} OR financial_type_id IS NULL";

      $premiumsProductDao = new CRM_Contribute_DAO_PremiumsProduct();
      $premiumsProductDao->premiums_id = $premiumID;
      $premiumsProductDao->whereAdd($addWhere);
      $premiumsProductDao->orderBy('weight');
      $premiumsProductDao->find();

      $products = [];
      while ($premiumsProductDao->fetch()) {
        $productDAO = new CRM_Contribute_DAO_Product();
        $productDAO->id = $premiumsProductDao->product_id;
        $productDAO->is_active = 1;
        if ($productDAO->find(TRUE)) {
          if ($selectedProductID != NULL) {
            if ($selectedProductID == $productDAO->id) {
              if ($selectedOption) {
                $productDAO->options = ts('Selected Option') . ': ' . $selectedOption;
              }
              else {
                $productDAO->options = NULL;
              }
              CRM_Core_DAO::storeValues($productDAO, $products[$productDAO->id]);
            }
          }
          else {
            // Why? should we not skip if not found?
            CRM_Core_DAO::storeValues($productDAO, $products[$productDAO->id]);
          }
        }
        $options = self::parseProductOptions($productDAO->options);
        if (!empty($options)) {
          $form->addElement('select', 'options_' . $productDAO->id, NULL, $options);
        }
      }
      if (count($products)) {
        $form->assign('showPremiumSelectionFields', $formItems);
        $form->assign('showSelectOptions', $formItems);
        $form->assign('premiumBlock', $premiumBlock);
      }
    }
    $form->assign('products', $products ?? NULL);
  }

  /**
   * Build Premium Preview block for Contribution Pages.
   *
   * @param CRM_Core_Form $form
   * @param int|null $productID
   *
   * @return void
   */
  public static function buildPremiumPreviewBlock($form, $productID) {
    $productDAO = new CRM_Contribute_DAO_Product();
    $productDAO->id = $productID;
    $productDAO->is_active = 1;
    $products = [];

    if ($productDAO->find(TRUE)) {
      CRM_Core_DAO::storeValues($productDAO, $products[$productDAO->id]);
    }

    $radio[$productDAO->id] = NULL;
    $options = $temp = [];
    $temp = explode(',', $productDAO->options);
    foreach ($temp as $value) {
      $options[$value] = $value;
    }
    if ($temp[0] != '') {
      $form->add('select', 'options_' . $productDAO->id, NULL, $options);
    }

    $form->addRadio('selectProduct', NULL, $radio);

    $form->assign('showRadio', TRUE);
    $form->assign('showSelectOptions', TRUE);
    $form->assign('products', $products);
    $form->assign('preview', TRUE);
  }

  /**
   * Delete premium associated w/ contribution page.
   *
   * @param int $contributionPageID
   */
  public static function deletePremium($contributionPageID) {
    if (!$contributionPageID) {
      return;
    }

    //need to delete entries from civicrm_premiums
    //as well as from civicrm_premiums_product, CRM-4586

    $params = [
      'entity_id' => $contributionPageID,
      'entity_table' => 'civicrm_contribution_page',
    ];

    $premium = new CRM_Contribute_DAO_Premium();
    $premium->copyValues($params);
    $premium->find();
    while ($premium->fetch()) {
      //lets delete from civicrm_premiums_product
      $premiumsProduct = new CRM_Contribute_DAO_PremiumsProduct();
      $premiumsProduct->premiums_id = $premium->id;
      $premiumsProduct->delete();

      //now delete premium
      $premium->delete();
    }
  }

  /**
   * Retrieve premium product and their options.
   *
   * @return array
   *   product and option arrays
   */
  public static function getPremiumProductInfo() {
    if (!self::$productInfo) {
      $products = $options = [];

      $dao = new CRM_Contribute_DAO_Product();
      $dao->is_active = 1;
      $dao->find();

      while ($dao->fetch()) {
        $products[$dao->id] = $dao->name . " ( " . $dao->sku . " )";
        $opts = explode(',', $dao->options);
        foreach ($opts as $k => $v) {
          $ops[$k] = trim($v);
        }
        if ($ops[0] != '') {
          $options[$dao->id] = $opts;
        }
      }

      self::$productInfo = [$products, $options];
    }
    return self::$productInfo;
  }

  /**
   * Convert key=val options into an array while keeping
   * compatibility for values only.
   */
  public static function parseProductOptions($string) : array {
    $options = [];
    $temp = explode(',', $string);

    foreach ($temp as $value) {
      $parts = explode('=', $value, 2);
      if (count($parts) == 2) {
        $options[trim($parts[0])] = trim($parts[1]);
      }
      else {
        $options[trim($value)] = trim($value);
      }
    }

    return $options;
  }

}

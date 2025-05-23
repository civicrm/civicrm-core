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

/**
 * This class holds all the Pseudo constants that are specific to Contributions.
 *
 * This avoids polluting the core class and isolates the mass mailer class.
 */
class CRM_Contribute_PseudoConstant extends CRM_Core_PseudoConstant {

  /**
   * Financial types
   * @var array
   */
  private static $financialType;

  /**
   * Financial types
   * @var array
   */
  private static $financialAccount;

  /**
   * Contribution pages
   * @var array
   */
  private static $contributionPageActive = NULL;

  /**
   * Contribution pages
   * @var array
   */
  private static $contributionPageAll = NULL;

  /**
   * Payment instruments
   *
   * @var array
   */
  private static $paymentInstrument;

  /**
   * Contribution status
   *
   * @var array
   */
  private static $contributionStatus;

  /**
   * Personal campaign pages
   * @var array
   */
  private static $pcPage;

  /**
   * Contribution / financial batches
   * @var array
   */
  private static $batch;

  /**
   * @deprecated. Please use the buildOptions() method in the appropriate BAO object.
   *
   *
   * Get all the financial types
   *
   *
   * @param int $id
   * @param bool $includeDisabled
   *
   * @return array
   *   array reference of all financial types if any
   */
  public static function &financialType($id = NULL, $includeDisabled = FALSE) {
    if (!self::$financialType) {
      $condition = "";
      if (!$includeDisabled) {
        $condition = " is_active = 1 ";
      }
      CRM_Core_PseudoConstant::populate(
        self::$financialType,
        'CRM_Financial_DAO_FinancialType',
        TRUE,
        'label',
        NULL,
        $condition
      );
    }

    if ($id) {
      $result = self::$financialType[$id] ?? NULL;
      return $result;
    }
    return self::$financialType;
  }

  /**
   * @deprecated. Please use the buildOptions() method in the appropriate BAO object.
   * TODO: buildOptions() doesn't replace this as it doesn't support filtering, which is used with this function.
   *
   * Get all/filtered array of the financial Accounts
   *
   *
   * @param int $id
   * @param int $financialAccountTypeId Optional filer to return only financial accounts of type
   * @param string $retrieveColumn
   * @param string $key
   *
   * @return array
   *   array reference of all financial accounts if any
   */
  public static function &financialAccount($id = NULL, $financialAccountTypeId = NULL, $retrieveColumn = 'name', $key = 'id') {
    $condition = NULL;
    if ($financialAccountTypeId) {
      $condition = " financial_account_type_id = " . $financialAccountTypeId;
    }
    $cacheKey = "{$id}_{$financialAccountTypeId}_{$retrieveColumn}_{$key}";
    if (!isset(self::$financialAccount[$cacheKey])) {
      CRM_Core_PseudoConstant::populate(
        self::$financialAccount[$cacheKey],
        'CRM_Financial_DAO_FinancialAccount',
        TRUE,
        $retrieveColumn,
        'is_active',
        $condition,
        NULL,
        $key
      );

    }
    if ($id) {
      $result = self::$financialAccount[$cacheKey][$id] ?? NULL;
      return $result;
    }
    return self::$financialAccount[$cacheKey];
  }

  /**
   * Flush given pseudoconstant so it can be reread from db
   * nex time it's requested.
   *
   *
   * @param bool|string $name pseudoconstant to be flushed
   */
  public static function flush($name = 'cache') {
    if (isset(self::$$name)) {
      self::$$name = NULL;
    }
  }

  /**
   * @deprecated. Please use the buildOptions() method in the appropriate BAO object.
   *
   * Get all the contribution pages
   *
   * @param int $id
   *   Id of the contribution page.
   * @param bool $all
   *   Do we want all pages or only active pages.
   *
   *
   * @return string|array|null
   *   array reference of all contribution pages if any
   */
  public static function &contributionPage($id = NULL, $all = FALSE) {
    if ($all) {
      $cacheVarToUse = &self::$contributionPageAll;
    }
    else {
      $cacheVarToUse = &self::$contributionPageActive;
    }

    if (!$cacheVarToUse) {
      CRM_Core_PseudoConstant::populate($cacheVarToUse,
        'CRM_Contribute_DAO_ContributionPage',
        $all, 'title'
      );
    }
    if ($id) {
      $pageTitle = $cacheVarToUse[$id] ?? NULL;
      return $pageTitle;
    }
    return $cacheVarToUse;
  }

  /**
   * @deprecated. Please use the buildOptions() method in the appropriate BAO object.
   *
   * Get all the payment instruments
   *
   *
   * @param string $columnName
   *
   * @return array
   *   array reference of all payment instruments if any
   */
  public static function &paymentInstrument($columnName = 'label') {
    if (!isset(self::$paymentInstrument[$columnName])) {
      self::$paymentInstrument[$columnName] = CRM_Core_OptionGroup::values('payment_instrument',
        FALSE, FALSE, FALSE, NULL, $columnName
      );
    }

    return self::$paymentInstrument[$columnName];
  }

  /**
   * Get all the valid accepted credit cards.
   *
   *
   * @return array
   *   array reference of all payment instruments if any
   */
  public static function &creditCard() {
    return CRM_Core_OptionGroup::values('accept_creditcard', FALSE, FALSE, FALSE, NULL, 'label', TRUE, FALSE, 'name');
  }

  /**
   * Get all premiums.
   *
   *
   * @param int $pageID
   * @return array
   *   array of all Premiums if any
   */
  public static function products($pageID = NULL) {
    $products = [];
    $dao = new CRM_Contribute_DAO_Product();
    $dao->is_active = 1;
    $dao->orderBy('id');
    $dao->find();

    while ($dao->fetch()) {
      $products[$dao->id] = $dao->name;
    }
    if ($pageID) {
      $dao = new CRM_Contribute_DAO_Premium();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id = $pageID;
      $dao->find(TRUE);
      $premiumID = $dao->id;

      $productID = [];

      $dao = new CRM_Contribute_DAO_PremiumsProduct();
      $dao->premiums_id = $premiumID;
      $dao->find();
      while ($dao->fetch()) {
        $productID[$dao->product_id] = $dao->product_id;
      }

      $tempProduct = [];
      foreach ($products as $key => $value) {
        if (!array_key_exists($key, $productID)) {
          $tempProduct[$key] = $value;
        }
      }

      return $tempProduct;
    }

    return $products;
  }

  /**
   * Get all the contribution statuses.
   *
   *
   * @param int $id
   * @param string $columnName
   * @deprecated use standard methods like
   *   CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contributionStatusID);
   *   CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contributionStatusID);
   *   CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contributionStatusID);
   *   & don't specify 'CRM_Contribute_BAO_Contribution' if you mean 'CRM_Contribute_BAO_ContributionRecur' ...
   *
   * @return array
   *   array reference of all contribution statuses
   */
  public static function &contributionStatus($id = NULL, $columnName = 'label') {
    $cacheKey = $columnName;
    if (!isset(self::$contributionStatus[$cacheKey])) {
      self::$contributionStatus[$cacheKey] = CRM_Core_OptionGroup::values('contribution_status',
        FALSE, FALSE, FALSE, NULL, $columnName
      );
    }
    $result = self::$contributionStatus[$cacheKey];
    if ($id) {
      $result = $result[$id] ?? NULL;
    }

    return $result;
  }

  /**
   * Get all the active Personal Campaign Pages.
   */
  public static function &pcPage(): array {
    if (!isset(self::$pcPage)) {
      $result = (array) \Civi\Api4\PCP::get(FALSE)
        ->addSelect('id', 'title')
        ->addWhere('is_active', '=', TRUE)
        ->execute()
        ->indexBy('id');
      $returnValue = \CRM_Utils_Type::escapeAll(array_column($result, 'title', 'id'), 'String');
      self::$pcPage = $returnValue;
    }
    return self::$pcPage;
  }

  /**
   * Get financial account for a Financial type.
   *
   * @deprecated use the alternative with caching
   * CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship
   *
   * @param int $entityId
   * @param string $accountRelationType
   * @param string $entityTable
   * @param string $returnField
   * @return int
   */
  public static function getRelationalFinancialAccount($entityId, $accountRelationType, $entityTable = 'civicrm_financial_type', $returnField = 'financial_account_id') {
    $params = [
      'return' => [$returnField],
      'entity_table' => $entityTable,
      'entity_id' => $entityId,
    ];
    if ($accountRelationType) {
      $params['account_relationship.name'] = $accountRelationType;
    }
    $result = civicrm_api3('EntityFinancialAccount', 'get', $params);
    if (!$result['count']) {
      return NULL;
    }
    return $result['values'][$result['id']][$returnField];
  }

  /**
   * Get all batches.
   *
   *
   * @param int $id
   * @return array
   *   array reference of all batches if any
   */
  public static function &batch($id = NULL) {
    if (!self::$batch) {
      $orderBy = " id DESC ";
      CRM_Core_PseudoConstant::populate(
        self::$batch,
        'CRM_Batch_DAO_Batch',
        TRUE,
        'title',
        NULL,
        NULL,
        $orderBy
      );
    }

    if ($id) {
      $result = self::$batch[$id] ?? NULL;
      return $result;
    }
    return self::$batch;
  }

}

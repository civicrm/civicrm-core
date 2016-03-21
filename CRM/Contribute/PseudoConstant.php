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
  private static $financialTypeAccount;


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
   * Status of personal campaign page
   * @var array
   */
  private static $pcpStatus;

  /**
   * Contribution / financial batches
   * @var array
   */
  private static $batch;

  /**
   * DEPRECATED. Please use the buildOptions() method in the appropriate BAO object.
   *
   * Get all the financial types
   *
   *
   * @param int $id
   *
   * @return array
   *   array reference of all financial types if any
   */
  public static function &financialType($id = NULL) {
    if (!self::$financialType) {
      $condition = " is_active = 1 ";
      CRM_Core_PseudoConstant::populate(
        self::$financialType,
        'CRM_Financial_DAO_FinancialType',
        TRUE,
        'name',
        NULL,
        $condition
      );
    }

    if ($id) {
      $result = CRM_Utils_Array::value($id, self::$financialType);
      return $result;
    }
    return self::$financialType;
  }

  /**
   * DEPRECATED. Please use the buildOptions() method in the appropriate BAO object.
   *
   * Get all the financial Accounts
   *
   *
   * @param int $id
   * @param int $financialAccountTypeId
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
      $result = CRM_Utils_Array::value($id, self::$financialAccount[$cacheKey]);
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
   * DEPRECATED. Please use the buildOptions() method in the appropriate BAO object.
   *
   * Get all the contribution pages
   *
   * @param int $id
   *   Id of the contribution page.
   * @param bool $all
   *   Do we want all pages or only active pages.
   *
   *
   * @return array
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
      $pageTitle = CRM_Utils_Array::value($id, $cacheVarToUse);
      return $pageTitle;
    }
    return $cacheVarToUse;
  }

  /**
   * DEPRECATED. Please use the buildOptions() method in the appropriate BAO object.
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
    $products = array();
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

      $productID = array();

      $dao = new CRM_Contribute_DAO_PremiumsProduct();
      $dao->premiums_id = $premiumID;
      $dao->find();
      while ($dao->fetch()) {
        $productID[$dao->product_id] = $dao->product_id;
      }

      $tempProduct = array();
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
      $result = CRM_Utils_Array::value($id, $result);
    }

    return $result;
  }

  /**
   * Get all the Personal campaign pages.
   *
   *
   * @param null $pageType
   * @param int $id
   *
   * @return array
   *   array reference of all pcp if any
   */
  public static function &pcPage($pageType = NULL, $id = NULL) {
    if (!isset(self::$pcPage[$pageType])) {
      if ($pageType) {
        $params = "page_type='{$pageType}'";
      }
      else {
        $params = '';
      }
      CRM_Core_PseudoConstant::populate(self::$pcPage[$pageType],
        'CRM_PCP_DAO_PCP',
        FALSE, 'title', 'is_active', $params
      );
    }
    $result = self::$pcPage[$pageType];
    if ($id) {
      return $result = CRM_Utils_Array::value($id, $result);
    }

    return $result;
  }

  /**
   * Get all PCP Statuses.
   *
   * The static array pcpStatus is returned
   *
   *
   * @param string $column
   * @return array
   *   array reference of all PCP activity statuses
   */
  public static function &pcpStatus($column = 'label') {
    if (NULL === self::$pcpStatus) {
      self::$pcpStatus = array();
    }
    if (!array_key_exists($column, self::$pcpStatus)) {
      self::$pcpStatus[$column] = array();

      self::$pcpStatus[$column] = CRM_Core_OptionGroup::values('pcp_status', FALSE,
        FALSE, FALSE, NULL, $column
      );
    }
    return self::$pcpStatus[$column];
  }

  /**
   * Get all financial accounts for a Financial type.
   *
   * The static array  $financialTypeAccount is returned
   *
   *
   * @param int $financialTypeId
   * @param int $relationTypeId
   * @return array
   *   array reference of all financial accounts for a Financial type
   */
  public static function financialAccountType($financialTypeId, $relationTypeId = NULL) {
    if (!CRM_Utils_Array::value($financialTypeId, self::$financialTypeAccount)) {
      $condition = " entity_id = $financialTypeId ";
      CRM_Core_PseudoConstant::populate(
        self::$financialTypeAccount[$financialTypeId],
        'CRM_Financial_DAO_EntityFinancialAccount',
        $all = TRUE,
        $retrieve = 'financial_account_id',
        $filter = NULL,
        $condition,
        NULL,
        'account_relationship'
      );
    }

    if ($relationTypeId) {
      return CRM_Utils_Array::value($relationTypeId, self::$financialTypeAccount[$financialTypeId]);
    }

    return self::$financialTypeAccount[$financialTypeId];
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
      $result = CRM_Utils_Array::value($id, self::$batch);
      return $result;
    }
    return self::$batch;
  }

}

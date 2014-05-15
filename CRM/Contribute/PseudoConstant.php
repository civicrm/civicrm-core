<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 +------- -------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class holds all the Pseudo constants that are specific to Contributions. This avoids
 * polluting the core class and isolates the mass mailer class
 */
class CRM_Contribute_PseudoConstant extends CRM_Core_PseudoConstant {

  /**
   * financial types
   * @var array
   * @static
   */
  private static $financialType;

  /**
   * financial types
   * @var array
   * @static
   */
  private static $financialTypeAccount;


  /**
   * financial types
   * @var array
   * @static
   */
  private static $financialAccount;

    /**
   * contribution pages
   * @var array
   * @static
   */
  private static $contributionPageActive = NULL;

  /**
   * contribution pages
   * @var array
   * @static
   */
  private static $contributionPageAll = NULL;

  /**
   * payment instruments
   *
   * @var array
   * @static
   */
  private static $paymentInstrument;

  /**
   * contribution status
   *
   * @var array
   * @static
   */
  private static $contributionStatus;

  /**
   * Personal campaign pages
   * @var array
   * @static
   */
  private static $pcPage;

  /**
   * status of personal campaign page
   * @var array
   * @static
   */
  private static $pcpStatus;

  /**
   * contribution / financial batches
   * @var array
   * @static
   */
  private static $batch;

  /**
   * DEPRECATED. Please use the buildOptions() method in the appropriate BAO object.
   *
   * Get all the financial types
   *
   * @access public
   *
   * @param null $id
   *
   * @return array - array reference of all financial types if any
   * @static
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
   * @access public
   *
   * @param null $id
   * @param null $financialAccountTypeId
   * @param string $retrieveColumn
   * @param string $key
   *
   * @return array - array reference of all financial accounts if any
   * @static
   */
  public static function &financialAccount($id = NULL, $financialAccountTypeId = NULL, $retrieveColumn = 'name', $key = 'id') {
    $condition = NUll;
    if ($financialAccountTypeId) {
      $condition = " financial_account_type_id = ". $financialAccountTypeId;
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
   * @access public
   * @static
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
   * @param integer $id  id of the contribution page
   * @param boolean $all do we want all pages or only active pages
   *
   * @access public
   *
   * @return array - array reference of all contribution pages if any
   * @static
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
   * @access public
   *
   * @param string $columnName
   *
   * @return array - array reference of all payment instruments if any
   * @static
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
   * Get all the valid accepted credit cards
   *
   * @access public
   *
   * @return array - array reference of all payment instruments if any
   * @static
   */
  public static function &creditCard() {
    return CRM_Core_OptionGroup::values('accept_creditcard', FALSE, FALSE, FALSE, NULL, 'label', TRUE, FALSE, 'name');
  }

  /**
   * Get all premiums
   *
   * @access public
   *
   * @param null $pageID
   * @return array - array of all Premiums if any
   * @static
   */
  public static function products($pageID = NULL) {
    $products       = array();
    $dao            = new CRM_Contribute_DAO_Product();
    $dao->is_active = 1;
    $dao->orderBy('id');
    $dao->find();

    while ($dao->fetch()) {
      $products[$dao->id] = $dao->name;
    }
    if ($pageID) {
      $dao               = new CRM_Contribute_DAO_Premium();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id    = $pageID;
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
   * Get all the contribution statuses
   *
   * @access public
   *
   * @param null $id
   * @param string $columnName
   * @return array - array reference of all contribution statuses
   * @static
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
   * Get all the Personal campaign pages
   *
   * @access public
   *
   * @param null $pageType
   * @param null $id
   *
   * @return array - array reference of all pcp if any
   * @static
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
   * @access public
   * @static
   *
   * @param string $column
   * @return array - array reference of all PCP activity statuses
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
   * @access public
   * @static
   *
   * @param $financialTypeId
   * @param null $relationTypeId
   * @return array - array reference of all financial accounts for a Financial type
   */
  public static function financialAccountType($financialTypeId, $relationTypeId = NULL) {
    if (!CRM_Utils_Array::value($financialTypeId, self::$financialTypeAccount)) {
      $condition = " entity_id = $financialTypeId ";
      CRM_Core_PseudoConstant::populate(
        self::$financialTypeAccount[$financialTypeId],
        'CRM_Financial_DAO_EntityFinancialAccount',
        $all = true,
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
   * Get all batches
   *
   * @access public
   *
   * @param null $id
   * @return array - array reference of all batches if any
   * @static
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


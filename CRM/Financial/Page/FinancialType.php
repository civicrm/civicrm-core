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
 * Page for displaying list of financial types
 */
class CRM_Financial_Page_FinancialType extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;
  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Financial_BAO_FinancialType';
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::BROWSE => [
          'name' => ts('Accounts'),
          'url' => 'civicrm/admin/financial/financialType/accounts',
          'qs' => 'reset=1&action=browse&aid=%%id%%',
          'title' => ts('Accounts'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::VIEW),
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/financial/financialType/edit',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Financial Type'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Financial Type'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Financial Type'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ENABLE),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/financial/financialType/edit',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Financial Type'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
      ];
    }
    return self::$_links;
  }

  /**
   * Browse all financial types.
   */
  public function browse() {
    // get all financial types sorted by weight
    $financialType = [];
    $dao = new CRM_Financial_DAO_FinancialType();
    $dao->orderBy('name');
    $dao->find();

    while ($dao->fetch()) {
      $financialType[$dao->id] = [];
      CRM_Core_DAO::storeValues($dao, $financialType[$dao->id]);
      $defaults = $financialAccountId = [];
      $financialAccounts = CRM_Contribute_PseudoConstant::financialAccount(NULL, NULL, 'label');
      $financialAccountIds = [];

      $params['entity_id'] = $dao->id;
      $params['entity_table'] = 'civicrm_financial_type';
      $null = [];
      CRM_Financial_BAO_EntityFinancialAccount::retrieve($params, $null, $financialAccountIds);

      foreach ($financialAccountIds as $key => $values) {
        if (!empty($financialAccounts[$values['financial_account_id']])) {
          $financialAccountId[$values['financial_account_id']] = $financialAccounts[$values['financial_account_id']] ?? NULL;
        }
      }

      if (!empty($financialAccountId)) {
        $financialType[$dao->id]['financial_account'] = implode(',', $financialAccountId);
      }

      // form all action links
      $action = array_sum(array_keys($this->links()));

      // update enable/disable links depending on if it is is_reserved or is_active
      if ($dao->is_reserved) {
        $action -= CRM_Core_Action::ENABLE;
        $action -= CRM_Core_Action::DISABLE;
        $action -= CRM_Core_Action::DELETE;
      }
      else {
        if ($dao->is_active) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }
      }

      $financialType[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(), $action,
        ['id' => $dao->id],
        ts('more'),
        FALSE,
        'financialType.manage.action',
        'FinancialType',
        $dao->id
      );
    }
    $this->assign('rows', $financialType);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Financial_Form_FinancialType';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'Financial Types';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/financial/financialType';
  }

}

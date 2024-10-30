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
 * Page for displaying list of financial accounts
 */
class CRM_Financial_Page_FinancialAccount extends CRM_Core_Page_Basic {

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
    return 'CRM_Financial_BAO_FinancialAccount';
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
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/financial/financialAccount/edit',
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
          'url' => 'civicrm/admin/financial/financialAccount/edit',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Financial Type'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
      ];
    }
    return self::$_links;
  }

  /**
   * Browse all custom data groups.
   */
  public function browse() {
    // get all custom groups sorted by weight
    $contributionType = [];
    $dao = new CRM_Financial_DAO_FinancialAccount();
    $dao->orderBy('financial_account_type_id, label');
    $dao->find();
    $financialAccountType = CRM_Financial_DAO_FinancialAccount::buildOptions('financial_account_type_id');

    while ($dao->fetch()) {
      $contributionType[$dao->id] = [];
      CRM_Core_DAO::storeValues($dao, $contributionType[$dao->id]);
      $contributionType[$dao->id]['financial_account_type_id'] = $financialAccountType[$dao->financial_account_type_id];
      // form all action links
      $action = array_sum(array_keys($this->links()));

      // update enable/disable links depending on if it is is_reserved or is_active
      if ($dao->is_reserved) {
        continue;
      }
      else {
        if ($dao->is_active) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }
      }

      // Ensure keys are always set to avoid Smarty notices
      if (!isset($contributionType[$dao->id]['accounting_code'])) {
        $contributionType[$dao->id]['accounting_code'] = FALSE;
      }
      if (!isset($contributionType[$dao->id]['account_type_code'])) {
        $contributionType[$dao->id]['account_type_code'] = FALSE;
      }

      $contributionType[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(), $action,
        ['id' => $dao->id],
        ts('more'),
        FALSE,
        'financialAccount.manage.action',
        'FinancialAccount',
        $dao->id
      );
    }
    $this->assign('rows', $contributionType);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Financial_Form_FinancialAccount';
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
    return 'civicrm/admin/financial/financialAccount';
  }

}

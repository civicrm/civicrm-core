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
 * Page for displaying list of financial types
 */
class CRM_Financial_Page_FinancialType extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;
  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  static $_links = NULL;

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
      self::$_links = array(
        CRM_Core_Action::BROWSE => array(
          'name' => ts('Accounts'),
          'url' => 'civicrm/admin/financial/financialType/accounts',
          'qs' => 'reset=1&action=browse&aid=%%id%%',
          'title' => ts('Accounts'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/financial/financialType',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Financial Type'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Financial Type'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Financial Type'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/financial/financialType',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Financial Type'),
        ),
      );
    }
    return self::$_links;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   */
  public function run() {
    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse'); // default to 'browse'

    // assign vars to templates
    $this->assign('action', $action);
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);

    // what action to take ?
    if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $this->edit($action, $id);
    }

    // parent run
    return parent::run();
  }

  /**
   * Browse all financial types.
   */
  public function browse() {
    // Check permission for Financial Type when ACL-FT is enabled
    if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()
      && !CRM_Core_Permission::check('administer CiviCRM Financial Types')
    ) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }
    // get all financial types sorted by weight
    $financialType = array();
    $dao = new CRM_Financial_DAO_FinancialType();
    $dao->orderBy('name');
    $dao->find();

    while ($dao->fetch()) {
      $financialType[$dao->id] = array();
      CRM_Core_DAO::storeValues($dao, $financialType[$dao->id]);
      $defaults = $financialAccountId = array();
      $financialAccounts = CRM_Contribute_PseudoConstant::financialAccount();
      $financialAccountIds = array();

      $params['entity_id'] = $dao->id;
      $params['entity_table'] = 'civicrm_financial_type';
      CRM_Financial_BAO_FinancialTypeAccount::retrieve($params, CRM_Core_DAO::$_nullArray, $financialAccountIds);

      foreach ($financialAccountIds as $key => $values) {
        if (!empty($financialAccounts[$values['financial_account_id']])) {
          $financialAccountId[$values['financial_account_id']] = CRM_Utils_Array::value(
            $values['financial_account_id'], $financialAccounts);
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
        array('id' => $dao->id),
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

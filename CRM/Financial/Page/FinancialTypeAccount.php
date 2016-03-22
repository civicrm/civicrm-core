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
 * Page for displaying list of financial type accounts
 */
class CRM_Financial_Page_FinancialTypeAccount extends CRM_Core_Page {
  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  static $_links = NULL;

  /**
   * The account id that we need to display for the browse screen.
   *
   * @var array
   */
  protected $_aid = NULL;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Financial_BAO_FinancialTypeAccount';
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
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/financial/financialType/accounts',
          'qs' => 'action=update&id=%%id%%&aid=%%aid%%&reset=1',
          'title' => ts('Edit Financial Type Account'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/financial/financialType/accounts',
          'qs' => 'action=delete&id=%%id%%&aid=%%aid%%',
          'title' => ts('Delete Financial Type Account'),
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
    $this->_aid = CRM_Utils_Request::retrieve('aid', 'Positive', $this, FALSE, 0);

    // what action to take ?
    if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE)) {
      $this->edit($action, $id);
    }
    else {
      $this->browse($action, $id);
    }

    // parent run
    return parent::run();
  }

  /**
   * Browse all Financial Type Account data.
   */
  public function browse() {
    // get all Financial Type Account data sorted by weight
    $financialType = array();
    $params = array();
    $dao = new CRM_Financial_DAO_EntityFinancialAccount();
    $params['entity_id'] = $this->_aid;
    $params['entity_table'] = 'civicrm_financial_type';
    if ($this->_aid) {
      $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Accounts Receivable Account is' "));
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', $this->_aid, 'name');
      CRM_Utils_System::setTitle($this->_title . ' - ' . ts('Assigned Financial Accounts'));
      $financialAccountType = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_FinancialAccount', 'financial_account_type_id');
      $accountRelationship = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_EntityFinancialAccount', 'account_relationship');
      $dao->copyValues($params);
      $dao->find();
      while ($dao->fetch()) {
        $financialType[$dao->id] = array();
        CRM_Core_DAO::storeValues($dao, $financialType[$dao->id]);

        $params = array('id' => $dao->financial_account_id);
        $defaults = array();
        $financialAccount = CRM_Financial_BAO_FinancialAccount::retrieve($params, $defaults);
        if (!empty($financialAccount)) {
          $financialType[$dao->id]['financial_account'] = $financialAccount->name;
          $financialType[$dao->id]['accounting_code'] = $financialAccount->accounting_code;
          $financialType[$dao->id]['account_type_code'] = $financialAccount->account_type_code;
          $financialType[$dao->id]['is_active'] = $financialAccount->is_active;
          if (!empty($financialAccount->contact_id)) {
            $financialType[$dao->id]['owned_by'] = CRM_Contact_BAO_Contact::displayName($financialAccount->contact_id);
          }
          if (!empty($financialAccount->financial_account_type_id)) {
            $optionGroupName = 'financial_account_type';
            $financialType[$dao->id]['financial_account_type'] = CRM_Utils_Array::value($financialAccount->financial_account_type_id, $financialAccountType);

          }
          if (!empty($dao->account_relationship)) {
            $optionGroupName = 'account_relationship';
            $financialType[$dao->id]['account_relationship'] = CRM_Utils_Array::value($dao->account_relationship, $accountRelationship);
          }
        }
        // form all action links
        $action = array_sum(array_keys($this->links()));
        $links = self::links();

        // CRM-12492
        if ($dao->account_relationship == $relationTypeId) {
          unset($links[CRM_Core_Action::DELETE]);
        }
        $financialType[$dao->id]['action'] = CRM_Core_Action::formLink($links, $action,
          array(
            'id' => $dao->id,
            'aid' => $dao->entity_id,
          ),
          ts('more'),
          FALSE,
          'financialTypeAccount.manage.action',
          'FinancialTypeAccount',
          $dao->id
        );
      }
      $this->assign('rows', $financialType);
      $this->assign('aid', $this->_aid);
      $this->assign('financialTypeTitle', $this->_title);
    }
    else {
      CRM_Core_Error::fatal();
      return NULL;
    }
  }

  /**
   * Edit CiviCRM Financial Type Account data.
   *
   * editing would involved modifying existing financial Account Type + adding data
   * to new financial Account Type.
   *
   * @param string $action
   *   The action to be invoked.
   */
  public function edit($action) {
    // create a simple controller for editing CiviCRM Profile data
    $controller = new CRM_Core_Controller_Simple('CRM_Financial_Form_FinancialTypeAccount', ts('Financial Account Types'), $action);

    // set the userContext stack
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/financial/financialType/accounts',
      'reset=1&action=browse&aid=' . $this->_aid));
    $controller->set('aid', $this->_aid);

    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }

}

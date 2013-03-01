<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Page for displaying list of financial types
 */
class CRM_Financial_Page_FinancialAccount extends CRM_Core_Page_Basic {
  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = null;

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Financial_BAO_FinancialAccount';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::UPDATE  => array(
          'name'  => ts('Edit'),
          'url'   => 'civicrm/admin/financial/financialAccount',
          'qs'    => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Financial Type'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name'  => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\''. 'CRM_Financial_BAO_FinancialAccount' . '\',\'' . 'enable-disable' . '\' );"',
          'ref'   => 'disable-action',
          'title' => ts('Disable Financial Type'),
        ),
        CRM_Core_Action::ENABLE  => array(
          'name'  => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\''. 'CRM_Financial_BAO_FinancialAccount' . '\',\'' . 'disable-enable' . '\' );"',
          'ref'   => 'enable-action',
          'title' => ts('Enable Financial Type'),
        ),
        CRM_Core_Action::DELETE  => array(
          'name'  => ts('Delete'),
          'url'   => 'civicrm/admin/financial/financialAccount',
          'qs'    => 'action=delete&id=%%id%%',
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
   *
   * @return void
   * @access public
   *
   */
  function run() {
    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, false, 'browse'); // default to 'browse'

    // assign vars to templates
    $this->assign('action', $action);
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, false, 0);

    // what action to take ?
    if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $this->edit($action, $id) ;
    }

    // parent run 
    return parent::run();
  }

  /**
   * Browse all custom data groups.
   *
   *
   * @return void
   * @access public
   * @static
   */
  function browse() {
    // get all custom groups sorted by weight
    $contributionType = array();
    $dao = new CRM_Financial_DAO_FinancialAccount();
    $dao->orderBy('financial_account_type_id, name');
    $dao->find();
    $financialAccountType = CRM_Core_PseudoConstant::accountOptionValues('financial_account_type');

    while ($dao->fetch()) {
      $contributionType[$dao->id] = array();
      CRM_Core_DAO::storeValues( $dao, $contributionType[$dao->id]);
      $contributionType[$dao->id]['financial_account_type_id'] =  $financialAccountType[$dao->financial_account_type_id];
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

      $contributionType[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(), $action,
        array('id' => $dao->id));
    }
    $this->assign('rows', $contributionType);
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_Financial_Form_FinancialAccount';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return 'Financial Types';
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext($mode = null) {
    return 'civicrm/admin/financial/financialAccount';
  }
}



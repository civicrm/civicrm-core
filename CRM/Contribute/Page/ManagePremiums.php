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
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * Page for displaying list of Premiums
 */
class CRM_Contribute_Page_ManagePremiums extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Contribute_BAO_ManagePremiums';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/contribute/managePremiums',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Premium'),
        ),
        CRM_Core_Action::PREVIEW => array(
          'name' => ts('Preview'),
          'url' => 'civicrm/admin/contribute/managePremiums',
          'qs' => 'action=preview&id=%%id%%',
          'title' => ts('Preview Premium'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Premium'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Premium'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/contribute/managePremiums',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Premium'),
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
    $action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );

    // assign vars to templates
    $this->assign('action', $action);
    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, 0
    );

    // what action to take ?
    if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::PREVIEW)) {
      $this->edit($action, $id, TRUE);
    }
    // finally browse the custom groups
    $this->browse();

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
    $premiums = array();
    $dao = new CRM_Contribute_DAO_Product();
    $dao->orderBy('name');
    $dao->find();

    while ($dao->fetch()) {
      $premiums[$dao->id] = array();
      CRM_Core_DAO::storeValues($dao, $premiums[$dao->id]);
      // form all action links
      $action = array_sum(array_keys($this->links()));


      if ($dao->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      $premiums[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(),
        $action,
        array('id' => $dao->id),
        ts('more'),
        FALSE,
        'premium.manage.row',
        'Premium',
        $dao->id
      );
           //Financial Type
                if( !empty( $dao->financial_type_id )  ){
                    require_once 'CRM/Core/DAO.php';
                    $premiums[$dao->id]['financial_type_id'] = CRM_Core_DAO::getFieldValue( 'CRM_Financial_DAO_FinancialType', $dao->financial_type_id, 'name' );
    }
        }
    $this->assign('rows', $premiums);
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_Contribute_Form_ManagePremiums';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return 'Manage Premiums';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/admin/contribute/managePremiums';
  }
}


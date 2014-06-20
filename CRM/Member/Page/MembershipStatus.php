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
 * Page for displaying list of membership types
 */
class CRM_Member_Page_MembershipStatus extends CRM_Core_Page_Basic {

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
    return 'CRM_Member_BAO_MembershipStatus';
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
          'url' => 'civicrm/admin/member/membershipStatus',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Membership Status'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Membership Status'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Membership Status'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/member/membershipStatus',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Membership Status'),
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
    if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $this->edit($action, $id);
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
    $membershipStatus = array();
    $dao = new CRM_Member_DAO_MembershipStatus();

    $dao->orderBy('weight');
    $dao->find();

    while ($dao->fetch()) {
      $membershipStatus[$dao->id] = array();
      CRM_Core_DAO::storeValues($dao, $membershipStatus[$dao->id]);

      // form all action links
      $action = array_sum(array_keys($this->links()));
      // update enable/disable links depending on if it is is_reserved or is_active
      if (!$dao->is_reserved) {
        if ($dao->is_active) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }
        $membershipStatus[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(), $action,
          array('id' => $dao->id),
          ts('more'),
          FALSE,
          'membershipStatus.manage.action',
          'MembershipStatus',
          $dao->id
        );
      }
      if ($startEvent = CRM_Utils_Array::value('start_event', $membershipStatus[$dao->id])) {
        $membershipStatus[$dao->id]['start_event'] = ($startEvent == 'join_date') ? 'member since' : str_replace("_", " ", $startEvent);
      }
      if ($endEvent = CRM_Utils_Array::value('end_event', $membershipStatus[$dao->id])) {
        $membershipStatus[$dao->id]['end_event'] = ($endEvent == 'join_date') ? 'member since' : str_replace("_", " ", $endEvent);
      }
    }
    // Add order changing widget to selector
    $returnURL = CRM_Utils_System::url('civicrm/admin/member/membershipStatus', "reset=1&action=browse");
    CRM_Utils_Weight::addOrder($membershipStatus, 'CRM_Member_DAO_MembershipStatus',
      'id', $returnURL
    );

    $this->assign('rows', $membershipStatus);
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_Member_Form_MembershipStatus';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return 'Membership Status';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/admin/member/membershipStatus';
  }
}


<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_ACL_Page_EntityRole extends CRM_Core_Page_Basic {

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
    return 'CRM_ACL_BAO_EntityRole';
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
          'url' => 'civicrm/acl/entityrole',
          'qs' => 'action=update&id=%%id%%',
          'title' => ts('Edit ACL Role Assignment'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable ACL Role Assignment'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable ACL Role Assignment'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/acl/entityrole',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete ACL Role Assignment'),
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
    $action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );

    // assign vars to templates
    $this->assign('action', $action);
    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, 0
    );

    // set breadcrumb to append to admin/access
    $breadCrumb = array(
      array(
        'title' => ts('Access Control'),
        'url' => CRM_Utils_System::url('civicrm/admin/access',
          'reset=1'
        ),
      ),
    );
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
    CRM_Utils_System::setTitle(ts('Assign Users to Roles'));

    // what action to take ?
    if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE)) {
      $this->edit($action, $id);
    }

    // reset cache if enabled/disabled
    if ($action & (CRM_Core_Action::DISABLE | CRM_Core_Action::ENABLE)) {
      CRM_ACL_BAO_Cache::resetCache();
    }

    // finally browse the acl's
    if ($action & CRM_Core_Action::BROWSE) {
    }

    // parent run
    return parent::run();
  }

  /**
   * Browse all acls.
   */
  public function browse() {

    // get all acl's sorted by weight
    $entityRoles = array();
    $dao = new CRM_ACL_DAO_EntityRole();
    $dao->find();

    $aclRoles = CRM_Core_OptionGroup::values('acl_role');
    $groups = CRM_Core_PseudoConstant::staticGroup();

    while ($dao->fetch()) {
      $entityRoles[$dao->id] = array();
      CRM_Core_DAO::storeValues($dao, $entityRoles[$dao->id]);

      $entityRoles[$dao->id]['acl_role'] = $aclRoles[$dao->acl_role_id];
      $entityRoles[$dao->id]['entity'] = $groups[$dao->entity_id];

      // form all action links
      $action = array_sum(array_keys($this->links()));
      if ($dao->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      $entityRoles[$dao->id]['action'] = CRM_Core_Action::formLink(
        self::links(),
        $action,
        array('id' => $dao->id),
        ts('more'),
        FALSE,
        'entityRole.manage.action',
        'EntityRole',
        $dao->id
      );
    }
    $this->assign('rows', $entityRoles);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_ACL_Form_EntityRole';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'ACL EntityRole';
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
    return 'civicrm/acl/entityrole';
  }

}

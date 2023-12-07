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
class CRM_ACL_Page_EntityRole extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_ACL_BAO_ACLEntityRole';
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   */
  public function run() {
    $id = $this->getIdAndAction();

    // set breadcrumb to append to admin/access
    $breadCrumb = [
      [
        'title' => ts('Access Control'),
        'url' => CRM_Utils_System::url('civicrm/admin/access', 'reset=1'),
      ],
    ];
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
    CRM_Utils_System::setTitle(ts('Assign Users to Roles'));

    // what action to take ?
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE)) {
      $this->edit($this->_action, $id);
    }

    // reset cache if enabled/disabled
    if ($this->_action & (CRM_Core_Action::DISABLE | CRM_Core_Action::ENABLE)) {
      CRM_ACL_BAO_Cache::resetCache();
    }

    // finally browse the acl's
    if ($this->_action & CRM_Core_Action::BROWSE) {
      $this->browse();
    }

    // This replaces parent run, but do parent's parent run
    return CRM_Core_Page::run();
  }

  /**
   * Browse all acls.
   */
  public function browse() {

    // get all acl's sorted by weight
    $entityRoles = [];
    $dao = new CRM_ACL_DAO_EntityRole();
    $dao->find();

    $aclRoles = CRM_Core_OptionGroup::values('acl_role');
    $groups = CRM_Core_PseudoConstant::staticGroup();

    while ($dao->fetch()) {
      $entityRoles[$dao->id] = ['class' => ''];
      CRM_Core_DAO::storeValues($dao, $entityRoles[$dao->id]);

      $entityRoles[$dao->id]['acl_role'] = $aclRoles[$dao->acl_role_id] ?? NULL;
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
        ['id' => $dao->id],
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

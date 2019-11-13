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
class CRM_Group_Page_Group extends CRM_Core_Page_Basic {
  protected $_sortByCharacter;

  /**
   * Get BAO name.
   *
   * @return string
   */
  public function getBAOName() {
    return 'CRM_Contact_BAO_Group';
  }

  /**
   * Define action links.
   *
   *   self::$_links array of action links
   */
  public function &links() {
  }

  /**
   * Return class name of edit form.
   *
   * @return string
   */
  public function editForm() {
    return 'CRM_Group_Form_Edit';
  }

  /**
   * Return name of edit form.
   *
   * @return string
   */
  public function editName() {
    return ts('Edit Group');
  }

  /**
   * Return name of delete form.
   *
   * @return string
   */
  public function deleteName() {
    return 'Delete Group';
  }

  /**
   * Return user context uri to return to.
   *
   * @param null $mode
   *
   * @return string
   */
  public function userContext($mode = NULL) {
    return 'civicrm/group';
  }

  /**
   * Return user context uri params.
   *
   * @param null $mode
   *
   * @return string
   */
  public function userContextParams($mode = NULL) {
    return 'reset=1&action=browse';
  }

  /**
   * Re-implement browse.
   *
   * We need to do slightly different things for groups vs saved search groups, hence we
   * re-implement browse from Page_Basic.
   *
   * @param int $action
   */
  public function browse($action = NULL) {
    $groupPermission = CRM_Core_Permission::check('edit groups') ? CRM_Core_Permission::EDIT : CRM_Core_Permission::VIEW;
    $this->assign('groupPermission', $groupPermission);

    $showOrgInfo = FALSE;

    // CRM-9936
    $reservedPermission = CRM_Core_Permission::check('administer reserved groups') ? CRM_Core_Permission::EDIT : CRM_Core_Permission::VIEW;
    $this->assign('reservedPermission', $reservedPermission);

    if (CRM_Core_Permission::check('administer Multiple Organizations') &&
      CRM_Core_Permission::isMultisiteEnabled()
    ) {
      $showOrgInfo = TRUE;
    }
    $this->assign('showOrgInfo', $showOrgInfo);

    // Refresh smart group cache
    if (!empty($_GET['update_smart_groups'])) {
      CRM_Contact_BAO_GroupContactCache::loadAll();
    }
    elseif (!CRM_Core_DAO::singleValueQuery("SELECT contact_id FROM civicrm_group_contact_cache LIMIT 1")) {
      CRM_Core_Session::setStatus(ts('Count data for smart groups is not currently calculated. You may click Update Smart Groups to generate it. Be aware this can cause significant server load'));
    }

    $this->search();
  }

  /**
   * Search for groups.
   */
  public function search() {
    if ($this->_action & (CRM_Core_Action::ADD |
        CRM_Core_Action::UPDATE |
        CRM_Core_Action::DELETE
      )
    ) {
      return;
    }

    $form = new CRM_Core_Controller_Simple('CRM_Group_Form_Search', ts('Search Groups'), CRM_Core_Action::ADD);
    $form->setEmbedded(TRUE);
    $form->setParent($this);
    $form->process();
    $form->run();
  }

}

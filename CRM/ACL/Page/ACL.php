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
class CRM_ACL_Page_ACL extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_ACL_BAO_ACL';
  }

  /**
   * Run the page.
   *
   * Set the breadcrumb before beginning the standard page run.
   */
  public function run() {
    // set breadcrumb to append to admin/access
    $breadCrumb = [
      [
        'title' => ts('Access Control'),
        'url' => CRM_Utils_System::url('civicrm/admin/access', 'reset=1'),
      ],
    ];
    CRM_Utils_System::appendBreadCrumb($breadCrumb);

    // parent run
    return parent::run();
  }

  /**
   * Browse all acls.
   */
  public function browse() {
    // get all acl's sorted by weight
    $acl = [];
    $query = "SELECT * FROM civicrm_acl";
    $dao = CRM_Core_DAO::executeQuery($query);

    $roles = CRM_Core_OptionGroup::values('acl_role');

    $group = [
      '-1' => ts('- select -'),
      '0' => ts('All Groups'),
    ] + CRM_Core_PseudoConstant::group();
    $customGroup = [
      '-1' => ts('- select -'),
      '0' => ts('All Custom Groups'),
    ] + CRM_Core_DAO_CustomField::buildOptions('custom_group_id');
    $ufGroup = [
      '-1' => ts('- select -'),
      '0' => ts('All Profiles'),
    ] + CRM_Core_DAO_UFField::buildOptions('uf_group_id');

    $event = [
      '-1' => ts('- select -'),
      '0' => ts('All Events'),
    ] + CRM_Event_PseudoConstant::event();

    while ($dao->fetch()) {
      $acl[$dao->id] = ['class' => ''];
      $acl[$dao->id]['name'] = $dao->name;
      $acl[$dao->id]['operation'] = $dao->operation;
      $acl[$dao->id]['entity_id'] = $dao->entity_id;
      $acl[$dao->id]['entity_table'] = $dao->entity_table;
      $acl[$dao->id]['object_table'] = $dao->object_table;
      $acl[$dao->id]['object_id'] = $dao->object_id;
      $acl[$dao->id]['is_active'] = $dao->is_active;
      $acl[$dao->id]['deny'] = $dao->deny;
      $acl[$dao->id]['priority'] = $dao->priority;

      if ($acl[$dao->id]['entity_id']) {
        $acl[$dao->id]['entity'] = $roles[$acl[$dao->id]['entity_id']] ?? NULL;
      }
      else {
        $acl[$dao->id]['entity'] = ts('Everyone');
      }

      switch ($acl[$dao->id]['object_table']) {
        case 'civicrm_group':
          $acl[$dao->id]['object'] = $group[$acl[$dao->id]['object_id']] ?? NULL;
          $acl[$dao->id]['object_name'] = ts('Group');
          break;

        case 'civicrm_uf_group':
          $acl[$dao->id]['object'] = $ufGroup[$acl[$dao->id]['object_id']] ?? NULL;
          $acl[$dao->id]['object_name'] = ts('Profile');
          break;

        case 'civicrm_custom_group':
          $acl[$dao->id]['object'] = $customGroup[$acl[$dao->id]['object_id']] ?? NULL;
          $acl[$dao->id]['object_name'] = ts('Custom Group');
          break;

        case 'civicrm_event':
          $acl[$dao->id]['object'] = $event[$acl[$dao->id]['object_id']] ?? NULL;
          $acl[$dao->id]['object_name'] = ts('Event');
          break;

        default:
          $acl[$dao->id]['object'] = $acl[$dao->id]['object_name'] = NULL;
      }

      // form all action links
      $action = array_sum(array_keys($this->links()));

      if ($dao->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      $acl[$dao->id]['action'] = CRM_Core_Action::formLink(
        self::links(),
        $action,
        ['id' => $dao->id],
        ts('more'),
        FALSE,
        'ACL.manage.action',
        'ACL',
        $dao->id
      );
    }
    $this->assign('rows', $acl);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_ACL_Form_ACL';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'ACL';
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
    return 'civicrm/acl';
  }

  /**
   * Edit an ACL.
   *
   * @param int $mode
   *   What mode for the form ?.
   * @param int $id
   *   Id of the entity (for update, view operations).
   * @param bool $imageUpload
   *   Not used in this case, but extended from CRM_Core_Page_Basic.
   * @param bool $pushUserContext
   *   Not used in this case, but extended from CRM_Core_Page_Basic.
   */
  public function edit($mode, $id = NULL, $imageUpload = FALSE, $pushUserContext = TRUE) {
    if ($mode & (CRM_Core_Action::UPDATE)) {
      if (isset($id)) {
        $aclName = CRM_Core_DAO::getFieldValue('CRM_ACL_DAO_ACL', $id);
        CRM_Utils_System::setTitle(ts('Edit ACL &ndash; %1', [1 => $aclName]));
      }
    }
    parent::edit($mode, $id, $imageUpload, $pushUserContext);
  }

}

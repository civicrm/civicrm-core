<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_ACL_Page_ACL extends CRM_Core_Page_Basic {

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
    return 'CRM_ACL_BAO_ACL';
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
          'url' => 'civicrm/acl',
          'qs' => 'reset=1&action=update&id=%%id%%',
          'title' => ts('Edit ACL'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable ACL'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable ACL'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/acl',
          'qs' => 'reset=1&action=delete&id=%%id%%',
          'title' => ts('Delete ACL'),
        ),
      );
    }
    return self::$_links;
  }

  /**
   * Run the page.
   *
   * Set the breadcrumb before beginning the standard page run.
   */
  public function run() {
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

    // parent run
    return parent::run();
  }

  /**
   * Browse all acls.
   */
  public function browse() {
    // get all acl's sorted by weight
    $acl = array();
    $query = "
  SELECT *
    FROM civicrm_acl
   WHERE ( object_table IN ( 'civicrm_saved_search', 'civicrm_uf_group', 'civicrm_custom_group', 'civicrm_event' ) )
ORDER BY entity_id
";
    $dao = CRM_Core_DAO::executeQuery($query);

    $roles = CRM_Core_OptionGroup::values('acl_role');

    $group = array(
      '-1' => ts('- select -'),
      '0' => ts('All Groups'),
    ) + CRM_Core_PseudoConstant::group();
    $customGroup = array(
      '-1' => ts('- select -'),
      '0' => ts('All Custom Groups'),
    ) + CRM_Core_PseudoConstant::get('CRM_Core_DAO_CustomField', 'custom_group_id');
    $ufGroup = array(
      '-1' => ts('- select -'),
      '0' => ts('All Profiles'),
    ) + CRM_Core_PseudoConstant::get('CRM_Core_DAO_UFField', 'uf_group_id');

    $event = array(
      '-1' => ts('- select -'),
      '0' => ts('All Events'),
    ) + CRM_Event_PseudoConstant::event();

    while ($dao->fetch()) {
      $acl[$dao->id] = array();
      $acl[$dao->id]['name'] = $dao->name;
      $acl[$dao->id]['operation'] = $dao->operation;
      $acl[$dao->id]['entity_id'] = $dao->entity_id;
      $acl[$dao->id]['entity_table'] = $dao->entity_table;
      $acl[$dao->id]['object_table'] = $dao->object_table;
      $acl[$dao->id]['object_id'] = $dao->object_id;
      $acl[$dao->id]['is_active'] = $dao->is_active;

      if ($acl[$dao->id]['entity_id']) {
        $acl[$dao->id]['entity'] = CRM_Utils_Array::value($acl[$dao->id]['entity_id'], $roles);
      }
      else {
        $acl[$dao->id]['entity'] = ts('Everyone');
      }

      switch ($acl[$dao->id]['object_table']) {
        case 'civicrm_saved_search':
          $acl[$dao->id]['object'] = CRM_Utils_Array::value($acl[$dao->id]['object_id'], $group);
          $acl[$dao->id]['object_name'] = ts('Group');
          break;

        case 'civicrm_uf_group':
          $acl[$dao->id]['object'] = CRM_Utils_Array::value($acl[$dao->id]['object_id'], $ufGroup);
          $acl[$dao->id]['object_name'] = ts('Profile');
          break;

        case 'civicrm_custom_group':
          $acl[$dao->id]['object'] = CRM_Utils_Array::value($acl[$dao->id]['object_id'], $customGroup);
          $acl[$dao->id]['object_name'] = ts('Custom Group');
          break;

        case 'civicrm_event':
          $acl[$dao->id]['object'] = CRM_Utils_Array::value($acl[$dao->id]['object_id'], $event);
          $acl[$dao->id]['object_name'] = ts('Event');
          break;
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
        array('id' => $dao->id),
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
        CRM_Utils_System::setTitle(ts('Edit ACL &ndash; %1', array(1 => $aclName)));
      }
    }
    parent::edit($mode, $id, $imageUpload, $pushUserContext);
  }

}

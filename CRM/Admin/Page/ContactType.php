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

/**
 * Page for displaying list of contact Subtypes.
 */
class CRM_Admin_Page_ContactType extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Contact_BAO_ContactType';
  }

  /**
   * Run page.
   */
  public function run() {
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 0);
    $this->assign('action', $action);
    if (!$action) {
      $this->browse();
    }
    return parent::run();
  }

  /**
   * Browse contact types.
   */
  public function browse() {
    $rows = CRM_Contact_BAO_ContactType::contactTypeInfo(TRUE);
    foreach ($rows as $key => $value) {
      $mask = NULL;
      if (!empty($value['is_reserved'])) {
        $mask = CRM_Core_Action::UPDATE;
      }
      else {
        $mask -= CRM_Core_Action::DELETE - 2;
        if (!empty($value['is_active'])) {
          $mask -= CRM_Core_Action::ENABLE;
        }
        else {
          $mask -= CRM_Core_Action::DISABLE;
        }
      }
      $rows[$key]['action'] = CRM_Core_Action::formLink(self::links(), $mask,
        ['id' => $value['id']],
        ts('more'),
        FALSE,
        'contactType.manage.action',
        'ContactType',
        $value['id']
      );
      $rows[$key] = array_merge(['class' => ''], $rows[$key]);
    }
    $this->assign('rows', $rows);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Admin_Form_ContactType';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'Contact Types';
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
    return 'civicrm/admin/options/subtype';
  }

}

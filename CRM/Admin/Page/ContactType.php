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
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

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
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/options/subtype',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Contact Type'),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Contact Type'),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Contact Type'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/options/subtype',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Contact Type'),
        ],
      ];
    }
    return self::$_links;
  }

  /**
   * Run page.
   */
  public function run() {
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 0);
    $this->assign('action', $action);
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);
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

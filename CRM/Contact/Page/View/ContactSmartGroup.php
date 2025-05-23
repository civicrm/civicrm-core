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
class CRM_Contact_Page_View_ContactSmartGroup extends CRM_Core_Page {

  /**
   * Contact id.
   *
   * @var int
   */
  public $_contactId;

  /**
   * Called when action is browse.
   */
  public function browse() {
    $in = CRM_Contact_BAO_GroupContact::getContactGroup($this->_contactId, 'Added');

    // keep track of all 'added' contact groups so we can remove them from the smart group
    // section
    $staticGroups = [];
    if (!empty($in)) {
      foreach ($in as $group) {
        $staticGroups[$group['group_id']] = 1;
      }
    }

    $allGroup = CRM_Contact_BAO_GroupContactCache::contactGroup($this->_contactId);
    $this->assign('groupSmart', NULL);
    $this->assign('groupParent', NULL);

    if (!empty($allGroup)) {
      $smart = $parent = [];
      foreach ($allGroup['group'] as $group) {
        // delete all smart groups which are also in static groups
        if (isset($staticGroups[$group['id']])) {
          continue;
        }
        if (empty($group['children'])) {
          $smart[] = $group;
        }
        else {
          $parent[] = $group;
        }
      }

      if (!empty($smart)) {
        $this->assign('groupSmart', $smart);
      }
      if (!empty($parent)) {
        $this->assign('groupParent', $parent);
      }
    }
  }

  public function preProcess() {
    $this->_contactId = (int) CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->assign('contactId', $this->_contactId);

    $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactId);
    $this->assign('displayName', $displayName);

    // check logged in url permission
    CRM_Contact_Page_View::checkUserPermission($this);
  }

  /**
   * the main function that is called
   * when the page loads, it decides the which action has
   * to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->preProcess();
    $this->browse();
    return parent::run();
  }

}

<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Contact_Page_View_ContactSmartGroup extends CRM_Core_Page {

  /**
   * @var int contact id
   */
  public $_contactId;

  /**
   * called when action is browse.
   *
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
        $this->assign_by_ref('groupSmart', $smart);
      }
      if (!empty($parent)) {
        $this->assign_by_ref('groupParent', $parent);
      }
    }
  }

  public function preProcess() {
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
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

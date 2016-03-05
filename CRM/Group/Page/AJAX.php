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
 *
 */

/**
 * This class contains the functions that are called using AJAX (jQuery)
 */
class CRM_Group_Page_AJAX {
  /**
   * Get list of groups.
   *
   * @return array
   */
  public static function getGroupList() {
    $params = $_GET;

    if (isset($params['parent_id'])) {
      // requesting child groups for a given parent
      $params['page'] = 1;
      $params['rp'] = 0;
      $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);

      CRM_Utils_JSON::output($groups);
    }
    else {

      $sortMapper = array();
      foreach ($_GET['columns'] as $key => $value) {
        $sortMapper[$key] = $value['data'];
      };

      $offset = isset($_GET['start']) ? CRM_Utils_Type::escape($_GET['start'], 'Integer') : 0;
      $rowCount = isset($_GET['length']) ? CRM_Utils_Type::escape($_GET['length'], 'Integer') : 25;
      $sort = isset($_GET['order'][0]['column']) ? CRM_Utils_Array::value(CRM_Utils_Type::escape($_GET['order'][0]['column'], 'Integer'), $sortMapper) : NULL;
      $sortOrder = isset($_GET['order'][0]['dir']) ? CRM_Utils_Type::escape($_GET['order'][0]['dir'], 'String') : 'asc';

      if ($sort && $sortOrder) {
        $params['sortBy'] = $sort . ' ' . $sortOrder;
      }

      $params['page'] = ($offset / $rowCount) + 1;
      $params['rp'] = $rowCount;

      // get group list
      $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);

      // if no groups found with parent-child hierarchy and logged in user say can view child groups only (an ACL case),
      // go ahead with flat hierarchy, CRM-12225
      if (empty($groups)) {
        $groupsAccessible = CRM_Core_PseudoConstant::group();
        $parentsOnly = CRM_Utils_Array::value('parentsOnly', $params);
        if (!empty($groupsAccessible) && $parentsOnly) {
          // recompute group list with flat hierarchy
          $params['parentsOnly'] = 0;
          $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
        }
      }

      //add setting so this can be tested by unit test
      //@todo - ideally the portion of this that retrieves the groups should be extracted into a function separate
      // from the one which deals with web inputs & outputs so we have a properly testable & re-usable function
      if (!empty($params['is_unit_test'])) {
        return array($groups, $iFilteredTotal);
      }
      CRM_Utils_JSON::output($groups);
    }
  }

}

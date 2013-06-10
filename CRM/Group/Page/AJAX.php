<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 *
 */

/**
 * This class contains the functions that are called using AJAX (jQuery)
 */
class CRM_Group_Page_AJAX {
  static function getGroupList() {
    $params = $_REQUEST;

    if ( isset($params['parent_id']) ) {
      // requesting child groups for a given parent
      $params['page'] = 1; 
      $params['rp']   = 0;
      $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);

      echo json_encode($groups);
      CRM_Utils_System::civiExit();      
    }
    else {
      $sortMapper = array(
        0 => 'groups.title', 1 => 'groups.id', 2 => 'createdBy.sort_name', 3 => '',
        4 => 'groups.group_type', 5 => 'groups.visibility',
      );

      $sEcho     = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
      $offset    = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
      $rowCount  = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;
      $sort      = isset($_REQUEST['iSortCol_0']) ? CRM_Utils_Array::value(CRM_Utils_Type::escape($_REQUEST['iSortCol_0'], 'Integer'), $sortMapper) : NULL;
      $sortOrder = isset($_REQUEST['sSortDir_0']) ? CRM_Utils_Type::escape($_REQUEST['sSortDir_0'], 'String') : 'asc';

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
        $parentsOnly      = CRM_Utils_Array::value('parentsOnly', $params);
        if (!empty($groupsAccessible) && $parentsOnly) {
          // recompute group list with flat hierarchy
          $params['parentsOnly'] = 0;
          $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
        }
      }

      $iFilteredTotal = $iTotal = $params['total'];
      $selectorElements = array(
        'group_name', 'group_id', 'created_by', 'group_description',
        'group_type', 'visibility', 'org_info', 'links', 'class',
      );

      if (!CRM_Utils_Array::value('showOrgInfo', $params)) {
        unset($selectorElements[6]);
      }

      echo CRM_Utils_JSON::encodeDataTableSelector($groups, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
      CRM_Utils_System::civiExit();
    }
  }
}


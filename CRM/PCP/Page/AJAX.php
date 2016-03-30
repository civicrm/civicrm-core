<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                               |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 *
 */

/**
 * This class contains the functions that are called using AJAX (jQuery)
 */
class CRM_PCP_Page_AJAX {
  /**
   * Get list of groups.
   *
   * @return array
   */
  public static function getPcpList() {
    $params = $_REQUEST;
    $sortMapper = array(
      0 => 'pcps.title',
      1 => 'pcp_supporter.display_name',
      2 => 'page',
      3 => 'start_date',
      4 => 'end_date',
      5 => 'pcps.status_id',
    );

    $sEcho = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $offset = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;
    $sort = isset($_REQUEST['iSortCol_0']) ? CRM_Utils_Array::value(CRM_Utils_Type::escape($_REQUEST['iSortCol_0'], 'Integer'), $sortMapper) : NULL;
    $sortOrder = isset($_REQUEST['sSortDir_0']) ? CRM_Utils_Type::escape($_REQUEST['sSortDir_0'], 'String') : 'asc';

    if ($sort && $sortOrder) {
      $params['sortBy'] = $sort . ' ' . $sortOrder;
    }

    $params['page'] = ($offset / $rowCount) + 1;
    $params['rp'] = $rowCount;

    // get pcp list
    $groups = CRM_PCP_BAO_PCP::getPcpListSelector($params);

    $iFilteredTotal = $iTotal = $params['total'];
    $selectorElements = array(
      'pcp_name',
      'pcp_supporter',
      'pcp_page',
      'pcp_start_date',
      'pcp_end_date',
      'pcp_status',
      'action',
      'class',
    );

    header('Content-Type: application/json');
    echo CRM_Utils_JSON::encodeDataTableSelector($groups, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }
  
  public static function approve() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive');
    $action = CRM_Utils_Request::retrieve('action', 'String');
    if ($action & CRM_Core_Action::RENEW) {
      CRM_PCP_BAO_PCP::setIsActive($id, 1);
      $response = array('status' => 'Approved',);
      echo json_encode($response);
      CRM_Utils_System::civiExit();
    }
  }
  
  public static function reject() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive');
    $action = CRM_Utils_Request::retrieve('action', 'String');
    if ($action & CRM_Core_Action::REVERT) {
      CRM_PCP_BAO_PCP::setIsActive($id, 0);
      $response = array('status' => 'Reverted',);
      echo json_encode($response);
      CRM_Utils_System::civiExit();
    }
  }

}

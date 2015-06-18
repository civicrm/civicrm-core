<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * This api exposes CiviCRM Contribution Payment records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Retrieve a set of contributions which are payments.
 *
 * @param array $params
 *  Input parameters.
 *
 * @return array
 *   Array of contributions, if error an array with an error id and error message
 */
function civicrm_api3_payment_get($params) {
  
  require_once 'api/v3/Contribution.php';

  $mode = CRM_Contact_BAO_Query::MODE_CONTRIBUTE;
  $params['is_payment'] = 1;
  list($dao, $query) = _civicrm_api3_get_query_object($params, $mode, 'Contribution');

  $contribution = array();
  while ($dao->fetch()) {
    //CRM-8662
    $contribution_details = $query->store($dao);
    $softContribution = CRM_Contribute_BAO_ContributionSoft::getSoftContribution($dao->contribution_id, TRUE);
    $contribution[$dao->contribution_id] = array_merge($contribution_details, $softContribution);
    // format soft credit for backward compatibility
    _civicrm_api3_format_soft_credit($contribution[$dao->contribution_id]);
  }
  return civicrm_api3_create_success($contribution, $params, 'Contribution', 'get', $dao);
}
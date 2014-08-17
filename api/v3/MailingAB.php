<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * APIv3 functions for registering/processing mailing ab testing events.
 *
 * @package CiviCRM_APIv3
 * @subpackage API_MailingAB
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * Files required for this package
 */

/**
 * Handle a create mailing ab testing
 *
 * @param array $params
 * @param array $ids
 *
 * @return array API Success Array
 */
function civicrm_api3_mailing_a_b_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Handle a delete event.
 *
 * @param array $params
 * @param array $ids
 *
 * @return array API Success Array
 */
function civicrm_api3_mailing_a_b_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Handle a get event.
 *
 * @param array $params
 * @return array
 */
function civicrm_api3_mailing_a_b_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}


/**
 * Update recipients of A/B mail randomly based on group percentage selected.
 *
 * @param array $params
 * @return array
 */
function civicrm_api3_mailing_a_b_recipients_update($params) {
civicrm_api3_verify_mandatory($params,
    'CRM_Mailing_DAO_MailingAB',
    array('id'),
    FALSE
  );

  $mailingAB = civicrm_api3('MailingAB', 'get', $params);
  $mailingAB = $mailingAB['values'][$params['id']];

  //update mailingC with include/exclude group id(s) provided
  civicrm_api3('Mailing', 'create', array('id' => $mailingAB['mailing_id_c'], 'groups' =>  $params['groups']));
  //update recipients for mailing_id_c
  CRM_Mailing_BAO_Mailing::getRecipients($mailingAB['mailing_id_c'], $mailingAB['mailing_id_c'], NULL, NULL, TRUE);

  //calulate total number of random recipients for mail C from group_percentage selected
  $totalCount =  civicrm_api3('MailingRecipients', 'getcount', array('mailing_id' => $mailingAB['mailing_id_c']));
  $totalSelected = round(($totalCount * $mailingAB['group_percentage'])/200);

  foreach (array('mailing_id_a', 'mailing_id_b') as $columnName) {
    CRM_Mailing_BAO_Recipients::updateRandomRecipients($mailingAB['mailing_id_c'], $mailingAB[$columnName], $totalSelected);
  }

  return civicrm_api3_create_success();
}

/**
 * Adjust Metadata for send_mail action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_mailing_a_b_send_mail_spec(&$params) {
  $params['scheduled_date']['api.default'] = 'now';
}

/**
 * Send A/B mail to A/B recipients respectively
 *
 * @param array $params
 * @return array
 */
function civicrm_api3_mailing_a_b_send_mail($params) {
  civicrm_api3_verify_mandatory($params,
    'CRM_Mailing_DAO_MailingAB',
    array('id'),
    FALSE
  );

  if ($params['scheduled_date'] == 'now') {
    $params['scheduled_date'] = date('YmdHis');
  }
  else {
    $params['scheduled_date'] = CRM_Utils_Date::processDate($params['scheduled_date'] . ' ' . $params['scheduled_date_time']);
  }

  $mailingAB = civicrm_api3('MailingAB', 'get', array('id' => $params['id']));
  $mailingAB = $mailingAB['values'][$params['id']];

 foreach (array('mailing_id_a', 'mailing_id_b') as $columnName) {
    $params['id'] = $mailingAB[$columnName];
    civicrm_api3('Mailing', 'create', $params);
  }

  return civicrm_api3_create_success();
}

/**
 * Adjust Metadata for graph_stats action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_mailing_a_b_graph_stats_spec(&$params) {
   $params['split_count']['api.default'] = 6;
   $params['split_count_select']['api.required'] = 1;
  }

/**
 * Send graph detail for A/B tests mail
 *
 * @param array $params
 * @return array
 */
function civicrm_api3_mailing_a_b_graph_stats($params) {
   civicrm_api3_verify_mandatory($params,
     'CRM_Mailing_DAO_MailingAB',
     array('id'),
 FALSE
   );

 $mailingAB = civicrm_api3('MailingAB', 'get', array('id' => $params['id']));
 $mailingAB = $mailingAB['values'][$params['id']];

 $optionGroupValue = civicrm_api3('OptionValue', 'get', array('option_group_name' => 'mailing_ab_winner_criteria', 'value' => $mailingAB['winner_criteria_id']));
 $winningCriteria = $optionGroupValue['values'][$optionGroupValue['id']]['name'];

 $graphStats = array();
  $ABFormat = array('A' => 'mailing_id_a', 'B' => 'mailing_id_b');

 foreach ($ABFormat as $name => $column) {
     switch ($winningCriteria) {
     case 'Open':
         $totalCounts = CRM_Mailing_Event_BAO_Opened::getTotalCount($mailingAB[$column]);
         $rowCount = round($totalCounts / $params['split_count']);
         $offset = $rowCount * ($params['split_count_select'] - 1);
         $graphStats[$name] = CRM_Mailing_Event_BAO_Opened::getRows($mailingAB[$column], NULL, FALSE, $offset, $rowCount, "{$open}.time_stamp ASC");
         break;
 case 'Total Unique Clicks':
         $totalCounts = CRM_Mailing_Event_BAO_TrackableURLOpen::getTotalCount($mailingAB[$column]);
         $rowCount = round($totalCounts / $params['split_count']);
         $offset = $rowCount * ($params['split_count_select'] - 1);
         $graphStats[$name] = CRM_Mailing_Event_BAO_TrackableURLOpen::getRows($mailingAB[$column], NULL, FALSE, NULL, $offset, $rowCount, "{$click}.time_stamp ASC");
         break;
 case 'Total Clicks on a particular link':
         if (empty($params['url'])) {
         throw new API_Exception("Provide url to get stats result for '{$winningCriteria}'");
 }
 $url_id = CRM_Mailing_BAO_TrackableURL::getTrackerURLId($mailingAB[$column], $params['url']);
 $totalCounts = CRM_Mailing_Event_BAO_TrackableURLOpen::getTotalCount($params['mailing_id'], NULL, FALSE, $url_id);
 $rowCount = round($totalCounts / $params['split_count']);
 $offset = $rowCount * ($params['split_count_select'] - 1);
 $graphStats[$name] = CRM_Mailing_Event_BAO_TrackableURLOpen::getRows($mailingAB[$column], NULL, FALSE, $url_id, $offset, $rowCount, "{$click}.time_stamp ASC");
 break;
 }
 }

 return civicrm_api3_create_success($graphStats);
}
<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
 * APIv3 functions for registering/processing mailing ab testing events.
 *
 * @package CiviCRM_APIv3
 */

/**
 * @param array $spec
 */
function _civicrm_api3_mailing_a_b_create_spec(&$spec) {
  $spec['created_date']['api.default'] = 'now';
  $spec['created_id']['api.required'] = 1;
  $spec['created_id']['api.default'] = 'user_contact_id';
}

/**
 * Handle a create mailing ab testing.
 *
 * @param array $params
 *
 * @return array
 *   API Success Array
 */
function civicrm_api3_mailing_a_b_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Handle a delete event.
 *
 * @param array $params
 *
 * @return array
 *   API Success Array
 */
function civicrm_api3_mailing_a_b_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Handle a get event.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_a_b_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for submit action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $spec
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_mailing_a_b_submit_spec(&$spec) {
  $mailingFields = CRM_Mailing_DAO_Mailing::fields();
  $mailingAbFields = CRM_Mailing_DAO_MailingAB::fields();
  $spec['id'] = $mailingAbFields['id'];
  $spec['status'] = $mailingAbFields['status'];
  $spec['scheduled_date'] = $mailingFields['scheduled_date'];
  $spec['approval_date'] = $mailingFields['approval_date'];
  $spec['approval_status_id'] = $mailingFields['approval_status_id'];
  $spec['approval_note'] = $mailingFields['approval_note'];
  // Note: we'll pass through approval_* fields to the underlying mailing, but they may be ignored
  // if the user doesn't have suitable permission. If separate approvals are required, they must be provided
  // outside the A/B Test UI.
}

/**
 * Send A/B mail to A/B recipients respectively.
 *
 * @param array $params
 *
 * @return array
 * @throws API_Exception
 */
function civicrm_api3_mailing_a_b_submit($params) {
  civicrm_api3_verify_mandatory($params, 'CRM_Mailing_DAO_MailingAB', array('id', 'status'));

  if (!isset($params['scheduled_date']) && !isset($updateParams['approval_date'])) {
    throw new API_Exception("Missing parameter scheduled_date and/or approval_date");
  }

  $dao = new CRM_Mailing_DAO_MailingAB();
  $dao->id = $params['id'];
  if (!$dao->find(TRUE)) {
    throw new API_Exception("Failed to locate A/B test by ID");
  }
  if (empty($dao->mailing_id_a) || empty($dao->mailing_id_b) || empty($dao->mailing_id_c)) {
    throw new API_Exception("Missing mailing IDs for A/B test");
  }

  $submitParams = CRM_Utils_Array::subset($params, array(
    'scheduled_date',
    'approval_date',
    'approval_note',
    'approval_status_id',
  ));

  switch ($params['status']) {
    case 'Testing':
      if (!empty($dao->status) && $dao->status != 'Draft') {
        throw new API_Exception("Cannot transition to state 'Testing'");
      }
      civicrm_api3('Mailing', 'submit', $submitParams + array(
          'id' => $dao->mailing_id_a,
          '_skip_evil_bao_auto_recipients_' => 0,
        ));
      civicrm_api3('Mailing', 'submit', $submitParams + array(
          'id' => $dao->mailing_id_b,
          '_skip_evil_bao_auto_recipients_' => 1,
        ));
      CRM_Mailing_BAO_MailingAB::distributeRecipients($dao);
      break;

    case 'Final':
      if ($dao->status != 'Testing') {
        throw new API_Exception("Cannot transition to state 'Final'");
      }
      civicrm_api3('Mailing', 'submit', $submitParams + array(
          'id' => $dao->mailing_id_c,
          '_skip_evil_bao_auto_recipients_' => 1,
        ));
      break;

    default:
      throw new API_Exception("Unrecognized submission status");
  }

  return civicrm_api3('MailingAB', 'create', array(
    'id' => $dao->id,
    'status' => $params['status'],
    'options' => array(
      'reload' => 1,
    ),
  ));
}

/**
 * Adjust Metadata for graph_stats action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_mailing_a_b_graph_stats_spec(&$params) {
  $params['criteria'] = array(
    'title' => 'Criteria',
    'default' => 'Open',
    'type' => CRM_Utils_Type::T_STRING,
  );

  // mailing_ab_winner_criteria
  $params['target_date']['title'] = 'Target Date';
  $params['target_date']['type'] = CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME;
  $params['split_count'] = array(
    'title' => 'Split Count',
    'api.default' => 6,
    'type' => CRM_Utils_Type::T_INT,
  );
  $params['split_count_select']['title'] = 'Split Count Select';
  $params['split_count_select']['api.required'] = 1;
  $params['target_url']['title'] = 'Target URL';
}

/**
 * Send graph detail for A/B tests mail.
 *
 * @param array $params
 *
 * @return array
 * @throws API_Exception
 */
function civicrm_api3_mailing_a_b_graph_stats($params) {
  civicrm_api3_verify_mandatory($params,
    'CRM_Mailing_DAO_MailingAB',
    array('id'),
    FALSE
  );

  $defaults = array(
    'criteria' => 'Open',
    'target_date' => CRM_Utils_Time::getTime('YmdHis'),
    'split_count' => 6,
    'split_count_select' => 1,
  );
  $params = array_merge($defaults, $params);

  $mailingAB = civicrm_api3('MailingAB', 'getsingle', array('id' => $params['id']));
  $graphStats = array();
  $ABFormat = array('A' => 'mailing_id_a', 'B' => 'mailing_id_b');

  foreach ($ABFormat as $name => $column) {
    switch (strtolower($params['criteria'])) {
      case 'open':
        $result = CRM_Mailing_Event_BAO_Opened::getRows($mailingAB['mailing_id_a'], NULL, TRUE, 0, 1, "civicrm_mailing_event_opened.time_stamp ASC");
        $startDate = CRM_Utils_Date::processDate($result[0]['date']);
        $targetDate = CRM_Utils_Date::processDate($params['target_date']);
        $dateDuration = round(round(strtotime($targetDate) - strtotime($startDate)) / $params['split_count']);
        $toDate = strtotime($startDate) + ($dateDuration * $params['split_count_select']);
        $toDate = date('YmdHis', $toDate);
        $graphStats[$name] = array(
          $params['split_count_select'] => array(
            'count' => CRM_Mailing_Event_BAO_Opened::getTotalCount($mailingAB[$column], NULL, TRUE, $toDate),
            'time' => CRM_Utils_Date::customFormat($toDate),
          ),
        );
        break;

      case 'total unique clicks':
        $result = CRM_Mailing_Event_BAO_TrackableURLOpen::getRows($mailingAB['mailing_id_a'], NULL, TRUE, 0, 1, "civicrm_mailing_event_trackable_url_open.time_stamp ASC");
        $startDate = CRM_Utils_Date::processDate($result[0]['date']);
        $targetDate = CRM_Utils_Date::processDate($params['target_date']);
        $dateDuration = round(abs(strtotime($targetDate) - strtotime($startDate)) / $params['split_count']);
        $toDate = strtotime($startDate) + ($dateDuration * $params['split_count_select']);
        $toDate = date('YmdHis', $toDate);
        $graphStats[$name] = array(
          $params['split_count_select'] => array(
            'count' => CRM_Mailing_Event_BAO_TrackableURLOpen::getTotalCount($params['mailing_id'], NULL, FALSE, NULL, $toDate),
            'time' => CRM_Utils_Date::customFormat($toDate),
          ),
        );
        break;

      case 'total clicks on a particular link':
        if (empty($params['target_url'])) {
          throw new API_Exception("Provide url to get stats result for total clicks on a particular link");
        }
        // FIXME: doesn't make sense to get url_id mailing_id_(a|b) while getting start date in mailing_id_a
        $url_id = CRM_Mailing_BAO_TrackableURL::getTrackerURLId($mailingAB[$column], $params['target_url']);
        $result = CRM_Mailing_Event_BAO_TrackableURLOpen::getRows($mailingAB['mailing_id_a'], NULL, FALSE, $url_id, 0, 1, "civicrm_mailing_event_trackable_url_open.time_stamp ASC");
        $startDate = CRM_Utils_Date::processDate($result[0]['date']);
        $targetDate = CRM_Utils_Date::processDate($params['target_date']);
        $dateDuration = round(abs(strtotime($targetDate) - strtotime($startDate)) / $params['split_count']);
        $toDate = strtotime($startDate) + ($dateDuration * $params['split_count_select']);
        $toDate = CRM_Utils_Date::processDate($toDate);
        $graphStats[$name] = array(
          $params['split_count_select'] => array(
            'count' => CRM_Mailing_Event_BAO_TrackableURLOpen::getTotalCount($params['mailing_id'], NULL, FALSE, $url_id, $toDate),
            'time' => CRM_Utils_Date::customFormat($toDate),
          ),
        );
        break;
    }
  }

  return civicrm_api3_create_success($graphStats);
}

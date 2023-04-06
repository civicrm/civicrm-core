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
  $spec['domain_id']['api.default'] = CRM_Core_Config::domainID();
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
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'MailingAB');
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
  $spec['winner_id'] = [
    'name' => 'winner_id',
    'type' => 1,
    'title' => 'Winner ID',
    'description' => 'The experimental mailing with the best results. If specified, values are copied to the final mailing.',
    'localizable' => 0,
  ];
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
 * @throws CRM_Core_Exception
 */
function civicrm_api3_mailing_a_b_submit($params) {
  civicrm_api3_verify_mandatory($params, 'CRM_Mailing_DAO_MailingAB', ['id', 'status']);

  if (!isset($params['scheduled_date']) && !isset($updateParams['approval_date'])) {
    throw new CRM_Core_Exception("Missing parameter scheduled_date and/or approval_date");
  }

  $dao = new CRM_Mailing_DAO_MailingAB();
  $dao->id = $params['id'];
  if (!$dao->find(TRUE)) {
    throw new CRM_Core_Exception("Failed to locate A/B test by ID");
  }
  if (empty($dao->mailing_id_a) || empty($dao->mailing_id_b) || empty($dao->mailing_id_c)) {
    throw new CRM_Core_Exception("Missing mailing IDs for A/B test");
  }

  $submitParams = CRM_Utils_Array::subset($params, [
    'scheduled_date',
    'approval_date',
    'approval_note',
    'approval_status_id',
  ]);

  switch ($params['status']) {
    case 'Testing':
      if (!empty($dao->status) && $dao->status != 'Draft') {
        throw new CRM_Core_Exception("Cannot transition to state 'Testing'");
      }
      civicrm_api3('Mailing', 'submit', $submitParams + [
        'id' => $dao->mailing_id_a,
        '_skip_evil_bao_auto_recipients_' => 0,
      ]);
      civicrm_api3('Mailing', 'submit', $submitParams + [
        'id' => $dao->mailing_id_b,
        '_skip_evil_bao_auto_recipients_' => 1,
      ]);
      CRM_Mailing_BAO_MailingAB::distributeRecipients($dao);
      break;

    case 'Final':
      if ($dao->status != 'Testing') {
        throw new CRM_Core_Exception("Cannot transition to state 'Final'");
      }
      if (!empty($params['winner_id'])) {
        _civicrm_api3_mailing_a_b_fill_winner($params['winner_id'], $dao->mailing_id_c);
      }
      civicrm_api3('Mailing', 'submit', $submitParams + [
        'id' => $dao->mailing_id_c,
        '_skip_evil_bao_auto_recipients_' => 1,
      ]);
      break;

    default:
      throw new CRM_Core_Exception("Unrecognized submission status");
  }

  return civicrm_api3('MailingAB', 'create', [
    'id' => $dao->id,
    'status' => $params['status'],
    'options' => [
      'reload' => 1,
    ],
  ]);
}

/**
 * @param int $winner_id
 *   The experimental mailing chosen as the "winner".
 * @param int $final_id
 *   The final mailing which should imitate the "winner".
 * @throws \CRM_Core_Exception
 */
function _civicrm_api3_mailing_a_b_fill_winner($winner_id, $final_id) {
  $copyFields = [
    // 'id',
    // 'name',
    'campaign_id',
    'from_name',
    'from_email',
    'replyto_email',
    'subject',
    'dedupe_email',
    // 'recipients',
    'body_html',
    'body_text',
    'footer_id',
    'header_id',
    'visibility',
    'url_tracking',
    'dedupe_email',
    'forward_replies',
    'auto_responder',
    'open_tracking',
    'override_verp',
    'optout_id',
    'reply_id',
    'resubscribe_id',
    'unsubscribe_id',
    'template_type',
    'template_options',
    'language',
  ];
  $f = CRM_Utils_SQL_Select::from('civicrm_mailing')
    ->where('id = #id', ['id' => $winner_id])
    ->select($copyFields)
    ->execute()
    ->fetchAll();
  if (count($f) !== 1) {
    throw new CRM_Core_Exception('Invalid winner_id');
  }
  foreach ($f as $winner) {
    civicrm_api3('Mailing', 'create', $winner + [
      'id' => $final_id,
      '_skip_evil_bao_auto_recipients_' => 1,
    ]);
  }
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
  $params['criteria'] = [
    'title' => 'Criteria',
    'default' => 'Open',
    'type' => CRM_Utils_Type::T_STRING,
  ];

  // mailing_ab_winner_criteria
  $params['target_date']['title'] = 'Target Date';
  $params['target_date']['type'] = CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME;
  $params['split_count'] = [
    'title' => 'Split Count',
    'api.default' => 6,
    'type' => CRM_Utils_Type::T_INT,
  ];
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
 * @throws CRM_Core_Exception
 */
function civicrm_api3_mailing_a_b_graph_stats($params) {
  civicrm_api3_verify_mandatory($params,
    'CRM_Mailing_DAO_MailingAB',
    ['id'],
    FALSE
  );

  $defaults = [
    'criteria' => 'Open',
    'target_date' => CRM_Utils_Time::getTime('YmdHis'),
    'split_count' => 6,
    'split_count_select' => 1,
  ];
  $params = array_merge($defaults, $params);

  $mailingAB = civicrm_api3('MailingAB', 'getsingle', ['id' => $params['id']]);
  $graphStats = [];
  $ABFormat = ['A' => 'mailing_id_a', 'B' => 'mailing_id_b'];

  foreach ($ABFormat as $name => $column) {
    switch (strtolower($params['criteria'])) {
      case 'open':
        $result = CRM_Mailing_Event_BAO_MailingEventOpened::getRows($mailingAB['mailing_id_a'], NULL, TRUE, 0, 1, "civicrm_mailing_event_opened.time_stamp ASC");
        $startDate = CRM_Utils_Date::processDate($result[0]['date']);
        $targetDate = CRM_Utils_Date::processDate($params['target_date']);
        $dateDuration = round(round(strtotime($targetDate) - strtotime($startDate)) / $params['split_count']);
        $toDate = strtotime($startDate) + ($dateDuration * $params['split_count_select']);
        $toDate = date('YmdHis', $toDate);
        $graphStats[$name] = [
          $params['split_count_select'] => [
            'count' => CRM_Mailing_Event_BAO_MailingEventOpened::getTotalCount($mailingAB[$column], NULL, TRUE, $toDate),
            'time' => CRM_Utils_Date::customFormat($toDate),
          ],
        ];
        break;

      case 'total unique clicks':
        $result = CRM_Mailing_Event_BAO_MailingEventTrackableURLOpen::getRows($mailingAB['mailing_id_a'], NULL, TRUE, 0, 1, "civicrm_mailing_event_trackable_url_open.time_stamp ASC");
        $startDate = CRM_Utils_Date::processDate($result[0]['date']);
        $targetDate = CRM_Utils_Date::processDate($params['target_date']);
        $dateDuration = round(abs(strtotime($targetDate) - strtotime($startDate)) / $params['split_count']);
        $toDate = strtotime($startDate) + ($dateDuration * $params['split_count_select']);
        $toDate = date('YmdHis', $toDate);
        $graphStats[$name] = [
          $params['split_count_select'] => [
            'count' => CRM_Mailing_Event_BAO_MailingEventTrackableURLOpen::getTotalCount($params['mailing_id'], NULL, FALSE, NULL, $toDate),
            'time' => CRM_Utils_Date::customFormat($toDate),
          ],
        ];
        break;

      case 'total clicks on a particular link':
        if (empty($params['target_url'])) {
          throw new CRM_Core_Exception("Provide url to get stats result for total clicks on a particular link");
        }
        // FIXME: doesn't make sense to get url_id mailing_id_(a|b) while getting start date in mailing_id_a
        $url_id = CRM_Mailing_BAO_MailingTrackableURL::getTrackerURLId($mailingAB[$column], $params['target_url']);
        $result = CRM_Mailing_Event_BAO_MailingEventTrackableURLOpen::getRows($mailingAB['mailing_id_a'], NULL, FALSE, $url_id, 0, 1, "civicrm_mailing_event_trackable_url_open.time_stamp ASC");
        $startDate = CRM_Utils_Date::processDate($result[0]['date']);
        $targetDate = CRM_Utils_Date::processDate($params['target_date']);
        $dateDuration = round(abs(strtotime($targetDate) - strtotime($startDate)) / $params['split_count']);
        $toDate = strtotime($startDate) + ($dateDuration * $params['split_count_select']);
        $toDate = CRM_Utils_Date::processDate($toDate);
        $graphStats[$name] = [
          $params['split_count_select'] => [
            'count' => CRM_Mailing_Event_BAO_MailingEventTrackableURLOpen::getTotalCount($params['mailing_id'], NULL, FALSE, $url_id, $toDate),
            'time' => CRM_Utils_Date::customFormat($toDate),
          ],
        ];
        break;
    }
  }

  return civicrm_api3_create_success($graphStats);
}

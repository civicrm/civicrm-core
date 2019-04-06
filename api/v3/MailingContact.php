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
 * This api exposes CiviCRM contact and mailing.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Get all the mailings and details that a contact was involved with.
 *
 * @param array $params
 *   Input parameters - see _spec for details (returned by getfields)
 *
 * @return array
 *   API result
 */
function civicrm_api3_mailing_contact_get($params) {
  return civicrm_api3_create_success(_civicrm_api3_mailing_contact_getresults($params, FALSE));
}

/**
 * This is a wrapper for the functions that return the results from the 'quasi-entity' mailing contact.
 *
 * @param array $params
 * @param bool $count
 *
 * @throws Exception
 */
function _civicrm_api3_mailing_contact_getresults($params, $count) {
  if (empty($params['type'])) {
    //ie. because the api is an anomaly & passing in id is not valid
    throw new Exception('This api call does not accept api as a parameter');
  }
  $options  = _civicrm_api3_get_options_from_params($params, TRUE, 'contribution', 'get');
  $fnName = '_civicrm_api3_mailing_contact_get_' . strtolower($params['type']);
  return $fnName(
      $params['contact_id'],
      $options['offset'],
      $options['limit'],
      $options['sort'],
      $count
  );
}

/**
 * Adjust Metadata for Get action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_mailing_contact_get_spec(&$params) {
  $params['contact_id'] = [
    'api.required' => 1,
    'title' => 'Contact ID',
    'type' => CRM_Utils_Type::T_INT,
  ];

  $params['type'] = [
    'api.default' => 'Delivered',
    // doesn't really explain the field - but not sure I understand it to explain it better
    'title' => 'Type',
    'type' => CRM_Utils_Type::T_STRING,
    'options' => [
      'Delivered' => 'Delivered',
      'Bounced' => 'Bounced',
    ],
  ];
}

/**
 * Helper function for mailing contact queries.
 *
 * @param int $contactID
 * @param $offset
 * @param $limit
 * @param $selectFields
 * @param $fromClause
 * @param $whereClause
 * @param $sort
 * @param $getCount
 *
 * @return array
 */
function _civicrm_api3_mailing_contact_query(
  $contactID,
  $offset,
  $limit,
  $selectFields,
  $fromClause,
  $whereClause,
  $sort,
  $getCount
) {

  if ($getCount) {
    $sql = "
SELECT     count(*)
FROM       civicrm_mailing m
INNER JOIN civicrm_contact c ON m.created_id = c.id
INNER JOIN civicrm_mailing_job j ON j.mailing_id = m.id
INNER JOIN civicrm_mailing_event_queue meq ON meq.job_id = j.id
           $fromClause
WHERE      j.is_test = 0
AND        meq.contact_id = %1
           $whereClause
GROUP BY   m.id
";

    $qParams = [
      1 => [$contactID, 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($sql, $qParams);

    $results = $dao->N;
  }
  else {
    $defaultFields = [
      'm.id'       => 'mailing_id',
      'm.subject'  => 'subject',
      'c.id' => 'creator_id',
      'c.sort_name' => 'creator_name',
    ];

    if ($selectFields) {
      $fields = array_merge($selectFields, $defaultFields);
    }
    else {
      $fields = $defaultFields;
    }

    $select = [];
    foreach ($fields as $n => $l) {
      $select[] = "$n as $l";
    }
    $select = implode(', ', $select);

    $orderBy = 'ORDER BY MIN(j.start_date) DESC';
    if ($sort) {
      $orderBy = "ORDER BY $sort";
    }

    $groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns(array_keys($fields), "m.id");

    $sql = "
SELECT     $select
FROM       civicrm_mailing m
INNER JOIN civicrm_contact c ON m.created_id = c.id
INNER JOIN civicrm_mailing_job j ON j.mailing_id = m.id
INNER JOIN civicrm_mailing_event_queue meq ON meq.job_id = j.id
           $fromClause
WHERE      j.is_test = 0
AND        meq.contact_id = %1
           $whereClause
{$groupBy}
{$orderBy}
";

    if ($limit > 0) {
      $sql .= "
LIMIT %2, %3
";
    }

    $qParams = [
      1 => [$contactID, 'Integer'],
      2 => [$offset, 'Integer'],
      3 => [$limit, 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($sql, $qParams);

    $results = [];
    while ($dao->fetch()) {
      foreach ($fields as $n => $l) {
        $results[$dao->mailing_id][$l] = $dao->$l;
      }
    }
  }

  return $results;
}

/**
 * Get delivered mailing contacts.
 *
 * @param int $contactID
 * @param $offset
 * @param $limit
 * @param $sort
 * @param $getCount
 *
 * @return array
 */
function _civicrm_api3_mailing_contact_get_delivered(
  $contactID,
  $offset,
  $limit,
  $sort,
  $getCount
) {
  $selectFields = ['med.time_stamp' => 'start_date'];

  $fromClause = "
INNER JOIN civicrm_mailing_event_delivered med ON med.event_queue_id = meq.id
LEFT  JOIN civicrm_mailing_event_bounce meb ON meb.event_queue_id = meq.id
";

  $whereClause = "
AND        meb.id IS NULL
";

  return _civicrm_api3_mailing_contact_query(
    $contactID,
    $offset,
    $limit,
    $selectFields,
    $fromClause,
    $whereClause,
    $sort,
    $getCount
  );
}

/**
 * Get bounced mailing contact records.
 *
 * @param int $contactID
 * @param $offset
 * @param $limit
 * @param $sort
 * @param $getCount
 *
 * @return array
 */
function _civicrm_api3_mailing_contact_get_bounced(
  $contactID,
  $offset,
  $limit,
  $sort,
  $getCount
) {
  $fromClause = "
INNER JOIN civicrm_mailing_event_bounce meb ON meb.event_queue_id = meq.id
";

  return _civicrm_api3_mailing_contact_query(
    $contactID,
    $offset,
    $limit,
    NULL,
    $fromClause,
    NULL,
    $sort,
    $getCount
  );
}

/**
 * Get count of all the mailings that a contact was involved with.
 *
 * @param array $params
 *   Input parameters per getfields
 *
 * @return array
 *   API result
 */
function civicrm_api3_mailing_contact_getcount($params) {
  return _civicrm_api3_mailing_contact_getresults($params, TRUE);
}

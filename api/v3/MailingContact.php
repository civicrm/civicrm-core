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
 * File for the CiviCRM APIv3 contact and mailing functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_MailingContact
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id$
 *
 */

/**
 * Get all the mailings and details that a contact was involved with
 *
 * @param array    $params input parameters
 *                    - key: contact_id, value: int - required
 *                    - key: type, value: Delivered | Bounced - optional, defaults to Delivered
 *                    - Future extensions will include: Opened, Clicked, Forwarded
 *
 * @return array API result
 * @static void
 * @access public
 * @example CRM/Mailing/BAO/Mailing.php
 *
 */
function civicrm_api3_mailing_contact_get($params) {
  if (empty($params['contact_id'])) {
    return civicrm_api3_create_error('contact_id is a required field');
  }

  if (empty($params['type'])) {
    $params['type'] = 'Delivered';
  }

  $validTypeValues = array('Delivered', 'Bounced');
  if (!in_array($params['type'], $validTypeValues)) {
    return civicrm_api3_create_error(
      'type should be one of the following: ' .
      implode(', ', $validTypeValues)
    );
  }

  if (!isset($params['offset'])) {
    $params['offset'] = 0;
  }

  if (!isset($params['limit'])) {
    $params['limit'] = 50;
  }

  $fnName = '_civicrm_api3_mailing_contact_get_' . strtolower($params['type']);
  return $fnName(
    $params['contact_id'],
    $params['offset'],
    $params['limit'],
    CRM_Utils_Array::value('sort', $params),
    CRM_Utils_Array::value('getcount', $params)
  );
}

function _civicrm_api3_mailing_contact_query(
  $type,
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

    $qParams = array(
      1 => array($contactID, 'Integer')
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $qParams);

    $params = array(
      'type'   => $type,
      'contact_id' => $contactID
    );

    $results = array('count' => $dao->N);
  }
  else {
    $defaultFields = array(
      'm.id'       => 'mailing_id',
      'm.subject'  => 'subject',
      'c.id' => 'creator_id',
      'c.sort_name' => 'creator_name',
    );

    if ($selectFields) {
      $fields = array_merge($selectFields, $defaultFields);
    }
    else {
      $fields = $defaultFields;
    }

    $select = array();
    foreach ($fields as $n => $l) {
      $select[] = "$n as $l";
    }
    $select = implode(', ', $select);

    $orderBy = 'ORDER BY j.start_date DESC';
    if ($sort) {
      $orderBy = "ORDER BY $sort";
    }

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
GROUP BY   m.id
{$orderBy}
";

    if ($limit > 0) {
      $sql .= "
LIMIT %2, %3
";
    }

    $qParams = array(
      1 => array($contactID, 'Integer'),
      2 => array($offset, 'Integer'),
      3 => array($limit, 'Integer')
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $qParams);

    $results = array();
    while ($dao->fetch()) {
      foreach ($fields as $n => $l) {
        $results[$dao->mailing_id][$l] = $dao->$l;
      }
    }

    $params = array(
      'type'   => $type,
      'contact_id' => $contactID,
      'offset' => $offset,
      'limit'  => $limit
    );
  }

  return civicrm_api3_create_success($results, $params);
}

function _civicrm_api3_mailing_contact_get_delivered(
  $contactID,
  $offset,
  $limit,
  $sort,
  $getCount
) {
  $selectFields = array('med.time_stamp' => 'start_date');

  $fromClause = "
INNER JOIN civicrm_mailing_event_delivered med ON med.event_queue_id = meq.id
LEFT  JOIN civicrm_mailing_event_bounce meb ON meb.event_queue_id = meq.id
";

  $whereClause = "
AND        meb.id IS NULL
";

  return _civicrm_api3_mailing_contact_query(
    'Delivered',
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
    'Bounced',
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
 * Get count of all the mailings that a contact was involved with
 *
 * @param array    $params input parameters
 *                    - key: contact_id, value: int - required
 *                    - key: type, value: Delivered | Bounced - optional, defaults to Delivered
 *                    - Future extensions will include: Opened, Clicked, Forwarded
 *
 * @return array API result
 * @static void
 * @access public
 * @example CRM/Mailing/BAO/Mailing.php
 *
 */
function civicrm_api3_mailing_contact_getcount($params) {
  if (empty($params['contact_id'])) {
    return civicrm_api3_create_error('contact_id is a required field');
  }

  // set the count mode for the api
  $params['getcount'] = 1;
  return civicrm_api3_mailing_contact_get($params);
}

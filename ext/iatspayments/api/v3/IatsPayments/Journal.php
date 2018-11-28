<?php

/**
 * @file
 */

/**
 * Action journal.
 *
 * @param array $params
 *
 * Record an entry from iATSPayments into the journal table
 */
function _civicrm_api3_iats_payments_journal_spec(&$params) {
  $params['transaction_id']['api.required'] = 1;
}

/**
 * Action IatsPayments VerifyLog.
 *
 * @param array $params
 *
 * @return array
 *   API result array.
 *
 * @throws CiviCRM_API3_Exception
 */

/**
 *
 */
function civicrm_api3_iats_payments_journal($params) {
  //CRM_Core_Error::debug_var('params', $params);
  //return civicrm_api3_create_success(TRUE, array('test' => TRUE));
  try {
    $data = $params['data'];
    $dtm = date('YmdHis', $params['receive_date']);
    $defaults = array(
      'Client Code' => '',
      'Method of Payment' => '',
      'Comment' => '',
    );
    foreach ($defaults as $key => $default) {
      $data[$key] = empty($data[$key]) ? $default : $data[$key];
    }
    // There are unique keys on tnid (transaction) and iats_id (journal)
    // If I don't have a journal id, don't overwrite.
    if (empty($data['Journal Id'])) {
      $iats_journal_id = 'NULL';
      $sql_action = 'INSERT IGNORE ';
    }
    else {
      $iats_journal_id = (int) $data['Journal Id'];
      $sql_action = 'REPLACE INTO ';
    }
    $query_params = array(
      1 => array($data['Transaction ID'], 'String'),
      3 => array($dtm, 'String'),
      4 => array($data['Client Code'], 'String'),
      5 => array($params['customer_code'], 'String'),
      6 => array($params['invoice'], 'String'),
      7 => array($params['amount'], 'String'),
      8 => array($data['Result'], 'String'),
      9 => array($data['Method of Payment'], 'String'),
      10 => array($data['Comment'], 'String'),
      11 => array($params['status_id'], 'Integer'),
    );
    $result = CRM_Core_DAO::executeQuery($sql_action . " civicrm_iats_journal
        (tnid, iats_id, dtm, agt, cstc, inv, amt, rst, tntyp, cm, status_id) VALUES (%1, $iats_journal_id, %3, %4, %5, %6, %7, %8, %9, %10, %11)", $query_params);
  }
  catch (Exception $e) {
    CRM_Core_Error::debug_var('params', $params);
    // throw CiviCRM_API3_Exception('iATS Payments journalling failed: ' . $e->getMessage());
  }
  return civicrm_api3_create_success();
}

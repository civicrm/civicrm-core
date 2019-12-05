<?php

/**
 * @file
 */

/**
 * Action get journal.
 *
 * @param array $params
 *
 * Get entries from iATS FAPS in the faps_journal table
 */
function _civicrm_api3_faps_transaction_get_journal_spec(&$params) {
  $params['transactionId'] = array(
    'name' => 'transactionId',
    'title' => '1stPay Transaction Id',
    'api.required' => 0,
  );
  $params['isAch'] = array(
    'name' => 'isAch',
    'title' => 'is ACH',
    'api.required' => 0,
  );
  $params['cardType'] = array(
    'name' => 'cardType',
    'title' => 'Card Type',
    'api.required' => 0,
  );
  $params['orderId'] = array(
    'name' => 'orderId',
    'title' => 'Order Id',
    'api.required' => 0,
  );
}

/**
 * Action FapsTransaction GetJournal
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
function civicrm_api3_faps_transaction_get_journal($params) {

  // print_r($params); die();
  $select = "SELECT * FROM civicrm_iats_faps_journal WHERE TRUE ";
  $args = array();

  $select_params = array(
    'transactionId' => 'Integer',
    'isAch' => 'Integer',
    'CardType' => 'String',
    'orderId' => 'String',
  );
  $i = 0;
  foreach ($params as $key => $value) {
    if (isset($select_params[$key])) {
      $i++;
      if (is_string($value)) {
        $select .= " AND $key = %$i";
        $args[$i] = array($value, $select_params[$key]);
      }
      elseif (is_array($value)) {
        foreach (array_keys($value) as $sql) {
          $select .= " AND ($key %$i)";
          $args[$i] = array($sql, 'String');
        }
      }
    }
  }
  if (isset($params['options']['sort'])) {
    $sort = $params['options']['sort'];
    $i++;
    $select .= " ORDER BY %$i";
    $args[$i] = array($sort, 'String');
  }
  else { // by default, get the "latest" entry
    $select .= " ORDER BY id DESC";
  }
  $limit = 1;
  if (isset($params['options']['limit'])) {
    $limit = (integer) $params['options']['limit'];
  }
  if ($limit > 0) {
    $i++;
    $select .= " LIMIT %$i";
    $args[$i] = array($limit, 'Integer');
  }
  $values = array();
  try {
    $dao = CRM_Core_DAO::executeQuery($select, $args);
    while ($dao->fetch()) {
      /* We index in the id */
      $record = array();
      foreach (get_object_vars($dao) as $key => $value) {
        if ('N' != $key && (0 !== strpos($key, '_'))) {
          $record[$key] = $value;
        }
      }
      // also return some of this data in "normalized" field names
      $record['transaction_id'] = $record['transactionId'];
      $record['client_code'] = $record['cimRefNumber'];
      $record['auth_result'] = $record['authResponse'];
      $key = $dao->id;
      $values[$key] = $record;
    }
  }
  catch (Exception $e) {
    CRM_Core_Error::debug_var('params', $params);
    // throw API_Exception('iATS Payments journalling failed: '. $e->getMessage());
  }
  return civicrm_api3_create_success($values);
}

<?php

/**
 * @file
 */

/**
 * Action get journal.
 *
 * @param array $params
 *
 * Get entries from iATSPayments in the journal table
 */
function _civicrm_api3_iats_payments_get_journal_spec(&$params) {
  $params['tnid'] = array(
    'name' => 'tnid',
    'title' => 'Transaction string',
    'api.required' => 0,
  );
  $params['iats_id'] = array(
    'name' => 'iats_id',
    'title' => 'IatsPayments Journal Id',
    'api.required' => 0,
  );
  $params['tntyp'] = array(
    'name' => 'tntyp',
    'title' => 'Transaction type',
    'api.required' => 0,
  );
  $params['inv'] = array(
    'name' => 'inv',
    'title' => 'Invoice Reference',
    'api.required' => 0,
  );
}

/**
 * Action IatsPayments GetJournal
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
function civicrm_api3_iats_payments_get_journal($params) {

  // print_r($params); die();
  $select = "SELECT * FROM civicrm_iats_journal WHERE TRUE ";
  $args = array();

  $select_params = array(
    'tnid' => 'String',
    'tn_type' => 'Integer',
    'iats_id' => 'Integer',
    'inv' => 'String',
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
  $limit = 25;
  if (isset($params['options']['limit'])) {
    $limit = (integer) $params['options']['limit'];
  }
  if ($limit > 0) {
    $i++;
    $select .= " LIMIT %$i";
    $args[$i] = array($limit, 'Integer');
  }
  if (isset($params['options']['sort'])) {
    $sort = $params['options']['sort'];
    $i++;
    $select .= " ORDER BY %$i";
    $args[$i] = array($sort, 'String');
  }

  $values = array();
  try {
    $dao = CRM_Core_DAO::executeQuery($select, $args);
    while ($dao->fetch()) {
      /* We index in the transaction_id */
      $record = array();
      foreach (get_object_vars($dao) as $key => $value) {
        if ('N' != $key && (0 !== strpos($key, '_'))) {
          $record[$key] = $value;
        }
      }
      $key = $dao->tnid;
      $values[$key] = $record;
    }
  }
  catch (Exception $e) {
    CRM_Core_Error::debug_var('params', $params);
    // throw API_Exception('iATS Payments journalling failed: '. $e->getMessage());
  }
  return civicrm_api3_create_success($values);
}

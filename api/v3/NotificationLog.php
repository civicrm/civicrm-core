<?php
/**
 * Quick & dirty api to re-try an api call
 *
 */
function civicrm_api3_notification_log_retry($params) {
  if(!empty($params['id'])) {
    $raw = array(CRM_Core_DAO::singleValueQuery("SELECT message_raw FROM civicrm_notification_log
      WHERE id = %1", array(1 => array($params['id'], 'Integer'))));
  }
  foreach ($raw as $ipnData) {
    $payPal = new CRM_Core_Payment_PayPalProIPN(json_decode($ipnData, TRUE));
    $payPal->main();
  }
}

function _civicrm_api3_notification_log_retry_spec(&$params) {
  $params['id']['api.required'] = TRUE;
}

/**
 * hack to do many @ once - this is not an approach I would do for Core or an extension without a
 * lot more thought
 * @param unknown_type $params
 */
function civicrm_api3_notification_log_retrysearch($params) {
  $dao = CRM_Core_DAO::executeQuery("
    SELECT id FROM civicrm_notification_log
    WHERE message_raw LIKE %1", array(1 => array('%'  . $params['search'] . '%', 'String')));
  while ($dao->fetch()) {
    $raw[] = $dao->id;
  }
  $result = array();
  foreach ($raw as $id) {
    try{
      civicrm_api3('notification_log', 'retry', array('id' => $id));
      $resut['success'][] = $id;
    }
    catch (Exception $e) {
      throw new Exception( $e->getMessage() . $id);
      $errors[]= $e->getMessage() . "  on  id " . $id;
      $resut['errors'][] = $id;
    }
  }
  return civicrm_api3_create_success($result, $params);
}

function _civicrm_api3_notification_log_retrysearch_spec(&$params) {
  $params['search']['api.required'] = TRUE;
}

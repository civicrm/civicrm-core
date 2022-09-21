<?php

/**
 * BeepTimes.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_beep_times_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * BeepTimes.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_beep_times_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * BeepTimes.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_beep_times_get($params) {
  CRM_Core_DAO::disableFullGroupByMode();
  $sql = CRM_Utils_SQL_Select::fragment();
  $sql->select('CONCAT(recording_device_id, start_time) as id');
  $result = civicrm_api3_create_success(_civicrm_api3_basic_get('CRM_PrimaryKeys_BAO_BeepTimes', $params, FALSE, 'BeepTimes', $sql, FALSE), $params, 'BeepTimes', 'get');
  CRM_Core_DAO::reenableFullGroupByMode();
  return $result;
}

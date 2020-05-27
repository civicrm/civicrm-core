<?php

/**
 * @param $apiRequest
 *
 * @return array
 */
function civicrm_api3_generic_example_action1($apiRequest) {
  return civicrm_api3_create_success(
    ['0' => 'civicrm_api3_generic_example_action1 is ok'],
    $apiRequest['params'],
    $apiRequest['entity'],
    $apiRequest['action']
  );
}

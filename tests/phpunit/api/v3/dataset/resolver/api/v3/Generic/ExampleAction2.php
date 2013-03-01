<?php
// $Id$

function civicrm_api3_generic_example_action2($apiRequest) {
  return civicrm_api3_create_success(
    array('0' => 'civicrm_api3_generic_example_action2 should not be called'),
    $apiRequest['params'],
    $apiRequest['entity'],
    $apiRequest['action']
  );
}


<?php

/**
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_contact_example_action2($params) {
  return civicrm_api3_create_success(
    ['0' => 'civicrm_api3_contact_example_action2 is ok'],
    $params,
    'contact',
    'example_action2'
  );
}

<?php

/**
 * Example result for API Test.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_test_entity_example_action3($params) {
  return civicrm_api3_create_success(
    ['0' => 'civicrm_api3_test_entity_example_action3 is ok'],
    $params,
    'test_entity',
    'example_action3'
  );
}

<<<<<<< HEAD
<?php

/**
 * @param $apiRequest
 *
 * @return array
 */
function civicrm_api3_generic_example_action2($apiRequest) {
  return civicrm_api3_create_success(
    array('0' => 'civicrm_api3_generic_example_action2 should not be called'),
    $apiRequest['params'],
    $apiRequest['entity'],
    $apiRequest['action']
  );
}

=======
<?php

/**
 * Example result for API Test.
 *
 * @param array $apiRequest
 *
 * @return array
 */
function civicrm_api3_generic_example_action2($apiRequest) {
  return civicrm_api3_create_success(
    array('0' => 'civicrm_api3_generic_example_action2 should not be called'),
    $apiRequest['params'],
    $apiRequest['entity'],
    $apiRequest['action']
  );
}
>>>>>>> 650ff6351383992ec77abface9b7f121f16ae07e

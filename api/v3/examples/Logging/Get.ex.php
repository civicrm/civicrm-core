<?php
/**
 * Test Generated example demonstrating the Logging.get API.
 *
 * @return array
 *   API result array
 */
function logging_get_example() {
  $params = [
    'log_conn_id' => 'wooty wop wop',
  ];

  try{
    $result = civicrm_api3('Logging', 'get', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return [
      'is_error' => 1,
      'error_message' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    ];
  }

  return $result;
}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function logging_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 17,
    'values' => [
      '0' => [
        'action' => 'Update',
        'id' => '3',
        'field' => 'sort_name',
        'from' => 'Anderson, Anthony',
        'to' => 'Dwarf, Dopey',
        'table' => 'civicrm_contact',
        'log_date' => '2019-08-20 19:23:24',
        'log_conn_id' => 'wooty wop wop',
      ],
      '1' => [
        'action' => 'Update',
        'id' => '3',
        'field' => 'display_name',
        'from' => 'Mr. Anthony Anderson II',
        'to' => 'Mr. Dopey Dwarf II',
        'table' => 'civicrm_contact',
        'log_date' => '2019-08-20 19:23:24',
        'log_conn_id' => 'wooty wop wop',
      ],
      '2' => [
        'action' => 'Update',
        'id' => '3',
        'field' => 'first_name',
        'from' => 'Anthony',
        'to' => 'Dopey',
        'table' => 'civicrm_contact',
        'log_date' => '2019-08-20 19:23:24',
        'log_conn_id' => 'wooty wop wop',
      ],
      '3' => [
        'action' => 'Update',
        'id' => '3',
        'field' => 'last_name',
        'from' => 'Anderson',
        'to' => 'Dwarf',
        'table' => 'civicrm_contact',
        'log_date' => '2019-08-20 19:23:24',
        'log_conn_id' => 'wooty wop wop',
      ],
      '4' => [
        'action' => 'Update',
        'id' => '3',
        'field' => 'modified_date',
        'from' => '2019-08-20 19:23:20',
        'to' => '2019-08-20 19:23:24',
        'table' => 'civicrm_contact',
        'log_date' => '2019-08-20 19:23:24',
        'log_conn_id' => 'wooty wop wop',
      ],
      '5' => [
        'action' => 'Insert',
        'id' => '2',
        'field' => 'id',
        'from' => '',
        'to' => '2',
        'table' => 'civicrm_email',
        'log_date' => '2019-08-20 19:23:25',
        'log_conn_id' => 'wooty wop wop',
      ],
      '6' => [
        'action' => 'Insert',
        'id' => '2',
        'field' => 'contact_id',
        'from' => '',
        'to' => '3',
        'table' => 'civicrm_email',
        'log_date' => '2019-08-20 19:23:25',
        'log_conn_id' => 'wooty wop wop',
      ],
      '7' => [
        'action' => 'Insert',
        'id' => '2',
        'field' => 'location_type_id',
        'from' => '',
        'to' => '1',
        'table' => 'civicrm_email',
        'log_date' => '2019-08-20 19:23:25',
        'log_conn_id' => 'wooty wop wop',
      ],
      '8' => [
        'action' => 'Insert',
        'id' => '2',
        'field' => 'email',
        'from' => '',
        'to' => 'dopey@mail.com',
        'table' => 'civicrm_email',
        'log_date' => '2019-08-20 19:23:25',
        'log_conn_id' => 'wooty wop wop',
      ],
      '9' => [
        'action' => 'Insert',
        'id' => '2',
        'field' => 'is_primary',
        'from' => '',
        'to' => 0,
        'table' => 'civicrm_email',
        'log_date' => '2019-08-20 19:23:25',
        'log_conn_id' => 'wooty wop wop',
      ],
      '10' => [
        'action' => 'Insert',
        'id' => '2',
        'field' => 'is_billing',
        'from' => '',
        'to' => 0,
        'table' => 'civicrm_email',
        'log_date' => '2019-08-20 19:23:25',
        'log_conn_id' => 'wooty wop wop',
      ],
      '11' => [
        'action' => 'Insert',
        'id' => '2',
        'field' => 'on_hold',
        'from' => '',
        'to' => 0,
        'table' => 'civicrm_email',
        'log_date' => '2019-08-20 19:23:25',
        'log_conn_id' => 'wooty wop wop',
      ],
      '12' => [
        'action' => 'Insert',
        'id' => '2',
        'field' => 'is_bulkmail',
        'from' => '',
        'to' => 0,
        'table' => 'civicrm_email',
        'log_date' => '2019-08-20 19:23:25',
        'log_conn_id' => 'wooty wop wop',
      ],
      '13' => [
        'action' => 'Insert',
        'id' => '2',
        'field' => 'hold_date',
        'from' => '',
        'to' => '',
        'table' => 'civicrm_email',
        'log_date' => '2019-08-20 19:23:25',
        'log_conn_id' => 'wooty wop wop',
      ],
      '14' => [
        'action' => 'Insert',
        'id' => '2',
        'field' => 'reset_date',
        'from' => '',
        'to' => '',
        'table' => 'civicrm_email',
        'log_date' => '2019-08-20 19:23:25',
        'log_conn_id' => 'wooty wop wop',
      ],
      '15' => [
        'action' => 'Insert',
        'id' => '2',
        'field' => 'signature_text',
        'from' => '',
        'to' => '',
        'table' => 'civicrm_email',
        'log_date' => '2019-08-20 19:23:25',
        'log_conn_id' => 'wooty wop wop',
      ],
      '16' => [
        'action' => 'Insert',
        'id' => '2',
        'field' => 'signature_html',
        'from' => '',
        'to' => '',
        'table' => 'civicrm_email',
        'log_date' => '2019-08-20 19:23:25',
        'log_conn_id' => 'wooty wop wop',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetNoDate"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/LoggingTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-Core-Matrix/
*
* To Learn about the API read
* https://docs.civicrm.org/dev/en/latest/api/
*
* Browse the API on your own site with the API Explorer. It is in the main
* CiviCRM menu, under: Support > Development > API Explorer.
*
* Read more about testing here
* https://docs.civicrm.org/dev/en/latest/testing/
*
* API Standards documentation:
* https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
*/

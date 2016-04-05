<?php
/**
 * Test Generated example demonstrating the Logging.get API.
 *
 * @return array
 *   API result array
 */
function logging_get_example() {
  $params = array(
    'log_conn_id' => 'wooty woot',
    'log_date' => '2016-04-06 00:08:23',
  );

  try{
    $result = civicrm_api3('Logging', 'get', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    );
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

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 17,
    'values' => array(
      '0' => array(
        'action' => 'Update',
        'id' => '3',
        'field' => 'sort_name',
        'from' => 'Anderson, Anthony',
        'to' => 'Dwarf, Dopey',
        'table' => 'civicrm_contact',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
      '1' => array(
        'action' => 'Update',
        'id' => '3',
        'field' => 'display_name',
        'from' => 'Mr. Anthony Anderson II',
        'to' => 'Mr. Dopey Dwarf II',
        'table' => 'civicrm_contact',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
      '2' => array(
        'action' => 'Update',
        'id' => '3',
        'field' => 'first_name',
        'from' => 'Anthony',
        'to' => 'Dopey',
        'table' => 'civicrm_contact',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
      '3' => array(
        'action' => 'Update',
        'id' => '3',
        'field' => 'last_name',
        'from' => 'Anderson',
        'to' => 'Dwarf',
        'table' => 'civicrm_contact',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
      '4' => array(
        'action' => 'Update',
        'id' => '3',
        'field' => 'modified_date',
        'from' => '2016-04-06 00:08:06',
        'to' => '2016-04-06 00:08:23',
        'table' => 'civicrm_contact',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
      '5' => array(
        'action' => 'Insert',
        'id' => '4',
        'field' => 'id',
        'from' => '',
        'to' => '4',
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
      '6' => array(
        'action' => 'Insert',
        'id' => '4',
        'field' => 'contact_id',
        'from' => '',
        'to' => '3',
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
      '7' => array(
        'action' => 'Insert',
        'id' => '4',
        'field' => 'location_type_id',
        'from' => '',
        'to' => '',
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
      '8' => array(
        'action' => 'Insert',
        'id' => '4',
        'field' => 'email',
        'from' => '',
        'to' => 'dopey@mail.com',
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
      '9' => array(
        'action' => 'Insert',
        'id' => '4',
        'field' => 'is_primary',
        'from' => '',
        'to' => 0,
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
      '10' => array(
        'action' => 'Insert',
        'id' => '4',
        'field' => 'is_billing',
        'from' => '',
        'to' => 0,
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
      '11' => array(
        'action' => 'Insert',
        'id' => '4',
        'field' => 'on_hold',
        'from' => '',
        'to' => 0,
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
      '12' => array(
        'action' => 'Insert',
        'id' => '4',
        'field' => 'is_bulkmail',
        'from' => '',
        'to' => 0,
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
      '13' => array(
        'action' => 'Insert',
        'id' => '4',
        'field' => 'hold_date',
        'from' => '',
        'to' => '',
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
      '14' => array(
        'action' => 'Insert',
        'id' => '4',
        'field' => 'reset_date',
        'from' => '',
        'to' => '',
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
      '15' => array(
        'action' => 'Insert',
        'id' => '4',
        'field' => 'signature_text',
        'from' => '',
        'to' => '',
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
      '16' => array(
        'action' => 'Insert',
        'id' => '4',
        'field' => 'signature_html',
        'from' => '',
        'to' => '',
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 00:08:23',
        'log_conn_id' => 'wooty woot',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGet"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/LoggingTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/

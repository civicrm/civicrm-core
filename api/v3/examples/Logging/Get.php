<?php
/**
 * Test Generated example demonstrating the Logging.get API.
 *
 * @return array
 *   API result array
 */
function logging_get_example() {
  $params = array(
    'log_conn_id' => 'wooty wop wop',
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
        'id' => '9',
        'field' => 'sort_name',
        'from' => 'Anderson, Anthony',
        'to' => 'Dwarf, Dopey',
        'table' => 'civicrm_contact',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
      '1' => array(
        'action' => 'Update',
        'id' => '9',
        'field' => 'display_name',
        'from' => 'Mr. Anthony Anderson II',
        'to' => 'Mr. Dopey Dwarf II',
        'table' => 'civicrm_contact',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
      '2' => array(
        'action' => 'Update',
        'id' => '9',
        'field' => 'first_name',
        'from' => 'Anthony',
        'to' => 'Dopey',
        'table' => 'civicrm_contact',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
      '3' => array(
        'action' => 'Update',
        'id' => '9',
        'field' => 'last_name',
        'from' => 'Anderson',
        'to' => 'Dwarf',
        'table' => 'civicrm_contact',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
      '4' => array(
        'action' => 'Update',
        'id' => '9',
        'field' => 'modified_date',
        'from' => '2016-04-06 02:53:27',
        'to' => '2016-04-06 02:53:44',
        'table' => 'civicrm_contact',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
      '5' => array(
        'action' => 'Insert',
        'id' => '2',
        'field' => 'id',
        'from' => '',
        'to' => '2',
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
      '6' => array(
        'action' => 'Insert',
        'id' => '2',
        'field' => 'contact_id',
        'from' => '',
        'to' => '9',
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
      '7' => array(
        'action' => 'Insert',
        'id' => '2',
        'field' => 'location_type_id',
        'from' => '',
        'to' => '',
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
      '8' => array(
        'action' => 'Insert',
        'id' => '2',
        'field' => 'email',
        'from' => '',
        'to' => 'dopey@mail.com',
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
      '9' => array(
        'action' => 'Insert',
        'id' => '2',
        'field' => 'is_primary',
        'from' => '',
        'to' => 0,
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
      '10' => array(
        'action' => 'Insert',
        'id' => '2',
        'field' => 'is_billing',
        'from' => '',
        'to' => 0,
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
      '11' => array(
        'action' => 'Insert',
        'id' => '2',
        'field' => 'on_hold',
        'from' => '',
        'to' => 0,
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
      '12' => array(
        'action' => 'Insert',
        'id' => '2',
        'field' => 'is_bulkmail',
        'from' => '',
        'to' => 0,
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
      '13' => array(
        'action' => 'Insert',
        'id' => '2',
        'field' => 'hold_date',
        'from' => '',
        'to' => '',
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
      '14' => array(
        'action' => 'Insert',
        'id' => '2',
        'field' => 'reset_date',
        'from' => '',
        'to' => '',
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
      '15' => array(
        'action' => 'Insert',
        'id' => '2',
        'field' => 'signature_text',
        'from' => '',
        'to' => '',
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
      '16' => array(
        'action' => 'Insert',
        'id' => '2',
        'field' => 'signature_html',
        'from' => '',
        'to' => '',
        'table' => 'civicrm_email',
        'log_date' => '2016-04-06 02:53:44',
        'log_conn_id' => 'wooty wop wop',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetNoDate"
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

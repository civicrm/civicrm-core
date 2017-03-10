<?php
/**
 * Test Generated example demonstrating the Email.replace API.
 *
 * @return array
 *   API result array
 */
function email_replace_example() {
  $params = array(
    'contact_id' => 9,
    'values' => array(
      '0' => array(
        'location_type_id' => 18,
        'email' => '1-1@example.com',
        'is_primary' => 1,
      ),
      '1' => array(
        'location_type_id' => 18,
        'email' => '1-2@example.com',
        'is_primary' => 0,
      ),
      '2' => array(
        'location_type_id' => 18,
        'email' => '1-3@example.com',
        'is_primary' => 0,
      ),
      '3' => array(
        'location_type_id' => 19,
        'email' => '2-1@example.com',
        'is_primary' => 0,
      ),
      '4' => array(
        'location_type_id' => 19,
        'email' => '2-2@example.com',
        'is_primary' => 0,
      ),
    ),
  );

  try{
    $result = civicrm_api3('Email', 'replace', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'is_error' => 1,
      'error_message' => $errorMessage,
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
function email_replace_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 5,
    'values' => array(
      '12' => array(
        'id' => '12',
        'contact_id' => '9',
        'location_type_id' => '18',
        'email' => '1-1@example.com',
        'is_primary' => '1',
        'is_billing' => '',
        'on_hold' => '',
        'is_bulkmail' => '',
        'hold_date' => '',
        'reset_date' => '',
        'signature_text' => '',
        'signature_html' => '',
      ),
      '13' => array(
        'id' => '13',
        'contact_id' => '9',
        'location_type_id' => '18',
        'email' => '1-2@example.com',
        'is_primary' => 0,
        'is_billing' => '',
        'on_hold' => '',
        'is_bulkmail' => '',
        'hold_date' => '',
        'reset_date' => '',
        'signature_text' => '',
        'signature_html' => '',
      ),
      '14' => array(
        'id' => '14',
        'contact_id' => '9',
        'location_type_id' => '18',
        'email' => '1-3@example.com',
        'is_primary' => 0,
        'is_billing' => '',
        'on_hold' => '',
        'is_bulkmail' => '',
        'hold_date' => '',
        'reset_date' => '',
        'signature_text' => '',
        'signature_html' => '',
      ),
      '15' => array(
        'id' => '15',
        'contact_id' => '9',
        'location_type_id' => '19',
        'email' => '2-1@example.com',
        'is_primary' => 0,
        'is_billing' => '',
        'on_hold' => '',
        'is_bulkmail' => '',
        'hold_date' => '',
        'reset_date' => '',
        'signature_text' => '',
        'signature_html' => '',
      ),
      '16' => array(
        'id' => '16',
        'contact_id' => '9',
        'location_type_id' => '19',
        'email' => '2-2@example.com',
        'is_primary' => 0,
        'is_billing' => '',
        'on_hold' => '',
        'is_bulkmail' => '',
        'hold_date' => '',
        'reset_date' => '',
        'signature_text' => '',
        'signature_html' => '',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testReplaceEmail"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/EmailTest.php
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

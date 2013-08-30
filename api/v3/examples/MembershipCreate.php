<?php
/**
 * Test Generated example of using membership create API
 * *
 */
function membership_create_example(){
$params = array(
  'contact_id' => 30,
  'membership_type_id' => 48,
  'join_date' => '2009-01-21',
  'start_date' => '2009-01-21',
  'end_date' => '2009-12-21',
  'source' => 'Payment',
  'is_override' => 1,
  'status_id' => 31,
  'custom_1' => 'custom string',
);

try{
  $result = civicrm_api3('membership', 'create', $params);
}
catch (CiviCRM_API3_Exception $e) {
  // handle error here
  $errorMessage = $e->getMessage();
  $errorCode = $e->getErrorCode();
  $errorData = $e->getExtraParams();
  return array('error' => $errorMessage, 'error_code' => $errorCode, 'error_data' => $errorData);
}

return $result;
}

/**
 * Function returns array of result expected from previous function
 */
function membership_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array(
      '1' => array(
          'id' => '1',
          'contact_id' => '30',
          'membership_type_id' => '48',
          'join_date' => '20090121000000',
          'start_date' => '2013-07-29 00:00:00',
          'end_date' => '2013-08-04 00:00:00',
          'source' => 'Payment',
          'status_id' => '31',
          'is_override' => '1',
          'owner_membership_id' => '',
          'max_related' => '',
          'is_test' => 0,
          'is_pay_later' => '',
          'contribution_recur_id' => '',
          'campaign_id' => '',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateWithCustom and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/MembershipTest.php
*
* You can see the outcome of the API tests at
* http://tests.dev.civicrm.org/trunk/results-api_v3
*
* To Learn about the API read
* http://book.civicrm.org/developer/current/techniques/api/
*
* and review the wiki at
* http://wiki.civicrm.org/confluence/display/CRMDOC/CiviCRM+Public+APIs
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
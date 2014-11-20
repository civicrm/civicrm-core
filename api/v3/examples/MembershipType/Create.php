<?php
/**
 * Test Generated example of using membership_type create API
 * *
 */
function membership_type_create_example(){
$params = array(
  'name' => '40+ Membership',
  'description' => 'people above 40 are given health instructions',
  'member_of_contact_id' => 1,
  'financial_type_id' => 1,
  'domain_id' => '1',
  'minimum_fee' => '200',
  'duration_unit' => 'month',
  'duration_interval' => '10',
  'period_type' => 'rolling',
  'visibility' => 'public',
);

try{
  $result = civicrm_api3('membership_type', 'create', $params);
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
function membership_type_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 2,
  'values' => array(
      '2' => array(
          'id' => '2',
          'domain_id' => '1',
          'name' => '40+ Membership',
          'description' => 'people above 40 are given health instructions',
          'member_of_contact_id' => '1',
          'financial_type_id' => '1',
          'minimum_fee' => '200',
          'duration_unit' => 'month',
          'duration_interval' => '10',
          'period_type' => 'rolling',
          'fixed_period_start_day' => '',
          'fixed_period_rollover_day' => '',
          'relationship_type_id' => '',
          'relationship_direction' => '',
          'max_related' => '',
          'visibility' => 'Public',
          'weight' => '',
          'receipt_text_signup' => '',
          'receipt_text_renewal' => '',
          'auto_renew' => '',
          'is_active' => '',
          'contribution_type_id' => '1',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreate and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/MembershipTypeTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api/explorer
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/

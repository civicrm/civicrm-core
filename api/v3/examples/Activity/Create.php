<?php
/**
 * Test Generated example of using activity create API
 * *
 */
function activity_create_example(){
$params = array(
  'source_contact_id' => 1,
  'activity_type_id' => '49',
  'subject' => 'test activity type id',
  'activity_date_time' => '2011-06-02 14:36:13',
  'status_id' => 2,
  'priority_id' => 1,
  'duration' => 120,
  'location' => 'Pensulvania',
  'details' => 'a test activity',
  'custom_1' => 'custom string',
);

try{
  $result = civicrm_api3('activity', 'create', $params);
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
function activity_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array(
      '1' => array(
          'id' => '1',
          'source_record_id' => '',
          'activity_type_id' => '49',
          'subject' => 'test activity type id',
          'activity_date_time' => '20110602143613',
          'duration' => '120',
          'location' => 'Pensulvania',
          'phone_id' => '',
          'phone_number' => '',
          'details' => 'a test activity',
          'status_id' => '2',
          'priority_id' => '1',
          'parent_id' => '',
          'is_test' => '',
          'medium_id' => '',
          'is_auto' => '',
          'relationship_id' => '',
          'is_current_revision' => '',
          'original_id' => '',
          'result' => '',
          'is_deleted' => '',
          'campaign_id' => '',
          'engagement_level' => '',
          'weight' => '',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testActivityCreateCustom and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ActivityTest.php
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

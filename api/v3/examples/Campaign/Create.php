<?php
/**
 * Test Generated example demonstrating the Campaign.create API.
 *
 * Create a campaign - Note use of relative dates here:
 * @link http://www.php.net/manual/en/datetime.formats.relative.php.
 *
 * @return array
 *   API result array
 */
function campaign_create_example() {
  $params = array(
    'title' => 'campaign title',
    'description' => 'Call people, ask for money',
    'created_date' => 'first sat of July 2008',
  );

  try{
    $result = civicrm_api3('Campaign', 'create', $params);
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
function campaign_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => array(
      '1' => array(
        'id' => '1',
        'name' => 'campaign_title',
        'title' => 'campaign title',
        'description' => 'Call people, ask for money',
        'start_date' => '',
        'end_date' => '',
        'campaign_type_id' => '',
        'status_id' => '',
        'external_identifier' => '',
        'parent_id' => '',
        'is_active' => '',
        'created_id' => '',
        'created_date' => '2013-07-28 08:49:19',
        'last_modified_id' => '',
        'last_modified_date' => '',
        'goal_general' => '',
        'goal_revenue' => '',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateCampaign"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/CampaignTest.php
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

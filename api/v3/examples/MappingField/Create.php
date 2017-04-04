<?php
/**
 * Test Generated example demonstrating the MappingField.create API.
 *
 * @return array
 *   API result array
 */
function mapping_field_create_example() {
  $params = array(
    'mapping_id' => 1,
    'name' => 'last_name',
    'contact_type' => 'Individual',
    'column_number' => 2,
    'grouping' => 1,
  );

  try{
    $result = civicrm_api3('MappingField', 'create', $params);
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
function mapping_field_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => array(
      '1' => array(
        'id' => '1',
        'mapping_id' => '1',
        'name' => 'last_name',
        'contact_type' => 'Individual',
        'column_number' => '2',
        'location_type_id' => '',
        'phone_type_id' => '',
        'im_provider_id' => '',
        'website_type_id' => '',
        'relationship_type_id' => '',
        'relationship_direction' => '',
        'grouping' => '1',
        'operator' => '',
        'value' => '',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateMappingField"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/MappingFieldTest.php
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

<?php
/**
 * Test Generated example demonstrating the PriceFieldValue.create API.
 *
 * @return array
 *   API result array
 */
function price_field_value_create_example() {
  $params = array(
    'price_field_id' => 13,
    'membership_type_id' => 5,
    'name' => 'memType1',
    'label' => 'memType1',
    'amount' => 90,
    'membership_num_terms' => 2,
    'is_active' => 1,
    'financial_type_id' => 2,
  );

  try{
    $result = civicrm_api3('PriceFieldValue', 'create', $params);
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
function price_field_value_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 10,
    'values' => array(
      '10' => array(
        'id' => '10',
        'price_field_id' => '13',
        'name' => 'memType1',
        'label' => 'memType1',
        'description' => '',
        'help_pre' => '',
        'help_post' => '',
        'amount' => '90',
        'count' => '',
        'max_value' => '',
        'weight' => '1',
        'membership_type_id' => '5',
        'membership_num_terms' => '2',
        'is_default' => '',
        'is_active' => '1',
        'financial_type_id' => '2',
        'non_deductible_amount' => '',
        'contribution_type_id' => '2',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreatePriceFieldValuewithMultipleTerms"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PriceFieldValueTest.php
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

<?php
/**
 * Test Generated example demonstrating the Website.getfields API.
 *
 * @return array
 *   API result array
 */
function website_getfields_example() {
  $params = array(
    'action' => 'get',
  );

  try{
    $result = civicrm_api3('Website', 'getfields', $params);
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
function website_getfields_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 4,
    'values' => array(
      'id' => array(
        'name' => 'id',
        'type' => 1,
        'title' => 'Website ID',
        'required' => TRUE,
        'api.aliases' => array(
          '0' => 'website_id',
        ),
      ),
      'contact_id' => array(
        'name' => 'contact_id',
        'type' => 1,
        'title' => 'Contact',
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'FKApiName' => 'Contact',
      ),
      'url' => array(
        'name' => 'url',
        'type' => 2,
        'title' => 'Website',
        'maxlength' => 128,
        'size' => 30,
        'import' => TRUE,
        'where' => 'civicrm_website.url',
        'headerPattern' => '/Website/i',
        'dataPattern' => '/^[A-Za-z][0-9A-Za-z]{20,}$/',
        'export' => TRUE,
        'html' => array(
          'type' => 'Text',
        ),
      ),
      'website_type_id' => array(
        'name' => 'website_type_id',
        'type' => 1,
        'title' => 'Website Type',
        'html' => array(
          'type' => 'Select',
        ),
        'pseudoconstant' => array(
          'optionGroupName' => 'website_type',
        ),
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetFields"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/WebsiteTest.php
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

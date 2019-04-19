<?php
/**
 * Test Generated example demonstrating the Website.get API.
 *
 * Demonostrates returning field metadata
 *
 * @return array
 *   API result array
 */
function website_get_example() {
  $params = [
    'options' => [
      'metadata' => [
        '0' => 'fields',
      ],
    ],
  ];

  try{
    $result = civicrm_api3('Website', 'get', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return [
      'is_error' => 1,
      'error_message' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    ];
  }

  return $result;
}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function website_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 0,
    'values' => [],
    'metadata' => [
      'fields' => [
        'id' => [
          'name' => 'id',
          'type' => '1',
          'title' => 'Website ID',
          'description' => 'Unique Website ID',
          'required' => '1',
          'table_name' => 'civicrm_website',
          'entity' => 'Website',
          'bao' => 'CRM_Core_BAO_Website',
          'api.aliases' => [
            '0' => 'website_id',
          ],
        ],
        'contact_id' => [
          'name' => 'contact_id',
          'type' => '1',
          'title' => 'Contact',
          'description' => 'FK to Contact ID',
          'table_name' => 'civicrm_website',
          'entity' => 'Website',
          'bao' => 'CRM_Core_BAO_Website',
          'FKClassName' => 'CRM_Contact_DAO_Contact',
          'FKApiName' => 'Contact',
        ],
        'url' => [
          'name' => 'url',
          'type' => '2',
          'title' => 'Website',
          'description' => 'Website',
          'maxlength' => '128',
          'size' => '30',
          'import' => '1',
          'where' => 'civicrm_website.url',
          'headerPattern' => '/Website/i',
          'dataPattern' => '/^[A-Za-z][0-9A-Za-z]{20,}$/',
          'export' => '1',
          'table_name' => 'civicrm_website',
          'entity' => 'Website',
          'bao' => 'CRM_Core_BAO_Website',
          'html' => [
            'type' => 'Text',
            'maxlength' => '128',
            'size' => '30',
          ],
        ],
        'website_type_id' => [
          'name' => 'website_type_id',
          'type' => '1',
          'title' => 'Website Type',
          'description' => 'Which Website type does this website belong to.',
          'table_name' => 'civicrm_website',
          'entity' => 'Website',
          'bao' => 'CRM_Core_BAO_Website',
          'html' => [
            'type' => 'Select',
            'size' => '6',
            'maxlength' => '14',
          ],
          'pseudoconstant' => [
            'optionGroupName' => 'website_type',
            'optionEditPath' => 'civicrm/admin/options/website_type',
          ],
        ],
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetMetadata"
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

<?php
/**
 * Test Generated example demonstrating the Website.getfields API.
 *
 * @return array
 *   API result array
 */
function website_getfields_example() {
  $params = [
    'action' => 'get',
  ];

  try{
    $result = civicrm_api3('Website', 'getfields', $params);
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
function website_getfields_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 4,
    'values' => [
      'id' => [
        'name' => 'id',
        'type' => 1,
        'title' => 'Website ID',
        'description' => 'Unique Website ID',
        'required' => TRUE,
        'where' => 'civicrm_website.id',
        'table_name' => 'civicrm_website',
        'entity' => 'Website',
        'bao' => 'CRM_Core_BAO_Website',
        'localizable' => 0,
        'is_core_field' => TRUE,
        'api.aliases' => [
          '0' => 'website_id',
        ],
      ],
      'contact_id' => [
        'name' => 'contact_id',
        'type' => 1,
        'title' => 'Contact',
        'description' => 'FK to Contact ID',
        'where' => 'civicrm_website.contact_id',
        'table_name' => 'civicrm_website',
        'entity' => 'Website',
        'bao' => 'CRM_Core_BAO_Website',
        'localizable' => 0,
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'is_core_field' => TRUE,
        'FKApiName' => 'Contact',
      ],
      'url' => [
        'name' => 'url',
        'type' => 2,
        'title' => 'Website',
        'description' => 'Website',
        'maxlength' => 128,
        'size' => 30,
        'import' => TRUE,
        'where' => 'civicrm_website.url',
        'headerPattern' => '/Website/i',
        'dataPattern' => '/^[A-Za-z][0-9A-Za-z]{20,}$/',
        'export' => TRUE,
        'table_name' => 'civicrm_website',
        'entity' => 'Website',
        'bao' => 'CRM_Core_BAO_Website',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'maxlength' => 128,
          'size' => 30,
        ],
        'is_core_field' => TRUE,
      ],
      'website_type_id' => [
        'name' => 'website_type_id',
        'type' => 1,
        'title' => 'Website Type',
        'description' => 'Which Website type does this website belong to.',
        'where' => 'civicrm_website.website_type_id',
        'table_name' => 'civicrm_website',
        'entity' => 'Website',
        'bao' => 'CRM_Core_BAO_Website',
        'localizable' => 0,
        'html' => [
          'type' => 'Select',
          'size' => 6,
          'maxlength' => 14,
        ],
        'pseudoconstant' => [
          'optionGroupName' => 'website_type',
          'optionEditPath' => 'civicrm/admin/options/website_type',
        ],
        'is_core_field' => TRUE,
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetFields"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/WebsiteTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-Core-Matrix/
*
* To Learn about the API read
* https://docs.civicrm.org/dev/en/latest/api/
*
* Browse the API on your own site with the API Explorer. It is in the main
* CiviCRM menu, under: Support > Development > API Explorer.
*
* Read more about testing here
* https://docs.civicrm.org/dev/en/latest/testing/
*
* API Standards documentation:
* https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
*/

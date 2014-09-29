<?php
/**
 * Test Generated example of using report_template getrows API
 * Retrieve rows from a report template (optionally providing the instance_id) *
 */
function report_template_getrows_example(){
$params = array(
  'report_id' => 'contact/summary',
  'options' => array(
      'metadata' => array(
          '0' => 'labels',
          '1' => 'title',
        ),
    ),
);

try{
  $result = civicrm_api3('report_template', 'getrows', $params);
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
function report_template_getrows_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 2,
  'values' => array(
      '0' => array(
          'civicrm_contact_sort_name' => 'Default Organization',
          'civicrm_contact_id' => '1',
        ),
      '1' => array(
          'civicrm_contact_sort_name' => 'Second Domain',
          'civicrm_contact_id' => '2',
        ),
    ),
  'metadata' => array(
      'title' => 'ERROR: Title is not Set',
      'labels' => array(
          'civicrm_contact_sort_name' => 'Contact Name',
          'civicrm_contact_id' => 'Internal Contact ID',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testReportTemplateGetRowsContactSummary and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ReportTemplateTest.php
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

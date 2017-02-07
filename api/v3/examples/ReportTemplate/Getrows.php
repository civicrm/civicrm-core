<?php
/**
 * Test Generated example demonstrating the ReportTemplate.getrows API.
 *
 * Retrieve rows from a report template (optionally providing the instance_id).
 *
 * @return array
 *   API result array
 */
function report_template_getrows_example() {
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
    $result = civicrm_api3('ReportTemplate', 'getrows', $params);
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
function report_template_getrows_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 2,
    'values' => array(
      '0' => array(
        'civicrm_contact_sort_name' => 'Second Domain',
        'civicrm_contact_id' => '2',
        'civicrm_contact_sort_name_link' => '/index.php?q=civicrm/report/contact/detail&amp;reset=1&amp;force=1&amp;id_op=eq&amp;id_value=2',
        'civicrm_contact_sort_name_hover' => 'View Contact Detail Report for this contact',
      ),
      '1' => array(
        'civicrm_contact_sort_name' => 'Unit Test Organization',
        'civicrm_contact_id' => '1',
        'civicrm_contact_sort_name_link' => '/index.php?q=civicrm/report/contact/detail&amp;reset=1&amp;force=1&amp;id_op=eq&amp;id_value=1',
        'civicrm_contact_sort_name_hover' => 'View Contact Detail Report for this contact',
      ),
    ),
    'metadata' => array(
      'title' => 'ERROR: Title is not Set',
      'labels' => array(
        'civicrm_contact_sort_name' => 'Contact Name',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testReportTemplateGetRowsContactSummary"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ReportTemplateTest.php
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

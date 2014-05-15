<?php
/**
 * Test Generated example of using report_template getstatistics API
 * Get Statistics from a report (note there isn't much data to get in the test DB :-( *
 */
function report_template_getstatistics_example(){
$params = array(
  'report_id' => 'member/contributionDetail',
);

try{
  $result = civicrm_api3('report_template', 'getstatistics', $params);
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
function report_template_getstatistics_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 2,
  'values' => array(
      'counts' => array(
          'rowCount' => array(
              'title' => 'Row(s) Listed',
              'value' => 0,
            ),
          'amount' => array(
              'title' => 'Total Amount',
              'value' => '',
              'type' => 2,
            ),
          'avg' => array(
              'title' => 'Average',
              'value' => '',
              'type' => 2,
            ),
        ),
      'filters' => array(),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testReportTemplateGetStatisticsAllReports and can be found in
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

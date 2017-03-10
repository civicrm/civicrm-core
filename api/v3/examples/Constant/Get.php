<?php
/**
 * Test Generated example demonstrating the Constant.get API.
 *
 * @deprecated
 * The Constant api is deprecated as of CiviCRM 4.4. Please use the getoptions api action instead.
 *
 * @return array
 *   API result array
 */
function constant_get_example() {
  $params = array(
    'name' => 'activityType',
  );

  try{
    $result = civicrm_api3('Constant', 'get', $params);
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
function constant_get_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 36,
    'values' => array(
      '1' => 'Meeting',
      '2' => 'Phone Call',
      '3' => 'Email',
      '4' => 'Outbound SMS',
      '5' => 'Event Registration',
      '6' => 'Contribution',
      '7' => 'Membership Signup',
      '8' => 'Membership Renewal',
      '9' => 'Tell a Friend',
      '10' => 'Pledge Acknowledgment',
      '11' => 'Pledge Reminder',
      '12' => 'Inbound Email',
      '17' => 'Membership Renewal Reminder',
      '19' => 'Bulk Email',
      '22' => 'Print/Merge Document',
      '34' => 'Mass SMS',
      '35' => 'Change Membership Status',
      '36' => 'Change Membership Type',
      '37' => 'Cancel Recurring Contribution',
      '38' => 'Update Recurring Contribution Billing Details',
      '39' => 'Update Recurring Contribution',
      '40' => 'Reminder Sent',
      '41' => 'Export Accounting Batch',
      '42' => 'Create Batch',
      '43' => 'Edit Batch',
      '44' => 'SMS delivery',
      '45' => 'Inbound SMS',
      '46' => 'Payment',
      '47' => 'Refund',
      '48' => 'Change Registration',
      '49' => 'Downloaded Invoice',
      '50' => 'Emailed Invoice',
      '51' => 'Contact Merged',
      '52' => 'Contact Deleted by Merge',
      '53' => 'Failed Payment',
      '54' => 'Close Accounting Period',
    ),
    'deprecated' => 'The Constant api is deprecated as of CiviCRM 4.4. Please use the getoptions api action instead.',
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testActivityType"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ConstantTest.php
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

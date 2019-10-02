<?php
/**
 * Test Generated example demonstrating the ActivityType.get API.
 *
 * @deprecated
 * The ActivityType api is deprecated. Please use the OptionValue api instead.
 *
 * @return array
 *   API result array
 */
function activity_type_get_example() {
  $params = [];

  try{
    $result = civicrm_api3('ActivityType', 'get', $params);
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
function activity_type_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 35,
    'values' => [
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
      '54' => 'Failed Payment',
    ],
    'deprecated' => 'The ActivityType api is deprecated. Please use the OptionValue api instead.',
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testActivityTypeGet"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ActivityTypeTest.php
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

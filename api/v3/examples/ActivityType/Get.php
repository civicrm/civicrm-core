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
    'count' => 54,
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
      '13' => 'Open Case',
      '14' => 'Follow up',
      '15' => 'Change Case Type',
      '16' => 'Change Case Status',
      '17' => 'Membership Renewal Reminder',
      '18' => 'Change Case Start Date',
      '19' => 'Bulk Email',
      '20' => 'Assign Case Role',
      '21' => 'Remove Case Role',
      '22' => 'Print/Merge Document',
      '23' => 'Merge Case',
      '24' => 'Reassigned Case',
      '25' => 'Link Cases',
      '26' => 'Change Case Tags',
      '27' => 'Add Client To Case',
      '28' => 'Survey',
      '29' => 'Canvass',
      '30' => 'PhoneBank',
      '31' => 'WalkList',
      '32' => 'Petition Signature',
      '33' => 'Change Custom Data',
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

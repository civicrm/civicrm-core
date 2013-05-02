<?php

/*
 
 */
function activity_type_get_example(){
$params = array( 
  'version' => 3,
);

  $result = civicrm_api( 'activity_type','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function activity_type_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 44,
  'values' => array( 
      '1' => 'Meeting',
      '2' => 'Phone Call',
      '3' => 'Email',
      '4' => 'Text Message (SMS)',
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
      '22' => 'Print PDF Letter',
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
      '44' => 'Test activity type',
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testActivityTypeGet and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ActivityTypeTest.php
*
* You can see the outcome of the API tests at
* http://tests.dev.civicrm.org/trunk/results-api_v3
*
* To Learn about the API read
* http://book.civicrm.org/developer/current/techniques/api/
*
* and review the wiki at
* http://wiki.civicrm.org/confluence/display/CRMDOC/CiviCRM+Public+APIs
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
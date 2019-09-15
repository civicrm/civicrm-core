<?php
/**
 * Test Generated example demonstrating the ReportTemplate.getrows API.
 *
 * Retrieve rows from a mailing opened report template.
 *
 * @return array
 *   API result array
 */
function report_template_getrows_example() {
  $params = [
    'report_id' => 'Mailing/opened',
    'options' => [
      'metadata' => [
        '0' => 'labels',
        '1' => 'title',
      ],
    ],
  ];

  try{
    $result = civicrm_api3('ReportTemplate', 'getrows', $params);
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
function report_template_getrows_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 5,
    'values' => [
      '0' => [
        'civicrm_contact_id' => '102',
        'civicrm_contact_sort_name' => 'One, Test',
        'civicrm_mailing_mailing_name' => 'Second Test Mailing Events',
        'civicrm_mailing_mailing_name_alias' => 'Second Test Mailing Events',
        'civicrm_mailing_mailing_subject' => 'Hello again, {contact.display_name}',
        'civicrm_mailing_event_opened_id' => '17',
        'civicrm_mailing_event_opened_time_stamp' => '2011-05-26 13:23:22',
        'civicrm_contact_sort_name_link' => '/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=102',
        'civicrm_contact_sort_name_hover' => 'View Contact details for this contact.',
      ],
      '1' => [
        'civicrm_contact_id' => '109',
        'civicrm_contact_sort_name' => 'Five, Test',
        'civicrm_mailing_mailing_name' => 'First Mailing Events',
        'civicrm_mailing_mailing_name_alias' => 'First Mailing Events',
        'civicrm_mailing_mailing_subject' => 'Hello {contact.display_name}',
        'civicrm_mailing_event_opened_id' => '9',
        'civicrm_mailing_event_opened_time_stamp' => '2011-05-26 13:19:03',
        'civicrm_contact_sort_name_link' => '/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=109',
        'civicrm_contact_sort_name_hover' => 'View Contact details for this contact.',
      ],
      '2' => [
        'civicrm_contact_id' => '110',
        'civicrm_contact_sort_name' => 'Six, Test',
        'civicrm_mailing_mailing_name' => 'First Mailing Events',
        'civicrm_mailing_mailing_name_alias' => 'First Mailing Events',
        'civicrm_mailing_mailing_subject' => 'Hello {contact.display_name}',
        'civicrm_mailing_event_opened_id' => '5',
        'civicrm_mailing_event_opened_time_stamp' => '2011-05-26 13:17:54',
        'civicrm_contact_sort_name_link' => '/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=110',
        'civicrm_contact_sort_name_hover' => 'View Contact details for this contact.',
      ],
      '3' => [
        'civicrm_contact_id' => '111',
        'civicrm_contact_sort_name' => 'Seven, Test',
        'civicrm_mailing_mailing_name' => 'First Mailing Events',
        'civicrm_mailing_mailing_name_alias' => 'First Mailing Events',
        'civicrm_mailing_mailing_subject' => 'Hello {contact.display_name}',
        'civicrm_mailing_event_opened_id' => '15',
        'civicrm_mailing_event_opened_time_stamp' => '2011-05-26 13:20:59',
        'civicrm_contact_sort_name_link' => '/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=111',
        'civicrm_contact_sort_name_hover' => 'View Contact details for this contact.',
      ],
      '4' => [
        'civicrm_contact_id' => '112',
        'civicrm_contact_sort_name' => 'Eight, Test',
        'civicrm_mailing_mailing_name' => 'First Mailing Events',
        'civicrm_mailing_mailing_name_alias' => 'First Mailing Events',
        'civicrm_mailing_mailing_subject' => 'Hello {contact.display_name}',
        'civicrm_mailing_event_opened_id' => '11',
        'civicrm_mailing_event_opened_time_stamp' => '2011-05-26 13:19:44',
        'civicrm_contact_sort_name_link' => '/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=112',
        'civicrm_contact_sort_name_hover' => 'View Contact details for this contact.',
      ],
    ],
    'metadata' => [
      'title' => 'ERROR: Title is not Set',
      'labels' => [
        'civicrm_contact_sort_name' => 'Contact Name',
        'civicrm_mailing_mailing_name' => 'Mailing Name',
        'civicrm_mailing_mailing_subject' => 'Mailing Subject',
        'civicrm_mailing_event_opened_time_stamp' => 'Open Date',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testReportTemplateGetRowsMailingUniqueOpened"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ReportTemplateTest.php
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

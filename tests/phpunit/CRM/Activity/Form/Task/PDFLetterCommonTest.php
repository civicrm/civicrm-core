<?php

/**
 * Class CRM_Activity_Form_Task_PDFLetterCommonTest
 * @group headless
 */
class CRM_Activity_Form_Task_PDFLetterCommonTest extends CiviUnitTestCase {

  public function setUp() {
    $this->useTransaction(TRUE);
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  public function testCreateDocumentBasicTokens() {
    $activity = $this->activityCreate();
    $data = [
      ["Subject: {activity.subject}", "Subject: Discussion on warm beer"],
      ["Date: {activity.activity_date_time}", "Date: " . \CRM_Utils_Date::customFormat(date('Ymd')) . " 12:00 AM"],
      ["Duration: {activity.duration}", "Duration: 90"],
      ["Location: {activity.location}", "Location: Baker Street"],
      ["Details: {activity.details}", "Details: Lets schedule a meeting"],
      ["Status ID: {activity.status_id}", "Status ID: 1"],
      ["Status: {activity.status}", "Status: Scheduled"],
      ["Activity Type ID: {activity.activity_type_id}", "Activity Type ID: 1"],
      ["Activity Type: {activity.activity_type}", "Activity Type: Meeting"],
      ["Activity ID: {activity.activity_id}", "Activity ID: " . $activity['id']],
    ];
    $html_message = "\n" . implode("\n", CRM_Utils_Array::collect('0', $data)) . "\n";
    $output = CRM_Activity_Form_Task_PDFLetterCommon::createDocument([$activity['id']], $html_message, ['is_unit_test' => TRUE]);

    // Check some basic fields
    foreach ($data as $line) {
      $this->assertContains("\n" . $line[1] . "\n", $output[0]);
    }
  }

  public function testCreateDocumentCustomFieldTokens() {
    // Set up custom group, and field
    // returns custom_group_id, custom_field_id, custom_field_option_group_id, custom_field_group_options
    $cg = $this->entityCustomGroupWithSingleStringMultiSelectFieldCreate("MyCustomField", "ActivityTest.php");
    $cf = 'custom_' . $cg['custom_field_id'];
    foreach (array_keys($cg['custom_field_group_options']) as $option) {
      $activity = $this->activityCreate([$cf => $option]);
      $activities[] = [
        'id' => $activity['id'],
        'option' => $option,
      ];
    }

    $html_message = "Custom: {activity.$cf}";
    $activityIds = CRM_Utils_Array::collect('id', $activities);
    $output = CRM_Activity_Form_Task_PDFLetterCommon::createDocument($activityIds, $html_message, ['is_unit_test' => TRUE]);
    // Should have one row of output per activity
    $this->assertEquals(count($activities), count($output));

    // Check each line has the correct substitution
    foreach ($output as $key => $line) {
      $this->assertEquals($line, "Custom: " . $cg['custom_field_group_options'][$activities[$key]['option']]);
    }
  }

  public function testCreateDocumentSpecialTokens() {
    $this->markTestIncomplete('special tokens not yet merged - see https://github.com/civicrm/civicrm-core/pull/12012');
    $activity = $this->activityCreate();
    $data = [
      ["Source First Name: {activity.source_first_name}", "Source First Name: Anthony"],
      ["Target N First Name: {activity.target_N_first_name}", "Target N First Name: Julia"],
      ["Target 0 First Name: {activity.target_0_first_name}", "Target 0 First Name: Julia"],
      ["Target 1 First Name: {activity.target_1_first_name}", "Target 1 First Name: Julia"],
      ["Target 2 First Name: {activity.target_2_first_name}", "Target 2 First Name: "],
      ["Assignee N First Name: {activity.target_N_first_name}", "Assignee N First Name: Julia"],
      ["Assignee 0 First Name: {activity.target_0_first_name}", "Assignee 0 First Name: Julia"],
      ["Assignee 1 First Name: {activity.target_1_first_name}", "Assignee 1 First Name: Julia"],
      ["Assignee 2 First Name: {activity.target_2_first_name}", "Assignee 2 First Name: "],
      ["Assignee Count: {activity.assignees_count}", "Assignee Count: 1"],
      ["Target Count: {activity.targets_count}", "Target Count: 1"],
    ];
    $html_message = "\n" . implode("\n", CRM_Utils_Array::collect('0', $data)) . "\n";
    $output = CRM_Activity_Form_Task_PDFLetterCommon::createDocument([$activity['id']], $html_message, ['is_unit_test' => TRUE]);

    foreach ($data as $line) {
      $this->assertContains("\n" . $line[1] . "\n", $output[0]);
    }
  }

  public function testCreateDocumentUnknownTokens() {
    $activity = $this->activityCreate();
    $html_message = "Unknown token: {activity.something_unknown}";
    $output = CRM_Activity_Form_Task_PDFLetterCommon::createDocument([$activity['id']], $html_message, ['is_unit_test' => TRUE]);
    // Unknown tokens should be left alone
    $this->assertEquals($output[0], $html_message);
  }

}

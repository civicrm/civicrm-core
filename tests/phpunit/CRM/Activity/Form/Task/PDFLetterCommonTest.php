<?php

use Civi\Token\TokenProcessor;

/**
 * Class CRM_Activity_Form_Task_PDFLetterCommonTest
 * @group headless
 */
class CRM_Activity_Form_Task_PDFLetterCommonTest extends CiviUnitTestCase {

  /**
   * Set up for tests.
   */
  public function setUp(): void {
    $this->useTransaction();
    parent::setUp();
  }

  /**
   * Test create a document with basic tokens.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testCreateDocumentBasicTokens(): void {
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    $this->enableCiviCampaign();

    $activity = $this->activityCreate(['campaign_id' => $this->campaignCreate()]);
    $data = [
      ['Subject: {activity.subject}', 'Subject: Discussion on warm beer'],
      ['Date: {activity.activity_date_time}', 'Date: ' . CRM_Utils_Date::customFormat(date('Ymd'))],
      ['Duration: {activity.duration}', 'Duration: 90'],
      ['Location: {activity.location}', 'Location: Baker Street'],
      ['Details: {activity.details}', 'Details: Lets schedule a meeting'],
      ['Status ID: {activity.status_id}', 'Status ID: 1'],
      ['(legacy) Status: {activity.status}', '(legacy) Status: Scheduled'],
      ['Status: {activity.status_id:label}', 'Status: Scheduled'],
      ['Activity Type ID: {activity.activity_type_id}', 'Activity Type ID: 1'],
      ['(legacy) Activity Type: {activity.activity_type}', '(legacy) Activity Type: Meeting'],
      ['Activity Type: {activity.activity_type_id:label}', 'Activity Type: Meeting'],
      ['(legacy) Activity ID: {activity.activity_id}', '(legacy) Activity ID: ' . $activity['id']],
      ['Activity ID: {activity.id}', 'Activity ID: ' . $activity['id']],
      ['(just weird) Case ID: {activity.case_id}', '(just weird) Case ID: ' . ''],
    ];
    $tokenProcessor = new TokenProcessor(Civi::dispatcher(), ['schema' => ['activityId']]);

    $this->assertEquals(array_merge($this->getActivityTokens(), CRM_Core_SelectValues::domainTokens()), $tokenProcessor->listTokens());
    $html_message = "\n" . implode("\n", CRM_Utils_Array::collect('0', $data)) . "\n";
    $form = $this->getFormObject('CRM_Activity_Form_Task_PDF');
    try {
      $output = $form->createDocument([$activity['id']], $html_message, []);
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $output = $e->errorData['html'];
    }
    // Check some basic fields
    foreach ($data as $line) {
      $this->assertStringContainsString("\n" . $line[1] . "\n", $output[0]);
    }
  }

  /**
   * Get expected activity Tokens.
   *
   * @return string[]
   */
  protected function getActivityTokens(): array {
    return [
      '{activity.id}' => 'Activity ID',
      '{activity.subject}' => 'Activity Subject',
      '{activity.details}' => 'Activity Details',
      '{activity.activity_date_time}' => 'Activity Date-Time',
      '{activity.created_date}' => 'Activity Created Date',
      '{activity.modified_date}' => 'Activity Modified Date',
      '{activity.activity_type_id}' => 'Activity Type ID',
      '{activity.status_id}' => 'Activity Status ID',
      '{activity.location}' => 'Activity Location',
      '{activity.duration}' => 'Activity Duration',
      '{activity.activity_type_id:label}' => 'Activity Type',
      '{activity.activity_type_id:name}' => 'Machine name: Activity Type',
      '{activity.status_id:label}' => 'Activity Status',
      '{activity.status_id:name}' => 'Machine name: Activity Status',
      '{activity.campaign_id:label}' => 'Campaign',
      '{activity.campaign_id:name}' => 'Machine name: Campaign',
      '{activity.campaign_id}' => 'Campaign ID',
      '{activity.case_id}' => 'Activity Case ID',
    ];
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
    $form = $this->getFormObject('CRM_Activity_Form_Task_PDF');
    try {
      $output = $form->createDocument($activityIds, $html_message, []);
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $output = $e->errorData['html'];
    }
    // Should have one row of output per activity
    $this->assertCount(count($activities), $output);

    // Check each line has the correct substitution
    foreach ($output as $key => $line) {
      $this->assertEquals($line, 'Custom: ' . $cg['custom_field_group_options'][$activities[$key]['option']]);
    }
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testCreateDocumentSpecialTokens(): void {
    $this->markTestIncomplete('special tokens not yet merged - see https://github.com/civicrm/civicrm-core/pull/12012');
    $activity = $this->activityCreate();
    $data = [
      ['Source First Name: {activity.source_first_name}', 'Source First Name: Anthony'],
      ['Target N First Name: {activity.target_N_first_name}', 'Target N First Name: Julia'],
      ['Target 0 First Name: {activity.target_0_first_name}', 'Target 0 First Name: Julia'],
      ['Target 1 First Name: {activity.target_1_first_name}', 'Target 1 First Name: Julia'],
      ['Target 2 First Name: {activity.target_2_first_name}', 'Target 2 First Name: '],
      ['Assignee N First Name: {activity.target_N_first_name}', 'Assignee N First Name: Julia'],
      ['Assignee 0 First Name: {activity.target_0_first_name}', 'Assignee 0 First Name: Julia'],
      ['Assignee 1 First Name: {activity.target_1_first_name}', 'Assignee 1 First Name: Julia'],
      ['Assignee 2 First Name: {activity.target_2_first_name}', 'Assignee 2 First Name: '],
      ['Assignee Count: {activity.assignees_count}', 'Assignee Count: 1'],
      ['Target Count: {activity.targets_count}', 'Target Count: 1'],
    ];
    $html_message = "\n" . implode("\n", CRM_Utils_Array::collect('0', $data)) . "\n";
    $form = $this->getFormObject('CRM_Activity_Form_Task_PDF');
    $output = $form->createDocument([$activity['id']], $html_message, []);

    foreach ($data as $line) {
      $this->assertContains("\n" . $line[1] . "\n", $output[0]);
    }
  }

  /**
   * Unknown tokens are removed at the very end.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testCreateDocumentUnknownTokens(): void {
    $activity = $this->activityCreate();
    $html_message = 'Unknown token:{activity.something_unknown}';
    $form = $this->getFormObject('CRM_Activity_Form_Task_PDF', ['document_type' => 'pdf']);
    try {
      $form->createDocument([$activity['id']], $html_message, []);
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $html = $e->errorData['html'];
      $this->assertStringContainsString('<div id="crm-container">
Unknown token:
    </div>', $html);
      return;
    }
    $this->fail('should be unreachable');
  }

}

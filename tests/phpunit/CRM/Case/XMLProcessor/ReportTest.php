<?php
require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Class CRM_Case_XMLProcessor_ReportTest
 * @group headless
 */
class CRM_Case_XMLProcessor_ReportTest extends CiviCaseTestCase {

  public function setUp(): void {
    parent::setUp();

    $this->simplifyCaseTypeDefinition();

    $this->report = new CRM_Case_XMLProcessor_Report();
  }

  public function tearDown(): void {
    parent::tearDown();
  }

  /**
   * Test that getCaseReport has the right output.
   *
   * @param $activitySetName string Also acts as data provider test identifier.
   * @param $expected array
   *
   * @dataProvider caseReportDataProvider
   */
  public function testGetCaseReport($activitySetName, $expected) {
    $client_id = $this->individualCreate([
      'first_name' => 'Casey',
      'middle_name' => '',
      'last_name' => 'Reportee',
      'prefix_id' => NULL,
      'suffix_id' => NULL,
    ]);
    $caseObj = $this->createCase($client_id, $this->_loggedInUser, ['start_date' => '2019-11-14', 'start_date_time' => '20191114000000']);
    $case_id = $caseObj->id;

    // Add an additional meeting activity not in the timeline to the case.
    $meetingTypeId = $this->callAPISuccess('OptionValue', 'getsingle', [
      'return' => ["value"],
      'option_group_id' => 'activity_type',
      'name' => 'Meeting',
    ]);
    $this->callAPISuccess('activity', 'create', [
      'case_id' => $case_id,
      'activity_type_id' => $meetingTypeId['value'],
      'activity_date_time' => '20191114123456',
      'subject' => 'Test Meeting',
      'source_contact_id' => $this->_loggedInUser,
      'target_contact_id' => $client_id,
    ]);

    $caseReportParams = [
      'is_redact' => FALSE,
      'include_activities' => 1,
    ];

    // run the thing we're testing and get the output vars
    $template = CRM_Case_XMLProcessor_Report::populateCaseReportTemplate($client_id, $case_id, $activitySetName, $caseReportParams, $this->report);
    $assigned_vars = $template->get_template_vars();

    // Update $expected now since dataprovider doesn't have access to the variables from setup() because it runs before setup.
    $this->updateExpectedBecauseDataProviderEvaluatesBeforeEverything($expected, $client_id, $case_id);

    foreach ($expected as $key => $value) {
      // does the assigned template var match the expected value?
      $this->assertEquals($value, $assigned_vars[$key], "$activitySetName: $key does not match" . print_r($assigned_vars[$key], TRUE));
    }
  }

  /**
   * This is similar to testGetCaseReport but test with a timeline that
   * does have Meeting in it.
   */
  public function testGetCaseReportWithMeetingInTimeline() {
    $client_id = $this->individualCreate([
      'first_name' => 'Casey',
      'middle_name' => '',
      'last_name' => 'Reportee',
      'prefix_id' => NULL,
      'suffix_id' => NULL,
    ]);
    $caseObj = $this->createCase($client_id, $this->_loggedInUser, ['start_date' => '2019-11-14', 'start_date_time' => '20191114000000']);
    $case_id = $caseObj->id;

    // Now update the timeline so it has Meeting in it.
    $this->addMeetingToTimeline();

    // Add a meeting activity to the case.
    $meetingTypeId = $this->callAPISuccess('OptionValue', 'getsingle', [
      'return' => ["value"],
      'option_group_id' => 'activity_type',
      'name' => 'Meeting',
    ]);
    $this->callAPISuccess('activity', 'create', [
      'case_id' => $case_id,
      'activity_type_id' => $meetingTypeId['value'],
      'activity_date_time' => '20191114123456',
      'subject' => 'Test Meeting',
      'source_contact_id' => $this->_loggedInUser,
      'target_contact_id' => $client_id,
    ]);

    $caseReportParams = [
      'is_redact' => FALSE,
      'include_activities' => 1,
    ];

    // run the thing we're testing and get the output vars
    $template = CRM_Case_XMLProcessor_Report::populateCaseReportTemplate($client_id, $case_id, 'standard_timeline', $caseReportParams, $this->report);
    $assigned_vars = $template->get_template_vars();

    // We don't want to run all the data in the dataprovider but we know
    // in this case it should be the same as the second one in the
    // dataprovider so we can reuse it.
    $expected = $this->caseReportDataProvider()[1][1];
    $this->updateExpectedBecauseDataProviderEvaluatesBeforeEverything($expected, $client_id, $case_id);

    foreach ($expected as $key => $value) {
      // does the assigned template var match the expected value?
      $this->assertEquals($value, $assigned_vars[$key], "$key does not match" . print_r($assigned_vars[$key], TRUE));
    }
  }

  /**
   * Data provider for testGetCaseReport
   * @return array
   */
  public function caseReportDataProvider() {
    return [
      [
        // activity set name
        'standard_timeline',
        // Some expected assigned vars of CRM_Core_Smarty template.
        // In particular we shouldn't have meeting in the output since it's
        // not in the timeline.
        [
          'case' => [
            'clientName' => 'Casey Reportee',
            'subject' => 'Case Subject',
            'start_date' => '2019-11-14',
            'end_date' => NULL,
            'caseType' => 'Housing Support',
            'caseTypeName' => 'housing_support',
            'status' => 'Ongoing',
          ],
          'activities' => [
            0 => [
              'fields' => [
                0 => [
                  'name' => 'Client',
                  'label' => 'Client',
                  'value' => 'Casey Reportee',
                  'type' => 'String',
                ],
                1 => [
                  'name' => 'Activity Type',
                  'label' => 'Activity Type',
                  'value' => 'Open Case',
                  'type' => 'String',
                ],
                2 => [
                  'name' => 'Subject',
                  'label' => 'Subject',
                  'value' => 'Case Subject',
                  'type' => 'Memo',
                ],
                3 => [
                  'name' => 'Created By',
                  'label' => 'Created By',
                  // data providers run before everything, so update this later
                  'value' => 'placeholder',
                  'type' => 'String',
                ],
                4 => [
                  'name' => 'Reported By',
                  'label' => 'Reported By',
                  // see above - need to update this later
                  'value' => 'placeholder',
                  'type' => 'String',
                ],
                5 => [
                  'name' => 'Medium',
                  'label' => 'Medium',
                  'value' => 'Phone',
                  'type' => 'String',
                ],
                6 => [
                  'name' => 'Location',
                  'label' => 'Location',
                  'value' => NULL,
                  'type' => 'String',
                ],
                7 => [
                  'name' => 'Date and Time',
                  'label' => 'Date and Time',
                  'value' => '2019-11-14 00:00:00',
                  'type' => 'Date',
                ],
                8 => [
                  'name' => 'Details',
                  'label' => 'Details',
                  'value' => NULL,
                  'type' => 'Memo',
                ],
                9 => [
                  'name' => 'Status',
                  'label' => 'Status',
                  'value' => 'Completed',
                  'type' => 'String',
                ],
                10 => [
                  'name' => 'Priority',
                  'label' => 'Priority',
                  'value' => 'Normal',
                  'type' => 'String',
                ],
              ],
              'editURL' => 'placeholder',
              'customGroups' => NULL,
            ],
            1 => [
              'fields' => [
                0 => [
                  'name' => 'Client',
                  'label' => 'Client',
                  'value' => 'Casey Reportee',
                  'type' => 'String',
                ],
                1 => [
                  'name' => 'Activity Type',
                  'label' => 'Activity Type',
                  'value' => 'Medical evaluation',
                  'type' => 'String',
                ],
                2 => [
                  'name' => 'Subject',
                  'label' => 'Subject',
                  'value' => '',
                  'type' => 'Memo',
                ],
                3 => [
                  'name' => 'Created By',
                  'label' => 'Created By',
                  // see above - need to update this later
                  'value' => 'placeholder',
                  'type' => 'String',
                ],
                4 => [
                  'name' => 'Reported By',
                  'label' => 'Reported By',
                  // see above - need to update this later
                  'value' => 'placeholder',
                  'type' => 'String',
                ],
                5 => [
                  'name' => 'Location',
                  'label' => 'Location',
                  'value' => NULL,
                  'type' => 'String',
                ],
                6 => [
                  'name' => 'Date and Time',
                  'label' => 'Date and Time',
                  'value' => '2019-11-15 00:00:00',
                  'type' => 'Date',
                ],
                7 => [
                  'name' => 'Details',
                  'label' => 'Details',
                  'value' => NULL,
                  'type' => 'Memo',
                ],
                8 => [
                  'name' => 'Status',
                  'label' => 'Status',
                  'value' => 'Scheduled',
                  'type' => 'String',
                ],
                9 => [
                  'name' => 'Priority',
                  'label' => 'Priority',
                  'value' => 'Normal',
                  'type' => 'String',
                ],
              ],
              'editURL' => 'placeholder',
              'customGroups' => NULL,
            ],
          ],
        ],
      ],
      [
        // activity set name is blank here, meaning don't filter the activities
        '',
        // Some expected assigned vars of CRM_Core_Smarty template.
        // In particular now we will have Meeting in the output.
        [
          'case' => [
            'clientName' => 'Casey Reportee',
            'subject' => 'Case Subject',
            'start_date' => '2019-11-14',
            'end_date' => NULL,
            'caseType' => 'Housing Support',
            'caseTypeName' => 'housing_support',
            'status' => 'Ongoing',
          ],
          'activities' => [
            0 => [
              'fields' => [
                0 => [
                  'name' => 'Client',
                  'label' => 'Client',
                  'value' => 'Casey Reportee',
                  'type' => 'String',
                ],
                1 => [
                  'name' => 'Activity Type',
                  'label' => 'Activity Type',
                  'value' => 'Open Case',
                  'type' => 'String',
                ],
                2 => [
                  'name' => 'Subject',
                  'label' => 'Subject',
                  'value' => 'Case Subject',
                  'type' => 'Memo',
                ],
                3 => [
                  'name' => 'Created By',
                  'label' => 'Created By',
                  // data providers run before everything, so update this later
                  'value' => 'placeholder',
                  'type' => 'String',
                ],
                4 => [
                  'name' => 'Reported By',
                  'label' => 'Reported By',
                  // see above - need to update this later
                  'value' => 'placeholder',
                  'type' => 'String',
                ],
                5 => [
                  'name' => 'Medium',
                  'label' => 'Medium',
                  'value' => 'Phone',
                  'type' => 'String',
                ],
                6 => [
                  'name' => 'Location',
                  'label' => 'Location',
                  'value' => NULL,
                  'type' => 'String',
                ],
                7 => [
                  'name' => 'Date and Time',
                  'label' => 'Date and Time',
                  'value' => '2019-11-14 00:00:00',
                  'type' => 'Date',
                ],
                8 => [
                  'name' => 'Details',
                  'label' => 'Details',
                  'value' => NULL,
                  'type' => 'Memo',
                ],
                9 => [
                  'name' => 'Status',
                  'label' => 'Status',
                  'value' => 'Completed',
                  'type' => 'String',
                ],
                10 => [
                  'name' => 'Priority',
                  'label' => 'Priority',
                  'value' => 'Normal',
                  'type' => 'String',
                ],
              ],
              'editURL' => 'placeholder',
              'customGroups' => NULL,
            ],
            1 => [
              'fields' => [
                0 => [
                  'name' => 'Client',
                  'label' => 'Client',
                  'value' => 'Casey Reportee',
                  'type' => 'String',
                ],
                1 => [
                  'name' => 'Activity Type',
                  'label' => 'Activity Type',
                  'value' => 'Medical evaluation',
                  'type' => 'String',
                ],
                2 => [
                  'name' => 'Subject',
                  'label' => 'Subject',
                  'value' => '',
                  'type' => 'Memo',
                ],
                3 => [
                  'name' => 'Created By',
                  'label' => 'Created By',
                  // see above - need to update this later
                  'value' => 'placeholder',
                  'type' => 'String',
                ],
                4 => [
                  'name' => 'Reported By',
                  'label' => 'Reported By',
                  // see above - need to update this later
                  'value' => 'placeholder',
                  'type' => 'String',
                ],
                5 => [
                  'name' => 'Location',
                  'label' => 'Location',
                  'value' => NULL,
                  'type' => 'String',
                ],
                6 => [
                  'name' => 'Date and Time',
                  'label' => 'Date and Time',
                  'value' => '2019-11-15 00:00:00',
                  'type' => 'Date',
                ],
                7 => [
                  'name' => 'Details',
                  'label' => 'Details',
                  'value' => NULL,
                  'type' => 'Memo',
                ],
                8 => [
                  'name' => 'Status',
                  'label' => 'Status',
                  'value' => 'Scheduled',
                  'type' => 'String',
                ],
                9 => [
                  'name' => 'Priority',
                  'label' => 'Priority',
                  'value' => 'Normal',
                  'type' => 'String',
                ],
              ],
              'editURL' => 'placeholder',
              'customGroups' => NULL,
            ],
            2 => [
              'fields' => [
                0 => [
                  'name' => 'Client',
                  'label' => 'Client',
                  'value' => 'Casey Reportee',
                  'type' => 'String',
                ],
                1 => [
                  'name' => 'Activity Type',
                  'label' => 'Activity Type',
                  'value' => 'Meeting',
                  'type' => 'String',
                ],
                2 => [
                  'name' => 'Subject',
                  'label' => 'Subject',
                  'value' => 'Test Meeting',
                  'type' => 'Memo',
                ],
                3 => [
                  'name' => 'Created By',
                  'label' => 'Created By',
                  // see above - need to update this later
                  'value' => 'placeholder',
                  'type' => 'String',
                ],
                4 => [
                  'name' => 'Reported By',
                  'label' => 'Reported By',
                  // see above - need to update this later
                  'value' => 'placeholder',
                  'type' => 'String',
                ],
                5 => [
                  'name' => 'Location',
                  'label' => 'Location',
                  'value' => NULL,
                  'type' => 'String',
                ],
                6 => [
                  'name' => 'Date and Time',
                  'label' => 'Date and Time',
                  'value' => '2019-11-14 12:34:56',
                  'type' => 'Date',
                ],
                7 => [
                  'name' => 'Details',
                  'label' => 'Details',
                  'value' => NULL,
                  'type' => 'Memo',
                ],
                8 => [
                  'name' => 'Status',
                  'label' => 'Status',
                  'value' => 'Completed',
                  'type' => 'String',
                ],
                9 => [
                  'name' => 'Priority',
                  'label' => 'Priority',
                  'value' => 'Normal',
                  'type' => 'String',
                ],
              ],
              'editURL' => 'placeholder',
              'customGroups' => NULL,
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Since data providers get evaluated before setup and other variable
   * assignments, we call this during the test to update placeholders we set
   * in the data provider.
   * Maybe it doesn't really make sense to use a data provider here, but kinda.
   *
   * @param &$expected array Contains the placeholders to update.
   * @param $client_id int
   * @param $case_id int
   */
  private function updateExpectedBecauseDataProviderEvaluatesBeforeEverything(&$expected, $client_id, $case_id) {
    $display_name = $this->callAPISuccess('Contact', 'getsingle', [
      'return' => ["display_name"],
      'id' => $this->_loggedInUser,
    ]);

    foreach ($expected['activities'] as $idx => $activity) {
      $expected['activities'][$idx]['fields'][3]['value'] = $display_name['display_name'];
      $expected['activities'][$idx]['fields'][4]['value'] = $display_name['display_name'];

      $activityTypeId = $this->callAPISuccess('OptionValue', 'getsingle', [
        'return' => ["value"],
        'option_group_id' => 'activity_type',
        'name' => $expected['activities'][$idx]['fields'][1]['value'],
      ]);
      $expected['activities'][$idx]['editURL'] = CRM_Utils_System::url('civicrm/case/activity', "reset=1&cid={$client_id}&caseid={$case_id}&action=update&atype={$activityTypeId['value']}&id=" . ($idx + 1));
    }
  }

  /**
   * We don't need so many activities as in the stock case type. Just makes
   * dataprovider unnecessarily long. Just take the first two.
   * @return void
   */
  private function simplifyCaseTypeDefinition() {
    $caseType = $this->callAPISuccess('CaseType', 'getsingle', ['id' => $this->caseTypeId]);
    $newActivitySet = array_slice($caseType['definition']['activitySets'][0]['activityTypes'], 0, 2);
    $caseType['definition']['activitySets'][0]['activityTypes'] = $newActivitySet;
    $this->callAPISuccess('CaseType', 'create', $caseType);
  }

  /**
   * Add Meeting to the standard timeline.
   */
  private function addMeetingToTimeline() {
    $caseType = $this->callAPISuccess('CaseType', 'getsingle', ['id' => $this->caseTypeId]);
    $activityTypes = $caseType['definition']['activitySets'][0]['activityTypes'];
    // Make a copy of the second activity type and change the type.
    $activityType = $activityTypes[1];
    $activityType['name'] = 'Meeting';
    $activityType['label'] = 'Meeting';

    $activityTypes[] = $activityType;
    $caseType['definition']['activitySets'][0]['activityTypes'] = $activityTypes;
    $this->callAPISuccess('CaseType', 'create', $caseType);
  }

}

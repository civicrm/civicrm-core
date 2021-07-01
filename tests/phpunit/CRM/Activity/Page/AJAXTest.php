<?php

/**
 * @group headless
 */
class CRM_Activity_Page_AJAXTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();

    $this->loadAllFixtures();

    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');

    $this->loggedInUser = $this->createLoggedInUser();
    $this->target = $this->individualCreate();
  }

  /**
   * Test the underlying function that implements file-on-case.
   *
   * The UI is a quickform but it's only realized as a popup ajax form that
   * doesn't have its own postProcess. Instead the values are ultimately
   * passed to the function this test is testing. So there's no form or ajax
   * being tested here, just the final act of filing the activity.
   */
  public function testConvertToCaseActivity() {
    $activity = $this->callAPISuccess('Activity', 'create', [
      'source_contact_id' => $this->loggedInUser,
      'activity_type_id' => 'Meeting',
      'subject' => 'test file on case',
      'status_id' => 'Completed',
      'target_id' => $this->target,
    ]);

    $case = $this->callAPISuccess('Case', 'create', [
      'contact_id' => $this->target,
      'case_type_id' => 'housing_support',
      'subject' => 'Needs housing',
    ]);

    $params = [
      'activityID' => $activity['id'],
      'caseID' => $case['id'],
      'mode' => 'file',
      'targetContactIds' => $this->target,
    ];
    $result = CRM_Activity_Page_AJAX::_convertToCaseActivity($params);

    $this->assertEmpty($result['error_msg']);
    $newActivityId = $result['newId'];

    $caseActivities = $this->callAPISuccess('Activity', 'get', [
      'case_id' => $case['id'],
    ])['values'];
    $this->assertEquals('test file on case', $caseActivities[$newActivityId]['subject']);
    // This should be a different physical activity, not the same db record as the original.
    $this->assertNotEquals($activity['id'], $caseActivities[$newActivityId]['id']);
  }

  /**
   * Check if the selected filters are saved.
   */
  public function testPreserveFilters() {
    \Civi::settings()->set('preserve_activity_tab_filter', '1');

    // Simulate visiting activity tab with all the filters set to something
    $_GET = $_REQUEST = [
      'snippet' => '4',
      'context' => 'activity',
      // Need a logged in user since the filter is per-contact, but this
      // cid is the visited contact so could be anything, but might as well
      // use this one.
      'cid' => $this->loggedInUser,
      'draw' => '5',
      'columns' => [
        0 => [
          'data' => 'activity_type',
          'name' => '',
          'searchable' => 'true',
          'orderable' => 'true',
          'search' => [
            'value' => '',
            'regex' => 'false',
          ],
        ],
        1 => [
          'data' => 'subject',
          'name' => '',
          'searchable' => 'true',
          'orderable' => 'true',
          'search' => [
            'value' => '',
            'regex' => 'false',
          ],
        ],
        2 => [
          'data' => 'source_contact_name',
          'name' => '',
          'searchable' => 'true',
          'orderable' => 'true',
          'search' => [
            'value' => '',
            'regex' => 'false',
          ],
        ],
        3 => [
          'data' => 'target_contact_name',
          'name' => '',
          'searchable' => 'true',
          'orderable' => 'false',
          'search' => [
            'value' => '',
            'regex' => 'false',
          ],
        ],
        4 => [
          'data' => 'assignee_contact_name',
          'name' => '',
          'searchable' => 'true',
          'orderable' => 'false',
          'search' => [
            'value' => '',
            'regex' => 'false',
          ],
        ],
        5 => [
          'data' => 'activity_date_time',
          'name' => '',
          'searchable' => 'true',
          'orderable' => 'true',
          'search' => [
            'value' => '',
            'regex' => 'false',
          ],
        ],
        6 => [
          'data' => 'status_id',
          'name' => '',
          'searchable' => 'true',
          'orderable' => 'true',
          'search' => [
            'value' => '',
            'regex' => 'false',
          ],
        ],
        7 => [
          'data' => 'links',
          'name' => '',
          'searchable' => 'true',
          'orderable' => 'false',
          'search' => [
            'value' => '',
            'regex' => 'false',
          ],
        ],
      ],
      'start' => '0',
      'length' => '25',
      'search' => [
        'value' => '',
        'regex' => 'false',
      ],
      // Meeting
      'activity_type_id' => [0 => '1'],
      // Phone call
      'activity_type_exclude_id' => [0 => '2'],
      'activity_date_time_relative' => 'this.month',
      'activity_date_time_low' => '',
      'activity_date_time_high' => '',
      // Completed
      'activity_status_id' => '2',
    ];
    try {
      CRM_Activity_Page_AJAX::getContactActivity();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      // Check that the filter is what we expect
      $this->assertEquals([
        'activity_type_filter_id' => [0 => 1],
        'activity_type_exclude_filter_id' => [0 => 2],
        'activity_status_id' => '2',
        'status_id' => [0 => '2'],
        'activity_date_time_relative' => 'this.month',
      ], \Civi::contactSettings()->get('activity_tab_filter'));
    }

    // clean up
    \Civi::settings()->set('preserve_activity_tab_filter', '0');
    $_GET = $_REQUEST = [];
  }

}

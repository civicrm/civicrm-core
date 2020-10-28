<?php

/**
 * @group headless
 */
class CRM_Activity_Page_AJAXTest extends CiviUnitTestCase {

  public function setUp() {
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

}

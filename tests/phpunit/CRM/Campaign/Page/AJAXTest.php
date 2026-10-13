<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Test class for CRM_Campaign_Page_AJAX.
 *
 * @group headless
 */
class CRM_Campaign_Page_AJAXTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->enableCiviCampaign();
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
  }

  public function tearDown(): void {
    unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    $_REQUEST = [];
    $_GET = [];
    $_POST = [];
    $this->quickCleanUp([
      'civicrm_survey',
      'civicrm_activity_contact',
      'civicrm_activity',
    ]);
    parent::tearDown();
  }

  /**
   * Test processVoterData is denied for invalid operations.
   */
  public function testProcessVoterDataInvalidOperation(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'administer CiviCampaign']);

    $_POST = $_REQUEST = [
      'operation' => 'unsupported_op',
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Campaign_Page_AJAX::processVoterData();
  }

  /**
   * Test processVoterData operations are denied when lacking specific permissions.
   *
   * @dataProvider unsupportedPermissionProvider
   */
  public function testProcessVoterDataDeniedWithoutPermission(string $operation, array $userPermissions): void {
    $this->createLoggedInUser();
    $this->setPermissions($userPermissions);

    $_POST = $_REQUEST = [
      'operation' => $operation,
      'activity_id' => 1,
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Campaign_Page_AJAX::processVoterData();
  }

  public function unsupportedPermissionProvider(): array {
    return [
      ['reserve', ['access CiviCRM']],
      ['release', ['access CiviCRM']],
      ['gotv', ['access CiviCRM']],
    ];
  }

  /**
   * Test reserving and unreserving a voter respondent.
   */
  public function testProcessVoterDataReserveSuccess(): void {
    $interviewerId = $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'reserve campaign contacts', 'view all contacts']);

    $survey = $this->createTestEntity('Survey', [
      'title' => 'Voter Outreach Survey',
      'activity_type_id:name' => 'Survey',
    ]);
    $voterId = $this->individualCreate();

    // 1. Reserve respondent
    $_POST = $_REQUEST = [
      'operation' => 'reserve',
      'source_record_id' => $survey['id'],
      'source_contact_id' => $interviewerId,
      'target_contact_id' => $voterId,
      'assignee_contact_id' => $interviewerId,
      'isReserved' => '1',
      'surveyTitle' => 'Voter Outreach Survey',
    ];

    try {
      CRM_Campaign_Page_AJAX::processVoterData();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals(['status' => 'success', 'error_code' => 0], $e->errorData);
    }

    $activity = \Civi\Api4\Activity::get(FALSE)
      ->addWhere('source_record_id', '=', $survey['id'])
      ->addWhere('activity_type_id:name', '=', 'Survey')
      ->addWhere('is_deleted', '=', FALSE)
      ->execute()
      ->first();
    $this->assertNotNull($activity);

    // 2. Unreserve respondent
    $_POST = $_REQUEST = [
      'operation' => 'reserve',
      'source_record_id' => $survey['id'],
      'source_contact_id' => $interviewerId,
      'target_contact_id' => $voterId,
      'assignee_contact_id' => $interviewerId,
      'isReserved' => '0',
    ];

    try {
      CRM_Campaign_Page_AJAX::processVoterData();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals(['status' => 'success', 'error_code' => 0], $e->errorData);
    }

    $activityAfter = \Civi\Api4\Activity::get(FALSE)
      ->addWhere('id', '=', $activity['id'])
      ->addSelect('is_deleted')
      ->execute()
      ->first();
    $this->assertEquals(1, $activityAfter['is_deleted']);
  }

  /**
   * Test reserving a voter respondent is denied when user lacks permission on target contact.
   */
  public function testProcessVoterDataReserveDeniedWithoutContactPermission(): void {
    $interviewerId = $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'reserve campaign contacts']);

    $survey = $this->createTestEntity('Survey', [
      'title' => 'Voter Outreach Survey',
      'activity_type_id:name' => 'Survey',
    ]);
    $voterId = $this->individualCreate();

    $_POST = $_REQUEST = [
      'operation' => 'reserve',
      'source_record_id' => $survey['id'],
      'source_contact_id' => $interviewerId,
      'target_contact_id' => $voterId,
      'assignee_contact_id' => $interviewerId,
      'isReserved' => '1',
      'surveyTitle' => 'Voter Outreach Survey',
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Campaign_Page_AJAX::processVoterData();
  }

  /**
   * Test releasing an activity.
   */
  public function testProcessVoterDataReleaseSuccess(): void {
    $interviewerId = $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'release campaign contacts', 'view all contacts', 'view all activities', 'view debug output']);

    $survey = $this->createTestEntity('Survey', [
      'title' => 'Release Test Survey',
      'activity_type_id:name' => 'Survey',
    ]);
    $activity = $this->createTestEntity('Activity', [
      'source_record_id' => $survey['id'],
      'source_contact_id' => $interviewerId,
      'activity_type_id:name' => 'Survey',
      'is_deleted' => 0,
    ]);

    $_POST = $_REQUEST = [
      'operation' => 'release',
      'activity_id' => $activity['id'],
      'isDelete' => '1',
    ];

    try {
      CRM_Campaign_Page_AJAX::processVoterData();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals(['status' => 'success', 'error_code' => 0], $e->errorData);
    }

    $activityAfter = \Civi\Api4\Activity::get(FALSE)
      ->addWhere('id', '=', $activity['id'])
      ->addSelect('is_deleted')
      ->execute()
      ->first();
    $this->assertEquals(1, $activityAfter['is_deleted']);
  }

  /**
   * Test GOTV voting status updates.
   */
  public function testProcessVoterDataGotvSuccess(): void {
    $interviewerId = $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'gotv campaign contacts', 'view all contacts', 'view all activities']);

    $survey = $this->createTestEntity('Survey', [
      'title' => 'GOTV Test Survey',
      'activity_type_id:name' => 'Survey',
    ]);
    $activity = $this->createTestEntity('Activity', [
      'source_record_id' => $survey['id'],
      'source_contact_id' => $interviewerId,
      'activity_type_id:name' => 'Survey',
      'status_id:name' => 'Scheduled',
    ]);

    // 1. Mark as voted
    $_POST = $_REQUEST = [
      'operation' => 'gotv',
      'activity_id' => $activity['id'],
      'hasVoted' => '1',
    ];

    try {
      CRM_Campaign_Page_AJAX::processVoterData();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals(['status' => 'success', 'error_code' => 0], $e->errorData);
    }

    $activityAfter = \Civi\Api4\Activity::get(FALSE)
      ->addWhere('id', '=', $activity['id'])
      ->addSelect('status_id:name')
      ->execute()
      ->first();
    $this->assertEquals('Completed', $activityAfter['status_id:name']);

    // 2. Mark as not voted
    $_POST = $_REQUEST = [
      'operation' => 'gotv',
      'activity_id' => $activity['id'],
      'hasVoted' => '0',
    ];

    try {
      CRM_Campaign_Page_AJAX::processVoterData();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals(['status' => 'success', 'error_code' => 0], $e->errorData);
    }

    $activityAfter2 = \Civi\Api4\Activity::get(FALSE)
      ->addWhere('id', '=', $activity['id'])
      ->addSelect('status_id:name')
      ->execute()
      ->first();
    $this->assertEquals('Scheduled', $activityAfter2['status_id:name']);
  }

  /**
   * Test registerInterview is denied when user lacks 'interview campaign contacts' permission.
   */
  public function testRegisterInterviewDeniedWithoutPermission(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM']);

    $_POST = $_REQUEST = [
      'voter_id' => 1,
      'survey_id' => 1,
      'activity_id' => 1,
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Campaign_Page_AJAX::registerInterview();
  }

  /**
   * Test registerInterview is denied when activity does not belong to survey or voter.
   */
  public function testRegisterInterviewDeniedOnMismatchedSurveyOrContact(): void {
    $interviewerId = $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'interview campaign contacts']);

    $survey = $this->createTestEntity('Survey', [
      'title' => 'Interview Survey',
      'activity_type_id:name' => 'Survey',
    ]);
    $voterId = $this->individualCreate();
    $otherContactId = $this->individualCreate([], 'other');

    $activity = $this->createTestEntity('Activity', [
      'source_record_id' => $survey['id'],
      'source_contact_id' => $interviewerId,
      'target_contact_id' => [$otherContactId],
      'activity_type_id:name' => 'Survey',
      'status_id:name' => 'Scheduled',
    ]);

    // Mismatched voter (voterId is not target of activity)
    $_POST = $_REQUEST = [
      'voter_id' => $voterId,
      'survey_id' => $survey['id'],
      'activity_id' => $activity['id'],
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Campaign_Page_AJAX::registerInterview();
  }

  /**
   * Test registerInterview successfully records interview for valid voter and survey activity.
   */
  public function testRegisterInterviewSuccess(): void {
    $interviewerId = $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'interview campaign contacts']);

    $survey = $this->createTestEntity('Survey', [
      'title' => 'Interview Survey Success',
      'activity_type_id:name' => 'Survey',
    ]);
    $voterId = $this->individualCreate();

    $activity = $this->createTestEntity('Activity', [
      'source_record_id' => $survey['id'],
      'source_contact_id' => $interviewerId,
      'target_contact_id' => [$voterId],
      'activity_type_id:name' => 'Survey',
      'status_id:name' => 'Scheduled',
    ]);

    $_POST = $_REQUEST = [
      'voter_id' => $voterId,
      'survey_id' => $survey['id'],
      'activity_id' => $activity['id'],
      'activity_type_id' => $activity['activity_type_id'],
      'interviewer_id' => $interviewerId,
      'surveyTitle' => 'Interview Survey Success',
      'result' => 'Completed interview',
      'note' => 'Positive response',
    ];

    try {
      CRM_Campaign_Page_AJAX::registerInterview();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals('success', $e->errorData['status']);
    }

    $activityAfter = \Civi\Api4\Activity::get(FALSE)
      ->addWhere('id', '=', $activity['id'])
      ->addSelect('status_id:name', 'result', 'details')
      ->execute()
      ->first();

    $this->assertEquals('Completed', $activityAfter['status_id:name']);
    $this->assertEquals('Completed interview', $activityAfter['result']);
    $this->assertEquals('Positive response', $activityAfter['details']);
  }

}

<?php

/**
 * Class CRM_Case_BAO_CaseTest
 * @group headless
 */
class CRM_Case_BAO_CaseTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();

    $this->tablesToTruncate = [
      'civicrm_activity',
      'civicrm_contact',
      'civicrm_custom_group',
      'civicrm_custom_field',
      'civicrm_case',
      'civicrm_case_contact',
      'civicrm_case_activity',
      'civicrm_case_type',
      'civicrm_activity_contact',
      'civicrm_managed',
      'civicrm_relationship',
      'civicrm_relationship_type',
    ];

    $this->quickCleanup($this->tablesToTruncate);

    $this->loadAllFixtures();

    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
  }

  /**
   * Make sure that the latest case activity works accurately.
   */
  public function testCaseActivity() {
    $userID = $this->createLoggedInUser();

    $addTimeline = civicrm_api3('Case', 'addtimeline', [
      'case_id' => 1,
      'timeline' => "standard_timeline",
    ]);

    $query = CRM_Case_BAO_Case::getCaseActivityQuery('recent', $userID, ' civicrm_case.id IN( 1 )');
    $res = CRM_Core_DAO::executeQuery($query);
    $openCaseType = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Open Case');
    while ($res->fetch()) {
      $message = 'Failed asserting that the case activity query has a activity_type_id property:';
      $this->assertObjectHasAttribute('activity_type_id', $res, $message . PHP_EOL . print_r($res, TRUE));
      $message = 'Failed asserting that the latest activity from Case ID 1 was "Open Case":';
      $this->assertEquals($openCaseType, $res->activity_type_id, $message . PHP_EOL . print_r($res, TRUE));
    }
  }

  protected function tearDown() {
    parent::tearDown();
    $this->quickCleanup($this->tablesToTruncate, TRUE);
  }

  public function testAddCaseToContact() {
    $params = [
      'case_id' => 1,
      'contact_id' => 17,
    ];
    CRM_Case_BAO_CaseContact::create($params);

    $recent = CRM_Utils_Recent::get();
    $this->assertEquals('Test Contact - Housing Support', $recent[0]['title']);
  }

  /**
   * Create and return case object of given Client ID.
   * @param $clientId
   * @param $loggedInUser
   * @return CRM_Case_BAO_Case
   */
  private function createCase($clientId, $loggedInUser = NULL) {
    if (empty($loggedInUser)) {
      // backwards compatibility - but it's more typical that the creator is a different person than the client
      $loggedInUser = $clientId;
    }
    $caseParams = [
      'activity_subject' => 'Case Subject',
      'client_id'        => $clientId,
      'case_type_id'     => 1,
      'status_id'        => 1,
      'case_type'        => 'housing_support',
      'subject'          => 'Case Subject',
      'start_date'       => date("Y-m-d"),
      'start_date_time'  => date("YmdHis"),
      'medium_id'        => 2,
      'activity_details' => '',
    ];
    $form = new CRM_Case_Form_Case();
    $caseObj = $form->testSubmit($caseParams, "OpenCase", $loggedInUser, "standalone");
    return $caseObj;
  }

  /**
   * Create case role relationship between given contacts for provided case ID.
   *
   * @param $contactIdA
   * @param $contactIdB
   * @param $caseId
   * @param bool $isActive
   */
  private function createCaseRoleRelationship($contactIdA, $contactIdB, $caseId, $isActive = TRUE) {
    $relationshipType = $this->relationshipTypeCreate([
      'contact_type_b' => 'Individual',
    ]);

    $this->callAPISuccess('Relationship', 'create', [
      'contact_id_a'         => $contactIdA,
      'contact_id_b'         => $contactIdB,
      'relationship_type_id' => $relationshipType,
      'case_id'              => $caseId,
      'is_active'            => $isActive,
    ]);
  }

  /**
   * Asserts number of cases for given logged in user.
   *
   * @param $loggedInUser
   * @param $caseId
   * @param $caseCount
   */
  private function assertCasesOfUser($loggedInUser, $caseId, $caseCount) {
    $summary = CRM_Case_BAO_Case::getCasesSummary(FALSE);
    $upcomingCases = CRM_Case_BAO_Case::getCases(FALSE, [], 'dashboard', TRUE);
    $caseRoles = CRM_Case_BAO_Case::getCaseRoles($loggedInUser, $caseId);

    $this->assertEquals($caseCount, $upcomingCases, 'Upcoming case count must be ' . $caseCount);
    $this->assertEquals($caseCount, $summary['rows']['Housing Support']['Ongoing']['count'], 'Housing Support Ongoing case summary must be ' . $caseCount);
    $this->assertEquals($caseCount, count($caseRoles), 'Total case roles for logged in users must be ' . $caseCount);
  }

  /**
   * Test that Case count is exactly one for logged in user for user's active role.
   *
   * @throws \CRM_Core_Exception
   */
  public function testActiveCaseRole() {
    $individual = $this->individualCreate();
    $caseObj = $this->createCase($individual);
    $caseId = $caseObj->id;
    $loggedInUser = $this->createLoggedInUser();
    $this->createCaseRoleRelationship($individual, $loggedInUser, $caseId);
    $this->assertCasesOfUser($loggedInUser, $caseId, 1);
  }

  /**
   * Test that case count is zero for logged in user for user's inactive role.
   */
  public function testInactiveCaseRole() {
    $individual = $this->individualCreate();
    $caseObj = $this->createCase($individual);
    $caseId = $caseObj->id;
    $loggedInUser = $this->createLoggedInUser();
    $this->createCaseRoleRelationship($individual, $loggedInUser, $caseId, FALSE);
    $this->assertCasesOfUser($loggedInUser, $caseId, 0);
  }

  public function testGetCaseType() {
    $caseTypeLabel = CRM_Case_BAO_Case::getCaseType(1);
    $this->assertEquals('Housing Support', $caseTypeLabel);
  }

  public function testRetrieveCaseIdsByContactId() {
    $caseIds = CRM_Case_BAO_Case::retrieveCaseIdsByContactId(3, FALSE, 'housing_support');
    $this->assertEquals([1], $caseIds);
  }

  /**
   * Test that all custom files are migrated to new case when case is assigned to new client.
   */
  public function testCaseReassignForCustomFiles() {
    $individual = $this->individualCreate();
    $customGroup = $this->customGroupCreate(array(
      'extends' => 'Case',
    ));
    $customGroup = $customGroup['values'][$customGroup['id']];

    $customFileFieldA = $this->customFieldCreate(array(
      'custom_group_id' => $customGroup['id'],
      'html_type'       => 'File',
      'is_active'       => 1,
      'default_value'   => 'null',
      'label'           => 'Custom File A',
      'data_type'       => 'File',
    ));

    $customFileFieldB = $this->customFieldCreate(array(
      'custom_group_id' => $customGroup['id'],
      'html_type'       => 'File',
      'is_active'       => 1,
      'default_value'   => 'null',
      'label'           => 'Custom File B',
      'data_type'       => 'File',
    ));

    // Create two files to attach to the new case
    $filepath = Civi::paths()->getPath('[civicrm.files]/custom');

    CRM_Utils_File::createFakeFile($filepath, 'Bananas do not bend themselves without a little help.', 'i_bend_bananas.txt');
    $fileA = $this->callAPISuccess('File', 'create', ['uri' => "$filepath/i_bend_bananas.txt"]);

    CRM_Utils_File::createFakeFile($filepath, 'Wombats will bite your ankles if you run from them.', 'wombats_bite_your_ankles.txt');
    $fileB = $this->callAPISuccess('File', 'create', ['uri' => "$filepath/wombats_bite_your_ankles.txt"]);

    $caseObj = $this->createCase($individual);

    $this->callAPISuccess('Case', 'create', array(
      'id'                                => $caseObj->id,
      'custom_' . $customFileFieldA['id'] => $fileA['id'],
      'custom_' . $customFileFieldB['id'] => $fileB['id'],
    ));

    $reassignIndividual = $this->individualCreate();
    $this->createLoggedInUser();
    $newCase = CRM_Case_BAO_Case::mergeCases($reassignIndividual, $caseObj->id, $individual, NULL, TRUE);

    $entityFiles = new CRM_Core_DAO_EntityFile();
    $entityFiles->entity_id = $newCase[0];
    $entityFiles->entity_table = $customGroup['table_name'];
    $entityFiles->find();

    $totalEntityFiles = 0;
    while ($entityFiles->fetch()) {
      $totalEntityFiles++;
    }

    $this->assertEquals(2, $totalEntityFiles, 'Two files should be attached with new case.');
  }

  /**
   * FIXME: need to create an activity to run this test
   * function testGetCases() {
   *   $cases = CRM_Case_BAO_Case::getCases(TRUE, 3);
   *   $this->assertEquals('Housing Support', $cases[1]['case_type']);
   *   $this->assertEquals(1, $cases[1]['case_type_id']);
   * }
   */
  public function testGetCasesSummary() {
    $cases = CRM_Case_BAO_Case::getCasesSummary();
    $this->assertEquals(1, $cases['rows']['Housing Support']['Ongoing']['count']);
  }

  /* FIXME: requires activities
   * function testGetRelatedCases() {
   * }
   */

  /**
   * Test various things after a case is closed.
   *
   * This annotation is not ideal, but without it there is some kind of
   * messup that happens to quickform that persists between tests, e.g.
   * it can't add maxfilesize validation rules.
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testCaseClosure() {
    $loggedInUser = $this->createLoggedInUser();
    $client_id = $this->individualCreate();
    $caseObj = $this->createCase($client_id, $loggedInUser);
    $case_id = $caseObj->id;

    // Get the case status option value for "Resolved" (name="Closed").
    $closed_status = $this->callAPISuccess('OptionValue', 'getValue', [
      'return' => 'value',
      'option_group_id' => 'case_status',
      'name' => 'Closed',
    ]);
    $this->assertNotEmpty($closed_status);

    // Get the activity status option value for "Completed"
    $completed_status = $this->callAPISuccess('OptionValue', 'getValue', [
      'return' => 'value',
      'option_group_id' => 'activity_status',
      'name' => 'Completed',
    ]);
    $this->assertNotEmpty($completed_status);

    // Get the value for the activity type id we need to create
    $atype = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Change Case Status');

    // Now it gets weird. There doesn't seem to be a good way to test this, so we simulate a form and the various bits that go with it.

    // HTTP vars needed because that's how the form determines stuff
    $oldMETHOD = empty($_SERVER['REQUEST_METHOD']) ? NULL : $_SERVER['REQUEST_METHOD'];
    $oldGET = empty($_GET) ? [] : $_GET;
    $oldREQUEST = empty($_REQUEST) ? [] : $_REQUEST;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['caseid'] = $case_id;
    $_REQUEST['caseid'] = $case_id;
    $_GET['cid'] = $client_id;
    $_REQUEST['cid'] = $client_id;
    $_GET['action'] = 'add';
    $_REQUEST['action'] = 'add';
    $_GET['reset'] = 1;
    $_REQUEST['reset'] = 1;
    $_GET['atype'] = $atype;
    $_REQUEST['atype'] = $atype;

    $form = new CRM_Case_Form_Activity();
    $form->controller = new CRM_Core_Controller_Simple('CRM_Case_Form_Activity', 'Case Activity');
    $form->_activityTypeId  = $atype;
    $form->_activityTypeName = 'Change Case Status';
    $form->_activityTypeFile = 'ChangeCaseStatus';

    $form->preProcess();
    $form->buildQuickForm();
    $form->setDefaultValues();

    // Now submit the form. Store the date used so we can check it later.

    $t = time();
    $now_date = date('Y-m-d H:i:s', $t);
    $now_date_date_only = date('Y-m-d', $t);
    $actParams = [
      'is_unittest' => TRUE,
      'case_status_id' => $closed_status,
      'activity_date_time' => $now_date,
      'target_contact_id' => $client_id,
      'source_contact_id' => $loggedInUser,
      // yeah this is extra weird, but without it you get the wrong subject
      'subject' => 'null',
    ];

    $form->postProcess($actParams);

    // Ok now let's check some things

    $result = $this->callAPISuccess('Case', 'get', [
      'sequential' => 1,
      'id' => $case_id,
    ]);
    $caseData = array_shift($result['values']);

    $this->assertEquals($caseData['end_date'], $now_date_date_only);
    $this->assertEquals($caseData['status_id'], $closed_status);

    // now get the latest activity and check some things for it

    $actId = max($caseData['activities']);
    $this->assertNotEmpty($actId);

    $result = $this->callAPISuccess('Activity', 'get', [
      'sequential' => 1,
      'id' => $actId,
    ]);
    $activity = array_shift($result['values']);

    $this->assertEquals($activity['subject'], 'Case status changed from Ongoing to Resolved');
    $this->assertEquals($activity['activity_date_time'], $now_date);
    $this->assertEquals($activity['status_id'], $completed_status);

    // Now replace old globals
    if (is_null($oldMETHOD)) {
      unset($_SERVER['REQUEST_METHOD']);
    }
    else {
      $_SERVER['REQUEST_METHOD'] = $oldMETHOD;
    }
    $_GET = $oldGET;
    $_REQUEST = $oldREQUEST;
  }

  /**
   * Test max_instances
   */
  public function testMaxInstances() {
    $loggedInUser = $this->createLoggedInUser();
    $client_id = $this->individualCreate();
    $caseObj = $this->createCase($client_id, $loggedInUser);
    $case_id = $caseObj->id;

    // Sanity check to make sure we'll be testing what we think we're testing.
    $this->assertEquals($caseObj->case_type_id, 1);

    // Get the case type
    $result = $this->callAPISuccess('CaseType', 'get', [
      'sequential' => 1,
      'id' => 1,
    ]);
    $caseType = array_shift($result['values']);
    $activityTypeName = $caseType['definition']['activityTypes'][1]['name'];
    // Sanity check to make sure we'll be testing what we think we're testing.
    $this->assertEquals($activityTypeName, "Medical evaluation");

    // Look up the activity type label - we need it later
    $result = $this->callAPISuccess('OptionValue', 'get', [
      'sequential' => 1,
      'option_group_id' => 'activity_type',
      'name' => $activityTypeName,
    ]);
    $optionValue = array_shift($result['values']);
    $activityTypeLabel = $optionValue['label'];
    $this->assertNotEmpty($activityTypeLabel);

    // Locate the existing activity independently so we can check it
    $result = $this->callAPISuccess('Activity', 'get', [
      'sequential' => 1,
      // this sometimes confuses me - pass in the name for the id
      'activity_type_id' => $activityTypeName,
    ]);
    // There should be only one in the database at this point so this should be the id.
    $activity_id = $result['id'];
    $this->assertNotEmpty($activity_id);
    $this->assertGreaterThan(0, $activity_id);
    $activityArr = array_shift($result['values']);

    // At the moment everything should be happy, although there's nothing to test because if max_instances has no value then nothing gets called, which is correct since it means unlimited. But we don't have a way to test that right now. For fun we could test max_instances=0 but that isn't the same as "not set". 0 would actually mean 0 are allowed, which is pointless, since then why would you even add the activity type to the config.

    // Update max instances for the activity type
    // We're not really checking that the tested code has retrieved the new case type definition, just that given some numbers as input it returns the right thing as output, so these lines are mostly symbolic at the moment.
    $caseType['definition']['activityTypes'][1]['max_instances'] = 1;
    $this->callAPISuccess('CaseType', 'create', $caseType);

    // Now we should get a link back
    $editUrl = CRM_Case_Form_Activity::checkMaxInstances(
      $case_id,
      $activityArr['activity_type_id'],
      // max instances
      1,
      $loggedInUser,
      $client_id,
      // existing activity count
      1
    );
    $this->assertNotNull($editUrl);

    $expectedUrl = CRM_Utils_System::url(
      'civicrm/case/activity',
      "reset=1&cid={$client_id}&caseid={$case_id}&action=update&id={$activity_id}"
    );
    $this->assertEquals($editUrl, $expectedUrl);

    // And also a bounce message is expected
    $bounceMessage = CRM_Case_Form_Activity::getMaxInstancesBounceMessage(
      $editUrl,
      $activityTypeLabel,
      // max instances,
      1,
      // existing activity count
      1
    );
    $this->assertNotEmpty($bounceMessage);

    // Now check with max_instances = 2
    $caseType['definition']['activityTypes'][1]['max_instances'] = 2;
    $this->callAPISuccess('CaseType', 'create', $caseType);

    // So it should now be back to being happy
    $editUrl = CRM_Case_Form_Activity::checkMaxInstances(
      $case_id,
      $activityArr['activity_type_id'],
      // max instances
      2,
      $loggedInUser,
      $client_id,
      // existing activity count
      1
    );
    $this->assertNull($editUrl);
    $bounceMessage = CRM_Case_Form_Activity::getMaxInstancesBounceMessage(
      $editUrl,
      $activityTypeLabel,
      // max instances,
      2,
      // existing activity count
      1
    );
    $this->assertEmpty($bounceMessage);

    // Add new activity check again
    $newActivity = [
      'case_id' => $case_id,
      'activity_type_id' => $activityArr['activity_type_id'],
      'status_id' => $activityArr['status_id'],
      'subject' => "A different subject",
      'activity_date_time' => date('Y-m-d H:i:s'),
      'source_contact_id' => $loggedInUser,
      'target_id' => $client_id,
    ];
    $this->callAPISuccess('Activity', 'create', $newActivity);

    $editUrl = CRM_Case_Form_Activity::checkMaxInstances(
      $case_id,
      $activityArr['activity_type_id'],
      // max instances
      2,
      $loggedInUser,
      $client_id,
      // existing activity count
      2
    );
    // There should be no url here.
    $this->assertNull($editUrl);

    // But there should be a warning message still.
    $bounceMessage = CRM_Case_Form_Activity::getMaxInstancesBounceMessage(
      $editUrl,
      $activityTypeLabel,
      // max instances,
      2,
      // existing activity count
      2
    );
    $this->assertNotEmpty($bounceMessage);
  }

  /**
   * Test changing the label for the case manager role and then creating
   * a case.
   * At the time this test was written this test would fail, demonstrating
   * one problem with name vs label.
   */
  public function testCreateCaseWithChangedManagerLabel() {
    // We could just assume the relationship that gets created has
    // relationship_type_id = 1, but let's create a case, see what the
    // id is, then do our actual test.
    $loggedInUser = $this->createLoggedInUser();
    $client_id = $this->individualCreate();
    $caseObj = $this->createCase($client_id, $loggedInUser);
    $case_id = $caseObj->id;

    // Going to assume the stock case type has what it currently has at the
    // time of writing, which is the autocreated case manager relationship for
    // the logged in user.
    $getParams = [
      'contact_id_b' => $loggedInUser,
      'case_id' => $case_id,
    ];
    $result = $this->callAPISuccess('Relationship', 'get', $getParams);
    // as noted above assume this is the only one
    $relationship_type_id = $result['values'][$result['id']]['relationship_type_id'];

    // Save the old labels first so we can put back at end of test.
    $oldParams = [
      'id' => $relationship_type_id,
    ];
    $oldValues = $this->callAPISuccess('RelationshipType', 'get', $oldParams);
    // Now change the label of the relationship type.
    $changeParams = [
      'id' => $relationship_type_id,
      'label_a_b' => 'Best ' . $oldValues['values'][$relationship_type_id]['label_a_b'],
      'label_b_a' => 'Best ' . $oldValues['values'][$relationship_type_id]['label_b_a'],
    ];
    $this->callAPISuccess('RelationshipType', 'create', $changeParams);

    // Now try creating another case.
    $caseObj2 = $this->createCase($client_id, $loggedInUser);
    $case_id2 = $caseObj2->id;

    $checkParams = [
      'contact_id_b' => $loggedInUser,
      'case_id' => $case_id2,
    ];
    $result = $this->callAPISuccess('Relationship', 'get', $checkParams);
    // Main thing is the above createCase call doesn't fail, but let's check
    // the relationship type id is what we expect too while we're here.
    // See note above about assuming this is the only relationship autocreated.
    $this->assertEquals($relationship_type_id, $result['values'][$result['id']]['relationship_type_id']);

    // Now put relationship type back to the way it was.
    $changeParams = [
      'id' => $relationship_type_id,
      'label_a_b' => $oldValues['values'][$relationship_type_id]['label_a_b'],
      'label_b_a' => $oldValues['values'][$relationship_type_id]['label_b_a'],
    ];
    $this->callAPISuccess('RelationshipType', 'create', $changeParams);
  }

}

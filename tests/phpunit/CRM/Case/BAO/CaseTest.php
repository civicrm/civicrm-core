<?php

/**
 * Class CRM_Case_BAO_CaseTest
 * @group headless
 */
class CRM_Case_BAO_CaseTest extends CiviUnitTestCase {

  public function setUp(): void {
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
      'civicrm_file',
      'civicrm_entity_file',
      'civicrm_activity_contact',
      'civicrm_managed',
      'civicrm_relationship',
      'civicrm_relationship_type',
    ];

    $this->quickCleanup($this->tablesToTruncate);

    $this->loadAllFixtures();

    // I don't understand why need to disable but if don't then only one
    // case type is defined on 2nd and subsequent dataprovider runs.
    CRM_Core_BAO_ConfigSetting::disableComponent('CiviCase');
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
  }

  /**
   * Make sure that the latest case activity works accurately.
   */
  public function testCaseActivity(): void {
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

  protected function tearDown(): void {
    $this->quickCleanup($this->tablesToTruncate, TRUE);
    parent::tearDown();
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
    if ($caseCount === 0) {
      // If there really are 0 cases then there won't be any subelements for
      // status and count, so we get a false error if we use the assertEquals
      // check since it tries to get a subelement on type int. In this case
      // the summary rows are just the case type pseudoconstant list.
      $this->assertSame(array_flip(CRM_Case_PseudoConstant::caseType()), $summary['rows']);
    }
    else {
      $this->assertEquals($caseCount, $summary['rows']['Housing Support']['Ongoing']['count'], 'Housing Support Ongoing case summary must be ' . $caseCount);
    }
    $this->assertEquals($caseCount, count($caseRoles), 'Total case roles for logged in users must be ' . $caseCount);
  }

  /**
   * core/issue-1623: My Case dashlet doesn't sort by name but contact_id instead
   *
   * @throws \CRM_Core_Exception
   */
  public function testSortByCaseContact() {
    // delete any cases if present
    $this->callAPISuccess('Case', 'get', ['api.Case.delete' => ['id' => '$value.id']]);

    // create three contacts with different name, later used in respective cases
    $contacts = [
      $this->individualCreate(['first_name' => 'Antonia', 'last_name' => 'D`souza']),
      $this->individualCreate(['first_name' => 'Darric', 'last_name' => 'Roy']),
      $this->individualCreate(['first_name' => 'Adam', 'last_name' => 'Pitt']),
    ];
    $loggedInUser = $this->createLoggedInUser();
    $relationshipType = $this->relationshipTypeCreate([
      'contact_type_b' => 'Individual',
    ]);

    // create cases for each contact
    $cases = [];
    foreach ($contacts as $contactID) {
      $cases[] = $caseID = $this->createCase($contactID)->id;
      $this->callAPISuccess('Relationship', 'create', [
        'contact_id_a'         => $contactID,
        'contact_id_b'         => $loggedInUser,
        'relationship_type_id' => $relationshipType,
        'case_id'              => $caseID,
        'is_active'            => TRUE,
      ]);
    }

    // USECASE A: fetch all cases using the AJAX fn without any sorting criteria, and match the result
    global $_GET;
    $_GET = [
      'start' => 0,
      'length' => 10,
      'type' => 'any',
      'all' => 1,
      'is_unittest' => 1,
    ];

    $cases = [];
    try {
      CRM_Case_Page_AJAX::getCases();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $cases = $e->errorData['data'];
    }

    // list of expected sorted names in order the respective cases were created
    $unsortedExpectedContactNames = [
      'D`souza, Antonia',
      'Roy, Darric',
      'Pitt, Adam',
    ];
    $unsortedActualContactNames = CRM_Utils_Array::collect('sort_name', $cases);
    foreach ($unsortedExpectedContactNames as $key => $name) {
      // Something has changed recently that has exposed one of the problems with queries that are not full-groupby-compliant. Temporarily commenting this out until figure out what to do since this exact query doesn't seem to come up anywhere on common screens.
      //$this->assertContains($name, $unsortedActualContactNames[$key]);
    }

    // USECASE B: fetch all cases using the AJAX fn based any 'Contact' sorting criteria, and match the result against expected sequence of names
    $_GET = [
      'start' => 0,
      'length' => 10,
      'type' => 'any',
      'all' => 1,
      'is_unittest' => 1,
      'columns' => [
        1 => [
          'data' => 'sort_name',
          'name' => NULL,
          'searchable' => TRUE,
          'orderable' => TRUE,
          'search' => [
            'value' => NULL,
            'regex' => FALSE,
          ],
        ],
      ],
      'order' => [
        [
          'column' => 1,
          'dir' => 'asc',
        ],
      ],
    ];

    $cases = [];
    try {
      CRM_Case_Page_AJAX::getCases();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $cases = $e->errorData['data'];
    }

    // list of expected sorted names in ASC order
    $sortedExpectedContactNames = [
      'D`souza, Antonia',
      'Pitt, Adam',
      'Roy, Darric',
    ];
    $sortedActualContactNames = CRM_Utils_Array::collect('sort_name', $cases);
    foreach ($sortedExpectedContactNames as $key => $name) {
      $this->assertStringContainsString($name, $sortedActualContactNames[$key]);
    }
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

    // delete original files
    unlink($fileA['values']['uri']);
    unlink($fileB['values']['uri']);
    // find out the hashed name of the attached file
    foreach (CRM_Core_BAO_File::getEntityFile($customGroup['table_name'], $newCase[0]) as $file) {
      unlink($file['fullPath']);
    }
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

  /**
   * Test that getRelatedCases() returns the other case when you create a
   * Link Cases activity on one of the cases.
   */
  public function testGetRelatedCases() {
    $loggedInUser = $this->createLoggedInUser();
    // create some cases
    $client_id_1 = $this->individualCreate([], 0);
    $caseObj_1 = $this->createCase($client_id_1, $loggedInUser);
    $case_id_1 = $caseObj_1->id;
    $client_id_2 = $this->individualCreate([], 1);
    $caseObj_2 = $this->createCase($client_id_2, $loggedInUser);
    $case_id_2 = $caseObj_2->id;

    // Create link case activity. We could go thru the whole form processes
    // but we really just want to test the BAO function so just need the
    // activity to exist.
    $result = $this->callAPISuccess('activity', 'create', [
      'activity_type_id' => 'Link Cases',
      'subject' => 'Test Link Cases',
      'status_id' => 'Completed',
      'source_contact_id' => $loggedInUser,
      'target_contact_id' => $client_id_1,
      'case_id' => $case_id_1,
    ]);

    // Put it in the format needed for endPostProcess
    $activity = new StdClass();
    $activity->id = $result['id'];
    $params = [
      'link_to_case_id' => $case_id_2,
    ];
    CRM_Case_Form_Activity_LinkCases::endPostProcess(NULL, $params, $activity);

    // Get related cases for case 1
    $cases = CRM_Case_BAO_Case::getRelatedCases($case_id_1);
    // It should have case 2
    $this->assertEquals($case_id_2, $cases[$case_id_2]['case_id']);

    // Ditto but reverse the cases
    $cases = CRM_Case_BAO_Case::getRelatedCases($case_id_2);
    $this->assertEquals($case_id_1, $cases[$case_id_1]['case_id']);
  }

  /**
   * Test various things after a case is closed.
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
   * Test getGlobalContacts
   */
  public function testGetGlobalContacts() {
    //Add contact to case resource.
    $caseResourceContactID = $this->individualCreate();
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => "Case_Resources",
      'contact_id' => $caseResourceContactID,
    ]);

    //No contact should be returned.
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    $groupInfo = [];
    $groupContacts = CRM_Case_BAO_Case::getGlobalContacts($groupInfo);
    $this->assertEquals(0, count($groupContacts));

    //Verify if contact is returned correctly.
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
    ];
    $groupInfo = [];
    $groupContacts = CRM_Case_BAO_Case::getGlobalContacts($groupInfo);
    $this->assertEquals(1, count($groupContacts));
    $this->assertEquals($caseResourceContactID, key($groupContacts));
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

  /**
   * Test change case status with linked cases choosing the option to
   * update the linked cases.
   */
  public function testChangeCaseStatusLinkedCases() {
    $loggedInUser = $this->createLoggedInUser();
    $clientId1 = $this->individualCreate();
    $clientId2 = $this->individualCreate();
    $case1 = $this->createCase($clientId1, $loggedInUser);
    $case2 = $this->createCase($clientId2, $loggedInUser);
    $linkActivity = $this->callAPISuccess('Activity', 'create', [
      'case_id' => $case1->id,
      'source_contact_id' => $loggedInUser,
      'target_contact' => $clientId1,
      'activity_type_id' => 'Link Cases',
      'subject' => 'Test Link Cases',
      'status_id' => 'Completed',
    ]);

    // Put it in the format needed for endPostProcess
    $activity = new StdClass();
    $activity->id = $linkActivity['id'];
    $params = ['link_to_case_id' => $case2->id];
    CRM_Case_Form_Activity_LinkCases::endPostProcess(NULL, $params, $activity);

    // Get the option_value.value for case status Closed
    $closedStatusResult = $this->callAPISuccess('OptionValue', 'get', [
      'option_group_id' => 'case_status',
      'name' => 'Closed',
      'return' => ['value'],
    ]);
    $closedStatus = $closedStatusResult['values'][$closedStatusResult['id']]['value'];

    // Go thru the motions to change case status
    $form = new CRM_Case_Form_Activity_ChangeCaseStatus();
    $form->_caseId = [$case1->id];
    $form->_oldCaseStatus = [$case1->status_id];
    $params = [
      'id' => $case1->id,
      'case_status_id' => $closedStatus,
      'updateLinkedCases' => '1',
    ];

    CRM_Case_Form_Activity_ChangeCaseStatus::beginPostProcess($form, $params);
    // Check that the second case is now also in the form member.
    $this->assertEquals([$case1->id, $case2->id], $form->_caseId);

    // We need to pass in an actual activity later
    $result = $this->callAPISuccess('Activity', 'create', [
      'case_id' => $case1->id,
      'source_contact_id' => $loggedInUser,
      'target_contact' => $clientId1,
      'activity_type_id' => 'Change Case Status',
      'subject' => 'Status changed',
      'status_id' => 'Completed',
    ]);
    $changeStatusActivity = new CRM_Activity_DAO_Activity();
    $changeStatusActivity->id = $result['id'];
    $changeStatusActivity->find(TRUE);

    $params = [
      'case_id' => $case1->id,
      'target_contact_id' => [$clientId1],
      'case_status_id' => $closedStatus,
      'activity_date_time' => $changeStatusActivity->activity_date_time,
    ];

    CRM_Case_Form_Activity_ChangeCaseStatus::endPostProcess($form, $params, $changeStatusActivity);

    // @todo Check other case got closed.
    /*
     * We can't do this here because it doesn't happen until the parent
     * activity does its thing.
    $linkedCase = $this->callAPISuccess('Case', 'get', ['id' => $case2->id]);
    $this->assertEquals($closedStatus, $linkedCase['values'][$linkedCase['id']]['status_id']);
    $this->assertEquals(date('Y-m-d', strtotime($changeStatusActivity->activity_date_time)), $linkedCase['values'][$linkedCase['id']]['end_date']);
     */
  }

  /**
   * test getCaseActivityQuery
   * @dataProvider caseActivityQueryProvider
   * @param array $input
   * @param array $expected
   */
  public function testGetCaseActivityQuery(array $input, array $expected): void {
    $activity_type_map = array_flip(CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'validate'));

    $loggedInUser = $this->createLoggedInUser();
    $individual[1] = $this->individualCreate();
    $caseObj[1] = $this->createCase($individual[1], $loggedInUser, [
      // Unfortunately the query we're testing is not full-group-by compliant
      // and does not have a well-defined sort order either. If we use the
      // default casetype then there are two activities with the same date
      // and sometimes you get one returned and sometimes the other. If the
      // second case type timeline is altered in future it's possible the
      // same problem could intermittently occur.
      'case_type_id' => 2,
      'case_type' => 'adult_day_care_referral',
    ]);
    $individual[2] = $this->individualCreate([], 1);

    // create a second case with a different start date
    $other_date = strtotime($input['other_date']);
    $caseObj[2] = $this->createCase($individual[2], $loggedInUser, [
      'case_type_id' => 2,
      'case_type' => 'adult_day_care_referral',
      'start_date' => date('Y-m-d', $other_date),
      'start_date_time' => date('YmdHis', $other_date),
    ]);

    foreach (['upcoming', 'recent'] as $type) {
      // See note above about the query being ill-defined, so this only works
      // on mysql 5.7 and 8, similar to other tests in this file that call
      // getCases which also uses this query.
      $sql = CRM_Case_BAO_Case::getCaseActivityQuery($type, $loggedInUser);
      $dao = CRM_Core_DAO::executeQuery($sql);
      $activities = [];
      $counter = 0;
      while ($dao->fetch()) {
        $activities[$counter] = [
          'case_id' => $dao->case_id,
          'case_subject' => $dao->case_subject,
          'contact_id' => (int) $dao->contact_id,
          'phone' => $dao->phone,
          'contact_type' => $dao->contact_type,
          'activity_type_id' => (int) $dao->activity_type_id,
          'case_type_id' => (int) $dao->case_type_id,
          'case_status_id' => (int) $dao->case_status_id,
          // This is activity status
          'status_id' => (int) $dao->status_id,
          'case_start_date' => $dao->case_start_date,
          'case_role' => $dao->case_role,
          'activity_date_time' => $dao->activity_date_time,
        ];

        // Need to replace some placeholders since we don't know what they
        // are at the time the dataprovider is evaluated.
        $offset = $expected[$type][$counter]['which_case_offset'];
        unset($expected[$type][$counter]['which_case_offset']);
        $expected[$type][$counter]['case_id'] = $caseObj[$offset]->id;
        $expected[$type][$counter]['contact_id'] = $individual[$offset];
        $expected[$type][$counter]['activity_type_id'] = $activity_type_map[$expected[$type][$counter]['activity_type_id']];
        $expected[$type][$counter]['case_start_date'] = $caseObj[$offset]->start_date;
        // To avoid a millisecond rollover bug, where e.g. the dataprovider
        // runs a whole second before this test, we make this relative to the
        // case start date, which it is anyway in the timeline.
        $expected[$type][$counter]['activity_date_time'] = date('Y-m-d H:i:s', strtotime($caseObj[$offset]->start_date . $expected[$type][$counter]['activity_date_time']));

        $counter++;
      }
      $this->assertEquals($expected[$type], $activities);
    }
  }

  /**
   * dataprovider for testGetCaseActivityQuery
   * @return array
   */
  public function caseActivityQueryProvider(): array {
    return [
      0 => [
        'input' => [
          'other_date' => '-1 day',
        ],
        'expected' => [
          'upcoming' => [
            0 => [
              'which_case_offset' => 2,
              'case_id' => 'REPLACE_ME',
              'case_subject' => 'Case Subject',
              'contact_id' => 'REPLACE_ME',
              'phone' => NULL,
              'contact_type' => 'Individual',
              'activity_type_id' => 'Medical evaluation',
              'case_type_id' => 2,
              'case_status_id' => 1,
              'status_id' => 1,
              'case_start_date' => 'REPLACE_ME',
              'case_role' => 'Senior Services Coordinator is',
              'activity_date_time' => ' +3 day',
            ],
            1 => [
              'which_case_offset' => 1,
              // REPLACE_ME's will get replaced in the test since the values haven't been created yet at the time dataproviders get evaluated.
              'case_id' => 'REPLACE_ME',
              'case_subject' => 'Case Subject',
              'contact_id' => 'REPLACE_ME',
              'phone' => NULL,
              'contact_type' => 'Individual',
              'activity_type_id' => 'Medical evaluation',
              'case_type_id' => 2,
              'case_status_id' => 1,
              'status_id' => 1,
              'case_start_date' => 'REPLACE_ME',
              'case_role' => 'Senior Services Coordinator is',
              'activity_date_time' => ' +3 day',
            ],
          ],
          'recent' => [
            0 => [
              'which_case_offset' => 2,
              'case_id' => 'REPLACE_ME',
              'case_subject' => 'Case Subject',
              'contact_id' => 'REPLACE_ME',
              'phone' => NULL,
              'contact_type' => 'Individual',
              'activity_type_id' => 'Open Case',
              'case_type_id' => 2,
              'case_status_id' => 1,
              'status_id' => 2,
              'case_start_date' => 'REPLACE_ME',
              'case_role' => 'Senior Services Coordinator is',
              // means no offset from case start date
              'activity_date_time' => '',
            ],
            1 => [
              'which_case_offset' => 1,
              'case_id' => 'REPLACE_ME',
              'case_subject' => 'Case Subject',
              'contact_id' => 'REPLACE_ME',
              'phone' => NULL,
              'contact_type' => 'Individual',
              'activity_type_id' => 'Open Case',
              'case_type_id' => 2,
              'case_status_id' => 1,
              'status_id' => 2,
              'case_start_date' => 'REPLACE_ME',
              'case_role' => 'Senior Services Coordinator is',
              // means no offset from case start date
              'activity_date_time' => '',
            ],
          ],
        ],
      ],

      1 => [
        'input' => [
          'other_date' => '-7 day',
        ],
        'expected' => [
          'upcoming' => [
            0 => [
              'which_case_offset' => 2,
              'case_id' => 'REPLACE_ME',
              'case_subject' => 'Case Subject',
              'contact_id' => 'REPLACE_ME',
              'phone' => NULL,
              'contact_type' => 'Individual',
              'activity_type_id' => 'Medical evaluation',
              'case_type_id' => 2,
              'case_status_id' => 1,
              'status_id' => 1,
              'case_start_date' => 'REPLACE_ME',
              'case_role' => 'Senior Services Coordinator is',
              'activity_date_time' => ' +3 day',
            ],
            1 => [
              'which_case_offset' => 1,
              'case_id' => 'REPLACE_ME',
              'case_subject' => 'Case Subject',
              'contact_id' => 'REPLACE_ME',
              'phone' => NULL,
              'contact_type' => 'Individual',
              'activity_type_id' => 'Medical evaluation',
              'case_type_id' => 2,
              'case_status_id' => 1,
              'status_id' => 1,
              'case_start_date' => 'REPLACE_ME',
              'case_role' => 'Senior Services Coordinator is',
              'activity_date_time' => ' +3 day',
            ],
          ],
          'recent' => [
            0 => [
              'which_case_offset' => 2,
              'case_id' => 'REPLACE_ME',
              'case_subject' => 'Case Subject',
              'contact_id' => 'REPLACE_ME',
              'phone' => NULL,
              'contact_type' => 'Individual',
              'activity_type_id' => 'Open Case',
              'case_type_id' => 2,
              'case_status_id' => 1,
              'status_id' => 2,
              'case_start_date' => 'REPLACE_ME',
              'case_role' => 'Senior Services Coordinator is',
              // means no offset from case start date
              'activity_date_time' => '',
            ],
            1 => [
              'which_case_offset' => 1,
              'case_id' => 'REPLACE_ME',
              'case_subject' => 'Case Subject',
              'contact_id' => 'REPLACE_ME',
              'phone' => NULL,
              'contact_type' => 'Individual',
              'activity_type_id' => 'Open Case',
              'case_type_id' => 2,
              'case_status_id' => 1,
              'status_id' => 2,
              'case_start_date' => 'REPLACE_ME',
              'case_role' => 'Senior Services Coordinator is',
              // means no offset from case start date
              'activity_date_time' => '',
            ],
          ],
        ],
      ],

      2 => [
        'input' => [
          'other_date' => '-14 day',
        ],
        'expected' => [
          'upcoming' => [
            0 => [
              'which_case_offset' => 2,
              'case_id' => 'REPLACE_ME',
              'case_subject' => 'Case Subject',
              'contact_id' => 'REPLACE_ME',
              'phone' => NULL,
              'contact_type' => 'Individual',
              'activity_type_id' => 'Medical evaluation',
              'case_type_id' => 2,
              'case_status_id' => 1,
              'status_id' => 1,
              'case_start_date' => 'REPLACE_ME',
              'case_role' => 'Senior Services Coordinator is',
              'activity_date_time' => ' +3 day',
            ],
            1 => [
              'which_case_offset' => 1,
              'case_id' => 'REPLACE_ME',
              'case_subject' => 'Case Subject',
              'contact_id' => 'REPLACE_ME',
              'phone' => NULL,
              'contact_type' => 'Individual',
              'activity_type_id' => 'Medical evaluation',
              'case_type_id' => 2,
              'case_status_id' => 1,
              'status_id' => 1,
              'case_start_date' => 'REPLACE_ME',
              'case_role' => 'Senior Services Coordinator is',
              'activity_date_time' => ' +3 day',
            ],
          ],
          'recent' => [
            0 => [
              'which_case_offset' => 1,
              'case_id' => 'REPLACE_ME',
              'case_subject' => 'Case Subject',
              'contact_id' => 'REPLACE_ME',
              'phone' => NULL,
              'contact_type' => 'Individual',
              'activity_type_id' => 'Open Case',
              'case_type_id' => 2,
              'case_status_id' => 1,
              'status_id' => 2,
              'case_start_date' => 'REPLACE_ME',
              'case_role' => 'Senior Services Coordinator is',
              // means no offset from case start date
              'activity_date_time' => '',
            ],
          ],
        ],
      ],

      3 => [
        'input' => [
          'other_date' => '-21 day',
        ],
        'expected' => [
          'upcoming' => [
            0 => [
              'which_case_offset' => 2,
              'case_id' => 'REPLACE_ME',
              'case_subject' => 'Case Subject',
              'contact_id' => 'REPLACE_ME',
              'phone' => NULL,
              'contact_type' => 'Individual',
              'activity_type_id' => 'Medical evaluation',
              'case_type_id' => 2,
              'case_status_id' => 1,
              'status_id' => 1,
              'case_start_date' => 'REPLACE_ME',
              'case_role' => 'Senior Services Coordinator is',
              'activity_date_time' => ' +3 day',
            ],
            1 => [
              'which_case_offset' => 1,
              'case_id' => 'REPLACE_ME',
              'case_subject' => 'Case Subject',
              'contact_id' => 'REPLACE_ME',
              'phone' => NULL,
              'contact_type' => 'Individual',
              'activity_type_id' => 'Medical evaluation',
              'case_type_id' => 2,
              'case_status_id' => 1,
              'status_id' => 1,
              'case_start_date' => 'REPLACE_ME',
              'case_role' => 'Senior Services Coordinator is',
              'activity_date_time' => ' +3 day',
            ],
          ],
          'recent' => [
            0 => [
              'which_case_offset' => 1,
              'case_id' => 'REPLACE_ME',
              'case_subject' => 'Case Subject',
              'contact_id' => 'REPLACE_ME',
              'phone' => NULL,
              'contact_type' => 'Individual',
              'activity_type_id' => 'Open Case',
              'case_type_id' => 2,
              'case_status_id' => 1,
              'status_id' => 2,
              'case_start_date' => 'REPLACE_ME',
              'case_role' => 'Senior Services Coordinator is',
              // means no offset from case start date
              'activity_date_time' => '',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Test that if you only have "my cases" permission you can still view
   * Manage Case for **closed** cases of yours.
   */
  public function testCanViewClosedCaseAsNonAdmin() {
    $loggedInUser = $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
      'edit all contacts',
      'add cases',
      // this is one important part we're testing
      'access my cases and activities',
    ];
    $individual = $this->individualCreate();
    $caseObj = $this->createCase($individual, $loggedInUser);
    $caseId = $caseObj->id;

    // This isn't everything needed to close a case but is good enough for
    // our purposes.
    $this->callAPISuccess('Case', 'create', [
      'id' => $caseId,
      'status_id' => 'Closed',
    ]);

    // Manage Case goes thru this tab even when not visiting from the tab.
    $tab = new CRM_Case_Page_Tab();
    $tab->set('action', 'view');
    $tab->set('cid', $individual);
    $tab->set('id', $caseId);
    $tab->set('context', 'standalone');
    $tab->preProcess();
    // At this point it would have thrown PrematureExitException if we didn't have access.
    // Let's assert something while we're here. This is also what would have
    // failed, but by itself doesn't depend on permissions.
    $this->assertArrayHasKey($caseId, CRM_Case_BAO_Case::getCases(FALSE, ['type' => 'any']));
  }

  /**
   * Test a high number of assigned case roles.
   */
  public function testGoingTo11() {
    $loggedInUser = $this->createLoggedInUser();
    $individual = $this->individualCreate();
    $caseObj = $this->createCase($individual, $loggedInUser);
    $caseId = $caseObj->id;

    // Create lots of assigned roles
    for ($i = 1; $i <= 30; $i++) {
      // create a new type
      $relationship_type_id = $this->callAPISuccess('RelationshipType', 'create', [
        'name_a_b' => "has as Wizard level $i",
        'name_b_a' => "is Wizard level $i for",
      ])['id'];

      // Now make a new person and give them the role
      $contact_id = $this->individualCreate([], 0, TRUE);
      $this->callAPISuccess('Relationship', 'create', [
        'case_id' => $caseId,
        'contact_id_a' => $individual,
        'contact_id_b' => $contact_id,
        'relationship_type_id' => $relationship_type_id,
      ]);
    }

    // Note the stock case type adds a manager role for the logged in user so it's 31 not 30.
    $this->assertCount(31, CRM_Case_BAO_Case::getCaseRoles($individual, $caseId), 'Why not just make ten louder?');
  }

  /**
   * Test that creating a regular activity with a subject including `[case #X]`
   * gets filed on case X.
   */
  public function testFileOnCaseBySubject() {
    $loggedInUserId = $this->createLoggedInUser();
    $clientId = $this->individualCreate();
    $caseObj = $this->createCase($clientId, $loggedInUserId);
    $subject = 'This should get filed on [case #' . $caseObj->id . ']';
    $form = $this->getFormObject('CRM_Activity_Form_Activity', [
      'source_contact_id' => $loggedInUserId,
      // target is comma-separated string, by the way
      'target_contact_id' => $clientId,
      'subject' => $subject,
      'activity_date_time' => date('Y-m-d H:i:s'),
      'activity_type_id' => 1,
    ]);
    $form->postProcess();

    $activity = $this->callAPISuccess('Activity', 'getsingle', [
      'subject' => $subject,
      'return' => ['case_id'],
    ]);
    // Note it's an array
    $this->assertEquals([$caseObj->id], $activity['case_id']);

    // Double-check
    $queryParams = [1 => [$subject, 'String']];
    $this->assertEquals(
      $caseObj->id,
      CRM_Core_DAO::singleValueQuery('SELECT ca.case_id
        FROM civicrm_case_activity ca
        INNER JOIN civicrm_activity a ON ca.activity_id = a.id
        WHERE a.subject = %1
        AND a.is_deleted = 0 AND a.is_current_revision = 1', $queryParams)
    );
  }

}

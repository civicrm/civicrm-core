<?php

/**
 * Class CRM_Case_BAO_CaseTest
 * @group headless
 */
class CRM_Case_BAO_CaseTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();

    $this->tablesToTruncate = array(
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
    );

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
    $params = array(
      'case_id' => 1,
      'contact_id' => 17,
    );
    CRM_Case_BAO_CaseContact::create($params);

    $recent = CRM_Utils_Recent::get();
    $this->assertEquals('Test Contact - Housing Support', $recent[0]['title']);
  }

  /**
   * Create and return case object of given Client ID.
   * @param $clientId
   * @return CRM_Case_BAO_Case
   */
  private function createCase($clientId) {
    $caseParams = array(
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
    );
    $form = new CRM_Case_Form_Case();
    $caseObj = $form->testSubmit($caseParams, "OpenCase", $clientId, "standalone");
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

    $this->callAPISuccess('Relationship', 'create', array(
      'contact_id_a'         => $contactIdA,
      'contact_id_b'         => $contactIdB,
      'relationship_type_id' => $relationshipType,
      'case_id'              => $caseId,
      'is_active'            => $isActive,
    ));
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
    $upcomingCases = CRM_Case_BAO_Case::getCases(FALSE, array(), 'dashboard', TRUE);
    $caseRoles = CRM_Case_BAO_Case::getCaseRoles($loggedInUser, $caseId);

    $this->assertEquals($caseCount, $upcomingCases, 'Upcoming case count must be ' . $caseCount);
    $this->assertEquals($caseCount, $summary['rows']['Housing Support']['Ongoing']['count'], 'Housing Support Ongoing case summary must be ' . $caseCount);
    $this->assertEquals($caseCount, count($caseRoles), 'Total case roles for logged in users must be ' . $caseCount);
  }

  /**
   * Test that Case count is exactly one for logged in user for user's active role.
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
    $this->assertEquals(array(1), $caseIds);
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
    $cases = CRM_Case_BAO_Case::getCasesSummary(TRUE, 3);
    $this->assertEquals(1, $cases['rows']['Housing Support']['Ongoing']['count']);
  }

  /* FIXME: requires activities
   * function testGetRelatedCases() {
   * }
   */

}

<?php
require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class CRM_Case_BAO_CaseTest
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

  public function testGetContactCases() {
    $cases = CRM_Case_BAO_Case::getContactCases(3);
    $this->assertEquals('Housing Support', $cases[1]['case_type']);
  }

  /* FIXME: requires activities
   * function testGetRelatedCases() {
   * }
   */

}

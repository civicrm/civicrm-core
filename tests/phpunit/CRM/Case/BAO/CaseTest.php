<?php
require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Case_BAO_CaseTest extends CiviUnitTestCase {
  function get_info() {
    return array(
      'name' => 'Case BAOs',
      'description' => 'Test Case_BAO_Case methods.',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();

    $this->loadAllFixtures();

    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
  }

  function testAddCaseToContact() {
    $params = array(
      'case_id' => 1,
      'contact_id' => 17,
    );
    CRM_Case_BAO_Case::addCaseToContact($params);

    $recent = CRM_Utils_Recent::get();
    $this->assertEquals('Test Contact - Housing Support', $recent[0]['title']);
  }

  function testGetCaseType() {
    $caseTypeLabel = CRM_Case_BAO_Case::getCaseType(1);
    $this->assertEquals('Housing Support', $caseTypeLabel);
  }

  function testRetrieveCaseIdsByContactId() {
    $caseIds = CRM_Case_BAO_Case::retrieveCaseIdsByContactId(3, FALSE, 'housing_support');
    $this->assertEquals(array(1), $caseIds);
  }

  /* FIXME: need to create an activity to run this test
   * function testGetCases() {
   *   $cases = CRM_Case_BAO_Case::getCases(TRUE, 3);
   *   $this->assertEquals('Housing Support', $cases[1]['case_type']);
   *   $this->assertEquals(1, $cases[1]['case_type_id']);
   * }
   */

  function testGetCasesSummary() {
    $cases = CRM_Case_BAO_Case::getCasesSummary(TRUE, 3);
    $this->assertEquals(1, $cases['rows']['Housing Support']['Ongoing']['count']);
  }

  function testGetUnclosedCases() {
    $params = array(
      'case_type' => 'ousing Suppor',
    );
    $cases = CRM_Case_BAO_Case::getUnclosedCases($params);
    $this->assertEquals('Housing Support', $cases[1]['case_type']);
  }

  function testGetContactCases() {
    $cases = CRM_Case_BAO_Case::getContactCases(3);
    $this->assertEquals('Housing Support', $cases[1]['case_type']);
  }

  /* FIXME: requires activities
   * function testGetRelatedCases() {
   * }
   */
}

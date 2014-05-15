<?php
require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Case_PseudoConstantTest extends CiviUnitTestCase {
  function get_info() {
    return array(
      'name' => 'Case PseudoConstants',
      'description' => 'Test Case_PseudoConstant methods.',
      'group' => 'Case',
    );
  }

  function setUp() {
    parent::setUp();

    $this->loadAllFixtures();

    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
  }

  function testCaseType() {
    CRM_Core_PseudoConstant::flush();
    $caseTypes = CRM_Case_PseudoConstant::caseType();
    $expectedTypes = array(
        1 => 'Housing Support',
    );
    $this->assertEquals($expectedTypes, $caseTypes);
  }
}

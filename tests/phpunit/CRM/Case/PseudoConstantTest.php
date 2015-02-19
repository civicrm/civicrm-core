<?php
require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Class CRM_Case_PseudoConstantTest
 */
class CRM_Case_PseudoConstantTest extends CiviCaseTestCase {

  public function testCaseType() {
    CRM_Core_PseudoConstant::flush();
    $caseTypes = CRM_Case_PseudoConstant::caseType();
    $expectedTypes = array(
      1 => 'Housing Support',
      2 => 'Adult Day Care Referral',
    );
    $this->assertEquals($expectedTypes, $caseTypes);
  }

}

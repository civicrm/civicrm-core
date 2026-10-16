<?php
require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Class CRM_Case_PseudoConstantTest
 * @group headless
 */
class CRM_Case_PseudoConstantTest extends CiviCaseTestCase {

  public function testCaseType(): void {
    CRM_Core_PseudoConstant::flush();
    $caseTypes = CRM_Case_PseudoConstant::caseType();
    $expectedTypes = [
      CRM_Core_PseudoConstant::getKey('CRM_Case_BAO_Case', 'case_type_id', 'housing_support') => 'Housing Support',
      CRM_Core_PseudoConstant::getKey('CRM_Case_BAO_Case', 'case_type_id', 'adult_day_care_referral') => 'Adult Day Care Referral',
    ];
    $this->assertEquals($expectedTypes, $caseTypes);
  }

}

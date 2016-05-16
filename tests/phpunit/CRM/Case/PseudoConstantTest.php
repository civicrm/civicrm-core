<<<<<<< HEAD
<?php
require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Class CRM_Case_PseudoConstantTest
 */
class CRM_Case_PseudoConstantTest extends CiviCaseTestCase {
  /**
   * @return array
   */
  function get_info() {
    return array(
      'name' => 'Case PseudoConstants',
      'description' => 'Test Case_PseudoConstant methods.',
      'group' => 'Case',
    );
  }

  function testCaseType() {
    CRM_Core_PseudoConstant::flush();
    $caseTypes = CRM_Case_PseudoConstant::caseType();
    $expectedTypes = array(
      1 => 'Housing Support',
      2 => 'Adult Day Care Referral',
    );
    $this->assertEquals($expectedTypes, $caseTypes);
  }
}
=======
<?php
require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Class CRM_Case_PseudoConstantTest
 * @group headless
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
>>>>>>> refs/remotes/civicrm/master

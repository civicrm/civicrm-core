<?php

require_once 'CRM/Utils/DeprecatedUtils.php';

/**
 * Class CRM_Utils_DeprecatedUtilsTest
 * @group headless
 */
class CRM_Utils_DeprecatedUtilsTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    // truncate a few tables
    $tablesToTruncate = [
      'civicrm_contact',
      'civicrm_email',
      'civicrm_contribution',
      'civicrm_website',
    ];

    $this->quickCleanup($tablesToTruncate);
  }

  /**
   *  Test civicrm_contact_check_params with no contact type.
   */
  public function testCheckParamsWithNoContactType(): void {
    $params = ['foo' => 'bar'];
    try {
      _civicrm_api3_deprecated_contact_check_params($params, FALSE);
    }
    catch (CRM_Core_Exception $e) {
      return;
    }
    $this->fail('An exception should have been thrown');
  }

  /**
   * Test civicrm_contact_check_params with a duplicate.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function testCheckParamsWithDuplicateContact2(): void {
    $this->individualCreate(['first_name' => 'Test', 'last_name' => 'Contact', 'email' => 'TestContact@example.com']);

    $params = [
      'first_name' => 'Test',
      'last_name' => 'Contact',
      'email' => 'TestContact@example.com',
      'contact_type' => 'Individual',
    ];
    try {
      $error = _civicrm_api3_deprecated_contact_check_params($params, TRUE);
      $this->assertEquals(1, $error['is_error']);
    }
    catch (CRM_Core_Exception $e) {
      $this->assertRegexp("/matching contacts.*1/s",
        $e->getMessage()
      );
      return;
    }
    // $this->fail('Exception was not optional');
  }

  /**
   *  Test civicrm_contact_check_params with check for required params.
   */
  public function testCheckParamsWithNoParams(): void {
    $params = [];
    try {
      _civicrm_api3_deprecated_contact_check_params($params, FALSE);
    }
    catch (CRM_Core_Exception $e) {
      return;
    }
    $this->fail('Exception required');
  }

}

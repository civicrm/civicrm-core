<?php

/**
 * Class CRM_Utils_Check_Component_EnvTest
 * @package CiviCRM
 * @subpackage CRM_Utils_Type
 * @group headless
 */
class CRM_Utils_Check_Component_EnvTest extends CiviUnitTestCase {

  public function tearDown(): void {
    Civi::settings()->set('logging', FALSE);
    $this->callAPISuccess('Extension', 'enable', ['key' => 'civi_report']);
    $this->callAPISuccess('Extension', 'enable', ['key' => 'civi_mail']);
    parent::tearDown();
  }

  /**
   * File check test should fail if reached maximum timeout.
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testResourceUrlCheck(): void {
    $check = new CRM_Utils_Check_Component_Env();
    $failRequest = $check->fileExists('https://civicrm.org', 0.001);
    $successRequest = $check->fileExists('https://civicrm.org', 0);

    $this->assertFalse($failRequest, 'Request should fail for minimum timeout.');
    $this->assertTrue($successRequest, 'Request should not fail for infinite timeout.');
  }

  /**
   * Test the check that warns users if they have enabled logging but not Civi-report.
   *
   * The check should not be triggered if logging is not enabled or it
   * is enabled and civi-report is enabled.
   *
   * @return void
   */
  public function testLoggingWithReport(): void {
    $this->callAPISuccess('Extension', 'disable', ['key' => 'civi_report']);
    $this->assertFalse($this->checkChecks('checkLoggingHasCiviReport'));

    Civi::settings()->set('logging', 1);
    $check = $this->checkChecks('checkLoggingHasCiviReport');
    $this->assertEquals('CiviReport required to display detailed logging.', $check['title']);

    $this->callAPISuccess('Extension', 'enable', ['key' => 'civi_report']);
    $this->assertFalse($this->checkChecks('checkLoggingHasCiviReport'));
  }

  /**
   * Check the checks are checking for the checky thing.
   *
   * @return bool|array
   */
  public function checkChecks($checkName) {
    $checks = $this->callAPISuccess('System', 'check');
    foreach ($checks['values'] as $check) {
      if ($check['name'] === $checkName) {
        return $check;
      }
    }
    return FALSE;
  }

}

<?php

/**
 * Class CRM_Utils_Check_Component_EnvTest
 * @package CiviCRM
 * @subpackage CRM_Utils_Type
 * @group headless
 */
class CRM_Utils_Check_Component_EnvTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * File check test should fail if reached maximum timeout.
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testResourceUrlCheck() {
    $check = new \CRM_Utils_Check_Component_Env();
    $failRequest = $check->fileExists('https://civicrm.org', 0.001);
    $successRequest = $check->fileExists('https://civicrm.org', 0);

    $this->assertEquals(FALSE, $failRequest, 'Request should fail for minimum timeout.');
    $this->assertEquals(TRUE, $successRequest, 'Request should not fail for infinite timeout.');

  }

}

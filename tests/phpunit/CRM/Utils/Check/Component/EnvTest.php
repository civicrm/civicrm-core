<?php

/**
 * Class CRM_Utils_Check_Component_EnvTest
 * @package CiviCRM
 * @subpackage CRM_Utils_Type
 * @group headless
 */
class CRM_Utils_Check_Component_EnvTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * File check test should fail if reached maximum timeout.
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testResourceUrlCheck(): void {
    $check = new CRM_Utils_Check_Component_Env();
    $failRequest = $check->fileExists('https://civicrm.org', 0.001);
    $successRequest = $check->fileExists('https://civicrm.org', 0);

    $this->assertEquals(FALSE, $failRequest, 'Request should fail for minimum timeout.');
    $this->assertEquals(TRUE, $successRequest, 'Request should not fail for infinite timeout.');

  }

  /**
   * Test legacy extension type status check warning message.
   */
  public function testCheckExtensionTypes(): void {
    $originalSystem = CRM_Extension_System::singleton();

    $mapperMock = $this->createMock('CRM_Extension_Mapper');
    $managerMock = $this->createMock('CRM_Extension_Manager');

    $managerMock->method('getStatuses')->willReturn([
      'my_module_ext' => CRM_Extension_Manager::STATUS_INSTALLED,
      'my_legacy_ext' => CRM_Extension_Manager::STATUS_INSTALLED,
      'my_disabled_legacy_ext' => CRM_Extension_Manager::STATUS_DISABLED,
    ]);

    $mapperMock->method('keyToInfo')->willReturnCallback(function($key) {
      if ($key === 'my_module_ext') {
        return new CRM_Extension_Info('my_module_ext', 'module');
      }
      if ($key === 'my_legacy_ext') {
        return new CRM_Extension_Info('my_legacy_ext', 'payment');
      }
      if ($key === 'my_disabled_legacy_ext') {
        return new CRM_Extension_Info('my_disabled_legacy_ext', 'report');
      }
      throw new CRM_Extension_Exception("Unknown extension");
    });

    $systemMock = $this->getMockBuilder('CRM_Extension_System')
      ->disableOriginalConstructor()
      ->getMock();
    $systemMock->method('getMapper')->willReturn($mapperMock);
    $systemMock->method('getManager')->willReturn($managerMock);

    CRM_Extension_System::setSingleton($systemMock);

    try {
      $check = new CRM_Utils_Check_Component_Env();
      $messages = $check->checkExtensionTypes();

      $this->assertCount(1, $messages);
      $message = $messages[0];
      $this->assertEquals('checkExtensionTypes_my_legacy_ext', $message->getName());
      $this->assertStringContainsString('extension "my_legacy_ext" uses legacy type "payment"', $message->getMessage());
      $this->assertEquals('warning', $message->getSeverity());
    }
    finally {
      CRM_Extension_System::setSingleton($originalSystem);
    }
  }

}

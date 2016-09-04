<?php

/**
 * Class CRM_Utils_SystemTest
 * @group headless
 */
class CRM_Utils_SystemTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function testUrlQueryString() {
    $config = CRM_Core_Config::singleton();
    $this->assertTrue($config->userSystem instanceof CRM_Utils_System_UnitTests);
    $expected = '/index.php?q=civicrm/foo/bar&foo=ab&bar=cd%26ef';
    $actual = CRM_Utils_System::url('civicrm/foo/bar', 'foo=ab&bar=cd%26ef', FALSE, NULL, FALSE);
    $this->assertEquals($expected, $actual);
  }

  public function testUrlQueryArray() {
    $config = CRM_Core_Config::singleton();
    $this->assertTrue($config->userSystem instanceof CRM_Utils_System_UnitTests);
    $expected = '/index.php?q=civicrm/foo/bar&foo=ab&bar=cd%26ef';
    $actual = CRM_Utils_System::url('civicrm/foo/bar', array(
      'foo' => 'ab',
      'bar' => 'cd&ef',
    ), FALSE, NULL, FALSE);
    $this->assertEquals($expected, $actual);
  }

  public function testEvalUrl() {
    $this->assertEquals(FALSE, CRM_Utils_System::evalUrl(FALSE));
    $this->assertEquals('http://example.com/', CRM_Utils_System::evalUrl('http://example.com/'));
    $this->assertEquals('http://example.com/?cms=UnitTests', CRM_Utils_System::evalUrl('http://example.com/?cms={uf}'));
  }

}

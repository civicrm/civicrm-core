<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Utils_SystemTest extends CiviUnitTestCase {

  function get_info() {
    return array(
      'name'      => 'System Test',
      'description' => 'Test system functions',
      'group'      => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();
  }

  function testUrlQueryString() {
    $config = CRM_Core_Config::singleton();
    $this->assertTrue($config->userSystem instanceof CRM_Utils_System_UnitTests);
    $expected = '/index.php?q=civicrm/foo/bar&foo=ab&bar=cd%26ef';
    $actual = CRM_Utils_System::url('civicrm/foo/bar', 'foo=ab&bar=cd%26ef', false, null, false);
    $this->assertEquals($expected, $actual);
  }
  
  function testUrlQueryArray() {
    $config = CRM_Core_Config::singleton();
    $this->assertTrue($config->userSystem instanceof CRM_Utils_System_UnitTests);
    $expected = '/index.php?q=civicrm/foo/bar&foo=ab&bar=cd%26ef';
    $actual = CRM_Utils_System::url('civicrm/foo/bar', array(
      'foo' => 'ab',
      'bar' => 'cd&ef',
    ), false, null, false);
    $this->assertEquals($expected, $actual);
  }
}

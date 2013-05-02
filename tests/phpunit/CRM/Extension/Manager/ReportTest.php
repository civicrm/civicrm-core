<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Extension_Manager_ReportTest extends CiviUnitTestCase {
  function setUp() {
    parent::setUp();
    //if (class_exists('test_extension_manager_reporttest')) {
    //  test_extension_manager_reporttest::$counts = array();
    //}
    $this->system = new CRM_Extension_System(array(
      'extensionsDir' => '',
      'extensionsURL' => '',
    ));
  }

  function tearDown() {
    parent::tearDown();
  }

  /**
   * Install an extension with a valid type name
   */
  function testInstallDisableUninstall() {
    $manager = $this->system->getManager();
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest"');

    $manager->install(array('test.extension.manager.reporttest'));
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest" AND is_active = 1');

    $manager->disable(array('test.extension.manager.reporttest'));
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest" AND is_active = 0');

    $manager->uninstall(array('test.extension.manager.reporttest'));
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest"');
  }

  /**
   * Install an extension with a valid type name
   */
  function testInstallDisableEnable() {
    $manager = $this->system->getManager();
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest"');

    $manager->install(array('test.extension.manager.reporttest'));
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest" AND is_active = 1');

    $manager->disable(array('test.extension.manager.reporttest'));
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest" AND is_active = 0');

    $manager->enable(array('test.extension.manager.reporttest'));
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest" AND is_active = 1');
  }
}

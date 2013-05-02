<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Extension_Manager_SearchTest extends CiviUnitTestCase {
  function setUp() {
    parent::setUp();
    //if (class_exists('test_extension_manager_searchtest')) {
    //  test_extension_manager_searchtest::$counts = array();
    //}
    $this->system = new CRM_Extension_System(array(
      'extensionsDir' => '',
      'extensionsURL' => '',
    ));
  }

  function tearDown() {
    parent::tearDown();
    $this->system = NULL;
  }

  /**
   * Install an extension with a valid type name
   */
  function testInstallDisableUninstall() {
    $manager = $this->system->getManager();
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest"');

    $manager->install(array('test.extension.manager.searchtest'));
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest" AND is_active = 1');

    $manager->disable(array('test.extension.manager.searchtest'));
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest" AND is_active = 0');

    $manager->uninstall(array('test.extension.manager.searchtest'));
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest"');
  }

  /**
   * Install an extension with a valid type name
   */
  function testInstallDisableEnable() {
    $manager = $this->system->getManager();
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest"');

    $manager->install(array('test.extension.manager.searchtest'));
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest" AND is_active = 1');

    $manager->disable(array('test.extension.manager.searchtest'));
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest" AND is_active = 0');

    $manager->enable(array('test.extension.manager.searchtest'));
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest" AND is_active = 1');
  }
}

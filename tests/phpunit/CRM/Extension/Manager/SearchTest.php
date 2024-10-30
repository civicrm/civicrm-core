<?php

/**
 * Class CRM_Extension_Manager_SearchTest
 * @group headless
 */
class CRM_Extension_Manager_SearchTest extends CiviUnitTestCase {

  /**
   * @var CRM_Extension_System
   */
  private $system;

  public function setUp(): void {
    parent::setUp();
    //if (class_exists('test_extension_manager_searchtest')) {
    //  test_extension_manager_searchtest::$counts = array();
    //}
    $this->system = new CRM_Extension_System([
      'extensionsDir' => '',
      'extensionsURL' => '',
    ]);
  }

  public function tearDown(): void {
    parent::tearDown();
    $this->system = NULL;
  }

  /**
   * Install an extension with a valid type name.
   */
  public function testInstallDisableUninstall(): void {
    $manager = $this->system->getManager();
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest"');

    $manager->install(['test.extension.manager.searchtest']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest" AND is_active = 1');

    $manager->disable(['test.extension.manager.searchtest']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest" AND is_active = 0');

    $manager->uninstall(['test.extension.manager.searchtest']);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest"');
  }

  /**
   * Install an extension with a valid type name.
   */
  public function testInstallDisableEnable(): void {
    $manager = $this->system->getManager();
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest"');

    $manager->install(['test.extension.manager.searchtest']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest" AND is_active = 1');

    $manager->disable(['test.extension.manager.searchtest']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest" AND is_active = 0');

    $manager->enable(['test.extension.manager.searchtest']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.searchtest" AND is_active = 1');
  }

}

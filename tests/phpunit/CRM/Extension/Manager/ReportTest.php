<?php

/**
 * Class CRM_Extension_Manager_ReportTest
 * @group headless
 */
class CRM_Extension_Manager_ReportTest extends CiviUnitTestCase {

  /**
   * @var CRM_Extension_System
   */
  private $system;

  public function setUp(): void {
    parent::setUp();
    //if (class_exists('test_extension_manager_reporttest')) {
    //  test_extension_manager_reporttest::$counts = array();
    //}
    $this->system = new CRM_Extension_System([
      'extensionsDir' => '',
      'extensionsURL' => '',
    ]);
  }

  /**
   * Install an extension with a valid type name.
   */
  public function testInstallDisableUninstall(): void {
    $manager = $this->system->getManager();
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest"');

    $manager->install(['test.extension.manager.reporttest']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest" AND is_active = 1');

    $manager->disable(['test.extension.manager.reporttest']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest" AND is_active = 0');

    $manager->uninstall(['test.extension.manager.reporttest']);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest"');
  }

  /**
   * Install an extension with a valid type name.
   */
  public function testInstallDisableEnable(): void {
    $manager = $this->system->getManager();
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest"');

    $manager->install(['test.extension.manager.reporttest']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest" AND is_active = 1');

    $manager->disable(['test.extension.manager.reporttest']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest" AND is_active = 0');

    $manager->enable(['test.extension.manager.reporttest']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "test.extension.manager.reporttest" AND is_active = 1');
  }

}

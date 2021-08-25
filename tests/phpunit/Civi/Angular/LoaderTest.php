<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Angular;

/**
 * Test the Angular loader.
 */
class LoaderTest extends \CiviUnitTestCase {

  public static $dummy_setting_count = 0;
  public static $dummy_callback_count = 0;

  public function setUp(): void {
    parent::setUp();
    $this->hookClass->setHook('civicrm_angularModules', [$this, 'hook_angularModules']);
    self::$dummy_setting_count = 0;
    self::$dummy_callback_count = 0;
    $this->createLoggedInUser();
  }

  public function factoryScenarios() {
    return [
      ['dummy1', 2, 1, ['access CiviCRM', 'administer CiviCRM']],
      ['dummy2', 2, 0, []],
      ['dummy3', 2, 2, ['access CiviCRM', 'administer CiviCRM', 'view debug output']],
    ];
  }

  /**
   * Tests that AngularLoader only conditionally loads settings via factory functions for in-use modules.
   * Our dummy settings callback functions keep a count of the number of times they have been called.
   *
   * @dataProvider factoryScenarios
   * @param $module
   * @param $expectedSettingCount
   * @param $expectedCallbackCount
   * @param $expectedPermissions
   */
  public function testSettingFactory($module, $expectedSettingCount, $expectedCallbackCount, $expectedPermissions) {
    $loader = \Civi::service('angularjs.loader');
    $loader->addModules([$module]);
    $loader->useApp();

    // Load angular resources.
    //
    // It seems like calling something like
    //   \CRM_Core_Region::instance('html-header')->render('');
    // would be more realistic but then the test fails. Maybe a future todo.
    \Civi::dispatcher()->dispatch('civi.region.render', \Civi\Core\Event\GenericHookEvent::create(['region' => \CRM_Core_Region::instance('html-header')]));

    // Run factory callbacks
    $actual = \Civi::resources()->getSettings();

    // Dummy1 module's factory setting should be set if it is loaded directly or required by dummy3
    $this->assertTrue(($expectedCallbackCount > 0) === isset($actual['dummy1']['dummy_setting_factory']));
    // Dummy3 module's factory setting should be set if it is loaded directly
    $this->assertTrue(($expectedCallbackCount > 1) === isset($actual['dummy3']['dummy_setting_factory']));

    // Dummy1 module's regular setting should be set if it is loaded directly or required by dummy3
    $this->assertTrue(($module !== 'dummy2') === isset($actual['dummy1']['dummy_setting']));
    // Dummy2 module's regular setting should be set if loaded
    $this->assertTrue(($module === 'dummy2') === isset($actual['dummy2']['dummy_setting']));

    // Assert appropriate permissions have been added
    $this->assertEquals($expectedPermissions, array_keys($actual['permissions']));

    // Assert the callback functions ran the expected number of times
    $this->assertEquals($expectedSettingCount, self::$dummy_setting_count);
    $this->assertEquals($expectedCallbackCount, self::$dummy_callback_count);
  }

  public function hook_angularModules(&$modules) {
    $modules['dummy1'] = [
      'ext' => 'civicrm',
      'settings' => $this->getDummySetting(),
      'permissions' => ['access CiviCRM', 'administer CiviCRM'],
      'settingsFactory' => [self::class, 'getDummySettingFactory'],
    ];
    $modules['dummy2'] = [
      'ext' => 'civicrm',
      'settings' => $this->getDummySetting(),
    ];
    $modules['dummy3'] = [
      'ext' => 'civicrm',
      // The string self::class is preferred but passing object $this should also work
      'settingsFactory' => [$this, 'getDummySettingFactory'],
      // This should get merged with dummy1's permissions
      'permissions' => ['view debug output', 'administer CiviCRM'],
      'requires' => ['dummy1'],
    ];
  }

  public function getDummySetting() {
    return ['dummy_setting' => self::$dummy_setting_count++];
  }

  public static function getDummySettingFactory() {
    return ['dummy_setting_factory' => self::$dummy_callback_count++];
  }

}

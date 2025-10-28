<?php

namespace Civi\Shimmy\Mixins;

/**
 * Assert that the `settings/*.setting.php` mixin is working properly.
 *
 * This class defines the assertions to run when installing or uninstalling the extension.
 * It use called as part of E2E_Shimmy_LifecycleTest.
 *
 * @see E2E_Shimmy_LifecycleTest
 */
class EntityTypes2Test extends \PHPUnit\Framework\Assert {

  const EXAMPLE_DAO = 'CRM_Shimmy_DAO_ShimThing2';

  const EXAMPLE_TABLE = 'civicrm_shim_thing2';

  const EXAMPLE_NAME = 'ShimThing2';

  public function testPreConditions($cv): void {
    $this->assertFileExists(static::getPath('/schema/ShimThing2.entityType.php'), 'The shimmy extension must have *.entityTYpe.php.');
  }

  public function testInstalled($cv): void {
    $this->assertEquals(self::EXAMPLE_NAME, $cv->phpCall('CRM_Core_DAO_AllCoreTables::getEntityNameForClass', [self::EXAMPLE_DAO]));
    $this->assertEquals(self::EXAMPLE_TABLE, $cv->phpCall('CRM_Core_DAO_AllCoreTables::getTableForClass', [self::EXAMPLE_DAO]));
    $this->assertEquals(self::EXAMPLE_NAME, $cv->phpCall('CRM_Core_DAO_AllCoreTables::getEntityNameForTable', [self::EXAMPLE_TABLE]));
  }

  public function testDisabled($cv): void {
    $this->assertEquals(NULL, $cv->phpCall('CRM_Core_DAO_AllCoreTables::getEntityNameForClass', [self::EXAMPLE_DAO]));
    $this->assertEquals(NULL, $cv->phpCall('CRM_Core_DAO_AllCoreTables::getTableForClass', [self::EXAMPLE_DAO]));
    $this->assertEquals(NULL, $cv->phpCall('CRM_Core_DAO_AllCoreTables::getEntityNameForTable', [self::EXAMPLE_TABLE]));
  }

  public function testUninstalled($cv): void {
    $this->assertEquals(NULL, $cv->phpCall('CRM_Core_DAO_AllCoreTables::getEntityNameForClass', [self::EXAMPLE_DAO]));
    $this->assertEquals(NULL, $cv->phpCall('CRM_Core_DAO_AllCoreTables::getTableForClass', [self::EXAMPLE_DAO]));
    $this->assertEquals(NULL, $cv->phpCall('CRM_Core_DAO_AllCoreTables::getEntityNameForTable', [self::EXAMPLE_TABLE]));
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 2) . $suffix;
  }

}

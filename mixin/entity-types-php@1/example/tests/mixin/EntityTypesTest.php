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
class EntityTypesTest extends \PHPUnit\Framework\Assert {

  const EXAMPLE_DAO = 'CRM_Shimmy_DAO_ShimThing';

  const EXAMPLE_TABLE = 'civicrm_shim_thing';

  const EXAMPLE_NAME = 'ShimThing';

  public function testPreConditions($cv): void {
    $this->assertFileExists(static::getPath('/xml/schema/CRM/Shimmy/ShimThing.xml'), 'The shimmy extension must have *.xml.');
    $this->assertFileExists(static::getPath('/xml/schema/CRM/Shimmy/ShimThing.entityType.php'), 'The shimmy extension must have *.entityTYpe.php.');
    $this->assertFileExists(static::getPath('/CRM/Shimmy/DAO/ShimThing.php'), 'The shimmy extension must have DAO.');
  }

  public function testInstalled($cv): void {
    $this->assertEquals(self::EXAMPLE_NAME, $cv->phpCall('CRM_Core_DAO_AllCoreTables::getEntityNameForClass', [self::EXAMPLE_DAO]));
    $this->assertEquals(self::EXAMPLE_TABLE, $cv->phpCall('CRM_Core_DAO_AllCoreTables::getTableForClass', [self::EXAMPLE_DAO]));
    $this->assertEquals(self::EXAMPLE_NAME, $cv->phpCall('CRM_Core_DAO_AllCoreTables::getEntityNameForTable', [self::EXAMPLE_TABLE]));
    $this->assertEquals('ShimThing ID', $cv->phpEval('return \CRM_Shimmy_DAO_ShimThing::fields()["id"]["title"];'));
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

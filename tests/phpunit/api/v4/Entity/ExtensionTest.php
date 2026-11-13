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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Api4\Extension;
use Civi\Test\Invasive;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ExtensionTest extends Api4TestBase implements TransactionalInterface {

  public function testGet(): void {
    $moduleTest = Extension::get(FALSE)
      ->addWhere('key', '=', 'test.extension.manager.moduletest')
      ->execute()->single();
    $this->assertSame('test_extension_manager_moduletest', $moduleTest['label']);
    $this->assertEquals(['mgmt:hidden', 'mock'], $moduleTest['tags']);

    $moduleTest = Extension::get(FALSE)
      ->addWhere('file', '=', 'moduletest')
      ->execute()->single();
    $this->assertSame('test_extension_manager_moduletest', $moduleTest['label']);
    $this->assertEquals(['mgmt:hidden', 'mock'], $moduleTest['tags']);
  }

  /**
   * Tests that \Civi\Api4\Action\Extension\Get minimizes calls to the extensionMapper
   */
  public function testOptimizedGet(): void {
    $mapper = \CRM_Extension_System::singleton()->getMapper();

    $extensions = Extension::get(FALSE)->execute()
      ->indexBy('key');
    $status = $extensions['test.extension.manager.moduletest']['status'];

    $this->assertGreaterThan(1, count(Invasive::get([$mapper, 'infos'])));

    // Reset Mapper's internal cache
    Invasive::set([$mapper, 'infos'], []);

    Extension::get(FALSE)
      ->addWhere('key', '=', 'test.extension.manager.moduletest')
      ->addWhere('status', '=', $status)
      ->execute()->single();

    // Optimization should have prevented Mapper from loading more than the 1 requested extension
    $this->assertCount(1, Invasive::get([$mapper, 'infos']));

    // Selecting only key and status should skip Mapper info loading altogether
    Invasive::set([$mapper, 'infos'], []);
    $result = Extension::get(FALSE)
      ->addSelect('key', 'status')
      ->addWhere('key', '=', 'test.extension.manager.moduletest')
      ->addOrderBy('status')
      ->execute()->single();
    $this->assertSame([
      'key' => 'test.extension.manager.moduletest',
      'status' => $status,
    ], $result);
    $this->assertCount(0, Invasive::get([$mapper, 'infos']));
  }

  public function testEnableDisableUninstall(): void {
    $key = 'test.extension.manager.moduletest';

    $initial = Extension::get(FALSE)
      ->addWhere('key', '=', $key)
      ->execute()->single();
    $this->assertSame(\CRM_Extension_Manager::STATUS_UNINSTALLED, $initial['status']);

    // Enable by file name; default payload only includes key and status
    $enabled = Extension::enable(FALSE)
      ->addWhere('file', '=', 'moduletest')
      ->execute();
    $this->assertSame([
      [
        'key' => $key,
        'status' => \CRM_Extension_Manager::STATUS_INSTALLED,
      ],
    ], (array) $enabled);

    $disabled = Extension::disable(FALSE)
      ->addWhere('key', '=', $key)
      ->execute();
    $this->assertSame([
      [
        'key' => $key,
        'status' => \CRM_Extension_Manager::STATUS_DISABLED,
      ],
    ], (array) $disabled);

    $uninstalled = Extension::uninstall(FALSE)
      ->addWhere('key', '=', $key)
      ->execute();
    $this->assertSame([
      [
        'key' => $key,
        'status' => \CRM_Extension_Manager::STATUS_UNINSTALLED,
      ],
    ], (array) $uninstalled);
  }

  public function testReload(): void {
    $key = 'test.extension.manager.moduletest';

    $enabled = Extension::enable(FALSE)
      ->addWhere('key', '=', $key)
      ->setReload(TRUE)
      ->execute()->single();
    $this->assertSame($key, $enabled['key']);
    $this->assertSame(\CRM_Extension_Manager::STATUS_INSTALLED, $enabled['status']);
    $this->assertSame('test_extension_manager_moduletest', $enabled['label']);
    $this->assertEquals(['mgmt:hidden', 'mock'], $enabled['tags']);

    $disabled = Extension::disable(FALSE)
      ->addWhere('key', '=', $key)
      ->setReload(['key', 'label', 'status'])
      ->execute()->single();
    $this->assertSame([
      'key' => $key,
      'label' => 'test_extension_manager_moduletest',
      'status' => \CRM_Extension_Manager::STATUS_DISABLED,
    ], $disabled);

    Extension::uninstall(FALSE)
      ->addWhere('key', '=', $key)
      ->execute();
  }

  public function testUninstallEnabledExtension(): void {
    $key = 'test.extension.manager.moduletest';
    Extension::enable(FALSE)
      ->addWhere('key', '=', $key)
      ->execute();

    $uninstalled = Extension::uninstall(FALSE)
      ->addWhere('key', '=', $key)
      ->execute();
    $this->assertSame([
      [
        'key' => $key,
        'status' => \CRM_Extension_Manager::STATUS_UNINSTALLED,
      ],
    ], (array) $uninstalled);
  }

  public function testMissingWhereThrowsException(): void {
    $this->expectException(\CRM_Core_Exception::class);
    Extension::enable(FALSE)->execute();
  }

  public function setUp(): void {
    parent::setUp();
    $this->ensureModuletestUninstalled();
  }

  public function tearDown(): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    $this->ensureModuletestUninstalled();
    parent::tearDown();
  }

  private function ensureModuletestUninstalled(): void {
    $manager = \CRM_Extension_System::singleton()->getManager();
    $status = $manager->getStatus('test.extension.manager.moduletest');
    if ($status === \CRM_Extension_Manager::STATUS_INSTALLED) {
      $manager->disable(['test.extension.manager.moduletest']);
      $status = $manager->getStatus('test.extension.manager.moduletest');
    }
    if ($status === \CRM_Extension_Manager::STATUS_DISABLED) {
      $manager->uninstall(['test.extension.manager.moduletest']);
    }
  }

  public function testPermissions(): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->expectException(\Civi\API\Exception\UnauthorizedException::class);
    Extension::enable(TRUE)
      ->addWhere('key', '=', 'test.extension.manager.moduletest')
      ->execute();
  }

}

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
    $this->assertEquals('test_extension_manager_moduletest', $moduleTest['label']);
    $this->assertEquals(['mock'], $moduleTest['tags']);

    $moduleTest = Extension::get(FALSE)
      ->addWhere('file', '=', 'moduletest')
      ->execute()->single();
    $this->assertEquals('test_extension_manager_moduletest', $moduleTest['label']);
    $this->assertEquals(['mock'], $moduleTest['tags']);
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
  }

}

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

use api\v4\UnitTestCase;
use Civi\Api4\Extension;

/**
 * @group headless
 */
class ExtensionTest extends UnitTestCase {

  public function testGet() {
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

}

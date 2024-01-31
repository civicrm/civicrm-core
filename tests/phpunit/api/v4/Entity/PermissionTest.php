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

use Civi\Api4\Permission;
use api\v4\Api4TestBase;

/**
 * @group headless
 */
class PermissionTest extends Api4TestBase {

  public function testGet(): void {
    $permissions = (array) Permission::get(FALSE)->execute()->indexBy('name');
    $this->assertArrayHasKey('*always deny*', $permissions);
    $this->assertEquals(['*'], $permissions['*always allow*']['implies']);
    $this->assertEquals(['*'], $permissions['all CiviCRM permissions and ACLs']['implies']);
    $this->assertContains('edit message templates', $permissions['administer CiviCRM data']['implies']);
  }

}

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
use Civi\Api4\Navigation;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class NavigationTest extends UnitTestCase implements TransactionalInterface {

  public function testCreate() {
    $created = Navigation::create(FALSE)
      ->addValue('permission', ['administer CiviCRM', 'access CiviCRM'])
      ->addValue('name', 'Test menu item')
      ->execute()->single();

    $fetched = Navigation::get(FALSE)
      ->addWhere('id', '=', $created['id'])
      ->execute()->single();

    $this->assertEquals(['administer CiviCRM', 'access CiviCRM'], $created['permission']);
    $this->assertEquals(\CRM_Core_Config::domainID(), $fetched['domain_id']);
    $this->assertGreaterThan(0, $fetched['weight']);
  }

}

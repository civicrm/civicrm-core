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
use Civi\Api4\Domain;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class DomainTest extends UnitTestCase implements TransactionalInterface {

  public function testActiveDomain() {
    Domain::create(FALSE)
      ->addValue('name', 'Not current')
      ->addValue('version', \CRM_Utils_System::version())
      ->execute();

    Domain::update(FALSE)
      ->addValue('name', 'Currently the current domain')
      ->addWhere('is_active', '=', TRUE)
      ->execute();

    $getCurrent = Domain::get(FALSE)
      ->addWhere('is_active', '=', TRUE)
      ->execute()->single();

    $this->assertEquals('Currently the current domain', $getCurrent['name']);

    $getAll = Domain::get(FALSE)
      ->addSelect('*', 'is_active')
      ->execute()->indexBy('name');

    $this->assertTrue($getAll['Currently the current domain']['is_active']);
    $this->assertFalse($getAll['Not current']['is_active']);
  }

}

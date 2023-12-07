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
use Civi\Api4\Domain;
use Civi\Api4\WordReplacement;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class DomainTest extends Api4TestBase implements TransactionalInterface {

  /**
   * Test active domain and domain_id.name parameters.
   *
   * @throws \CRM_Core_Exception
   */
  public function testActiveDomain(): void {
    Domain::create(FALSE)
      ->addValue('name', 'Not current')
      ->addValue('version', \CRM_Utils_System::version())
      ->execute();
    Domain::create(FALSE)
      ->addValue('name', 'Also not current')
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
    $this->assertFalse($getAll['Also not current']['is_active']);

    $getNotCurrent = Domain::get(FALSE)
      ->addWhere('id', '!=', 'current_domain')
      ->execute()->column('name');

    $this->assertContains('Not current', $getNotCurrent);
    $this->assertContains('Also not current', $getNotCurrent);
    $this->assertNotContains('Currently the current domain', $getNotCurrent);

    $wordReplacements = $this->saveTestRecords('WordReplacement', [
      'records' => [
        ['find_word' => 'One', 'replace_word' => 'First'],
        ['find_word' => 'Two', 'replace_word' => 'Second', 'domain_id:name' => 'Not current'],
        ['find_word' => 'Three', 'replace_word' => 'Third', 'domain_id:name' => 'Also not current'],
      ],
    ])->column('id');

    $fromTwoDomains = WordReplacement::get(FALSE)
      ->addWhere('domain_id:name', 'IN', ['current_domain', 'Not current'])
      ->execute()->column('id');

    $this->assertContains($wordReplacements[0], $fromTwoDomains);
    $this->assertContains($wordReplacements[1], $fromTwoDomains);
    $this->assertNotContains($wordReplacements[2], $fromTwoDomains);
  }

}

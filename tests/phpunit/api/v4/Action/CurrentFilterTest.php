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


namespace api\v4\Action;

use Civi\Api4\Relationship;
use api\v4\Api4TestBase;
use Civi\Api4\Contact;
use Civi\Api4\SavedSearch;
use Civi\Api4\UserJob;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class CurrentFilterTest extends Api4TestBase implements TransactionalInterface {

  /**
   * Test relationship is_current checks start, end, active.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCurrentRelationship(): void {
    $contactID1 = Contact::create()->addValue('first_name', 'Bob1')->execute()->first()['id'];
    $contactID2 = Contact::create()->addValue('first_name', 'Bob2')->execute()->first()['id'];

    $current = Relationship::create()->setValues([
      'relationship_type_id' => 1,
      'contact_id_a' => $contactID1,
      'contact_id_b' => $contactID2,
      'end_date' => 'now + 1 week',
    ])->execute()->first();
    $indefinite = Relationship::create()->setValues([
      'relationship_type_id' => 2,
      'contact_id_a' => $contactID1,
      'contact_id_b' => $contactID2,
    ])->execute()->first();
    $expiring = Relationship::create()->setValues([
      'relationship_type_id' => 3,
      'contact_id_a' => $contactID1,
      'contact_id_b' => $contactID2,
      'end_date' => 'now',
    ])->execute()->first();
    $past = Relationship::create()->setValues([
      'relationship_type_id' => 3,
      'contact_id_a' => $contactID1,
      'contact_id_b' => $contactID2,
      'end_date' => 'now - 1 week',
    ])->execute()->first();
    $inactive = Relationship::create()->setValues([
      'relationship_type_id' => 4,
      'contact_id_a' => $contactID1,
      'contact_id_b' => $contactID2,
      'is_active' => 0,
    ])->execute()->first();

    $getCurrent = Relationship::get()->addWhere('is_current', '=', TRUE)->execute()->indexBy('id');
    $notCurrent = Relationship::get()->addWhere('is_current', '=', FALSE)->execute()->indexBy('id');
    $getAll = Relationship::get()->addSelect('is_current')->execute()->indexBy('id');

    $this->assertTrue($getAll[$current['id']]['is_current']);
    $this->assertTrue($getAll[$indefinite['id']]['is_current']);
    $this->assertTrue($getAll[$expiring['id']]['is_current']);
    $this->assertFalse($getAll[$past['id']]['is_current']);
    $this->assertFalse($getAll[$inactive['id']]['is_current']);

    $this->assertArrayHasKey($current['id'], $getCurrent);
    $this->assertArrayHasKey($indefinite['id'], $getCurrent);
    $this->assertArrayHasKey($expiring['id'], $getCurrent);
    $this->assertArrayNotHasKey($past['id'], $getCurrent);
    $this->assertArrayNotHasKey($inactive['id'], $getCurrent);

    $this->assertArrayNotHasKey($current['id'], $notCurrent);
    $this->assertArrayNotHasKey($indefinite['id'], $notCurrent);
    $this->assertArrayNotHasKey($expiring['id'], $notCurrent);
    $this->assertArrayHasKey($past['id'], $notCurrent);
    $this->assertArrayHasKey($inactive['id'], $notCurrent);

    // Assert that "Extra" fields like is_current are not returned with select *
    $defaultGet = Relationship::get()->setLimit(1)->execute()->single();
    $this->assertArrayNotHasKey('is_current', $defaultGet);
    $starGet = Relationship::get()->addSelect('*')->setLimit(1)->execute()->single();
    $this->assertArrayNotHasKey('is_current', $starGet);
  }

  /**
   * Test UserJob checks expires.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCurrentUserJob(): void {
    $current = UserJob::create()->setValues([
      'expires_date' => '+ 1 week',
      'status_id:name' => 'draft',
      'job_type:name' => 'contact_import',
    ])->execute()->first();
    $indefinite = UserJob::create()->setValues([
      'status_id:name' => 'draft',
      'job_type:name' => 'contact_import',
    ])->execute()->first();
    $expired = UserJob::create()->setValues([
      'expires_date' => '-1 week',
      'status_id:name' => 'draft',
      'job_type:name' => 'contact_import',
    ])->execute()->first();

    $getCurrent = (array) UserJob::get()->addWhere('is_current', '=', TRUE)->execute()->indexBy('id');
    $notCurrent = (array) UserJob::get()->addWhere('is_current', '=', FALSE)->execute()->indexBy('id');
    $getAll = (array) UserJob::get()->addSelect('is_current')->execute()->indexBy('id');

    $this->assertTrue($getAll[$current['id']]['is_current']);
    $this->assertTrue($getAll[$indefinite['id']]['is_current']);
    $this->assertFalse($getAll[$expired['id']]['is_current']);

    $this->assertEquals([$current['id'], $indefinite['id']], array_keys($getCurrent));
    $this->assertEquals([$expired['id']], array_keys($notCurrent));
  }

  /**
   * Test saved search api checks expires_date.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCurrentSavedSearch(): void {
    $current = SavedSearch::create()->setValues([
      'expires_date' => '+ 1 week',
      'name' => 'current',
    ])->execute()->first();
    $indefinite = SavedSearch::create()->setValues([
      'name' => 'never expires',
    ])->execute()->first();
    $expired = SavedSearch::create()->setValues([
      'expires_date' => '-1 week',
      'name' => 'expired',
    ])->execute()->first();

    $getCurrent = (array) SavedSearch::get()->addWhere('is_current', '=', TRUE)->addWhere('has_base', '=', FALSE)->execute()->indexBy('id');
    $notCurrent = (array) SavedSearch::get()->addWhere('is_current', '=', FALSE)->addWhere('has_base', '=', FALSE)->execute()->indexBy('id');
    $getAll = (array) SavedSearch::get()->addSelect('is_current')->addWhere('has_base', '=', FALSE)->execute()->indexBy('id');

    $this->assertTrue($getAll[$current['id']]['is_current']);
    $this->assertTrue($getAll[$indefinite['id']]['is_current']);
    $this->assertFalse($getAll[$expired['id']]['is_current']);

    $this->assertEquals([$current['id'], $indefinite['id']], array_keys($getCurrent));
    $this->assertEquals([$expired['id']], array_keys($notCurrent));
  }

}

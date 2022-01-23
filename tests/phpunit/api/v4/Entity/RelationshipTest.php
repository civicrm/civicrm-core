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

use Civi\Api4\Contact;
use api\v4\UnitTestCase;
use Civi\Api4\Relationship;
use Civi\Api4\RelationshipCache;
use Civi\Test\TransactionalInterface;

/**
 * Assert that interchanging data between APIv3 and APIv4 yields consistent
 * encodings.
 *
 * @group headless
 */
class RelationshipTest extends UnitTestCase implements TransactionalInterface {

  public function testRelCacheCount() {
    $c1 = Contact::create(FALSE)->addValue('first_name', '1')->execute()->first()['id'];
    $c2 = Contact::create(FALSE)->addValue('first_name', '2')->execute()->first()['id'];
    Relationship::create(FALSE)
      ->setValues([
        'contact_id_a' => $c1,
        'contact_id_b' => $c2,
        'relationship_type_id' => 1,
      ])->execute();
    $cacheRecords = RelationshipCache::get(FALSE)
      ->addClause('OR', ['near_contact_id', '=', $c1], ['far_contact_id', '=', $c1])
      ->execute();
    $this->assertCount(2, $cacheRecords);
  }

  public function testRelCacheCalcFields() {
    $c1 = Contact::create(FALSE)->addValue('first_name', '1')->execute()->first()['id'];
    $c2 = Contact::create(FALSE)->addValue('first_name', '2')->execute()->first()['id'];
    $relationship = Relationship::create(FALSE)
      ->setValues([
        'contact_id_a' => $c1,
        'contact_id_b' => $c2,
        'relationship_type_id' => 1,
        'description' => "Wow, we're related!",
        'is_permission_a_b' => 1,
        'is_permission_b_a' => 2,
      ])->execute()->first();
    $relationship = Relationship::get(FALSE)
      ->addWhere('id', '=', $relationship['id'])
      ->execute()->first();
    $cacheRecords = RelationshipCache::get(FALSE)
      ->addWhere('near_contact_id', 'IN', [$c1, $c2])
      ->addSelect('near_contact_id', 'orientation', 'description', 'relationship_created_date', 'relationship_modified_date', 'permission_near_to_far', 'permission_far_to_near')
      ->execute()->indexBy('near_contact_id');
    $this->assertCount(2, $cacheRecords);
    $this->assertEquals("Wow, we're related!", $cacheRecords[$c1]['description']);
    $this->assertEquals("Wow, we're related!", $cacheRecords[$c2]['description']);
    $this->assertEquals(1, $cacheRecords[$c1]['permission_near_to_far']);
    $this->assertEquals(2, $cacheRecords[$c2]['permission_near_to_far']);
    $this->assertEquals(2, $cacheRecords[$c1]['permission_far_to_near']);
    $this->assertEquals(1, $cacheRecords[$c2]['permission_far_to_near']);
    $this->assertEquals($relationship['created_date'], $cacheRecords[$c1]['relationship_created_date']);
    $this->assertEquals($relationship['modified_date'], $cacheRecords[$c2]['relationship_modified_date']);
  }

}

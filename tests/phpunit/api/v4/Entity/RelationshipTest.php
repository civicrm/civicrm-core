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

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Contact;
use api\v4\Api4TestBase;
use Civi\Api4\Relationship;
use Civi\Api4\RelationshipCache;
use Civi\Test\TransactionalInterface;
use DateInterval;
use DateTime;

/**
 * Assert that interchanging data between APIv3 and APIv4 yields consistent
 * encodings.
 *
 * @group headless
 */
class RelationshipTest extends Api4TestBase implements TransactionalInterface {

  use \Civi\Test\ACLPermissionTrait;

  /**
   * Test relationship cache tracks created relationships.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRelationshipCacheCount(): void {
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

  /**
   * @throws \CRM_Core_Exception
   */
  public function testRelationshipCacheCalcFields(): void {
    $c1 = Contact::create(FALSE)->addValue('first_name', '1')->execute()->first()['id'];
    $c2 = Contact::create(FALSE)->addValue('first_name', '2')->execute()->first()['id'];
    $relationship = Relationship::create(FALSE)
      ->setValues([
        'contact_id_a' => $c1,
        'contact_id_b' => $c2,
        'relationship_type_id' => 1,
        'description' => "Wow, we're related!",
        'is_permission_a_b' => 1,
        'is_permission_b_a:name' => 'View only',
      ])->execute()->first();
    $relationship = Relationship::get(FALSE)
      ->addWhere('id', '=', $relationship['id'])
      ->execute()->first();
    $cacheRecords = RelationshipCache::get(FALSE)
      ->addWhere('near_contact_id', 'IN', [$c1, $c2])
      ->addSelect('near_contact_id', 'orientation', 'description', 'relationship_created_date', 'relationship_modified_date', 'permission_near_to_far:name', 'permission_far_to_near:name')
      ->execute()->indexBy('near_contact_id');
    $this->assertCount(2, $cacheRecords);
    $this->assertEquals("Wow, we're related!", $cacheRecords[$c1]['description']);
    $this->assertEquals("Wow, we're related!", $cacheRecords[$c2]['description']);
    $this->assertEquals('View and update', $cacheRecords[$c1]['permission_near_to_far:name']);
    $this->assertEquals('View only', $cacheRecords[$c2]['permission_near_to_far:name']);
    $this->assertEquals('View only', $cacheRecords[$c1]['permission_far_to_near:name']);
    $this->assertEquals('View and update', $cacheRecords[$c2]['permission_far_to_near:name']);
    $this->assertEquals($relationship['created_date'], $cacheRecords[$c1]['relationship_created_date']);
    $this->assertEquals($relationship['modified_date'], $cacheRecords[$c2]['relationship_modified_date']);
  }

  /**
   * Test that a relationship can be created with the same values as a disabled relationship.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRelationshipDisableCreate(): void {
    $today = new DateTime('today');
    $future = new DateTime('today');
    $future->add(new DateInterval('P1Y'));

    $c1 = Contact::create(FALSE)->addValue('first_name', '1')->execute()->first()['id'];
    $c2 = Contact::create(FALSE)->addValue('first_name', '2')->execute()->first()['id'];
    $relationship = Relationship::create(FALSE)
      ->setValues([
        'contact_id_a' => $c1,
        'contact_id_b' => $c2,
        'start_date' => $today->format('Y-m-d'),
        'end_date' => $future->format('Y-m-d'),
        'relationship_type_id' => 1,
        'description' => "Wow, we're related!",
        'is_permission_a_b' => 1,
        'is_permission_b_a:name' => 'View only',
      ])->execute()->first();
    $relationship = Relationship::get(FALSE)
      ->addWhere('id', '=', $relationship['id'])
      ->execute()->first();
    Relationship::update(FALSE)
      ->addWhere('id', '=', $relationship['id'])
      ->addValue('is_active', FALSE)
      ->execute()->first();
    Relationship::create(FALSE)
      ->setValues([
        'contact_id_a' => $c1,
        'contact_id_b' => $c2,
        'start_date' => $today->format('Y-m-d'),
        'end_date' => $future->format('Y-m-d'),
        'relationship_type_id' => 1,
        'description' => "Wow, we're related!",
        'is_permission_a_b' => 1,
        'is_permission_b_a:name' => 'View only',
      ])->execute()->first();

    $cacheRecords = RelationshipCache::get(FALSE)
      ->addWhere('near_contact_id', 'IN', [$c1])
      ->addWhere('is_active', '=', FALSE)
      ->addSelect('near_contact_id', 'orientation', 'description', 'start_date', 'end_date', 'is_active')
      ->execute()->indexBy('near_contact_id');
    $this->assertCount(1, $cacheRecords);
    $this->assertEquals(FALSE, $cacheRecords[$c1]['is_active']);
    $this->assertEquals($today->format('Y-m-d'), $cacheRecords[$c1]['start_date']);
    $this->assertEquals($future->format('Y-m-d'), $cacheRecords[$c1]['end_date']);

    $cacheRecords = RelationshipCache::get(FALSE)
      ->addWhere('near_contact_id', 'IN', [$c1])
      ->addWhere('is_active', '=', TRUE)
      ->addSelect('near_contact_id', 'orientation', 'description', 'start_date', 'end_date', 'is_active')
      ->execute()->indexBy('near_contact_id');
    $this->assertCount(1, $cacheRecords);
    $cacheRecord = $cacheRecords->first();
    $this->assertEquals(TRUE, $cacheRecord['is_active']);
    $this->assertEquals($today->format('Y-m-d'), $cacheRecord['start_date']);
    $this->assertEquals($future->format('Y-m-d'), $cacheRecord['end_date']);
  }

  public function testRelationshipCheckAccess(): void {
    $cid = $this->saveTestRecords('Contact', ['records' => 4])->column('id');
    $this->allowedContacts = array_slice($cid, 1);
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    \CRM_Utils_Hook::singleton()->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);
    $check = Relationship::checkAccess()
      ->setAction('create')
      ->setValues([
        'contact_id_a' => $cid[0],
        'contact_id_b' => $cid[1],
        'relationship_type_id' => 1,
      ])
      ->execute()->first();
    $this->assertFalse($check['access']);

    try {
      Relationship::create()->setValues([
        'contact_id_a' => $cid[1],
        'contact_id_b' => $cid[0],
        'relationship_type_id' => 1,
      ])->execute();
      $this->fail();
    }
    catch (UnauthorizedException $e) {
      Relationship::create(FALSE)->setValues([
        'contact_id_a' => $cid[1],
        'contact_id_b' => $cid[0],
        'relationship_type_id' => 1,
      ])->execute();
    }
    Relationship::create()->setValues([
      'contact_id_a' => $cid[1],
      'contact_id_b' => $cid[2],
      'relationship_type_id' => 1,
    ])->execute();

    $this->assertCount(2, Relationship::get(FALSE)->addWhere('contact_id_a', '=', $cid[1])->execute());
    $this->assertCount(1, Relationship::get()->addWhere('contact_id_a', '=', $cid[1])->execute());

    Relationship::delete()
      ->addWhere('contact_id_a', '=', $cid[1])
      ->execute()->single();

  }

}

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
use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Test\TransactionalInterface;

/**
 * Test Address functionality
 *
 * @group headless
 */
class AddressTest extends Api4TestBase implements TransactionalInterface {

  use \Civi\Test\ACLPermissionTrait;

  public function setUp(): void {
    \Civi\Api4\Setting::revert()
      ->addSelect('geoProvider')
      ->execute();
    parent::setUp();
  }

  public function tearDown(): void {
    \CRM_Utils_Hook::singleton()->reset();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    parent::tearDown();
  }

  /**
   * Check that 2 addresses for the same contact can't both be primary
   */
  public function testPrimary(): void {
    $cid = Contact::create(FALSE)->addValue('first_name', uniqid())->execute()->single()['id'];

    $a1 = Address::create(FALSE)
      ->addValue('is_primary', TRUE)
      ->addValue('contact_id', $cid)
      ->addValue('location_type_id:name', 'Home')
      ->addValue('city', 'Somewhere')
      ->execute()->single();

    $a2 = Address::create(FALSE)
      ->addValue('is_primary', TRUE)
      ->addValue('contact_id', $cid)
      ->addValue('location_type_id:name', 'Work')
      ->addValue('city', 'Elsewhere')
      ->execute()->single();

    $addresses = Address::get(FALSE)
      ->addWhere('contact_id', '=', $cid)
      ->addOrderBy('id')
      ->execute();

    $this->assertFalse($addresses[0]['is_primary']);
    $this->assertTrue($addresses[1]['is_primary']);

    $contact = $this->getTestRecord('Contact', $cid, ['address_primary']);
    $this->assertEquals($a2['id'], $contact['address_primary']);
  }

  public function testSearchProximity(): void {
    $cid = $this->createTestRecord('Contact')['id'];
    $sampleData = [
      ['geo_code_1' => 20, 'geo_code_2' => 20],
      ['geo_code_1' => 21, 'geo_code_2' => 21],
      ['geo_code_1' => 19, 'geo_code_2' => 19],
      ['geo_code_1' => 15, 'geo_code_2' => 15],
      ['geo_code_1' => 8, 'geo_code_2' => 20],
    ];
    $addreses = $this->saveTestRecords('Address', [
      'records' => $sampleData,
      'defaults' => ['contact_id' => $cid],
    ])->column('id');

    $result = Address::get(FALSE)
      ->addWhere('contact_id', '=', $cid)
      ->addWhere('proximity', '<=', ['distance' => 600, 'geo_code_1' => 20, 'geo_code_2' => 20])
      ->execute()->column('id');

    $this->assertCount(3, $result);
    $this->assertContains($addreses[0], $result);
    $this->assertContains($addreses[1], $result);
    $this->assertContains($addreses[2], $result);
    $this->assertNotContains($addreses[3], $result);

    // Regression test for dev/core#6715: identical coordinates can round above 1 before ACOS().
    $exactResult = Address::get(FALSE)
      ->addWhere('contact_id', '=', $cid)
      ->addWhere('proximity', '<=', ['distance' => 0, 'geo_code_1' => 8, 'geo_code_2' => 20])
      ->execute()->column('id');

    $this->assertContains($addreses[4], $exactResult);
  }

  public function testMasterAddressUpdate(): void {
    $contact = $this->createTestRecord('Contact');
    $master = $this->createTestRecord('Address', [
      'contact_id' => $contact['id'],
      'street_address' => 'Somewhere 123',
    ]);
    $address = $this->createTestRecord('Address', [
      'master_id' => $master['id'],
      'contact_id' => $this->createTestRecord('Contact')['id'],
    ]);

    Address::update(FALSE)
      ->addValue('id', $master['id'])
      ->addValue('master_id', '')
      ->addValue('street_address', 'Somewhere 234')
      ->execute();

    // Child address should be updated
    $result = $this->getTestRecord('Address', $address['id']);
    // Should still retain master id
    $this->assertEquals($master['id'], $result['master_id']);
    $this->assertEquals('Somewhere 234', $result['street_address']);

    // Unlink child address
    Address::update(FALSE)
      ->addValue('id', $address['id'])
      ->addValue('master_id', NULL)
      ->execute();

    // Child address should be unlinked
    $result = $this->getTestRecord('Address', $address['id']);
    // Should still retain master id
    $this->assertNull($result['master_id']);
    $this->assertEquals('Somewhere 234', $result['street_address']);
  }

  public function testMasterAddressJoin(): void {
    $contact = $this->createTestRecord('Contact');
    $master = $this->createTestRecord('Address', [
      'contact_id' => $contact['id'],
    ]);
    $address = $this->createTestRecord('Address', [
      'master_id' => $master['id'],
      'contact_id' => $this->createTestRecord('Contact')['id'],
    ]);
    $result = Address::get(FALSE)
      ->addJoin('Contact AS master_contact', 'LEFT', ['master_id.contact_id', '=', 'master_contact.id'])
      ->addSelect('master_contact.id')
      // Ensure the query can handle the ambiguity of two joined entities with a `location_type_id` field
      ->addOrderBy('location_type_id:label', 'ASC')
      ->execute()->indexBy('id');
    $this->assertEquals($contact['id'], $result[$address['id']]['master_contact.id']);
  }

  /**
   * Tests Address::get() permissions with various combinations of
   * contact ACLs, contribution permissions, and event permissions.
   */
  public function testAddressGetPermissions(): void {
    $this->createLoggedInUser();

    $contact1 = $this->createTestEntity('Contact', [
      'first_name' => 'Alice',
      'last_name' => 'Test',
    ], 'contact1');
    $addressContact1 = $this->createTestEntity('Address', [
      'contact_id' => $contact1['id'],
      'street_address' => '100 Alice Way',
      'location_type_id' => 1,
    ], 'addr_contact1');

    $contact2 = $this->createTestEntity('Contact', [
      'first_name' => 'Bob',
      'last_name' => 'Test',
    ], 'contact2');
    $addressContact2 = $this->createTestEntity('Address', [
      'contact_id' => $contact2['id'],
      'street_address' => '200 Bob St',
      'location_type_id' => 1,
    ], 'addr_contact2');

    $addressContrib = $this->createTestEntity('Address', [
      'contact_id' => NULL,
      'street_address' => '300 Contrib Blvd',
      'location_type_id' => 1,
    ], 'addr_contrib');
    $this->createTestEntity('Contribution', [
      'contact_id' => $contact1['id'],
      'financial_type_id' => 1,
      'total_amount' => 100,
      'address_id' => $addressContrib['id'],
    ], 'contrib1');

    $addressEvent1 = $this->createTestEntity('Address', [
      'contact_id' => NULL,
      'street_address' => '401 Event Ave',
      'location_type_id' => 1,
    ], 'addr_event1');
    $addressEvent2 = $this->createTestEntity('Address', [
      'contact_id' => NULL,
      'street_address' => '402 Event Ave',
      'location_type_id' => 1,
    ], 'addr_event2');
    $locBlock = $this->createTestEntity('LocBlock', [
      'address_id' => $addressEvent1['id'],
      'address_2_id' => $addressEvent2['id'],
    ], 'loc_block1');
    $this->createTestEntity('Event', [
      'title' => 'Annual Gala',
      'event_type_id' => 1,
      'default_role_id' => 1,
      'start_date' => '2026-10-01',
      'loc_block_id' => $locBlock['id'],
    ], 'event1');

    $addressOrphan = $this->createTestEntity('Address', [
      'contact_id' => NULL,
      'street_address' => '500 Orphan St',
      'location_type_id' => 1,
    ], 'addr_orphan');

    $allTestAddressIds = [
      $addressContact1['id'],
      $addressContact2['id'],
      $addressContrib['id'],
      $addressEvent1['id'],
      $addressEvent2['id'],
      $addressOrphan['id'],
    ];

    $resetAclCache = function(): void {
      \CRM_Core_DAO::executeQuery('TRUNCATE civicrm_acl_contact_cache');
      unset(\Civi::$statics['CRM_Contact_BAO_Contact_Permission']);
      \CRM_ACL_BAO_Cache::resetCache();
    };

    // Scenario 1: User with no permissions gets 0 records
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    $resetAclCache();
    $results = Address::get()
      ->addWhere('id', 'IN', $allTestAddressIds)
      ->execute()
      ->column('id');
    $this->assertEmpty($results);

    // Scenario 2: 'view all contacts' gives access to contact addresses only
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
    ];
    $resetAclCache();
    $results = Address::get()
      ->addWhere('id', 'IN', $allTestAddressIds)
      ->execute()
      ->column('id');
    $this->assertEqualsCanonicalizing([$addressContact1['id'], $addressContact2['id']], $results);

    // Scenario 3: 'view all contacts' + 'access CiviContribute' also grants contribution billing address
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
      'access CiviContribute',
    ];
    $resetAclCache();
    $results = Address::get()
      ->addWhere('id', 'IN', $allTestAddressIds)
      ->execute()
      ->column('id');
    $this->assertEqualsCanonicalizing([$addressContact1['id'], $addressContact2['id'], $addressContrib['id']], $results);

    // Scenario 4: 'view event info' gives access to event location addresses only
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'view event info',
    ];
    $resetAclCache();
    $results = Address::get()
      ->addWhere('id', 'IN', $allTestAddressIds)
      ->execute()
      ->column('id');
    $this->assertEqualsCanonicalizing([$addressEvent1['id'], $addressEvent2['id']], $results);

    // Scenario 4b: 'access CiviEvent' also gives access to event location addresses
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'access CiviEvent',
    ];
    $resetAclCache();
    $results = Address::get()
      ->addWhere('id', 'IN', $allTestAddressIds)
      ->execute()
      ->column('id');
    $this->assertEqualsCanonicalizing([$addressEvent1['id'], $addressEvent2['id']], $results);

    // Scenario 5: Contact ACL for Contact 1 only + 'access CiviContribute'
    $this->allowedContacts = [$contact1['id']];
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'access CiviContribute',
    ];
    \CRM_Utils_Hook::singleton()->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);
    $resetAclCache();
    $results = Address::get()
      ->addWhere('id', 'IN', $allTestAddressIds)
      ->execute()
      ->column('id');
    $this->assertEqualsCanonicalizing([$addressContact1['id'], $addressContrib['id']], $results);
    \CRM_Utils_Hook::singleton()->reset();

    // Scenario 6: All permissions grant all valid addresses, but never the orphaned address
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
      'access CiviContribute',
      'access CiviEvent',
    ];
    $resetAclCache();
    $results = Address::get()
      ->addWhere('id', 'IN', $allTestAddressIds)
      ->execute()
      ->column('id');
    $this->assertEqualsCanonicalizing([
      $addressContact1['id'],
      $addressContact2['id'],
      $addressContrib['id'],
      $addressEvent1['id'],
      $addressEvent2['id'],
    ], $results);
    $this->assertNotContains($addressOrphan['id'], $results);

    // Scenario 7: Deleted contact's address is hidden unless 'access deleted contacts' is granted
    Contact::update(FALSE)
      ->addWhere('id', '=', $contact2['id'])
      ->addValue('is_deleted', TRUE)
      ->execute();

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
    ];
    $resetAclCache();
    $results = Address::get()
      ->addWhere('id', 'IN', $allTestAddressIds)
      ->execute()
      ->column('id');
    $this->assertEquals([$addressContact1['id']], $results);

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
      'access deleted contacts',
    ];
    $resetAclCache();
    $results = Address::get()
      ->addWhere('id', 'IN', $allTestAddressIds)
      ->execute()
      ->column('id');
    $this->assertEqualsCanonicalizing([$addressContact1['id'], $addressContact2['id']], $results);

    Contact::update(FALSE)
      ->addWhere('id', '=', $contact2['id'])
      ->addValue('is_deleted', FALSE)
      ->execute();

    // Scenario 8: Row count queries with permissions
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    $resetAclCache();
    $count = Address::get()
      ->addWhere('id', 'IN', $allTestAddressIds)
      ->selectRowCount()
      ->execute()
      ->countMatched();
    $this->assertSame(0, $count);

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
      'access CiviContribute',
    ];
    $resetAclCache();
    $count = Address::get()
      ->addWhere('id', 'IN', $allTestAddressIds)
      ->selectRowCount()
      ->execute()
      ->countMatched();
    $this->assertSame(3, $count);

    // Scenario 9: Joins with permissions
    $addressWithContact = Address::get()
      ->addSelect('id', 'contact_id.id')
      ->addWhere('id', '=', $addressContact1['id'])
      ->execute()
      ->first();
    $this->assertEquals($contact1['id'], $addressWithContact['contact_id.id']);

    // Scenario 10: checkPermissions = FALSE returns all addresses including orphaned
    $results = Address::get(FALSE)
      ->addWhere('id', 'IN', $allTestAddressIds)
      ->execute()
      ->column('id');
    $this->assertEqualsCanonicalizing($allTestAddressIds, $results);
  }

}

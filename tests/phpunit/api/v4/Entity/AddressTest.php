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

  public function setUp():void {
    \Civi\Api4\Setting::revert()
      ->addSelect('geoProvider')
      ->execute();
    parent::setUp();
  }

  /**
   * Check that 2 addresses for the same contact can't both be primary
   */
  public function testPrimary(): void {
    $cid = Contact::create(FALSE)->addValue('first_name', uniqid())->execute()->single()['id'];

    $a1 = Address::create(FALSE)
      ->addValue('is_primary', TRUE)
      ->addValue('contact_id', $cid)
      ->addValue('location_type_id', 1)
      ->addValue('city', 'Somewhere')
      ->execute();

    $a2 = Address::create(FALSE)
      ->addValue('is_primary', TRUE)
      ->addValue('contact_id', $cid)
      ->addValue('location_type_id', 2)
      ->addValue('city', 'Elsewhere')
      ->execute();

    $addresses = Address::get(FALSE)
      ->addWhere('contact_id', '=', $cid)
      ->addOrderBy('id')
      ->execute();

    $this->assertFalse($addresses[0]['is_primary']);
    $this->assertTrue($addresses[1]['is_primary']);
  }

  public function testSearchProximity(): void {
    $cid = $this->createTestRecord('Contact')['id'];
    $sampleData = [
      ['geo_code_1' => 20, 'geo_code_2' => 20],
      ['geo_code_1' => 21, 'geo_code_2' => 21],
      ['geo_code_1' => 19, 'geo_code_2' => 19],
      ['geo_code_1' => 15, 'geo_code_2' => 15],
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

}

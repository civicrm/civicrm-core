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

use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Api4\Contribution;
use Civi\Api4\Email;
use Civi\Api4\OptionValue;
use api\v4\Api4TestBase;
use Civi\Api4\Phone;

/**
 * @group headless
 */
class ContactJoinTest extends Api4TestBase {

  public function testContactJoin(): void {
    $contact = $this->createTestRecord('Contact', [
      'first_name' => uniqid(),
      'last_name' => uniqid(),
    ]);
    $entitiesToTest = ['Address', 'OpenID', 'IM', 'Website', 'Email', 'Phone'];

    foreach ($entitiesToTest as $entity) {
      $this->createTestRecord($entity, [
        'contact_id' => $contact['id'],
      ]);
      $results = civicrm_api4($entity, 'get', [
        'where' => [['contact_id', '=', $contact['id']]],
        'select' => ['contact_id.*_name', 'contact_id.id'],
      ]);
      foreach ($results as $result) {
        $this->assertEquals($contact['id'], $result['contact_id.id']);
        $this->assertEquals($contact['display_name'], $result['contact_id.display_name']);
      }
    }
  }

  public function testJoinToPCMWillReturnArray(): void {
    $contact = $this->createTestRecord('Contact', [
      'preferred_communication_method' => [1, 2, 3],
      'contact_type' => 'Individual',
      'first_name' => 'Test',
      'last_name' => 'PCM',
    ]);

    $fetchedContact = Contact::get()
      ->addWhere('id', '=', $contact['id'])
      ->addSelect('preferred_communication_method')
      ->execute()
      ->first();

    $this->assertCount(3, $fetchedContact["preferred_communication_method"]);
  }

  public function testJoinToPCMOptionValueWillShowLabel(): void {
    $options = OptionValue::get()
      ->addWhere('option_group_id:name', '=', 'preferred_communication_method')
      ->execute()
      ->getArrayCopy();

    $optionValues = array_column($options, 'value');
    $labels = array_column($options, 'label');

    $contact = $this->createTestRecord('Contact', [
      'preferred_communication_method' => $optionValues,
      'contact_type' => 'Individual',
      'first_name' => 'Test',
      'last_name' => 'PCM',
    ]);

    $contact2 = $this->createTestRecord('Contact', [
      'preferred_communication_method' => $optionValues,
      'contact_type' => 'Individual',
      'first_name' => 'Test',
      'last_name' => 'PCM2',
    ]);

    $contactIds = array_column([$contact, $contact2], 'id');

    $fetchedContact = Contact::get()
      ->addWhere('id', 'IN', $contactIds)
      ->addSelect('preferred_communication_method:label')
      ->execute()
      ->first();

    $this->assertEquals($labels, $fetchedContact['preferred_communication_method:label']);
  }

  public function testCreateWithPrimaryAndBilling(): void {
    $contact = $this->createTestRecord('Contact', [
      'email_primary.email' => 'a@test.com',
      'email_billing.email' => 'b@test.com',
      'address_billing.city' => 'Hello',
      'address_billing.state_province_id:abbr' => 'AK',
      'address_billing.country_id:abbr' => 'USA',
    ]);
    $addr = Address::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute();
    $this->assertCount(1, $addr);
    $this->assertEquals('Hello', $contact['address_billing.city']);
    $this->assertEquals(1001, $contact['address_billing.state_province_id']);
    $this->assertEquals(1228, $contact['address_billing.country_id']);
    $emails = Email::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute();
    $this->assertCount(2, $emails);
    $this->assertEquals('a@test.com', $contact['email_primary.email']);
    $this->assertEquals('b@test.com', $contact['email_billing.email']);
  }

  /**
   * This is the same as testCreateWithPrimaryAndBilling, but the ambiguous
   * state "AK" is resolved within a different country "NG".
   */
  public function testCreateWithPrimaryAndBilling_Nigeria(): void {
    $contact = $this->createTestRecord('Contact', [
      'email_primary.email' => 'a@test.com',
      'email_billing.email' => 'b@test.com',
      'address_billing.city' => 'Hello',
      'address_billing.state_province_id:abbr' => 'AK',
      'address_billing.country_id:abbr' => 'NG',
    ]);
    $addr = Address::get(FALSE)
      ->addSelect('country_id:label', 'state_province_id:label')
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute()
      ->single();
    $this->assertEquals('Hello', $contact['address_billing.city']);
    $this->assertEquals('Akwa Ibom', $addr['state_province_id:label']);
    $this->assertEquals('Nigeria', $addr['country_id:label']);
    $emails = Email::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute();
    $this->assertCount(2, $emails);
    $this->assertEquals('a@test.com', $contact['email_primary.email']);
    $this->assertEquals('b@test.com', $contact['email_billing.email']);
  }

  public function testUpdateDeletePrimaryAndBilling(): void {
    $contact = $this->createTestRecord('Contact', [
      'phone_primary.phone' => '12345',
      'phone_billing.phone' => '54321',
    ]);
    Contact::update(FALSE)
      ->addValue('id', $contact['id'])
      // Delete primary phone, update billing phone
      ->addValue('phone_primary.phone', NULL)
      ->addValue('phone_billing.phone', 99999)
      ->execute();
    $phone = Phone::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute()
      ->single();
    $this->assertEquals('99999', $phone['phone']);
    $this->assertTrue($phone['is_billing']);
    // Contact only has one phone now, so it should be auto-set to primary
    $this->assertTrue($phone['is_primary']);

    $get = Contact::get(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->addSelect('phone_primary.*')
      ->addSelect('phone_billing.*')
      ->execute()->single();
    $this->assertEquals('99999', $get['phone_primary.phone']);
    $this->assertEquals('99999', $get['phone_billing.phone']);
    $this->assertEquals($get['phone_primary.id'], $get['phone_billing.id']);
  }

  public function testJoinToEmailId(): void {
    $contact = $this->createTestRecord('Contact', [
      'email_primary.email' => 'a@test.com',
      'email_billing.email' => 'b@test.com',
    ]);
    $emails = Email::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->addOrderBy('is_primary', 'DESC')
      ->execute()->column('id');
    $contribution = $this->createTestRecord('Contribution', [
      'contact_id' => $contact['id'],
    ]);

    $result = Contribution::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->addSelect('contact_id.email_primary')
      ->execute()->single();
    $this->assertEquals($emails[0], $result['contact_id.email_primary']);

    $result = Contribution::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->addSelect('contact_id.email_primary.email')
      ->addSelect('contact_id.email_billing')
      ->execute()->single();
    $this->assertEquals('a@test.com', $result['contact_id.email_primary.email']);
    $this->assertEquals($emails[1], $result['contact_id.email_billing']);
  }

}

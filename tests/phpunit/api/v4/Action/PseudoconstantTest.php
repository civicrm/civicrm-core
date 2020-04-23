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
 *
 */


namespace api\v4\Action;

use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Api4\Activity;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\Email;
use Civi\Api4\OptionValue;

/**
 * @group headless
 */
class PseudoconstantTest extends BaseCustomValueTest {

  public function testOptionValue() {
    $cid = Contact::create()->setCheckPermissions(FALSE)->addValue('first_name', 'bill')->execute()->first()['id'];
    $subject = uniqid('subject');
    OptionValue::create()
      ->addValue('option_group_id:name', 'activity_type')
      ->addValue('label', 'Fake')
      ->execute();

    Activity::create()
      ->addValue('activity_type_id:name', 'Fake')
      ->addValue('source_contact_id', $cid)
      ->addValue('subject', $subject)
      ->execute();

    $act = Activity::get()
      ->addWhere('activity_type_id:name', '=', 'Fake')
      ->addWhere('subject', '=', $subject)
      ->addSelect('activity_type_id:label')
      ->addSelect('activity_type_id')
      ->execute();

    $this->assertCount(1, $act);
    $this->assertEquals('Fake', $act[0]['activity_type_id:label']);
    $this->assertTrue(is_numeric($act[0]['activity_type_id']));
  }

  public function testAddressOptions() {
    $cid = Contact::create()->setCheckPermissions(FALSE)->addValue('first_name', 'addr')->execute()->first()['id'];
    Address::save()
      ->addRecord([
        'contact_id' => $cid,
        'state_province_id:abbr' => 'CA',
        'country_id:label' => 'United States',
        'street_address' => '1',
      ])
      ->addRecord([
        'contact_id' => $cid,
        'state_province_id:abbr' => 'CA',
        'country_id:label' => 'Uruguay',
        'street_address' => '2',
      ])
      ->addRecord([
        'contact_id' => $cid,
        'state_province_id:abbr' => 'CA',
        'country_id:abbr' => 'ES',
        'street_address' => '3',
      ])
      ->execute();

    $addr = Address::get()
      ->addWhere('contact_id', '=', $cid)
      ->addSelect('state_province_id:abbr', 'state_province_id:name', 'country_id:label', 'country_id:name')
      ->addOrderBy('street_address')
      ->execute();

    $this->assertCount(3, $addr);

    // US - California
    $this->assertEquals('CA', $addr[0]['state_province_id:abbr']);
    $this->assertEquals('California', $addr[0]['state_province_id:name']);
    $this->assertEquals('US', $addr[0]['country_id:name']);
    $this->assertEquals('United States', $addr[0]['country_id:label']);
    // Uruguay - Canelones
    $this->assertEquals('CA', $addr[1]['state_province_id:abbr']);
    $this->assertEquals('Canelones', $addr[1]['state_province_id:name']);
    $this->assertEquals('UY', $addr[1]['country_id:name']);
    $this->assertEquals('Uruguay', $addr[1]['country_id:label']);
    // Spain - Cádiz
    $this->assertEquals('CA', $addr[2]['state_province_id:abbr']);
    $this->assertEquals('Cádiz', $addr[2]['state_province_id:name']);
    $this->assertEquals('ES', $addr[2]['country_id:name']);
    $this->assertEquals('Spain', $addr[2]['country_id:label']);
  }

  public function testCustomOptions() {
    CustomGroup::create()
      ->setCheckPermissions(FALSE)
      ->addValue('name', 'myPseudoconstantTest')
      ->addValue('extends', 'Individual')
      ->addChain('field', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('option_values', ['r' => 'red', 'g' => 'green', 'b' => 'blue'])
        ->addValue('label', 'Color')
        ->addValue('html_type', 'Select')
      )->execute();

    $cid = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'col')
      ->addValue('myPseudoconstantTest.Color:label', 'blue')
      ->execute()->first()['id'];

    $result = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $cid)
      ->addSelect('myPseudoconstantTest.Color:label', 'myPseudoconstantTest.Color')
      ->execute()->first();

    $this->assertEquals('blue', $result['myPseudoconstantTest.Color:label']);
    $this->assertEquals('b', $result['myPseudoconstantTest.Color']);
  }

  public function testJoinOptions() {
    $cid1 = Contact::create()->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Tom')
      ->addValue('gender_id:label', 'Male')
      ->addChain('email', Email::create()->setValues(['contact_id' => '$id', 'email' => 'tom@example.com', 'location_type_id:name' => 'Work']))
      ->execute()->first()['id'];
    $cid2 = Contact::create()->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Sue')
      ->addValue('gender_id:name', 'Female')
      ->addChain('email', Email::create()->setValues(['contact_id' => '$id', 'email' => 'sue@example.com', 'location_type_id:name' => 'Home']))
      ->execute()->first()['id'];
    $cid3 = Contact::create()->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Pat')
      ->addChain('email', Email::create()->setValues(['contact_id' => '$id', 'email' => 'pat@example.com', 'location_type_id:name' => 'Home']))
      ->execute()->first()['id'];

    $emails = Email::get()
      ->addSelect('location_type_id:name', 'contact.gender_id:label', 'email', 'contact_id')
      ->addWhere('contact_id', 'IN', [$cid1, $cid2, $cid3])
      ->addWhere('contact.gender_id:label', 'IN', ['Male', 'Female'])
      ->execute()->indexBy('contact_id');
    $this->assertCount(2, $emails);
    $this->assertEquals('Work', $emails[$cid1]['location_type_id:name']);
    $this->assertEquals('Home', $emails[$cid2]['location_type_id:name']);
    $this->assertEquals('Male', $emails[$cid1]['contact.gender_id:label']);
    $this->assertEquals('Female', $emails[$cid2]['contact.gender_id:label']);

    $emails = Email::get()
      ->addSelect('location_type_id:name', 'contact.gender_id:label', 'email', 'contact_id')
      ->addWhere('contact_id', 'IN', [$cid1, $cid2, $cid3])
      ->addWhere('location_type_id:name', 'IN', ['Home'])
      ->execute()->indexBy('contact_id');
    $this->assertCount(2, $emails);
    $this->assertEquals('Home', $emails[$cid2]['location_type_id:name']);
    $this->assertEquals('Home', $emails[$cid3]['location_type_id:name']);
    $this->assertEquals('Female', $emails[$cid2]['contact.gender_id:label']);
    $this->assertNull($emails[$cid3]['contact.gender_id:label']);
  }

}

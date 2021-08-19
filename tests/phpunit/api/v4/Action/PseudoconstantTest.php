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

use Civi\Api4\Address;
use Civi\Api4\Campaign;
use Civi\Api4\Contact;
use Civi\Api4\Activity;
use Civi\Api4\Contribution;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\Email;
use Civi\Api4\EntityTag;
use Civi\Api4\OptionValue;
use Civi\Api4\Participant;
use Civi\Api4\Tag;

/**
 * @group headless
 */
class PseudoconstantTest extends BaseCustomValueTest {

  public function testOptionValue() {
    $cid = Contact::create(FALSE)->addValue('first_name', 'bill')->execute()->first()['id'];
    $subject = uniqid('subject');
    OptionValue::create()
      ->addValue('option_group_id:name', 'activity_type')
      ->addValue('label', 'Fake Type')
      ->execute();

    $options = Activity::getFields()
      ->addWhere('name', '=', 'activity_type_id')
      ->setLoadOptions(['id', 'name', 'label'])
      ->execute()->first()['options'];
    $options = array_column($options, NULL, 'name');
    $this->assertEquals('Fake Type', $options['Fake_Type']['label']);

    Activity::create()
      ->addValue('activity_type_id:name', 'Meeting')
      ->addValue('source_contact_id', $cid)
      ->addValue('subject', $subject)
      ->execute();

    Activity::create()
      ->addValue('activity_type_id:name', 'Fake_Type')
      ->addValue('source_contact_id', $cid)
      ->addValue('subject', $subject)
      ->execute();

    $act = Activity::get()
      ->addWhere('activity_type_id:label', '=', 'Fake Type')
      ->addWhere('subject', '=', $subject)
      ->addSelect('activity_type_id:name')
      ->addSelect('activity_type_id:label')
      ->addSelect('activity_type_id')
      ->execute();

    $this->assertCount(1, $act);
    $this->assertEquals('Fake Type', $act[0]['activity_type_id:label']);
    $this->assertEquals('Fake_Type', $act[0]['activity_type_id:name']);
    $this->assertTrue(is_numeric($act[0]['activity_type_id']));

    $act = Activity::get()
      ->addHaving('activity_type_id:name', '=', 'Fake_Type')
      ->addHaving('subject', '=', $subject)
      ->addSelect('activity_type_id:label')
      ->addSelect('activity_type_id')
      ->addSelect('subject')
      ->execute();

    $this->assertCount(1, $act);
    $this->assertEquals('Fake Type', $act[0]['activity_type_id:label']);
    $this->assertTrue(is_numeric($act[0]['activity_type_id']));

    $act = Activity::get()
      ->addHaving('activity_type_id:name', '=', 'Fake_Type')
      ->addHaving('subject', '=', $subject)
      ->addSelect('activity_type_id')
      ->addSelect('subject')
      ->execute();

    $this->assertCount(1, $act);
    $this->assertTrue(is_numeric($act[0]['activity_type_id']));
  }

  public function testAddressOptions() {
    $cid = Contact::create(FALSE)->addValue('first_name', 'addr')->execute()->first()['id'];
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
    $technicolor = [
      ['id' => 'r', 'name' => 'red', 'label' => 'RED', 'color' => '#ff0000', 'description' => 'Red color', 'icon' => 'fa-red'],
      ['id' => 'g', 'name' => 'green', 'label' => 'GREEN', 'color' => '#00ff00', 'description' => 'Green color', 'icon' => 'fa-green'],
      ['id' => 'b', 'name' => 'blue', 'label' => 'BLUE', 'color' => '#0000ff', 'description' => 'Blue color', 'icon' => 'fa-blue'],
    ];

    CustomGroup::create(FALSE)
      ->addValue('name', 'myPseudoconstantTest')
      ->addValue('extends', 'Individual')
      ->addChain('field1', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('option_values', ['r' => 'red', 'g' => 'green', 'b' => 'blü'])
        ->addValue('label', 'Color')
        ->addValue('html_type', 'Select')
      )->addChain('field2', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('option_values', $technicolor)
        ->addValue('label', 'Technicolor')
        ->addValue('html_type', 'CheckBox')
      )->execute();

    $fields = Contact::getFields()
      ->setLoadOptions(array_keys($technicolor[0]))
      ->execute()
      ->indexBy('name');

    foreach ($technicolor as $index => $option) {
      foreach ($option as $prop => $val) {
        $this->assertEquals($val, $fields['myPseudoconstantTest.Technicolor']['options'][$index][$prop]);
      }
    }

    $cid = Contact::create(FALSE)
      ->addValue('first_name', 'col')
      ->addValue('myPseudoconstantTest.Color:label', 'blü')
      ->execute()->first()['id'];

    $result = Contact::get(FALSE)
      ->addWhere('id', '=', $cid)
      ->addSelect('myPseudoconstantTest.Color:name', 'myPseudoconstantTest.Color:label', 'myPseudoconstantTest.Color')
      ->execute()->first();

    $this->assertEquals('blü', $result['myPseudoconstantTest.Color:label']);
    $this->assertEquals('bl_', $result['myPseudoconstantTest.Color:name']);
    $this->assertEquals('b', $result['myPseudoconstantTest.Color']);

    $cid1 = Contact::create(FALSE)
      ->addValue('first_name', 'two')
      ->addValue('myPseudoconstantTest.Technicolor:label', 'RED')
      ->execute()->first()['id'];
    $cid2 = Contact::create(FALSE)
      ->addValue('first_name', 'two')
      ->addValue('myPseudoconstantTest.Technicolor:label', 'GREEN')
      ->execute()->first()['id'];

    // Test ordering by label
    $result = Contact::get(FALSE)
      ->addWhere('id', 'IN', [$cid1, $cid2])
      ->addSelect('id')
      ->addOrderBy('myPseudoconstantTest.Technicolor:label')
      ->execute()->first()['id'];
    $this->assertEquals($cid2, $result);
    $result = Contact::get(FALSE)
      ->addWhere('id', 'IN', [$cid1, $cid2])
      ->addSelect('id')
      ->addOrderBy('myPseudoconstantTest.Technicolor:label', 'DESC')
      ->execute()->first()['id'];
    $this->assertEquals($cid1, $result);
  }

  public function testJoinOptions() {
    $cid1 = Contact::create(FALSE)
      ->addValue('first_name', 'Tom')
      ->addValue('gender_id:label', 'Male')
      ->addChain('email', Email::create()->setValues(['contact_id' => '$id', 'email' => 'tom@example.com', 'location_type_id:name' => 'Work']))
      ->execute()->first()['id'];
    $cid2 = Contact::create(FALSE)
      ->addValue('first_name', 'Sue')
      ->addValue('gender_id:name', 'Female')
      ->addChain('email', Email::create()->setValues(['contact_id' => '$id', 'email' => 'sue@example.com', 'location_type_id:name' => 'Home']))
      ->execute()->first()['id'];
    $cid3 = Contact::create(FALSE)
      ->addValue('first_name', 'Pat')
      ->addChain('email', Email::create()->setValues(['contact_id' => '$id', 'email' => 'pat@example.com', 'location_type_id:name' => 'Home']))
      ->execute()->first()['id'];

    $emails = Email::get()
      ->addSelect('location_type_id:name', 'contact_id.gender_id:label', 'email', 'contact_id')
      ->addWhere('contact_id', 'IN', [$cid1, $cid2, $cid3])
      ->addWhere('contact_id.gender_id:label', 'IN', ['Male', 'Female'])
      ->execute()->indexBy('contact_id');
    $this->assertCount(2, $emails);
    $this->assertEquals('Work', $emails[$cid1]['location_type_id:name']);
    $this->assertEquals('Home', $emails[$cid2]['location_type_id:name']);
    $this->assertEquals('Male', $emails[$cid1]['contact_id.gender_id:label']);
    $this->assertEquals('Female', $emails[$cid2]['contact_id.gender_id:label']);

    $emails = Email::get()
      ->addSelect('location_type_id:name', 'contact_id.gender_id:label', 'email', 'contact_id')
      ->addWhere('contact_id', 'IN', [$cid1, $cid2, $cid3])
      ->addWhere('location_type_id:name', 'IN', ['Home'])
      ->execute()->indexBy('contact_id');
    $this->assertCount(2, $emails);
    $this->assertEquals('Home', $emails[$cid2]['location_type_id:name']);
    $this->assertEquals('Home', $emails[$cid3]['location_type_id:name']);
    $this->assertEquals('Female', $emails[$cid2]['contact_id.gender_id:label']);
    $this->assertNull($emails[$cid3]['contact_id.gender_id:label']);
  }

  public function testTagOptions() {
    $tag = uniqid('tag');
    Tag::create(FALSE)
      ->addValue('name', $tag)
      ->addValue('description', 'colorful')
      ->addValue('color', '#aabbcc')
      ->execute();
    $options = EntityTag::getFields()
      ->setLoadOptions(['id', 'name', 'color', 'description', 'label'])
      ->addWhere('name', '=', 'tag_id')
      ->execute()->first()['options'];
    $options = array_column($options, NULL, 'name');
    $this->assertEquals('colorful', $options[$tag]['description']);
    $this->assertEquals('#aabbcc', $options[$tag]['color']);
    $this->assertEquals($tag, $options[$tag]['label']);
  }

  public function testParticipantRole() {
    $event = $this->createEntity(['type' => 'Event']);
    $contact = $this->createEntity(['type' => 'Individual']);
    $participant = Participant::create()
      ->addValue('contact_id', $contact['id'])
      ->addValue('event_id', $event['id'])
      ->addValue('role_id:label', ['Attendee', 'Volunteer'])
      ->execute()->first();

    $search1 = Participant::get()
      ->addSelect('role_id', 'role_id:label')
      ->addWhere('role_id:label', 'CONTAINS', 'Volunteer')
      ->addOrderBy('id')
      ->execute()->last();

    $this->assertEquals(['Attendee', 'Volunteer'], $search1['role_id:label']);
    $this->assertEquals(['1', '2'], $search1['role_id']);

    $search2 = Participant::get()
      ->addWhere('role_id:label', 'CONTAINS', 'Host')
      ->execute()->indexBy('id');

    $this->assertArrayNotHasKey($participant['id'], (array) $search2);
  }

  public function testPreloadFalse() {
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviContribute');
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');

    $contact = $this->createEntity(['type' => 'Individual']);

    $campaignTitle = uniqid('Test ');

    $campaignId = Campaign::create(FALSE)
      ->addValue('title', $campaignTitle)
      ->addValue('campaign_type_id', 1)
      ->execute()->first()['id'];

    $contributionId = Contribution::create(FALSE)
      ->addValue('campaign_id', $campaignId)
      ->addValue('contact_id', $contact['id'])
      ->addValue('financial_type_id', 1)
      ->addValue('total_amount', .01)
      ->execute()->first()['id'];

    // Even though the option list of campaigns is not available (prefetch = false)
    // We should still be able to get the title of the campaign as :label
    $result = Contribution::get(FALSE)
      ->addWhere('id', '=', $contributionId)
      ->addSelect('campaign_id:label')
      ->execute()->single();

    $this->assertEquals($campaignTitle, $result['campaign_id:label']);

    // Fetching the title via join ought to work too
    $result = Contribution::get(FALSE)
      ->addWhere('id', '=', $contributionId)
      ->addSelect('campaign_id.title')
      ->execute()->single();

    $this->assertEquals($campaignTitle, $result['campaign_id.title']);
  }

}

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

use api\v4\UnitTestCase;
use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Api4\Email;
use Civi\Api4\EntityTag;
use Civi\Api4\Phone;
use Civi\Api4\Tag;

/**
 * @group headless
 */
class FkJoinTest extends UnitTestCase {

  public function setUpHeadless() {
    $relatedTables = [
      'civicrm_activity',
      'civicrm_phone',
      'civicrm_activity_contact',
    ];
    $this->cleanup(['tablesToTruncate' => $relatedTables]);
    $this->loadDataSet('DefaultDataSet');

    return parent::setUpHeadless();
  }

  /**
   * Fetch all phone call activities. Expects a single activity
   * loaded from the data set.
   */
  public function testThreeLevelJoin() {
    $results = Activity::get(FALSE)
      ->addWhere('activity_type_id:name', '=', 'Phone Call')
      ->execute();

    $this->assertCount(1, $results);
  }

  public function testOptionalJoin() {
    // DefaultDataSet includes 2 phones for contact 1, 0 for contact 2.
    // We'll add one for contact 2 as a red herring to make sure we only get back the correct ones.
    Phone::create(FALSE)
      ->setValues(['contact_id' => $this->getReference('test_contact_2')['id'], 'phone' => '123456'])
      ->execute();
    $contacts = Contact::get(FALSE)
      ->addJoin('Phone', FALSE)
      ->addSelect('id', 'phone.phone')
      ->addWhere('id', 'IN', [$this->getReference('test_contact_1')['id']])
      ->addOrderBy('phone.id')
      ->execute();
    $this->assertCount(2, $contacts);
    $this->assertEquals($this->getReference('test_contact_1')['id'], $contacts[0]['id']);
    $this->assertEquals($this->getReference('test_contact_1')['id'], $contacts[1]['id']);
  }

  public function testRequiredJoin() {
    // Joining with no condition
    $contacts = Contact::get(FALSE)
      ->addSelect('id', 'phone.phone')
      ->addJoin('Phone', TRUE)
      ->addWhere('id', 'IN', [$this->getReference('test_contact_1')['id'], $this->getReference('test_contact_2')['id']])
      ->addOrderBy('phone.id')
      ->execute();
    $this->assertCount(2, $contacts);
    $this->assertEquals($this->getReference('test_contact_1')['id'], $contacts[0]['id']);
    $this->assertEquals($this->getReference('test_contact_1')['id'], $contacts[1]['id']);

    // Add is_primary condition, should result in only one record
    $contacts = Contact::get(FALSE)
      ->addSelect('id', 'phone.phone', 'phone.location_type_id')
      ->addJoin('Phone', TRUE, ['phone.is_primary', '=', TRUE])
      ->addWhere('id', 'IN', [$this->getReference('test_contact_1')['id'], $this->getReference('test_contact_2')['id']])
      ->addOrderBy('phone.id')
      ->execute();
    $this->assertCount(1, $contacts);
    $this->assertEquals($this->getReference('test_contact_1')['id'], $contacts[0]['id']);
    $this->assertEquals('+35355439483', $contacts[0]['phone.phone']);
    $this->assertEquals('1', $contacts[0]['phone.location_type_id']);
  }

  public function testJoinToTheSameTableTwice() {
    $cid1 = Contact::create(FALSE)
      ->addValue('first_name', 'Aaa')
      ->addChain('email1', Email::create()->setValues(['email' => 'yoohoo@yahoo.test', 'contact_id' => '$id', 'location_type_id:name' => 'Home']))
      ->addChain('email2', Email::create()->setValues(['email' => 'yahoo@yoohoo.test', 'contact_id' => '$id', 'location_type_id:name' => 'Work']))
      ->execute()
      ->first()['id'];

    $cid2 = Contact::create(FALSE)
      ->addValue('first_name', 'Bbb')
      ->addChain('email1', Email::create()->setValues(['email' => '1@test.test', 'contact_id' => '$id', 'location_type_id:name' => 'Home']))
      ->addChain('email2', Email::create()->setValues(['email' => '2@test.test', 'contact_id' => '$id', 'location_type_id:name' => 'Work']))
      ->addChain('email3', Email::create()->setValues(['email' => '3@test.test', 'contact_id' => '$id', 'location_type_id:name' => 'Other']))
      ->execute()
      ->first()['id'];

    $cid3 = Contact::create(FALSE)
      ->addValue('first_name', 'Ccc')
      ->execute()
      ->first()['id'];

    $contacts = Contact::get(FALSE)
      ->addSelect('id', 'first_name', 'any_email.email', 'any_email.location_type_id:name', 'any_email.is_primary', 'primary_email.email')
      ->setJoin([
        ['Email AS any_email', TRUE, NULL],
        ['Email AS primary_email', FALSE, ['primary_email.is_primary', '=', TRUE]],
      ])
      ->addWhere('id', 'IN', [$cid1, $cid2, $cid3])
      ->addOrderBy('any_email.id')
      ->setDebug(TRUE)
      ->execute();
    $this->assertCount(5, $contacts);
    $this->assertEquals('Home', $contacts[0]['any_email.location_type_id:name']);
    $this->assertEquals('yoohoo@yahoo.test', $contacts[1]['primary_email.email']);
    $this->assertEquals('1@test.test', $contacts[2]['primary_email.email']);
    $this->assertEquals('1@test.test', $contacts[3]['primary_email.email']);
    $this->assertEquals('1@test.test', $contacts[4]['primary_email.email']);
  }

  public function testBridgeJoinTags() {
    $tag1 = Tag::create()->setCheckPermissions(FALSE)
      ->addValue('name', uniqid('join1'))
      ->execute()
      ->first()['name'];
    $tag2 = Tag::create()->setCheckPermissions(FALSE)
      ->addValue('name', uniqid('join2'))
      ->execute()
      ->first()['name'];
    $tag3 = Tag::create()->setCheckPermissions(FALSE)
      ->addValue('name', uniqid('join3'))
      ->execute()
      ->first()['name'];

    $cid1 = Contact::create()->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Aaa')
      ->addChain('tag1', EntityTag::create()->setValues(['entity_id' => '$id', 'tag_id:name' => $tag1]))
      ->addChain('tag2', EntityTag::create()->setValues(['entity_id' => '$id', 'tag_id:name' => $tag2]))
      ->execute()
      ->first()['id'];
    $cid2 = Contact::create()->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Bbb')
      ->addChain('tag1', EntityTag::create()->setValues(['entity_id' => '$id', 'tag_id:name' => $tag1]))
      ->addChain('tag3', EntityTag::create()->setValues(['entity_id' => '$id', 'tag_id:name' => $tag3]))
      ->execute()
      ->first()['id'];
    $cid3 = Contact::create()->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Ccc')
      ->execute()
      ->first()['id'];

    $required = Contact::get()->setCheckPermissions(FALSE)
      ->addJoin('Tag', TRUE, 'EntityTag')
      ->addSelect('first_name', 'tag.name')
      ->addWhere('id', 'IN', [$cid1, $cid2, $cid3])
      ->execute();
    $this->assertCount(4, $required);

    $optional = Contact::get()->setCheckPermissions(FALSE)
      ->addJoin('Tag', FALSE, 'EntityTag', ['tag.name', 'IN', [$tag1, $tag2, $tag3]])
      ->addSelect('first_name', 'tag.name')
      ->addWhere('id', 'IN', [$cid1, $cid2, $cid3])
      ->execute();
    $this->assertCount(5, $optional);

    $grouped = Contact::get()->setCheckPermissions(FALSE)
      ->addJoin('Tag', FALSE, 'EntityTag', ['tag.name', 'IN', [$tag1, $tag3]])
      ->addSelect('first_name', 'COUNT(tag.name) AS tags')
      ->addWhere('id', 'IN', [$cid1, $cid2, $cid3])
      ->addGroupBy('id')
      ->execute()->indexBy('id');
    $this->assertEquals(1, (int) $grouped[$cid1]['tags']);
    $this->assertEquals(2, (int) $grouped[$cid2]['tags']);
    $this->assertEquals(0, (int) $grouped[$cid3]['tags']);

    $reverse = Tag::get()->setCheckPermissions(FALSE)
      ->addJoin('Contact', FALSE, 'EntityTag', ['contact.id', 'IN', [$cid1, $cid2, $cid3]])
      ->addGroupBy('id')
      ->addSelect('name', 'COUNT(contact.id) AS contacts')
      ->execute()->indexBy('name');
    $this->assertEquals(2, (int) $reverse[$tag1]['contacts']);
    $this->assertEquals(1, (int) $reverse[$tag2]['contacts']);
    $this->assertEquals(1, (int) $reverse[$tag3]['contacts']);
  }

}

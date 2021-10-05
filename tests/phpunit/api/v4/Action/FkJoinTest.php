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
use Civi\Api4\Relationship;
use Civi\Api4\Tag;

/**
 * @group headless
 */
class FkJoinTest extends UnitTestCase {

  public function setUpHeadless() {
    $this->loadDataSet('DefaultDataSet');

    return parent::setUpHeadless();
  }

  public function tearDown(): void {
    $relatedTables = [
      'civicrm_activity',
      'civicrm_phone',
      'civicrm_activity_contact',
    ];
    $this->cleanup(['tablesToTruncate' => $relatedTables]);
    parent::tearDown();
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

  public function testImplicitJoinOnExplicitJoin() {
    $contacts = Contact::get(FALSE)
      ->addWhere('id', '=', $this->getReference('test_contact_1')['id'])
      ->addJoin('Address AS address', TRUE, ['id', '=', 'address.contact_id'], ['address.location_type_id', '=', 1])
      ->addSelect('id', 'address.country_id.iso_code')
      ->execute();
    $this->assertCount(1, $contacts);
    $this->assertEquals('US', $contacts[0]['address.country_id.iso_code']);
  }

  public function testExcludeJoin() {
    $contacts = Contact::get(FALSE)
      ->addJoin('Address AS address', 'EXCLUDE', ['id', '=', 'address.contact_id'], ['address.location_type_id', '=', 1])
      ->addSelect('id')
      ->execute()->column('id');
    $this->assertNotContains($this->getReference('test_contact_1')['id'], $contacts);
  }

  public function testInvalidJoinAlias() {
    // Not allowed to use same alias as the base table
    try {
      Contact::get(FALSE)->addJoin('Address AS a')->execute();
    }
    catch (\API_Exception $e) {
      $message = $e->getMessage();
    }
    $this->assertEquals('Illegal join alias: "a"', $message);

    // Not allowed to use dots in the alias
    try {
      Contact::get(FALSE)->addJoin('Address AS add.ress')->execute();
    }
    catch (\API_Exception $e) {
      $message = $e->getMessage();
    }
    $this->assertEquals('Illegal join alias: "add.ress"', $message);

    // Not allowed to use an alias > 256 characters
    try {
      $longAlias = str_repeat('z', 257);
      Contact::get(FALSE)->addJoin("Address AS $longAlias")->execute();
    }
    catch (\API_Exception $e) {
      $message = $e->getMessage();
    }
    $this->assertEquals("Illegal join alias: \"$longAlias\"", $message);

    // Alpha-numeric with dashes 256 characters long - weird but allowed
    $okAlias = str_repeat('-0_a-9Z_', 32);
    Contact::get(FALSE)->addJoin("Address AS $okAlias")->execute();
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
        ['Email AS any_email', 'INNER', NULL],
        ['Email AS primary_email', 'LEFT', ['primary_email.is_primary', '=', TRUE]],
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
    $tag1 = Tag::create(FALSE)
      ->addValue('name', uniqid('join1'))
      ->execute()
      ->first()['name'];
    $tag2 = Tag::create(FALSE)
      ->addValue('name', uniqid('join2'))
      ->execute()
      ->first()['name'];
    $tag3 = Tag::create(FALSE)
      ->addValue('name', uniqid('join3'))
      ->execute()
      ->first()['name'];
    // Create using pseudoconstant syntax (:name)
    $cid1 = Contact::create(FALSE)
      ->addValue('first_name', 'Aaa')
      ->addChain('tag1', EntityTag::create()->setValues(['entity_id' => '$id', 'tag_id:name' => $tag1]))
      ->addChain('tag2', EntityTag::create()->setValues(['entity_id' => '$id', 'tag_id:name' => $tag2]))
      ->execute()
      ->first()['id'];
    // Create using fk syntax (.name)
    $cid2 = Contact::create(FALSE)
      ->addValue('first_name', 'Bbb')
      ->addChain('tag1', EntityTag::create()->setValues(['entity_id' => '$id', 'tag_id.name' => $tag1]))
      ->addChain('tag3', EntityTag::create()->setValues(['entity_id' => '$id', 'tag_id.name' => $tag3]))
      ->execute()
      ->first()['id'];
    $cid3 = Contact::create(FALSE)
      ->addValue('first_name', 'Ccc')
      ->execute()
      ->first()['id'];

    $required = Contact::get(FALSE)
      ->addJoin('Tag', TRUE, 'EntityTag')
      ->addSelect('first_name', 'tag.name')
      ->addWhere('id', 'IN', [$cid1, $cid2, $cid3])
      ->execute();
    $this->assertCount(4, $required);

    $optional = Contact::get(FALSE)
      ->addJoin('Tag', FALSE, 'EntityTag', ['tag.name', 'IN', [$tag1, $tag2, $tag3]])
      ->addSelect('first_name', 'tag.name')
      ->addWhere('id', 'IN', [$cid1, $cid2, $cid3])
      ->execute();
    $this->assertCount(5, $optional);

    $grouped = Contact::get(FALSE)
      ->addJoin('Tag', FALSE, 'EntityTag', ['tag.name', 'IN', [$tag1, $tag3]])
      ->addSelect('first_name', 'COUNT(tag.name) AS tag_count')
      ->addWhere('id', 'IN', [$cid1, $cid2, $cid3])
      ->addGroupBy('id')
      ->execute()->indexBy('id');
    $this->assertEquals(1, (int) $grouped[$cid1]['tag_count']);
    $this->assertEquals(2, (int) $grouped[$cid2]['tag_count']);
    $this->assertEquals(0, (int) $grouped[$cid3]['tag_count']);

    $reverse = Tag::get(FALSE)
      ->addJoin('Contact', FALSE, 'EntityTag', ['contact.id', 'IN', [$cid1, $cid2, $cid3]])
      ->addGroupBy('id')
      ->addSelect('name', 'COUNT(contact.id) AS contacts')
      ->execute()->indexBy('name');
    $this->assertEquals(2, (int) $reverse[$tag1]['contacts']);
    $this->assertEquals(1, (int) $reverse[$tag2]['contacts']);
    $this->assertEquals(1, (int) $reverse[$tag3]['contacts']);
  }

  public function testBridgeJoinRelationshipContactActivity() {
    $cid1 = Contact::create(FALSE)
      ->addValue('first_name', 'Aaa')
      ->addChain('activity', Activity::create()
        ->addValue('activity_type_id:name', 'Meeting')
        ->addValue('source_contact_id', '$id')
        ->addValue('target_contact_id', '$id')
      )
      ->execute()
      ->first()['id'];
    $cid2 = Contact::create(FALSE)
      ->addValue('first_name', 'Bbb')
      ->addChain('activity', Activity::create()
        ->addValue('activity_type_id:name', 'Phone Call')
        ->addValue('source_contact_id', $cid1)
        ->addValue('target_contact_id', '$id')
      )
      ->addChain('r1', Relationship::create()
        ->setValues(['contact_id_a' => '$id', 'contact_id_b' => $cid1, 'relationship_type_id' => 1])
      )
      ->execute()
      ->first()['id'];
    $cid3 = Contact::create(FALSE)
      ->addValue('first_name', 'Ccc')
      ->addChain('activity', Activity::create()
        ->addValue('activity_type_id:name', 'Meeting')
        ->addValue('source_contact_id', $cid1)
        ->addValue('target_contact_id', '$id')
      )
      ->addChain('activity2', Activity::create()
        ->addValue('activity_type_id:name', 'Phone Call')
        ->addValue('source_contact_id', $cid1)
        ->addValue('target_contact_id', '$id')
      )
      ->addChain('r1', Relationship::create()
        ->setValues(['contact_id_a' => '$id', 'contact_id_b' => $cid1, 'relationship_type_id' => 1])
      )
      ->addChain('r2', Relationship::create()
        ->setValues(['contact_id_a' => '$id', 'contact_id_b' => $cid2, 'relationship_type_id' => 2, 'is_active' => FALSE])
      )
      ->execute()
      ->first()['id'];

    $result = Contact::get(FALSE)
      ->addSelect('id', 'act.id')
      ->addJoin('Activity AS act', TRUE, 'ActivityContact', ['act.record_type_id:name', '=', "'Activity Targets'"])
      ->addWhere('id', 'IN', [$cid1, $cid2, $cid3])
      ->execute();
    $this->assertCount(4, $result);

    $result = Contact::get(FALSE)
      ->addSelect('id', 'act.id')
      ->addJoin('Activity AS act', TRUE, 'ActivityContact', ['act.activity_type_id:name', '=', "'Meeting'"], ['act.record_type_id:name', '=', "'Activity Targets'"])
      ->addWhere('id', 'IN', [$cid1, $cid2, $cid3])
      ->execute();
    $this->assertCount(2, $result);

    $result = Activity::get(FALSE)
      ->addSelect('id', 'contact.id')
      ->addJoin('Contact', FALSE, 'ActivityContact')
      ->addWhere('contact.id', 'IN', [$cid1, $cid2, $cid3])
      ->execute();
    $this->assertCount(8, $result);

    $result = Activity::get(FALSE)
      ->addSelect('id', 'contact.id', 'rel.id')
      ->addJoin('Contact', 'LEFT', 'ActivityContact', ['contact.record_type_id:name', '=', "'Activity Targets'"])
      ->addJoin('Contact AS rel', 'LEFT', 'RelationshipCache', ['rel.far_contact_id', '=', 'contact.id'], ['rel.near_relation:name', '=', '"Child of"'])
      ->addWhere('contact.id', 'IN', [$cid1, $cid2, $cid3])
      ->addOrderBy('id')
      ->execute();
    $this->assertCount(5, $result);
    $this->assertEquals($cid1, $result[0]['contact.id']);
    $this->assertEquals($cid2, $result[0]['rel.id']);
    $this->assertEquals($cid1, $result[1]['contact.id']);
    $this->assertEquals($cid3, $result[1]['rel.id']);
    $this->assertEquals($cid2, $result[2]['contact.id']);
    $this->assertNull($result[2]['rel.id']);
    $this->assertEquals($cid3, $result[3]['contact.id']);
    $this->assertNull($result[3]['rel.id']);
    $this->assertEquals($cid3, $result[4]['contact.id']);
    $this->assertNull($result[3]['rel.id']);

    // Ensure calculated fields such as is_current work correctly for both LEFT and INNER joins
    foreach (['LEFT', 'INNER'] as $side) {
      $result = civicrm_api4('Contact', 'get', [
        'select' => [
          'id',
          'display_name',
          'Contact_RelationshipCache_Contact_01.id',
          'Contact_RelationshipCache_Contact_01.near_relation:label',
          'Contact_RelationshipCache_Contact_01.is_current',
          'Contact_RelationshipCache_Contact_01.relationship_id',
          'Contact_RelationshipCache_Contact_01.orientation',
        ],
        'where' => [
          ['Contact_RelationshipCache_Contact_01.id', '=', $cid3],
        ],
        'join' => [
          [
            'Contact AS Contact_RelationshipCache_Contact_01',
            $side,
            'RelationshipCache',
            ['id', '=', 'Contact_RelationshipCache_Contact_01.far_contact_id'],
          ],
        ],
        'orderBy' => ['id' => 'ASC'],
        'checkPermissions' => TRUE,
        'limit' => 50,
        'offset' => 0,
      ]);
      $this->assertEquals($cid1, $result[0]['id']);
      $this->assertEquals($cid2, $result[1]['id']);
      $this->assertEquals($cid3, $result[1]['Contact_RelationshipCache_Contact_01.id']);
      $this->assertEquals(TRUE, $result[0]['Contact_RelationshipCache_Contact_01.is_current']);
      $this->assertEquals(FALSE, $result[1]['Contact_RelationshipCache_Contact_01.is_current']);
    }
  }

  public function testJoinToEmployerId() {
    $employer = Contact::create(FALSE)
      ->addValue('contact_type', 'Organization')
      ->addValue('organization_name', 'TesterCo')
      ->execute()->first()['id'];
    $employee = Contact::create(FALSE)
      ->addValue('employer_id', $employer)
      ->addValue('first_name', 'TesterMan')
      ->execute()->first()['id'];
    $email = Email::create(FALSE)
      ->addValue('email', 'tester@test.com')
      ->addValue('contact_id', $employee)
      ->execute()->first()['id'];

    $contactGet = Contact::get(FALSE)
      ->addWhere('id', '=', $employee)
      ->addSelect('employer_id', 'employer_id.display_name')
      ->execute()->first();
    $this->assertEquals($employer, $contactGet['employer_id']);
    $this->assertEquals('TesterCo', $contactGet['employer_id.display_name']);

    $emailGet = Email::get(FALSE)
      ->addWhere('id', '=', $email)
      ->addSelect('contact_id.employer_id', 'contact_id.employer_id.display_name')
      ->execute()->first();
    $this->assertEquals($employer, $emailGet['contact_id.employer_id']);
    $this->assertEquals('TesterCo', $emailGet['contact_id.employer_id.display_name']);
  }

  public function testDeprecatedJoins() {
    $message = '';
    try {
      \Civi\Api4\Email::get(FALSE)
        ->addWhere('contact.first_name', '=', 'Peter')
        ->addWhere('contact.last_name', '=', '')
        ->addWhere('contact.is_deleted', '=', 0)
        ->addWhere('contact.is_deceased', '=', 0)
        ->addWhere('email', '=', '')
        ->addWhere('is_primary', '=', TRUE)
        ->setSelect(['contact_id'])->execute();
    }
    catch (\Exception $e) {
      $message = $e->getMessage();
    }
    $this->assertStringContainsString("Deprecated join alias 'contact' used in APIv4 get. Should be changed to 'contact_id'", $message);
  }

  public function testJoinWithExpression() {
    Phone::create(FALSE)
      ->setValues(['contact_id' => $this->getReference('test_contact_1')['id'], 'phone' => '654321'])
      ->execute();
    $contacts = Contact::get(FALSE)
      ->addSelect('id', 'phone.phone')
      ->addJoin('Phone', 'INNER', ['LOWER(phone.phone)', '=', "CONCAT('6', '5', '4', '3', '2', '1')"])
      ->addWhere('id', 'IN', [$this->getReference('test_contact_1')['id'], $this->getReference('test_contact_2')['id']])
      ->addOrderBy('phone.id')
      ->execute();
    $this->assertCount(1, $contacts);
    $this->assertEquals($this->getReference('test_contact_1')['id'], $contacts[0]['id']);
    $this->assertEquals('654321', $contacts[0]['phone.phone']);
  }

}

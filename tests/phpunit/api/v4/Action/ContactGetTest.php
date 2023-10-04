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

use api\v4\Api4TestBase;
use Civi\Api4\Contact;
use Civi\Api4\ContactType;
use Civi\Api4\Relationship;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ContactGetTest extends Api4TestBase implements TransactionalInterface {

  public function testGetDeletedContacts(): void {
    $last_name = uniqid('deleteContactTest');

    $bob = Contact::create()
      ->setValues(['first_name' => 'Bob', 'last_name' => $last_name])
      ->execute()->first();

    $jan = Contact::create()
      ->setValues(['first_name' => 'Jan', 'last_name' => $last_name])
      ->execute()->first();

    $del = Contact::create()
      ->setValues(['first_name' => 'Del', 'last_name' => $last_name, 'is_deleted' => 1])
      ->execute()->first();

    // Deleted contacts are not fetched by default
    $this->assertCount(2, Contact::get()->addWhere('last_name', '=', $last_name)->selectRowCount()->execute());

    // You can search for them specifically
    $contacts = Contact::get()->addWhere('last_name', '=', $last_name)->addWhere('is_deleted', '=', 1)->addSelect('id')->execute();
    $this->assertEquals($del['id'], $contacts->first()['id']);

    // Or by id
    $this->assertCount(3, Contact::get()->addWhere('id', 'IN', [$bob['id'], $jan['id'], $del['id']])->selectRowCount()->execute());

    // Putting is_deleted anywhere in the where clause will disable the default
    $contacts = Contact::get()->addClause('OR', ['last_name', '=', $last_name], ['is_deleted', '=', 0])->addSelect('id')->execute();
    $this->assertContains($del['id'], $contacts->column('id'));
  }

  /**
   * Test ordering of contact by contact_sub_type when field
   * contains more than one type and contact_type label and name 
   * are different enough to get sorted in different order.
   *
   * 
   */

   public function testGetWithOrderBy(): void {
    $last_name = uniqid('GetWithOrderByTest');

	  ContactType::create(FALSE)
        ->addValue('name', 'Membre')
        ->addValue('label', '1.Actif')
        ->addValue('parent_id.name', 'Individual')
        ->execute();
    ContactType::create(FALSE)
        ->addValue('name', 'Proche')
        ->addValue('label', '2.Proche')
        ->addValue('parent_id.name', 'Individual')
        ->execute();
    ContactType::create(FALSE)
        ->addValue('name', 'Soutien')
        ->addValue('label', '3.Soutien')
        ->addValue('parent_id.name', 'Individual')
        ->execute();
	  ContactType::create(FALSE)
        ->addValue('name', 'Employ_')
        ->addValue('label', '5.Employé')
        ->addValue('parent_id.name', 'Individual')
        ->execute();
	  ContactType::create(FALSE)
        ->addValue('name', 'DCD')
        ->addValue('label', '0.Décédé')
        ->addValue('parent_id.name', 'Individual')
        ->execute();

	  $bob = Contact::create()
      ->setValues(['first_name' => 'Bob', 'last_name' => $last_name, 'contact_sub_type' => 'Membre'])
      ->execute()->first();

	  $jan = Contact::create()
      ->setValues(['first_name' => 'Jan', 'last_name' => $last_name, 'contact_sub_type' => 'Proche'])
      ->execute()->first();

	  $dan = Contact::create()
      ->setValues(['first_name' => 'Dan', 'last_name' => $last_name, 'contact_sub_type' => 'Soutien'])
      ->execute()->first();

	  $joe = Contact::create()
      ->setValues(['first_name' => 'Joe', 'last_name' => $last_name, 'contact_sub_type' => 'MembreEmploy_'])
      ->execute()->first();

	  $eli = Contact::create()
      ->setValues(['first_name' => 'Eli', 'last_name' => $last_name, 'contact_sub_type' => 'DCDMembre'])
      ->execute()->first();

	  $yan = Contact::create()
      ->setValues(['first_name' => 'Yan', 'last_name' => $last_name, 'contact_sub_type' => ''])
      ->execute()->first();

	  $contacts = \Civi\Api4\Contact::get(TRUE)
	  ->addSelect('id', 'contact_type', 'contact_sub_type', 'contact_sub_type:label')
	  ->addWhere('contact_type', '=', 'Individual')
	  ->addOrderBy('contact_sub_type:label', 'ASC')
	  ->setLimit(10)
	  ->execute();

	foreach ($contacts as $contact) {
	  // do something
	}

    $msg = '';
    try {
//      $limit2->single();
    }
    catch (\CRM_Core_Exception $e) {
      $msg = $e->getMessage();
    }
}


  public function testGetWithLimit(): void {
    $last_name = uniqid('getWithLimitTest');

    $bob = Contact::create()
      ->setValues(['first_name' => 'Bob', 'last_name' => $last_name])
      ->execute()->first();

    $jan = Contact::create()
      ->setValues(['first_name' => 'Jan', 'last_name' => $last_name])
      ->execute()->first();

    $dan = Contact::create()
      ->setValues(['first_name' => 'Dan', 'last_name' => $last_name])
      ->execute()->first();

    $num = Contact::get(FALSE)->selectRowCount()->execute()->count();

    // The object's count() method will account for all results, ignoring limit & offset, while the array results are limited
    $offset1 = Contact::get(FALSE)->setOffset(1)->execute();
    $this->assertCount($num, $offset1);
    $this->assertCount($num - 1, (array) $offset1);
    $offset2 = Contact::get(FALSE)->setOffset(2)->execute();
    $this->assertCount($num - 2, (array) $offset2);
    $this->assertCount($num, $offset2);
    // With limit, it doesn't fetch total count by default
    $limit2 = Contact::get(FALSE)->setLimit(2)->execute();
    $this->assertCount(2, (array) $limit2);
    $this->assertCount(2, $limit2);
    // With limit, you have to trigger the full row count manually
    $limit2 = Contact::get(FALSE)->setLimit(2)->addSelect('sort_name', 'row_count')->execute();
    $this->assertCount(2, (array) $limit2);
    $this->assertCount($num, $limit2);
    $msg = '';
    try {
      $limit2->single();
    }
    catch (\CRM_Core_Exception $e) {
      $msg = $e->getMessage();
    }
    $this->assertMatchesRegularExpression(';Expected to find one Contact record;', $msg);
    $limit1 = Contact::get(FALSE)->addWhere('last_name', '=', $last_name)->setLimit(1)->execute();
    $this->assertCount(1, (array) $limit1);
    $this->assertCount(1, $limit1);
    $this->assertTrue(!empty($limit1->single()['sort_name']));
  }

  /**
   * Test a lack of fatal errors when the where contains an emoji.
   *
   * By default our DBs are not 🦉 compliant. This test will age
   * out when we are.
   *
   * @throws \CRM_Core_Exception
   */
  public function testEmoji(): void {
    $schemaNeedsAlter = \CRM_Core_BAO_SchemaHandler::databaseSupportsUTF8MB4();
    if ($schemaNeedsAlter) {
      \CRM_Core_DAO::executeQuery("
        ALTER TABLE civicrm_contact MODIFY COLUMN
        `first_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'First Name.',
        CHARSET utf8 COLLATE utf8_unicode_ci
      ");
      \Civi::$statics['CRM_Core_BAO_SchemaHandler'] = [];
    }
    \Civi::$statics['CRM_Core_BAO_SchemaHandler'] = [];
    Contact::get()
      ->setDebug(TRUE)
      ->addWhere('first_name', '=', '🦉Claire')
      ->execute();
    if ($schemaNeedsAlter) {
      \CRM_Core_DAO::executeQuery("
        ALTER TABLE civicrm_contact MODIFY COLUMN
        `first_name` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'First Name.',
        CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci
      ");
    }
  }

  public function testEmptyAndNullOperators(): void {
    $last_name = uniqid(__FUNCTION__);

    $bob = Contact::create()
      ->setValues(['first_name' => 'Bob', 'last_name' => $last_name, 'prefix_id' => 0])
      ->execute()->first();
    // Initial value is NULL, but to test the empty operator, change it to an empty string
    \CRM_Core_DAO::executeQuery("UPDATE civicrm_contact SET middle_name = '' WHERE id = " . $bob['id']);

    $jan = Contact::create()
      ->setValues(['first_name' => 'Jan', 'middle_name' => 'J', 'last_name' => $last_name, 'prefix_id' => 1])
      ->execute()->first();

    $dan = Contact::create()
      ->setValues(['first_name' => 'Dan', 'last_name' => $last_name, 'prefix_id' => NULL])
      ->execute()->first();

    // Test EMPTY and NULL operators on string fields
    $result = Contact::get(FALSE)
      ->addWhere('last_name', '=', $last_name)
      ->addWhere('middle_name', 'IS EMPTY')
      ->execute()->indexBy('id');
    $this->assertCount(2, $result);
    $this->assertArrayNotHasKey($jan['id'], (array) $result);

    $result = Contact::get(FALSE)
      ->addWhere('last_name', '=', $last_name)
      ->addWhere('middle_name', 'IS NOT NULL')
      ->execute()->indexBy('id');
    $this->assertCount(2, $result);
    $this->assertArrayNotHasKey($dan['id'], (array) $result);

    $result = Contact::get(FALSE)
      ->addWhere('last_name', '=', $last_name)
      ->addWhere('middle_name', 'IS NOT EMPTY')
      ->execute()->indexBy('id');
    $this->assertCount(1, $result);
    $this->assertArrayHasKey($jan['id'], (array) $result);

    // Test EMPTY and NULL operators on Integer fields
    $result = Contact::get(FALSE)
      ->addWhere('last_name', '=', $last_name)
      ->addWhere('prefix_id', 'IS EMPTY')
      ->execute()->indexBy('id');
    $this->assertCount(2, $result);
    $this->assertArrayNotHasKey($jan['id'], (array) $result);

    $result = Contact::get(FALSE)
      ->addWhere('last_name', '=', $last_name)
      ->addWhere('prefix_id', 'IS NOT NULL')
      ->execute()->indexBy('id');
    $this->assertCount(2, $result);
    $this->assertArrayNotHasKey($dan['id'], (array) $result);

    $result = Contact::get(FALSE)
      ->addWhere('last_name', '=', $last_name)
      ->addWhere('prefix_id', 'IS NOT EMPTY')
      ->execute()->indexBy('id');
    $this->assertCount(1, $result);
    $this->assertArrayHasKey($jan['id'], (array) $result);
  }

  public function testRegexpOperators(): void {
    $last_name = uniqid(__FUNCTION__);

    $alice = Contact::create()
      ->setValues(['first_name' => 'Alice', 'last_name' => $last_name])
      ->execute()->first();

    $alex = Contact::create()
      ->setValues(['first_name' => 'Alex', 'last_name' => $last_name])
      ->execute()->first();

    $jane = Contact::create()
      ->setValues(['first_name' => 'Jane', 'last_name' => $last_name])
      ->execute()->first();

    $result = Contact::get(FALSE)
      ->addWhere('last_name', '=', $last_name)
      ->addWhere('first_name', 'REGEXP', '^A')
      ->execute()->indexBy('id');
    $this->assertCount(2, $result);
    $this->assertArrayHasKey($alice['id'], (array) $result);
    $this->assertArrayHasKey($alex['id'], (array) $result);

    $result = Contact::get(FALSE)
      ->addWhere('last_name', '=', $last_name)
      ->addWhere('first_name', 'NOT REGEXP', '^A')
      ->execute()->indexBy('id');
    $this->assertCount(1, $result);
    $this->assertArrayHasKey($jane['id'], (array) $result);
  }

  public function testGetRelatedWithSubType(): void {
    $org = Contact::create(FALSE)
      ->addValue('contact_type', 'Organization')
      ->addValue('organization_name', 'Run Amok')
      ->execute()->single()['id'];

    $ind = Contact::create(FALSE)
      ->addValue('first_name', 'Guy')
      ->addValue('last_name', 'Amok')
      ->addValue('contact_sub_type', ['Student'])
      ->addChain('relationship', Relationship::create()
        ->addValue('contact_id_a', '$id')
        ->addValue('contact_id_b', $org)
        ->addValue("relationship_type_id:name", "Employee of")
      )
      ->execute()->single()['id'];

    // We can retrieve contact sub-type directly
    $result = Contact::get()
      ->addSelect('contact_sub_type:label')
      ->addWhere('id', '=', $ind)
      ->execute()->single();
    $this->assertEquals(['Student'], $result['contact_sub_type:label']);

    // Ensure we can also retrieve it indirectly via join
    $params = [
      'select' => [
        'id',
        'display_name',
        'contact_type',
        'Contact_RelationshipCache_Contact_01.id',
        'Contact_RelationshipCache_Contact_01.far_relation:label',
        'Contact_RelationshipCache_Contact_01.display_name',
        'Contact_RelationshipCache_Contact_01.contact_sub_type:label',
        'Contact_RelationshipCache_Contact_01.contact_type',
      ],
      'where' => [
        ['contact_type:name', '=', 'Organization'],
        ['Contact_RelationshipCache_Contact_01.contact_sub_type:name', 'CONTAINS', 'Student'],
        ['id', '=', $org],
      ],
      'join' => [
        [
          'Contact AS Contact_RelationshipCache_Contact_01',
          'INNER',
          'RelationshipCache',
          ['id', '=', 'Contact_RelationshipCache_Contact_01.far_contact_id'],
          ['Contact_RelationshipCache_Contact_01.near_relation:name', 'IN', ['Employee of']],
        ],
      ],
      'checkPermissions' => TRUE,
      'limit' => 50,
      'offset' => 0,
      'debug' => TRUE,
    ];

    $results = civicrm_api4('Contact', 'get', $params);
    $result = $results->single();
    $this->assertEquals('Run Amok', $result['display_name']);
    $this->assertEquals('Guy Amok', $result['Contact_RelationshipCache_Contact_01.display_name']);
    $this->assertEquals('Employer of', $result['Contact_RelationshipCache_Contact_01.far_relation:label']);
    $this->assertEquals(['Student'], $result['Contact_RelationshipCache_Contact_01.contact_sub_type:label']);
  }

  public function testGetWithWhereExpression(): void {
    $last_name = uniqid(__FUNCTION__);

    $alice = Contact::create()
      ->setValues(['first_name' => 'Alice', 'last_name' => $last_name])
      ->execute()->first();

    $result = Contact::get(FALSE)
      ->addWhere('last_name', '=', $last_name)
      ->addWhere('LOWER(first_name)', '=', "BINARY('ALICE')", TRUE)
      ->execute()->indexBy('id');
    $this->assertCount(0, $result);

    $result = Contact::get(FALSE)
      ->addWhere('last_name', '=', $last_name)
      ->addWhere('LOWER(first_name)', '=', "BINARY('alice')", TRUE)
      ->execute()->indexBy('id');
    $this->assertArrayHasKey($alice['id'], (array) $result);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testOrClause(): void {
    Contact::get()
      ->addClause('OR', ['first_name', '=', '🚂'], ['last_name', '=', '🚂'])
      ->setCheckPermissions(FALSE)
      ->execute();
  }

  public function testAge(): void {
    $lastName = uniqid(__FUNCTION__);
    $sampleData = [
      ['first_name' => 'abc', 'last_name' => $lastName, 'birth_date' => 'now - 2 year + 3 day'],
      ['first_name' => 'def', 'last_name' => $lastName, 'birth_date' => 'now - 21 year - 6 month'],
      ['first_name' => 'ghi', 'last_name' => $lastName, 'birth_date' => 'now'],
    ];
    $this->saveTestRecords('Contact', ['records' => $sampleData]);

    $result = Contact::get(FALSE)
      ->addWhere('last_name', '=', $lastName)
      ->addSelect('first_name', 'age_years', 'next_birthday', 'DAYSTOANNIV(birth_date)')
      ->execute()->indexBy('first_name');
    $this->assertEquals(1, $result['abc']['age_years']);
    $this->assertEquals(3, $result['abc']['next_birthday']);
    $this->assertEquals(3, $result['abc']['DAYSTOANNIV:birth_date']);
    $this->assertEquals(21, $result['def']['age_years']);
    $this->assertEquals(0, $result['ghi']['age_years']);
    $this->assertEquals(0, $result['ghi']['next_birthday']);
    $this->assertEquals(0, $result['ghi']['DAYSTOANNIV:birth_date']);

    Contact::get(FALSE)
      ->addWhere('age_years', '=', 21)
      ->addWhere('last_name', '=', $lastName)
      ->execute()->single();
  }

  /**
   *
   */
  public function testGetWithCount(): void {
    $myName = uniqid('count');
    for ($i = 1; $i <= 20; ++$i) {
      $this->createTestRecord('Contact', [
        'first_name' => "Contact $i",
        'last_name' => $myName,
      ]);
    }

    $get1 = Contact::get(FALSE)
      ->addWhere('last_name', '=', $myName)
      ->selectRowCount()
      ->addSelect('first_name')
      ->setLimit(10)
      ->execute();

    $this->assertEquals(20, $get1->count());
    $this->assertCount(10, (array) $get1);

  }

  public function testGetWithPrimaryEmailPhoneIMAddress(): void {
    $lastName = uniqid(__FUNCTION__);
    $email = uniqid() . '@example.com';
    $phone = uniqid('phone');
    $im = uniqid('im');
    $c1 = $this->createTestRecord('Contact', ['last_name' => $lastName]);
    $c2 = $this->createTestRecord('Contact', ['last_name' => $lastName]);
    $c3 = $this->createTestRecord('Contact', ['last_name' => $lastName]);

    $this->createTestRecord('Email', ['email' => $email, 'contact_id' => $c1['id']]);
    $this->createTestRecord('Email', ['email' => 'not@primary.com', 'contact_id' => $c1['id']]);
    $this->createTestRecord('Phone', ['phone' => $phone, 'contact_id' => $c1['id']]);
    $this->createTestRecord('IM', ['name' => $im, 'contact_id' => $c2['id']]);
    $this->createTestRecord('Address', ['city' => 'Somewhere', 'street_address' => '123 Street', 'contact_id' => $c2['id']]);

    $results = Contact::get(FALSE)
      ->addSelect('id', 'email_primary.email', 'phone_primary.phone', 'im_primary.name', 'address_primary.*')
      ->addWhere('last_name', '=', $lastName)
      ->addOrderBy('id')
      ->execute();

    $this->assertEquals($email, $results[0]['email_primary.email']);
    $this->assertEquals($phone, $results[0]['phone_primary.phone']);
    $this->assertEquals($im, $results[1]['im_primary.name']);
    $this->assertEquals('Somewhere', $results[1]['address_primary.city']);
    $this->assertEquals('123 Street', $results[1]['address_primary.street_address']);
    $this->assertNull($results[0]['im_primary.name']);
    $this->assertNull($results[2]['email_primary.email']);
    $this->assertNull($results[2]['phone_primary.phone']);
    $this->assertNull($results[2]['im_primary.name']);
    $this->assertNull($results[2]['address_primary.city']);
  }

  public function testInvalidPseudoConstantWithIN(): void {
    $this->createTestRecord('Contact', [
      'first_name' => uniqid(),
      'last_name' => uniqid(),
      'prefix_id:name' => 'Ms.',
    ]);
    $resultCount = Contact::get(FALSE)
      ->addSelect('id')
      ->addWhere('prefix_id:name', 'IN', ['Msssss.'])
      ->execute();
    $this->assertCount(0, $resultCount);
  }

}

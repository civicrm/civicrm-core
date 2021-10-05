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

use Civi\Api4\Contact;
use Civi\Api4\Relationship;

/**
 * @group headless
 */
class ContactGetTest extends \api\v4\UnitTestCase {

  public function testGetDeletedContacts() {
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

  public function testGetWithLimit() {
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
    catch (\API_Exception $e) {
      $msg = $e->getMessage();
    }
    $this->assertRegExp(';Expected to find one Contact record;', $msg);
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
   * @throws \API_Exception
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

  public function testEmptyAndNullOperators() {
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

  public function testRegexpOperators() {
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

  public function testGetRelatedWithSubType() {
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

  public function testGetWithWhereExpression() {
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
   * @throws \API_Exception
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
      ['first_name' => 'abc', 'last_name' => $lastName, 'birth_date' => 'now - 1 year - 1 month'],
      ['first_name' => 'def', 'last_name' => $lastName, 'birth_date' => 'now - 21 year - 6 month'],
    ];
    Contact::save(FALSE)
      ->setRecords($sampleData)
      ->execute();

    $result = Contact::get(FALSE)
      ->addWhere('last_name', '=', $lastName)
      ->addSelect('first_name', 'age_years')
      ->execute()->indexBy('first_name');
    $this->assertEquals(1, $result['abc']['age_years']);
    $this->assertEquals(21, $result['def']['age_years']);

    Contact::get(FALSE)
      ->addWhere('age_years', '=', 21)
      ->addWhere('last_name', '=', $lastName)
      ->execute()->single();
  }

}

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

namespace Civi\tests\phpunit\api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Api4\Contact;
use Civi\Api4\MockSqlView;

/**
 * @group headless
 */
class SqlViewTest extends Api4TestBase {

  public function setUp(): void {
    parent::setUp();
    // Enable mock view. See MockSqlView::_on_schema_map_build
    $GLOBALS['enableMockSqlView'] = TRUE;
    \Civi::cache('metadata')->clear();
  }

  public function tearDown(): void {
    $GLOBALS['enableMockSqlView'] = FALSE;
    parent::tearDown();
  }

  /**
   * Test relationship cache tracks created relationships.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetFields(): void {
    $fields = MockSqlView::getFields(FALSE)
      ->setLoadOptions(TRUE)
      ->execute()->indexBy('name');
    $this->assertCount(5, $fields);
    $this->assertEquals('String', $fields['full_name']['data_type']);
    $this->assertEquals('Contact', $fields['contact_id']['fk_entity']);
    $this->assertContains('Home', $fields['email_location_type_id']['options']);
  }

  public function testGetSqlView() {
    $sampleData = [
      [
        'first_name' => 'One',
        'last_name' => 'Tester',
        'email_primary.email' => 'test1@example.com',
      ],
      [
        'first_name' => 'Two',
        'last_name' => 'Not Included',
      ],
      [
        'first_name' => 'Three',
        'last_name' => 'Tester',
      ],
    ];
    $cid = $this->saveTestRecords('Contact', ['records' => $sampleData])->column('id');

    $results = MockSqlView::get(FALSE)
      ->addSelect('contact_id', 'full_name', 'email', 'email_location_type_id:label')
      ->execute()->indexBy('contact_id');

    $this->assertSame('One Tester', $results[$cid[0]]['full_name']);
    $this->assertSame('Three Tester', $results[$cid[2]]['full_name']);
    // Where clause in view excludes contact 2.
    $this->assertArrayNotHasKey($cid[1], $results);

    // Check emails
    $this->assertSame('test1@example.com', $results[$cid[0]]['email']);
    $this->assertNull($results[$cid[2]]['email']);
    // Pseudoconstant ought to work
    $defaultLocation = \CRM_Core_BAO_LocationType::getDefault()->display_name;
    $this->assertSame($defaultLocation, $results[$cid[0]]['email_location_type_id:label']);
    // No email address
    $this->assertNull($results[$cid[2]]['email_location_type_id:label']);
  }

  public function testJoinViaFkFields(): void {
    $contact = $this->createTestRecord('Contact', [
      'first_name' => uniqid('first_'),
      'last_name' => uniqid('Tester'),
      'email_primary.email' => 'primary@example.com',
      'email_billing.email' => 'billing@example.com',
    ]);

    $view = MockSqlView::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->addSelect('contact_id.display_name', 'email_id.email', 'full_name')
      ->addJoin('Email AS explicit_email', FALSE, ['email_id', '=', 'explicit_email.id'])
      ->addSelect('explicit_email.email');

    $result = $view->execute()->single();

    $this->assertSame($contact['display_name'], $result['contact_id.display_name']);
    $this->assertSame('primary@example.com', $result['email_id.email']);
    $this->assertSame($contact['display_name'], $result['full_name']);
    $this->assertSame('primary@example.com', $result['explicit_email.email']);
  }

  public function testJoinFromContact(): void {
    $sampleData = [
      [
        'first_name' => 'Alpha',
        'last_name' => 'Tester',
        'email_primary.email' => 'alpha@example.com',
      ],
      [
        'first_name' => 'Beta',
        'last_name' => 'Tester',
      ],
    ];
    $contacts = $this->saveTestRecords('Contact', ['records' => $sampleData]);

    $results = Contact::get(FALSE)
      ->addWhere('id', 'IN', $contacts->column('id'))
      ->addJoin('MockSqlView AS view', 'LEFT', ['id', '=', 'view.contact_id'])
      ->addSelect('id', 'display_name', 'view.full_name', 'view.email')
      ->addOrderBy('id')
      ->execute()->indexBy('id');

    $this->assertCount(2, $results);
    $this->assertSame($results[$contacts[0]['id']]['display_name'], $results[$contacts[0]['id']]['view.full_name']);
    $this->assertSame('alpha@example.com', $results[$contacts[0]['id']]['view.email']);
    $this->assertSame($results[$contacts[1]['id']]['display_name'], $results[$contacts[1]['id']]['view.full_name']);
    $this->assertNull($results[$contacts[1]['id']]['view.email']);
  }

}

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

namespace api\v4\Action;

use api\v4\Api4TestBase;
use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Batch;
use Civi\Api4\Contact;
use Civi\Api4\Email;
use Civi\Api4\LocBlock;
use Civi\Api4\Note;
use Civi\Api4\Relationship;
use Civi\Api4\SavedSearch;
use Civi\Api4\SearchParamSet;
use Civi\Api4\UserJob;
use Civi\Test\TransactionalInterface;

/**
 * Tests preventing non-admin users from stealing records belonging to other contacts.
 *
 * @group headless
 */
class ContactBelongingsPermissionTest extends Api4TestBase implements TransactionalInterface {

  public function tearDown(): void {
    \CRM_Utils_Hook::singleton()->reset();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    parent::tearDown();
  }

  /**
   * Helper to set up a logged in user and another contact with an email record.
   *
   * @return array [int $userContactId, int $otherContactId, int $otherEmailId]
   */
  private function setupTestContacts(): array {
    $userContact = $this->createLoggedInUser();
    $otherContact = Contact::create(FALSE)
      ->addValue('first_name', 'Other')
      ->addValue('last_name', 'Contact')
      ->execute()->first()['id'];
    $otherEmail = Email::create(FALSE)
      ->addValue('contact_id', $otherContact)
      ->addValue('email', 'other@example.com')
      ->execute()->first()['id'];

    return [$userContact, $otherContact, $otherEmail];
  }

  /**
   * Test AbstractUpdateAction: updating an existing email to point to user's contact.
   */
  public function testAbstractUpdateActionCannotStealContactRecord(): void {
    [$userContact, $otherContact, $otherEmail] = $this->setupTestContacts();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit my contact',
      'view my contact',
    ];

    $values = [
      'id' => $otherEmail,
      'contact_id' => $userContact,
    ];

    $accessCheck = Email::checkAccess()
      ->setValues($values)
      ->setAction('update')
      ->execute()->single();
    $this->assertFalse($accessCheck['access']);

    $this->expectException(UnauthorizedException::class);
    Email::update()
      ->setValues($values)
      ->execute();
  }

  /**
   * Test AbstractSaveAction: saving an existing email record to reassign ownership.
   */
  public function testAbstractSaveActionCannotStealContactRecord(): void {
    [$userContact, $otherContact, $otherEmail] = $this->setupTestContacts();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit my contact',
      'view my contact',
    ];

    $this->expectException(UnauthorizedException::class);
    Email::save()
      ->setRecords([
        [
          'id' => $otherEmail,
          'contact_id' => $userContact,
        ],
      ])
      ->execute();
  }

  /**
   * Test ContactSaveTrait: updating own contact with nested email_primary.id pointing to other contact's email.
   */
  public function testContactSaveCannotStealLocationRecord(): void {
    [$userContact, $otherContact, $otherEmail] = $this->setupTestContacts();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit my contact',
      'view my contact',
    ];

    $this->expectException(UnauthorizedException::class);
    Contact::update()
      ->addWhere('id', '=', $userContact)
      ->addValue('email_primary.id', $otherEmail)
      ->execute();
  }

  /**
   * Test LocBlockSaveTrait: creating LocBlock with nested email_id pointing to another contact's email.
   */
  public function testLocBlockSaveCannotStealContactLocationRecord(): void {
    [$userContact, $otherContact, $otherEmail] = $this->setupTestContacts();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'access CiviEvent',
      'edit all events',
    ];

    $this->expectException(UnauthorizedException::class);
    LocBlock::create()
      ->setValues([
        'email_id.id' => $otherEmail,
        'email_id.email' => 'hacked@example.com',
      ])
      ->execute();
  }

  /**
   * Test AbstractUpdateAction: updating an existing note to point to user's contact.
   */
  public function testAbstractUpdateActionCannotStealNoteRecord(): void {
    $userContact = $this->createLoggedInUser();
    $otherContact = Contact::create(FALSE)
      ->addValue('first_name', 'Other')
      ->execute()->first()['id'];
    $otherNote = Note::create(FALSE)
      ->addValue('entity_table', 'civicrm_contact')
      ->addValue('entity_id', $otherContact)
      ->addValue('note', 'Victim Secret')
      ->execute()->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit my contact',
      'view my contact',
    ];

    $values = [
      'id' => $otherNote,
      'entity_table' => 'civicrm_contact',
      'entity_id' => $userContact,
    ];

    $accessCheck = Note::checkAccess()
      ->setValues($values)
      ->setAction('update')
      ->execute()->single();
    $this->assertFalse($accessCheck['access']);

    $this->expectException(UnauthorizedException::class);
    Note::update()
      ->setValues($values)
      ->execute();
  }

  /**
   * Test AbstractSaveAction: saving an existing note to point to user's contact.
   */
  public function testAbstractSaveActionCannotStealNoteRecord(): void {
    $userContact = $this->createLoggedInUser();
    $otherContact = Contact::create(FALSE)
      ->addValue('first_name', 'Other')
      ->execute()->first()['id'];
    $otherNote = Note::create(FALSE)
      ->addValue('entity_table', 'civicrm_contact')
      ->addValue('entity_id', $otherContact)
      ->addValue('note', 'Victim Secret')
      ->execute()->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit my contact',
      'view my contact',
    ];

    $this->expectException(UnauthorizedException::class);
    Note::save()
      ->setRecords([
        [
          'id' => $otherNote,
          'entity_table' => 'civicrm_contact',
          'entity_id' => $userContact,
        ],
      ])
      ->execute();
  }

  /**
   * Test AbstractUpdateAction: updating an existing saved search to point to user.
   */
  public function testAbstractUpdateActionCannotStealSavedSearch(): void {
    $userContact = $this->createLoggedInUser();
    $otherContact = Contact::create(FALSE)
      ->addValue('first_name', 'Other')
      ->execute()->first()['id'];
    $otherSearch = SavedSearch::create(FALSE)
      ->addValue('name', 'victim_search_test1')
      ->addValue('created_id', $otherContact)
      ->execute()->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'manage own search_kit',
    ];

    $values = [
      'id' => $otherSearch,
      'created_id' => $userContact,
    ];

    $accessCheck = SavedSearch::checkAccess()
      ->setValues($values)
      ->setAction('update')
      ->execute()->single();
    $this->assertFalse($accessCheck['access']);

    $this->expectException(UnauthorizedException::class);
    SavedSearch::update()
      ->setValues($values)
      ->execute();
  }

  /**
   * Test AbstractSaveAction: saving an existing saved search to point to user.
   */
  public function testAbstractSaveActionCannotStealSavedSearch(): void {
    $userContact = $this->createLoggedInUser();
    $otherContact = Contact::create(FALSE)
      ->addValue('first_name', 'Other')
      ->execute()->first()['id'];
    $otherSearch = SavedSearch::create(FALSE)
      ->addValue('name', 'victim_search_test2')
      ->addValue('created_id', $otherContact)
      ->execute()->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'manage own search_kit',
    ];

    $this->expectException(UnauthorizedException::class);
    SavedSearch::save()
      ->setRecords([
        [
          'id' => $otherSearch,
          'created_id' => $userContact,
        ],
      ])
      ->execute();
  }

  /**
   * Test AbstractUpdateAction: updating an existing relationship between other contacts.
   */
  public function testAbstractUpdateActionCannotStealRelationship(): void {
    $userContact = $this->createLoggedInUser();
    $otherContact1 = Contact::create(FALSE)
      ->addValue('first_name', 'Other1')
      ->execute()->first()['id'];
    $otherContact2 = Contact::create(FALSE)
      ->addValue('first_name', 'Other2')
      ->execute()->first()['id'];
    $otherRel = Relationship::create(FALSE)
      ->addValue('contact_id_a', $otherContact1)
      ->addValue('contact_id_b', $otherContact2)
      ->addValue('relationship_type_id', 1)
      ->execute()->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit my contact',
      'view my contact',
    ];

    $values = [
      'id' => $otherRel,
      'contact_id_a' => $userContact,
      'contact_id_b' => $userContact,
    ];

    $accessCheck = Relationship::checkAccess()
      ->setValues($values)
      ->setAction('update')
      ->execute()->single();
    $this->assertFalse($accessCheck['access']);

    $this->expectException(UnauthorizedException::class);
    Relationship::update()
      ->setValues($values)
      ->execute();
  }

  /**
   * Test AbstractSaveAction: saving an existing relationship between other contacts.
   */
  public function testAbstractSaveActionCannotStealRelationship(): void {
    $userContact = $this->createLoggedInUser();
    $otherContact1 = Contact::create(FALSE)
      ->addValue('first_name', 'Other1')
      ->execute()->first()['id'];
    $otherContact2 = Contact::create(FALSE)
      ->addValue('first_name', 'Other2')
      ->execute()->first()['id'];
    $otherRel = Relationship::create(FALSE)
      ->addValue('contact_id_a', $otherContact1)
      ->addValue('contact_id_b', $otherContact2)
      ->addValue('relationship_type_id', 1)
      ->execute()->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit my contact',
      'view my contact',
    ];

    $this->expectException(UnauthorizedException::class);
    Relationship::save()
      ->setRecords([
        [
          'id' => $otherRel,
          'contact_id_a' => $userContact,
          'contact_id_b' => $userContact,
        ],
      ])
      ->execute();
  }

  /**
   * Test AbstractUpdateAction: updating an existing user job to point to user.
   */
  public function testAbstractUpdateActionCannotStealUserJob(): void {
    $userContact = $this->createLoggedInUser();
    $otherContact = Contact::create(FALSE)
      ->addValue('first_name', 'Other')
      ->execute()->first()['id'];
    $otherJob = UserJob::create(FALSE)
      ->addValue('name', 'victim_job_test1')
      ->addValue('created_id', $otherContact)
      ->addValue('job_type', 'mail_import')
      ->addValue('status_id', 2)
      ->execute()->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
    ];

    $values = [
      'id' => $otherJob,
      'created_id' => $userContact,
    ];

    $accessCheck = UserJob::checkAccess()
      ->setValues($values)
      ->setAction('update')
      ->execute()->single();
    $this->assertFalse($accessCheck['access']);

    $this->expectException(UnauthorizedException::class);
    UserJob::update()
      ->setValues($values)
      ->execute();
  }

  /**
   * Test AbstractSaveAction: saving an existing user job to point to user.
   */
  public function testAbstractSaveActionCannotStealUserJob(): void {
    $userContact = $this->createLoggedInUser();
    $otherContact = Contact::create(FALSE)
      ->addValue('first_name', 'Other')
      ->execute()->first()['id'];
    $otherJob = UserJob::create(FALSE)
      ->addValue('name', 'victim_job_test2')
      ->addValue('created_id', $otherContact)
      ->addValue('job_type', 'mail_import')
      ->addValue('status_id', 2)
      ->execute()->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
    ];

    $this->expectException(UnauthorizedException::class);
    UserJob::save()
      ->setRecords([
        [
          'id' => $otherJob,
          'created_id' => $userContact,
        ],
      ])
      ->execute();
  }

  /**
   * Test AbstractUpdateAction: updating an existing search param set to point to user.
   */
  public function testAbstractUpdateActionCannotStealSearchParamSet(): void {
    $userContact = $this->createLoggedInUser();
    $otherContact = Contact::create(FALSE)
      ->addValue('first_name', 'Other')
      ->execute()->first()['id'];
    $otherParamSet = SearchParamSet::create(FALSE)
      ->addValue('label', 'victim_filter_test1')
      ->addValue('created_by', $otherContact)
      ->execute()->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
    ];

    $values = [
      'id' => $otherParamSet,
      'created_by' => $userContact,
    ];

    $accessCheck = SearchParamSet::checkAccess()
      ->setValues($values)
      ->setAction('update')
      ->execute()->single();
    $this->assertFalse($accessCheck['access']);

    $this->expectException(UnauthorizedException::class);
    SearchParamSet::update()
      ->setValues($values)
      ->execute();
  }

  /**
   * Test AbstractSaveAction: saving an existing search param set to point to user.
   */
  public function testAbstractSaveActionCannotStealSearchParamSet(): void {
    $userContact = $this->createLoggedInUser();
    $otherContact = Contact::create(FALSE)
      ->addValue('first_name', 'Other')
      ->execute()->first()['id'];
    $otherParamSet = SearchParamSet::create(FALSE)
      ->addValue('label', 'victim_filter_test2')
      ->addValue('created_by', $otherContact)
      ->execute()->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
    ];

    $this->expectException(UnauthorizedException::class);
    SearchParamSet::save()
      ->setRecords([
        [
          'id' => $otherParamSet,
          'created_by' => $userContact,
        ],
      ])
      ->execute();
  }

  /**
   * Test AbstractUpdateAction: updating an existing batch to change created_id.
   */
  public function testAbstractUpdateActionCannotStealBatchCreatedId(): void {
    $userContact = $this->createLoggedInUser();
    $otherContact = Contact::create(FALSE)
      ->addValue('first_name', 'Other')
      ->execute()->first()['id'];
    $batch = Batch::create(FALSE)
      ->addValue('title', 'Test Batch 1')
      ->addValue('name', 'test_batch_1')
      ->addValue('status_id:name', 'Open')
      ->addValue('created_id', $otherContact)
      ->execute()->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'administer CiviCRM',
    ];

    $values = [
      'id' => $batch,
      'created_id' => $userContact,
    ];

    $accessCheck = Batch::checkAccess()
      ->setValues($values)
      ->setAction('update')
      ->execute()->single();
    $this->assertFalse($accessCheck['access']);

    $this->expectException(UnauthorizedException::class);
    Batch::update()
      ->setValues($values)
      ->execute();
  }

  /**
   * Test AbstractSaveAction: saving an existing batch to change created_id.
   */
  public function testAbstractSaveActionCannotStealBatchCreatedId(): void {
    $userContact = $this->createLoggedInUser();
    $otherContact = Contact::create(FALSE)
      ->addValue('first_name', 'Other')
      ->execute()->first()['id'];
    $batch = Batch::create(FALSE)
      ->addValue('title', 'Test Batch 2')
      ->addValue('name', 'test_batch_2')
      ->addValue('status_id:name', 'Open')
      ->addValue('created_id', $otherContact)
      ->execute()->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'administer CiviCRM',
    ];

    $this->expectException(UnauthorizedException::class);
    Batch::save()
      ->setRecords([
        [
          'id' => $batch,
          'created_id' => $userContact,
        ],
      ])
      ->execute();
  }

  /**
   * Test that an admin with 'edit all manual batches' can change created_id in APIv4.
   */
  public function testAdminCanChangeBatchCreatedId(): void {
    $userContact = $this->createLoggedInUser();
    $otherContact = Contact::create(FALSE)
      ->addValue('first_name', 'Other')
      ->execute()->first()['id'];
    $batch = Batch::create(FALSE)
      ->addValue('title', 'Test Batch 3')
      ->addValue('name', 'test_batch_3')
      ->addValue('status_id:name', 'Open')
      ->addValue('created_id', $otherContact)
      ->execute()->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'administer CiviCRM',
      'edit all manual batches',
    ];

    $values = [
      'id' => $batch,
      'created_id' => $userContact,
    ];

    $accessCheck = Batch::checkAccess()
      ->setValues($values)
      ->setAction('update')
      ->execute()->single();
    $this->assertTrue($accessCheck['access']);

    Batch::update()
      ->setValues($values)
      ->execute();

    $stored = \CRM_Core_DAO::getFieldValue('CRM_Batch_DAO_Batch', $batch, 'created_id');
    $this->assertEquals($userContact, $stored);
  }

  /**
   * Test updating an existing report instance to change owner_id.
   */
  public function testCannotStealReportInstanceOwnerWithCheckPermissions(): void {
    $userContact = $this->createLoggedInUser();
    $otherContact = Contact::create(FALSE)
      ->addValue('first_name', 'Other')
      ->execute()->first()['id'];
    $report = \CRM_Report_BAO_ReportInstance::add([
      'report_id' => 'contact/summary',
      'title' => 'Victim Report 1',
      'owner_id' => $otherContact,
      'created_id' => $otherContact,
    ]);

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviReport',
      'save Report Criteria',
    ];

    $this->expectException(UnauthorizedException::class);
    \CRM_Report_BAO_ReportInstance::writeRecord([
      'id' => $report->id,
      'owner_id' => $userContact,
      'check_permissions' => 1,
    ]);
  }

  /**
   * Test updating an existing report instance to change created_id.
   */
  public function testCannotStealReportInstanceCreatedIdWithCheckPermissions(): void {
    $userContact = $this->createLoggedInUser();
    $otherContact = Contact::create(FALSE)
      ->addValue('first_name', 'Other')
      ->execute()->first()['id'];
    $report = \CRM_Report_BAO_ReportInstance::add([
      'report_id' => 'contact/summary',
      'title' => 'Victim Report 2',
      'owner_id' => $otherContact,
      'created_id' => $otherContact,
    ]);

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviReport',
      'save Report Criteria',
    ];

    $this->expectException(UnauthorizedException::class);
    \CRM_Report_BAO_ReportInstance::writeRecord([
      'id' => $report->id,
      'created_id' => $userContact,
      'check_permissions' => 1,
    ]);
  }

  /**
   * Test AbstractUpdateAction: updating an existing custom value to change entity_id.
   */
  public function testAbstractUpdateActionCannotUpdateOtherCustomValue(): void {
    $groupName = uniqid('grp');
    $fieldName = uniqid('fld');
    $customGroup = $this->createTestRecord('CustomGroup', [
      'title' => $groupName,
      'name' => $groupName,
      'extends' => 'Contact',
      'is_multiple' => TRUE,
    ]);

    $this->createTestRecord('CustomField', [
      'label' => $fieldName,
      'name' => $fieldName,
      'custom_group_id' => $customGroup['id'],
      'html_type' => 'Text',
      'data_type' => 'String',
    ]);

    $userContact = $this->createLoggedInUser();
    $otherContact = Contact::create(FALSE)
      ->addValue('first_name', 'Other')
      ->execute()->first()['id'];

    $customRecord = civicrm_api4("Custom_$groupName", 'create', [
      'checkPermissions' => FALSE,
      'values' => [
        'entity_id' => $otherContact,
        $fieldName => 'Victim Data',
      ],
    ])->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit my contact',
      'view all contacts',
      'access all custom data',
    ];

    $values = [
      'id' => $customRecord,
      'entity_id' => $userContact,
      $fieldName => 'Attacker Data',
    ];

    $accessCheck = civicrm_api4("Custom_$groupName", 'checkAccess', [
      'values' => $values,
      'action' => 'update',
    ])->single();
    $this->assertFalse($accessCheck['access']);

    $this->expectException(UnauthorizedException::class);
    civicrm_api4("Custom_$groupName", 'update', [
      'values' => $values,
    ]);
  }

  /**
   * Test AbstractSaveAction: saving an existing custom value with different entity_id.
   */
  public function testAbstractSaveActionCannotUpdateOtherCustomValue(): void {
    $groupName = uniqid('grp');
    $fieldName = uniqid('fld');
    $customGroup = $this->createTestRecord('CustomGroup', [
      'title' => $groupName,
      'name' => $groupName,
      'extends' => 'Contact',
      'is_multiple' => TRUE,
    ]);

    $this->createTestRecord('CustomField', [
      'label' => $fieldName,
      'name' => $fieldName,
      'custom_group_id' => $customGroup['id'],
      'html_type' => 'Text',
      'data_type' => 'String',
    ]);

    $userContact = $this->createLoggedInUser();
    $otherContact = Contact::create(FALSE)
      ->addValue('first_name', 'Other')
      ->execute()->first()['id'];

    $customRecord = civicrm_api4("Custom_$groupName", 'create', [
      'checkPermissions' => FALSE,
      'values' => [
        'entity_id' => $otherContact,
        $fieldName => 'Victim Data',
      ],
    ])->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit my contact',
      'access all custom data',
    ];

    $this->expectException(UnauthorizedException::class);
    civicrm_api4("Custom_$groupName", 'save', [
      'records' => [
        [
          'id' => $customRecord,
          'entity_id' => $userContact,
          $fieldName => 'Attacker Data',
        ],
      ],
    ]);
  }

  /**
   * Test that a user can update their own custom value record.
   */
  public function testUserCanUpdateOwnCustomValue(): void {
    $groupName = uniqid('grp');
    $fieldName = uniqid('fld');
    $customGroup = $this->createTestRecord('CustomGroup', [
      'title' => $groupName,
      'name' => $groupName,
      'extends' => 'Contact',
      'is_multiple' => TRUE,
    ]);

    $this->createTestRecord('CustomField', [
      'label' => $fieldName,
      'name' => $fieldName,
      'custom_group_id' => $customGroup['id'],
      'html_type' => 'Text',
      'data_type' => 'String',
    ]);

    $userContact = $this->createLoggedInUser();

    $customRecord = civicrm_api4("Custom_$groupName", 'create', [
      'checkPermissions' => FALSE,
      'values' => [
        'entity_id' => $userContact,
        $fieldName => 'My Data',
      ],
    ])->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit my contact',
      'access all custom data',
    ];

    $values = [
      'id' => $customRecord,
      $fieldName => 'Updated Data',
    ];

    $accessCheck = civicrm_api4("Custom_$groupName", 'checkAccess', [
      'values' => $values,
      'action' => 'update',
    ])->single();
    $this->assertTrue($accessCheck['access']);

    civicrm_api4("Custom_$groupName", 'update', [
      'values' => $values,
    ]);

    $saved = civicrm_api4("Custom_$groupName", 'get', [
      'checkPermissions' => FALSE,
      'where' => [['id', '=', $customRecord]],
    ])->first();
    $this->assertEquals('Updated Data', $saved[$fieldName]);
  }

  /**
   * Test Address::create cannot set master_id to an address belonging to a contact the user cannot view.
   */
  public function testAddressCannotShareAddressOfForbiddenContact(): void {
    [$userContact, $otherContact] = $this->setupTestContacts();
    $otherAddress = \Civi\Api4\Address::create(FALSE)
      ->addValue('contact_id', $otherContact)
      ->addValue('location_type_id', 1)
      ->addValue('street_address', '123 Secret Lane')
      ->execute()->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit my contact',
      'view my contact',
    ];

    $this->expectException(UnauthorizedException::class);
    \Civi\Api4\Address::create()
      ->addValue('contact_id', $userContact)
      ->addValue('location_type_id', 1)
      ->addValue('master_id', $otherAddress)
      ->execute();
  }

  /**
   * Test Contact::update cannot set employer_id to an organization the user cannot view.
   */
  public function testContactCannotSetEmployerToForbiddenOrganization(): void {
    $userContact = $this->createLoggedInUser();
    $otherOrg = Contact::create(FALSE)
      ->addValue('contact_type', 'Organization')
      ->addValue('organization_name', 'Secret Org')
      ->execute()->first()['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit my contact',
      'view my contact',
    ];

    $this->expectException(UnauthorizedException::class);
    Contact::update()
      ->addWhere('id', '=', $userContact)
      ->addValue('employer_id', $otherOrg)
      ->execute();
  }

}

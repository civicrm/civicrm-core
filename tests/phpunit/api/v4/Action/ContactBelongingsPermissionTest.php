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
use Civi\Api4\Contact;
use Civi\Api4\Email;
use Civi\Api4\LocBlock;
use Civi\Api4\Note;
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

}

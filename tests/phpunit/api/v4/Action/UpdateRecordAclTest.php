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
use Civi\Api4\Email;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * A write to an existing contact-delegated record must be authorized against
 * the record's stored owner, never against caller-supplied ownership fields.
 *
 * Otherwise a user permitted to edit their own contact can seize another
 * contact's email/phone/etc (and read it afterwards) by asserting their own
 * contact_id in the update/save values.
 *
 * @group headless
 */
class UpdateRecordAclTest extends Api4TestBase implements TransactionalInterface, HookInterface {

  use \Civi\Test\ACLPermissionTrait;

  /**
   * Restrict contact edit/view access to the given contacts.
   */
  private function restrictToContacts(array $allowedContacts): void {
    $this->allowedContacts = $allowedContacts;
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    \CRM_Utils_Hook::singleton()->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);
  }

  private function ownerOf(int $emailId): int {
    return (int) Email::get(FALSE)
      ->addWhere('id', '=', $emailId)
      ->addSelect('contact_id')
      ->execute()->single()['contact_id'];
  }

  /**
   * Update::_run single-record fast path must not trust submitted contact_id.
   */
  public function testUpdateCannotSeizeForeignRecord(): void {
    $cid = $this->saveTestRecords('Individual', ['records' => 2])->column('id');
    $email = $this->createTestRecord('Email', ['contact_id' => $cid[0], 'email' => 'victim@example.org'])['id'];

    // User may edit contact 1, but not contact 0 (the email's owner).
    $this->restrictToContacts([$cid[1]]);

    $error = NULL;
    try {
      Email::update()
        ->addWhere('id', '=', $email)
        ->addValue('contact_id', $cid[1])
        ->execute();
    }
    catch (UnauthorizedException $e) {
      $error = $e->getMessage();
    }
    $this->assertEquals('ACL check failed', $error);
    $this->assertEquals($cid[0], $this->ownerOf($email), 'Foreign email must not be reassigned.');
  }

  /**
   * Save must apply the same rule for existing records.
   */
  public function testSaveCannotSeizeForeignRecord(): void {
    $cid = $this->saveTestRecords('Individual', ['records' => 2])->column('id');
    $email = $this->createTestRecord('Email', ['contact_id' => $cid[0], 'email' => 'victim@example.org'])['id'];

    $this->restrictToContacts([$cid[1]]);

    $error = NULL;
    try {
      Email::save()
        ->addRecord(['id' => $email, 'contact_id' => $cid[1]])
        ->execute();
    }
    catch (UnauthorizedException $e) {
      $error = $e->getMessage();
    }
    $this->assertEquals('ACL check failed', $error);
    $this->assertEquals($cid[0], $this->ownerOf($email), 'Foreign email must not be reassigned.');
  }

  /**
   * The owner check must not produce false positives: a user may still edit a
   * record on a contact they are permitted to edit, including reassigning it to
   * another contact they may edit.
   */
  public function testOwnerMayStillUpdateAndReassign(): void {
    $cid = $this->saveTestRecords('Individual', ['records' => 2])->column('id');
    $email = $this->createTestRecord('Email', ['contact_id' => $cid[1], 'email' => 'me@example.org'])['id'];

    // Both contacts are editable by the user.
    $this->restrictToContacts([$cid[0], $cid[1]]);

    // Plain field update works.
    Email::update()
      ->addWhere('id', '=', $email)
      ->addValue('email', 'me2@example.org')
      ->execute();
    $this->assertEquals('me2@example.org', Email::get(FALSE)
      ->addWhere('id', '=', $email)->addSelect('email')->execute()->single()['email']);

    // Reassigning between contacts is allowed for users with edit all contacts.
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'edit all contacts'];
    Email::update()
      ->addWhere('id', '=', $email)
      ->addValue('contact_id', $cid[0])
      ->execute();
    $this->assertEquals($cid[0], $this->ownerOf($email));
  }

  /**
   * Even if a user can edit both the original and target contact, reassigning
   * contact belongings requires 'edit all contacts'. Both the stored owner and
   * the proposed changes must be authorized.
   */
  public function testCannotReassignContactWithoutEditAllContacts(): void {
    $cid = $this->saveTestRecords('Individual', ['records' => 2])->column('id');
    $email = $this->createTestRecord('Email', ['contact_id' => $cid[1], 'email' => 'me@example.org'])['id'];

    // Both contacts are editable by the user, but user lacks 'edit all contacts'
    $this->restrictToContacts([$cid[0], $cid[1]]);

    $this->expectException(UnauthorizedException::class);
    Email::update()
      ->addWhere('id', '=', $email)
      ->addValue('contact_id', $cid[0])
      ->execute();
  }

  public function testCannotReassignContactWithoutEditAllContactsViaSave(): void {
    $cid = $this->saveTestRecords('Individual', ['records' => 2])->column('id');
    $email = $this->createTestRecord('Email', ['contact_id' => $cid[1], 'email' => 'me@example.org'])['id'];

    // Both contacts are editable by the user, but user lacks 'edit all contacts'
    $this->restrictToContacts([$cid[0], $cid[1]]);

    $this->expectException(UnauthorizedException::class);
    Email::save()
      ->addRecord(['id' => $email, 'contact_id' => $cid[0]])
      ->execute();
  }

}

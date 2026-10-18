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

use Civi\Api4\Email;
use Civi\Test\ACLPermissionTrait;
use Civi\Test\HeadlessInterface;

/**
 * Test class for APIv3 basic delete and setValue permission checks on contact belongings
 *
 * @package CiviCRM
 * @group headless
 */
class api_v3_ContactBelongingsPermissionTest extends CiviUnitTestCase implements HeadlessInterface {

  use ACLPermissionTrait;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->apply();
  }

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_email', 'civicrm_contact'], TRUE);
    CRM_Utils_Hook::singleton()->reset();
    CRM_Core_Session::singleton()->reset();
    $_REQUEST = $_GET = $_POST = [];
    parent::tearDown();
  }

  /**
   * Test that deleting an email belonging to an inaccessible contact is blocked.
   */
  public function testBasicDeleteContactBelongingUnauthorized(): void {
    $c1 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_1');
    $email = $this->createTestEntity('Email', [
      'contact_id' => $c1['id'],
      'email' => 'test@example.com',
    ], 'email_1');

    $this->createLoggedInUser();
    $this->allowedContacts = [0];
    $this->setPermissions(['access CiviCRM', 'access CiviMail']);
    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('Permission denied to modify contact record');

    civicrm_api3('Email', 'delete', [
      'id' => $email['id'],
      'check_permissions' => 1,
    ]);
  }

  /**
   * Test that deleting an email belonging to an accessible contact succeeds.
   */
  public function testBasicDeleteContactBelongingAuthorized(): void {
    $c1 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_1');
    $email = $this->createTestEntity('Email', [
      'contact_id' => $c1['id'],
      'email' => 'test@example.com',
    ], 'email_1');

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'edit all contacts']);

    $result = civicrm_api3('Email', 'delete', [
      'id' => $email['id'],
      'check_permissions' => 1,
    ]);
    $this->assertEquals(0, $result['is_error']);

    $exists = Email::get(FALSE)->addWhere('id', '=', $email['id'])->execute()->first();
    $this->assertEmpty($exists);
  }

  /**
   * Test that setValue on an email belonging to an inaccessible contact is blocked.
   */
  public function testSetValueContactBelongingUnauthorized(): void {
    $c1 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_1');
    $email = $this->createTestEntity('Email', [
      'contact_id' => $c1['id'],
      'email' => 'test@example.com',
    ], 'email_1');

    $this->createLoggedInUser();
    $this->allowedContacts = [0];
    $this->setPermissions(['access CiviCRM', 'access CiviMail']);
    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('Permission denied to modify contact record');

    civicrm_api3('Email', 'setvalue', [
      'id' => $email['id'],
      'field' => 'email',
      'value' => 'hacked@example.com',
      'check_permissions' => 1,
    ]);
  }

  /**
   * Test that setValue cannot reassign contact_id on an existing contact belonging.
   */
  public function testSetValueContactBelongingCannotReassignContactId(): void {
    $c1 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_1');
    $c2 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_2');
    $email = $this->createTestEntity('Email', [
      'contact_id' => $c1['id'],
      'email' => 'test@example.com',
    ], 'email_1');

    $this->createLoggedInUser();
    $this->allowedContacts = [$c1['id']];
    $this->setPermissions(['access CiviCRM']);
    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('Cannot change the contact_id of an existing Email.');

    civicrm_api3('Email', 'setvalue', [
      'id' => $email['id'],
      'field' => 'contact_id',
      'value' => $c2['id'],
      'check_permissions' => 1,
    ]);
  }

  /**
   * Test that setValue on an inaccessible contact record is blocked.
   */
  public function testSetValueContactUnauthorized(): void {
    $c1 = $this->createTestEntity('Contact', ['contact_type' => 'Individual', 'first_name' => 'Original'], 'contact_1');

    $this->createLoggedInUser();
    $this->allowedContacts = [0];
    $this->setPermissions(['access CiviCRM']);
    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('Permission denied to modify contact record');

    civicrm_api3('Contact', 'setvalue', [
      'id' => $c1['id'],
      'field' => 'first_name',
      'value' => 'Hacked',
      'check_permissions' => 1,
    ]);
  }

}

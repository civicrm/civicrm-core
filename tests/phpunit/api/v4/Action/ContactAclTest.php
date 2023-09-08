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
use Civi\Api4\Email;
use Civi\Api4\LocationType;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ContactAclTest extends Api4TestBase implements TransactionalInterface, HookInterface {

  use \Civi\Test\ACLPermissionTrait;

  public function testBasicContactPermissions(): void {
    $this->createLoggedInUser();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
    ];

    $this->createTestRecord('Contact');

    $result = Contact::get()->execute();
    $this->assertGreaterThan(0, $result->count());

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
    ];

    $this->createTestRecord('Contact');

    $result = Contact::get()->execute();
    $this->assertCount(0, $result);
  }

  public function testContactAclForRelatedEntity() {
    $cid = $this->saveTestRecords('Contact', ['records' => 4])
      ->column('id');
    $email = $this->saveTestRecords('Email', [
      'records' => [
        ['contact_id' => $cid[0], 'email' => '0@test'],
        ['contact_id' => $cid[1], 'email' => '1@test'],
        ['contact_id' => $cid[2], 'email' => '2@test'],
        ['contact_id' => $cid[3], 'email' => '3@test'],
      ],
    ])->column('id');
    // Grant access to all but contact 0
    $this->allowedContacts = array_slice($cid, 1);
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view debug output'];
    \CRM_Utils_Hook::singleton()->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);

    $allowedEmails = Email::get()->setDebug(TRUE)
      ->execute();
    $this->assertCount(3, $allowedEmails);
    $this->assertEquals(array_slice($email, 1), $allowedEmails->column('id'));
    // ACL clause should have been inserted once
    $this->assertEquals(1, substr_count($allowedEmails->debug['sql'][0], 'civicrm_acl_contact_cache'));
  }

  public function testContactAclClauseDedupe() {
    $cid = $this->saveTestRecords('Contact', ['records' => 4])
      ->column('id');
    $locationType = $this->createTestRecord('LocationType');
    $email = $this->saveTestRecords('Email', [
      'records' => [
        ['contact_id' => $cid[0], 'email' => '0@test'],
        ['contact_id' => $cid[1], 'email' => '1@test'],
        ['contact_id' => $cid[2], 'email' => '2@test'],
        ['contact_id' => $cid[3], 'email' => '3@test'],
      ],
      'defaults' => ['location_type_id' => $locationType['id']],
    ])->column('id');
    // Grant access to all but contact 0
    $this->allowedContacts = array_slice($cid, 1);
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view debug output'];
    \CRM_Utils_Hook::singleton()->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);

    // Acl clause is added only once and shared by the joined entities
    $contactGet = Contact::get()->setDebug(TRUE)
      ->addSelect('email.id')
      ->addJoin('Email AS email', 'LEFT', ['email.contact_id', '=', 'id'])
      ->execute();
    $this->assertCount(3, $contactGet);
    $this->assertEquals(array_slice($email, 1), $contactGet->column('email.id'));
    // ACL clause should have been inserted once
    $this->assertEquals(1, substr_count($contactGet->debug['sql'][0], 'civicrm_acl_contact_cache'));

    // Joining through another entity does not allow acl bypass
    $locationTypeGet = LocationType::get()->setDebug(TRUE)
      ->addSelect('email.id')
      ->addJoin('Email AS email', 'INNER', ['email.location_type_id', '=', 'id'])
      ->execute();
    $this->assertCount(3, $locationTypeGet);
    $this->assertEquals(array_slice($email, 1), $locationTypeGet->column('email.id'));
    // ACL clause should have been inserted once
    $this->assertEquals(1, substr_count($locationTypeGet->debug['sql'][0], 'civicrm_acl_contact_cache'));
  }

}

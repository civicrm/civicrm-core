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
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ContactIsDeletedTest extends Api4TestBase implements TransactionalInterface {

  /**
   * This locks in a fix to ensure that if a user doesn't have permission to view the is_deleted field that doesn't hard fail if that field happens to be in an APIv4 call.
   */
  public function testIsDeletedPermission(): void {
    $contact = $this->createLoggedInUser();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts'];
    $originalQuery = civicrm_api4('Contact', 'get', [
      'checkPermissions' => TRUE,
      'select' => ['id', 'display_name', 'is_deleted'],
      'where' => [['first_name', '=', 'phoney']],
    ]);

    try {
      $isDeletedQuery = civicrm_api4('Contact', 'get', [
        'checkPermissions' => TRUE,
        'select' => ['id', 'display_name'],
        'where' => [['first_name', '=', 'phoney'], ['is_deleted', '=', 0]],
      ]);
      $this->assertEquals(count($originalQuery), count($isDeletedQuery));
    }
    catch (\API_Exception $e) {
      $this->fail('An Exception Should not have been raised');
    }
    try {
      $isDeletedJoinTest = civicrm_api4('Email', 'get', [
        'checkPermissions' => TRUE,
        'where' => [['contact_id.first_name', '=', 'phoney'], ['contact_id.is_deleted', '=', 0]],
      ]);
    }
    catch (\API_Exception $e) {
      $this->fail('An Exception Should not have been raised');
    }
  }

  public function testIsDeletedDefault() {
    $lastName = uniqid(__FUNCTION__);
    $c1 = $this->createTestRecord('Contact', ['last_name' => $lastName]);
    $c2 = $this->createTestRecord('Contact', ['last_name' => $lastName, 'is_deleted' => TRUE]);
    $this->createTestRecord('Email', ['contact_id' => $c1['id'], 'email' => "$lastName@example.com"]);
    $this->createTestRecord('Email', ['contact_id' => $c2['id'], 'email' => "$lastName@example.com"]);

    // By default, deleted contacts are not shown, so expect only one record
    $simpleGet = Contact::get(FALSE)->addWhere('last_name', '=', $lastName)
      ->execute()->single();
    $this->assertEquals($c1['id'], $simpleGet['id']);

    $getDeleted = Contact::get(FALSE)->addWhere('last_name', '=', $lastName)
      ->addWhere('is_deleted', '=', TRUE)
      ->execute()->single();
    $this->assertEquals($c2['id'], $getDeleted['id']);

    $getAll = Contact::get(FALSE)->addWhere('last_name', '=', $lastName)
      ->addWhere('is_deleted', 'IN', [TRUE, FALSE])
      ->execute();
    $this->assertCount(2, $getAll);

    $emailGet = Email::get(FALSE)
      ->addJoin('Contact AS contact', 'INNER')
      ->addWhere('contact.last_name', '=', $lastName)
      ->execute()->single();
    $this->assertEquals($c1['id'], $emailGet['contact_id']);

    // Adding 'contact.is_deleted' to the ON clause overrides the default
    $emailGetDeleted = Email::get(FALSE)
      ->addJoin('Contact AS contact', 'INNER', ['contact.is_deleted', '=', TRUE])
      ->addWhere('contact.last_name', '=', $lastName)
      ->execute()->single();
    $this->assertEquals($c2['id'], $emailGetDeleted['contact_id']);

    // Adding 'contact.is_deleted' to the ON clause overrides the default
    $emailGetAll = Email::get(FALSE)
      ->addJoin('Contact AS contact', 'INNER', ['contact.is_deleted', 'IN', [TRUE, FALSE]])
      ->addWhere('contact.last_name', '=', $lastName)
      ->execute();
    $this->assertCount(2, $emailGetAll);

    // Adding 'contact.is_deleted' to the WHERE clause also overrides the default
    $emailGetAll = Email::get(FALSE)
      ->addJoin('Contact AS contact', 'INNER')
      ->addWhere('contact.last_name', '=', $lastName)
      ->addWhere('contact.is_deleted', 'IN', [TRUE, FALSE])
      ->execute();
    $this->assertCount(2, $emailGetAll);
  }

}

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
use Civi\Test\CiviEnvBuilder;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ContactIsDeletedTest extends Api4TestBase implements TransactionalInterface {

  public function setUpHeadless(): CiviEnvBuilder {
    $relatedTables = [
      'civicrm_address',
      'civicrm_email',
      'civicrm_phone',
      'civicrm_openid',
      'civicrm_im',
      'civicrm_website',
      'civicrm_activity',
      'civicrm_activity_contact',
    ];
    $this->cleanup(['tablesToTruncate' => $relatedTables]);
    $displayNameFormat = '{contact.first_name}{ }{contact.last_name}';
    \Civi::settings()->set('display_name_format', $displayNameFormat);

    return parent::setUpHeadless();
  }

  /**
   * This locks in a fix to ensure that if a user doesn't have permission to view the is_deleted field that doesn't hard fail if that field happens to be in an APIv4 call.
   */
  public function testIsDeletedPermission(): void {
    $contact = $this->createLoggedInUser();
    $this->createTestRecord('Individual', ['first_name' => 'phoney']);
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts'];
    $originalQuery = civicrm_api4('Contact', 'get', [
      'checkPermissions' => TRUE,
      'select' => ['id', 'display_name', 'is_deleted'],
      'where' => [['first_name', '=', 'phoney']],
    ]);
    $this->assertGreaterThan(0, $originalQuery->countFetched());

    $isDeletedQuery = civicrm_api4('Contact', 'get', [
      'checkPermissions' => TRUE,
      'select' => ['id', 'display_name'],
      'where' => [['first_name', '=', 'phoney'], ['is_deleted', '=', 0]],
    ]);
    $this->assertEquals(count($originalQuery), count($isDeletedQuery));

    $isDeletedQuery = civicrm_api4('Individual', 'get', [
      'checkPermissions' => TRUE,
      'select' => ['id', 'display_name'],
      'where' => [['first_name', '=', 'phoney'], ['is_deleted', '=', 0]],
    ]);
    $this->assertEquals(count($originalQuery), count($isDeletedQuery));

    civicrm_api4('Email', 'get', [
      'checkPermissions' => TRUE,
      'where' => [['contact_id.first_name', '=', 'phoney'], ['contact_id.is_deleted', '=', 0]],
    ]);
  }

}

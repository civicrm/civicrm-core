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
use Civi\Api4\Activity;
use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Api4\Dashboard;
use Civi\Api4\Email;
use Civi\Api4\Group;
use Civi\Api4\Individual;
use Civi\Api4\IM;
use Civi\Api4\LocationType;
use Civi\Api4\Organization;
use Civi\Api4\Phone;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ContactAclTest extends Api4TestBase implements TransactionalInterface, HookInterface {

  use \Civi\Test\ACLPermissionTrait;

  public function tearDown(): void {
    \CRM_Utils_Hook::singleton()->reset();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    parent::tearDown();
  }

  public function testPermissionInfo(): void {
    foreach (['Contact', 'Individual', 'Organization', 'Household'] as $entity) {
      $apiClass = '\Civi\Api4\\' . $entity;
      $permissions = $apiClass::permissions();
      $this->assertEquals([], $permissions['get']);
      $this->assertContains('add contacts', $permissions['create']);
      $this->assertContains('delete contacts', $permissions['delete']);
      $this->assertContains('merge duplicate contacts', $permissions['merge']);
    }
  }

  public function testBasicContactPermissions(): void {
    $this->createLoggedInUser();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
    ];

    $this->createTestRecord('Individual');

    $result = Contact::get()->execute();
    $this->assertGreaterThan(0, $result->count());

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
    ];

    $this->createTestRecord('Contact');

    $result = Contact::get()->execute();
    $this->assertCount(0, $result);

    $result = Individual::get()->execute();
    $this->assertCount(0, $result);

    $result = Organization::get()->execute();
    $this->assertCount(0, $result);
  }

  public function testContactAclForRelatedEntity(): void {
    $cid = $this->saveTestRecords('Individual', ['records' => 4])
      ->column('id');

    $email = $this->saveTestRecords('Email', [
      'records' => [
        ['contact_id' => $cid[0], 'email' => '0@test'],
        ['contact_id' => $cid[1], 'email' => '1@test'],
        ['contact_id' => $cid[2], 'email' => '2@test'],
        ['contact_id' => $cid[3], 'email' => '3@test'],
      ],
    ])->column('id');

    $activity = $this->saveTestRecords('Activity', [
      'records' => [
        ['source_contact_id' => $cid[0], 'subject' => '0test'],
        ['source_contact_id' => $cid[1], 'subject' => '1test'],
        ['source_contact_id' => $cid[2], 'subject' => '2test'],
        ['source_contact_id' => $cid[3], 'subject' => '3test'],
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

    $allowedActivities = Activity::get()->setDebug(TRUE)
      ->execute();
    $this->assertCount(3, $allowedActivities);
    $this->assertEquals(array_slice($activity, 1), $allowedActivities->column('id'));
    // ACL clause should have been inserted once
    $this->assertEquals(1, substr_count($allowedActivities->debug['sql'][0], 'civicrm_acl_contact_cache'));

    $error = NULL;
    try {
      Phone::create()
        ->setValues(['contact_id' => $cid[0], 'phone' => '1234567890'])
        ->execute();
    }
    catch (\Exception $e) {
      $error = $e->getMessage();
    }
    $this->assertEquals('ACL check failed', $error);

    $result = Phone::create()
      ->setValues(['contact_id' => $cid[1], 'phone' => '1234567890'])
      ->execute();
    $this->assertCount(1, $result);

    $result = Phone::get()
      ->addWhere('contact_id', '=', $cid[1])
      ->execute();
    $this->assertCount(1, $result);
  }

  /**
   * Tests that a user with no permissions cannot get addresses, emails, phones, and IMs with NULL contact_id.
   */
  public function testNullContactIdGetWithoutPermissions(): void {
    $contact = $this->createTestEntity('Contact', [
      'first_name' => 'Hidden',
      'last_name' => 'Contact',
    ], 'contact');

    $nullAddress = $this->createTestEntity('Address', [
      'street_address' => '123 Main St',
      'contact_id' => NULL,
    ], 'null_address');
    $contactAddress = $this->createTestEntity('Address', [
      'street_address' => '456 Elm St',
      'contact_id' => $contact['id'],
    ], 'contact_address');

    $nullEmail = $this->createTestEntity('Email', [
      'email' => 'null@example.com',
      'contact_id' => NULL,
    ], 'null_email');
    $contactEmail = $this->createTestEntity('Email', [
      'email' => 'contact@example.com',
      'contact_id' => $contact['id'],
    ], 'contact_email');

    $nullPhone = $this->createTestEntity('Phone', [
      'phone' => '1234567',
      'contact_id' => NULL,
    ], 'null_phone');
    $contactPhone = $this->createTestEntity('Phone', [
      'phone' => '7654321',
      'contact_id' => $contact['id'],
    ], 'contact_phone');

    $nullIM = $this->createTestEntity('IM', [
      'name' => 'null_user',
      'contact_id' => NULL,
    ], 'null_im');
    $contactIM = $this->createTestEntity('IM', [
      'name' => 'contact_user',
      'contact_id' => $contact['id'],
    ], 'contact_im');

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [];

    $addresses = Address::get()
      ->addWhere('id', 'IN', [$nullAddress['id'], $contactAddress['id']])
      ->execute();
    $this->assertCount(0, $addresses);

    $emails = Email::get()
      ->addWhere('id', 'IN', [$nullEmail['id'], $contactEmail['id']])
      ->execute();
    $this->assertCount(0, $emails);

    $phones = Phone::get()
      ->addWhere('id', 'IN', [$nullPhone['id'], $contactPhone['id']])
      ->execute();
    $this->assertCount(0, $phones);

    $ims = IM::get()
      ->addWhere('id', 'IN', [$nullIM['id'], $contactIM['id']])
      ->execute();
    $this->assertCount(0, $ims);
  }

  public function testContactAclClauseDedupe(): void {
    $cid = $this->saveTestRecords('Individual', ['records' => 4])
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

    // Should now have access to 3 contacts
    $this->assertCount(3, Individual::get()->execute());

    // Acl clause is added only once and shared by the joined entities
    $contactGet = Contact::get()->setDebug(TRUE)
      ->addSelect('email.id')
      ->addJoin('Email AS email', 'LEFT', ['email.contact_id', '=', 'id'])
      ->execute();
    $this->assertCount(3, $contactGet);
    $this->assertEquals(array_slice($email, 1), $contactGet->column('email.id'));
    // ACL clause should have been inserted once
    $this->assertEquals(1, substr_count($contactGet->debug['sql'][0], 'civicrm_acl_contact_cache'));

    // Same should work with Individual api as Contact
    $contactGet = Individual::get()->setDebug(TRUE)
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

  public function testContactAclBypassViaDashboardJoin(): void {
    $firstDash = $this->createTestRecord('Dashboard')['id'];
    $indId = 0;
    $allInds = [];
    while ($indId < $firstDash + 4 || count($allInds) < 4) {
      $indId = $this->createTestRecord('Individual')['id'];
      $allInds[] = $indId;
    }
    $cid = array_slice($allInds, -4);

    $dashId = $firstDash;
    while ($dashId < max($cid)) {
      $dashId = $this->createTestRecord('Dashboard')['id'];
    }

    // Grant access to all but contact 0
    $this->allowedContacts = array_slice($cid, 1);
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view debug output'];
    \CRM_Utils_Hook::singleton()->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);

    // Direct query on Contact should only return the 3 allowed contacts
    $this->assertCount(3, Contact::get()->execute());

    // Joining through Dashboard should NOT bypass contact ACLs
    $dashboardGet = Dashboard::get()
      ->setCheckPermissions(TRUE)
      ->addSelect('id', 'c.id', 'c.display_name')
      ->addJoin('Contact AS c', 'INNER', ['c.id', '=', 'id'])
      ->addWhere('id', 'IN', $cid)
      ->execute();

    $this->assertCount(3, $dashboardGet);
    $this->assertEquals($this->allowedContacts, $dashboardGet->column('c.id'));
  }

  public function testActivityAclBypassViaGroupJoin(): void {
    $firstGroup = $this->createTestRecord('Group')['id'];
    $actId = 0;
    $allActs = [];
    while ($actId < $firstGroup + 4 || count($allActs) < 4) {
      $actId = $this->createTestRecord('Activity')['id'];
      $allActs[] = $actId;
    }
    $activities = array_slice($allActs, -4);

    $groupId = $firstGroup;
    while ($groupId < max($activities)) {
      $groupId = $this->createTestRecord('Group')['id'];
    }

    $allowedActivities = array_slice($activities, 0, 2);
    \CRM_Utils_Hook::singleton()->setHook('civicrm_selectWhereClause', function ($entity, &$clauses) use ($allowedActivities, $activities) {
      $entityName = is_object($entity) ? \CRM_Core_DAO_AllCoreTables::getEntityNameForClass(get_class($entity)) : $entity;
      if ($entityName === 'Activity') {
        $clauses['id'][] = 'IN (' . implode(',', $allowedActivities) . ')';
      }
      elseif ($entityName === 'Group') {
        $clauses['id'][] = 'IN (' . implode(',', $activities) . ')';
      }
    });

    $actGet = Activity::get()
      ->setCheckPermissions(TRUE)
      ->addSelect('id')
      ->addWhere('id', 'IN', $activities)
      ->execute();
    $this->assertCount(2, $actGet);
    $this->assertEquals($allowedActivities, $actGet->column('id'));

    $groupGet = Group::get()
      ->setCheckPermissions(TRUE)
      ->addSelect('act.id', 'act.subject')
      ->addJoin('Activity AS act', 'INNER', ['act.id', '=', 'id'])
      ->addWhere('id', 'IN', $activities)
      ->execute();
    $this->assertCount(2, $groupGet);
    $this->assertEquals($allowedActivities, $groupGet->column('act.id'));
  }

  public function testAclCacheBuildDoubleChecksRetrieveUnderLock(): void {
    $userID = $this->createLoggedInUser();

    // 1. Setup mock Lock
    $mockLock = $this->createMock(\Civi\Core\Lock\LockInterface::class);
    $mockLock->expects($this->once())
      ->method('release');

    // Mock LockManager to return our custom mock Lock and populate DB when acquire is called
    $mockLockManager = $this->createMock(\Civi\Core\Lock\LockManager::class);
    $mockLockManager->expects($this->once())
      ->method('acquire')
      ->with("data.core.acl.{$userID}")
      ->willReturnCallback(function () use ($userID, $mockLock) {
        // Manually insert a mock ACL ID (999) to simulate a concurrent thread rebuilding the cache while we waited for the lock
        \CRM_Core_DAO::executeQuery("INSERT INTO civicrm_acl_cache (acl_id, contact_id) VALUES (999, %1)", [
          1 => [$userID, 'Integer'],
        ]);
        return $mockLock;
      });

    // Swap lockManager in Civi::$statics
    $originalLockManager = \Civi::$statics['Civi\Core\Container']['boot']['lockManager'];
    \Civi::$statics['Civi\Core\Container']['boot']['lockManager'] = $mockLockManager;

    try {
      // Clear caches
      \CRM_ACL_BAO_Cache::resetCache();
      \CRM_ACL_BAO_Cache::$_cache = NULL;

      // Call build()
      $acls = \CRM_ACL_BAO_Cache::build($userID);

      // Verify that build() returned the double-checked database state [999 => 1]
      // instead of rebuilding it (which would have returned the actual user's ACLs)
      $this->assertEquals([999 => 1], $acls);
    }
    finally {
      // Restore lockManager
      \Civi::$statics['Civi\Core\Container']['boot']['lockManager'] = $originalLockManager;
    }
  }

}

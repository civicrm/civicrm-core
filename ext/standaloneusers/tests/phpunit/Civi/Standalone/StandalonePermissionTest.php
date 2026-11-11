<?php

namespace Civi\Standalone;

use Civi\Api4\Role;
use Civi\Test\EntityTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class StandalonePermissionTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  use EntityTrait;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testPermissionEmails(): void {
    $perm = new \CRM_Core_Permission_Standalone();

    $editorRole = $this->createTestEntity('Role', [
      'name' => uniqid('test_editor_'),
      'label' => 'Test Editor',
      'permissions' => ['edit all contacts'],
    ], 'editor_role');

    $managerRole = $this->createTestEntity('Role', [
      'name' => uniqid('test_manager_'),
      'label' => 'Test Manager',
      'permissions' => ['edit all contacts'],
    ], 'manager_role');

    $inactiveRole = $this->createTestEntity('Role', [
      'name' => uniqid('test_inactive_role_'),
      'label' => 'Test Inactive Role',
      'permissions' => ['edit all contacts'],
      'is_active' => FALSE,
    ], 'inactive_role');

    $adminRole = Role::get(FALSE)->addWhere('name', '=', 'admin')->execute()->first()
      ?? $this->createTestEntity('Role', [
        'name' => 'admin',
        'label' => 'Admin',
        'permissions' => ['all CiviCRM permissions and ACLs'],
      ], 'admin_role');

    $aliceEmail = uniqid('alice_') . '@example.com';
    $aliceSecondaryEmail = uniqid('alice_sec_') . '@example.com';
    $bobEmail = uniqid('bob_') . '@example.com';
    $charlieEmail = uniqid('charlie_') . '@example.com';
    $danaEmail = uniqid('dana_') . '@example.com';
    $eveEmail = uniqid('eve_') . '@example.com';
    $frankEmail = uniqid('frank_') . '@example.com';

    // Contact 1 (Alice): has 2 roles granting the permission, and a secondary email.
    $contact1 = $this->createTestEntity('Contact', [
      'first_name' => 'Alice',
      'last_name' => 'Tester',
      'contact_type' => 'Individual',
    ], 'alice');
    $this->createTestEntity('Email', [
      'contact_id' => $contact1['id'],
      'email' => $aliceEmail,
      'is_primary' => 1,
    ], 'alice_primary_email');
    $this->createTestEntity('Email', [
      'contact_id' => $contact1['id'],
      'email' => $aliceSecondaryEmail,
      'is_primary' => 0,
    ], 'alice_secondary_email');

    // Contact 2 (Bob): admin role.
    $contact2 = $this->createTestEntity('Contact', [
      'first_name' => 'Bob',
      'last_name' => 'Admin',
      'contact_type' => 'Individual',
    ], 'bob');
    $this->createTestEntity('Email', [
      'contact_id' => $contact2['id'],
      'email' => $bobEmail,
      'is_primary' => 1,
    ], 'bob_email');

    // Contact 3 (Charlie): deceased contact.
    $contact3 = $this->createTestEntity('Contact', [
      'first_name' => 'Charlie',
      'last_name' => 'Deceased',
      'contact_type' => 'Individual',
      'is_deceased' => 1,
    ], 'charlie');
    $this->createTestEntity('Email', [
      'contact_id' => $contact3['id'],
      'email' => $charlieEmail,
      'is_primary' => 1,
    ], 'charlie_email');

    // Contact 4 (Dana): inactive user.
    $contact4 = $this->createTestEntity('Contact', [
      'first_name' => 'Dana',
      'last_name' => 'Inactive',
      'contact_type' => 'Individual',
    ], 'dana');
    $this->createTestEntity('Email', [
      'contact_id' => $contact4['id'],
      'email' => $danaEmail,
      'is_primary' => 1,
    ], 'dana_email');

    // Contact 5 (Eve): deleted (trashed) contact.
    $contact5 = $this->createTestEntity('Contact', [
      'first_name' => 'Eve',
      'last_name' => 'Deleted',
      'contact_type' => 'Individual',
      'is_deleted' => 1,
    ], 'eve');
    $this->createTestEntity('Email', [
      'contact_id' => $contact5['id'],
      'email' => $eveEmail,
      'is_primary' => 1,
    ], 'eve_email');

    // Contact 6 (Frank): assigned only to an inactive role.
    $contact6 = $this->createTestEntity('Contact', [
      'first_name' => 'Frank',
      'last_name' => 'InactiveRole',
      'contact_type' => 'Individual',
    ], 'frank');
    $this->createTestEntity('Email', [
      'contact_id' => $contact6['id'],
      'email' => $frankEmail,
      'is_primary' => 1,
    ], 'frank_email');

    $this->createTestEntity('User', [
      'username' => uniqid('alice_user_'),
      'contact_id' => $contact1['id'],
      'roles' => [$editorRole['id'], $managerRole['id']],
      'is_active' => TRUE,
    ], 'alice_user');

    $this->createTestEntity('User', [
      'username' => uniqid('bob_user_'),
      'contact_id' => $contact2['id'],
      'roles' => [$adminRole['id']],
      'is_active' => TRUE,
    ], 'bob_user');

    $this->createTestEntity('User', [
      'username' => uniqid('charlie_user_'),
      'contact_id' => $contact3['id'],
      'roles' => [$editorRole['id']],
      'is_active' => TRUE,
    ], 'charlie_user');

    $this->createTestEntity('User', [
      'username' => uniqid('dana_user_'),
      'contact_id' => $contact4['id'],
      'roles' => [$editorRole['id']],
      'is_active' => FALSE,
    ], 'dana_user');

    $this->createTestEntity('User', [
      'username' => uniqid('eve_user_'),
      'contact_id' => $contact5['id'],
      'roles' => [$editorRole['id']],
      'is_active' => TRUE,
    ], 'eve_user');

    $this->createTestEntity('User', [
      'username' => uniqid('frank_user_'),
      'contact_id' => $contact6['id'],
      'roles' => [$inactiveRole['id']],
      'is_active' => TRUE,
    ], 'frank_user');

    // Alice (editor + manager, no duplicates, primary email only) + Bob (admin)
    $emails = array_filter(explode(', ', $perm->permissionEmails('edit all contacts')));
    sort($emails);
    $this->assertSame([$aliceEmail, $bobEmail], array_values($emails));

    // Test implied permission (view all contacts is implied by edit all contacts)
    $impliedEmails = array_filter(explode(', ', $perm->permissionEmails('view all contacts')));
    sort($impliedEmails);
    $this->assertSame([$aliceEmail, $bobEmail], array_values($impliedEmails));

    // Test permission not granted to editor (only Bob with admin has it)
    $otherEmails = array_filter(explode(', ', $perm->permissionEmails('administer reserved groups')));
    sort($otherEmails);
    $this->assertSame([$bobEmail], array_values($otherEmails));

    // Test always deny
    $this->assertSame('', $perm->permissionEmails(\CRM_Core_Permission::ALWAYS_DENY_PERMISSION));
  }

}

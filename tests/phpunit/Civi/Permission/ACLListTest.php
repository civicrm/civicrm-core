<?php
declare(strict_types = 1);

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */
namespace Civi\Permission;

use Civi\Api4\Contact;
use Civi\Test\ACLPermissionTrait;
use CRM_Contact_BAO_Contact_Permission as ContactPermission;
use CRM_Core_Permission as Permission;

/**
 * Tests the ID list-based contact permission functions.
 *
 * @see \CRM_Contact_BAO_Contact_Permission::allowList()
 * @see \CRM_Contact_BAO_Contact_Permission::relationshipList()
 *
 * @group headless
 */
class ACLListTest extends \CiviUnitTestCase {

  use ACLPermissionTrait;

  protected $_apiversion = 4;

  public function tearDown(): void {
    \Civi::settings()->revert('secondDegRelPermissions');
    $this->cleanUpAfterACLs();
    $this->quickCleanup(['civicrm_relationship', 'civicrm_relationship_cache']);
    parent::tearDown();
  }

  /**
   * Test the 'view all contacts' permission.
   */
  public function testViewAllPermission(): void {
    $contacts = $this->createScenarioPlain();

    // NULL means 'all permissions' in the unit test environment.
    $this->setPermissions(NULL);
    $this->assertSame($contacts, $this->getSortedAllowList($contacts), "Contacts should be viewable with all permissions");

    $this->setPermissions(['view all contacts']);
    $this->assertSame($contacts, $this->getSortedAllowList($contacts, Permission::VIEW), "Contacts should be viewable when 'view all contacts'");

    $this->setPermissions(['edit all contacts']);
    $this->assertSame($contacts, $this->getSortedAllowList($contacts, Permission::VIEW), "Contacts should be viewable when 'edit all contacts'");

    $this->setPermissions([]);
    $this->assertSame([], $this->getSortedAllowList($contacts), "Contacts should NOT be viewable when 'view all contacts' is not set");
  }

  /**
   * Test the 'edit all contacts' permission.
   */
  public function testEditAllPermission(): void {
    $contacts = $this->createScenarioPlain();

    $this->setPermissions(['edit all contacts']);
    $this->assertSame($contacts, $this->getSortedAllowList($contacts, Permission::EDIT), "Contacts should be editable when 'edit all contacts'");

    $this->setPermissions([]);
    $this->assertSame([], $this->getSortedAllowList($contacts), "Contacts should NOT be viewable when 'edit all contacts' is not set");
  }

  /**
   * Test that deleted contacts are excluded without 'access deleted contacts'.
   *
   * @throws \CRM_Core_Exception
   */
  public function testViewEditDeleted(): void {
    $contacts = $this->createScenarioPlain();
    $deletedContactID = $contacts[2];
    Contact::update(FALSE)
      ->addWhere('id', '=', $deletedContactID)
      ->addValue('is_deleted', TRUE)
      ->execute();

    $this->setPermissions(['edit all contacts', 'view all contacts']);
    $result = $this->getSortedAllowList($contacts, Permission::EDIT);
    $this->assertNotContains($deletedContactID, $result, 'Deleted contacts should be excluded');
    $this->assertCount(count($contacts) - 1, $result, 'Only deleted contacts should be excluded');
  }

  /**
   * Test access based on relationships.
   *
   * The relationship graph is described on createScenarioRelations().
   *
   * @throws \CRM_Core_Exception
   */
  public function testPermissionByRelation(): void {
    $contacts = $this->createScenarioRelations();
    $this->setPermissions([]);

    \Civi::settings()->set('secondDegRelPermissions', FALSE);
    foreach ([Permission::VIEW => 'View', Permission::EDIT => 'Edit'] as $permission => $label) {
      $isView = $permission === Permission::VIEW;
      $this->assertAllowedContacts($contacts, $permission, [
        1 => TRUE,
        // View permission (b_a).
        5 => $isView,
        // Edit permission (a_b).
        8 => TRUE,
      ], "$label (first degree)");
    }

    \Civi::settings()->set('secondDegRelPermissions', TRUE);
    foreach ([Permission::VIEW => 'View', Permission::EDIT => 'Edit'] as $permission => $label) {
      $isView = $permission === Permission::VIEW;
      $this->assertAllowedContacts($contacts, $permission, [
        1 => TRUE,
        // Edit then edit -> edit.
        2 => TRUE,
        5 => $isView,
        // View then edit -> view.
        6 => $isView,
        // View then view -> view.
        7 => $isView,
        8 => TRUE,
        // Edit then view -> view.
        9 => $isView,
      ], "$label (second degree)");
    }
  }

  /**
   * A permissioned relationship grants access only between its start and end date.
   */
  public function testPermissionByRelationDates(): void {
    $contacts = $this->createScenarioPlain();
    $dates = [
      1 => ['end_date' => '-1 day'],
      2 => ['start_date' => '+1 day'],
      3 => ['start_date' => '-1 year', 'end_date' => '+1 year'],
      4 => ['start_date' => 'today', 'end_date' => 'today'],
      5 => [],
      // Second degree, reached through the expired relationship to contact 1.
      6 => [],
    ];
    foreach ($dates as $index => $range) {
      $this->createTestEntity('Relationship', [
        'relationship_type_id:name' => 'Sibling of',
        'contact_id_a' => $contacts[$index === 6 ? 1 : 0],
        'contact_id_b' => $contacts[$index],
        'is_permission_a_b' => 1,
        'is_active' => TRUE,
      ] + array_map(fn($date) => date('Y-m-d', strtotime($date)), $range), "dates-$index");
    }
    $this->setPermissions([]);

    foreach ([FALSE, TRUE] as $secondDegree) {
      \Civi::settings()->set('secondDegRelPermissions', $secondDegree);
      $this->assertAllowedContacts($contacts, Permission::EDIT, [3 => TRUE, 4 => TRUE, 5 => TRUE], 'Edit');
    }
  }

  /**
   * Test access based on ACL.
   */
  public function testPermissionByACL(): void {
    $contacts = $this->createScenarioPlain();
    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);
    $this->allowedContacts = [$contacts[0], $contacts[1], $contacts[4]];
    $this->setPermissions([]);

    $this->assertAllowedContacts($contacts, Permission::VIEW, [0 => TRUE, 1 => TRUE, 4 => TRUE], 'ACL');
  }

  /**
   * Test access with a mix of ACL and relationship.
   */
  public function testPermissionACLvsRelationship(): void {
    $contacts = $this->createScenarioRelations();
    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);
    $this->allowedContacts = [$contacts[0], $contacts[1], $contacts[4]];
    $this->setPermissions([]);
    \Civi::settings()->set('secondDegRelPermissions', TRUE);

    $result = $this->getSortedAllowList($contacts);
    $this->assertContains($contacts[0], $result, 'User[0] should have an ACL permission on contact[0].');
    $this->assertContains($contacts[1], $result, 'User[0] should have an ACL permission on contact[1].');
    $this->assertContains($contacts[2], $result, 'User[0] should have a second degree relationship permission on contact[2].');
    $this->assertNotContains($contacts[3], $result, 'User[0] should NOT have an ACL permission on contact[3].');
    $this->assertContains($contacts[4], $result, 'User[0] should have an ACL permission on contact[4].');
  }

  /**
   * A contact allowed only by relationship must not be dropped when its array key equals an ACL-allowed contact ID.
   */
  public function testAllowListKeyMatchingAllowedContactID(): void {
    $contacts = $this->createScenarioRelations();
    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);
    $this->setPermissions([]);
    \Civi::settings()->set('secondDegRelPermissions', FALSE);
    // contacts[4] is allowed by ACL only, contacts[8] by relationship only.
    $this->allowedContacts = [$contacts[4]];

    $result = ContactPermission::allowList([0 => $contacts[4], $contacts[4] => $contacts[8]]);
    sort($result);
    $this->assertSame($this->sorted([$contacts[4], $contacts[8]]), $result);
  }

  /**
   * Test that allowList() prevents any ACL or relationship privilege escalation.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAllowListNoAclEscalation(): void {
    $contacts = $this->createScenarioRelations();
    $unrelatedContactID = $this->createTestEntity('Contact', [
      'first_name' => 'Completely',
      'last_name' => 'Unauthorized',
      'contact_type' => 'Individual',
    ], 'unrelated')['id'];

    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);
    $this->setPermissions([]);
    \Civi::settings()->set('secondDegRelPermissions', FALSE);
    $this->allowedContacts = [
      Permission::VIEW => [$contacts[4]],
      Permission::EDIT => [],
    ];

    // With secondDegRelPermissions off:
    // contacts[4] has VIEW ACL only, no relationship.
    // contacts[5] has VIEW relationship only (is_permission_b_a = 2), no ACL.
    // contacts[8] has EDIT relationship (is_permission_a_b = 1), no ACL.
    // contacts[3] and $unrelatedContactID have neither.
    $checkContacts = [
      $contacts[4],
      $contacts[5],
      $contacts[8],
      $contacts[3],
      $unrelatedContactID,
    ];

    $this->assertSame(
      $this->sorted([$contacts[4], $contacts[5], $contacts[8]]),
      $this->getSortedAllowList($checkContacts, Permission::VIEW)
    );

    $this->assertSame(
      [$contacts[8]],
      ContactPermission::allowList($checkContacts, Permission::EDIT),
      'Only the contact with an EDIT relationship should be returned; VIEW must not escalate to EDIT.'
    );

    $this->assertSame([], ContactPermission::allowList([$contacts[3], $unrelatedContactID], Permission::VIEW));
    $this->assertSame([], ContactPermission::allowList([$unrelatedContactID], Permission::EDIT));

    // Non-sequential keys must not be confused with contact IDs.
    $associativeInput = [
      42 => $contacts[4],
      99 => $contacts[3],
      105 => $contacts[5],
      999 => $unrelatedContactID,
    ];
    $this->assertSame(
      $this->sorted([$contacts[4], $contacts[5]]),
      $this->getSortedAllowList($associativeInput, Permission::VIEW)
    );
  }

  /**
   * Test that allowList() and allow() agree across permission combinations.
   */
  public function testPermissionCompare(): void {
    $contacts = $this->createScenarioRelations();
    $contactIndex = array_flip($contacts);
    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);
    $this->allowedContacts = [$contacts[0], $contacts[1], $contacts[4]];
    \Civi::settings()->set('secondDegRelPermissions', TRUE);

    $userPermissionOptions = [
      'all' => NULL,
      'none' => [],
      'view all' => ['view all contacts'],
      'edit all' => ['edit all contacts'],
      'view and edit all' => ['view all contacts', 'edit all contacts'],
    ];

    foreach ([Permission::VIEW => 'View', Permission::EDIT => 'Edit'] as $permission => $label) {
      foreach ($userPermissionOptions as $permissionsLabel => $userPermissions) {
        $contactRange = $contacts;
        if ($userPermissions === []) {
          // Without any permission the user's own contact is handled differently by allow().
          unset($contactRange[0]);
        }
        $this->setPermissions($userPermissions);

        $listResult = ContactPermission::allowList($contactRange, $permission);
        $this->assertSame([], array_diff($listResult, $contactRange), 'allowList() should return a subset of the contacts.');

        foreach ($contactRange as $contactID) {
          $isListed = in_array($contactID, $listResult, TRUE);
          $this->assertSame(
            $isListed,
            ContactPermission::allow($contactID, $permission),
            sprintf('allow() and allowList() disagree on %s access for contact[%d] with "%s" permissions.', $label, $contactIndex[$contactID], $permissionsLabel)
          );
        }
      }
    }
  }

  /**
   * Assert which of the scenario contacts allowList() grants.
   *
   * @param int[] $contacts
   *   Scenario contact IDs.
   * @param int $permission
   *   Permission::VIEW or Permission::EDIT.
   * @param bool[] $expected
   *   Keyed by index in $contacts. Contacts not listed are expected to be denied.
   * @param string $label
   */
  private function assertAllowedContacts(array $contacts, int $permission, array $expected, string $label): void {
    $result = $this->getSortedAllowList($contacts, $permission);
    foreach ($contacts as $index => $contactID) {
      if ($expected[$index] ?? FALSE) {
        $this->assertContains($contactID, $result, "User[0] should have $label permission on contact[$index].");
      }
      else {
        $this->assertNotContains($contactID, $result, "User[0] should NOT have $label permission on contact[$index].");
      }
    }
  }

  /**
   * @param int[] $contactIDs
   * @param int $permission
   *
   * @return int[]
   */
  private function getSortedAllowList(array $contactIDs, int $permission = Permission::VIEW): array {
    return $this->sorted(ContactPermission::allowList($contactIDs, $permission));
  }

  /**
   * @param int[] $contactIDs
   *
   * @return int[]
   */
  private function sorted(array $contactIDs): array {
    sort($contactIDs);
    return $contactIDs;
  }

  /**
   * Create a logged-in user and nine other contacts, with no relationships or ACLs.
   *
   * @return int[]
   *   Sorted contact IDs, the logged-in user first.
   */
  private function createScenarioPlain(): array {
    $contacts = [$this->createLoggedInUser()];
    foreach (['Senior', 'Junior', 'Laura', 'Barbara', 'Brother', 'Nephew', 'Nephew2', 'Other Brother', 'Other Nephew'] as $firstName) {
      $contacts[] = $this->createTestEntity('Contact', [
        'contact_type' => 'Individual',
        'first_name' => $firstName,
        'last_name' => 'Bush',
      ], $firstName)['id'];
    }
    return $this->sorted($contacts);
  }

  /**
   * Create the plain scenario plus permissioned relationships.
   *
   * Permission flows (b_a means contact_id_b has permission over contact_id_a):
   *   0 -edit-> 1 -edit-> 2 -edit-> 4
   *   0 -view-> 5 -edit-> 6
   *             5 -view-> 7
   *   0 -edit-> 8 -view-> 9
   *
   * @return int[]
   */
  private function createScenarioRelations(): array {
    $contacts = $this->createScenarioPlain();
    $relationships = [
      // [type, contact a, contact b, is_permission_a_b, is_permission_b_a]
      ['Child of', 1, 0, 0, 1],
      ['Child of', 2, 1, 0, 1],
      ['Child of', 4, 2, 0, 1],
      ['Sibling of', 5, 0, 0, 2],
      ['Child of', 6, 5, 0, 1],
      ['Child of', 7, 5, 0, 2],
      ['Sibling of', 0, 8, 1, 0],
      ['Child of', 9, 8, 0, 2],
    ];
    foreach ($relationships as [$type, $a, $b, $permissionAB, $permissionBA]) {
      $this->createTestEntity('Relationship', [
        'relationship_type_id:name' => $type,
        'contact_id_a' => $contacts[$a],
        'contact_id_b' => $contacts[$b],
        'is_permission_a_b' => $permissionAB,
        'is_permission_b_a' => $permissionBA,
        'is_active' => TRUE,
      ], "$a-$b");
    }
    return $contacts;
  }

}

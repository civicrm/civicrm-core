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

namespace Civi\Test;

/**
 * Trait Civi\Test\ACLPermissionTrait.
 *
 * Trait for working with ACLs in tests
 */
trait ACLPermissionTrait {
  use EventTestTrait;

  /**
   * ContactID of allowed Contact
   * @var int
   */
  protected $allowedContactId = 0;

  /**
   * Array of allowed contactIds
   * @var array
   */
  protected $allowedContacts = [];

  /**
   * Ids created for the scenario in use.
   *
   * @var array
   */
  protected $scenarioIDs = [];

  /**
   * All results returned.
   *
   * @implements CRM_Utils_Hook::aclWhereClause
   *
   * @param string $type
   * @param array $tables
   * @param array $whereTables
   * @param int $contactID
   * @param string|null $where
   *
   * @noinspection PhpUnusedParameterInspection
   */
  public function aclWhereHookAllResults(string $type, array &$tables, array &$whereTables, int &$contactID, ?string &$where): void {
    $where = ' (1) ';
  }

  /**
   * No results returned.
   *
   * @implements CRM_Utils_Hook::aclWhereClause
   *
   * @param string $type
   * @param array $tables
   * @param array $whereTables
   * @param int $contactID
   * @param string|null $where
   */
  public function aclWhereHookNoResults(string $type, array &$tables, array &$whereTables, int &$contactID, ?string &$where): void {
  }

  /**
   * All but first results returned.
   *
   * @implements CRM_Utils_Hook::aclWhereClause
   *
   * @param string $type
   * @param array $tables
   * @param array $whereTables
   * @param int $contactID
   * @param string|null $where
   *
   * @noinspection PhpUnusedParameterInspection
   */
  public function aclWhereOnlySecond(string $type, array &$tables, array &$whereTables, int &$contactID, ?string &$where): void {
    $where = ' contact_a.id > 1';
  }

  /**
   * Only specified contact returned.
   *
   * @implements CRM_Utils_Hook::aclWhereClause
   *
   * @param string $type
   * @param array $tables
   * @param array $whereTables
   * @param int $contactID
   * @param string|null $where
   *
   * @noinspection PhpUnusedParameterInspection
   */
  public function aclWhereOnlyOne(string $type, array &$tables, array &$whereTables, int &$contactID, ?string &$where): void {
    $where = ' contact_a.id = ' . $this->allowedContactId;
  }

  /**
   * Results after the allowedContact are returned.
   *
   * @implements CRM_Utils_Hook::aclWhereClause
   *
   * @param string $type
   * @param array $tables
   * @param array $whereTables
   * @param int $contactID
   * @param string|null $where
   *
   * @noinspection PhpUnusedParameterInspection
   */
  public function aclWhereGreaterThan(string $type, array &$tables, array &$whereTables, int &$contactID, ?string &$where): void {
    $where = ' contact_a.id > ' . $this->allowedContactId;
  }

  /**
   * Only specified contact returned.
   *
   * @implements CRM_Utils_Hook::aclWhereClause
   *
   * @param $type
   * @param $tables
   * @param $whereTables
   * @param $contactID
   * @param $where
   */
  public function aclWhereMultipleContacts($type, &$tables, &$whereTables, &$contactID, &$where) {
    $where = " contact_a.id IN (" . implode(', ', $this->allowedContacts) . ")";
  }

  /**
   * Set up a core ACL.
   *
   * It is recommended that this helper function is accessed through a scenario function.
   *
   * @param array $permissionedEntities Array of groups for whom ACLs enable access.
   * @param string|int $groupAllowedAccess Group permitted to access the permissioned Group
   *   An ID of 0 means that 'Everyone' can access the group.
   * @param string $operation View|Edit|Create|Delete|Search|All
   * @param string $entity Group|CustomGroup|Profile|Event
   */
  public function setupCoreACLPermittedAcl(array $permissionedEntities = [], $groupAllowedAccess = 'Everyone', string $operation = 'View', string $entity = 'Group'): void {
    $tableMap = ['Group' => 'civicrm_group', 'CustomGroup' => 'civicrm_custom_group', 'Profile' => 'civicrm_uf_group', 'Event' => 'civicrm_event'];
    $entityTable = $tableMap[$entity];

    $permittedRoleID = ($groupAllowedAccess === 'Everyone') ? 0 : $groupAllowedAccess;
    if ($permittedRoleID !== 0) {
      $this->fail('only handling everyone group as yet');
    }

    foreach ($permissionedEntities as $permissionedEntityID) {
      $this->createTestEntity('ACL', [
        'name' => 'test acl' . $permissionedEntityID,
        'operation' => $operation,
        'entity_id' => $permittedRoleID,
        'object_id' => $permissionedEntityID,
        'object_table' => $entityTable,
      ]);
    }
  }

  /**
   * Set up a scenario where everyone can access the permissioned group.
   *
   * A scenario in this class involves multiple defined assets. In this case we create
   * - a group to which the everyone has permission
   * - a contact in the group
   * - a contact not in the group
   *
   * These are arrayed as follows
   *   $this->scenarioIDs['Contact'] = ['permitted_contact' => x, 'non_permitted_contact' => y]
   *   $this->scenarioIDs['Group'] = ['permitted_group' => x]
   */
  public function setupScenarioCoreACLEveryonePermittedToGroup(): void {
    $this->quickCleanup(['civicrm_acl_cache', 'civicrm_acl_contact_cache']);
    $this->scenarioIDs['Group']['permitted_group'] = $this->groupCreate();
    $this->scenarioIDs['Contact']['permitted_contact'] = $this->individualCreate();
    $this->callAPISuccess('GroupContact', 'create', ['group_id' => $this->scenarioIDs['Group']['permitted_group'], 'contact_id' => $this->scenarioIDs['Contact']['permitted_contact'], 'status' => 'Added']);
    $this->scenarioIDs['Contact']['non_permitted_contact'] = $this->individualCreate();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    $this->setupCoreACLPermittedAcl([$this->scenarioIDs['Group']['permitted_group']]);
  }

  /**
   * Set up a scenario where everyone can access the permissioned group.
   *
   * A scenario in this class involves multiple defined assets. In this case we
   * create
   * - a group to which the everyone has permission
   * - a contact in the group
   * - a contact not in the group
   *
   * These are arrayed as follows
   *   $this->scenarioIDs['Contact'] = ['permitted_contact' => x,
   * 'non_permitted_contact' => y]
   *   $this->scenarioIDs['Group'] = ['permitted_group' => x]
   */
  public function setupScenarioCoreACLEveryonePermittedToEvent(): void {
    $this->quickCleanup(['civicrm_acl_cache', 'civicrm_acl_contact_cache']);
    $this->scenarioIDs['Event']['permitted_event'] = $this->eventCreateUnpaid()['id'];
    $this->scenarioIDs['Contact']['permitted_contact'] = $this->individualCreate();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['view event info'];
    $this->setupCoreACLPermittedAcl([$this->scenarioIDs['Event']['permitted_event']], 'Everyone', 'View', 'Event');
  }

  /**
   * Clean up places where permissions get cached.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function cleanupCachedPermissions(): void {
    if (isset(\Civi::$statics['CRM_Contact_BAO_Contact_Permission'])) {
      unset(\Civi::$statics['CRM_Contact_BAO_Contact_Permission']);
    }
    \CRM_Core_DAO::executeQuery('TRUNCATE civicrm_acl_contact_cache');
  }

}

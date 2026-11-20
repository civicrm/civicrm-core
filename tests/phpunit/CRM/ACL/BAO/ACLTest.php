<?php

/**
 * Test the per-request cache of CRM_ACL_BAO_ACL::group().
 *
 * @group headless
 */
class CRM_ACL_BAO_ACLTest extends CiviUnitTestCase {

  /**
   * Number of times hook_civicrm_aclGroup ran, i.e. how often group() computed its result.
   *
   * @var int
   */
  private int $aclGroupHookCalls = 0;

  protected function tearDown(): void {
    $this->quickCleanup(['civicrm_group', 'civicrm_acl', 'civicrm_acl_cache'], TRUE);
    parent::tearDown();
  }

  public static function cachedResultProvider(): array {
    return [
      'no custom group permitted' => [FALSE],
      'custom group granted by hook' => [TRUE],
    ];
  }

  /**
   * A user without 'access all custom data' asks for the permitted custom groups twice.
   *
   * The second call must come from the cache, also when no custom group is permitted.
   *
   * @dataProvider cachedResultProvider
   */
  public function testCustomGroupPermissionIsComputedOncePerRequest(bool $grantByHook): void {
    $customGroupID = $this->createTestEntity('CustomGroup', [
      'title' => 'ACL cache test',
      'extends' => 'Individual',
    ])['id'];
    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->hookClass->setHook('civicrm_aclGroup', function ($type, $contactID, $tableName, &$allGroups, &$currentGroups) use ($grantByHook, $customGroupID) {
      if ($tableName === 'civicrm_custom_group') {
        $this->aclGroupHookCalls++;
        if ($grantByHook) {
          $currentGroups[] = $customGroupID;
        }
      }
    });
    $expected = $grantByHook ? [$customGroupID] : [];

    $this->assertSame($expected, CRM_Core_Permission::customGroup(CRM_Core_Permission::VIEW));
    $this->assertSame($expected, CRM_Core_Permission::customGroup(CRM_Core_Permission::VIEW));
    $this->assertSame(1, $this->aclGroupHookCalls);
  }

  /**
   * A cached empty result is dropped when a group changes.
   *
   * The contact becomes the creator of a hidden group, which makes the group visible to them.
   */
  public function testCachedEmptyResultIsFlushedWhenGroupChanges(): void {
    $contactID = $this->createLoggedInUser();
    $otherContactID = $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Other',
      'last_name' => 'Creator',
    ], 'other')['id'];
    $group = $this->createTestEntity('Group', [
      'title' => 'Hidden search result',
      'is_hidden' => TRUE,
      'created_id' => $otherContactID,
    ]);
    $allGroups = [$group['id'] => $group['title']];

    $this->assertSame([], CRM_ACL_API::group(CRM_Core_Permission::VIEW, $contactID, 'civicrm_group', $allGroups));

    \Civi\Api4\Group::update(FALSE)
      ->addWhere('id', '=', $group['id'])
      ->addValue('created_id', $contactID)
      ->execute();
    $this->assertSame([(string) $group['id']], CRM_ACL_API::group(CRM_Core_Permission::VIEW, $contactID, 'civicrm_group', $allGroups));
  }

  public static function resultBeforeAclChangeProvider(): array {
    return [
      'nothing permitted before' => [FALSE],
      'other custom group granted by hook before' => [TRUE],
    ];
  }

  /**
   * A cached result is dropped when the ACL cache is reset.
   *
   * An ACL that grants a custom group is added in the same request, followed by
   * the reset that e.g. assigning an ACL role or the ACL cache job does.
   *
   * @dataProvider resultBeforeAclChangeProvider
   */
  public function testCachedResultIsFlushedWhenAclCacheIsReset(bool $grantOtherByHook): void {
    $grantedID = $this->createTestEntity('CustomGroup', [
      'title' => 'ACL cache granted',
      'extends' => 'Individual',
    ], 'granted')['id'];
    $otherID = $this->createTestEntity('CustomGroup', [
      'title' => 'ACL cache other',
      'extends' => 'Individual',
    ], 'other')['id'];
    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->hookClass->setHook('civicrm_aclGroup', function ($type, $contactID, $tableName, &$allGroups, &$currentGroups) use ($grantOtherByHook, $otherID) {
      if ($tableName === 'civicrm_custom_group' && $grantOtherByHook) {
        $currentGroups[] = $otherID;
      }
    });
    $grantedByHook = $grantOtherByHook ? [$otherID] : [];
    $this->assertSame($grantedByHook, CRM_Core_Permission::customGroup(CRM_Core_Permission::VIEW));

    $this->createTestEntity('ACL', [
      'name' => 'Everyone views ACL cache granted',
      'entity_table' => 'civicrm_acl_role',
      'entity_id' => 0,
      'operation' => 'View',
      'object_table' => 'civicrm_custom_group',
      'object_id' => $grantedID,
      'is_active' => TRUE,
    ]);
    CRM_ACL_BAO_Cache::resetCache();

    $this->assertSame(array_merge([(string) $grantedID], $grantedByHook), CRM_Core_Permission::customGroup(CRM_Core_Permission::VIEW));
  }

}

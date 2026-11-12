<?php

use Civi\Api4\ACL;

/**
 * The permitted objects of an ACL check do not depend on what was checked before.
 *
 * @group headless
 */
class CRM_ACL_ObjectListCacheTest extends CiviUnitTestCase {

  private const USER_PERMISSIONS = ['access CiviCRM', 'access CiviEvent', 'view event info'];

  private array $eventIDs = [];

  public function setUp(): void {
    parent::setUp();
    $this->quickCleanup(['civicrm_acl_cache', 'civicrm_acl_contact_cache']);
    $creatorID = $this->individualCreate([], 'creator');
    $this->eventIDs['spring'] = $this->eventCreateUnpaid(['title' => 'Spring meeting', 'created_id' => $creatorID], 'spring')['id'];
    $this->eventIDs['autumn'] = $this->eventCreateUnpaid(['title' => 'Autumn meeting', 'created_id' => $creatorID], 'autumn')['id'];

    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = self::USER_PERMISSIONS;
  }

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_acl', 'civicrm_acl_cache', 'civicrm_acl_contact_cache', 'civicrm_group']);
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Create an Edit ACL for the "Everyone" role; object ID 0 stands for all objects.
   */
  private function createEditAcl(string $name, string $table, int $objectID, bool $deny, int $priority): void {
    $this->createTestEntity('ACL', [
      'name' => $name,
      'entity_table' => 'civicrm_acl_role',
      'entity_id' => 0,
      'operation' => 'Edit',
      'object_table' => $table,
      'object_id' => $objectID,
      'deny' => $deny,
      'priority' => $priority,
      'is_active' => TRUE,
    ], $name);
  }

  /**
   * The "Everyone" allow on one object has a lower priority than the deny on all objects.
   */
  private function assertAllowThenDenyAll(string $table, int $objectID): void {
    $acls = ACL::get(FALSE)->addWhere('object_table', '=', $table)
      ->addWhere('entity_table', '=', 'civicrm_acl_role')->addWhere('entity_id', '=', 0)
      ->addSelect('object_id', 'deny', 'priority')->addOrderBy('priority')->execute()->getArrayCopy();
    $this->assertEquals([[$objectID, FALSE], [0, TRUE]], array_map(fn($acl) => [(int) $acl['object_id'], (bool) $acl['deny']], $acls));
    $this->assertLessThan($acls[1]['priority'], $acls[0]['priority']);
  }

  /**
   * A new HTTP request starts with empty permission statics and an empty ArrayCache.
   */
  private function startNewRequest(): void {
    unset(Civi::$statics['CRM_ACL_BAO_ACL'], Civi::$statics['CRM_ACL_API'], Civi::$statics['CRM_Event_BAO_Event']);
    CRM_ACL_BAO_Cache::$_cache = NULL;
    CRM_Utils_Cache::singleton()->flush();
  }

  public function testCheckPermissionGrantsSecondEvent(): void {
    $this->createEditAcl('Everyone edits all events', 'civicrm_event', 0, FALSE, 1);
    $this->assertTrue(CRM_Event_BAO_Event::checkPermission($this->eventIDs['spring'], CRM_Core_Permission::EDIT), 'first event');
    $this->assertTrue(CRM_Event_BAO_Event::checkPermission($this->eventIDs['autumn'], CRM_Core_Permission::EDIT), 'Second event denied although the ACL covers all events');
  }

  /**
   * The permission-filtered event list (getEvents(), used e.g. by the event
   * aggregate search and the APIv3 ActionSchedule ACL check) keeps only one event.
   */
  public function testPermissionedEventListContainsAllEvents(): void {
    $this->createEditAcl('Everyone edits all events', 'civicrm_event', 0, FALSE, 1);
    $events = CRM_Event_BAO_Event::getEvents(1, NULL, TRUE, TRUE, TRUE);
    $this->assertEquals(['Autumn meeting', 'Spring meeting'], array_values($events));
  }

  public static function eventCheckOrders(): array {
    return [
      'control: event X checked alone' => [FALSE],
      'event Y checked first, then event X' => [TRUE],
    ];
  }

  /**
   * ACL 1 allows Edit on event X; ACL 2, with a higher priority,
   * denies Edit on All Events. X stays denied in any check order.
   *
   * @dataProvider eventCheckOrders
   */
  public function testHigherPriorityDenyOnAllEventsKeepsEventDenied(bool $checkOtherEventFirst): void {
    $eventX = $this->eventIDs['spring'];
    $this->createEditAcl('Everyone edits Spring meeting', 'civicrm_event', $eventX, FALSE, 1);
    $this->createEditAcl('Nobody edits any event', 'civicrm_event', 0, TRUE, 2);
    $this->assertAllowThenDenyAll('civicrm_event', $eventX);

    $this->startNewRequest();
    if ($checkOtherEventFirst) {
      $this->assertFalse(CRM_Event_BAO_Event::checkPermission($this->eventIDs['autumn'], CRM_Core_Permission::EDIT), 'event Y');
    }
    $this->assertFalse(CRM_Event_BAO_Event::checkPermission($eventX, CRM_Core_Permission::EDIT), 'Event X granted although a higher-priority ACL denies Edit on All Events');
  }

  public static function groupListOrders(): array {
    return [
      'control: full group list only' => [FALSE],
      'mailing group list first, then full list' => [TRUE],
    ];
  }

  /**
   * The same rules for contact groups: an allow on "Board" and a
   * higher-priority deny on All Groups leave "Board" not permitted.
   *
   * @dataProvider groupListOrders
   */
  public function testHigherPriorityDenyOnAllGroupsKeepsGroupDenied(bool $mailingListFirst): void {
    $boardID = $this->createTestEntity('Group', ['name' => 'board', 'title' => 'Board', 'group_type:name' => ['Access Control']], 'board')['id'];
    $this->createTestEntity('Group', ['name' => 'newsletter', 'title' => 'Newsletter', 'group_type:name' => ['Mailing List']], 'newsletter');
    $this->createEditAcl('Everyone edits Board', 'civicrm_group', $boardID, FALSE, 1);
    $this->createEditAcl('Nobody edits any group', 'civicrm_group', 0, TRUE, 2);
    $this->assertAllowThenDenyAll('civicrm_group', $boardID);

    $this->startNewRequest();
    if ($mailingListFirst) {
      CRM_Core_Permission::group('Mailing');
    }
    $this->assertArrayNotHasKey($boardID, CRM_Core_Permission::group(NULL, FALSE), 'Group "Board" permitted although a higher-priority ACL denies Edit on All Groups');
  }

}

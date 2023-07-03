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

use Civi\Test\ACLPermissionTrait;

/**
 * Class CRM_Event_BAO_EventPermissionsTest
 * @group headless
 */
class CRM_Event_BAO_EventPermissionsTest extends CiviUnitTestCase {

  use ACLPermissionTrait;

  public function setUp(): void {
    parent::setUp();
    $this->createLoggedInUser();
    $this->createOwnEvent();
    $this->createOtherEvent();
  }

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  public function createOwnEvent(): void {
    $event = $this->eventCreateUnpaid([
      'created_id' => $this->ids['Contact']['logged_in'],
    ], 'own');
    $this->ids['Event']['own'] = $event['id'];
  }

  public function createOtherEvent(): void {
    $event = $this->eventCreateUnpaid([
      'created_id' => $this->individualCreate([], 'other'),
    ], 'other');
    $this->ids['Event']['other'] = $event['id'];
  }

  private function setViewOwnEventPermissions(): void {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'access CiviEvent', 'view event info'];
    unset(\Civi::$statics['CRM_Event_BAO_Event']['permissions']);
  }

  private function setViewAllEventPermissions(): void {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'access CiviEvent', 'view event info', 'view event participants'];
    unset(\Civi::$statics['CRM_Event_BAO_Event']['permissions']);
  }

  private function setEditAllEventPermissions(): void {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'access CiviEvent', 'view event info', 'edit all events'];
    unset(\Civi::$statics['CRM_Event_BAO_Event']['permissions']);
  }

  private function setDeleteAllEventPermissions(): void {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'access CiviEvent', 'view event info', 'delete in CiviEvent'];
    unset(\Civi::$statics['CRM_Event_BAO_Event']['permissions']);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testViewOwnEvent(): void {
    $this->setViewOwnEventPermissions();
    $this->assertTrue(CRM_Event_BAO_Event::checkPermission($this->ids['Event']['own'], CRM_Core_Permission::VIEW));
    // Now check that caching is actually working.
    \Civi::$statics['CRM_Event_BAO_Event']['permission']['view'][$this->ids['Event']['own']] = FALSE;
    $permissions = CRM_Event_BAO_Event::checkPermission($this->ids['Event']['own'], CRM_Core_Permission::VIEW);
    $this->assertFalse($permissions);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testEditOwnEvent(): void {
    $this->setViewOwnEventPermissions();
    $permissions = CRM_Event_BAO_Event::checkPermission($this->ids['Event']['own'], CRM_Core_Permission::EDIT);
    $this->assertTrue($permissions);
  }

  /**
   * This requires the same permissions as testDeleteOtherEvent()
   *
   * @throws \CRM_Core_Exception
   */
  public function testDeleteOwnEvent(): void {
    // Check that you can't delete your own event without "Delete in CiviEvent" permission
    $this->setViewOwnEventPermissions();
    $this->assertFalse(CRM_Event_BAO_Event::checkPermission($this->ids['Event']['own'], CRM_Core_Permission::DELETE));
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testViewOtherEventDenied(): void {
    $this->setViewOwnEventPermissions();
    $this->assertFalse(CRM_Event_BAO_Event::checkPermission($this->ids['Event']['other'], CRM_Core_Permission::VIEW));
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testViewOtherEventAllowed(): void {
    $this->setViewAllEventPermissions();
    $this->assertTrue(CRM_Event_BAO_Event::checkPermission($this->ids['Event']['other'], CRM_Core_Permission::VIEW));
  }

  /**
   * Test that the contact can view an event with an ACL permitting everyone to view it.
   */
  public function testViewAclEventAllowed(): void {
    $this->setupScenarioCoreACLEveryonePermittedToEvent();
    $permittedEventID = CRM_Core_Permission::event(CRM_Core_Permission::VIEW, $this->scenarioIDs['Event']['permitted_event']);
    $this->assertEquals($this->scenarioIDs['Event']['permitted_event'], $permittedEventID);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testEditOtherEventDenied(): void {
    $this->setViewAllEventPermissions();
    $this->assertFalse(CRM_Event_BAO_Event::checkPermission($this->ids['Event']['other'], CRM_Core_Permission::EDIT));
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testEditOtherEventAllowed(): void {
    $this->setEditAllEventPermissions();
    $this->assertTrue(CRM_Event_BAO_Event::checkPermission($this->ids['Event']['other'], CRM_Core_Permission::EDIT));
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testDeleteOtherEventAllowed(): void {
    $this->setDeleteAllEventPermissions();
    $this->assertTrue(CRM_Event_BAO_Event::checkPermission($this->ids['Event']['other'], CRM_Core_Permission::DELETE));
  }

  /**
   * Test checks that we can't delete if we don't have "Delete in CiviEvent"
   *
   * @throws \CRM_Core_Exception
   */
  public function testDeleteOtherEventDenied(): void {
    $this->setEditAllEventPermissions();
    $this->assertFalse(CRM_Event_BAO_Event::checkPermission($this->ids['Event']['other'], CRM_Core_Permission::DELETE));
  }

  /**
   * Test get complete info function returns all info for contacts with view all info.
   */
  public function testGetCompleteInfo(): void {
    $this->setupScenarioCoreACLEveryonePermittedToEvent();
    $info = CRM_Event_BAO_Event::getCompleteInfo('20000101');
    $this->assertEquals('Annual CiviCRM meet', $info[0]['title']);
    $this->assertEquals('Annual CiviCRM meet', $info[1]['title']);
  }

}

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
 * Class CRM_Event_BAO_EventPermissionsTest
 * @group headless
 */
class CRM_Event_BAO_EventPermissionsTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->_contactId = $this->createLoggedInUser();
    $this->createOwnEvent();
    $this->createOtherEvent();
  }

  public function createOwnEvent() {
    $event = $this->eventCreate([
      'created_id' => $this->_contactId,
    ]);
    $this->_ownEventId = $event['id'];
  }

  public function createOtherEvent() {
    $this->_otherContactId = $this->_contactId + 1;
    $event = $this->eventCreate([
      'created_id' => $this->_otherContactId,
    ]);
    $this->_otherEventId = $event['id'];
  }

  private function setViewOwnEventPermissions() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'access CiviEvent', 'view event info'];
  }

  private function setViewAllEventPermissions() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'access CiviEvent', 'view event info', 'view event participants'];
  }

  private function setEditAllEventPermissions() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'access CiviEvent', 'view event info', 'edit all events'];
  }

  private function setDeleteAllEventPermissions() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'access CiviEvent', 'view event info', 'delete in CiviEvent'];
  }

  public function testViewOwnEvent() {
    self::setViewOwnEventPermissions();
    unset(\Civi::$statics['CRM_Event_BAO_Event']['permissions']);
    $permissions = CRM_Event_BAO_Event::checkPermission($this->_ownEventId, CRM_Core_Permission::VIEW);
    $this->assertTrue($permissions);
    // Now check that caching is actually working
    \Civi::$statics['CRM_Event_BAO_Event']['permission']['view'][$this->_ownEventId] = FALSE;
    $permissions = CRM_Event_BAO_Event::checkPermission($this->_ownEventId, CRM_Core_Permission::VIEW);
    $this->assertFalse($permissions);
  }

  public function testEditOwnEvent() {
    self::setViewOwnEventPermissions();
    unset(\Civi::$statics['CRM_Event_BAO_Event']['permissions']);
    $this->_loggedInUser = CRM_Core_Session::singleton()->get('userID');
    $permissions = CRM_Event_BAO_Event::checkPermission($this->_ownEventId, CRM_Core_Permission::EDIT);
    $this->assertTrue($permissions);
  }

  /**
   * This requires the same permissions as testDeleteOtherEvent()
   */
  public function testDeleteOwnEvent() {
    // Check that you can't delete your own event without "Delete in CiviEvent" permission
    self::setViewOwnEventPermissions();
    unset(\Civi::$statics['CRM_Event_BAO_Event']['permissions']);
    $permissions = CRM_Event_BAO_Event::checkPermission($this->_ownEventId, CRM_Core_Permission::DELETE);
    $this->assertFalse($permissions);
  }

  public function testViewOtherEventDenied() {
    $this->_loggedInUser = CRM_Core_Session::singleton()->get('userID');
    self::setViewOwnEventPermissions();
    unset(\Civi::$statics['CRM_Event_BAO_Event']['permissions']);
    $permissions = CRM_Event_BAO_Event::checkPermission($this->_otherEventId, CRM_Core_Permission::VIEW);
    $this->assertFalse($permissions);
  }

  public function testViewOtherEventAllowed() {
    $this->_loggedInUser = CRM_Core_Session::singleton()->get('userID');
    self::setViewAllEventPermissions();
    unset(\Civi::$statics['CRM_Event_BAO_Event']['permissions']);
    $permissions = CRM_Event_BAO_Event::checkPermission($this->_otherEventId, CRM_Core_Permission::VIEW);
    $this->assertTrue($permissions);
  }

  public function testEditOtherEventDenied() {
    $this->_loggedInUser = CRM_Core_Session::singleton()->get('userID');
    self::setViewAllEventPermissions();
    unset(\Civi::$statics['CRM_Event_BAO_Event']['permissions']);
    $permissions = CRM_Event_BAO_Event::checkPermission($this->_otherEventId, CRM_Core_Permission::EDIT);
    $this->assertFalse($permissions);
  }

  public function testEditOtherEventAllowed() {
    $this->_loggedInUser = CRM_Core_Session::singleton()->get('userID');
    self::setEditAllEventPermissions();
    unset(\Civi::$statics['CRM_Event_BAO_Event']['permissions']);
    $permissions = CRM_Event_BAO_Event::checkPermission($this->_otherEventId, CRM_Core_Permission::EDIT);
    $this->assertTrue($permissions);
  }

  public function testDeleteOtherEventAllowed() {
    self::setDeleteAllEventPermissions();
    unset(\Civi::$statics['CRM_Event_BAO_Event']['permissions']);
    $permissions = CRM_Event_BAO_Event::checkPermission($this->_otherEventId, CRM_Core_Permission::DELETE);
    $this->assertTrue($permissions);
  }

  public function testDeleteOtherEventDenied() {
    // FIXME: This test could be improved, but for now it checks that we can't delete if we don't have "Delete in CiviEvent"
    self::setEditAllEventPermissions();
    unset(\Civi::$statics['CRM_Event_BAO_Event']['permissions']);
    $permissions = CRM_Event_BAO_Event::checkPermission($this->_otherEventId, CRM_Core_Permission::DELETE);
    $this->assertFalse($permissions);
  }

}

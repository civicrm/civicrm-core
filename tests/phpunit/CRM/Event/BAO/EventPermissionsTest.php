<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
    $event = $this->eventCreate(array(
      'created_id' => $this->_contactId,
    ));
    $this->_ownEventId = $event['id'];
  }

  public function createOtherEvent() {
    $this->_otherContactId = $this->_contactId + 1;
    $event = $this->eventCreate(array(
      'created_id' => $this->_otherContactId,
    ));
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

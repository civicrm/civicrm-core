<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
    $event = $this->eventCreate(array(
      'created_id' => $this->_contactId,
    ));
    $this->_eventId = $event['id'];
  }

  public function testEditOwnEvent() {
    CRM_Core_Config::singleton()->userPermissionTemp = ['access civievent', 'access CiviCRM', 'view event info'];
    $permissions = CRM_Event_BAO_Event::checkPermission($this->_eventId, CRM_Core_Permission::EDIT);
    $this->assertEquals($permissions, TRUE);
  }

}

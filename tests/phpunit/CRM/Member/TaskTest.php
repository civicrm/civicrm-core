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
 * Class CRM_Member_BAO_MembershipTest
 * @group headless
 */
class CRM_Member_TaskTest extends CiviUnitTestCase {

  use Civi\Test\ACLPermissionTrait;

  /**
   * Test tiles are correctly filtered on permissions.
   */
  public function testPermissionedTiles(): void {
    $this->createLoggedInUser();

    CRM_Member_Task::tasks();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['view memberships'];
    $tasks = CRM_Member_Task::permissionedTaskTitles(CRM_Core_Permission::VIEW);
    $this->assertEquals([8 => 'Export members', 5 => 'Print selected rows'], $tasks);

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['delete in CiviMember', 'view memberships'];
    $tasks = CRM_Member_Task::permissionedTaskTitles(CRM_Core_Permission::VIEW);
    $this->assertEquals([8 => 'Export members', 5 => 'Print selected rows', 4 => 'Delete memberships'], $tasks);

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['edit memberships'];
    $tasks = CRM_Member_Task::permissionedTaskTitles(CRM_Core_Permission::VIEW);
    $this->assertEquals([8 => 'Export members', 5 => 'Print selected rows'], $tasks);

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['edit memberships'];
    $tasks = CRM_Member_Task::permissionedTaskTitles(CRM_Core_Permission::EDIT);
    $this->assertEquals([
      8 => 'Export members',
      5 => 'Print selected rows',
      9 => 'Email - send now (to 50 or less)',
      201 => 'Mailing labels - print',
      3 => 'Print/merge document for memberships',
      6 => 'Update multiple memberships',
    ], $tasks);

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['edit memberships', 'delete in CiviMember', 'edit groups'];
    $tasks = CRM_Member_Task::permissionedTaskTitles(CRM_Core_Permission::EDIT);
    $this->assertEquals([
      8 => 'Export members',
      5 => 'Print selected rows',
      9 => 'Email - send now (to 50 or less)',
      201 => 'Mailing labels - print',
      3 => 'Print/merge document for memberships',
      6 => 'Update multiple memberships',
      4 => 'Delete memberships',
      12 => 'Group - create smart group',
    ], $tasks);
  }

}

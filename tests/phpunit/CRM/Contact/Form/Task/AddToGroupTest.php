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

use Civi\Api4\Group;
use Civi\Api4\GroupContact;
use Civi\Test\FormTrait;

/**
 * @group headless
 */
class CRM_Contact_Form_Task_AddToGroupTest extends CiviUnitTestCase {
  use FormTrait;

  /**
   * Test add to existing group.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddToGroup(): void {
    $contact = $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'John',
      'last_name' => 'Doe',
    ]);
    $existingGroupId = $this->groupCreate();
    $form = $this->getTestForm('CRM_Contact_Form_Search_Basic', ['radio_ts' => 'ts_all'])
      ->addSubsequentForm('CRM_Contact_Form_Task_AddToGroup', [
        'group_option' => 0,
        'group_id' => $existingGroupId,
      ]);
    $form->processForm();
    $groupCount = GroupContact::get()
      ->addWhere('group_id', '=', $existingGroupId)
      ->addWhere('status', '=', 'Added')
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute()
      ->count();
    $this->assertEquals(1, $groupCount);
  }

  /**
   * Test delete to trash.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddToNewGroupWithCustomField(): void {
    $contact = $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Pete',
      'last_name' => 'Johnson',
    ]);

    $customGroup = $this->customGroupCreate(['extends' => 'Group']);
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);
    $customFieldId = $customField['id'];

    $form = $this->getTestForm(
      'CRM_Contact_Form_Search_Basic',
      ['radio_ts' => 'ts_all']
    )->addSubsequentForm(
      'CRM_Contact_Form_Task_AddToGroup',
      [
        'group_option' => 1,
        'title' => 'Test Group With Custom Field',
        'description' => '',
        'custom_' . $customFieldId => 'Custom Value ABC',
      ]
    );
    $form->processForm();

    $group = Group::get()
      ->addSelect('custom.*', 'id')
      ->addWhere('title', '=', 'Test Group With Custom Field')
      ->execute();
    $this->assertEquals(1, $group->count());
    $group = $group->first();
    $this->assertArrayKeyExists('new_custom_group.Custom_Field', $group);
    $this->assertEquals('Custom Value ABC', $group['new_custom_group.Custom_Field']);

    $groupCount = GroupContact::get()
      ->addWhere('group_id', '=', $group['id'])
      ->addWhere('status', '=', 'Added')
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute()
      ->count();
    $this->assertEquals(1, $groupCount);
  }

}

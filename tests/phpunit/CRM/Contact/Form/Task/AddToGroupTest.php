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

/**
 * @group headless
 */
class CRM_Contact_Form_Task_AddToGroupTest extends CiviUnitTestCase {

  /**
   * @param array $formValues
   * @return CRM_Contact_Form_Task_AddToGroup
   */
  protected function getSearchTaskFormObject(array $formValues): CRM_Contact_Form_Task_AddToGroup {
    $_POST = $formValues;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $form = new CRM_Contact_Form_Task_AddToGroup();
    $form->controller = new CRM_Contact_Controller_Search();
    $form->controller->setStateMachine(new CRM_Core_StateMachine($form->controller));
    $_SESSION['_' . $form->controller->_name . '_container']['values']['Advanced'] = $formValues;
    return $form;
  }

  /**
   * Test delete to trash.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddToGroup(): void {
    $contact = $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'John',
      'last_name' => 'Doe',
    ]);
    $contactId = $contact['id'];
    $existingGroupId = $this->groupCreate();
    $form = $this->getSearchTaskFormObject(['cids' => $contactId, 'group_option' => 0, 'group_id' => $existingGroupId]);
    $form->preProcess();
    $form->_contactIds = [$contactId];
    $form->set('_componentIds', [$contactId]);
    $form->buildQuickForm();
    $form->setDefaultValues();
    $form->postProcess();

    $groupCount = GroupContact::get()
      ->addWhere('group_id', '=', $existingGroupId)
      ->addWhere('status', '=', 'Added')
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
    $contactId = $contact['id'];
    $customGroup = $this->customGroupCreate(['extends' => 'Group']);
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);
    $customFieldId = $customField['id'];

    $form = $this->getSearchTaskFormObject([
      'cids' => $contactId,
      'group_option' => 1,
      'title' => 'Test Group With Custom Field',
      'description' => '',
      'custom_' . $customFieldId => 'Custom Value ABC',
    ]);
    $form->preProcess();
    $form->_contactIds = [$contactId];
    $form->set('_componentIds', [$contactId]);
    $form->buildQuickForm();
    $form->setDefaultValues();
    $form->postProcess();

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
      ->execute()
      ->count();
    $this->assertEquals(1, $groupCount);
  }

}

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
  * @group headless
  */
class CRM_Contact_Form_Task_AddToGroupTest extends CiviUnitTestCase {

  protected function setUp(): void {
    parent::setUp();
  }

  protected function getSearchTaskFormObject(array $formValues) {
    $_POST = $formValues;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    /* @var CRM_Core_Form $form */
    $form = new CRM_Contact_Form_Task_AddToGroup();
    $form->controller = new CRM_Contact_Controller_Search();
    $form->controller->setStateMachine(new CRM_Core_StateMachine($form->controller));
    $_SESSION['_' . $form->controller->_name . '_container']['values']['Advanced'] = $formValues;
    return $form;
  }

  /**
   * Test delete to trash.
   */
  public function testAddToGroup() {
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

    $groupCount = \Civi\Api4\GroupContact::get()
      ->addWhere('group_id', '=', $existingGroupId)
      ->addWhere('status', '=', 'Added')
      ->execute()
      ->count();
    $this->assertEquals(1, $groupCount);
  }

  /**
   * Test delete to trash.
   */
  public function testAddToNewGroupWithCustomField() {
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

    $group = \Civi\Api4\Group::get()
      ->addSelect('custom.*', 'id')
      ->addWhere('title', '=', 'Test Group With Custom Field')
      ->execute();
    $this->assertEquals(1, $group->count());
    $group = $group->first();
    $this->assertArrayKeyExists('new_custom_group.Custom_Field', $group);
    $this->assertEquals('Custom Value ABC', $group['new_custom_group.Custom_Field']);

    $groupCount = \Civi\Api4\GroupContact::get()
      ->addWhere('group_id', '=', $group['id'])
      ->addWhere('status', '=', 'Added')
      ->execute()
      ->count();
    $this->assertEquals(1, $groupCount);
  }

}

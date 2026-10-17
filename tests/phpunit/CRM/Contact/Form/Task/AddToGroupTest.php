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
use Civi\Test\Invasive;

/**
 * @group headless
 */
class CRM_Contact_Form_Task_AddToGroupTest extends CiviUnitTestCase {
  use FormTrait;

  /**
   * Clean up after test.
   */
  public function tearDown(): void {
    \Civi\Api4\OptionValue::update(FALSE)
      ->addWhere('option_group_id:name', '=', 'group_type')
      ->addWhere('name', '=', 'Mailing List')
      ->addValue('label', 'Mailing List')
      ->execute();
    Civi::settings()->revert('civimail_workflow');
    $this->quickCleanup(['civicrm_group_contact', 'civicrm_group']);
    parent::tearDown();
  }

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
   * A new group is not offered as mailing list to a user without mailing access, whatever that type is labelled.
   *
   * @throws \CRM_Core_Exception
   */
  public function testNewGroupDoesNotOfferMailingListWithoutMailingAccess(): void {
    \Civi\Api4\OptionValue::update(FALSE)
      ->addWhere('option_group_id:name', '=', 'group_type')
      ->addWhere('name', '=', 'Mailing List')
      ->addValue('label', 'Mailingliste')
      ->execute();
    Civi::settings()->set('civimail_workflow', TRUE);
    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts', 'edit all contacts', 'edit groups'];
    $this->individualCreate();
    $mailingListType = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Group', 'group_type', 'Mailing List');

    $formWrapper = $this->getTestForm('CRM_Contact_Form_Search_Basic', ['radio_ts' => 'ts_all'])
      ->addSubsequentForm('CRM_Contact_Form_Task_AddToGroup', [
        'group_option' => 1,
        'title' => 'Newsletter',
        'description' => '',
      ])
      ->processForm();

    $form = Invasive::get([$formWrapper, 'subsequentForms'])['AddToGroup'];
    $offeredTypes = array_map(fn($checkbox) => $checkbox->getName(), $form->getElement('group_type')->getElements());
    $this->assertNotContains((string) $mailingListType, $offeredTypes);
    $this->assertNotEmpty($offeredTypes);
  }

  /**
   * Test Advanced Search opened from an "Add to Group" search keeps that context.
   *
   * The results of an "Add to Group" search link to Advanced Search with
   * context=amtg. This task form reads the context from the search controller
   * to lock the group and to return to the group's contacts afterwards, and
   * without it renders a broken form.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAdvancedSearchKeepsAddToGroupContext(): void {
    $groupID = $this->groupCreate();
    $form = $this->getTestForm('CRM_Contact_Form_Search_Advanced', ['radio_ts' => 'ts_all'], [
      'context' => 'amtg',
      'amtgID' => $groupID,
    ]);
    $form->processForm();
    $this->assertEquals('amtg', $form->getValueSetOnForm('context'));
    $this->assertEquals($groupID, $form->getValueSetOnForm('amtgID'));
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

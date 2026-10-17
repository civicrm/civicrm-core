<?php

use Civi\Api4\Group;
use Civi\Api4\GroupContact;
use Civi\Api4\OptionValue;
use Civi\Test\FormTrait;
use Civi\Test\Invasive;

/**
 * @group headless
 */
class CRM_Event_Form_Task_AddToGroupTest extends CiviUnitTestCase {
  use FormTrait;

  public function tearDown(): void {
    $this->relabelMailingList('Mailing List');
    Civi::settings()->revert('civimail_workflow');
    $this->quickCleanup(['civicrm_participant', 'civicrm_event', 'civicrm_group_contact', 'civicrm_group'], TRUE);
    parent::tearDown();
  }

  /**
   * A new group is not offered as mailing list to a user without mailing access, whatever that type is labelled.
   *
   * @throws \CRM_Core_Exception
   */
  public function testNewGroupDoesNotOfferMailingListWithoutMailingAccess(): void {
    $this->relabelMailingList('Mailingliste');
    Civi::settings()->set('civimail_workflow', TRUE);
    $this->participantCreate(['contact_id' => $this->individualCreate(), 'event_id' => $this->eventCreateUnpaid()['id']]);
    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'access CiviEvent', 'view all contacts', 'edit all contacts', 'edit groups'];

    $formWrapper = $this->getTestForm('CRM_Event_Form_Search', ['radio_ts' => 'ts_all', 'task' => CRM_Event_Task::GROUP_ADD])
      ->addSubsequentForm('CRM_Event_Form_Task_AddToGroup', ['group_option' => 1, 'title' => 'Newsletter', 'description' => ''])
      ->processForm();

    $form = Invasive::get([$formWrapper, 'subsequentForms'])['AddToGroup'];
    $offeredTypes = array_map(fn($checkbox) => $checkbox->getName(), $form->getElement('group_type')->getElements());
    $this->assertNotContains((string) CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Group', 'group_type', 'Mailing List'), $offeredTypes);
    $this->assertNotEmpty($offeredTypes);
  }

  /**
   * Custom data entered for a new group is saved with it.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddToNewGroupWithCustomField(): void {
    $contactID = $this->individualCreate();
    $this->participantCreate(['contact_id' => $contactID, 'event_id' => $this->eventCreateUnpaid()['id']]);
    $customField = $this->customFieldCreate(['custom_group_id' => $this->customGroupCreate(['extends' => 'Group'])['id']]);

    $this->getTestForm('CRM_Event_Form_Search', ['radio_ts' => 'ts_all', 'task' => CRM_Event_Task::GROUP_ADD])
      ->addSubsequentForm('CRM_Event_Form_Task_AddToGroup', [
        'group_option' => 1,
        'title' => 'Participants',
        'description' => '',
        'custom_' . $customField['id'] => 'Custom Value ABC',
      ])
      ->processForm();

    $group = Group::get(FALSE)
      ->addSelect('id', 'new_custom_group.Custom_Field')
      ->addWhere('title', '=', 'Participants')
      ->execute()->single();
    $this->assertEquals('Custom Value ABC', $group['new_custom_group.Custom_Field']);
    $this->assertCount(1, GroupContact::get(FALSE)
      ->addWhere('group_id', '=', $group['id'])
      ->addWhere('contact_id', '=', $contactID)
      ->addWhere('status', '=', 'Added')
      ->execute());
  }

  /**
   * @param string $label
   */
  private function relabelMailingList(string $label): void {
    OptionValue::update(FALSE)
      ->addWhere('option_group_id:name', '=', 'group_type')
      ->addWhere('name', '=', 'Mailing List')
      ->addValue('label', $label)
      ->execute();
  }

}

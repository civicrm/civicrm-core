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

use Civi\Api4\Email;

/**
 * Test class for CRM_Mailing_Form_Unsubscribe.
 * @group headless
 */
class CRM_Mailing_Form_UnsubscribeTest extends CiviUnitTestCase {

  /**
   * Submit the unsubscribe form.
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function testSubmit(): void {
    $this->setupMailing();
    $this->getTestForm('CRM_Mailing_Form_Unsubscribe', [], [
      'jid' => 1,
      'qid' => $this->ids['MailingEventQueue']['default'],
      'h' => 'abc',
    ])
      ->processForm();
    $this->assertTemplateVariable('groups', [$this->ids['Group']['group'] => ['title' => 'Public group name', 'description' => ''], $this->ids['Group']['smart_group'] => ['title' => 'Public group name', 'description' => '']]);
  }

  /**
   * Set up a 'sent' mailing with 2 attached groups which the contact is part of.
   *
   * One group is a smarty group of all individuals.
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function setupMailing(): void {

    $savedSearch = $this->createTestEntity('SavedSearch', [
      'api_entity' => 'Contact',
      'api_params' => [
        'version' => 4,
        'select' => ['id'],
        'where' => [['contact_type', '=', 'Individual']],
      ],
    ]);

    $params = [
      'contact_id' => $this->individualCreate(),
      'group_id' => $this->groupCreate(['name' => 'Test group 2', 'title' => 'group title 2']),
    ];
    $this->createTestEntity('GroupContact', $params);

    $email = Email::get()
      ->addWhere('id', '=', $params['contact_id'])
      ->execute()->first();
    $this->createTestEntity('MailingEventQueue', [
      'mailing_id' => $this->createMailing(),
      'hash' => 'abc',
      'contact_id' => $params['contact_id'],
      'email_id' => $email['id'],
    ]);
    $this->createTestEntity('MailingGroup', [
      'mailing_id' => $this->ids['Mailing']['default'],
      'entity_table' => 'civicrm_group',
      'entity_id' => $params['group_id'],
      'group_type' => 'Base',
    ]);
    $this->createTestEntity('MailingGroup', [
      'mailing_id' => $this->ids['Mailing']['default'],
      'entity_table' => 'civicrm_group',
      'entity_id' => $this->groupCreate(['saved_search_id' => $savedSearch['id']], 'smart_group'),
      'group_type' => 'Include',
    ]);
  }

}

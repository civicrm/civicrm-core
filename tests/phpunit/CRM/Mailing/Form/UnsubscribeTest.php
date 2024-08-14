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

  public function testSubmit(): void {
    $this->sendMailing();
    $this->getTestForm('CRM_Mailing_Form_Unsubscribe', [], [
      'jid' => 1,
      'qid' => $this->ids['MailingEventQueue']['default'],
      'h' => 'abc',
    ])
      ->processForm();
  }

  /**
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function sendMailing(): void {
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
  }

}

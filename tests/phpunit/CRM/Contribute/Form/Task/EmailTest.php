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
 *  Test Email task.
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Contribute_Form_Task_EmailTest extends CiviUnitTestCase {

  /**
   * Clean up after each test.
   *
   * @throws \CRM_Core_Exception
   * @throws \API_Exception
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Test that email tokens are rendered.
   */
  public function testEmailTokens(): void {
    Civi::settings()->set('max_attachments', 0);
    $contact1 = $this->individualCreate();
    $contact2 = $this->individualCreate();
    $userID = $this->createLoggedInUser();
    Civi::settings()->set('allow_mail_from_logged_in_contact', TRUE);
    $this->callAPISuccess('Email', 'create', [
      'contact_id' => $userID,
      'email' => 'benny_jetts@example.com',
      'signature_html' => 'Benny, Benny',
      'is_primary' => 1,
    ]);
    $contribution1 = $this->contributionCreate(['contact_id' => $contact2]);
    $contribution2 = $this->contributionCreate(['total_amount' => 999, 'contact_id' => $contact1]);
    $form = $this->getFormObject('CRM_Contribute_Form_Task_Email', ['cc_id' => '', 'bcc_id' => ''], [], [
      'radio_ts' => 'ts_sel',
      'task' => CRM_Contribute_Task::TASK_EMAIL,
      'mark_x_' . $contribution1 => 1,
      'mark_x_' . $contribution2 => 1,
    ]);
    $form->set('cid', $contact1 . ',' . $contact2);
    $form->buildForm();
    $this->assertEquals('<br/><br/>--Benny, Benny', $form->_defaultValues['html_message']);
  }

}

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
    $contact2 = $this->individualCreate(['first_name' => 'Elton']);
    $userID = $this->createLoggedInUser();
    $mut = new CiviMailUtils($this);
    Civi::settings()->set('allow_mail_from_logged_in_contact', TRUE);
    $emailID = $this->callAPISuccess('Email', 'create', [
      'contact_id' => $userID,
      'email' => 'benny_jetts@example.com',
      'signature_html' => 'Benny, Benny',
      'is_primary' => 1,
    ])['id'];
    $contribution1 = $this->contributionCreate(['contact_id' => $contact2, 'invoice_number' => 'soy']);
    $contribution2 = $this->contributionCreate(['total_amount' => 999, 'contact_id' => $contact1, 'invoice_number' => 'saucy']);
    $contribution3 = $this->contributionCreate(['total_amount' => 999, 'contact_id' => $contact1, 'invoice_number' => 'ranch']);
    $form = $this->getFormObject('CRM_Contribute_Form_Task_Email', [
      'cc_id' => '',
      'bcc_id' => '',
      'to' => implode(',', [
        $contact1 . '::teresajensen-nielsen65@spamalot.co.in',
        $contact2 . '::bob@example.com',
      ]),
      'subject' => '{contact.display_name} {contribution.total_amount}',
      'text_message' => '{contribution.financial_type_id:label} {contribution.invoice_number}',
      'html_message' => '{domain.name}',
      'from_email_address' => $emailID,
    ], [], [
      'radio_ts' => 'ts_sel',
      'task' => CRM_Core_Task::TASK_EMAIL,
      'mark_x_' . $contribution1 => 1,
      'mark_x_' . $contribution2 => 1,
      'mark_x_' . $contribution3 => 1,
    ]);
    $form->set('cid', $contact1 . ',' . $contact2);
    $form->buildForm();
    $this->assertEquals('<br/><br/>--Benny, Benny', $form->_defaultValues['html_message']);
    $form->postProcess();
    $mut->assertSubjects(['Mr. Anthony Anderson II $999.00', 'Mr. Elton Anderson II $100.00']);
    $mut->checkAllMailLog([
      'Subject: Mr. Anthony Anderson II',
      '$999.0',
      'Default Domain Name',
      'Donation soy',
      'Donation ranch',
    ]);
  }

}

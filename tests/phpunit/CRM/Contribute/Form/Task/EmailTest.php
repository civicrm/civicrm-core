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

  use Civi\Test\ContributionPageTestTrait;

  /**
   * Clean up after each test.
   *
   * @throws \CRM_Core_Exception
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
    $form = $this->getSearchFormObject('CRM_Contribute_Form_Task_Email', [
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
    ], NULL, [
      'radio_ts' => 'ts_sel',
      'task' => CRM_Core_Task::TASK_EMAIL,
      'mark_x_' . $contribution1 => 1,
      'mark_x_' . $contribution2 => 1,
      'mark_x_' . $contribution3 => 1,
    ]);
    $form->set('cid', $contact1 . ',' . $contact2);
    $form->buildForm();
    $this->assertEquals('<br /><br />--Benny, Benny', $form->_defaultValues['html_message']);
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

  /**
   * When a contribution page contains a profile with a groups field, and
   * you later resend the email when logged in as an admin, since you have
   * access to the private non-public groups it was including those, but
   * that is undesirable.
   */
  public function testEmailDoesNotContainPrivateGroups(): void {
    $loggedInContactID = $this->createLoggedInUser();
    $this->callAPISuccess('Email', 'create', [
      'contact_id' => $loggedInContactID,
      'email' => 'benny_jetts@example.com',
      'is_primary' => 1,
    ])['id'];

    // Note g1 vs g-1-front because they need to be different enough that checking the output for "g1" won't also accidentally include the frontend title, e.g. if it was "g1front".
    $groupID1 = $this->groupCreate(['name' => 'g1name', 'title' => 'g1', 'frontend_title' => 'g-1-front', 'visibility' => 'User and User Admin Only']);
    $groupID2 = $this->groupCreate(['name' => 'g2name', 'title' => 'g2', 'frontend_title' => 'g-2-front', 'visibility' => 'Public Pages']);

    $cid = $this->individualCreate();
    $this->callAPISuccess('GroupContact', 'create', ['group_id' => $groupID1, 'contact_id' => $cid, 'status' => 'Added']);
    $this->callAPISuccess('GroupContact', 'create', ['group_id' => $groupID2, 'contact_id' => $cid, 'status' => 'Added']);

    $contributionPage = $this->contributionPageCreate(['is_monetary' => FALSE]);

    // Add groups field to profile. Also email because it's required usually
    // for group subscription.
    $this->createTestEntity('UFField', [
      'uf_group_id' => $this->ids['UFGroup']['ContributionPage_post'],
      'field_name' => 'email',
      'visibility' => 'User and User Admin Only',
      'label' => 'Email (Primary)',
    ]);
    $this->createTestEntity('UFField', [
      'uf_group_id' => $this->ids['UFGroup']['ContributionPage_post'],
      'field_name' => 'group',
      'visibility' => 'User and User Admin Only',
      'label' => 'Group(s)',
    ]);

    $contributionID = $this->contributionCreate(['contact_id' => $cid, 'contribution_page_id' => $contributionPage['id'], 'total_amount' => 10]);

    $mut = new CiviMailUtils($this);
    Civi::settings()->set('allow_mail_from_logged_in_contact', TRUE);

    CRM_Contribute_BAO_ContributionPage::sendMail(
      $cid,
      [
        'receipt_from_name' => 'dontcare',
        'receipt_from_email' => 'benny_jetts@example.com',
        'title' => 'dontcare',
        'is_email_receipt' => TRUE,
        'contribution_id' => $contributionID,
        'modelProps' => [
          'contributionID' => $contributionID,
          'contactID' => $cid,
        ],
      ]
    );

    // Should include the public frontend title, but not the nonpublic group
    $mut->checkMailLog(['g-2-front'], ['g1', 'g-1-front']);

    $this->callAPISuccess('Contact', 'delete', ['id' => $cid]);
    $this->callAPISuccess('Group', 'delete', ['id' => $groupID1]);
    $this->callAPISuccess('Group', 'delete', ['id' => $groupID2]);
  }

}

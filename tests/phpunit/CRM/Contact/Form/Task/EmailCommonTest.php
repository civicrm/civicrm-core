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

use Civi\Api4\Activity;

/**
 * Test class for CRM_Contact_Form_Task_EmailCommon.
 * @group headless
 */
class CRM_Contact_Form_Task_EmailCommonTest extends CiviUnitTestCase {

  /**
   * Set up for tests.
   *
   * @throws \CRM_Core_Exception
   */
  protected function setUp() {
    parent::setUp();
    $this->_contactIds = [
      $this->individualCreate(['first_name' => 'Antonia', 'last_name' => 'D`souza']),
      $this->individualCreate(['first_name' => 'Anthony', 'last_name' => 'Collins']),
    ];
    $this->_optionValue = $this->callAPISuccess('optionValue', 'create', [
      'label' => '"Seamus Lee" <seamus@example.com>',
      'option_group_id' => 'from_email_address',
    ]);
  }

  /**
   * Cleanup after test class.
   *
   * Make sure the  setting is returned to 'stock'.
   */
  public function tearDown() {
    Civi::settings()->set('allow_mail_from_logged_in_contact', 0);
    parent::tearDown();
  }

  /**
   * Test generating domain emails
   *
   * @throws \CRM_Core_Exception
   */
  public function testDomainEmailGeneration() {
    $emails = CRM_Core_BAO_Email::domainEmails();
    $this->assertNotEmpty($emails);
    $optionValue = $this->callAPISuccess('OptionValue', 'Get', [
      'id' => $this->_optionValue['id'],
    ]);
    $this->assertTrue(array_key_exists('"Seamus Lee" <seamus@example.com>', $emails));
    $this->assertEquals('"Seamus Lee" <seamus@example.com>', $optionValue['values'][$this->_optionValue['id']]['label']);
  }

  /**
   * Test email uses signature.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testPostProcessWithSignature() {
    $mut = new CiviMailUtils($this, TRUE);
    $bcc1 = $this->individualCreate(['email' => 'bcc1@example.com']);
    $bcc2 = $this->individualCreate(['email' => 'bcc2@example.com']);
    $emails = $this->callAPISuccess('Email', 'getlist', ['input' => 'bcc'])['values'];
    $bcc  = [];
    foreach ($emails as $email) {
      $bcc[] = $email['id'];
    }
    $bcc = implode(',', $bcc);

    Civi::settings()->set('allow_mail_from_logged_in_contact', 1);
    $loggedInContactID = $this->createLoggedInUser();
    /* @var CRM_Contact_Form_Task_Email $form*/
    $form = $this->getFormObject('CRM_Contact_Form_Task_Email');

    for ($i = 0; $i < 27; $i++) {
      $email = 'spy' . $i . '@secretsquirrels.com';
      $contactID = $this->individualCreate(['email' => $email]);
      $form->_contactIds[$contactID] = $contactID;
      $form->_toContactEmails[$this->callAPISuccessGetValue('Email', ['return' => 'id', 'email' => $email])] = $email;
    }
    $deceasedContactID = $this->individualCreate(['is_deceased' => 1, 'email' => 'dead@example.com']);
    $form->_contactIds[$deceasedContactID] = $deceasedContactID;
    $form->_toContactEmails[$this->callAPISuccessGetValue('Email', ['return' => 'id', 'email' => 'dead@example.com'])] = 'dead@example.com';

    $loggedInEmail = $this->callAPISuccess('Email', 'create', [
      'email' => 'mickey@mouse.com',
      'location_type_id' => 1,
      'is_primary' => 1,
      'contact_id' => $loggedInContactID,
      'signature_text' => 'This is a test Signature',
      'signature_html' => '<p>This is a test Signature</p>',
    ]);
    $form->_allContactIds = $form->_toContactIds = $form->_contactIds;
    $form->_emails = [$loggedInEmail['id'] => 'mickey@mouse.com'];
    $form->_fromEmails = [$loggedInEmail['id'] => 'mickey@mouse.com'];
    // This rule somehow disappears if there's a form-related test before us,
    // so register it again. See packages/HTML/QuickForm/file.php.
    $form->registerRule('maxfilesize', 'callback', '_ruleCheckMaxFileSize', 'HTML_QuickForm_file');
    CRM_Contact_Form_Task_EmailCommon::preProcessFromAddress($form);
    $form->buildQuickForm();
    $form->submit(array_merge($form->_defaultValues, [
      'from_email_address' => $loggedInEmail['id'],
      'subject' => 'Really interesting stuff',
      'bcc_id' => $bcc,
      'cc_id' => '',
    ]));
    $mut->checkMailLog([
      'This is a test Signature',
    ]);
    $mut->stop();
    $activity = Activity::get(FALSE)->setSelect(['details'])->execute()->first();
    $bccUrl1 = CRM_Utils_System::url('civicrm/contact/view', ['reset' => 1, 'cid' => $bcc1], TRUE);
    $bccUrl2 = CRM_Utils_System::url('civicrm/contact/view', ['reset' => 1, 'cid' => $bcc2], TRUE);
    $this->assertContains("bcc : <a href='" . $bccUrl1 . "'>Mr. Anthony Anderson II</a>, <a href='" . $bccUrl2 . "'>Mr. Anthony Anderson II</a>", $activity['details']);
    $this->assertEquals([
      [
        'text' => '27 messages were sent successfully. ',
        'title' => 'Messages Sent',
        'type' => 'success',
        'options' => NULL,
      ],
      [
        'text' => '(because no email address on file or communication preferences specify DO NOT EMAIL or Contact is deceased or Primary email address is On Hold)<ul><li><a href="/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=' . $deceasedContactID . '" title="dead@example.com">Mr. Anthony Anderson II</a></li></ul>',
        'title' => 'One Message Not Sent',
        'type' => 'info',
        'options' => NULL,
      ],
    ], CRM_Core_Session::singleton()->getStatus());
  }

}

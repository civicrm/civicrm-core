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
  * Test class for CRM_Contact_Form_Task_EmailCommon.
  * @group headless
  */
class CRM_Contact_Form_Task_EmailCommonTest extends CiviUnitTestCase {

  protected function setUp() {
    parent::setUp();
    $this->_contactIds = [
      $this->individualCreate(['first_name' => 'Antonia', 'last_name' => 'D`souza']),
      $this->individualCreate(['first_name' => 'Anthony', 'last_name' => 'Collins']),
    ];
    $this->_optionValue = $this->callApiSuccess('optionValue', 'create', [
      'label' => '"Seamus Lee" <seamus@example.com>',
      'option_group_id' => 'from_email_address',
    ]);
  }

  /**
   * Test generating domain emails
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

  public function testPostProcessWithSignature() {
    $mut = new CiviMailUtils($this, TRUE);
    Civi::settings()->set('allow_mail_from_logged_in_contact', 1);
    $loggedInContactID = $this->createLoggedInUser();
    $form = new CRM_Contact_Form_Task_Email();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $form->controller = new CRM_Core_Controller();
    for ($i = 0; $i < 27; $i++) {
      $email = 'spy' . $i . '@secretsquirrels.com';
      $contactID = $this->individualCreate(['email' => $email]);
      $form->_contactIds[$contactID] = $contactID;
      $form->_toContactEmails[$this->callAPISuccessGetValue('Email', ['return' => 'id', 'email' => $email])] = $email;
    }
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
    CRM_Contact_Form_Task_EmailCommon::buildQuickForm($form);
    CRM_Contact_Form_Task_EmailCommon::submit($form, array_merge($form->_defaultValues, [
      'from_email_address' => $loggedInEmail['id'],
      'subject' => 'Really interesting stuff',
    ]));
    $mut->checkMailLog([
      'This is a test Signature',
    ]);
    $mut->stop();
    Civi::settings()->set('allow_mail_from_logged_in_contact', 0);
  }

}

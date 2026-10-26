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
use Civi\Api4\Email;
use Civi\Api4\SiteEmailAddress;
use Civi\Test\Invasive;

/**
 * Test class for CRM_Contact_Form_Task_Email.
 * @group headless
 */
class CRM_Contact_Form_Task_EmailTest extends CiviUnitTestCase {

  /**
   * Set up for tests.
   *
   */
  protected function setUp(): void {
    parent::setUp();
    $this->individualCreate(['first_name' => 'Antonia', 'last_name' => 'D`souza']);
    $this->individualCreate(['first_name' => 'Anthony', 'last_name' => 'Collins']);

    $this->createTestEntity('SiteEmailAddress', [
      'display_name' => 'Seamus Lee',
      'email' => 'seamus@example.com',
    ], 'aussie');
  }

  /**
   * Cleanup after test class.
   *
   * Make sure the  setting is returned to 'stock'.
   */
  public function tearDown(): void {
    Civi::settings()->set('allow_mail_from_logged_in_contact', 0);
    if (!empty($this->ids['SiteEmailAddress'])) {
      SiteEmailAddress::delete(FALSE)->addWhere('id', 'IN', $this->ids['SiteEmailAddress'])->execute();
    }
    parent::tearDown();
  }

  /**
   * Test generating domain emails
   */
  public function testDomainEmailGeneration(): void {
    $emails = CRM_Core_BAO_Email::domainEmails();
    $this->assertNotEmpty($emails);
    $this->assertArrayHasKey('"Seamus Lee" <seamus@example.com>', $emails);
  }

  /**
   * Test email uses signature.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testPostProcessWithSignature(): void {
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
    $loggedInEmail = $this->callAPISuccess('Email', 'create', [
      'email' => 'mickey@mouse.com',
      'location_type_id' => $this->getLocationTypeID('Home'),
      'is_primary' => 1,
      'contact_id' => $loggedInContactID,
      'signature_text' => 'This is a test Signature',
      'signature_html' => '<p>This is a test Signature</p>',
    ]);

    $to = $form_contactIds = [];
    for ($i = 0; $i < 27; $i++) {
      $email = 'spy' . $i . '@secretsquirrels.com';
      $contactID = $this->individualCreate(['email' => $email]);
      $form_contactIds[$contactID] = $contactID;
      $to[] = Email::get(FALSE)->addWhere('contact_id', '=', $contactID)->execute()->first()['id'];
    }
    $deceasedContactID = $this->individualCreate(['is_deceased' => 1, 'email' => 'dead@example.com']);
    $to[] = Email::get(FALSE)->addWhere('contact_id', '=', $deceasedContactID)->execute()->first()['id'];
    /** @var CRM_Contact_Form_Task_Email $form */
    $form = $this->getFormObject('CRM_Contact_Form_Task_Email', [
      'to' => implode(',', $to),
      'subject' => 'Really interesting stuff',
      'bcc_id' => $bcc,
      'cc_id' => '',
      'from_email_address' => $loggedInEmail['id'],
      'html_message' => 'blah',
      'text_message' => 'blah',
    ]);
    $form->_contactIds = $form_contactIds;
    $form->_contactIds[$deceasedContactID] = $deceasedContactID;

    $form->_allContactIds = $form->_toContactIds = $form->_contactIds;
    $form->isSearchContext = FALSE;
    $form->buildForm();
    $this->assertEquals([
      'html_message' => '<br /><br />--<p>This is a test Signature</p>',
      'text_message' => '

--
This is a test Signature',
      'to' => implode(',', array_slice($to, 0, 27)),
    ], $form->_defaultValues);
    $form->postProcess();
    $activity = Activity::get(FALSE)->setSelect(['details'])->execute()->first();
    $bccUrl1 = CRM_Utils_System::url('civicrm/contact/view', ['reset' => 1, 'cid' => $bcc1], TRUE);
    $bccUrl2 = CRM_Utils_System::url('civicrm/contact/view', ['reset' => 1, 'cid' => $bcc2], TRUE);
    $this->assertStringContainsString("bcc : <a href='" . $bccUrl1 . "'>Mr. Anthony Anderson II</a>, <a href='" . $bccUrl2 . "'>Mr. Anthony Anderson II</a>", $activity['details']);
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

  /**
   * Only deliverable email IDs are returned, and several emails of one contact are kept.
   */
  public function testGetEmailsFiltersSuppressedAndKeepsMultipleEmailsPerContact(): void {
    $contactID = $this->individualCreate(['email' => 'first@example.com']);
    $secondEmailID = $this->createTestEntity('Email', [
      'contact_id' => $contactID,
      'email' => 'second@example.com',
      'location_type_id:name' => 'Other',
    ], 'second')['id'];
    $firstEmailID = Email::get(FALSE)->addWhere('email', '=', 'first@example.com')->execute()->first()['id'];
    $onHoldEmailID = $this->createTestEntity('Email', [
      'contact_id' => $this->individualCreate(),
      'email' => 'onhold@example.com',
      'on_hold' => 1,
    ], 'onhold')['id'];
    $doNotEmailContactID = $this->individualCreate(['email' => 'dne@example.com', 'do_not_email' => 1]);
    $doNotEmailID = Email::get(FALSE)->addWhere('contact_id', '=', $doNotEmailContactID)->execute()->first()['id'];

    $form = $this->getFormObject('CRM_Contact_Form_Task_Email', [
      'to' => implode(',', [$firstEmailID, $secondEmailID, $onHoldEmailID, $doNotEmailID]),
    ]);
    $emails = Invasive::call([$form, 'getEmails']);

    $this->assertEqualsCanonicalizing([$firstEmailID, $secondEmailID], array_keys($emails));
    $this->assertSame($contactID, (int) $emails[$secondEmailID]['contact_id']);
    $this->assertSame('second@example.com', $emails[$secondEmailID]['email']);
    $suppressed = Invasive::get([$form, 'suppressedEmails']);
    $this->assertCount(2, $suppressed);
  }

  /**
   * An empty or non-numeric "to" value yields no emails.
   */
  public function testGetEmailsIgnoresEmptyAndNonNumericValues(): void {
    $form = $this->getFormObject('CRM_Contact_Form_Task_Email', ['to' => ' , abc,']);
    $this->assertSame([], Invasive::call([$form, 'getEmails']));
  }

  /**
   * When a specific (non-primary) email is requested it is the default "To" value.
   */
  public function testSpecificEmailIsDefaultTo(): void {
    $contactID = $this->individualCreate();
    $this->createTestEntity('Email', [
      'contact_id' => $contactID,
      'email' => 'primary@example.com',
      'location_type_id:name' => 'Home',
      'is_primary' => TRUE,
      'on_hold' => TRUE,
    ], 'primary');
    $otherEmailID = $this->createTestEntity('Email', [
      'contact_id' => $contactID,
      'email' => 'other@example.com',
      'location_type_id:name' => 'Other',
    ], 'other')['id'];

    /** @var CRM_Contact_Form_Task_Email $form */
    $form = $this->getFormObject('CRM_Contact_Form_Task_Email');
    $form->_contactIds = [$contactID => $contactID];
    $form->_allContactIds = $form->_toContactIds = $form->_contactIds;
    $form->_toEmail = Email::get(FALSE)->addWhere('id', '=', $otherEmailID)->execute()->first();
    $form->isSearchContext = FALSE;
    $form->buildForm();

    $this->assertSame((string) $otherEmailID, (string) $form->_defaultValues['to']);
    $this->assertEmpty(Invasive::get([$form, 'suppressedEmails']));
    $toEmails = Invasive::get([$form, '_toContactDetails']);
    $this->assertCount(1, $toEmails);
    $this->assertSame('other@example.com', $toEmails[$contactID]['email']);
    $this->assertEmpty($toEmails[$contactID]['on_hold']);
  }

}

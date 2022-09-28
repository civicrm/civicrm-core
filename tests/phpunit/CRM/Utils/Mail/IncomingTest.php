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
 * Class for testing CRM_Utils_Mail_Incoming.
 *
 * @group headless
 */
class CRM_Utils_Mail_IncomingTest extends CiviUnitTestCase {

  /**
   * Email that receives the message.
   *
   * @var string
   */
  protected $email;

  /**
   * Name of the contact.
   *
   * @var string
   */
  protected $name;

  public function setUp(): void {
    parent::setUp();

    $rand = rand(0, 1000);
    $this->email = "test{$rand}@example.com";
    $this->name = "Test$rand";
  }

  /**
   * Tests that an email to an existent individual contact uses that contact.
   */
  public function testEmailUseExistentIndividualContact(): void {
    $expectedContactId = $this->individualCreate(['email' => $this->email]);

    $receivedContactId = CRM_Utils_Mail_Incoming::getContactID($this->email, $this->name, TRUE, $mail);

    $this->assertEquals($expectedContactId, $receivedContactId);
  }

  /**
   * Tests that an email to a non-existent contact creates an individual.
   */
  public function testEmailCreateIndividualContact(): void {
    $contact = CRM_Contact_BAO_Contact::matchContactOnEmail($this->email, 'Individual');
    $this->assertNull($contact);

    CRM_Utils_Mail_Incoming::getContactID($this->email, $this->name, TRUE, $mail);

    $contact = CRM_Contact_BAO_Contact::matchContactOnEmail($this->email, 'Individual');
    $this->assertNotNull($contact);
  }

  /**
   * Tests that an email to an existent organization contact uses that contact.
   */
  public function testEmailUseExistentOrganizationContact(): void {
    $expectedContactId = $this->organizationCreate(['email' => $this->email]);

    $receivedContactId = CRM_Utils_Mail_Incoming::getContactID($this->email, $this->name, TRUE, $mail);

    $this->assertEquals($expectedContactId, $receivedContactId);
  }

  /**
   * Tests that individual contact has precedence over organization contacts.
   */
  public function testEmailPrefersExistentIndividualContact(): void {
    $individualContactId = $this->individualCreate(['email' => $this->email]);
    $this->organizationCreate(['email' => $this->email]);

    $receivedContactId = CRM_Utils_Mail_Incoming::getContactID($this->email, $this->name, TRUE, $mail);

    $this->assertEquals($individualContactId, $receivedContactId);
  }

}

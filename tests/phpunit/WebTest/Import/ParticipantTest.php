<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/


require_once 'WebTest/Import/ImportCiviSeleniumTestCase.php';
class WebTest_Import_ParticipantTest extends ImportCiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /*
     *  Test participant import for Individuals.
     */
  function testParticipantImportIndividual() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Get sample import data.
    list($headers, $rows) = $this->_participantIndividualCSVData();
   
    // Create and import csv from provided data and check imported data.
    $fieldMapper = array(
      'mapper[0][0]' => 'email',
      'mapper[1][0]' => 'event_id',
      'mapper[2][0]' => 'participant_fee_level',
      'mapper[4][0]' => 'participant_status_id',
    );

    $this->importCSVComponent('Event', $headers, $rows, 'Individual', 'Skip', $fieldMapper);
  }

  /*
     *  Test participant import for Organizations.
     */
  function testParticipantImportOrganization() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Get sample import data.
    list($headers, $rows) = $this->_participantOrganizationCSVData();

    // Create and import csv from provided data and check imported data.
    $fieldMapper = array(
      'mapper[0][0]' => 'organization_name',
      'mapper[1][0]' => 'event_id',
      'mapper[2][0]' => 'participant_fee_level',
      'mapper[4][0]' => 'participant_status_id',
    );

    $this->importCSVComponent('Event', $headers, $rows, 'Organization', 'Skip', $fieldMapper);
  }

  /*
     *  Test participant import for Households.
     */
  function testParticipantImportHousehold() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Get sample import data.
    list($headers, $rows) = $this->_participantHouseholdCSVData();

    // Create and import csv from provided data and check imported data.
    $fieldMapper = array(
      'mapper[0][0]' => 'household_name',
      'mapper[1][0]' => 'event_id',
      'mapper[2][0]' => 'participant_fee_level',
      'mapper[4][0]' => 'participant_status_id',
    );

    $this->importCSVComponent('Event', $headers, $rows, 'Household', 'Skip', $fieldMapper);
  }

  /*
     *  Helper function to provide data for participant import for Individuals.
     */
  function _participantIndividualCSVData() {
    $eventInfo = $this->_addNewEvent();

    $firstName1 = substr(sha1(rand()), 0, 7);
    $email1 = 'mail_' . substr(sha1(rand()), 0, 7) . '@example.com';
    $this->webtestAddContact($firstName1, 'Anderson', $email1);

    $firstName2 = substr(sha1(rand()), 0, 7);
    $email2 = 'mail_' . substr(sha1(rand()), 0, 7) . '@example.com';
    $this->webtestAddContact($firstName2, 'Anderson', $email2);

    $headers = array(
      'email' => 'Email',
      'event_id' => 'Event Id',
      'fee_level' => 'Fee Level',
      'role' => 'Participant Role',
      'status' => 'Participant Status',
      'register_date' => 'Register date',
    );

    $rows = array(
      array(
        'email' => $email1,
        'event_id' => $eventInfo['event_id'],
        'fee_level' => 'Member',
        'role' => 1,
        'status' => 1,
        'register_date' => '2011-03-30',
      ),
      array(
        'email' => $email2,
        'event_id' => $eventInfo['event_id'],
        'fee_level' => 'Non-Member',
        'role' => 1,
        'status' => 1,
        'register_date' => '2011-03-30',
      ),
    );

    return array($headers, $rows);
  }

  /*
     *  Helper function to provide data for participant import for Household.
     */
  function _participantHouseholdCSVData() {
    $eventInfo = $this->_addNewEvent();

    $household1 = substr(sha1(rand()), 0, 7) . ' home';
    $this->webtestAddHousehold($household1, TRUE);

    $household2 = substr(sha1(rand()), 0, 7) . ' home';
    $this->webtestAddHousehold($household2, TRUE);

    $headers = array(
      'household' => 'Household Name',
      'event_id' => 'Event Id',
      'fee_level' => 'Fee Level',
      'role' => 'Participant Role',
      'status' => 'Participant Status',
      'register_date' => 'Register date',
    );

    $rows = array(
      array(
        'household' => $household1,
        'event_id' => $eventInfo['event_id'],
        'fee_level' => 'Member',
        'role' => 1,
        'status' => 1,
        'register_date' => '2011-03-30',
      ),
      array(
        'household' => $household2,
        'event_id' => $eventInfo['event_id'],
        'fee_level' => 'Non-Member',
        'role' => 1,
        'status' => 1,
        'register_date' => '2011-03-30',
      ),
    );

    return array($headers, $rows);
  }

  /*
     *  Helper function to provide data for participant import for Organization.
     */
  function _participantOrganizationCSVData() {
    $eventInfo = $this->_addNewEvent();

    $organization1 = substr(sha1(rand()), 0, 7) . ' org';
    $this->webtestAddOrganization($organization1, TRUE);

    $organization2 = substr(sha1(rand()), 0, 7) . ' org';
    $this->webtestAddOrganization($organization2, TRUE);

    $headers = array(
      'organization' => 'Organization Name',
      'event_id' => 'Event Id',
      'fee_level' => 'Fee Level',
      'role' => 'Participant Role',
      'status' => 'Participant Status',
      'register_date' => 'Register date',
    );

    $rows = array(
      array(
        'organization' => $organization1,
        'event_id' => $eventInfo['event_id'],
        'fee_level' => 'Member',
        'role' => 1,
        'status' => 1,
        'register_date' => '2011-03-30',
      ),
      array(
        'organization' => $organization2,
        'event_id' => $eventInfo['event_id'],
        'fee_level' => 'Non-Member',
        'role' => 1,
        'status' => 1,
        'register_date' => '2011-03-30',
      ),
    );

    return array($headers, $rows);
  }

  /*
     * Helper function to add new event
     *
     * @params array $params parameters to create an event
     *
     * @return array $params event details of newly created event
     */
  function _addNewEvent($params = array(
    )) {

    if (empty($params)) {

      // We need a payment processor
      $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);
      $this->webtestAddPaymentProcessor($processorName);

      // create an event
      $eventTitle = 'My Conference - ' . substr(sha1(rand()), 0, 7);
      $params = array(
        'title' => $eventTitle,
        'template_id' => 6,
        'event_type_id' => 4,
        'payment_processor' => $processorName,
        'fee_level' => array(
          'Member' => "250.00",
          'Non-Member' => "325.00",
        ),
      );
    }

    $this->openCiviPage('event/add', 'reset=1&action=add', '_qf_EventInfo_upload-bottom');

    // Let's start filling the form with values.
    $this->select("event_type_id", "value={$params['event_type_id']}");

    // Attendee role s/b selected now.
    $this->select("default_role_id", "value=1");

    // Enter Event Title, Summary and Description
    $this->type("title", $params['title']);
    $this->type("summary", "This is a great conference. Sign up now!");
    $this->fillRichTextField("description", "Here is a description for this event.", 'CKEditor');

    // Choose Start and End dates.
    // Using helper webtestFillDate function.
    $this->webtestFillDateTime("start_date", "+1 week");
    $this->webtestFillDateTime("end_date", "+1 week 1 day 8 hours ");

    $this->type("max_participants", "50");
    $this->click("is_map");
    $this->click("_qf_EventInfo_upload-bottom");

    // Wait for Location tab form to load
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Go to Fees tab
    $this->click("link=Fees");
    $this->waitForElementPresent("_qf_Fee_upload-bottom");
    $this->click("CIVICRM_QFID_1_is_monetary");

    // select newly created processor
    $xpath = "xpath=//label[text() = '{$processorName}']/preceding-sibling::input[1]";
    $this->assertElementContainsText('paymentProcessor', $processorName);
    $this->check($xpath);
    $this->select("financial_type_id", "value=4");

    $counter = 1;
    foreach ($params['fee_level'] as $label => $amount) {
      $this->type("label_{$counter}", $label);
      $this->type("value_{$counter}", $amount);
      $counter++;
    }

    $this->click("_qf_Fee_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Go to Online Registration tab
    $this->click("link=Online Registration");
    $this->waitForElementPresent("_qf_Registration_upload-bottom");

    $this->check("is_online_registration");
    $this->assertChecked("is_online_registration");

    $this->fillRichTextField("intro_text", "Fill in all the fields below and click Continue.");

    // enable confirmation email
    $this->click("CIVICRM_QFID_1_is_email_confirm");
    $this->type("confirm_from_name", "Jane Doe");
    $this->type("confirm_from_email", "jane.doe@example.org");

    $this->click("_qf_Registration_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForTextPresent("'Registration' information has been saved.");

    // verify event input on info page
    // start at Manage Events listing
    $this->openCiviPage('event/manage', 'reset=1');
    $this->click("link=" . $params['title']);

    $this->waitForPageToLoad($this->getTimeoutMsec());

    $matches = array();
    preg_match('/id=([0-9]+)/', $this->getLocation(), $matches);
    $params['event_id'] = $matches[1];

    return $params;
  }
}


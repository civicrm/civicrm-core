<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

/**
 * Class WebTest_Import_MatchExternalIdTest
 */
class WebTest_Import_MatchExternalIdTest extends ImportCiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   *  Test participant import for Individuals matching on external identifier.
   */
  public function testContributionImport() {
    $this->webtestLogin();

    // Get sample import data.
    list($headers, $rows, $fieldMapper) = $this->_contributionIndividualCSVData();

    // Create and import csv from provided data and check imported data.
    $this->importCSVComponent('Contribution', $headers, $rows, 'Individual', 'Insert new contributions', $fieldMapper);
  }

  /**
   *  Test membership import for Individuals matching on external identifier.
   */
  public function testMemberImportIndividual() {
    $this->webtestLogin();

    // Get membership import data for Individuals.
    list($headers, $rows, $fieldMapper) = $this->_memberIndividualCSVData();

    // Import participants and check imported data.
    $this->importCSVComponent('Membership', $headers, $rows, 'Individual', 'Skip', $fieldMapper);
  }

  /**
   *  Test participant import for Individuals matching on external identifier.
   */
  public function testParticipantImportIndividual() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Get sample import data.
    list($headers, $rows, $fieldMapper) = $this->_participantIndividualCSVData();

    // Create and import csv from provided data and check imported data.
    $this->importCSVComponent('Event', $headers, $rows, 'Individual', 'Skip', $fieldMapper);
  }

  /**
   * Helper function to provide data for contribution  import for Individual.
   *
   * @return array
   */
  public function _contributionIndividualCSVData() {
    $firstName1 = substr(sha1(rand()), 0, 7);
    $lastName1 = substr(sha1(rand()), 0, 7);
    $externalId1 = substr(sha1(rand()), 0, 4);

    $this->_addContact($firstName1, $lastName1, $externalId1);

    $firstName2 = substr(sha1(rand()), 0, 7);
    $lastName2 = substr(sha1(rand()), 0, 7);
    $externalId2 = substr(sha1(rand()), 0, 4);

    $this->_addContact($firstName2, $lastName2, $externalId2);

    $headers = array(
      'external_identifier' => 'External Identifier',
      'fee_amount' => 'Fee Amount',
      'financial_type' => 'Financial Type',
      'contribution_status_id' => 'Contribution Status',
      'total_amount' => 'Total Amount',
    );

    $rows = array(
      array(
        'external_identifier' => $externalId1,
        'fee_amount' => '200',
        'financial_type' => 'Donation',
        'contribution_status_id' => 'Completed',
        'total_amount' => '200',
      ),
      array(
        'external_identifier' => $externalId2,
        'fee_amount' => '400',
        'financial_type' => 'Donation',
        'contribution_status_id' => 'Completed',
        'total_amount' => '400',
      ),
    );
    $fieldMapper = array(
      'mapper[0][0]' => 'external_identifier',
      'mapper[2][0]' => 'financial_type',
      'mapper[4][0]' => 'total_amount',
    );
    return array($headers, $rows, $fieldMapper);
  }

  /**
   * Helper function to provide data for membership import for Individual.
   *
   * @return array
   */
  public function _memberIndividualCSVData() {
    $memTypeParams = $this->webtestAddMembershipType();

    $firstName1 = substr(sha1(rand()), 0, 7);
    $lastName1 = substr(sha1(rand()), 0, 7);
    $externalId1 = substr(sha1(rand()), 0, 4);

    $this->_addContact($firstName1, $lastName1, $externalId1);
    $startDate1 = date('Y-m-d');
    $year = date('Y') - 1;

    $firstName2 = substr(sha1(rand()), 0, 7);
    $lastName2 = substr(sha1(rand()), 0, 7);
    $externalId2 = substr(sha1(rand()), 0, 4);

    $this->_addContact($firstName2, $lastName2, $externalId2);
    $startDate2 = date('Y-m-d', mktime(0, 0, 0, 9, 10, $year));

    $headers = array(
      'external_identifier' => 'External Identifier',
      'membership_type_id' => 'Membership Type',
      'membership_start_date' => 'Membership Start Date',
    );
    $rows = array(
      array(
        'external_identifier' => $externalId1,
        'membership_type_id' => $memTypeParams['membership_type'],
        'membership_start_date' => $startDate1,
      ),
      array(
        'external_identifier' => $externalId2,
        'membership_type_id' => $memTypeParams['membership_type'],
        'membership_start_date' => $startDate2,
      ),
    );

    $fieldMapper = array(
      'mapper[0][0]' => 'external_identifier',
      'mapper[1][0]' => 'membership_type_id',
      'mapper[2][0]' => 'membership_start_date',
    );
    return array($headers, $rows, $fieldMapper);
  }

  /**
   * Helper function to provide data for participant import for Individual.
   *
   * @return array
   */
  public function _participantIndividualCSVData() {
    $eventInfo = $this->_addNewEvent();

    $firstName1 = substr(sha1(rand()), 0, 7);
    $lastName1 = substr(sha1(rand()), 0, 7);
    $externalId1 = substr(sha1(rand()), 0, 4);

    $this->_addContact($firstName1, $lastName1, $externalId1);

    $firstName2 = substr(sha1(rand()), 0, 7);
    $lastName2 = substr(sha1(rand()), 0, 7);
    $externalId2 = substr(sha1(rand()), 0, 4);

    $this->_addContact($firstName2, $lastName2, $externalId2);

    $headers = array(
      'external_identifier' => 'External Identifier',
      'event_id' => 'Event Id',
      'fee_level' => 'Fee Level',
      'role' => 'Participant Role',
      'status' => 'Participant Status',
      'register_date' => 'Register date',
    );

    $rows = array(
      array(
        'external_identifier' => $externalId1,
        'event_id' => $eventInfo['event_id'],
        'fee_level' => 'Member',
        'role' => 1,
        'status' => 1,
        'register_date' => '2011-03-30',
      ),
      array(
        'external_identifier' => $externalId2,
        'event_id' => $eventInfo['event_id'],
        'fee_level' => 'Non-Member',
        'role' => 1,
        'status' => 1,
        'register_date' => '2011-03-30',
      ),
    );

    $fieldMapper = array(
      'mapper[0][0]' => 'external_identifier',
      'mapper[1][0]' => 'event_id',
      'mapper[2][0]' => 'participant_fee_level',
      'mapper[4][0]' => 'participant_status_id',
    );

    return array($headers, $rows, $fieldMapper);
  }

  /**
   * Helper function to add new contact.
   *
   * @param string $firstName
   * @param string $lastName
   * @param int $externalId
   *
   * @return int
   *   external id
   */
  public function _addContact($firstName, $lastName, $externalId) {
    $this->openCiviPage('contact/add', 'reset=1&ct=Individual');

    //fill in first name
    $this->type("first_name", $firstName);

    //fill in last name
    $this->type("last_name", $lastName);

    //fill in external identifier
    $this->type("external_identifier", $externalId);

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");

    return $externalId;
  }

  /**
   * Helper function to add new event.
   *
   * @param array $params
   *
   * @return array
   *   event details of newly created event
   */
  public function _addNewEvent($params = array()) {
    if (empty($params)) {

      // Use default payment processor
      $processorName = 'Test Processor';
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
    $this->select2('payment_processor', $processorName, TRUE);
    $this->select("financial_type_id", "value=4");

    $counter = 1;
    foreach ($params['fee_level'] as $label => $amount) {
      $this->type("label_{$counter}", $label);
      $this->type("value_{$counter}", $amount);
      $counter++;
    }

    $this->click("_qf_Fee_upload-bottom");
    $this->waitForElementPresent("_qf_Fee_upload-bottom");

    // Go to Online Registration tab
    $this->click("link=Online Registration");
    $this->waitForElementPresent("_qf_Registration_upload-bottom");

    $this->click("is_online_registration");
    $this->assertChecked("is_online_registration");

    $this->fillRichTextField("intro_text", "Fill in all the fields below and click Continue.", 'CKEditor', TRUE);

    // enable confirmation email
    $this->click("CIVICRM_QFID_1_is_email_confirm");
    $this->type("confirm_from_name", "Jane Doe");
    $this->type("confirm_from_email", "jane.doe@example.org");

    $this->click("_qf_Registration_upload-bottom");
    $this->waitForElementPresent("_qf_Registration_upload-bottom");
    $this->waitForText('crm-notification-container', "'Online Registration' information has been saved");

    // verify event input on info page
    // start at Manage Events listing
    $this->openCiviPage('event/manage', 'reset=1');
    $this->type("xpath=//div[@class='crm-block crm-form-block crm-event-searchevent-form-block']/table/tbody/tr/td/input", $params['title']);
    $this->click("_qf_SearchEvent_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->clickLink("link=" . $params['title'], NULL);

    $params['event_id'] = $this->urlArg('id');

    return $params;
  }

}

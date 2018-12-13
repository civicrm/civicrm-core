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

require_once 'CiviTest/CiviSeleniumTestCase.php';

/**
 * Class WebTest_Campaign_OnlineEventRegistrationTest
 */
class WebTest_Campaign_OnlineEventRegistrationTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testCreateCampaign() {
    $this->webtestLogin('admin');

    // Create new group
    $title = substr(sha1(rand()), 0, 7);
    $groupName = $this->WebtestAddGroup();

    // Adding contact
    // We're using Quick Add block on the main page for this.
    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName1, "Smith", "$firstName1.smith@example.org");

    // add contact to group
    // visit group tab
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // add to group
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForElementPresent('link=Remove');

    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName2, "John", "$firstName2.john@example.org");

    // add contact to group
    // visit group tab
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // add to group
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForElementPresent('link=Remove');

    // Enable CiviCampaign module if necessary
    $this->enableComponents(array('CiviCampaign'));

    // add the required permission
    $permissions = array('edit-2-administer-civicampaign', 'edit-1-register-for-events');
    $this->changePermissions($permissions);

    // Log in as normal user
    $this->webtestLogin();

    $this->openCiviPage("campaign/add", "reset=1", "_qf_Campaign_upload-bottom");

    $campaignTitle = "Campaign $title";
    $this->type("title", $campaignTitle);

    // select the campaign type
    $this->select("campaign_type_id", "value=2");

    // fill in the description
    $this->type("description", "This is a test campaign");

    // include groups for the campaign
    $this->multiselect2("includeGroups", array("$groupName", "Advisory Board"));

    // fill the end date for campaign
    $this->webtestFillDate("end_date", "+1 year");

    // select campaign status
    $this->select("status_id", "value=2");

    // click save
    $this->click("_qf_Campaign_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForText('crm-notification-container', "Campaign $title");

    $this->waitForElementPresent("//div[@id='campaignList']/div/table/tbody/tr/td[3]/div[text()='{$campaignTitle}']/../../td[1]");
    $id = (int) $this->getText("//div[@id='campaignList']/div/table/tbody/tr/td[3]/div[text()='{$campaignTitle}']/../../td[1]");

    $this->onlineParticipantAddTest($campaignTitle, $id);
  }

  /**
   * @param $campaignTitle
   * @param int $id
   */
  public function onlineParticipantAddTest($campaignTitle, $id) {
    // Use default payment processor
    $processorName = 'Test Processor';
    $paymentProcessorId = $this->webtestAddPaymentProcessor($processorName);

    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $eventDescription = "Here is a description for this conference.";
    $this->_testAddEventInfo($id, $eventTitle, $eventDescription);

    $streetAddress = "100 Main Street";
    $this->_testAddLocation($streetAddress);

    $this->_testAddFees(FALSE, FALSE, $processorName);

    // intro text for registration page
    $registerIntro = "Fill in all the fields below and click Continue.";
    $multipleRegistrations = TRUE;
    $this->_testAddOnlineRegistration($registerIntro, $multipleRegistrations);

    $eventInfoStrings = array($eventTitle, $eventDescription, $streetAddress);
    $this->_testVerifyEventInfo($eventTitle, $eventInfoStrings);

    $registerStrings = array("Member - $ 250.00", "Non-member - $ 325.00", $registerIntro);
    $registerUrl = $this->_testVerifyRegisterPage($registerStrings);

    $numberRegistrations = 3;
    $anonymous = TRUE;

    $this->_testOnlineRegistration($campaignTitle, $registerUrl, $numberRegistrations, $anonymous);
  }

  /**
   * @param int $id
   * @param $eventTitle
   * @param $eventDescription
   */
  public function _testAddEventInfo($id, $eventTitle, $eventDescription) {
    $this->waitForElementPresent("_qf_EventInfo_upload-bottom");

    $this->select("event_type_id", "value=1");

    // select campaign
    $this->click("campaign_id");
    $this->select("campaign_id", "value=$id");

    // Attendee role s/b selected now.
    $this->select("default_role_id", "value=1");

    // Enter Event Title, Summary and Description
    $this->type("title", $eventTitle);
    $this->type("summary", "This is a great conference. Sign up now!");

    // Type description in ckEditor (fieldname, text to type, editor)
    $this->fillRichTextField("description", $eventDescription, 'CKEditor');

    // Choose Start and End dates.
    // Using helper webtestFillDate function.
    $this->webtestFillDateTime("start_date", "+1 week");
    $this->webtestFillDateTime("end_date", "+1 week 1 day 8 hours ");

    $this->type("max_participants", "50");
    $this->click("is_map");
    $this->click("_qf_EventInfo_upload-bottom");
  }

  /**
   * @param $streetAddress
   */
  public function _testAddLocation($streetAddress) {
    // Wait for Location tab form to load
    $this->waitForElementPresent("_qf_Location_upload-bottom");

    // Fill in address fields
    $streetAddress = "100 Main Street";
    $this->waitForElementPresent('address_1_street_address');
    $this->type("address_1_street_address", $streetAddress);
    $this->waitForElementPresent('address_1_city');
    $this->type("address_1_city", "San Francisco");
    $this->type("address_1_postal_code", "94117");
    $this->select('address_1_country_id', 'UNITED STATES');
    $this->select("address_1_state_province_id", "value=1004");
    $this->type("email_1_email", "info@civicrm.org");

    $this->clickLink("_qf_Location_upload-bottom", '', FALSE);

    // Wait for "saved" status msg
    $this->waitForText('crm-notification-container', "saved");
  }

  /**
   * @param bool $discount
   * @param bool $priceSet
   * @param int $processorIds
   */
  public function _testAddFees($discount = FALSE, $priceSet = FALSE, $processorIds) {
    // Go to Fees tab
    $this->click("link=Fees");
    $this->waitForElementPresent("_qf_Fee_upload-bottom");
    $this->click("CIVICRM_QFID_1_is_monetary");
    $this->select2('payment_processor', $processorIds, TRUE);
    $this->select("financial_type_id", "value=4");
    if ($priceSet) {
      // get one - TBD
    }
    else {
      $this->type("label_1", "Member");
      $this->type("value_1", "250.00");
      $this->type("label_2", "Non-member");
      $this->type("value_2", "325.00");
      //add a default fee
      $this->check("xpath=//table[@id='map-field-table']/tbody/tr[2]/td[3]/input[@name='default']");
    }

    if ($discount) {
      // enter early bird discounts TBD
    }

    $this->click("_qf_Fee_upload-bottom");

    // Wait for "saved" status msg
    $this->waitForText('crm-notification-container', "saved");
  }

  /**
   * @param $registerIntro
   * @param bool $multipleRegistrations
   */
  public function _testAddOnlineRegistration($registerIntro, $multipleRegistrations = FALSE) {
    // Go to Online Registration tab
    $this->click("link=Online Registration");
    $this->waitForElementPresent("_qf_Registration_upload-bottom");

    $this->click("is_online_registration");
    $this->assertChecked("is_online_registration");
    if ($multipleRegistrations) {
      $this->check("is_multiple_registrations");
      $this->assertChecked("is_multiple_registrations");
    }

    $this->click("xpath=//div[@id='registration_screen']/table/tbody/tr[1]/td[2]/div[@class='replace-plain']");
    $this->fillRichTextField('intro_text', $registerIntro, 'CKEditor', TRUE);

    // enable confirmation email
    $this->click("CIVICRM_QFID_1_is_email_confirm");
    $this->type("confirm_from_name", "Jane Doe");
    $this->type("confirm_from_email", "jane.doe@example.org");

    $this->click("_qf_Registration_upload-bottom");
    $this->waitForText('crm-notification-container', "saved");
  }

  /**
   * @param $eventTitle
   * @param $eventInfoStrings
   */
  public function _testVerifyEventInfo($eventTitle, $eventInfoStrings) {
    // verify event input on info page
    // start at Manage Events listing
    $this->openCiviPage("event/manage", "reset=1");
    $this->clickLink("link=$eventTitle", "link=Register Now");

    // Check for correct event info strings
    $this->assertStringsPresent($eventInfoStrings);
  }

  /**
   * @param $registerStrings
   *
   * @return string
   */
  public function _testVerifyRegisterPage($registerStrings) {
    // Go to Register page and check for intro text and fee levels
    $this->click("link=Register Now");
    $this->waitForElementPresent("_qf_Register_upload-bottom");
    $this->assertStringsPresent($registerStrings);
    return $this->getLocation();
  }

  /**
   * @param $campaignTitle
   * @param $registerUrl
   * @param int $numberRegistrations
   * @param bool $anonymous
   */
  public function _testOnlineRegistration($campaignTitle, $registerUrl, $numberRegistrations = 1, $anonymous = TRUE) {
    if ($anonymous) {
      $this->webtestLogout();
    }
    $this->open($registerUrl);

    $this->select("additional_participants", "value=" . $numberRegistrations);

    $this->type("first_name", 'Jane');
    $lastName = "Smith" . substr(sha1(rand()), 0, 7);
    $this->type("last_name", $lastName);
    $email = "smith" . substr(sha1(rand()), 0, 7) . "@example.org";
    $this->type("email-Primary", $email);

    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");
    $this->type("billing_first_name", "Jane");
    $this->type("billing_last_name", $lastName);
    $this->type("billing_street_address-5", "15 Main St.");
    $this->type(" billing_city-5", "San Jose");
    $this->select("billing_country_id-5", "value=1228");
    $this->select("billing_state_province_id-5", "value=1004");
    $this->type("billing_postal_code-5", "94129");

    $this->click("_qf_Register_upload-bottom");

    if ($numberRegistrations > 1) {
      for ($i = 1; $i <= $numberRegistrations; $i++) {
        $this->waitForPageToLoad($this->getTimeoutMsec());
        // Look for Skip button
        $this->waitForElementPresent("_qf_Participant_{$i}_next_skip-Array");
        $this->type("first_name", "Jane Add $i");
        $this->type("last_name", "Smith" . substr(sha1(rand()), 0, 7));
        $this->type("email-Primary", "smith" . substr(sha1(rand()), 0, 7) . "@example.org");
        $this->click("_qf_Participant_{$i}_next");
      }
    }

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Confirm_next-bottom");
    $confirmStrings = array("Event Fee(s)", "Billing Name and Address", "Credit Card Information");
    $this->assertStringsPresent($confirmStrings);
    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $thankStrings = array("Thank You for Registering", "Event Total", "Transaction Date");
    $this->assertStringsPresent($thankStrings);

    $this->webtestLogin();
    $this->openCiviPage('event/search', 'reset=1', '_qf_Search_refresh');

    $this->type('sort_name', $email);
    $this->click("_qf_Search_refresh");
    $this->waitForElementPresent("xpath=//div[@id='participantSearch']");
    $this->click("xpath=//div[@id='participantSearch']/table/tbody/tr/td[11]/span[1]/a[2][text()='Edit']");
    $this->waitForElementPresent("_qf_Participant_cancel-bottom");
    $this->assertElementContainsText("xpath=//div[@id='s2id_campaign_id']", "$campaignTitle");
  }

}

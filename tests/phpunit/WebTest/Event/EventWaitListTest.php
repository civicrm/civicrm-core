<?php
/*
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
 * Class WebTest_Event_EventWaitListTest
 */
class WebTest_Event_EventWaitListTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testEventWaitList() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $eventDescription = "Here is a description for this conference.";
    $this->waitForElementPresent("_qf_EventInfo_upload-bottom");

    $this->select("event_type_id", "value=1");

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

    $this->type("max_participants", "6");
    $this->click("is_map");
    $this->click("_qf_EventInfo_upload-bottom");

    $streetAddress = "100 Main Street";

    // Wait for Location tab form to load
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Location_upload-bottom");

    // Fill in address fields
    $streetAddress = "100 Main Street";
    $this->type("address_1_street_address", $streetAddress);
    $this->type("address_1_city", "San Francisco");
    $this->type("address_1_postal_code", "94117");
    $this->select('address_1_country_id', 'UNITED STATES');
    $this->select("address_1_state_province_id", "value=1004");
    $this->type("email_1_email", "info@civicrm.org");

    $this->click("_qf_Location_upload-bottom");

    // Wait for "saved" status msg
    $this->waitForElementPresent("_qf_Location_upload-bottom");
    $this->waitForTextPresent("'Event Location' information has been saved.");

    // Go to Fees tab
    $this->click("link=Fees");
    $this->waitForElementPresent("_qf_Fee_upload-bottom");
    $this->click("CIVICRM_QFID_1_is_monetary");
    $this->select2('payment_processor', $processorName, TRUE);
    $this->select("financial_type_id", "Donation");
    $this->type("label_1", "Member");
    $this->type("value_1", "250.00");
    $this->type("label_2", "Non-member");
    $this->type("value_2", "325.00");
    //set default
    $this->click("xpath=//table[@id='map-field-table']/tbody/tr[2]/td[3]/input");

    $this->click("_qf_Fee_upload-bottom");

    // Wait for "saved" status msg
    $this->waitForElementPresent("_qf_Fee_upload-bottom");
    $this->waitForTextPresent("'Fees' information has been saved.");

    // intro text for registration page
    $registerIntro = "Fill in all the fields below and click Continue.";
    $multipleRegistrations = TRUE;

    // Go to Online Registration tab
    $this->click("link=Online Registration");
    $this->waitForElementPresent("_qf_Registration_upload-bottom");
    $this->isTextPresent("BCC Confirmation To");
    $this->check("is_online_registration");
    $this->assertChecked("is_online_registration");
    if ($multipleRegistrations) {
      $this->check("is_multiple_registrations");
      $this->assertChecked("is_multiple_registrations");
    }

    $this->fillRichTextField('intro_text', $registerIntro, 'CKEditor', TRUE);

    // enable confirmation email
    $this->click("CIVICRM_QFID_1_is_email_confirm");
    $this->type("confirm_from_name", "Jane Doe");
    $this->type("confirm_from_email", "jane.doe@example.org");

    $this->click("_qf_Registration_upload-bottom");
    $this->waitForElementPresent("_qf_Registration_upload-bottom");
    $this->waitForTextPresent("'Online Registration' information has been saved.");

    $eventInfoStrings = array($eventTitle, $eventDescription, $streetAddress);
    $this->_testVerifyEventInfo($eventTitle, $eventInfoStrings);

    $registerStrings = array("250.00", "Member", "325.00", "Non-member");
    $registerUrl = $this->_testVerifyRegisterPage($registerStrings);

    $numberRegistrations = 2;
    $anonymous = TRUE;
    $this->_testOnlineRegistration($registerUrl, $numberRegistrations, $anonymous);

    $numberRegistrations = 2;
    $anonymous = TRUE;
    $this->_testOnlineRegistration($registerUrl, $numberRegistrations, $anonymous);

    //check whether event is full
    $this->open($registerUrl);
    $this->assertStringsPresent("This event is currently full.");
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
   * @param $registerUrl
   * @param int $numberRegistrations
   * @param bool $anonymous
   */
  public function _testOnlineRegistration($registerUrl, $numberRegistrations = 1, $anonymous = TRUE) {
    if ($anonymous) {
      $this->webtestLogout();
    }
    $this->open($registerUrl);

    $this->select("additional_participants", "value=" . $numberRegistrations);
    $this->type("first_name", "Jane");
    $lastName = "Smith" . substr(sha1(rand()), 0, 7);
    $this->type("last_name", $lastName);
    $this->type("email-Primary", "smith" . substr(sha1(rand()), 0, 7) . "@example.org");

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
        // Look for continue button
        $this->waitForElementPresent("_qf_Participant_{$i}_next");
        $this->type("first_name", "Jane Add {$i}");
        $lastName = "Smith" . substr(sha1(rand()), 0, 7);
        $this->type("last_name", $lastName);
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

    if ($anonymous) {
      // log back in so we're in the same state
      $this->webtestLogin();
    }
  }

}

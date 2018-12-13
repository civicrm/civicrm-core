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
 * Class WebTest_Event_MultipleEventRegistrationbyCartTest
 */
class WebTest_Event_MultipleEventRegistrationbyCartTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   * this functionality is broken hence skipping the test.
   */
  public function skiptestAuthenticatedMultipleEvent() {

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Enable shopping cart style
    $this->openCiviPage("admin/setting/preferences/event", "reset=1");
    $this->check("enable_cart");
    $this->click("_qf_Event_next-top");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    //event 1

    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle1 = 'My Conference1 - ' . substr(sha1(rand()), 0, 7);
    $eventDescription1 = "Here is a description for this conference 1.";
    $this->_testAddEventInfo($eventTitle1, $eventDescription1);

    $streetAddress1 = "100 Main Street";
    $this->_testAddLocation($streetAddress1);

    $this->_testAddFees(FALSE, FALSE, $processorName);

    // intro text for registration page
    $registerIntro = "Fill in all the fields below and click Continue.";
    $multipleRegistrations = TRUE;
    $this->_testAddOnlineRegistration($registerIntro, $multipleRegistrations);

    $eventInfoStrings1 = array($eventTitle1, $eventDescription1, $streetAddress1);
    $this->_AddEventToCart($eventTitle1, $eventInfoStrings1);

    //event 2

    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle2 = 'My Conference2 - ' . substr(sha1(rand()), 0, 7);
    $eventDescription2 = "Here is a description for this conference 2.";
    $this->_testAddEventInfo($eventTitle2, $eventDescription2);

    $streetAddress2 = "101 Main Street";
    $this->_testAddLocation($streetAddress2);

    $this->_testAddFees(FALSE, FALSE, $processorName);

    // intro text for registration page
    $registerIntro = "Fill in all the fields below and click Continue.";
    $multipleRegistrations = TRUE;
    $this->_testAddOnlineRegistration($registerIntro, $multipleRegistrations);

    $eventInfoStrings2 = array($eventTitle2, $eventDescription2, $streetAddress2);
    $this->_AddEventToCart($eventTitle2, $eventInfoStrings2);

    //event 3

    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle3 = 'My Conference3 - ' . substr(sha1(rand()), 0, 7);
    $eventDescription3 = "Here is a description for this conference 3.";
    $this->_testAddEventInfo($eventTitle3, $eventDescription3);

    $streetAddress3 = "102 Main Street";
    $this->_testAddLocation($streetAddress3);

    $this->_testAddFees(FALSE, FALSE, $processorName);

    // intro text for registration page
    $registerIntro = "Fill in all the fields below and click Continue.";
    $multipleRegistrations = TRUE;
    $this->_testAddOnlineRegistration($registerIntro, $multipleRegistrations);

    $eventInfoStrings3 = array($eventTitle3, $eventDescription3, $streetAddress3);
    $this->_AddEventToCart($eventTitle3, $eventInfoStrings3);

    //Checkout
    $value = $this->_testCheckOut();

    //three event names
    $events = array(
      1 => $eventTitle1,
      2 => $eventTitle2,
      3 => $eventTitle3,
    );
    //check the existence of the contacts who were registered and the one who did the contribution

    $this->_checkContributionsandEventRegistration($value[0], $value[1], $events);
  }

  /**
   * this functionality is broken hence skipping the test.
   */
  public function skiptestAnonymousMultipleEvent() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    //event 1

    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle1 = 'My Conference1 - ' . substr(sha1(rand()), 0, 7);
    $eventDescription1 = "Here is a description for this conference 1.";
    $this->_testAddEventInfo($eventTitle1, $eventDescription1);

    $streetAddress1 = "100 Main Street";
    $this->_testAddLocation($streetAddress1);

    $this->_testAddFees(FALSE, FALSE, $processorName);

    // intro text for registration page
    $registerIntro = "Fill in all the fields below and click Continue.";
    $multipleRegistrations = TRUE;
    $this->_testAddOnlineRegistration($registerIntro, $multipleRegistrations);

    $eventInfoStrings1 = array($eventTitle1, $eventDescription1, $streetAddress1);
    $registerUrl1 = $this->_testVerifyEventInfo($eventTitle1, $eventInfoStrings1);

    //event 2

    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle2 = 'My Conference2 - ' . substr(sha1(rand()), 0, 7);
    $eventDescription2 = "Here is a description for this conference 2.";
    $this->_testAddEventInfo($eventTitle2, $eventDescription2);

    $streetAddress2 = "101 Main Street";
    $this->_testAddLocation($streetAddress2);

    $this->_testAddFees(FALSE, FALSE, $processorName);

    // intro text for registration page
    $registerIntro = "Fill in all the fields below and click Continue.";
    $multipleRegistrations = TRUE;
    $this->_testAddOnlineRegistration($registerIntro, $multipleRegistrations);

    $eventInfoStrings2 = array($eventTitle2, $eventDescription2, $streetAddress2);
    $registerUrl2 = $this->_testVerifyEventInfo($eventTitle2, $eventInfoStrings2);

    //event 3

    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle3 = 'My Conference3 - ' . substr(sha1(rand()), 0, 7);
    $eventDescription3 = "Here is a description for this conference 3.";
    $this->_testAddEventInfo($eventTitle3, $eventDescription3);

    $streetAddress3 = "102 Main Street";
    $this->_testAddLocation($streetAddress3);

    $this->_testAddFees(FALSE, FALSE, $processorName);

    // intro text for registration page
    $registerIntro = "Fill in all the fields below and click Continue.";
    $multipleRegistrations = TRUE;
    $this->_testAddOnlineRegistration($registerIntro, $multipleRegistrations);

    $eventInfoStrings3 = array($eventTitle3, $eventDescription3, $streetAddress3);
    $registerUrl3 = $this->_testVerifyEventInfo($eventTitle3, $eventInfoStrings3);

    //Enable shopping cart style
    $this->openCiviPage("admin/setting/preferences/event", "reset=1");
    $this->check("enable_cart");
    $this->click("_qf_Event_next-top");

    $numberRegistrations = 1;
    $anonymous = TRUE;
    $this->_testOnlineRegistration($registerUrl1, $numberRegistrations, $anonymous);
    $this->_testOnlineRegistration($registerUrl2, $numberRegistrations, $anonymous);
    $this->_testOnlineRegistration($registerUrl3, $numberRegistrations, $anonymous);
    //Checkout
    $value = $this->_testCheckOut();
    // Log in using webtestLogin() method
    $this->webtestLogin();

    $this->openCiviPage("dashboard", "reset=1");

    //three event names
    $events = array(
      1 => $eventTitle1,
      2 => $eventTitle2,
      3 => $eventTitle3,
    );
    //check the existence of the contacts who were registered and the one who did the contribution

    $this->_checkContributionsandEventRegistration($value[0], $value[1], $events);
  }

  /**
   * @param $eventTitle
   * @param $eventDescription
   */
  public function _testAddEventInfo($eventTitle, $eventDescription) {
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

    $this->type("max_participants", "50");
    $this->click("is_map");
    $this->click("_qf_EventInfo_upload-bottom");
  }

  /**
   * @param $streetAddress
   */
  public function _testAddLocation($streetAddress) {
    // Wait for Location tab form to load
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Location_upload-bottom");

    $this->type("address_1_street_address", $streetAddress);
    $this->type("address_1_city", "San Francisco");
    $this->type("address_1_postal_code", "94117");
    $this->select("address_1_state_province_id", "value=1004");
    $this->type("email_1_email", "info@civicrm.org");

    $this->click("_qf_Location_upload-bottom");

    // Wait for "saved" status msg
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForTextPresent("'Location' information has been saved.");
  }

  /**
   * @param bool $discount
   * @param bool $priceSet
   * @param string $processorName
   */
  public function _testAddFees($discount = FALSE, $priceSet = FALSE, $processorName = "PP Pro") {
    // Go to Fees tab
    $this->click("link=Fees");
    $this->waitForElementPresent("_qf_Fee_upload-bottom");
    $this->click("CIVICRM_QFID_1_is_monetary");
    $this->click("xpath=//tr[@class='crm-event-manage-fee-form-block-payment_processor']/td[2]/label[text()='$processorName']");
    $this->select('financial_type_id', 'Event Fee');
    if ($priceSet) {
      // get one - TBD
    }
    else {
      $this->type("label_1", "Member");
      $this->type("value_1", "250.00");
      $this->type("label_2", "Non-member");
      $this->type("value_2", "325.00");
      $this->click("CIVICRM_QFID_2_6");
    }

    if ($discount) {
      // enter early bird discount fees
      $this->click("is_discount");
      $this->waitForElementPresent("discount_name_1");
      $this->type("discount_name_1", "Early-bird" . substr(sha1(rand()), 0, 7));
      $this->webtestFillDate("discount_start_date_1", "-1 week");
      $this->webtestFillDate("discount_end_date_1", "+2 week");
      $this->clickLink("_qf_Fee_submit", "discounted_value_2_1");
      $this->type("discounted_value_1_1", "225.00");
      $this->type("discounted_value_2_1", "300.00");
      $this->click("xpath=//fieldset[@id='discount']/fieldset/table/tbody/tr[2]/td[3]/input");
    }

    $this->click("_qf_Fee_upload-bottom");

    // Wait for "saved" status msg
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForTextPresent("'Fee' information has been saved.");
  }

  /**
   * @param $registerIntro
   * @param bool $multipleRegistrations
   */
  public function _testAddOnlineRegistration($registerIntro, $multipleRegistrations = FALSE) {
    // Go to Online Registration tab
    $this->click("link=Online Registration");
    $this->waitForElementPresent("_qf_Registration_upload-bottom");

    $this->check("is_online_registration");
    $this->assertChecked("is_online_registration");
    if ($multipleRegistrations) {
      $this->check("is_multiple_registrations");
      $this->assertChecked("is_multiple_registrations");
    }

    $this->fillRichTextField("intro_text", $registerIntro);

    // enable confirmation email
    $this->click("CIVICRM_QFID_1_is_email_confirm");
    $this->type("confirm_from_name", "Jane Doe");
    $this->type("confirm_from_email", "jane.doe@example.org");

    $this->click("_qf_Registration_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForTextPresent("'Registration' information has been saved.");
  }

  /**
   * @param $eventTitle
   * @param $eventInfoStrings
   * @param null $eventFees
   */
  public function _AddEventToCart($eventTitle, $eventInfoStrings, $eventFees = NULL) {
    // verify event input on info page
    // start at Manage Events listing
    $this->openCiviPage("event/manage", "reset=1");
    $this->clickLink("link=$eventTitle", "link=Add to Cart");
    $this->click("link=Add to Cart");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("$eventTitle has been added to your cart"));
  }

  /**
   * @param $eventTitle
   * @param $eventInfoStrings
   * @param null $eventFees
   *
   * @return string
   */
  public function _testVerifyEventInfo($eventTitle, $eventInfoStrings, $eventFees = NULL) {
    // verify event input on info page
    // start at Manage Events listing
    $this->openCiviPage("event/manage", "reset=1");
    $this->click("link=$eventTitle");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Check for correct event info strings
    $this->assertStringsPresent($eventInfoStrings);

    // Optionally verify event fees (especially for discounts)
    if ($eventFees) {
      $this->assertStringsPresent($eventFees);

    }
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
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("link=Add to Cart");
    $this->click("link=Add to Cart");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  /**
   * @return array
   */
  public function _testCheckOut() {
    //View the Cart
    $this->click("xpath=//div[@id='messages']/div/div/a[text()='View your cart.']");

    //Click on Checkout
    $this->waitForElementPresent("xpath=//a[@class='button crm-check-out-button']/span");
    $this->click("xpath=//a[@class='button crm-check-out-button']/span");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $firstName = "AB" . substr(sha1(rand()), 0, 7);
    $lastName = "XY" . substr(sha1(rand()), 0, 7);
    for ($i = 1; $i <= 3; $i++) {
      $this->type("xpath=//form[@id='ParticipantsAndPrices']/fieldset[$i]/div/fieldset/div/div/fieldset/div/div[2]/input", "{$firstName}.{$lastName}@home.com");
      $this->type("xpath=//form[@id='ParticipantsAndPrices']/fieldset[$i]/div/fieldset/div/div[2]/div[2]/input", "{$firstName}.{$lastName}@example.com");
      $this->click("xpath=//form[@id='ParticipantsAndPrices']/fieldset[$i]/div[2]/div[2]/input[2]");
    }
    $this->click("_qf_ParticipantsAndPrices_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");
    $this->type("billing_first_name", $firstName);
    $this->type("billing_last_name", $lastName);
    $this->type("billing_street_address-5", "15 Main St.");
    $this->type(" billing_city-5", "San Jose");
    $this->select("billing_country_id-5", "value=1228");
    $this->select("billing_state_province_id-5", "value=1004");
    $this->type("billing_postal_code-5", "94129");
    $this->type("billing_contact_email", "{$firstName}.{$lastName}@example.com");

    $this->click("_qf_Payment_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("This is your receipt of payment made for the following event registration."));
    return array($firstName, $lastName);
  }

  /**
   * @param string $firstName
   * @param string $lastName
   * @param $events
   */
  public function _checkContributionsandEventRegistration($firstName, $lastName, $events) {
    //Type the registered participant's email in autocomplete.
    $this->click('sort_name_navigation');
    $this->type('css=input#sort_name_navigation', "{$firstName}.{$lastName}@home.com");
    $this->typeKeys('css=input#sort_name_navigation', "{$firstName}.{$lastName}@home.com");

    // Wait for result list.
    $this->waitForElementPresent("css=div.ac_results-inner li");

    // Visit contact summary page.
    $this->click("css=div.ac_results-inner li");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //click on Events Tab
    $this->click("xpath=//li[@id='tab_participant']/a");
    //check if the participant is registered for all the three events
    foreach ($events as $key => $value) {
      $this->waitForElementPresent("link=$value");
      $this->assertTrue($this->isElementPresent("link=$value"));
    }
    for ($i = 1; $i <= 3; $i++) {
      $this->waitForElementPresent("xpath=//table[@class='selector']/tbody/tr[$i]/td[6][text()='Registered']");
      $this->assertTrue($this->isElementPresent("xpath=//table[@class='selector']/tbody/tr[$i]/td[6][text()='Registered']"));
    }

    //Type the billing email in autocomplete.
    $this->click('sort_name_navigation');
    $this->type('css=input#sort_name_navigation', "{$firstName}.{$lastName}@example.com");
    $this->typeKeys('css=input#sort_name_navigation', "{$firstName}.{$lastName}@example.com");

    // Wait for result list.
    $this->waitForElementPresent("css=div.ac_results-inner li");

    // Visit contact summary page.
    $this->click("css=div.ac_results-inner li");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //click on Contributions Tab
    $this->click("xpath=//li[@id='tab_contribute']/a");
    //check for the three contributions
    foreach ($events as $key => $value) {
      $this->waitForElementPresent("xpath=//table[@class='selector']/tbody/tr/td[3][contains(text(),'$value')]");
      $this->assertTrue($this->isElementPresent("xpath=//table[@class='selector']/tbody/tr/td[3][contains(text(),'$value')]"));
    }

    //Disable shopping cart style
    $this->openCiviPage("admin/setting/preferences/event", "reset=1");
    $this->click("enable_cart");
    $this->click("_qf_Event_next-top");
  }

}

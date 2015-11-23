<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Class WebTest_Event_AddEventTest
 */
class WebTest_Event_AddEventTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAddPaidEventNoTemplate() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $eventDescription = "Here is a description for this conference.";
    $this->_testAddEventInfo($eventTitle, $eventDescription);

    $streetAddress = "100 Main Street";
    $this->_testAddLocation($streetAddress);

    $this->_testAddReminder($eventTitle);

    $this->_testAddFees(FALSE, FALSE, $processorName);

    // intro text for registration page
    $registerIntro = "Fill in all the fields below and click Continue.";
    $multipleRegistrations = TRUE;
    $this->_testAddOnlineRegistration($registerIntro, $multipleRegistrations);

    $eventInfoStrings = array($eventTitle, $eventDescription, $streetAddress);
    $eventId = $this->_testVerifyEventInfo($eventTitle, $eventInfoStrings);

    $registerStrings = array("225.00", "Member", "300.00", "Non-member", $registerIntro);
    $registerUrl = $this->_testVerifyRegisterPage($registerStrings);

    $numberRegistrations = 3;
    $anonymous = TRUE;
    $this->_testOnlineRegistration($registerUrl, $numberRegistrations, $anonymous);

    // Now test making a copy of the event
    $this->webtestLogin();
    $this->openCiviPage("event/manage", "reset=1&action=copy&id=$eventId");
    $this->_testVerifyEventInfo('Copy of ' . $eventTitle, $eventInfoStrings);
    $this->_testVerifyRegisterPage($registerStrings);
  }

  public function testAddPaidEventDiscount() {

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $eventDescription = "Here is a description for this conference.";
    $this->_testAddEventInfo($eventTitle, $eventDescription);

    $streetAddress = "100 Main Street";
    $this->_testAddLocation($streetAddress);

    $this->_testAddReminder($eventTitle);

    $this->_testAddFees(TRUE, FALSE, $processorName);

    // intro text for registration page
    $registerIntro = "Fill in all the fields below and click Continue.";
    $multipleRegistrations = TRUE;
    $this->_testAddOnlineRegistration($registerIntro, $multipleRegistrations);

    $discountFees = array("225.00", "300.00");

    $eventInfoStrings = array($eventTitle, $eventDescription, $streetAddress);
    $this->_testVerifyEventInfo($eventTitle, $eventInfoStrings, $discountFees);

    $registerStrings = array_push($discountFees, "Member", "Non-member", $registerIntro);
    $registerUrl = $this->_testVerifyRegisterPage($registerStrings);

    $numberRegistrations = 3;
    $anonymous = TRUE;
    $this->_testOnlineRegistration($registerUrl, $numberRegistrations, $anonymous);
  }

  public function testDeletePriceSetDiscount() {

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $eventDescription = "Here is a description for this conference.";
    $this->_testAddEventInfo($eventTitle, $eventDescription);

    $streetAddress = "100 Main Street";
    $this->_testAddLocation($streetAddress);

    //Add two discounts
    $discount = $this->_testAddFees(TRUE, FALSE, $processorName, TRUE);

    // intro text for registration page
    $registerIntro = "Fill in all the fields below and click Continue.";
    $multipleRegistrations = TRUE;
    $this->_testAddOnlineRegistration($registerIntro, $multipleRegistrations);

    $discountFees = array("225.00", "300.00");

    $eventInfoStrings = array($eventTitle, $eventDescription, $streetAddress);
    $id = $this->_testVerifyEventInfo($eventTitle, $eventInfoStrings, $discountFees);

    $registerStrings = array_push($discountFees, "Member", "Non-member", $registerIntro);
    $registerUrl = $this->_testVerifyRegisterPage($registerStrings);

    //Add Price Set now
    $this->openCiviPage("event/manage/fee", "reset=1&action=update&id=$id", "_qf_Fee_upload-bottom");
    $this->click("xpath=//a[@id='quickconfig']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']");
    $this->click("xpath=//div[@class='ui-dialog-buttonset']//button/span[text()='Continue']");

    //Assert quick config change and discount deletion
    $this->openCiviPage("admin/price", "reset=1");
    foreach ($discount as $key => $val) {
      $this->waitForTextPresent($val);
    }
  }

  public function testAddDeleteEventDiscount() {

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $eventDescription = "Here is a description for this conference.";
    $this->_testAddEventInfo($eventTitle, $eventDescription);

    $streetAddress = "100 Main Street";
    $this->_testAddLocation($streetAddress);

    //Add two discounts
    $discount = $this->_testAddFees(TRUE, FALSE, $processorName, TRUE);

    // intro text for registration page
    $registerIntro = "Fill in all the fields below and click Continue.";
    $multipleRegistrations = TRUE;
    $this->_testAddOnlineRegistration($registerIntro, $multipleRegistrations);

    $discountFees = array("225.00", "300.00");

    $eventInfoStrings = array($eventTitle, $eventDescription, $streetAddress);
    $id = $this->_testVerifyEventInfo($eventTitle, $eventInfoStrings, $discountFees);

    $registerStrings = array_push($discountFees, "Member", "Non-member", $registerIntro);
    $registerUrl = $this->_testVerifyRegisterPage($registerStrings);
    //Delete the discount
    $this->_deleteDiscount($id, $eventTitle, $discount);
  }

  /**
   * @param int $id
   * @param $eventTitle
   * @param $discount
   */
  public function _deleteDiscount($id, $eventTitle, $discount) {
    $this->openCiviPage("event/manage/fee", "reset=1&action=update&id=$id", "_qf_Fee_upload-bottom");
    $this->type("discount_name_2", "");
    $this->click("xpath=//tr[@id='discount_2']/td[3]/a");
    $this->click("xpath=//tr[@id='discount_2']/td[4]/a");
    $this->type("discounted_value_1_2", "");
    $this->type("discounted_value_2_2", "");
    $this->click("_qf_Fee_upload-bottom");
    $this->waitForText('crm-notification-container', "'Fees' information has been saved.");
    //Assertions
    $this->openCiviPage("admin/price", "reset=1");
    $this->assertStringsPresent($discount[1]);
  }

  public function testAddPaidEventWithTemplate() {

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $eventDescription = "Here is a description for this conference.";
    // Select paid online registration template.
    $templateID = 6;
    $eventTypeID = 1;
    $this->_testAddEventInfoFromTemplate($eventTitle, $eventDescription, $templateID, $eventTypeID);

    $streetAddress = "100 Main Street";
    $this->_testAddLocation($streetAddress);

    $this->_testAddFees(FALSE, FALSE, $processorName);

    // intro text for registration page
    $registerIntro = "Fill in all the fields below and click Continue.";
    $this->_testAddOnlineRegistration($registerIntro);

    // $eventInfoStrings = array( $eventTitle, $eventDescription, $streetAddress );
    $eventInfoStrings = array($eventTitle, $streetAddress);
    $this->_testVerifyEventInfo($eventTitle, $eventInfoStrings);

    $registerStrings = array("225.00", "Member", "300.00", "Non-member", $registerIntro);
    $this->_testVerifyRegisterPage($registerStrings);
  }

  public function testAddFreeEventWithTemplate() {

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle = 'My Free Meeting - ' . substr(sha1(rand()), 0, 7);
    $eventDescription = "Here is a description for this free meeting.";
    // Select "Free Meeting with Online Registration" template (id = 5).
    $templateID = 5;
    $eventTypeID = 4;

    $this->_testAddEventInfoFromTemplate($eventTitle, $eventDescription, $templateID, $eventTypeID);

    $streetAddress = "100 Main Street";

    $this->_testAddLocation($streetAddress);

    // Go to Fees tab and check that Paid Event is false (No)
    $this->click("link=Fees");
    $this->waitForElementPresent("_qf_Fee_upload-bottom");
    $this->verifyChecked("CIVICRM_QFID_0_is_monetary");

    // intro text for registration page
    $registerIntro = "Fill in all the fields below and click Continue.";
    $this->_testAddOnlineRegistration($registerIntro);

    // $eventInfoStrings = array( $eventTitle, $eventDescription, $streetAddress );
    $eventInfoStrings = array($eventTitle, $streetAddress);
    $this->_testVerifyEventInfo($eventTitle, $eventInfoStrings);

    $registerStrings = array($registerIntro);
    $this->_testVerifyRegisterPage($registerStrings);
    // make sure paid_event div is NOT present since this is a free event
    $this->verifyElementNotPresent("css=div.paid_event-section");
  }

  public function testUnpaidPaid() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    // Log in using webtestLogin() method
    $this->webtestLogin();

    $this->openCiviPage("event/add", "reset=1&action=add");
    $eventTitle = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $eventDescription = "Here is a description for this conference.";
    $this->_testAddEventInfo($eventTitle, $eventDescription);

    //add fee section with pay later checked
    $this->_testAddFees(FALSE, FALSE, NULL, FALSE, TRUE);

    //make the event unpaid
    $this->waitForElementPresent("_qf_Fee_upload-bottom");
    $this->assertChecked('is_pay_later');
    $this->click("CIVICRM_QFID_0_is_monetary");

    $this->click("_qf_Fee_upload-bottom");
    $this->waitForText('crm-notification-container', "'Fees' information has been saved.");
    $this->waitForAjaxContent();

    //check if pay later option is disabled
    $this->click('CIVICRM_QFID_1_is_monetary');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('is_pay_later');
    $this->assertNotChecked('is_pay_later');
  }

  public function testAjaxCustomGroupLoad() {
    $this->webtestLogin();

    $triggerElement = array('name' => 'event_type_id', 'type' => 'select');
    $customSets = array(
      array('entity' => 'Event', 'subEntity' => 'Conference', 'triggerElement' => $triggerElement),
    );

    $pageUrl = array('url' => 'event/add', 'args' => "reset=1&action=add");
    $this->customFieldSetLoadOnTheFlyCheck($customSets, $pageUrl);
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
    $this->click("is_public");
    $this->clickLink("_qf_EventInfo_upload-bottom");
  }

  /**
   * @param $eventTitle
   * @param $eventDescription
   * @param int $templateID
   * @param int $eventTypeID
   */
  public function _testAddEventInfoFromTemplate($eventTitle, $eventDescription, $templateID, $eventTypeID) {
    $this->waitForElementPresent("_qf_EventInfo_upload-bottom");

    // Select event template. Use option value, not label - since labels can be translated and test would fail
    $this->select("template_id", "value={$templateID}");

    // Wait for event type to be filled in (since page refreshes)
    $this->waitForAjaxContent();
    $this->verifySelectedValue("event_type_id", $eventTypeID);

    // Attendee role s/b selected now.
    $this->verifySelectedValue("default_role_id", "1");

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
    $this->clickLink("_qf_EventInfo_upload-bottom");
  }

  /**
   * @param $streetAddress
   */
  public function _testAddLocation($streetAddress) {
    // Wait for Location tab form to load
    $this->waitForAjaxContent();
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
    $this->waitForText('crm-notification-container', "'Event Location' information has been saved.");
  }

  /**
   * @param bool $discount
   * @param bool $priceSet
   * @param string $processorName
   * @param bool $double
   * @param bool $payLater
   *
   * @return array
   */
  public function _testAddFees($discount = FALSE, $priceSet = FALSE, $processorName = "PP Pro", $double = FALSE, $payLater = FALSE) {
    $discount1 = "Early-bird" . substr(sha1(rand()), 0, 7);
    $discount2 = "";
    // Go to Fees tab
    $this->click("link=Fees");
    $this->waitForElementPresent("_qf_Fee_upload-bottom");
    $this->click("CIVICRM_QFID_1_is_monetary");

    if ($payLater) {
      $this->click('is_pay_later');
      $this->fillRichTextField('pay_later_receipt', 'testing later instructions');
    }
    else {
      $this->uncheck('is_pay_later');
    }

    if ($processorName) {
      $this->select2('payment_processor', $processorName, TRUE);
    }
    $this->select("financial_type_id", "value=4");
    if ($priceSet) {
      // get one - TBD
    }
    else {
      $this->type("label_1", "Member");
      $this->type("value_1", "225.00");
      $this->type("label_2", "Non-member");
      $this->type("value_2", "300.00");
      $this->click("CIVICRM_QFID_1_6");
    }

    if ($discount) {
      // enter early bird discount fees
      $this->click("is_discount");
      $this->waitForElementPresent("discount_name_1");
      $this->type("discount_name_1", $discount1);
      $this->webtestFillDate("discount_start_date_1", "-3 week");
      $this->webtestFillDate("discount_end_date_1", "-2 week");
      $this->clickLink("_qf_Fee_submit", "discounted_value_1_1");

      $this->type("discounted_value_1_1", "225.00");
      $this->type("discounted_value_2_1", "300.00");

      if ($double) {
        $discount2 = "Early-bird" . substr(sha1(rand()), 0, 7);
        // enter early bird discount fees
        $this->click("link=another discount set");
        $this->waitForElementPresent("discount_name_2");
        $this->type("discount_name_2", $discount2);
        $this->webtestFillDate("discount_start_date_2", "-1 week");
        $this->webtestFillDate("discount_end_date_2", "+1 week");
        $this->clickLink("_qf_Fee_submit", "discounted_value_2_1");
        $this->type("discounted_value_1_2", "225.00");
        $this->type("discounted_value_2_2", "300.00");
      }
      $this->click("xpath=//fieldset[@id='discount']/fieldset/table/tbody/tr[2]/td[3]/input");
    }
    $this->click("_qf_Fee_upload-bottom");

    // Wait for "saved" status msg
    $this->waitForText('crm-notification-container', "'Fees' information has been saved");
    return array($discount1, $discount2);
  }

  /**
   * @param $registerIntro
   * @param bool $multipleRegistrations
   */
  public function _testAddOnlineRegistration($registerIntro, $multipleRegistrations = FALSE) {
    // Go to Online Registration tab
    $this->click("link=Online Registration");
    $this->waitForElementPresent("_qf_Registration_upload-bottom");

    $isChecked = $this->isChecked('is_online_registration');
    if (!$isChecked) {
      $this->click("is_online_registration");
    }
    $this->assertChecked("is_online_registration");
    if ($multipleRegistrations) {
      $isChecked = $this->isChecked('is_multiple_registrations');
      if (!$isChecked) {
        $this->click("is_multiple_registrations");
      }
      $this->assertChecked("is_multiple_registrations");
    }

    $this->fillRichTextField("intro_text", $registerIntro, 'CKEditor', TRUE);

    // enable confirmation email
    $this->click("CIVICRM_QFID_1_is_email_confirm");
    $this->type("confirm_from_name", "Jane Doe");

    $this->type("confirm_from_email", "jane.doe@example.org");

    $this->click("_qf_Registration_upload-bottom");
    $this->waitForText('crm-notification-container', "'Online Registration' information has been saved.");
  }

  /**
   * @param $eventTitle
   * @param $eventInfoStrings
   * @param null $eventFees
   *
   * @return null
   */
  public function _testVerifyEventInfo($eventTitle, $eventInfoStrings, $eventFees = NULL) {
    // verify event input on info page
    // start at Manage Events listing
    $this->openCiviPage("event/manage", "reset=1");
    $this->click("link=$eventTitle");

    // Look for Register button
    $this->waitForElementPresent("link=Register Now");

    // Check for correct event info strings
    $this->assertStringsPresent($eventInfoStrings);

    // Optionally verify event fees (especially for discounts)
    if ($eventFees) {
      $this->assertStringsPresent($eventFees);

    }
    return $this->urlArg('id');
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
   * @param bool $isPayLater
   * @param array $participantEmailInfo
   * @param null $paymentProcessor
   *
   * @return array
   */
  public function _testOnlineRegistration($registerUrl, $numberRegistrations = 1, $anonymous = TRUE, $isPayLater = FALSE, $participantEmailInfo = array(), $paymentProcessor = NULL) {
    $infoPassed = FALSE;
    if (!empty($participantEmailInfo)) {
      $infoPassed = TRUE;
    }
    if ($anonymous) {
      $this->webtestLogout();
    }
    $primaryParticipantInfo = array();
    $this->open($registerUrl);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent('additional_participants');

    $this->select("additional_participants", "value=" . $numberRegistrations);

    if ($infoPassed) {
      $primaryParticipantInfo['first_name'] = $participantEmailInfo[0]['first_name'];
      $primaryParticipantInfo['last_name'] = $participantEmailInfo[0]['last_name'];
      $primaryParticipantInfo['email'] = $participantEmailInfo[0]['email'];
    }
    else {
      $primaryParticipantInfo['first_name'] = "Jane";
      $primaryParticipantInfo['last_name'] = "Smith" . substr(sha1(rand()), 0, 7);
      $primaryParticipantInfo['email'] = "smith" . substr(sha1(rand()), 0, 7) . "@example.org";
    }

    $this->type("first_name", $primaryParticipantInfo['first_name']);
    $this->type("last_name", $primaryParticipantInfo['last_name']);
    $this->type("email-Primary", $primaryParticipantInfo['email']);

    if (!$isPayLater) {
      if ($paymentProcessor) {
        $paymentProcessorEle = $this->getAttribute("xpath=//form[@id='Register']//label[contains(text(), '{$paymentProcessor}')]/@for");
        $this->click($paymentProcessorEle);
      }
      $this->select("credit_card_type", "value=Visa");
      $this->type("credit_card_number", "4111111111111111");
      $this->type("cvv2", "000");
      $this->select("credit_card_exp_date[M]", "value=1");
      $this->select("credit_card_exp_date[Y]", "value=2020");
      $this->type("billing_first_name", $primaryParticipantInfo['first_name']);
      $this->type("billing_last_name", $primaryParticipantInfo['last_name']);
      $this->type("billing_street_address-5", "15 Main St.");
      $this->type(" billing_city-5", "San Jose");
      $this->select("billing_country_id-5", "value=1228");
      $this->select("billing_state_province_id-5", "value=1004");
      $this->type("billing_postal_code-5", "94129");
    }

    $this->click("_qf_Register_upload-bottom");

    if ($numberRegistrations > 1) {
      for ($i = 1; $i <= $numberRegistrations; $i++) {
        $this->waitForPageToLoad($this->getTimeoutMsec());
        // Look for Skip button
        $this->waitForElementPresent("_qf_Participant_{$i}_next_skip-Array");

        if ($infoPassed) {
          $this->type("first_name", $participantEmailInfo[$i]['first_name']);
          $this->type("last_name", $participantEmailInfo[$i]['last_name']);
          $this->type("email-Primary", $participantEmailInfo[$i]['email']);
        }
        else {
          $this->type("first_name", "Jane Add $i");
          $this->type("last_name", "Smith" . substr(sha1(rand()), 0, 7));
          $this->type("email-Primary", "smith" . substr(sha1(rand()), 0, 7) . "@example.org");
        }

        $this->click("_qf_Participant_{$i}_next");
      }
    }

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Confirm_next-bottom");
    $confirmStrings = array("Event Fee(s)");
    if (!$isPayLater) {
      $confirmStrings += array("Billing Name and Address", "Credit Card Information");
    }
    $this->assertStringsPresent($confirmStrings);
    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $thankStrings = array("Thank You for Registering", "Event Total");
    if (!$isPayLater) {
      $thankStrings = array("Transaction Date");
    }
    else {
      $thankStrings += array("testing later instructions");
    }
    $this->assertStringsPresent($thankStrings);
    return $primaryParticipantInfo;
  }

  /**
   * @param $eventTitle
   */
  public function _testAddReminder($eventTitle) {
    // Go to Schedule Reminders tab
    $this->click("link=Schedule Reminders");
    $this->waitForElementPresent("newScheduleReminder");
    $this->click("newScheduleReminder");
    $this->waitForElementPresent("_qf_ScheduleReminders_next-bottom");
    $this->type("title", "Event Reminder for " . $eventTitle);
    $this->select('entity', 'label=Registered');

    $this->select('start_action_offset', 'label=1');
    $this->select('start_action_condition', 'label=after');
    $this->click('is_repeat');
    $this->select('repetition_frequency_interval', 'label=2');
    $this->select('end_date', 'label=Event End Date');
    $this->click('recipient');
    $this->select('recipient', 'label=Participant Role');
    //  $this->select( 'recipient_listing', 'value=1' );

    // Fill Subject
    $subject = 'subject' . substr(sha1(rand()), 0, 4);
    $this->type('subject', $subject);
    $this->fillRichTextField("html_message", "This is the test HTML version here!!!", 'CKEditor');

    $this->type("text_message", "This is the test text version here!!!");
    //click on save
    $this->click('_qf_ScheduleReminders_next-bottom');
    $this->waitForElementPresent("link=Add Reminder");

    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[7]/span/a[1]");

    $verifyText = array(
      1 => 'Event Reminder for ' . $eventTitle,
      3 => '1 hour after Event Start Date',
      4 => 'Registered',
      5 => 'Yes',
      6 => 'Yes',
    );

    $this->waitForElementPresent("xpath=//form[@id='ScheduleReminders']//div[@id='option11_wrapper']");
    //verify the fields for Event Reminder selector
    foreach ($verifyText as $key => $value) {
      $this->verifyText("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody//tr/td[$key]", $value);
    }
  }

  public function testEventAddMultipleParticipant() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    // Log in using webtestLogin() method
    $this->webtestLogin();
    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $eventDescription = "Here is a description for this conference.";
    $this->_testAddEventInfo($eventTitle, $eventDescription);
    $streetAddress = "100 Main Street";
    $this->_testAddLocation($streetAddress);

    $this->_testAddFees(FALSE, FALSE, "Test Processor", FALSE, TRUE);
    $registerIntro = "Fill in all the fields below and click Continue.";
    $multipleRegistrations = TRUE;
    $this->_testAddOnlineRegistration($registerIntro, $multipleRegistrations);
    $eventInfoStrings = array($eventTitle, $eventDescription, $streetAddress);
    $eventId = $this->_testVerifyEventInfo($eventTitle, $eventInfoStrings);

    $registerStrings = array("225.00", "Member", "300.00", "Non-member", $registerIntro);
    $registerUrl = $this->_testVerifyRegisterPage($registerStrings);
    $numberRegistrations = 3;
    $anonymous = TRUE;

    // CRM-12615 add additional participants and check email, amount
    $primaryParticipant = array(
      'email' => "smith" . substr(sha1(rand()), 0, 7) . "@example.org",
      'first_name' => "Kate",
      'last_name' => "Simth" . substr(sha1(rand()), 0, 7),
    );
    $secParticipant = array(
      'email' => "smith" . substr(sha1(rand()), 0, 7) . "@example.org",
      'first_name' => "Kate Add 1",
      'last_name' => "Simth" . substr(sha1(rand()), 0, 7),
    );
    $thirdParticipant = array(
      'email' => "smith" . substr(sha1(rand()), 0, 7) . "@example.org",
      'first_name' => "Kate Add 2",
      'last_name' => "Simth" . substr(sha1(rand()), 0, 7),
    );

    $participantEmails = array($primaryParticipant, $secParticipant, $thirdParticipant);
    $addtlPart = array($secParticipant, $thirdParticipant);
    $primaryParticipantInfo = $this->_testOnlineRegistration($registerUrl, 2, $anonymous, FALSE, $participantEmails, "Test Processor");
    $primaryDisplayName = "{$primaryParticipantInfo['first_name']} {$primaryParticipantInfo['last_name']}";
    $this->webtestLogin();
    $this->openCiviPage("event/search", "reset=1");
    $this->select2("event_id", $eventTitle, FALSE);
    $this->clickLink('_qf_Search_refresh');
    $this->waitForElementPresent("xpath=//div[@id='participantSearch']/table/tbody//tr/td[3]/a");
    $this->verifyText("xpath=//div[@id='participantSearch']/table/tbody//tr/td[@class='crm-participant-sort_name']/a[contains(text(),
     '{$secParticipant['last_name']}, {$secParticipant['first_name']}')]/../../td[6]", preg_quote('225.00'));
    $this->verifyText("xpath=//div[@id='participantSearch']/table/tbody//tr/td[@class='crm-participant-sort_name']/a[contains(text(),
    '{$thirdParticipant['last_name']}, {$thirdParticipant['first_name']}')]/../../td[6]", preg_quote('225.00'));

    //CRM-12618 check edit screen of additional participant and ensuring record_contribution not present
    foreach ($addtlPart as $value) {
      $this->clickAjaxLink("xpath=//div[@id='participantSearch']/table/tbody//tr/td[3]/a[contains(text(),
       '{$value['last_name']}, {$value['first_name']}')]/../../td[11]/span/a[2][contains(text(), 'Edit')]",
        '_qf_Participant_upload-bottom');
      $this->assertTrue(
        $this->isElementPresent("xpath=//tr[@class='crm-participant-form-block-registered-by']/td[2]/a[contains(text(),
         '$primaryDisplayName')]"), 'Registered By info is wrong on additional participant edit form');
      $this->assertTrue(
        $this->isElementPresent(
          "xpath=//table/tbody/tr[@class='crm-participant-form-block-displayName']/td[2][contains(text(),
           '{$value['first_name']} {$value['last_name']}')]"),
        'Wrong Participant edit form'
      );
      $this->assertFalse($this->isElementPresent('record_contribution'),
        'Record Payment checkbox showed up wrongly for additional participant edit screen');
      $this->click("_qf_Participant_cancel-top");
    }

    //unselect the payment processor configured
    $this->openCiviPage("event/manage/fee", "reset=1&action=update&id={$eventId}", '_qf_Fee_upload-bottom');
    $this->click("_qf_Fee_upload-bottom");
    $this->waitForText('crm-notification-container', "'Fees' information has been saved.");

    // add participant and 3 additional participant and change status of participant from edit participant
    $this->_testOnlineRegistration($registerUrl, $numberRegistrations, $anonymous, TRUE);
    $this->webtestLogin();

    $this->openCiviPage("event/search?reset=1", "reset=1");
    $this->select2("event_id", $eventTitle, FALSE);
    $this->multiselect2('participant_status_id', array('Pending (pay later)'));
    $this->clickLink('_qf_Search_refresh');
    $this->waitForElementPresent("xpath=//div[@id='participantSearch']/table/tbody//tr/td[11]/span/a[2][text()='Edit']");

    $uRL = $this->getAttribute("xpath=//div[@id='participantSearch']/table/tbody//tr/td[11]/span/a[2][text()='Edit']@href");
    $this->click("xpath=//div[@id='participantSearch']/table/tbody//tr/td[11]/span/a[2][text()='Edit']");
    $this->waitForElementPresent("status_id");
    $this->select('status_id', 'label=Registered');
    $this->waitForElementPresent("record_contribution");
    $this->click('record_contribution');
    $this->waitForElementPresent("contribution_status_id");
    $this->select('contribution_status_id', 'label=Completed');
    $pID = $this->urlArg('id', $uRL);
    $contributionID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $pID, 'contribution_id', 'participant_id');
    $this->click('_qf_Participant_upload-top');
    $this->waitForElementPresent("xpath=//div[@id='participantSearch']/table/tbody//tr/td[11]/span/a[text()='Edit']");
    $this->waitForElementPresent("xpath=//div[@id='participantSearch']/table/tbody//tr/td[11]/span/a[text()='View']");
    $this->click("xpath=//div[@id='participantSearch']/table/tbody//tr/td[11]/span/a[text()='View']");
    $this->waitForElementPresent("css=.ui-dialog");
    $this->waitForAjaxContent();
    $this->verifyFinancialRecords($contributionID);

    // add participant and 3 additional participant and change status of participant from edit contribution
    $this->_testOnlineRegistration($registerUrl, $numberRegistrations, $anonymous, TRUE);
    $this->webtestLogin();

    $this->openCiviPage("event/search?reset=1", "reset=1");
    $this->select2("event_id", $eventTitle, FALSE);
    $this->multiselect2('participant_status_id', array('Pending (pay later)'));
    $this->clickLink('_qf_Search_refresh');
    $this->waitForElementPresent("xpath=//div[@id='participantSearch']/table/tbody//tr/td[11]/span/a[text()='View']");
    $uRL = $this->getAttribute("xpath=//div[@id='participantSearch']/table/tbody//tr/td[11]/span/a[text()='View']@href");
    $this->click("xpath=//div[@id='participantSearch']/table/tbody//tr/td[11]/span/a[text()='View']");
    $pID = $this->urlArg('id', $uRL);
    $contributionID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $pID, 'contribution_id', 'participant_id');
    $this->waitForElementPresent("xpath=//tr[@id='rowid$contributionID']/td[8]/span//a[text()='Edit']");
    $this->click("xpath=//tr[@id='rowid$contributionID']/td[8]/span//a[text()='Edit']");
    $this->waitForElementPresent("_qf_Contribution_upload-bottom");
    $this->select('contribution_status_id', 'label=Completed');
    $this->clickLink('_qf_Contribution_upload-bottom', '_qf_ParticipantView_cancel-bottom', FALSE);
    $this->waitForAjaxContent();
    $this->verifyFinancialRecords($contributionID);
  }

  /**
   * @param int $contributionID
   */
  public function verifyFinancialRecords($contributionID) {
    // check count for civicrm_contribution and civicrm_financial_item in civicrm_entity_financial_trxn
    $query = "SELECT COUNT(DISTINCT(c1.id)) civicrm_contribution, COUNT(c2.id) civicrm_financial_item  FROM civicrm_entity_financial_trxn c1
LEFT JOIN civicrm_entity_financial_trxn c2 ON c1.financial_trxn_id = c2.financial_trxn_id AND c2.entity_table ='civicrm_financial_item'
LEFT JOIN civicrm_financial_item cfi ON cfi.id = c2.entity_id
WHERE c1.entity_table  = 'civicrm_contribution' AND c1.entity_id = %1 AND cfi.status_id = 1";
    $params = array(1 => array($contributionID, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $dao->fetch();
    $this->assertEquals('2', $dao->civicrm_contribution, 'civicrm_financial_trxn count does not match');
    $this->assertEquals('8', $dao->civicrm_financial_item, 'civicrm_financial_item count does not match');
    $query = "SELECT COUNT(cft.id) civicrm_financial_trxn FROM civicrm_entity_financial_trxn ceft
INNER JOIN civicrm_financial_trxn cft ON ceft.financial_trxn_id = cft.id
WHERE ceft.entity_id = %1 AND ceft.entity_table = 'civicrm_contribution'";
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $dao->fetch();
    $this->assertEquals('2', $dao->civicrm_financial_trxn, 'civicrm_financial_trxn count does not match');
  }

  public function testEventApprovalRegistration() {
    $this->webtestLogin();

    //Participant Status
    $this->openCiviPage("admin/participant_status", "reset=1&action=browse");
    foreach (array('Awaiting approval', 'Pending from approval', 'Rejected') as $label) {
      $status = $this->webtest_civicrm_api("ParticipantStatusType", "getsingle", array('label' => $label));
      $this->_testEnableParticipantStatuses($status['id']);
      $this->isElementPresent("xpath=//tr[@id='participant_status_type-{$status['id']}']/td[9]/span/a[2][text()='Disable']");
    }

    //Create New Event

    $this->openCiviPage('event/add', 'reset=1&action=add', '_qf_EventInfo_upload-bottom');
    $eventTitle = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $email = 'Smith' . substr(sha1(rand()), 0, 7) . '@example.com';
    $eventDescription = 'Here is a description for this conference.';
    $this->select('event_type_id', 'value=1');

    // Attendee role s/b selected now.
    $this->select('default_role_id', 'value=1');
    // Enter Event Title, Summary and Description
    $this->type('title', $eventTitle);
    $this->type('summary', 'This is a great conference. Sign up now!');

    // Type description in ckEditor (fieldname, text to type, editor)
    $this->fillRichTextField('description', $eventDescription);
    $this->type('max_participants', '50');
    $this->click('is_map');
    $this->click('_qf_EventInfo_upload-bottom');

    // Wait for Location tab form to load
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Go to Fees tab
    $this->click('link=Fees');
    $id = $this->urlArg('id');
    $this->waitForElementPresent('_qf_Fee_upload-bottom');
    $this->click('CIVICRM_QFID_1_is_monetary');
    $processorName = 'Test Processor';
    $this->select2('payment_processor', $processorName, TRUE);

    $this->select('financial_type_id', 'label=Event Fee');
    $this->type("label[1]", 'Junior Stars');
    $this->type("value[1]", '500.00');
    $this->type("label[2]", 'Super Stars');
    $this->type("value[2]", '1000.00');
    $this->check('default');
    $this->click('_qf_Fee_upload-bottom');
    $this->waitForText('crm-notification-container', "'Fees' information has been saved.");

    // intro text for registration page
    $registerIntro = 'Fill in all the fields below and click Continue.';

    // Go to Online Registration tab
    $this->click('link=Online Registration');
    $this->waitForElementPresent('_qf_Registration_upload-bottom');
    $this->click('is_online_registration');
    $this->assertChecked('is_online_registration');

    //Requires Approvel
    $this->click('requires_approval');
    $this->assertChecked('requires_approval');
    $this->click('_qf_Registration_upload-bottom');
    $this->waitForText('crm-notification-container', "'Online Registration' information has been saved.");

    // verify event input on info page
    // start at Manage Events listing
    $this->openCiviPage('event/manage', 'reset=1');
    $this->click("link=$eventTitle");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, 'Anderson', TRUE);
    $contactName = "Anderson, $firstName";
    $displayName = "$firstName Anderson";
    $this->openCiviPage("event/register", "reset=1&id=$id&action=preview", '_qf_Register_upload-bottom');
    $this->type('first_name', $firstName);

    //fill in last name
    $lastName = 'Recuron' . substr(sha1(rand()), 0, 7);
    $this->type('last_name', $contactName);
    $email = $firstName . '@example.com';
    $this->type('email-Primary', $email);
    $this->click('_qf_Register_upload');
    $this->waitForElementPresent("_qf_Confirm_next");
    $this->click('_qf_Confirm_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("xpath=//div[@class='crm-group participant_info-group']");
    $this->assertTextPresent("Thank You for Registering");

  }

  /**
   * Test enabling participant statuses.
   *
   * @param int $statusId
   */
  public function _testEnableParticipantStatuses($statusId) {
    // enable participant status
    if ($this->isElementPresent("xpath=//tr[@id='participant_status_type-{$statusId}']/td[9]/span/a[2][text()='Enable']")) {
      $this->click("xpath=//tr[@id='participant_status_type-{$statusId}']/td[9]/span/a[2][text()='Enable']");
      $this->waitForElementPresent("xpath=//tr[@id='participant_status_type-{$statusId}']/td[9]/span/a[2][text()='Disable']");
    }
  }

  /**
   * CRM-16777: Allow to add schedule reminder for event with 'edit all event' permission
   */
  public function testConfigureScheduleReminder() {
    // Log in using webtestLogin() method
    $this->webtestLogin('admin');

    //Details for TestUser1
    $role1 = 'role1' . substr(sha1(rand()), 0, 7);
    $TestUser1 = "TestUser1" . substr(sha1(rand()), 0, 4);
    $emailId1 = substr(sha1(rand()), 0, 7) . '@web.com';

    //create Role1 with permission 'Access CiviCRM', 'edit all events' and 'Access CiviEvent' permissions.
    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->type("edit-name", $role1);
    $this->waitForElementPresent("edit-add");
    $this->click("edit-add");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->waitForElementPresent("xpath=//table[@id='user-roles']/tbody//tr/td[1][text()='{$role1}']");
    $roleId = explode('/', $this->getAttribute("xpath=//table[@id='user-roles']/tbody//tr/td[1][text()='{$role1}']/../td[4]/a[text()='edit permissions']/@href"));
    $permissions = array(
      "edit-{$roleId[5]}-access-civicrm",
      "edit-{$roleId[5]}-edit-all-events",
      "edit-{$roleId[5]}-access-civievent",
    );
    $this->changePermissions($permissions);

    //Create TestUser1
    $this->open($this->sboxPath . "admin/people/create");
    $this->waitForElementPresent("edit-submit");
    $this->type("edit-name", $TestUser1);
    $this->type("edit-mail", $emailId1);
    $this->type("edit-pass-pass1", "Test12345");
    $this->type("edit-pass-pass2", "Test12345");
    $this->click("xpath=//div[@class='form-item form-type-checkboxes form-item-roles']/div//div/label[contains(text(), '{$role1}')]");
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->type("street_address-1", "902C El Camino Way SW");
    $this->type("city-1", "Dumfries");
    $this->type("postal_code-1", "1234");
    $this->select("state_province-1", "value=1019");
    $this->click("edit-submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Add event
    $this->openCiviPage("event/add", "reset=1&action=add");
    $eventName = 'My Event - ' . substr(sha1(rand()), 0, 7);
    $eventDescription = "Here is a description for this conference.";
    $this->_testAddEventInfo($eventName, $eventDescription);

    //Logging out
    $this->webtestLogout();

    //Login with TestUser1
    $this->webtestLogin($TestUser1, 'Test12345');
    $this->openCiviPage("event/manage", "reset=1");
    $this->type("title", $eventName);
    $this->click("_qf_SearchEvent_refresh");
    $this->waitForAjaxContent();
    $this->_testAddReminder($eventName);
    $this->webtestLogout();

    //Details for TestUser2
    $role2 = 'role2' . substr(sha1(rand()), 0, 5);
    $TestUser2 = "TestUser2" . substr(sha1(rand()), 0, 5);
    $emailId2 = substr(sha1(rand()), 0, 7) . '@web.com';

    //create Role2 with only 'Access CiviCRM' and 'Access CiviEvent' permissions
    $this->webtestLogin('admin');
    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->type("edit-name", $role2);
    $this->waitForElementPresent("edit-add");
    $this->click("edit-add");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->waitForElementPresent("xpath=//table[@id='user-roles']/tbody//tr/td[1][text()='{$role2}']");
    $roleId = explode('/', $this->getAttribute("xpath=//table[@id='user-roles']/tbody//tr/td[1][text()='{$role2}']/../td[4]/a[text()='edit permissions']/@href"));
    $permissions = array(
      "edit-{$roleId[5]}-access-civicrm",
      "edit-{$roleId[5]}-access-civievent",
    );
    $this->changePermissions($permissions);

    //Create TestUser2
    $this->open($this->sboxPath . "admin/people/create");
    $this->waitForElementPresent("edit-submit");
    $this->type("edit-name", $TestUser2);
    $this->type("edit-mail", $emailId2);
    $this->type("edit-pass-pass1", "Test123");
    $this->type("edit-pass-pass2", "Test123");
    $this->click("xpath=//div[@class='form-item form-type-checkboxes form-item-roles']/div//div/label[contains(text(), '{$role2}')]");
    $firstName = 'Smith' . substr(sha1(rand()), 0, 4);
    $lastName = 'John' . substr(sha1(rand()), 0, 5);
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->type("street_address-1", "902C El Camino Way SW");
    $this->type("city-1", "Dumfries");
    $this->type("postal_code-1", "1234");
    $this->select("state_province-1", "value=1019");
    $this->click("edit-submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Logout
    $this->webtestLogout();

    //Login with TestUser2
    $this->webtestLogin($TestUser2, 'Test123');
    $this->openCiviPage("event/manage", "reset=1");
    $this->waitForElementPresent("xpath=//div[@id='event_status_id']/div[@class='dataTables_wrapper no-footer']");
    $this->verifyText("xpath=//div[@id='event_status_id']/div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td", "None found.");
    $this->webtestLogout();
  }

}

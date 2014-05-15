<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
class WebTest_Event_AddEventTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAddPaidEventNoTemplate() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // We need a payment processor
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);
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

  function testAddPaidEventDiscount() {

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // We need a payment processor
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);
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

  function testDeletePriceSetDiscount() {

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // We need a payment processor
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);
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
    $this->waitForElementPresent('popupContainer');
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(3);
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button[1]");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(3);

    //Assert quick config change and discount deletion
    $this->openCiviPage("admin/price", "reset=1");
    $this->assertStringsPresent($discount);
  }

  function testAddDeleteEventDiscount() {

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // We need a payment processor
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);
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

  function _deleteDiscount($id, $eventTitle, $discount) {
    $this->openCiviPage("event/manage/fee", "reset=1&action=update&id=$id", "_qf_Fee_upload-bottom");
    $this->type("discount_name_2", "");
    $this->click("xpath=//tr[@id='discount_2']/td[3]/span/a");
    $this->click("xpath=//tr[@id='discount_2']/td[4]/span/a");
    $this->type("discounted_value_1_2", "");
    $this->type("discounted_value_2_2", "");
    $this->click("_qf_Fee_upload-bottom");
    $this->waitForPageToLoad();
    //Assertions
    $this->openCiviPage("admin/price", "reset=1");
    $this->assertStringsPresent($discount[1]);
  }

  function testAddPaidEventWithTemplate() {

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // We need a payment processor
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);
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

  function testAddFreeEventWithTemplate() {

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

  function testUnpaidPaid() {
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
    $this->clickLink("_qf_Fee_upload-bottom", "_qf_Fee_upload-bottom");

    //check if pay later option is disabled
    $this->click('CIVICRM_QFID_1_is_monetary');
    $this->waitForElementPresent('is_pay_later');
    $this->assertNotChecked('is_pay_later');
  }

  function testAjaxCustomGroupLoad() {
    $this->webtestLogin();

    $triggerElement = array('name' => 'event_type_id', 'type' => 'select');
    $customSets = array(
      array('entity' => 'Event', 'subEntity' => 'Conference', 'triggerElement' => $triggerElement),
    );

    $pageUrl = array('url' => 'event/add', 'args' => "reset=1&action=add");
    $this->customFieldSetLoadOnTheFlyCheck($customSets, $pageUrl);
  }

  function _testAddEventInfo($eventTitle, $eventDescription) {
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
    $this->click("_qf_EventInfo_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  function _testAddEventInfoFromTemplate($eventTitle, $eventDescription, $templateID, $eventTypeID) {
    $this->waitForElementPresent("_qf_EventInfo_upload-bottom");

    // Select event template. Use option value, not label - since labels can be translated and test would fail
    $this->select("template_id", "value={$templateID}");

    // Wait for event type to be filled in (since page reloads)
    $this->waitForPageToLoad($this->getTimeoutMsec());
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
    $this->click("_qf_EventInfo_upload-bottom");

  }

  function _testAddLocation($streetAddress) {
    // Wait for Location tab form to load
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Location_upload-bottom");

    // Fill in address fields
    $streetAddress = "100 Main Street";
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

  function _testAddFees($discount = FALSE, $priceSet = FALSE, $processorName = "PP Pro", $double = FALSE, $payLater = FALSE) {
    $discount1 = "Early-bird" . substr(sha1(rand()), 0, 7);
    $discount2 = "";
    // Go to Fees tab
    $this->click("link=Fees");
    $this->waitForElementPresent("_qf_Fee_upload-bottom");
    $this->click("CIVICRM_QFID_1_is_monetary");

    if ($payLater) {
      $this->check('is_pay_later');
      $this->fillRichTextField('pay_later_receipt', 'testing later instructions');
    }
    else {
      $this->uncheck('is_pay_later');
    }

    if ($processorName) {
      $this->click("xpath=//tr[@class='crm-event-manage-fee-form-block-payment_processor']/td[2]/label[text()='$processorName']");
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
      $this->click("CIVICRM_QFID_1_4");
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
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "'Fee' information has been saved");
    return array($discount1, $discount2);
  }

  function _testAddOnlineRegistration($registerIntro, $multipleRegistrations = FALSE) {
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

  function _testVerifyEventInfo($eventTitle, $eventInfoStrings, $eventFees = NULL) {
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

  function _testVerifyRegisterPage($registerStrings) {
    // Go to Register page and check for intro text and fee levels
    $this->click("link=Register Now");
    $this->waitForElementPresent("_qf_Register_upload-bottom");
    $this->assertStringsPresent($registerStrings);
    return $this->getLocation();
  }

  function _testOnlineRegistration($registerUrl, $numberRegistrations = 1, $anonymous = TRUE, $isPayLater = FALSE, $participantEmailInfo = array(), $paymentProcessor = NULL) {
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
        $this->check($paymentProcessorEle);
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

  function _testAddReminder($eventTitle) {
    // Go to Schedule Reminders tab
    $this->click('css=li#tab_reminder a');
    $this->waitForElementPresent("_qf_ScheduleReminders_upload-bottom");
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
    $this->click('_qf_ScheduleReminders_upload-bottom');
    $this->waitForElementPresent("link=Add Reminder");

    $this->waitForElementPresent("link=Edit");

    $verifyText = array(
      1 => 'Event Reminder for ' . $eventTitle,
      3 => '1 hour after Event Start Date',
      4 => 'Registered',
      5 => 'Yes',
      6 => 'Yes',
    );

    //verify the fields for Event Reminder selector
    foreach ($verifyText as $key => $value) {
      $this->verifyText("xpath=//table[@class='display']/tbody/tr/td[$key]", $value);
    }
  }

  function testEventAddMultipleParticipant() {
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
    $primaryParticipantInfo =
      $this->_testOnlineRegistration($registerUrl, 2, $anonymous, FALSE, $participantEmails, "Test Processor");
    $primaryDisplayName = "{$primaryParticipantInfo['first_name']} {$primaryParticipantInfo['last_name']}";
    $this->webtestLogin();
    $this->openCiviPage("event/search?reset=1", "reset=1");
    $this->type('event_name', $eventTitle);
    $this->click("event_name");
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");
    $this->clickLink('_qf_Search_refresh');
    $this->verifyText("xpath=//div[@id='participantSearch']/table/tbody//tr/td[3]/a[contains(text(),
     '{$secParticipant['last_name']}, {$secParticipant['first_name']}')]/../../td[6]", preg_quote('225.00'));
    $this->verifyText("xpath=//div[@id='participantSearch']/table/tbody//tr/td[3]/a[contains(text(),
    '{$thirdParticipant['last_name']}, {$thirdParticipant['first_name']}')]/../../td[6]", preg_quote('225.00'));

    //CRM-12618 check edit screen of additional participant and ensuring record_contribution not present
    foreach ($addtlPart as $value) {
      $this->clickLink("xpath=//div[@id='participantSearch']/table/tbody//tr/td[3]/a[contains(text(),
       '{$value['last_name']}, {$value['first_name']}')]/../../td[11]/span/a[2][contains(text(), 'Edit')]",
        '_qf_Participant_upload-bottom');
      $this->assertTrue(
        $this->isElementPresent("xpath=//tr[@class='crm-participant-form-block-registered-by']/td[2]/a[contains(text(),
         '$primaryDisplayName')]"), 'Registered By info is wrong on additional participant edit form');
      $this->assertElementContainsText("xpath=//form[@id='Participant']/h3", 'Edit Event Registration');
      $this->assertTrue(
        $this->isElementPresent(
          "xpath=//table/tbody/tr[@class='crm-participant-form-block-displayName']/td[2][contains(text(),
           '{$value['first_name']} {$value['last_name']}')]"),
        'Wrong Participant edit form'
      );
      $this->assertFalse($this->isElementPresent('record_contribution'),
        'Record Payment checkbox showed up wrongly for additional participant edit screen');
      $this->clickLink("_qf_Participant_cancel-top");
    }

    //unselect the payment processor configured
    $this->openCiviPage("event/manage/fee", "reset=1&action=update&id={$eventId}", '_qf_Fee_upload-bottom');
    $this->click("xpath=//tr[@class='crm-event-manage-fee-form-block-payment_processor']/td[2]/label[text()='Test Processor']");
    $this->clickLink("_qf_Fee_upload-bottom");

    // add participant and 3 additional participant and change status of participant from edit participant
    $this->_testOnlineRegistration($registerUrl, $numberRegistrations, $anonymous, TRUE);
    $this->webtestLogin();

    $this->openCiviPage("event/search?reset=1", "reset=1");
    $this->type('event_name', $eventTitle);
    $this->click("event_name");
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");
    $this->click("xpath=//td[@class='crm-event-form-block-participant_status']/div[@class='listing-box']//div/label[text()='Pending from pay later']");
    $this->click('_qf_Search_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("xpath=//div[@id='participantSearch']/table/tbody//tr/td[11]/span/a[text()='Edit']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->select('status_id', 'label=Registered');
    $this->click('record_contribution');
    $this->select('contribution_status_id', 'label=Completed');
    $pID = $this->urlArg('id');
    $contributionID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $pID, 'contribution_id', 'participant_id');
    $this->click('_qf_Participant_upload-top');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->verifyFinancialRecords($contributionID);

    // add participant and 3 additional participant and change status of participant from edit contribution
    $this->_testOnlineRegistration($registerUrl, $numberRegistrations, $anonymous, TRUE);
    $this->webtestLogin();

    $this->openCiviPage("event/search?reset=1", "reset=1");
    $this->type('event_name', $eventTitle);
    $this->click("event_name");
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");
    $this->click("xpath=//td[@class='crm-event-form-block-participant_status']/div[@class='listing-box']//div/label[text()='Pending from pay later']");
    $this->click('_qf_Search_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("xpath=//div[@id='participantSearch']/table/tbody//tr/td[11]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $pID = $this->urlArg('id');
    $contributionID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $pID, 'contribution_id', 'participant_id');
    $this->click("xpath=//tr[@id='rowid$contributionID']/td[8]/span//a[text()='Edit']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->select('contribution_status_id', 'label=Completed');
    $this->click('_qf_Contribution_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->verifyFinancialRecords($contributionID);
  }

  function verifyFinancialRecords($contributionID) {
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

  function testEventApprovalRegistration() {
    $this->webtestLogin();

    //Participant Status
    $this->openCiviPage("admin/participant_status", "reset=1&action=browse");
    $this->_testEnableParticipantStatuses('Awaiting approval');
    $this->isElementPresent("xpath=//td[@class='crm-particpant-label'][contains(text(), 'Awaiting approval')]/../td[9]/span/a[2][text()='Disable']");
    $this->_testEnableParticipantStatuses('Pending from approval');
    $this->isElementPresent("xpath=//td[@class='crm-particpant-label'][contains(text(), 'Pending from approval')]/../td[9]/span/a[2][text()='Disable']");
    $this->_testEnableParticipantStatuses('Rejected');
    $this->isElementPresent("xpath=//td[@class='crm-particpant-label'][contains(text(), 'Rejected')]/../td[9]/span/a[2][text()='Disable']");

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
    $this->fillRichTextField('description', $eventDescription );
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
    $this->click("xpath=//tr[@class='crm-event-manage-fee-form-block-payment_processor']/td[2]/label[text()='$processorName']");
    $this->select('financial_type_id','label=Event Fee');
    $this->type("label[1]",'Junior Stars');
    $this->type("value[1]",'500.00');
    $this->type("label[2]",'Super Stars');
    $this->type("value[2]",'1000.00');
    $this->check('default');
    $this->click('_qf_Fee_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // intro text for registration page
    $registerIntro = 'Fill in all the fields below and click Continue.';

   // Go to Online Registration tab
   $this->click('link=Online Registration');
   $this->waitForElementPresent('_qf_Registration_upload-bottom');
   $this->check('is_online_registration');
   $this->assertChecked('is_online_registration');

   //Requires Approvel
   $this->check('requires_approval');
   $this->assertChecked('requires_approval');
   $this->click('_qf_Registration_upload-bottom');
   $this->waitForPageToLoad($this->getTimeoutMsec());
   $this->waitForTextPresent("'Registration' information has been saved.");

   // verify event input on info page
   // start at Manage Events listing
   $this->openCiviPage('event/manage', 'reset=1');
   $this->click("link=$eventTitle");
   $this->waitForPageToLoad($this->getTimeoutMsec());
   $firstName = substr(sha1(rand()), 0, 7);
   $this->webtestAddContact($firstName, 'Anderson', TRUE);
   $contactName = "Anderson, $firstName";
   $displayName = "$firstName Anderson";
   $this->openCiviPage("event/register", "reset=1&id=$id", '_qf_Register_upload-bottom');
   $this->type('first_name',$firstName);

   //fill in last name
   $lastName = 'Recuron'.substr(sha1(rand()), 0, 7);
   $this->type('last_name', $contactName);
   $email = $firstName . '@example.com';
   $this->type('email-Primary', $email);
   $this->click('_qf_Register_upload');
   $this->waitForElementPresent("_qf_Confirm_next");
   $this->click('_qf_Confirm_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());
   $this->waitForElementPresent("xpath=//div[@class='section']");
   $this->assertTextPresent("Thank You for Registering");

  }

 function _testEnableParticipantStatuses($status) {
   // enable participant status
   if ($this->isElementPresent("xpath=//td[@class='crm-particpant-label'][contains(text(), '{$status}')]/../td[9]/span/a[2][text()='Enable']")){
     $this->click("xpath=//td[@class='crm-particpant-label'][contains(text(), '{$status}')]/../td[9]/span/a[2][text()='Enable']");
     $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']");
     $this->click("xpath=//div[@class='ui-dialog-buttonset']/button[2]");
   }
 }
}

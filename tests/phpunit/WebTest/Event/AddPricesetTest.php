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


require_once 'CiviTest/CiviSeleniumTestCase.php';
class WebTest_Event_AddPricesetTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAddPriceSet() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor  = 'Event';
    $setHelp  = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp);

    // Get the price set id ($sid) by retrieving and parsing the URL of the New Price Field form
    // which is where we are after adding Price Set.
    $elements = $this->parseURL();
    $sid = $elements['queryString']['sid'];
    $this->assertType('numeric', $sid);

    $validStrings = array();

    $fields = array(
      'Full Conference' => 'Text',
      'Meal Choice' => 'Select',
      'Pre-conference Meetup?' => 'Radio',
      'Evening Sessions' => 'CheckBox',
    );
    $this->_testAddPriceFields($fields, $validateStrings);
    // var_dump($validateStrings);

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);
  }

  function _testAddSet($setTitle, $usedFor, $setHelp, $financialType = 'Event Fee') {
    $this->open($this->sboxPath . 'civicrm/admin/price?reset=1&action=add');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_Set_next-bottom');

    // Enter Priceset fields (Title, Used For ...)
    $this->type('title', $setTitle);
    if ($usedFor == 'Event') {
      $this->check('extends[1]');
    }
    elseif ($usedFor == 'Contribution') {
      $this->check('extends[2]');
    }

    $this->select("css=select.form-select", "label={$financialType}");    
    $this->type('help_pre', $setHelp);

    $this->assertChecked('is_active', 'Verify that Is Active checkbox is set.');
    $this->click('_qf_Set_next-bottom');

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_Field_next-bottom');
  }

  function _testAddPriceFields(&$fields, &$validateStrings, $dateSpecificFields = FALSE) {
    foreach ($fields as $label => $type) {
      $validateStrings[] = $label;

      $this->type('label', $label);
      $this->select('html_type', "value={$type}");

      switch ($type) {
        case 'Text':
          $validateStrings[] = '525.00';
          $this->type('price', '525.00');
          $this->select('financial_type_id', 'Donation');
          if ($dateSpecificFields == TRUE) {
            $this->webtestFillDateTime('active_on', '+1 week');
          }
          else {
            $this->check('is_required');
          }
          break;

        case 'Select':
          $options = array(
            1 => array('label' => 'Chicken',
              'amount' => '30.00',
              'financial_type_id' => 'Donation'
            ),
            2 => array(
              'label' => 'Vegetarian',
              'amount' => '25.00',
              'financial_type_id' => 'Donation'
            ),
          );
          $this->addMultipleChoiceOptions($options, $validateStrings);
          if ($dateSpecificFields == TRUE) {
            $this->webtestFillDateTime('expire_on', '-1 week');
          }
          break;

        case 'Radio':
          $options = array(
            1 => array('label' => 'Yes',
              'amount' => '50.00',
              'financial_type_id' => 'Donation'          
             ),
            2 => array(
              'label' => 'No',
              'amount' => '0',
              'financial_type_id' => 'Donation'
            ),
          );
          $this->addMultipleChoiceOptions($options, $validateStrings);
          $this->check('is_required');
          if ($dateSpecificFields == TRUE) {
            $this->webtestFillDateTime('active_on', '-1 week');
          }
          break;

        case 'CheckBox':
          $options = array(
            1 => array('label' => 'First Night',
              'amount' => '15.00',
              'financial_type_id' => 'Donation'
            ),
            2 => array(
              'label' => 'Second Night',
              'amount' => '15.00',
              'financial_type_id' => 'Donation'
            ),
          );
          $this->addMultipleChoiceOptions($options, $validateStrings);
          if ($dateSpecificFields == TRUE) {
            $this->webtestFillDateTime('expire_on', '+1 week');
          }
          break;

        default:
          break;
      }
      $this->click('_qf_Field_next_new-bottom');
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->waitForElementPresent('_qf_Field_next-bottom');
    }
  }

  function _testVerifyPriceSet($validateStrings, $sid) {
    // verify Price Set at Preview page
    // start at Manage Price Sets listing
    $this->open($this->sboxPath . 'civicrm/admin/price?reset=1');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Use the price set id ($sid) to pick the correct row
    $this->click("css=tr#row_{$sid} a[title='Preview Price Set']");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    // Look for Register button
    $this->waitForElementPresent('_qf_Preview_cancel-bottom');

    // Check for expected price set field strings
    $this->assertStringsPresent($validateStrings);
  }

  function testRegisterWithPriceSet() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor  = 'Event';
    $setHelp  = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp);

    // Get the price set id ($sid) by retrieving and parsing the URL of the New Price Field form
    // which is where we are after adding Price Set.
    $elements = $this->parseURL();
    $sid = $elements['queryString']['sid'];
    $this->assertType('numeric', $sid);

    $validStrings = array();
    $fields = array(
      'Full Conference' => 'Text',
      'Meal Choice' => 'Select',
      'Pre-conference Meetup?' => 'Radio',
      'Evening Sessions' => 'CheckBox',
    );
    $this->_testAddPriceFields($fields, $validateStrings);

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);

    // We need a payment processor
    $processorName = 'Webtest Dummy' . substr(sha1(rand()), 0, 7);
    $this->webtestAddPaymentProcessor($processorName);

    // Go directly to the URL of the screen that you will be testing (New Event).
    $this->open($this->sboxPath . 'civicrm/event/add?reset=1&action=add');

    $eventTitle       = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $email            = 'Smith' . substr(sha1(rand()), 0, 7) . '@example.com';
    $eventDescription = 'Here is a description for this conference.';

    $this->waitForElementPresent('_qf_EventInfo_upload-bottom');

    // Let's start filling the form with values.
    $this->select('event_type_id', 'value=1');

    // Attendee role s/b selected now.
    $this->select('default_role_id', 'value=1');

    // Enter Event Title, Summary and Description
    $this->type('title', $eventTitle);
    $this->type('summary', 'This is a great conference. Sign up now!');

    // Type description in ckEditor (fieldname, text to type, editor)
    $this->fillRichTextField('description', $eventDescription);

    // Choose Start and End dates.
    // Using helper webtestFillDate function.
    $this->webtestFillDateTime("start_date", "+1 week");
    $this->webtestFillDateTime("end_date", "+1 week 1 day 8 hours ");

    $this->type('max_participants', '50');
    $this->click('is_map');
    $this->click('_qf_EventInfo_upload-bottom');

    // Wait for Location tab form to load
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Go to Fees tab
    $this->click('link=Fees');
    $this->waitForElementPresent('_qf_Fee_upload-bottom');
    $this->click('CIVICRM_QFID_1_is_monetary');
    $this->click("xpath=//tr[@class='crm-event-manage-fee-form-block-payment_processor']/td[2]/label[text()='$processorName']");
    $this->select('financial_type_id','label=Event Fee');
    $this->select('price_set_id', 'label=' . $setTitle);

    $this->click('_qf_Fee_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // intro text for registration page
    $registerIntro = 'Fill in all the fields below and click Continue.';

    // Go to Online Registration tab
    $this->click('link=Online Registration');
    $this->waitForElementPresent('_qf_Registration_upload-bottom');

    $this->check('is_online_registration');
    $this->assertChecked('is_online_registration');

    $this->fillRichTextField('intro_text', $registerIntro);

    // enable confirmation email
    $this->click('CIVICRM_QFID_1_is_email_confirm');
    $this->type('confirm_from_name', 'Jane Doe');
    $this->type('confirm_from_email', 'jane.doe@example.org');

    $this->click('_qf_Registration_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForTextPresent("'Registration' information has been saved.");

    // verify event input on info page
    // start at Manage Events listing
    $this->open($this->sboxPath . 'civicrm/event/manage?reset=1');
    $this->click("link=$eventTitle");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $eventInfoUrl = $this->getLocation();

    $this->open($this->sboxPath . 'civicrm/logout?reset=1');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->open($eventInfoUrl);
    $this->click('link=Register Now');
    $this->waitForElementPresent('_qf_Register_upload-bottom');

    $this->type("xpath=//input[@class='form-text four required']", "1");
    $this->click("xpath=//input[@class='form-radio']");
    $this->click("xpath=//input[@class='form-checkbox']");
    $this->type('email-Primary', $email);

    $this->waitForElementPresent('credit_card_type');
    $this->select('credit_card_type', 'value=Visa');
    $this->type('credit_card_number', '4111111111111111');
    $this->type('cvv2', '000');
    $this->select('credit_card_exp_date[M]', 'value=1');
    $this->select('credit_card_exp_date[Y]', 'value=2020');
    $this->type('billing_first_name', 'Jane');
    $this->type('billing_last_name', 'San');
    $this->type('billing_street_address-5', '15 Main St.');
    $this->type(' billing_city-5', 'San Jose');
    $this->select('billing_country_id-5', 'value=1228');
    $this->select('billing_state_province_id-5', 'value=1004');
    $this->type('billing_postal_code-5', '94129');

    $this->click('_qf_Register_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_Confirm_next-bottom');
    $confirmStrings = array('Event Fee(s)', 'Billing Name and Address', 'Credit Card Information');
    $this->assertStringsPresent($confirmStrings);
    $this->click('_qf_Confirm_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $thankStrings = array('Thank You for Registering', 'Event Total', 'Transaction Date');
    $this->assertStringsPresent($thankStrings);

    //login to check participant
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Participant
    $this->open($this->sboxPath . 'civicrm/event/search?reset=1');

    $this->waitForElementPresent('_qf_Search_refresh');

    $this->type('sort_name', "$email");
    $this->click('_qf_Search_refresh');

    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("xpath=id('participantSearch')/table/tbody/tr/td[11]/span/a[text()='View']");
    $this->click("xpath=id('participantSearch')/table/tbody/tr/td[11]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ParticipantView_cancel-bottom');

    $expected = array(
      2 => 'Full Conference',
      3 => 'Pre-conference Meetup? - Yes',
      4 => 'Evening Sessions - First Night',
    );
    foreach ($expected as $value => $label) {
      $this->verifyText("xpath=id('ParticipantView')/div[2]/table[1]/tbody/tr[7]/td[2]/table/tbody/tr[$value]/td", $label);
    }
    // Fixme: We can't asset full string like - "Event Total: $ 590.00" as it has special char
    $this->assertStringsPresent(' 590.00');
    $this->click('_qf_ParticipantView_cancel-bottom');
  }

  function testParticipantWithDateSpecificPriceSet() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor  = 'Event';
    $setHelp  = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp);

    // Get the price set id ($sid) by retrieving and parsing the URL of the New Price Field form
    // which is where we are after adding Price Set.
    $elements = $this->parseURL();
    $sid = $elements['queryString']['sid'];
    $this->assertType('numeric', $sid);

    $validStrings = array();
    $fields = array(
      'Full Conference' => 'Text',
      'Meal Choice' => 'Select',
      'Pre-conference Meetup?' => 'Radio',
      'Evening Sessions' => 'CheckBox',
    );
    $this->_testAddPriceFields($fields, $validateStrings, TRUE);

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);

    // We need a payment processor
    $processorName = 'Webtest Dummy' . substr(sha1(rand()), 0, 7);
    $this->webtestAddPaymentProcessor($processorName);

    // Go directly to the URL of the screen that you will be testing (New Event).
    $this->open($this->sboxPath . 'civicrm/event/add?reset=1&action=add');

    $eventTitle       = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $email            = 'Smith' . substr(sha1(rand()), 0, 7) . '@example.com';
    $eventDescription = 'Here is a description for this conference.';

    $this->waitForElementPresent('_qf_EventInfo_upload-bottom');

    // Let's start filling the form with values.
    $this->select('event_type_id', 'value=1');

    // Attendee role s/b selected now.
    $this->select('default_role_id', 'value=1');

    // Enter Event Title, Summary and Description
    $this->type('title', $eventTitle);
    $this->type('summary', 'This is a great conference. Sign up now!');

    // Type description in ckEditor (fieldname, text to type, editor)
    $this->fillRichTextField('description', $eventDescription );

    // Choose Start and End dates.
    // Using helper webtestFillDate function.
    $this->webtestFillDateTime("start_date", "+1 week");
    $this->webtestFillDateTime("end_date", "+1 week 1 day 8 hours ");

    $this->type('max_participants', '50');
    $this->click('is_map');
    $this->click('_qf_EventInfo_upload-bottom');

    // Wait for Location tab form to load
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Go to Fees tab
    $this->click('link=Fees');
    $this->waitForElementPresent('_qf_Fee_upload-bottom');
    $this->click('CIVICRM_QFID_1_is_monetary');
    $this->click("xpath=//tr[@class='crm-event-manage-fee-form-block-payment_processor']/td[2]/label[text()='$processorName']");
    $this->select('financial_type_id','label=Event Fee');
    $this->select('price_set_id', 'label=' . $setTitle);

    $this->click('_qf_Fee_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // intro text for registration page
    $registerIntro = 'Fill in all the fields below and click Continue.';

    // Go to Online Registration tab
    $this->click('link=Online Registration');
    $this->waitForElementPresent('_qf_Registration_upload-bottom');

    $this->check('is_online_registration');
    $this->assertChecked('is_online_registration');

    $this->fillRichTextField('intro_text', $registerIntro);

    // enable confirmation email
    $this->click('CIVICRM_QFID_1_is_email_confirm');
    $this->type('confirm_from_name', 'Jane Doe');
    $this->type('confirm_from_email', 'jane.doe@example.org');

    $this->click('_qf_Registration_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForTextPresent("'Registration' information has been saved.");

    // verify event input on info page
    // start at Manage Events listing
    $this->open($this->sboxPath . 'civicrm/event/manage?reset=1');
    $this->click("link=$eventTitle");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    // Adding contact with randomized first name (so we can then select that contact when creating event registration)
    // We're using Quick Add block on the main page for this.
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, 'Anderson', TRUE);
    $contactName = "Anderson, $firstName";
    $displayName = "$firstName Anderson";

    // Go directly to the URL of the screen that you will be testing (Register Participant for Event-standalone).
    $this->open($this->sboxPath . 'civicrm/participant/add?reset=1&action=add&context=standalone');

    // As mentioned before, waitForPageToLoad is not always reliable. Below, we're waiting for the submit
    // button at the end of this page to show up, to make sure it's fully loaded.
    $this->waitForElementPresent('_qf_Participant_upload-bottom');

    // Let's start filling the form with values.
    // Type contact last name in contact auto-complete, wait for dropdown and click first result
    $this->webtestFillAutocomplete($firstName);

    // Select event. Based on label for now.
    $this->select('event_id', "label=regexp:$eventTitle");
    // Select role
    $this->click('role_id[2]');

    $this->click("xpath=//input[@class='form-radio']");
    $this->click("xpath=//input[@class='form-checkbox']");

    // Choose Registration Date.
    // Using helper webtestFillDate function.
    $this->webtestFillDate('register_date', 'now');
    $today = date('F jS, Y', strtotime('now'));

    // Select participant status
    $this->select('status_id', 'value=1');

    // Clicking save.
    $this->click('_qf_Participant_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    // Is status message correct?
    $this->assertTrue($this->isTextPresent("Event registration for $displayName has been added"), "Status message didn't show up after saving!");

    $this->waitForElementPresent("xpath=//div[@id='Events']//table//tbody/tr[1]/td[8]/span/a[text()='View']");

    //click through to the participant view screen
    $this->click("xpath=//div[@id='Events']//table//tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ParticipantView_cancel-bottom');
  }
}


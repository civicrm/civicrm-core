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
 * Class WebTest_Event_ParticipantCountTest
 */
class WebTest_Event_ParticipantCountTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testParticipantCountWithFeelevel() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    // create an event
    $eventTitle = 'A Conference - ' . substr(sha1(rand()), 0, 7);
    $paramsEvent = array(
      'title' => $eventTitle,
      'template_id' => 6,
      'event_type_id' => 4,
      'payment_processor' => $processorName,
      'fee_level' => array(
        'Member' => '250.00',
        'Non-Member' => '325.00',
      ),
    );

    $infoEvent = $this->_testAddEvent($paramsEvent);

    // logout to register for event.
    $this->webtestLogout();

    // Register Participant 1
    // visit event info page
    $this->open($infoEvent);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // register for event
    $this->click('link=Register Now');
    $this->waitForElementPresent('_qf_Register_upload-bottom');
    $this->click("xpath=//input[@class='crm-form-radio']");

    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $this->type('first_name', 'Mary');
    $this->type('last_name', 'Jones' . substr(sha1(rand()), 0, 5));
    $this->type('email-Primary', $email);

    // fill billing details and register
    $this->_testRegisterWithBillingInfo();

    // Register Participant 2
    // visit event info page
    $this->open($infoEvent);

    // register for event
    $this->click('link=Register Now');
    $this->waitForElementPresent('_qf_Register_upload-bottom');

    $this->click("xpath=//input[@class='crm-form-radio']");
    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $this->type('first_name', 'Mary');
    $this->type('last_name', 'Jones' . substr(sha1(rand()), 0, 5));
    $this->type('email-Primary', $email);

    // fill billing details and register
    $this->_testRegisterWithBillingInfo();

    // login to check participant count
    $this->webtestLogin();

    // Find Participant
    $this->openCiviPage("event/search", "reset=1", 'participant_fee_amount_low');
    $this->select2("event_id", $eventTitle);
    $this->click('_qf_Search_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // verify number of registered participants
    $this->assertElementContainsText("xpath=//div[@class='crm-results-block']//div/table/tbody/tr/td", '2 Results');
  }

  public function testParticipantCountWithPriceset() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    // create priceset
    $priceset = 'Price - ' . substr(sha1(rand()), 0, 7);
    $this->_testAddSet($priceset);

    // create price fields
    $fields = array(
      'Full Conference' => array(
        'type' => 'Text',
        'amount' => '525.00',
        'count' => '2',
      ),
      'Meal Choice' => array(
        'type' => 'Select',
        'options' => array(
          1 => array(
            'label' => 'Chicken',
            'amount' => '525.00',
            'count' => '2',
          ),
          2 => array(
            'label' => 'Vegetarian',
            'amount' => '200.00',
            'count' => '2',
          ),
        ),
      ),
      'Pre-conference Meetup?' => array(
        'type' => 'Radio',
        'options' => array(
          1 => array(
            'label' => 'Yes',
            'amount' => '50.00',
            'count' => '2',
          ),
          2 => array(
            'label' => 'No',
            'amount' => '0',
          ),
        ),
      ),
      'Evening Sessions' => array(
        'type' => 'CheckBox',
        'options' => array(
          1 => array(
            'label' => 'First Five',
            'amount' => '100.00',
            'count' => '5',
          ),
          2 => array(
            'label' => 'Second Four',
            'amount' => '50.00',
            'count' => '4',
          ),
        ),
      ),
    );

    foreach ($fields as $label => $field) {
      $this->waitForAjaxContent();
      $this->select('html_type', "value={$field['type']}");
      if ($field['type'] == 'Text') {
        $this->type('price', $field['amount']);
        //yash
        $this->waitForElementPresent('count');
        $this->type('count', $field['count']);
        $this->check('is_required');
      }
      else {
        $this->_testAddMultipleChoiceOptions($field['options']);
      }
      $this->type('label', $label);
      $this->clickLink('_qf_Field_next_new-bottom', '_qf_Field_next-bottom', FALSE);
      $this->waitForText("crm-notification-container", "Price Field '$label' has been saved.");
    }

    // create event.
    $eventTitle = 'Meeting - ' . substr(sha1(rand()), 0, 7);
    $paramsEvent = array(
      'title' => $eventTitle,
      'template_id' => 6,
      'event_type_id' => 4,
      'payment_processor' => $processorName,
      'price_set' => $priceset,
    );

    $infoEvent = $this->_testAddEvent($paramsEvent);

    // logout to register for event.
    $this->webtestLogout();

    $priceFieldOptionCounts = $participants = array();

    // Register Participant 1
    // visit event info page
    $this->open($infoEvent);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // register for event
    $this->click('link=Register Now');
    $this->waitForElementPresent('_qf_Register_upload-bottom');

    $this->type("xpath=//input[@class='four crm-form-text required']", '1');

    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $participants[1] = array(
      'email' => $email,
      'first_name' => 'Jane_' . substr(sha1(rand()), 0, 5),
      'last_name' => 'San_' . substr(sha1(rand()), 0, 5),
    );

    $this->type('first_name', $participants[1]['first_name']);
    $this->type('last_name', $participants[1]['last_name']);
    $this->type('email-Primary', $email);

    // fill billing related info and register
    $this->_testRegisterWithBillingInfo($participants[1]);

    // Options filled by 1st participants.
    $priceFieldOptionCounts[1] = array(
      'Full Conference' => 1,
      'Meal Choice - Chicken' => 1,
      'Meal Choice - Vegetarian' => 0,
      'Pre-conference Meetup? - Yes' => 1,
      'Pre-conference Meetup? - No' => 0,
      'Evening Sessions - First Five' => 1,
      'Evening Sessions - Second Four' => 0,
    );

    // Register Participant 1
    // visit event info page
    $this->open($infoEvent);

    // register for event
    $this->click('link=Register Now');
    $this->waitForElementPresent('_qf_Register_upload-bottom');

    $this->type("xpath=//input[@class='four crm-form-text required']", '2');
    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';

    $participants[2] = array(
      'email' => $email,
      'first_name' => 'Jane_' . substr(sha1(rand()), 0, 5),
      'last_name' => 'San_' . substr(sha1(rand()), 0, 5),
    );

    $this->type('first_name', $participants[2]['first_name']);
    $this->type('last_name', $participants[2]['last_name']);
    $this->type('email-Primary', $email);

    // fill billing related info and register
    $this->_testRegisterWithBillingInfo($participants[2]);

    // Options filled by 2nd participants.
    $priceFieldOptionCounts[2] = array(
      'Full Conference' => 2,
      'Meal Choice - Chicken' => 1,
      'Meal Choice - Vegetarian' => 0,
      'Pre-conference Meetup? - Yes' => 1,
      'Pre-conference Meetup? - No' => 0,
      'Evening Sessions - First Five' => 1,
      'Evening Sessions - Second Four' => 0,
    );

    // login to check participant count
    $this->webtestLogin();

    // Find Participant
    $this->openCiviPage('event/search', 'reset=1', 'participant_fee_amount_low');
    $this->waitForElementPresent('event_id');
    $this->select2("event_id", $eventTitle);
    $this->click('_qf_Search_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // verify number of participants records and total participant count
    $this->waitForAjaxContent();
    $this->assertStringsPresent(array('2 Results', 'Actual participant count : 24'));

    // CRM-7953, check custom search Price Set Details for Event
    // Participants
    $this->_testPricesetDetailsCustomSearch($paramsEvent, $participants, $priceFieldOptionCounts);
  }

  /**
   * @param $setTitle
   * @param string $financialType
   */
  public function _testAddSet($setTitle, $financialType = 'Event Fee') {
    $this->openCiviPage('admin/price', 'reset=1&action=add', '_qf_Set_next-bottom');

    // Enter Priceset fields (Title, Used For ...)
    $this->waitForElementPresent("title");
    $this->type('title', $setTitle);
    $this->check('extends[1]');
    $this->select("css=select.crm-form-select", "label={$financialType}");
    $this->waitForElementPresent("help_pre");
    $this->type('help_pre', 'This is test priceset.');

    $this->assertChecked('is_active', 'Verify that Is Active checkbox is set.');
    $this->clickLink('_qf_Set_next-bottom', '_qf_Field_next-bottom');
  }

  /**
   * @param $options
   */
  public function _testAddMultipleChoiceOptions($options) {
    foreach ($options as $oIndex => $oValue) {
      $this->type("option_label_{$oIndex}", $oValue['label']);
      $this->type("option_amount_{$oIndex}", $oValue['amount']);
      if (array_key_exists('count', $oValue)) {
        $this->waitForElementPresent("option_count_{$oIndex}");
        $this->type("option_count_{$oIndex}", $oValue['count']);
      }
      $this->click('link=another choice');
    }
    $this->click('CIVICRM_QFID_1_2');
  }

  /**
   * @param array $params
   *
   * @return string
   */
  public function _testAddEvent($params) {
    $this->openCiviPage('event/add', 'reset=1&action=add', '_qf_EventInfo_upload-bottom');

    $this->select('event_type_id', "value={$params['event_type_id']}");

    // Attendee role s/b selected now.
    $this->select('default_role_id', 'value=1');

    // Enter Event Title, Summary and Description
    $this->type('title', $params['title']);
    $this->type('summary', 'This is a great conference. Sign up now!');
    $this->fillRichTextField('description', 'Here is a description for this event.', 'CKEditor');

    // Choose Start and End dates.
    // Using helper webtestFillDate function.
    $this->webtestFillDateTime('start_date', '+1 week');
    $this->webtestFillDateTime('end_date', '+1 week 1 day 8 hours ');

    $this->type('max_participants', '50');
    $this->click('is_map');
    $this->click('_qf_EventInfo_upload-bottom');

    // Wait for Location tab form to load
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Go to Fees tab
    $this->click('link=Fees');
    $this->waitForElementPresent('_qf_Fee_upload-bottom');
    $this->click('CIVICRM_QFID_1_is_monetary');
    $this->select2('payment_processor', $params['payment_processor'], TRUE);

    $this->select('financial_type_id', 'Event Fee');
    if (array_key_exists('price_set', $params)) {
      $this->select('price_set_id', 'label=' . $params['price_set']);
    }
    if (array_key_exists('fee_level', $params)) {
      $counter = 1;
      foreach ($params['fee_level'] as $label => $amount) {
        $this->type("label_{$counter}", $label);
        $this->type("value_{$counter}", $amount);
        $counter++;
      }
    }

    $this->click('_qf_Fee_upload-bottom');
    $this->waitForElementPresent('_qf_Fee_cancel-top');

    // Go to Online Registration tab
    $this->click('link=Online Registration');
    $this->waitForElementPresent('_qf_Registration_upload-bottom');

    $this->check('is_online_registration');
    $this->assertChecked('is_online_registration');

    $this->click('intro_text');
    $this->fillRichTextField('intro_text', 'Fill in all the fields below and click Continue.', 'CKEditor', TRUE);

    // enable confirmation email
    $this->click('CIVICRM_QFID_1_is_email_confirm');
    $this->type('confirm_from_name', 'Jane Doe');
    $this->type('confirm_from_email', 'jane.doe@example.org');

    $this->click('_qf_Registration_upload-bottom');
    $this->waitForElementPresent('_qf_Registration_upload-bottom');
    $this->waitForTextPresent("'Online Registration' information has been saved.");

    // verify event input on info page
    // start at Manage Events listing
    $this->openCiviPage('event/manage', 'reset=1');
    $this->click('link=' . $params['title']);

    $this->waitForPageToLoad($this->getTimeoutMsec());
    return $this->getLocation();
  }

  /**
   * @param array $participant
   */
  public function _testRegisterWithBillingInfo($participant = array()) {
    $this->waitForElementPresent("credit_card_type");
    $this->select('credit_card_type', 'value=Visa');
    $this->type('credit_card_number', '4111111111111111');
    $this->type('cvv2', '000');
    $this->select('credit_card_exp_date[M]', 'value=1');
    $this->select('credit_card_exp_date[Y]', 'value=2020');
    $this->type('billing_first_name', isset($participant['first_name']) ? $participant['first_name'] : 'Jane_' . substr(sha1(rand()), 0, 5));
    $this->type('billing_last_name', isset($participant['last_name']) ? $participant['last_name'] : 'San_' . substr(sha1(rand()), 0, 5));
    $this->type('billing_street_address-5', '15 Main St.');
    $this->type(' billing_city-5', 'San Jose');
    $this->select('billing_country_id-5', 'value=1228');
    $this->select('billing_state_province_id-5', 'value=1004');
    $this->type('billing_postal_code-5', '94129');

    $this->clickLink('_qf_Register_upload-bottom', '_qf_Confirm_next-bottom', FALSE);
    $confirmStrings = array('Event Fee(s)', 'Billing Name and Address', 'Credit Card Information');
    $this->assertStringsPresent($confirmStrings);
    $this->click('_qf_Confirm_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $thankStrings = array('Thank You for Registering', 'Event Total', 'Transaction Date');
    $this->assertStringsPresent($thankStrings);
  }

  /**
   * @param array $eventParams
   * @param $participants
   * @param $priceFieldOptionCounts
   */
  public function _testPricesetDetailsCustomSearch($eventParams, $participants, $priceFieldOptionCounts) {
    $this->openCiviPage('contact/search/custom', 'csid=9&reset=1');

    $this->select('event_id', 'label=' . $eventParams['title']);
    $this->click('_qf_Custom_refresh-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $tableHeaders = array('Contact ID', 'Participant ID', 'Name');
    $tableHeaders = array_merge($tableHeaders, array_keys(current($priceFieldOptionCounts)));

    $tdnum = 2;
    foreach ($tableHeaders as $header) {
      $this->verifyText("xpath=//form[@id='Custom']//div[@class='crm-search-results']//table[@class='selector row-highlight']/thead/tr[1]/th[$tdnum]", $header);
      $tdnum++;
    }

    foreach ($participants as $participantNum => $participant) {
      $tdnum = 4;
      $this->verifyText("xpath=//form[@id='Custom']//div[@class='crm-search-results']//table[@class='selector row-highlight']/tbody/tr[{$participantNum}]/td[{$tdnum}]", preg_quote("{$participant['first_name']} {$participant['last_name']}"));
      foreach ($priceFieldOptionCounts[$participantNum] as $priceFieldOptionCount) {
        $tdnum++;
        $this->verifyText("xpath=//form[@id='Custom']//div[@class='crm-search-results']//table[@class='selector row-highlight']/tbody/tr[{$participantNum}]/td[{$tdnum}]", preg_quote($priceFieldOptionCount));
      }
    }
  }

}

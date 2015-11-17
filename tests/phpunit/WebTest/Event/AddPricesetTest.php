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
 * Class WebTest_Event_AddPricesetTest
 */
class WebTest_Event_AddPricesetTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAddPriceSet() {

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor = 'Event';
    $setHelp = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp);

    // Get the price set id ($sid) by retrieving and parsing the URL of the New Price Field form
    // which is where we are after adding Price Set.
    $sid = $this->urlArg('sid');
    $this->assertType('numeric', $sid);

    $validateStrings = array();

    $fields = array(
      'Full Conference' => 'Text',
      'Meal Choice' => 'Select',
      'Pre-conference Meetup?' => 'Radio',
      'Evening Sessions' => 'CheckBox',
    );
    $this->_testAddPriceFields($fields, $validateStrings);

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);
  }

  /**
   * @param $setTitle
   * @param $usedFor
   * @param $setHelp
   * @param string $financialType
   */
  public function _testAddSet($setTitle, $usedFor, $setHelp, $financialType = 'Event Fee') {
    $this->openCiviPage('admin/price', 'reset=1&action=add', '_qf_Set_next-bottom');

    // Enter Priceset fields (Title, Used For ...)
    $this->type('title', $setTitle);
    if ($usedFor == 'Event') {
      $this->check('extends[1]');
    }
    elseif ($usedFor == 'Contribution') {
      $this->check('extends[2]');
    }

    $this->select("financial_type_id", "label={$financialType}");

    $this->type('help_pre', $setHelp);

    $this->assertChecked('is_active', 'Verify that Is Active checkbox is set.');
    $this->clickLink('_qf_Set_next-bottom');
  }

  /**
   * @param $fields
   * @param $validateStrings
   * @param bool $dateSpecificFields
   */
  public function _testAddPriceFields(&$fields, &$validateStrings, $dateSpecificFields = FALSE) {
    $this->clickLinkSuppressPopup('newPriceField');
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
            1 => array(
              'label' => 'Chicken',
              'amount' => '30.00',
              'financial_type_id' => 'Donation',
            ),
            2 => array(
              'label' => 'Vegetarian',
              'amount' => '25.00',
              'financial_type_id' => 'Donation',
            ),
          );
          $this->addMultipleChoiceOptions($options, $validateStrings);
          if ($dateSpecificFields == TRUE) {
            $this->webtestFillDateTime('expire_on', '-1 week');
          }
          break;

        case 'Radio':
          $options = array(
            1 => array(
              'label' => 'Yes',
              'amount' => '50.00',
              'financial_type_id' => 'Donation',

            ),
            2 => array(
              'label' => 'No',
              'amount' => '0',
              'financial_type_id' => 'Donation',
            ),
          );
          $this->addMultipleChoiceOptions($options, $validateStrings);
          $this->click('is_required');
          if ($dateSpecificFields == TRUE) {
            $this->webtestFillDateTime('active_on', '-1 week');
          }
          break;

        case 'CheckBox':
          $options = array(
            1 => array(
              'label' => 'First Night',
              'amount' => '15.00',
              'financial_type_id' => 'Donation',
            ),
            2 => array(
              'label' => 'Second Night',
              'amount' => '15.00',
              'financial_type_id' => 'Donation',
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
      $this->clickLink('_qf_Field_next_new-bottom', '_qf_Field_next-bottom');
      $this->waitForText('crm-notification-container', "Price Field '" . $label . "' has been saved.");
    }
  }

  /**
   * @param $validateStrings
   * @param int $sid
   */
  public function _testVerifyPriceSet($validateStrings, $sid) {
    // verify Price Set at Preview page
    // start at Manage Price Sets listing
    $this->openCiviPage('admin/price', 'reset=1');

    // Use the price set id ($sid) to pick the correct row
    $this->clickLink("//*[@id='price_set-{$sid}']/td[4]/span[1]/a[2]", '_qf_Preview_cancel-bottom');

    // Check for expected price set field strings
    if ($this->isElementPresent("xpath=//*[@class ='select2-chosen']")) {
      $this->clickAt("xpath=//*[@class ='select2-chosen']");
    }
    $this->assertStringsPresent($validateStrings);
  }

  public function testRegisterWithPriceSet() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor = 'Event';
    $setHelp = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp);

    // Get the price set id ($sid) by retrieving and parsing the URL of the New Price Field form
    // which is where we are after adding Price Set.
    $sid = $this->urlArg('sid');
    $this->assertType('numeric', $sid);

    $validStrings = array();
    $fields = array(
      'Full Conference' => 'Text',
      'Pre-conference Meetup?' => 'Radio',
      'Evening Sessions' => 'CheckBox',
    );
    $this->_testAddPriceFields($fields, $validateStrings);

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

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

    // Choose Start and End dates.
    // Using helper webtestFillDate function.
    $this->webtestFillDateTime("start_date", "+1 week");
    $this->webtestFillDateTime("end_date", "+1 week 1 day 8 hours ");

    $this->type('max_participants', '50');
    $this->click('is_map');
    $this->click('_qf_EventInfo_upload-bottom');

    // Wait for Location tab form to load
    $this->waitForElementPresent('_qf_Location_upload_done-bottom');

    // Go to Fees tab
    $this->click('link=Fees');
    $this->waitForElementPresent('_qf_Fee_upload_done-bottom');
    $this->click('CIVICRM_QFID_1_is_monetary');
    $this->select2('payment_processor', $processorName, TRUE);
    $this->select('financial_type_id', 'label=Event Fee');
    $this->select('price_set_id', 'label=' . $setTitle);

    // intro text for registration page
    $registerIntro = 'Fill in all the fields below and click Continue.';
    $this->clickLink('_qf_Fee_upload-bottom', 'link=Online Registration', FALSE);

    // Go to Online Registration tab
    $this->click('link=Online Registration');
    $this->waitForElementPresent('_qf_Registration_upload-bottom');

    $this->check('is_online_registration');
    $this->assertChecked('is_online_registration');

    $this->fillRichTextField('intro_text', $registerIntro, 'CKEditor', TRUE);

    // enable confirmation email
    $this->click('CIVICRM_QFID_1_is_email_confirm');
    $this->type('confirm_from_name', 'Jane Doe');
    $this->type('confirm_from_email', 'jane.doe@example.org');

    $this->click('_qf_Registration_upload-bottom');
    $this->waitForTextPresent("'Online Registration' information has been saved.");

    // verify event input on info page
    // start at Manage Events listing
    $this->openCiviPage('event/manage', 'reset=1');
    $this->click("link=$eventTitle");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $eventInfoUrl = $this->getLocation();

    $permissions = array("edit-1-register-for-events");
    $this->changePermissions($permissions);
    $this->webtestLogout();
    $this->open($eventInfoUrl);
    $this->click('link=Register Now');
    $this->waitForElementPresent('_qf_Register_upload-bottom');

    $this->type("xpath=//input[@class='four crm-form-text required']", "1");
    $this->click("xpath=//input[@class='crm-form-radio']");
    $this->click("xpath=//input[@class='crm-form-checkbox']");
    $this->type("first_name", "Jane");
    $lastName = "Smith" . substr(sha1(rand()), 0, 7);
    $this->type("last_name", $lastName);
    $this->type('email-Primary', $email);

    $this->waitForElementPresent('credit_card_type');
    $this->select('credit_card_type', 'value=Visa');
    $this->type('credit_card_number', '4111111111111111');
    $this->type('cvv2', '000');
    $this->select('credit_card_exp_date[M]', 'value=1');
    $this->select('credit_card_exp_date[Y]', 'value=2020');
    $this->type('billing_first_name', 'Jane');
    $this->type('billing_last_name', $lastName);
    $this->type('billing_street_address-5', '15 Main St.');
    $this->type(' billing_city-5', 'San Jose');
    $this->select('billing_country_id-5', 'value=1228');
    $this->select('billing_state_province_id-5', 'value=1004');
    $this->type('billing_postal_code-5', '94129');

    $this->clickLink('_qf_Register_upload-bottom', '_qf_Confirm_next-bottom');
    $confirmStrings = array('Event Fee(s)', 'Billing Name and Address', 'Credit Card Information');
    $this->assertStringsPresent($confirmStrings);
    $this->click('_qf_Confirm_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $thankStrings = array('Thank You for Registering', 'Event Total', 'Transaction Date');
    $this->assertStringsPresent($thankStrings);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Participant
    $this->openCiviPage('event/search', 'reset=1', '_qf_Search_refresh');

    $this->type('sort_name', "$email");
    $this->clickLink('_qf_Search_refresh', "xpath=id('participantSearch')/table/tbody/tr/td[11]/span/a[text()='View']");
    $this->click("xpath=id('participantSearch')/table/tbody/tr/td[11]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ParticipantView_cancel-bottom');

    $expected = array(
      2 => 'Full Conference',
      3 => 'Pre-conference Meetup? - Yes',
      4 => 'Evening Sessions - First Night',
    );
    foreach ($expected as $value => $label) {
      $this->verifyText("xpath=id('ParticipantView')/div[2]/table[1]/tbody/tr[8]/td[2]/table/tbody/tr[$value]/td", $label);
    }
    // Fixme: We can't asset full string like - "Event Total: $ 590.00" as it has special char
    $this->assertStringsPresent(' 590.00');
    $this->click('_qf_ParticipantView_cancel-bottom');
  }

  public function testParticipantWithDateSpecificPriceSet() {

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor = 'Event';
    $setHelp = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp);

    // Get the price set id ($sid) by retrieving and parsing the URL of the New Price Field form
    // which is where we are after adding Price Set.
    $sid = $this->urlArg('sid');
    $this->assertType('numeric', $sid);

    $validStrings = array();
    $fields = array(
      'Full Conference' => 'Text',
      'Pre-conference Meetup?' => 'Radio',
      'Evening Sessions' => 'CheckBox',
    );
    $this->_testAddPriceFields($fields, $validateStrings, TRUE);

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

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
    $this->select2('payment_processor', $processorName, TRUE);
    $this->select('financial_type_id', 'label=Event Fee');
    $this->select('price_set_id', 'label=' . $setTitle);

    $this->clickLink('_qf_Fee_upload-bottom', 'link=Online Registration', FALSE);

    // intro text for registration page
    $registerIntro = 'Fill in all the fields below and click Continue.';

    // Go to Online Registration tab
    $this->click('link=Online Registration');
    $this->waitForElementPresent('_qf_Registration_upload-bottom');

    $this->check('is_online_registration');
    $this->assertChecked('is_online_registration');

    $this->fillRichTextField('intro_text', $registerIntro, 'CKEditor', TRUE);

    // enable confirmation email
    $this->click('CIVICRM_QFID_1_is_email_confirm');
    $this->type('confirm_from_name', 'Jane Doe');
    $this->type('confirm_from_email', 'jane.doe@example.org');

    $this->click('_qf_Registration_upload-bottom');
    $this->waitForTextPresent("'Online Registration' information has been saved.");

    // verify event input on info page
    // start at Manage Events listing
    $this->openCiviPage('event/manage', 'reset=1');
    $this->click("link=$eventTitle");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    // Adding contact with randomized first name (so we can then select that contact when creating event registration)
    // We're using Quick Add block on the main page for this.
    $firstName = substr(sha1(rand()), 0, 7);
    $lastName = 'Anderson' . substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, $lastName, TRUE);
    $contactName = "$lastName, $firstName";
    $displayName = "$firstName $lastName";

    $this->openCiviPage('participant/add', 'reset=1&action=add&context=standalone', '_qf_Participant_upload-bottom');

    // Type contact last name in contact auto-complete, wait for dropdown and click first result
    $this->webtestFillAutocomplete($firstName);

    // Select event. Based on label for now.
    $this->select2('event_id', "$eventTitle");
    // Select role
    $this->multiselect2('role_id', array('Volunteer'));

    $this->waitForElementPresent("xpath=//input[@class='crm-form-radio']");
    $this->click("xpath=//input[@class='crm-form-radio']");
    $this->click("xpath=//input[@class='crm-form-checkbox']");

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
    $this->waitForText("crm-notification-container", "Event registration for $displayName has been added", "Status message didn't show up after saving!");

    $this->waitForElementPresent("xpath=//form[@class='CRM_Event_Form_Search crm-search-form']/table//tbody/tr[1]/td[8]/span/a[text()='View']");

    //click through to the participant view screen
    $this->click("xpath=//form[@class='CRM_Event_Form_Search crm-search-form']/table/tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent("xpath=//button//span[contains(text(),'Done')]");
  }

  /**
   * Test to regiter participant for event with
   * multiple price fields in price-set
   * CRM-11986

   */
  public function testEventWithPriceSet() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Adding contact with randomized first name (so we can then select that contact when creating event registration)
    // We're using Quick Add block on the main page for this.
    $firstName = substr(sha1(rand()), 0, 7);
    $lastName = 'Anderson' . substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, $lastName, TRUE);
    $contactName = "$lastName, $firstName";
    $displayName = "$firstName $lastName";

    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor = 'Event';
    $setHelp = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp);

    // Get the price set id ($sid) by retrieving and parsing the URL of the New Price Field form
    // which is where we are after adding Price Set.
    $sid = $this->urlArg('sid');
    $this->assertType('numeric', $sid);

    $validStrings = array();
    $fields = array(
      'Full Conference' => 'Text',
      'Pre-conference Meetup?' => 'Radio',
      'Evening Sessions' => 'CheckBox',
    );
    $this->_testAddPriceFields($fields, $validateStrings);

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);

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
    $this->select('financial_type_id', 'label=Event Fee');
    $this->select('price_set_id', 'label=' . $setTitle);

    $this->click('_qf_Fee_upload-bottom');
    $this->waitForText("crm-notification-container", "'Fees' information has been saved.");
    $this->waitForAjaxContent();

    $this->openCiviPage('participant/add', 'reset=1&action=add&context=standalone', '_qf_Participant_upload-bottom');

    // Type contact last name in contact auto-complete, wait for dropdown and click first result
    $this->webtestFillAutocomplete($firstName);
    $this->select2('event_id', $eventTitle);
    // Select role
    $this->multiselect2('role_id', array('Volunteer'));

    // Choose Registration Date.
    // Using helper webtestFillDate function.
    $this->webtestFillDate('register_date', 'now');
    $today = date('F jS, Y', strtotime('now'));
    // May 5th, 2010

    // Select participant status
    $this->select('status_id', 'value=1');

    // Setting registration source
    $this->type('source', 'Event StandaloneAddTest Webtest');

    // Select an event fee
    $this->waitForElementPresent("xpath=//div[@id='priceset']/div[2]/div[2]/input");
    $this->type("xpath=//div[@id='priceset']/div[2]/div[2]/input", '5');
    $this->fireEvent("xpath=//div[@id='priceset']/div[2]/div[2]/input", 'blur');
    $this->waitForElementPresent("xpath=//div[@id='priceset']/div[3]/div[2]/div[1]/span/input");
    $this->click("xpath=//div[@id='priceset']/div[3]/div[2]/div[1]/span/input");
    $this->click("xpath=//div[@id='priceset']/div[4]/div[2]/div[1]/span/input");
    $this->click("xpath=//div[@id='priceset']/div[4]/div[2]/div[2]/span/input");

    // Select payment method = Check and enter chk number
    $this->select('payment_instrument_id', 'value=4');
    $this->waitForElementPresent('check_number');
    $this->type('check_number', '1044');

    // Clicking save.
    $this->click('_qf_Participant_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->waitForText("crm-notification-container", "Event registration for $displayName has been added");

    $this->waitForElementPresent("xpath=//form[@id='Search']/table/tbody/tr[1]/td[8]/span/a[text()='View']");
    //click through to the participant view screen
    $this->click("xpath=//form[@id='Search']/table/tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ParticipantView_cancel-bottom');

    $this->webtestVerifyTabularData(
      array(
        'Event' => $eventTitle,
        'Participant Role' => 'Attendee',
        'Status' => 'Registered',
        'Event Source' => 'Event StandaloneAddTest Webtest',
      )
    );
    $this->waitForElementPresent("xpath=//table/tbody/tr/td[text()='Fees']/following-sibling::td");
    $this->verifyText("xpath=//table/tbody/tr/td[text()='Fees']/following-sibling::td/table/tbody/tr[2]/td", preg_quote('$ 2,705.00'));
    $expectedLineItems = array(
      2 => array(
        1 => 'Full Conference ',
        2 => '5',
        3 => '$ 525.00',
        4 => '$ 2,625.00',
      ),
      3 => array(
        2 => '1',
        3 => '$ 50.00',
        4 => '$ 50.00',
      ),
      4 => array(
        1 => 'Evening Sessions - First Night ',
        2 => '1',
        3 => '$ 15.00',
        4 => '$ 15.00',
      ),
      5 => array(
        1 => 'Evening Sessions - Second Night ',
        2 => '1',
        3 => '$ 15.00',
        4 => '$ 15.00',
      ),
    );
    $this->_checkLineItems($expectedLineItems);
    // check contribution record as well
    // click through to the contribution view screen
    $this->click("xpath=//*[@id='ParticipantView']/div[2]/table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ContributionView_cancel-bottom');

    $this->webtestVerifyTabularData(
      array(
        'From' => $displayName,
        'Financial Type' => 'Event Fee',
        'Contribution Status' => 'Completed',
        'Payment Method' => 'Check',
        'Check Number' => '1044',
        'Received Into' => 'Deposit Bank Account',
      )
    );
    $this->verifyText("xpath=//td[text()='Contribution Amount']/following-sibling::td//div/div", preg_quote('Contribution Total: $ 2,705.00'));

    // CRM-17182 - Rename the price option and check the same in Find Participants
    $this->openCiviPage('admin/price/field', "reset=1&action=browse&sid={$sid}", 'newPriceField');
    $this->click("xpath=//table[@id='options']/tbody/tr[3]/td[8]/a[text()='Edit Price Options']");
    $this->waitForElementPresent("xpath=//span[contains(text(), 'Done')]");
    $this->click("xpath=//tr/td/div[text()='First Night']/../following-sibling::td[8]/span/a[text()='Edit Option']");
    $this->waitForElementPresent("_qf_Option_cancel");
    $this->type('label', 'First Night Edited');
    $this->click('_qf_Option_next');
    $this->waitForText('crm-notification-container', "The option 'First Night Edited' has been saved.");

    $this->openCiviPage('event/search', "reset=1", '_qf_Search_refresh');
    $this->select2('participant_fee_id', 'First Night Edited');
    $this->clickLink('_qf_Search_refresh');
    $this->clickLink("xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", "xpath=//span[contains(text(), 'Done')]", FALSE);
    $expectedLineItems[4][1] = 'Evening Sessions - First Night Edited';
    $this->_checkLineItems($expectedLineItems);
  }


  public function testDeletePriceSetforEventTemplate() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    // Log in using webtestLogin() method
    $this->webtestLogin();

    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor = 'Event';
    $setHelp = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp);

    // Get the price set id ($sid) by retrieving and parsing the URL of the New Price Field form
    // which is where we are after adding Price Set.
    $sid = $this->urlArg('sid');
    $this->assertType('numeric', $sid);

    $validStrings = array();
    $fields = array(
      'Test Field' => 'Text',
    );
    $this->_testAddPriceFields($fields, $validateStrings);

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);
    $this->openCiviPage('admin/eventTemplate', 'reset=1');
    $this->clickLink('newEventTemplate');
    $this->select("template_id", "value=6");
    // Wait for event type to be filled in (since page reloads)
    $this->waitForElementPresent("template_id");
    // Enter Event Title, Summary and Description
    $this->select("event_type_id", "value=4");
    $this->select("default_role_id", "value=1");
    $this->waitForAjaxContent();
    $this->type("title", "Test Event");
    $this->type("summary", "This is a great conference. Sign up now!");

    $this->click("_qf_EventInfo_upload-bottom");
    $this->waitForElementPresent('link=Fees');
    // Go to Fees tab
    $this->click('link=Fees');
    $this->waitForElementPresent('_qf_Fee_upload-bottom');
    $this->click('CIVICRM_QFID_1_is_monetary');
    $this->select('financial_type_id', 'label=Event Fee');
    $this->select('price_set_id', 'label=' . $setTitle);
    $templateId = $this->urlArg('id');
    $this->click('_qf_Fee_upload-bottom');

    //check the delete for price field
    $this->openCiviPage("admin/price/field", "reset=1&action=browse&sid={$sid}");
    $this->waitForElementPresent("xpath=//table[@id='options']/tbody/tr/td[9]/span[2]");
    $this->click("xpath=//table[@id='options']/tbody/tr/td[9]/span[2]/ul[@class='panel']/li[2]//a[text()='Delete']");
    //assert the message
    $this->waitForText('price_set_used_by', "it is currently in use by one or more active events or contribution pages or contributions or event templates. If you no longer want to use this price set, click the event title below, and modify the fees for that event.");

    //check the delete for priceset
    $this->openCiviPage("admin/price", "reset=1");
    $this->waitForElementPresent("xpath=//table[@class='display crm-price-set-listing dataTable no-footer']/tbody/tr/td[4]/span[2]");
    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[4]/span[2]/ul/li[3]//a[text()='Delete']");
    // Check confirmation alert.
    $this->assertTrue((bool) preg_match("/^Are you sure you want to delete this price set?/",
      $this->getConfirmation()
    ));
    $this->chooseOkOnNextConfirmation();
    $this->waitForPageToLoad($this->getTimeoutMsec());
    //assert the message
    $this->waitForText('price_set_used_by',
      "it is currently in use by one or more active events or contribution pages or contributions or event templates.");
  }

  /**
   * @param array $expectedLineItems
   */
  public function _checkLineItems($expectedLineItems) {
    foreach ($expectedLineItems as $lineKey => $lineValue) {
      foreach ($lineValue as $key => $value) {
        $this->verifyText("xpath=//table/tbody//tr/td[text()='Selections']/following-sibling::td/table/tbody//tr[$lineKey]/td[$key]", preg_quote($value));
      }
    }
  }

}

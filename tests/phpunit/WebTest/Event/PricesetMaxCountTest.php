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
 * Class WebTest_Event_PricesetMaxCountTest
 */
class WebTest_Event_PricesetMaxCountTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testWithoutFieldCount() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    // create priceset
    $priceset = 'Price - ' . substr(sha1(rand()), 0, 7);
    $financialType = 'Donation';
    $this->_testAddSet($priceset, $financialType);

    // create price fields
    $fields = array(
      'Full Conference' => array(
        'type' => 'Text',
        'amount' => '525.00',
        'max_count' => 2,
        'is_required' => TRUE,
        'financial_type_id' => 1,
      ),
      'Meal Choice' => array(
        'type' => 'Select',
        'options' => array(
          1 => array(
            'label' => 'Chicken',
            'amount' => '525.00',
            'max_count' => 1,
            'financial_type_id' => 1,
          ),
          2 => array(
            'label' => 'Vegetarian',
            'amount' => '200.00',
            'max_count' => 5,
            'financial_type_id' => 1,
          ),
        ),
      ),
      'Pre-conference Meetup?' => array(
        'type' => 'Radio',
        'options' => array(
          1 => array(
            'label' => 'Yes',
            'amount' => '50.00',
            'max_count' => 1,
            'financial_type_id' => 1,
          ),
          2 => array(
            'label' => 'No',
            'amount' => '10',
            'max_count' => 5,
            'financial_type_id' => 1,
          ),
        ),
      ),
      'Evening Sessions' => array(
        'type' => 'CheckBox',
        'options' => array(
          1 => array(
            'label' => 'First Five',
            'amount' => '100.00',
            'max_count' => 2,
            'financial_type_id' => 1,
          ),
          2 => array(
            'label' => 'Second Four',
            'amount' => '50.00',
            'max_count' => 4,
            'financial_type_id' => 1,
          ),
        ),
      ),
    );

    // add price fields
    $this->_testAddPriceFields($fields);

    // get price set url.
    $pricesetLoc = $this->getLocation();

    // get text field Id.
    $this->waitForElementPresent("xpath=//div[@id='crm-main-content-wrapper']/div/a[1]");
    $textFieldIdURL = $this->getAttribute("xpath=//div[@id='field_page']/table/tbody/tr[1]/td[9]/span[1]/a[2]@href");
    $textFieldId = $this->urlArg('fid', $textFieldIdURL);

    $this->open($pricesetLoc);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // get select field id
    $this->click("xpath=//div[@id='field_page']//table/tbody/tr[2]/td[8]/a");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button[2]/span[2]");
    $selectFieldLoc = $this->getLocation();
    $selectFieldURL = $this->getAttribute("xpath=//div[@id='field_page']//table/tbody/tr[2]/td[9]/span[1]/a[2]@href");
    $selectFieldId = $this->urlArg('fid', $selectFieldURL);

    // get select field ids
    // get select field option1
    $selectFieldOp1URL = $this->getAttribute("xpath=//div[@id='field_page']//table/tbody/tr[1]/td/span/a[text()='Edit Option']@href");
    $selectFieldOp1 = $this->urlArg('oid', $selectFieldOp1URL);

    // get select field option2
    $selectFieldOp2URL = $this->getAttribute("xpath=//div[@id='field_page']//table/tbody/tr[2]/td/span/a[text()='Edit Option']@href");
    $selectFieldOp2 = $this->urlArg('oid', $selectFieldOp2URL);

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

    // Register Participant 1
    // visit event info page
    $this->open($infoEvent);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // register for event
    $this->click('link=Register Now');
    $this->waitForElementPresent('_qf_Register_upload-bottom');

    // exceed maximun count for text field, check for form rule
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '3');

    $this->select("price_{$selectFieldId}", "value={$selectFieldOp1}");

    $this->type('first_name', 'Mary');
    $this->type('last_name', 'Jones' . substr(sha1(rand()), 0, 5));
    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $this->type('email-Primary', $email);

    // fill billing related info
    $this->_fillRegisterWithBillingInfo();

    $this->assertStringsPresent(array('Sorry, currently only 2 spaces are available for this option.'));

    // fill correct value for text field
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '1');

    $this->click('_qf_Register_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->_checkConfirmationAndRegister();

    // Register Participant 2
    // visit event info page
    $this->open($infoEvent);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click('link=Register Now');
    $this->waitForElementPresent('_qf_Register_upload-bottom');

    // exceed maximun count for text field, check for form rule
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '2');
    $this->type('first_name', 'Mary');
    $this->type('last_name', 'Jane' . substr(sha1(rand()), 0, 5));
    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $this->type('email-Primary', $email);

    // fill billing related info and register
    $this->_fillRegisterWithBillingInfo();

    $this->assertStringsPresent(array('Sorry, currently only a single space is available for this option.'));

    // fill correct value for test field
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '1');

    // select sold option for select field, check for form rule
    $this->assertElementContainsText("xpath=//select[@id='price_{$selectFieldId}']//option[@value='crm_disabled_opt-{$selectFieldOp1}']", "(Sold out)");

    // fill correct available option for select field
    $this->select("price_{$selectFieldId}", "value={$selectFieldOp2}");

    $this->click("css=input[data-amount=10]");
    $this->click('_qf_Register_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->_checkConfirmationAndRegister();
  }

  public function testWithFieldCount() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    // create priceset
    $priceset = 'Price - ' . substr(sha1(rand()), 0, 7);
    $financialType = 'Donation';
    $this->_testAddSet($priceset, $financialType);

    // create price fields
    $fields = array(
      'Full Conference' => array(
        'type' => 'Text',
        'amount' => '525.00',
        'max_count' => 4,
        'count' => 2,
        'is_required' => TRUE,
        'financial_type_id' => 1,
      ),
      'Meal Choice' => array(
        'type' => 'Select',
        'options' => array(
          1 => array(
            'label' => 'Chicken',
            'amount' => '525.00',
            'max_count' => 2,
            'count' => 2,
            'financial_type_id' => 1,
          ),
          2 => array(
            'label' => 'Vegetarian',
            'amount' => '200.00',
            'max_count' => 10,
            'count' => 5,
            'financial_type_id' => 1,
          ),
        ),
      ),
      'Pre-conference Meetup?' => array(
        'type' => 'Radio',
        'options' => array(
          1 => array(
            'label' => 'Yes',
            'amount' => '50.00',
            'max_count' => 2,
            'count' => 1,
            'financial_type_id' => 1,
          ),
          2 => array(
            'label' => 'No',
            'amount' => '10',
            'max_count' => 10,
            'count' => 5,
            'financial_type_id' => 1,
          ),
        ),
      ),
      'Evening Sessions' => array(
        'type' => 'CheckBox',
        'options' => array(
          1 => array(
            'label' => 'First Five',
            'amount' => '100.00',
            'max_count' => 4,
            'count' => 2,
            'financial_type_id' => 1,
          ),
          2 => array(
            'label' => 'Second Four',
            'amount' => '50.00',
            'max_count' => 8,
            'count' => 4,
            'financial_type_id' => 1,
          ),
        ),
      ),
    );

    // add price fields
    $this->_testAddPriceFields($fields);

    // get price set url.
    $pricesetLoc = $this->getLocation();

    // get text field Id.
    $this->waitForElementPresent("xpath=//div[@id='crm-main-content-wrapper']/div/a[1]");
    $textFieldIdURL = $this->getAttribute("xpath=//div[@id='field_page']//table/tbody/tr[1]/td[9]/span[1]/a[2]@href");
    $textFieldId = $this->urlArg('fid', $textFieldIdURL);

    $this->open($pricesetLoc);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // get select field id
    $this->click("xpath=//div[@id='field_page']//table/tbody/tr[2]/td[8]/a");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button[2]/span[2]");
    $selectFieldLoc = $this->getLocation();
    $selectFieldURL = $this->getAttribute("xpath=//div[@id='field_page']//table/tbody/tr[2]/td[9]/span[1]/a[2]@href");
    $selectFieldId = $this->urlArg('fid', $selectFieldURL);

    // get select field ids
    // get select field option1
    $selectFieldOp1URL = $this->getAttribute("xpath=//div[@id='field_page']//table/tbody/tr[1]/td/span/a[text()='Edit Option']@href");
    $selectFieldOp1 = $this->urlArg('oid', $selectFieldOp1URL);

    // get select field option2
    $selectFieldOp2URL = $this->getAttribute("xpath=//div[@id='field_page']//table/tbody/tr[2]/td/span/a[text()='Edit Option']@href");
    $selectFieldOp2 = $this->urlArg('oid', $selectFieldOp2URL);

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

    // Register Participant 1
    // visit event info page
    $this->open($infoEvent);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // register for event
    $this->click('link=Register Now');
    $this->waitForElementPresent('_qf_Register_upload-bottom');

    // check for form rule
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '3');

    $this->type('first_name', 'Mary');
    $this->type('last_name', 'Jane' . substr(sha1(rand()), 0, 5));
    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $this->type('email-Primary', $email);

    // fill billing related info
    $this->_fillRegisterWithBillingInfo();

    $this->assertStringsPresent(array('Sorry, currently only 4 spaces are available for this option.'));

    $this->select("price_{$selectFieldId}", "value={$selectFieldOp1}");

    // fill correct value and register
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '1');

    $this->click('_qf_Register_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->_checkConfirmationAndRegister();

    // Register Participant 2
    // visit event info page
    $this->open($infoEvent);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click('link=Register Now');
    $this->waitForElementPresent('_qf_Register_upload-bottom');

    // check for form rule
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '2');
    $this->type('first_name', 'Mary');
    $this->type('last_name', 'Jane' . substr(sha1(rand()), 0, 5));
    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $this->type('email-Primary', $email);

    // fill billing related info and register
    $this->_fillRegisterWithBillingInfo();

    $this->assertStringsPresent(array('Sorry, currently only 2 spaces are available for this option.'));

    // fill correct value and register
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '1');

    // check for sold option for select field
    $this->assertElementContainsText("xpath=//select[@id='price_{$selectFieldId}']//option[@value='crm_disabled_opt-{$selectFieldOp1}']", "(Sold out)");

    // check for sold option for select field
    $this->select("price_{$selectFieldId}", "value={$selectFieldOp2}");

    $this->click('_qf_Register_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->_checkConfirmationAndRegister();
  }

  public function testAdditionalParticipantWithoutFieldCount() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    // create priceset
    $priceset = 'Price - ' . substr(sha1(rand()), 0, 7);
    $financialType = 'Donation';
    $this->_testAddSet($priceset, $financialType);

    // create price fields
    $fields = array(
      'Full Conference' => array(
        'type' => 'Text',
        'amount' => '525.00',
        'max_count' => 6,
        'is_required' => TRUE,
        'financial_type_id' => 1,
      ),
      'Meal Choice' => array(
        'type' => 'Select',
        'options' => array(
          1 => array(
            'label' => 'Chicken',
            'amount' => '525.00',
            'max_count' => 3,
            'financial_type_id' => 1,
          ),
          2 => array(
            'label' => 'Vegetarian',
            'amount' => '200.00',
            'max_count' => 2,
            'financial_type_id' => 1,
          ),
        ),
      ),
      'Pre-conference Meetup?' => array(
        'type' => 'Radio',
        'options' => array(
          1 => array(
            'label' => 'Yes',
            'amount' => '50.00',
            'max_count' => 4,
            'financial_type_id' => 1,
          ),
          2 => array(
            'label' => 'No',
            'amount' => '10',
            'max_count' => 5,
            'financial_type_id' => 1,
          ),
        ),
      ),
      'Evening Sessions' => array(
        'type' => 'CheckBox',
        'options' => array(
          1 => array(
            'label' => 'First Five',
            'amount' => '100.00',
            'max_count' => 6,
            'financial_type_id' => 1,
          ),
          2 => array(
            'label' => 'Second Four',
            'amount' => '50.00',
            'max_count' => 4,
            'financial_type_id' => 1,
          ),
        ),
      ),
    );

    // add price fields
    $this->_testAddPriceFields($fields);

    // get price set url.
    $pricesetLoc = $this->getLocation();

    // get text field Id.
    $this->waitForElementPresent("xpath=//div[@id='crm-main-content-wrapper']/div/a[1]");
    $textFieldURL = $this->getAttribute("xpath=//div[@id='field_page']/table/tbody/tr[1]/td[9]/span[1]/a[2]@href");
    $textFieldId = $this->urlArg('fid', $textFieldURL);

    $this->open($pricesetLoc);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // get select field id
    $this->click("xpath=//div[@id='field_page']//table/tbody/tr[2]/td[8]/a");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button[2]/span[2]");
    $selectFieldLoc = $this->getLocation();
    $selectFieldURL = $this->getAttribute("xpath=//div[@id='field_page']//table/tbody/tr[2]/td[9]/span[1]/a[2]@href");
    $selectFieldId = $this->urlArg('fid', $selectFieldURL);

    // get select field ids
    // get select field option1
    $selectFieldOp1URL = $this->getAttribute("xpath=//div[@id='field_page']//table/tbody/tr[1]/td/span/a[text()='Edit Option']@href");
    $selectFieldOp1 = $this->urlArg('oid', $selectFieldOp1URL);

    // get select field option2
    $selectFieldOp2URL = $this->getAttribute("xpath=//div[@id='field_page']//table/tbody/tr[2]/td/span/a[text()='Edit Option']@href");
    $selectFieldOp2 = $this->urlArg('oid', $selectFieldOp2URL);

    // create event.
    $eventTitle = 'Meeting - ' . substr(sha1(rand()), 0, 7);
    $paramsEvent = array(
      'title' => $eventTitle,
      'template_id' => 6,
      'event_type_id' => 4,
      'payment_processor' => $processorName,
      'price_set' => $priceset,
      'is_multiple_registrations' => TRUE,
    );

    $infoEvent = $this->_testAddEvent($paramsEvent);

    // logout to register for event.
    $this->webtestLogout();

    // 1'st registration
    // Register Participant 1
    // visit event info page
    $this->open($infoEvent);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // register for event
    $this->click('link=Register Now');
    $this->waitForElementPresent('_qf_Register_upload-bottom');

    // select 3 participants ( including current )
    $this->select('additional_participants', 'value=2');

    // Check for Participant1
    // exceed maximun count for text field, check for form rule
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '7');

    $this->type('first_name', 'Mary');
    $this->type('last_name', 'Jane' . substr(sha1(rand()), 0, 5));
    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $this->type('email-Primary', $email);

    // fill billing related info
    $this->_fillRegisterWithBillingInfo();

    $this->assertStringsPresent(array('Sorry, currently only 6 spaces are available for this option.'));

    // fill correct value for text field
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '1');
    $this->select("price_{$selectFieldId}", "value={$selectFieldOp2}");

    $this->click('_qf_Register_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Check for Participant2
    // exceed maximun count for text field, check for form rule
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '6');

    $this->type('first_name', 'Mary Add 2');
    $this->type('last_name', 'Jane' . substr(sha1(rand()), 0, 5));
    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $this->type('email-Primary', $email);

    $this->click('_qf_Participant_1_next-Array');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertStringsPresent(array('Sorry, currently only 6 spaces are available for this option.'));

    // fill correct value for text field
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '3');
    $this->select("price_{$selectFieldId}", "value={$selectFieldOp2}");

    $this->click('_qf_Participant_1_next-Array');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Check for Participant3, check and skip
    // exceed maximun count for text field, check for form rule
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '3');

    $this->type('first_name', 'Mary Add 2');
    $this->type('last_name', 'Jane' . substr(sha1(rand()), 0, 5));
    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $this->type('email-Primary', $email);

    $this->click('_qf_Participant_2_next-Array');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertStringsPresent(array('Sorry, currently only 6 spaces are available for this option.'));

    // fill correct value for text field
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '1');

    // check for select
    $this->assertElementContainsText("xpath=//select[@id='price_{$selectFieldId}']//option[@value='crm_disabled_opt-{$selectFieldOp2}']", "(Sold out)");

    // Skip participant3 and register
    $this->click('_qf_Participant_2_next_skip-Array');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->_checkConfirmationAndRegister();

    // 2'st registration
    // Register Participant 1
    // visit event info page
    $this->open($infoEvent);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // register for event
    $this->click('link=Register Now');
    $this->waitForElementPresent('_qf_Register_upload-bottom');

    // select 2 participants ( including current )
    $this->select('additional_participants', 'value=1');

    // Check for Participant1
    // exceed maximun count for text field, check for form rule
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '3');

    $this->type('first_name', 'Mary');
    $this->type('last_name', 'Jane' . substr(sha1(rand()), 0, 5));
    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $this->type('email-Primary', $email);

    // fill billing related info
    $this->_fillRegisterWithBillingInfo();

    $this->assertStringsPresent(array('Sorry, currently only 2 spaces are available for this option.'));

    // fill correct value for text field
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '1');

    // check for select field
    $this->assertElementContainsText("xpath=//select[@id='price_{$selectFieldId}']//option[@value='crm_disabled_opt-{$selectFieldOp2}']", "(Sold out)");

    // fill available value for select
    $this->select("price_{$selectFieldId}", "value={$selectFieldOp1}");

    $this->click('_qf_Register_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Check for Participant2
    // exceed maximun count for text field, check for form rule
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '2');

    $this->type('first_name', 'Mary Add 1');
    $this->type('last_name', 'Jane' . substr(sha1(rand()), 0, 5));
    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $this->type('email-Primary', $email);

    $this->click('_qf_Participant_1_next-Array');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertStringsPresent(array('Sorry, currently only 2 spaces are available for this option.'));

    // fill correct value for text field
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '1');

    // check for select field
    $this->assertElementContainsText("xpath=//select[@id='price_{$selectFieldId}']//option[@value='crm_disabled_opt-{$selectFieldOp2}']", "(Sold out)");

    // fill available value for select
    $this->select("price_{$selectFieldId}", "value={$selectFieldOp1}");

    $this->click('_qf_Participant_1_next-Array');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->_checkConfirmationAndRegister();
  }

  public function testAdditionalParticipantWithFieldCount() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    // create priceset
    $priceset = 'Price - ' . substr(sha1(rand()), 0, 7);
    $financialType = 'Donation';
    $this->_testAddSet($priceset, $financialType);

    // create price fields
    $fields = array(
      'Full Conference' => array(
        'type' => 'Text',
        'amount' => '525.00',
        'count' => 2,
        'max_count' => 12,
        'is_required' => TRUE,
        'financial_type_id' => 1,
      ),
      'Meal Choice' => array(
        'type' => 'Select',
        'options' => array(
          1 => array(
            'label' => 'Chicken',
            'amount' => '525.00',
            'count' => 1,
            'max_count' => 3,
            'financial_type_id' => 1,
          ),
          2 => array(
            'label' => 'Vegetarian',
            'amount' => '200.00',
            'count' => 2,
            'max_count' => 4,
            'financial_type_id' => 1,
          ),
        ),
      ),
      'Pre-conference Meetup?' => array(
        'type' => 'Radio',
        'options' => array(
          1 => array(
            'label' => 'Yes',
            'amount' => '50.00',
            'count' => 2,
            'max_count' => 8,
            'financial_type_id' => 1,
          ),
          2 => array(
            'label' => 'No',
            'amount' => '10',
            'count' => 5,
            'max_count' => 25,
            'financial_type_id' => 1,
          ),
        ),
      ),
      'Evening Sessions' => array(
        'type' => 'CheckBox',
        'options' => array(
          1 => array(
            'label' => 'First Five',
            'amount' => '100.00',
            'count' => 2,
            'max_count' => 16,
            'financial_type_id' => 1,
          ),
          2 => array(
            'label' => 'Second Four',
            'amount' => '50.00',
            'count' => 1,
            'max_count' => 4,
            'financial_type_id' => 1,
          ),
        ),
      ),
    );

    // add price fields
    $this->_testAddPriceFields($fields);

    // get price set url.
    $pricesetLoc = $this->getLocation();

    // get text field Id.
    $this->waitForElementPresent("xpath=//div[@id='crm-main-content-wrapper']/div/a[1]");
    $textFieldIdURL = $this->getAttribute("xpath=//div[@id='field_page']//table/tbody/tr[1]/td[9]/span[1]/a[2]@href");
    $textFieldId = $this->urlArg('fid', $textFieldIdURL);

    $this->open($pricesetLoc);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // get select field id
    $this->click("xpath=//div[@id='field_page']//table/tbody/tr[2]/td[8]/a");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button[2]/span[2]");
    $selectFieldLoc = $this->getLocation();
    $selectFieldURL = $this->getAttribute("xpath=//div[@id='field_page']//table/tbody/tr[2]/td[9]/span[1]/a[2]@href");
    $selectFieldId = $this->urlArg('fid', $selectFieldURL);

    // get select field ids
    // get select field option1
    $selectFieldOp1URL = $this->getAttribute("xpath=//div[@id='field_page']//table/tbody/tr[1]/td/span/a[text()='Edit Option']@href");
    $selectFieldOp1 = $this->urlArg('oid', $selectFieldOp1URL);

    // get select field option2
    $selectFieldOp2URL = $this->getAttribute("xpath=//div[@id='field_page']//table/tbody/tr[2]/td/span/a[text()='Edit Option']@href");
    $selectFieldOp2 = $this->urlArg('oid', $selectFieldOp2URL);

    // create event.
    $eventTitle = 'Meeting - ' . substr(sha1(rand()), 0, 7);
    $paramsEvent = array(
      'title' => $eventTitle,
      'template_id' => 6,
      'event_type_id' => 4,
      'payment_processor' => $processorName,
      'price_set' => $priceset,
      'is_multiple_registrations' => TRUE,
    );

    $infoEvent = $this->_testAddEvent($paramsEvent);

    // logout to register for event.
    $this->webtestLogout();

    // 1'st registration
    // Register Participant 1
    // visit event info page
    $this->open($infoEvent);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // register for event
    $this->click('link=Register Now');
    $this->waitForElementPresent('_qf_Register_upload-bottom');

    // select 3 participants ( including current )
    $this->select('additional_participants', 'value=2');

    // Check for Participant1
    // exceed maximun count for text field, check for form rule
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '7');

    $this->type('first_name', 'Mary');
    $this->type('last_name', 'Jane' . substr(sha1(rand()), 0, 5));
    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $this->type('email-Primary', $email);

    // fill billing related info
    $this->_fillRegisterWithBillingInfo();

    $this->assertStringsPresent(array('Sorry, currently only 12 spaces are available for this option.'));

    // fill correct value for text field
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '1');
    $this->select("price_{$selectFieldId}", "value={$selectFieldOp2}");

    $this->click('_qf_Register_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Check for Participant2
    // exceed maximun count for text field, check for form rule
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '6');

    $this->type('first_name', 'Mary Add 1');
    $this->type('last_name', 'Jane' . substr(sha1(rand()), 0, 5));
    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $this->type('email-Primary', $email);

    $this->click('_qf_Participant_1_next-Array');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertStringsPresent(array('Sorry, currently only 12 spaces are available for this option.'));

    // fill correct value for text field
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '3');
    $this->select("price_{$selectFieldId}", "value={$selectFieldOp2}");

    $this->click('_qf_Participant_1_next-Array');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Check for Participant3, check and skip
    // exceed maximun count for text field, check for form rule
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '3');

    $this->type('first_name', 'Mary Add 2');
    $this->type('last_name', 'Jane' . substr(sha1(rand()), 0, 5));
    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $this->type('email-Primary', $email);

    $this->click('_qf_Participant_2_next-Array');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertStringsPresent(array('Sorry, currently only 12 spaces are available for this option.'));

    // fill correct value for text field
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '1');

    // check for select
    $this->assertElementContainsText("xpath=//select[@id='price_{$selectFieldId}']//option[@value='crm_disabled_opt-{$selectFieldOp2}']", "(Sold out)");

    // Skip participant3 and register
    $this->click('_qf_Participant_2_next_skip-Array');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->_checkConfirmationAndRegister();

    // 2'st registration
    // Register Participant 1
    // visit event info page
    $this->open($infoEvent);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // register for event
    $this->click('link=Register Now');
    $this->waitForElementPresent('_qf_Register_upload-bottom');

    // select 2 participants ( including current )
    $this->select('additional_participants', 'value=1');

    // Check for Participant1
    // exceed maximun count for text field, check for form rule
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '3');

    $this->type('first_name', 'Mary');
    $this->type('last_name', 'Jane' . substr(sha1(rand()), 0, 5));
    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $this->type('email-Primary', $email);

    // fill billing related info
    $this->_fillRegisterWithBillingInfo();

    $this->assertStringsPresent(array('Sorry, currently only 4 spaces are available for this option.'));

    // fill correct value for text field
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '1');

    // check for select field
    $this->assertElementContainsText("xpath=//select[@id='price_{$selectFieldId}']//option[@value='crm_disabled_opt-{$selectFieldOp2}']", "(Sold out)");

    // fill available value for select
    $this->select("price_{$selectFieldId}", "value={$selectFieldOp1}");

    $this->click('_qf_Register_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Check for Participant2
    // exceed maximun count for text field, check for form rule
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '2');

    $this->type('first_name', 'Mary Add 1');
    $this->type('last_name', 'Jane' . substr(sha1(rand()), 0, 5));
    $email = 'jane_' . substr(sha1(rand()), 0, 5) . '@example.org';
    $this->type('email-Primary', $email);

    $this->click('_qf_Participant_1_next-Array');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertStringsPresent(array('Sorry, currently only 4 spaces are available for this option.'));

    // fill correct value for text field
    $this->type("xpath=//input[@id='price_{$textFieldId}']", '1');

    // check for select field
    $this->assertElementContainsText("xpath=//select[@id='price_{$selectFieldId}']//option[@value='crm_disabled_opt-{$selectFieldOp2}']", "(Sold out)");

    // fill available value for select
    $this->select("price_{$selectFieldId}", "value={$selectFieldOp1}");

    $this->click('_qf_Participant_1_next-Array');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->_checkConfirmationAndRegister();
  }

  /**
   * @param $setTitle
   * @param null $financialType
   */
  public function _testAddSet($setTitle, $financialType = NULL) {
    $this->openCiviPage('admin/price', 'reset=1&action=add', '_qf_Set_next-bottom');

    // Enter Priceset fields (Title, Used For ...)
    $this->type('title', $setTitle);
    $this->check('extends_1');

    if ($financialType) {
      $this->select("css=select.crm-form-select", "label={$financialType}");
    }

    $this->click("xpath=//form[@id='Set']/div[3]/table/tbody/tr[4]/td[2]/select");
    $this->type('help_pre', 'This is test priceset.');

    $this->assertChecked('is_active', 'Verify that Is Active checkbox is set.');
    $this->clickLink('_qf_Set_next-bottom');
  }

  /**
   * @param $fields
   */
  public function _testAddPriceFields($fields) {
    $fieldCount = count($fields);
    $count = 1;
    $this->waitForElementPresent('label');
    foreach ($fields as $label => $field) {
      $this->waitForElementPresent('label');
      $this->type('label', $label);
      $this->select('html_type', "value={$field['type']}");

      if ($field['type'] == 'Text') {
        $this->type('price', $field['amount']);

        if (isset($field['count'])) {
          $this->waitForElementPresent('count');
          $this->type('count', $field['count']);
        }

        if (isset($field['count'])) {
          $this->waitForElementPresent('count');
          $this->type('count', $field['count']);
        }

        if (isset($field['max_count'])) {
          $this->waitForElementPresent('max_value');
          $this->type('max_value', $field['max_count']);
        }

        if (isset($field['financial_type_id'])) {
          $this->waitForElementPresent('financial_type_id');
          $this->select('financial_type_id', "value={$field['financial_type_id']}");
        }

      }
      else {
        $this->_testAddMultipleChoiceOptions($field['options'], $field['type']);
      }

      if (isset($field['is_required']) && $field['is_required']) {
        $this->check('is_required');
      }

      if ($count < $fieldCount) {
        $this->click('_qf_Field_next_new-bottom');
      }
      else {
        $this->click('_qf_Field_next-bottom');
      }
      $this->waitForAjaxContent();
      $this->waitForText('crm-notification-container', "Price Field '$label' has been saved.");

      $count++;
    }
  }

  /**
   * @param $options
   * @param $fieldType
   */
  public function _testAddMultipleChoiceOptions($options, $fieldType) {
    foreach ($options as $oIndex => $oValue) {
      $this->type("option_label_{$oIndex}", $oValue['label']);
      $this->type("option_amount_{$oIndex}", $oValue['amount']);

      if (isset($oValue['count'])) {
        $this->waitForElementPresent("option_count_{$oIndex}");
        $this->type("option_count_{$oIndex}", $oValue['count']);
      }

      if (isset($oValue['max_count'])) {
        $this->waitForElementPresent("option_max_value_{$oIndex}");
        $this->type("option_max_value_{$oIndex}", $oValue['max_count']);
      }

      if (!empty($oValue['financial_type_id'])) {
        $this->select("option_financial_type_id_{$oIndex}", "value={$oValue['financial_type_id']}");
      }

      $this->click('link=another choice');
    }

    // select first element as default
    if ($fieldType == 'CheckBox') {
      $this->click('default_checkbox_option[1]');
    }
    else {
      $this->click('CIVICRM_QFID_1_2');
    }
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
    $this->click('xpath=//form[@id="Fee"]//div/table/tbody//tr//td/label[contains(text(), "Yes")]');
    $processorName = $params['payment_processor'];
    $this->select2('payment_processor', $processorName, TRUE);
    $this->select('financial_type_id', 'value=4');

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

    $this->clickLink('_qf_Fee_upload-bottom', 'link=Online Registration', FALSE);

    // Go to Online Registration tab
    $this->click('link=Online Registration');
    $this->waitForElementPresent('_qf_Registration_upload-bottom');

    $this->check('is_online_registration');
    $this->assertChecked('is_online_registration');

    if (isset($params['is_multiple_registrations']) && $params['is_multiple_registrations']) {
      $this->click('is_multiple_registrations');
    }

    $this->fillRichTextField('intro_text', 'Fill in all the fields below and click Continue.', 'CKEditor', TRUE);

    // enable confirmation email
    $this->click('CIVICRM_QFID_1_is_email_confirm');
    $this->type('confirm_from_name', 'Jane Doe');
    $this->type('confirm_from_email', 'jane.doe@example.org');

    $this->click('_qf_Registration_upload-bottom');
    $this->waitForTextPresent("'Fees' information has been saved.");

    // verify event input on info page
    // start at Manage Events listing
    $this->openCiviPage('event/manage', 'reset=1');
    $this->click('link=' . $params['title']);

    $this->waitForPageToLoad($this->getTimeoutMsec());
    return $this->getLocation();
  }

  public function _fillRegisterWithBillingInfo() {
    $this->waitForElementPresent('credit_card_type');
    $this->select('credit_card_type', 'value=Visa');
    $this->type('credit_card_number', '4111111111111111');
    $this->type('cvv2', '000');
    $this->select('credit_card_exp_date[M]', 'value=1');
    $this->select('credit_card_exp_date[Y]', 'value=2020');
    $this->type('billing_first_name', 'Jane_' . substr(sha1(rand()), 0, 5));
    $this->type('billing_last_name', 'San_' . substr(sha1(rand()), 0, 5));
    $this->type('billing_street_address-5', '15 Main St.');
    $this->type(' billing_city-5', 'San Jose');
    $this->select('billing_country_id-5', 'value=1228');
    $this->select('billing_state_province_id-5', 'value=1004');
    $this->type('billing_postal_code-5', '94129');

    $this->click('_qf_Register_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  public function _checkConfirmationAndRegister() {
    $confirmStrings = array('Event Fee(s)', 'Billing Name and Address', 'Credit Card Information');
    $this->assertStringsPresent($confirmStrings);
    $this->waitForElementPresent("_qf_Confirm_next-bottom");
    $this->click('_qf_Confirm_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $thankStrings = array('Thank You for Registering', 'Event Total', 'Transaction Date');
    $this->assertStringsPresent($thankStrings);
  }

}

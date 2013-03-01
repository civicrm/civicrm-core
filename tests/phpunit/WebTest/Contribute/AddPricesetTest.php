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
class WebTest_Contribute_AddPricesetTest extends CiviSeleniumTestCase {

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

    //add financial type of account type expense
    
    $financialType = $this->_testAddFinancialType();

    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor  = 'Contribution';
    $setHelp  = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp, $financialType);

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
    $this->_testAddPriceFields( $fields, $validateStrings, $financialType );
    // var_dump($validateStrings);

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);
  }

  function _testAddSet($setTitle, $usedFor, $setHelp, $financialType = NULL) {
    $this->open($this->sboxPath . 'civicrm/admin/price?reset=1&action=add');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_Set_next-bottom');

    // Enter Priceset fields (Title, Used For ...)
    $this->type('title', $setTitle);
    if ($usedFor == 'Event') {
      $this->check('extends_1');
    }
    elseif ($usedFor == 'Contribution') {
      $this->check('extends_2');
    }

    if ($financialType) {
      $this->select("css=select.form-select", "label={$financialType}");
    }
    $this->type('help_pre', $setHelp);

    $this->assertChecked('is_active', 'Verify that Is Active checkbox is set.');
    $this->click('_qf_Set_next-bottom');

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_Field_next-bottom');
  }

  function _testAddPriceFields(&$fields, &$validateString, $financialType, $dateSpecificFields = FALSE) {
    $validateStrings[] = $financialType;
    foreach ($fields as $label => $type) {
      $validateStrings[] = $label;
      
      $this->type('label', $label);
      $this->select('html_type', "value={$type}");

      switch ($type) {
      case 'Text':
        $validateStrings[] = '525.00';
        $this->type('price', '525.00');
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
          ),
          2 => array(
            'label' => 'Vegetarian',
            'amount' => '25.00',
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
          ),
          2 => array(
            'label' => 'No',
            'amount' => '0',
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
          ),
          2 => array(
            'label' => 'Second Night',
            'amount' => '15.00',
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
      $this->select('financial_type_id', "label={$financialType}");
      $this->click('_qf_Field_next_new-bottom');
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->waitForElementPresent('_qf_Field_next-bottom');
    }
  }
  
  function _testAddFinancialType() {
    // Add new Financial Account
    $orgName = 'Alberta '.substr(sha1(rand()), 0, 7);
    $financialAccountTitle = 'Financial Account '.substr(sha1(rand()), 0, 4);
    $financialAccountDescription = "{$financialAccountTitle} Description";
    $accountingCode = 1033;
    $financialAccountType = 'Revenue';
    $taxDeductible = FALSE;
    $isActive = FALSE;
    $isTax = TRUE;
    $taxRate = 9;
    $isDefault = FALSE;
        
    //Add new organisation
    if($orgName) {
      $this->webtestAddOrganization($orgName);
    }
        
    $this->_testAddFinancialAccount($financialAccountTitle,
      $financialAccountDescription,
      $accountingCode,
      $orgName,
      $financialAccountType,
      $taxDeductible,
      $isActive,
      $isTax,
      $taxRate,
      $isDefault
    );
    $this->waitForElementPresent("xpath=//table/tbody//tr/td[1][text()='{$financialAccountTitle}']/../td[9]/span/a[text()='Edit']");
      
    //Add new Financial Type
    $financialType['name'] = 'FinancialType '.substr(sha1(rand()), 0, 4);
    $financialType['is_deductible'] = TRUE;
    $financialType['is_reserved'] = FALSE;
    $this->addeditFinancialType($financialType);

    $accountRelationship = "Income Account is";
    $expected[] = array('financial_account' => $financialAccountTitle, 
      'account_relationship' => $accountRelationship
    );

    $this->select('account_relationship', "label={$accountRelationship}");
    sleep(2);
    $this->select('financial_account_id', "label={$financialAccountTitle}");
    $this->click('_qf_FinancialTypeAccount_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $text = 'The financial type Account has been saved.';
    $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
    return $financialType['name'];
  }
  
  function _testVerifyPriceSet($validateStrings, $sid) {
    // verify Price Set at Preview page
    // start at Manage Price Sets listing
    $this->open($this->sboxPath . 'civicrm/admin/price?reset=1');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Use the price set id ($sid) to pick the correct row
    $this->click("css=tr#row_{$sid} a[title='View and Edit Price Fields']");
    
    $this->waitForPageToLoad($this->getTimeoutMsec());
    // Look for Register button
    $this->waitForElementPresent('Link=Add Price Field');
    // Check for expected price set field strings
    $this->assertStringsPresent($validateStrings);
  }

  function testContributeOfflineWithPriceSet() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //add financial type of account type expense
    $financialType = $this->_testAddFinancialType();
    
    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor  = 'Contribution';
    $setHelp  = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp, $financialType);
    
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
    $this->_testAddPriceFields($fields, $validateStrings, $financialType);

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);
    $this->open($this->sboxPath . 'civicrm/contribute/add?reset=1&action=add&context=standalone');

    // As mentioned before, waitForPageToLoad is not always reliable. Below, we're waiting for the submit
    // button at the end of this page to show up, to make sure it's fully loaded.
    $this->waitForElementPresent('_qf_Contribution_upload');
    
    // Let's start filling the form with values.
    
    // create new contact using dialog
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestNewDialogContact($firstName, 'Contributor', $firstName . '@example.com');

    // select financial type
    $this->select('financial_type_id', "label={$financialType}");

    // fill in Received Date
    $this->webtestFillDate('receive_date');

    // source
    $this->type('source', 'Mailer 1');

    // select price set items
    $this->select('price_set_id', "label=$setTitle");
    $this->type("xpath=//input[@class='form-text four required']", "1");
    $this->click("xpath=//input[@class='form-radio']");
    $this->click("xpath=//input[@class='form-checkbox']");
    // select payment instrument type = Check and enter chk number
    $this->select('payment_instrument_id', 'value=4');
    $this->waitForElementPresent('check_number');
    $this->type('check_number', 'check #1041');

    $this->type('trxn_id', 'P20901X1' . rand(100, 10000));

    //Additional Detail section
    $this->click('AdditionalDetail');
    $this->waitForElementPresent('thankyou_date');

    $this->type('note', 'This is a test note.');
    $this->type('non_deductible_amount', '10');
    $this->type('fee_amount', '0');
    $this->type('net_amount', '0');
    $this->type('invoice_id', time());
    $this->webtestFillDate('thankyou_date');

    // Clicking save.
    $this->click('_qf_Contribution_upload');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertTrue($this->isTextPresent('The contribution record has been saved.'), "Status message didn't show up after saving!");

    $this->waitForElementPresent("xpath=//div[@id='Contributions']//table//tbody/tr[1]/td[8]/span/a[text()='View']");

    //click through to the Membership view screen
    $this->click("xpath=//div[@id='Contributions']//table/tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ContributionView_cancel-bottom');
    $expected = array(
      2 => $financialType,
      3 => '590.00',
      9 => 'Completed',
      10 => 'Check',
      11 => 'check #1041',
    );
    foreach ($expected as $label => $value) {
      $this->verifyText("xpath=id('ContributionView')/div[2]/table[1]/tbody/tr[$label]/td[2]", preg_quote($value));
    }

    $exp = array(
      2 => '$ 525.00',
      3 => '$ 50.00',
      4 => '$ 15.00',
    );

    foreach ($exp as $lab => $val) {
      $this->verifyText("xpath=id('ContributionView')/div[2]/table[1]/tbody/tr[3]/td[2]/table/tbody/tr[$lab]/td[3]",
        preg_quote($val)
      );
    }
  }

  function _testVerifyRegisterPage($contributionPageTitle) {
    $this->open($this->sboxPath . 'civicrm/admin/contribute?reset=1');
    $this->waitForElementPresent('_qf_SearchContribution_refresh');
    $this->type('title', $contributionPageTitle);
    $this->click('_qf_SearchContribution_refresh');
    $this->waitForPageToLoad('50000');
    $id = $this->getAttribute("//div[@id='configure_contribution_page']//div[@class='dataTables_wrapper']/table/tbody/tr@id");
    $id = explode('_', $id);
    $registerUrl = "civicrm/contribute/transact?reset=1&id=$id[1]";
    return $registerUrl;
  }

  function testContributeOnlineWithPriceSet() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();

    //add financial type of account type expense
    $financialType = $this->_testAddFinancialType();
    
    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor  = 'Contribution';
    $setHelp  = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp, $financialType);

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
    
    $this->_testAddPriceFields($fields, $validateStrings, $financialType);
      
    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);

    // We need a payment processor
    $processorName = 'Webtest Dummy' . substr(sha1(rand()), 0, 7);
    $this->webtestAddPaymentProcessor($processorName);

    $this->open($this->sboxPath . 'civicrm/admin/contribute/add?reset=1&action=add');

    $contributionTitle = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);

    // fill in step 1 (Title and Settings)
    $contributionPageTitle = "Title $contributionTitle";
    $this->type('title', $contributionPageTitle);
    $this->fillRichTextField('intro_text', 'This is Test Introductory Message', 'CKEditor');
    $this->fillRichTextField('footer_text', 'This is Test Footer Message', 'CKEditor');

    $this->select('financial_type_id', "label={$financialType}");
    // go to step 2
    $this->click('_qf_Settings_next');
    $this->waitForElementPresent('_qf_Amount_next-bottom');

    //this contribution page for online contribution
    //$this->select( 'payment_processor_id', 'label=' . $processorName );
    $this->click("xpath=//tr[@class='crm-contribution-contributionpage-amount-form-block-payment_processor']/td/label[text()='$processorName']");
    $this->select('price_set_id', 'label=' . $setTitle);
    $this->click('_qf_Amount_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //get Url for Live Contribution Page
    $registerUrl = $this->_testVerifyRegisterPage($contributionPageTitle);

    //logout
    $this->open($this->sboxPath . 'civicrm/logout?reset=1');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Open Live Contribution Page
    $this->open($this->sboxPath . $registerUrl);
    $this->waitForElementPresent('_qf_Main_upload-bottom');

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $this->waitForElementPresent('_qf_Main_upload-bottom');
    $this->type('email-5', $firstName . '@example.com');
    $this->type('billing_first_name', $firstName);
    $this->type('billing_last_name', $lastName);
    $this->type("xpath=//input[@class='form-text four required']", "1");
    $this->click("xpath=//input[@class='form-radio']");
    $this->click("xpath=//input[@class='form-checkbox']");

    $streetAddress = '100 Main Street';
    $this->type('billing_street_address-5', $streetAddress);
    $this->type('billing_city-5', 'San Francisco');
    $this->type('billing_postal_code-5', '94117');
    $this->select('billing_country_id-5', 'value=1228');
    $this->select('billing_state_province_id-5', 'value=1001');

    //Credit Card Info
    $this->select('credit_card_type', 'value=Visa');
    $this->type('credit_card_number', '4111111111111111');
    $this->type('cvv2', '000');
    $this->select('credit_card_exp_date[M]', 'value=1');
    $this->select('credit_card_exp_date[Y]', 'value=2020');

    //Billing Info
    $this->type('billing_first_name', $firstName . 'billing');
    $this->type('billing_last_name', $lastName . 'billing');
    $this->type('billing_street_address-5', '15 Main St.');
    $this->type('billing_city-5', 'San Jose');
    $this->select('billing_country_id-5', 'value=1228');
    $this->select('billing_state_province_id-5', 'value=1004');
    $this->type('billing_postal_code-5', '94129');
    $this->click('_qf_Main_upload-bottom');

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_Confirm_next-bottom');

    $this->click('_qf_Confirm_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //login to check contribution
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Contribution
    $this->open($this->sboxPath . 'civicrm/contribute/search?reset=1');

    $this->waitForElementPresent('contribution_date_low');

    $this->type('sort_name', "$firstName $lastName");
    $this->click('_qf_Search_refresh');

    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->click("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");

    //View Contribution Record
    $expected = array(
      2 => $financialType,
      5 => '$ 590.00',
      8 => 'Completed',
    );
    foreach($expected as $label => $value) {
      $this->verifyText("xpath=//form[@id='ContributionView']/div[2]/table/tbody/tr[$label]/td[2]", preg_quote($value));
    }
  }

  function testContributeWithDateSpecificPriceSet() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();

    //add financial type of account type expense
    $financialType= $this->_testAddFinancialType();
    
    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor  = 'Contribution';
    $setHelp  = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp, $financialType);

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
    $this->_testAddPriceFields($fields, $validateStrings, $financialType, TRUE);

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);

    // We need a payment processor
    $processorName = 'Webtest Dummy' . substr(sha1(rand()), 0, 7);
    $this->webtestAddPaymentProcessor($processorName);

    $this->open($this->sboxPath . 'civicrm/admin/contribute/add?reset=1&action=add');

    $contributionTitle = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);

    // fill in step 1 (Title and Settings)
    $contributionPageTitle = "Title $contributionTitle";
    $this->type('title', $contributionPageTitle);
    $this->select('financial_type_id', "label={$financialType}");
    $this->fillRichTextField('intro_text', 'This is Test Introductory Message', 'CKEditor');
    $this->fillRichTextField('footer_text', 'This is Test Footer Message', 'CKEditor');

    // go to step 2
    $this->click('_qf_Settings_next');
    $this->waitForElementPresent('_qf_Amount_next-bottom');

    //this contribution page for online contribution
    //$this->select( 'payment_processor_id', 'label=' . $processorName );
    $this->click("xpath=//tr[@class='crm-contribution-contributionpage-amount-form-block-payment_processor']/td/label[text()='$processorName']");
    $this->select('price_set_id', 'label=' . $setTitle);
    $this->click('_qf_Amount_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //get Url for Live Contribution Page
    $registerUrl = $this->_testVerifyRegisterPage($contributionPageTitle);

    //logout
    $this->open($this->sboxPath . 'civicrm/logout?reset=1');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Open Live Contribution Page
    $this->open($this->sboxPath . $registerUrl);
    $this->waitForElementPresent('_qf_Main_upload-bottom');

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $this->waitForElementPresent('_qf_Main_upload-bottom');
    $this->type('email-5', $firstName . '@example.com');
    $this->type('billing_first_name', $firstName);
    $this->type('billing_last_name', $lastName);
    $this->click("xpath=//input[@class='form-radio']");
    $this->click("xpath=//input[@class='form-checkbox']");

    $streetAddress = '100 Main Street';
    $this->type('billing_street_address-5', $streetAddress);
    $this->type('billing_city-5', 'San Francisco');
    $this->type('billing_postal_code-5', '94117');
    $this->select('billing_country_id-5', 'value=1228');
    $this->select('billing_state_province_id-5', 'value=1001');

    //Credit Card Info
    $this->select('credit_card_type', 'value=Visa');
    $this->type('credit_card_number', '4111111111111111');
    $this->type('cvv2', '000');
    $this->select('credit_card_exp_date[M]', 'value=1');
    $this->select('credit_card_exp_date[Y]', 'value=2020');

    //Billing Info
    $this->type('billing_first_name', $firstName . 'billing');
    $this->type('billing_last_name', $lastName . 'billing');
    $this->type('billing_street_address-5', '15 Main St.');
    $this->type(' billing_city-5', 'San Jose');
    $this->select('billing_country_id-5', 'value=1228');
    $this->select('billing_state_province_id-5', 'value=1004');
    $this->type('billing_postal_code-5', '94129');
    $this->click('_qf_Main_upload-bottom');

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_Confirm_next-bottom');

    $this->click('_qf_Confirm_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //login to check contribution
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Contribution
    $this->open($this->sboxPath . 'civicrm/contribute/search?reset=1');

    $this->waitForElementPresent('contribution_date_low');

    $this->type('sort_name', "$firstName $lastName");
    $this->click('_qf_Search_refresh');

    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->click("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_ContributionView_cancel-bottom');

    //View Contribution Record
    $expected = array(
      2 => $financialType,
      5 => '65.00',
      8 => 'Completed',
    );
    foreach($expected as $label => $value) {
      $this->verifyText("xpath=//form[@id='ContributionView']/div[2]/table/tbody/tr[$label]/td[2]", preg_quote($value));
    }
  }

  function testContributeOfflineforSoftcreditwithApi() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin();
    
    //create a contact and return the contact id
    $firstNameSoft = "John_".substr(sha1(rand()), 0, 5);
    $lastNameSoft  = "Doe_".substr(sha1(rand()), 0, 5);
    $this->webtestAddContact($firstNameSoft, $lastNameSoft);
    $url = $this->parseURL();
    $cid = $url['queryString']['cid'];
    $this->assertType('numeric', $cid);
    
    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor  = 'Contribution';
    $setHelp  = 'Select your conference options.';
    $financialType = $this->_testAddFinancialType();
    $this->_testAddSet($setTitle, $usedFor, $setHelp, $financialType);

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
    $this->_testAddPriceFields($fields, $validateStrings, $financialType);

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);

    $this->open($this->sboxPath . 'civicrm/contribute/add?reset=1&action=add&context=standalone');

    // As mentioned before, waitForPageToLoad is not always reliable. Below, we're waiting for the submit
    // button at the end of this page to show up, to make sure it's fully loaded.
    $this->waitForElementPresent('_qf_Contribution_upload');

    // Let's start filling the form with values.

    // create new contact using dialog
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestNewDialogContact($firstName, 'Contributor', $firstName . '@example.com');

    // select contribution type
    $this->select('financial_type_id', "label={$financialType}");

    // fill in Received Date
    $this->webtestFillDate('receive_date');

    // source
    $this->type('source', 'Mailer 1');

    // select price set items
    $this->select('price_set_id', "label=$setTitle");
    $this->type("xpath=//input[@class='form-text four required']", "1");
    $this->click("xpath=//input[@class='form-radio']");
    $this->click("xpath=//input[@class='form-checkbox']");
    // select payment instrument type = Check and enter chk number
    $this->select('payment_instrument_id', 'value=4');
    $this->waitForElementPresent('check_number');
    $this->type('check_number', 'check #1041');

    $this->type('trxn_id', 'P20901X1' . rand(100, 10000));
   
    $this->type('soft_credit_to', "$lastNameSoft, $firstNameSoft");
    $this->click('soft_credit_to');
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");
    //Additional Detail section
    $this->click('AdditionalDetail');
    $this->waitForElementPresent('thankyou_date');

    $this->type('note', 'This is a test note.');
    $this->type('non_deductible_amount', '10');
    $this->type('fee_amount', '0');
    $this->type('net_amount', '0');
    $this->type('invoice_id', time());
    $this->webtestFillDate('thankyou_date');

    // Clicking save.
    $this->click('_qf_Contribution_upload');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertTrue($this->isTextPresent('The contribution record has been saved.'), "Status message didn't show up after saving!");

    $this->waitForElementPresent("xpath=//div[@id='Contributions']//table//tbody/tr[1]/td[8]/span/a[text()='View']");

    //click through to the Membership view screen
    $this->click("xpath=//div[@id='Contributions']//table/tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ContributionView_cancel-bottom');

    $expected = array(
      2 => $financialType,
      3 => '590.00',
      9 => 'Completed',
      10 => 'Check',
      11 => 'check #1041',
      17 => "$firstNameSoft $lastNameSoft",
    );
    foreach ($expected as $label => $value) {
      $this->verifyText("xpath=//form[@id='ContributionView']/div[2]/table/tbody/tr[$label]/td[2]", preg_quote($value));
    }
    
    $exp = array(
      2 => '$ 525.00',
      3 => '$ 50.00',
      4 => '$ 15.00',
    );

    foreach ($exp as $lab => $val) {
      $this->verifyText("xpath=id('ContributionView')/div[2]/table[1]/tbody/tr[3]/td[2]/table/tbody/tr[$lab]/td[3]",
        preg_quote($val)
      );
    }
        
    // Check for Soft contact created
    $this->click("css=input#sort_name_navigation");
    $this->type("css=input#sort_name_navigation", "$lastNameSoft, $firstNameSoft");
    $this->typeKeys("css=input#sort_name_navigation", "$lastNameSoft, $firstNameSoft");
    // wait for result list
    $this->waitForElementPresent("css=div.ac_results-inner li");
    
    // visit contact summary page
    $this->click("css=div.ac_results-inner li");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click( 'css=li#tab_contribute a' );
    $this->waitForElementPresent('link=Record Contribution (Check, Cash, EFT ...)');
   
    $id = explode('id=', $this->getAttribute("xpath=id('rowid')/td[7]/a[text()='View']@href"));
    $id = substr($id[1], 0, strpos($id[1], '&'));
    $this->click("xpath=id('rowid')/td[7]/a");
    $this->waitForElementPresent('_qf_ContributionView_cancel-bottom');
    
    foreach ($expected as $label => $value) {
      $this->verifyText("xpath=id('ContributionView')/div[2]/table[1]/tbody/tr[$label]/td[2]", preg_quote($value));
    }
    
    $params = array('contribution_id' => $id,
      'version' => 3,
    );
    
    require_once 'api/api.php';
    $fields = $this->webtest_civicrm_api('contribution','get',$params );
    $params['id'] = $params['contact_id'] = $fields['values'][$fields['id']]['soft_credit_to'];
    $contact = CRM_Contact_BAO_Contact::retrieve($params, $defaults, TRUE);
    $expected = array(1 => $fields['values'][$fields['id']]['display_name'],
      2 => $fields['values'][$fields['id']]['financial_type'],
      3 => $fields['values'][$fields['id']]['total_amount'],
      9 => $fields['values'][$fields['id']]['contribution_status'],
      10 => $fields['values'][$fields['id']]['contribution_payment_instrument'],
      11 => $fields['values'][$fields['id']]['contribution_check_number'],
      17 => $contact->display_name,
    );
    foreach ($expected as $label => $value) {
      $this->verifyText("xpath=//form[@id='ContributionView']/div[2]/table/tbody/tr[$label]/td[2]", preg_quote($value));
    }
  }
}


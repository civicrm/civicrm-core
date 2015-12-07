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
 * Class WebTest_Contribute_AddPricesetTest
 */
class WebTest_Contribute_AddPricesetTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAddPriceSet() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    //add financial type of account type expense

    $financialType = $this->_testAddFinancialType();

    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor = 'Contribution';
    $setHelp = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp, $financialType);

    // Get the price set id ($sid) by retrieving and parsing the URL of the New Price Field form
    // which is where we are after adding Price Set.
    $sid = $this->urlArg('sid');
    $this->assertType('numeric', $sid);

    $validStrings = array();

    $fields = array(
      'Full Conference' => 'Text',
      'Meal Choice' => 'Select',
      'Pre-conference Meetup?' => 'Radio',
      'Evening Sessions' => 'CheckBox',
    );
    $this->_testAddPriceFields($fields, $validateStrings, $financialType);
    // var_dump($validateStrings);

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);
  }

  /**
   * @param $setTitle
   * @param $usedFor
   * @param $setHelp
   * @param null $financialType
   */
  public function _testAddSet($setTitle, $usedFor, $setHelp, $financialType = NULL) {
    $this->openCiviPage("admin/price", "reset=1&action=add", '_qf_Set_next-bottom');

    // Enter Priceset fields (Title, Used For ...)
    $this->type('title', $setTitle);
    if ($usedFor == 'Event') {
      $this->check('extends_1');
    }
    elseif ($usedFor == 'Contribution') {
      $this->check('extends_2');
    }

    if ($financialType) {
      $this->select("financial_type_id", "label={$financialType}");
    }
    $this->type('help_pre', $setHelp);

    $this->assertChecked('is_active', 'Verify that Is Active checkbox is set.');
    $this->clickLink('_qf_Set_next-bottom');
  }

  /**
   * @param $fields
   * @param $validateString
   * @param $financialType
   * @param bool $dateSpecificFields
   */
  public function _testAddPriceFields(&$fields, &$validateString, $financialType, $dateSpecificFields = FALSE) {
    $validateStrings[] = $financialType;
    $sid = $this->urlArg('sid');
    $this->openCiviPage('admin/price/field', "reset=1&action=add&sid=$sid", 'label');
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
            1 => array(
              'label' => 'Chicken',
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
            1 => array(
              'label' => 'Yes',
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
            1 => array(
              'label' => 'First Night',
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
      $this->clickLink('_qf_Field_next_new-bottom', '_qf_Field_next-bottom', FALSE);
      $this->waitForText('crm-notification-container', "Price Field '$label' has been saved.");
    }
  }

  /**
   * @return string
   */
  public function _testAddFinancialType() {
    //Add new Financial Type
    $financialType['name'] = 'FinancialType ' . substr(sha1(rand()), 0, 4);
    $financialType['is_deductible'] = TRUE;
    $financialType['is_reserved'] = FALSE;
    $this->addeditFinancialType($financialType);
    return $financialType['name'];
  }

  /**
   * @param $validateStrings
   * @param int $sid
   */
  public function _testVerifyPriceSet($validateStrings, $sid) {
    // verify Price Set at Preview page
    // start at Manage Price Sets listing
    $this->openCiviPage("admin/price", "reset=1");

    // Use the price set id ($sid) to pick the correct row
    $this->clickLink("//*[@id='price_set-{$sid}']/td[4]/span[1]/a[1]", 'Link=Add Price Field');
    // Check for expected price set field strings
    $this->assertStringsPresent($validateStrings);
  }

  public function testContributeOfflineWithPriceSet() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    //add financial type of account type expense
    $financialType = $this->_testAddFinancialType();

    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor = 'Contribution';
    $setHelp = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp, $financialType);

    // Get the price set id ($sid) by retrieving and parsing the URL of the New Price Field form
    // which is where we are after adding Price Set.
    $sid = $this->urlArg('sid');
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
    $this->openCiviPage("contribute/add", "reset=1&action=add&context=standalone", '_qf_Contribution_upload');

    // create new contact using dialog
    $this->createDialogContact();

    // select financial type
    $this->select('financial_type_id', "label={$financialType}");

    // fill in Received Date
    $this->webtestFillDate('receive_date');

    // source
    $this->type('source', 'Mailer 1');

    // select price set items
    $this->select('price_set_id', "label=$setTitle");
    $this->type("xpath=//input[@class='four crm-form-text required']", "1");
    $this->click("xpath=//input[@class='crm-form-radio']");
    $this->click("xpath=//input[@class='crm-form-checkbox']");
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
    $this->waitForElementPresent("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span//a[text()='View']");

    //click through to the Membership view screen
    $this->click("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span//a[text()='View']");
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

  public function testContributeOnlineWithPriceSet() {
    $this->webtestLogin();

    //add financial type of account type expense
    $financialType = $this->_testAddFinancialType();

    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor = 'Contribution';
    $setHelp = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp, $financialType);

    // Get the price set id ($sid) by retrieving and parsing the URL of the New Price Field form
    // which is where we are after adding Price Set.
    $sid = $this->urlArg('sid');
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

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    $this->openCiviPage("admin/contribute/add", "reset=1&action=add");

    $contributionTitle = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);

    // fill in step 1 (Title and Settings)
    $contributionPageTitle = "Title $contributionTitle";
    $this->type('title', $contributionPageTitle);
    $this->fillRichTextField('intro_text', 'This is Test Introductory Message', 'CKEditor');
    $this->fillRichTextField('footer_text', 'This is Test Footer Message', 'CKEditor');

    $this->select('financial_type_id', "label={$financialType}");

    // Submit form
    $this->clickLink('_qf_Settings_next', "_qf_Amount_next-bottom");

    // Get contribution page id
    $pageId = $this->urlArg('id');

    //this contribution page for online contribution
    $this->click("xpath=//tr[@class='crm-contribution-contributionpage-amount-form-block-payment_processor']/td/label[text()='$processorName']");
    $this->select('price_set_id', 'label=' . $setTitle);
    $this->click('_qf_Amount_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //logout
    $this->webtestLogout();

    //Open Live Contribution Page
    $this->openCiviPage('contribute/transact', "reset=1&id=$pageId&action=preview", '_qf_Main_upload-bottom');

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $email = $firstName . "@example.com";
    $this->waitForElementPresent('_qf_Main_upload-bottom');
    $this->type("email-5", $email);
    $this->type("xpath=//input[@class='four crm-form-text required']", "1");
    $this->click("xpath=//input[@class='crm-form-radio']");
    $this->click("xpath=//input[@class='crm-form-checkbox']");

    $streetAddress = '100 Main Street';
    $this->type('billing_street_address-5', $streetAddress);
    $this->type('billing_city-5', 'San Francisco');
    $this->type('billing_postal_code-5', '94117');
    $this->waitForAjaxContent();
    $this->select('billing_country_id-5', 'value=1228');
    $this->select('billing_state_province_id-5', 'value=1001');

    //Credit Card Info
    $this->select('credit_card_type', 'value=Visa');
    $this->type('credit_card_number', '4111111111111111');
    $this->type('cvv2', '000');
    $this->select('credit_card_exp_date[M]', 'value=1');
    $this->select('credit_card_exp_date[Y]', 'value=2020');

    //Billing Info
    $this->type('billing_first_name', $firstName);
    $this->type('billing_last_name', $lastName);
    $this->type('billing_street_address-5', '15 Main St.');
    $this->type('billing_city-5', 'San Jose');
    $this->select('billing_country_id-5', 'value=1228');
    $this->select('billing_state_province_id-5', 'value=1004');
    $this->type('billing_postal_code-5', '94129');
    $this->clickLink('_qf_Main_upload-bottom', '_qf_Confirm_next-bottom');

    $this->click('_qf_Confirm_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //login to check contribution

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Contribution
    $this->openCiviPage("contribute/search", "reset=1", 'contribution_date_low');

    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Contribution is a Test?')]/../../td[2]/label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->type("sort_name", "$email");
    $this->waitForAjaxContent();
    $this->click("xpath=//div[@class='crm-accordion-wrapper crm-contribution_search_form-accordion ']/div[2]/table/tbody/tr[8]/td[1]/table/tbody/tr[3]/td[2]/label[1]");
    $this->clickLink('_qf_Search_refresh', "xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']");
    $this->clickLink("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", "_qf_ContributionView_cancel-bottom", FALSE);

    // View Contribution Record and test for expected values
    $expected = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => $financialType,
      // as per changes made in CRM-15407
      'Fee Amount' => '$ 1.50',
      'Net Amount' => '$ 588.50',
      'Contribution Status' => 'Completed',
    );
    $this->webtestVerifyTabularData($expected);

  }

  public function testContributeWithDateSpecificPriceSet() {
    $this->webtestLogin();

    //add financial type of account type expense
    $financialType = $this->_testAddFinancialType();

    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor = 'Contribution';
    $setHelp = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp, $financialType);

    // Get the price set id ($sid) by retrieving and parsing the URL of the New Price Field form
    // which is where we are after adding Price Set.
    $sid = $this->urlArg('sid');
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

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    $this->openCiviPage("admin/contribute/add", "reset=1&action=add");

    $contributionTitle = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);

    // fill in step 1 (Title and Settings)
    $contributionPageTitle = "Title $contributionTitle";
    $this->type('title', $contributionPageTitle);
    $this->select('financial_type_id', "label={$financialType}");
    $this->fillRichTextField('intro_text', 'This is Test Introductory Message', 'CKEditor');
    $this->fillRichTextField('footer_text', 'This is Test Footer Message', 'CKEditor');

    // Submit form
    $this->clickLink('_qf_Settings_next', "_qf_Amount_next-bottom");

    // Get contribution page id
    $pageId = $this->urlArg('id');

    //this contribution page for online contribution
    $this->waitForElementPresent("xpath=//tr[@class='crm-contribution-contributionpage-amount-form-block-payment_processor']/td");
    $this->click("xpath=//tr[@class='crm-contribution-contributionpage-amount-form-block-payment_processor']/td/label[text()='$processorName']");
    $this->select('price_set_id', 'label=' . $setTitle);
    $this->clickLink('_qf_Amount_next-bottom');

    //logout
    $this->webtestLogout();

    //Open Live Contribution Page
    $this->openCiviPage('contribute/transact', "reset=1&id=$pageId&action=preview", '_qf_Main_upload-bottom');

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $email = $firstName . "@example.com";
    $this->waitForElementPresent('_qf_Main_upload-bottom');
    $this->type('email-5', $email);
    $this->click("xpath=//input[@class='crm-form-radio']");
    $this->click("xpath=//input[@class='crm-form-checkbox']");

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
    $this->type('billing_first_name', $firstName);
    $this->type('billing_last_name', $lastName);
    $this->type('billing_street_address-5', '15 Main St.');
    $this->type(' billing_city-5', 'San Jose');
    $this->select('billing_country_id-5', 'value=1228');
    $this->select('billing_state_province_id-5', 'value=1004');
    $this->type('billing_postal_code-5', '94129');
    $this->clickLink('_qf_Main_upload-bottom', '_qf_Confirm_next-bottom');

    $this->click('_qf_Confirm_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //login to check contribution
    $this->webtestLogin();

    //Find Contribution
    $this->openCiviPage("contribute/search", "reset=1", 'contribution_date_low');
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Contribution is a Test?')]/../../td[2]/label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->type("sort_name", "$email");
    $this->waitForAjaxContent();
    $this->click("xpath=//div[@class='crm-accordion-wrapper crm-contribution_search_form-accordion ']/div[2]/table/tbody/tr[8]/td[1]/table/tbody/tr[3]/td[2]/label[1]");
    $this->clickLink('_qf_Search_refresh', "xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", FALSE);
    $this->clickLink("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", '_qf_ContributionView_cancel-bottom', FALSE);

    // View Contribution Record and test for expected values
    $expected = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => $financialType,
      // as per changes made in CRM-15407
      'Fee Amount' => '$ 1.50',
      'Net Amount' => '$ 63.50',
      'Contribution Status' => 'Completed',
    );
    $this->webtestVerifyTabularData($expected);
  }

  public function testContributeOfflineforSoftcreditwithApi() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    //create a contact and return the contact id
    $firstNameSoft = "John_" . substr(sha1(rand()), 0, 5);
    $lastNameSoft = "Doe_" . substr(sha1(rand()), 0, 5);
    $this->webtestAddContact($firstNameSoft, $lastNameSoft);
    $url = $this->parseURL();
    $cid = $url['queryString']['cid'];
    $this->assertType('numeric', $cid);

    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor = 'Contribution';
    $setHelp = 'Select your conference options.';
    $financialType = $this->_testAddFinancialType();
    $this->_testAddSet($setTitle, $usedFor, $setHelp, $financialType);

    // Get the price set id ($sid) by retrieving and parsing the URL of the New Price Field form
    // which is where we are after adding Price Set.
    $sid = $this->urlArg('sid');
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

    $this->openCiviPage("contribute/add", "reset=1&action=add&context=standalone", '_qf_Contribution_upload');

    // create new contact using dialog
    $contact = $this->createDialogContact();

    // select contribution type
    $this->select('financial_type_id', "label={$financialType}");

    // fill in Received Date
    $this->webtestFillDate('receive_date');

    // source
    $this->type('source', 'Mailer 1');

    // select price set items
    $this->select('price_set_id', "label=$setTitle");
    $this->type("xpath=//input[@class='four crm-form-text required']", "1");
    $this->click("xpath=//input[@class='crm-form-radio']");
    $this->click("xpath=//input[@class='crm-form-checkbox']");
    // select payment instrument type = Check and enter chk number
    $this->select('payment_instrument_id', 'value=4');
    $this->waitForElementPresent('check_number');
    $this->type('check_number', '1041');

    $this->type('trxn_id', 'P20901X1' . rand(100, 10000));

    $this->webtestFillAutocomplete("{$lastNameSoft}, {$firstNameSoft}", 'soft_credit_contact_id_1');

    $this->type('soft_credit_amount_1', "65");
    //Additional Detail section
    $this->click('AdditionalDetail');
    $this->waitForElementPresent('thankyou_date');

    $this->type('note', 'This is a test note.');
    $this->type('invoice_id', time());
    $this->webtestFillDate('thankyou_date');

    // Clicking save.
    $this->clickLink('_qf_Contribution_upload', "xpath=//table[@class='selector row-highlight']//tbody/tr[1]/td[8]/span//a[text()='View']", FALSE);
    $this->assertTrue($this->isTextPresent('The contribution record has been saved.'), "Status message didn't show up after saving!");

    //click through to the Contribution view screen
    $this->click("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ContributionView_cancel-bottom');

    // View Contribution Record and test for expected values
    $expected = array(
      'From' => $contact['display_name'],
      'Financial Type' => $financialType,
      'Contribution Amount' => 'Contribution Total: $ 590.00',
      'Payment Method' => 'Check',
      'Check Number' => '1041',
      'Contribution Status' => 'Completed',
    );
    $this->webtestVerifyTabularData($expected);

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

    // verify if soft credit was created successfully
    $softCreditValues = array(
      'Soft Credit To' => "{$firstNameSoft} {$lastNameSoft}",
      'Amount' => '65.00',
    );

    foreach ($softCreditValues as $value) {
      $this->verifyText("css=table.crm-soft-credit-listing", preg_quote($value));
    }

    // Check for Soft contact created
    $this->click("css=input#sort_name_navigation");
    $this->type("css=input#sort_name_navigation", "$lastNameSoft, $firstNameSoft");
    $this->typeKeys("css=input#sort_name_navigation", "$lastNameSoft, $firstNameSoft");
    // wait for result list
    $this->waitForElementPresent("css=ul.ui-autocomplete li");

    // visit contact summary page
    $this->click("css=ul.ui-autocomplete li");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click('css=li#tab_contribute a');
    $this->waitForElementPresent('link=Record Contribution (Check, Cash, EFT ...)');

    $id = explode('id=', $this->getAttribute("xpath=//table[@class='selector row-highlight']/tbody//tr[@id='rowid']/td[8]/a[text()='View']@href"));
    $id = substr($id[1], 0, strpos($id[1], '&'));
    $this->click("xpath=//table[@class='selector row-highlight']/tbody//tr[@id='rowid']/td[8]/a");
    $this->waitForElementPresent('_qf_ContributionView_cancel-bottom');

    $this->webtestVerifyTabularData($expected);

    $params = array(
      'contribution_id' => $id,
      'version' => 3,
    );

    // Retrieve contribution from the DB via api and verify DB values against view contribution page
    $fields = $this->webtest_civicrm_api('contribution', 'get', $params);

    $params['id'] = $params['contact_id'] = $fields['values'][$fields['id']]['soft_credit_to'];
    $softCreditContact = CRM_Contact_BAO_Contact::retrieve($params, $defaults, TRUE);

    // View Contribution Record and test for expected values
    $expected = array(
      'From' => $fields['values'][$fields['id']]['display_name'],
      'Financial Type' => $fields['values'][$fields['id']]['financial_type'],
      'Contribution Amount' => $fields['values'][$fields['id']]['total_amount'],
      'Contribution Status' => $fields['values'][$fields['id']]['contribution_status'],
      'Payment Method' => $fields['values'][$fields['id']]['payment_instrument'],
      'Check Number' => $fields['values'][$fields['id']]['contribution_check_number'],
    );

    $this->webtestVerifyTabularData($expected);

    // verify if soft credit
    $softCreditValues = array(
      'Soft Credit To' => $softCreditContact->display_name,
      'Amount' => '65.00',
    );

    foreach ($softCreditValues as $value) {
      $this->verifyText("css=table.crm-soft-credit-listing", preg_quote($value));
    }
  }

}

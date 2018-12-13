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
 * Class WebTest_Contribute_OnlineContributionTest
 */
class WebTest_Contribute_OnlineContributionTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testOnlineContributionAdd() {
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $processorType = 'Dummy';
    $pageTitle = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(10, 50);
    $hash = substr(sha1(rand()), 0, 7);
    $amountSection = TRUE;
    $payLater = FALSE;
    $onBehalf = FALSE;
    $pledges = FALSE;
    $recurring = FALSE;
    $memberships = FALSE;
    $friend = TRUE;
    $profilePreId = 1;
    $profilePostId = NULL;
    $premiums = FALSE;
    $widget = FALSE;
    $pcp = FALSE;
    $memPriceSetId = NULL;

    // create a new online contribution page
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $pageTitle,
      array($processorName => $processorType),
      $amountSection,
      $payLater,
      $onBehalf,
      $pledges,
      $recurring,
      $memberships,
      $memPriceSetId,
      $friend,
      $profilePreId,
      $profilePostId,
      $premiums,
      $widget,
      $pcp
    );

    //logout
    $this->webtestLogout();

    //Open Live Contribution Page
    $this->openCiviPage("contribute/transact", "reset=1&id=$pageId&action=preview", "_qf_Main_upload-bottom");

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $honorFirstName = 'In' . substr(sha1(rand()), 0, 4);
    $honorLastName = 'Hon' . substr(sha1(rand()), 0, 7);
    $honorEmail = $honorFirstName . "@example.com";
    $honorSortName = $honorLastName . ', ' . $honorFirstName;
    $honorDisplayName = 'Ms. ' . $honorFirstName . ' ' . $honorLastName;

    $this->type("email-5", $firstName . "@example.com");

    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    $this->click("xpath=//div[@class='crm-section other_amount-section']//div[2]/input");
    $this->type("xpath=//div[@class='crm-section other_amount-section']//div[2]/input", 100);

    $streetAddress = "100 Main Street";
    $this->type("street_address-1", $streetAddress);
    $this->type("city-1", "San Francisco");
    $this->type("postal_code-1", "94117");
    $this->select("country-1", "value=1228");
    $this->select("state_province-1", "value=1001");
    // Honoree Info
    $this->click("xpath=id('Main')/div[3]/fieldset/div[2]/div/label[text()='In Honor of']");

    $this->select("honor[prefix_id]", "label=Ms.");
    $this->type("honor[first_name]", $honorFirstName);
    $this->type("honor[last_name]", $honorLastName);
    $this->type("honor[email-1]", $honorEmail);

    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");

    //Billing Info
    $this->type("billing_first_name", $firstName . "billing");
    $this->type("billing_last_name", $lastName . "billing");
    $this->type("billing_street_address-5", "15 Main St.");
    $this->type(" billing_city-5", "San Jose");
    $this->select("billing_country_id-5", "value=1228");
    $this->select("billing_state_province_id-5", "value=1004");
    $this->type("billing_postal_code-5", "94129");
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");
    $this->waitForElementPresent("xpath=//div[@class='crm-group billing_name_address-group']//div[@class='crm-section no-label billing_name-section']");
    $this->assertElementContainsText("xpath=//div[@class='crm-group billing_name_address-group']//div[@class='crm-section no-label billing_name-section']", $firstName . "billing");
    $this->assertElementContainsText("xpath=//div[@class='crm-group billing_name_address-group']//div[@class='crm-section no-label billing_name-section']", $lastName . "billing");

    $stateText = CRM_Core_PseudoConstant::stateProvinceAbbreviation(1004);
    $countryText = CRM_Core_PseudoConstant::countryIsoCode(1228);
    $billingDetails = array('15 Main St.', 'San Jose', '94129', $stateText, $countryText);
    foreach ($billingDetails as $field) {
      $this->assertElementContainsText("xpath=//div[@class='crm-group billing_name_address-group']//div[@class='crm-section no-label billing_address-section']", $field);
    }

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("xpath=//div[@class='crm-group billing_name_address-group']//div[@class='crm-section no-label billing_name-section']");
    $this->assertElementContainsText("xpath=//div[@class='crm-group billing_name_address-group']//div[@class='crm-section no-label billing_name-section']", $firstName . "billing");
    $this->assertElementContainsText("xpath=//div[@class='crm-group billing_name_address-group']//div[@class='crm-section no-label billing_name-section']", $lastName . "billing");

    //login to check contribution

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Contribution
    $this->openCiviPage("contribute/search", "reset=1", "contribution_date_low");
    $this->type("sort_name", "$lastName $firstName");
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Contribution is a Test?')]/../../td[2]/label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->clickLink("_qf_Search_refresh", "xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", FALSE);
    $this->clickLink("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", "_qf_ContributionView_cancel-bottom", FALSE);

    //View Contribution Record and verify data
    $expected = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Total Amount' => '100.00',
      'Contribution Status' => 'Completed',
    );
    $this->webtestVerifyTabularData($expected);
    //View Soft Credit record of type 'Honor of'
    $this->waitForTextPresent($honorDisplayName);
    $this->waitForTextPresent('100.00 (In Honor of)');

    // Check for Honoree contact created
    $this->click("css=input#sort_name_navigation");
    $this->type("css=input#sort_name_navigation", $honorSortName);
    $this->typeKeys("css=input#sort_name_navigation", $honorSortName);

    // wait for result list
    $this->waitForElementPresent("css=ul.ui-autocomplete li");

    // visit contact summary page
    $this->click("css=ul.ui-autocomplete li");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is contact present?
    $this->assertTrue($this->isTextPresent("$honorDisplayName"), "Honoree contact not found.");

    // CRM-16064 - Contributions pricesets charge $1 more than selected
    $contributionAmt = number_format($rand, 2);
    $label = "Label $hash";
    $this->_verifyContributionAmt($pageId, $contributionAmt, $label);

  }

  public function testOnlineContributionWithZeroAmount() {
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $processorType = 'Dummy';
    $pageTitle = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(10, 50);
    $hash = substr(sha1(rand()), 0, 7);
    $amountSection = TRUE;
    $payLater = FALSE;
    $onBehalf = FALSE;
    $pledges = FALSE;
    $recurring = FALSE;
    $memberships = FALSE;
    $friend = FALSE;
    $profilePreId = NULL;
    $profilePostId = NULL;
    $premiums = FALSE;
    $widget = FALSE;
    $pcp = FALSE;
    $memPriceSetId = NULL;

    // create a new online contribution page
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $pageTitle,
      array($processorName => $processorType),
      $amountSection,
      $payLater,
      $onBehalf,
      $pledges,
      $recurring,
      $memberships,
      $memPriceSetId,
      $friend,
      $profilePreId,
      $profilePostId,
      $premiums,
      $widget,
      $pcp
    );

    $this->openCiviPage("admin/contribute/amount", "reset=1&action=update&id=$pageId", '_qf_Amount_cancel-bottom');
    $this->type('label_1', "Label $hash");
    $this->type('value_1', 0);
    $this->clickLink('_qf_Amount_upload_done-top');

    //Contribution using Contribution Options
    $this->_doContributionAndVerifyData($pageId);

    //add priceset
    $this->openCiviPage("admin/price", "reset=1&action=add", '_qf_Set_next-bottom');
    $this->type('title', "Test Priceset $rand");
    $this->check('extends_2');
    $this->select("financial_type_id", "label=Donation");
    $this->clickLink('_qf_Set_next-bottom', '_qf_Field_next-bottom', FALSE);
    $sid = $this->urlArg('sid');
    //add field
    $this->type('label', "Testfield");
    $this->select('html_type', "value=Radio");
    $this->type('option_label_1', 'test Label');
    $this->type('option_amount_1', 0.00);
    $this->clickLink('_qf_Field_next_new-bottom', '_qf_Field_next-bottom', FALSE);
    $this->openCiviPage("admin/contribute/amount", "reset=1&action=update&id=$pageId", '_qf_Amount_cancel-bottom');
    $this->select('price_set_id', "value=$sid");
    $this->clickLink('_qf_Amount_upload_done-bottom', FALSE);

    //Contribution using priceset
    $this->_doContributionAndVerifyData($pageId, TRUE);
  }

  /**
   * @param int $pageId
   * @param bool $priceSet
   */
  public function _doContributionAndVerifyData($pageId, $priceSet = FALSE) {
    //logout
    $this->webtestLogout();
    $amountLabel = 'Total Amount';
    $amountValue = '0.00';
    //Open Live Contribution Page
    $this->openCiviPage("contribute/transact", "reset=1&id=$pageId&action=preview", "_qf_Main_upload-bottom");

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $email = $firstName . "@example.com";
    $this->type("email-5", $email);

    if ($priceSet) {
      $this->click("xpath=//div[@id='priceset']/div/div[2]/div/span/input");
      $amountLabel = 'Contribution Amount';
      $amountValue = 'Contribution Total: $ 0.00';
    }

    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");

    //Billing Info
    $this->type("billing_first_name", $firstName);
    $this->type("billing_last_name", $lastName);
    $this->type("billing_street_address-5", "15 Main St.");
    $this->type(" billing_city-5", "San Jose");
    $this->select("billing_country_id-5", "value=1228");
    $this->select("billing_state_province_id-5", "value=1004");
    $this->type("billing_postal_code-5", "94129");
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");

    $this->clickLink("_qf_Confirm_next-bottom", NULL);

    //login to check contribution

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Contribution
    $this->openCiviPage("contribute/search", "reset=1", "contribution_date_low");
    $this->type("sort_name", "$email");
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Contribution is a Test?')]/../../td[2]/label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->waitForAjaxContent();
    $this->click("xpath=//div[@class='crm-accordion-wrapper crm-contribution_search_form-accordion ']/div[2]/table/tbody/tr[8]/td[1]/table/tbody/tr[3]/td[2]/label[1]");
    $this->clickLink("_qf_Search_refresh", "xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", FALSE);
    $this->clickLink("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", "_qf_ContributionView_cancel-bottom", FALSE);

    //View Contribution Record and verify data
    $expected = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      $amountLabel => $amountValue,
      'Contribution Status' => 'Completed',
    );
    $this->webtestVerifyTabularData($expected);
  }

  public function _verifyContributionAmt($pageId, $contributionAmt, $label) {
    // logout
    $this->webtestLogout();

    //Open Live Contribution Page
    $this->openCiviPage("contribute/transact", "reset=1&id=$pageId&action=preview", "_qf_Main_upload-bottom");
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $this->type("email-5", $firstName . "@example.com");

    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->check("xpath=//div[@id='priceset']/div[contains(@class, 'contribution_amount-section')]//div[contains(@class, 'contribution_amount-row1')]/span/input");
    $streetAddress = "100 Main Street";
    $this->type("street_address-1", $streetAddress);
    $this->type("city-1", "San Francisco");
    $this->type("postal_code-1", "94117");
    $this->select("country-1", "value=1228");
    $this->select("state_province-1", "value=1001");

    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");

    //Billing Info
    $this->type("billing_first_name", $firstName . "billing");
    $this->type("billing_last_name", $lastName . "billing");
    $this->type("billing_street_address-5", "15 Main St.");
    $this->type(" billing_city-5", "San Jose");
    $this->select("billing_country_id-5", "value=1228");
    $this->select("billing_state_province_id-5", "value=1004");
    $this->type("billing_postal_code-5", "94129");
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");

    $this->assertElementContainsText("xpath=//div[contains(@class, 'amount_display-group')]//div[@class='display-block']/strong", "$ $contributionAmt - $label", "Contribution amount does not match");
    $this->clickLink("_qf_Confirm_next-bottom");
    $this->assertElementContainsText("xpath=//div[contains(@class, 'amount_display-group')]//div[@class='display-block']/strong", "$ $contributionAmt - $label", "Contribution amount does not match");
    // Log in using webtestLogin() method
    $this->webtestLogin();
    $this->openCiviPage("contribute/search", "reset=1", "contribution_date_low");

    $this->type("sort_name", "$lastName $firstName");
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Contribution is a Test?')]/../../td[2]/label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='contributionSearch']//table[@class='selector row-highlight']//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='contributionSearch']//table[@class='selector row-highlight']//tbody/tr[1]/td[10]/span/a[text()='View']", "_qf_ContributionView_cancel-bottom", FALSE);

    //View Contribution Record and verify data
    $expected = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Total Amount' => $contributionAmt,
      'Contribution Status' => 'Completed',
    );
    $this->webtestVerifyTabularData($expected);
  }

  public function testOnlineContributionWithPremium() {
    //CRM-16713: Contribution search by premiums on find contribution form.
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $processorType = 'Dummy';
    $pageTitle = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(10, 50);
    $hash = substr(sha1(rand()), 0, 7);
    $amountSection = TRUE;
    $payLater = FALSE;
    $onBehalf = FALSE;
    $pledges = FALSE;
    $recurring = FALSE;
    $memberships = FALSE;
    $friend = TRUE;
    $profilePreId = 1;
    $profilePostId = NULL;
    $premiums = TRUE;
    $widget = FALSE;
    $pcp = FALSE;
    $memPriceSetId = NULL;

    // create a new online contribution page
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $pageTitle,
      array($processorName => $processorType),
      $amountSection,
      $payLater,
      $onBehalf,
      $pledges,
      $recurring,
      $memberships,
      $memPriceSetId,
      $friend,
      $profilePreId,
      $profilePostId,
      $premiums,
      $widget,
      $pcp
    );

    //logout
    $this->webtestLogout();

    //Open Live Contribution Page
    $this->openCiviPage("contribute/transact", "reset=1&id=$pageId&action=preview", "_qf_Main_upload-bottom");

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);

    $this->type("email-5", $firstName . "@example.com");

    $this->waitForAjaxContent();
    $this->click("xpath=//div[@id='premiums-listings']/div[2]/div[1]");
    $this->waitForAjaxContent();
    $this->select("xpath=//div[@id='premiums']/fieldset/div[@id='premiums-listings']/div[2]/div[2]/div[2]/div[4]/p/select", "value=Black");

    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    $this->click("xpath=//div[@class='crm-section other_amount-section']//div[2]/input");
    $this->type("xpath=//div[@class='crm-section other_amount-section']//div[2]/input", 100);

    $streetAddress = "100 Main Street";
    $this->type("street_address-1", $streetAddress);
    $this->type("city-1", "San Francisco");
    $this->type("postal_code-1", "94117");
    $this->select("country-1", "value=1228");
    $this->select("state_province-1", "value=1001");

    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");

    //Billing Info
    $this->type("billing_first_name", $firstName . "billing");
    $this->type("billing_last_name", $lastName . "billing");
    $this->type("billing_street_address-5", "15 Main St.");
    $this->type(" billing_city-5", "San Jose");
    $this->select("billing_country_id-5", "value=1228");
    $this->select("billing_state_province_id-5", "value=1004");
    $this->type("billing_postal_code-5", "94129");
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Contribution
    $this->openCiviPage("contribute/search", "reset=1", "contribution_date_low");
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Contribution is a Test?')]/../../td[2]/label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->type("sort_name", "$lastName $firstName");
    $this->multiselect2('contribution_product_id', array('Coffee Mug'));
    $this->click("_qf_Search_refresh");
    $this->waitForAjaxContent();
    $this->waitForElementPresent("xpath=//table[@class='selector row-highlight']/tbody//tr/td[10]/span//a[text()='View']");
    $this->click("xpath=//table[@class='selector row-highlight']/tbody//tr/td[2]/a[text()='{$lastName}, {$firstName}']/../../td[10]/span//a[text()='View']");
    $this->waitForElementPresent("xpath=//button//span[contains(text(),'Done')]");

    //View Contribution Record and verify data
    $expected = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Total Amount' => '100.00',
      'Contribution Status' => 'Completed',
      'Premium' => 'Coffee Mug',
    );
    $this->webtestVerifyTabularData($expected);
  }

  /**
   * CRM-19263 - Test online pay now functionality
   */
  public function testOnlineContributionWithPayNowLink() {
    $this->webtestLogin();
    $pageId = 1;
    $this->openCiviPage("admin/contribute/amount", "reset=1&action=update&id=$pageId", 'is_pay_later');
    $this->check('is_pay_later');
    $this->type('pay_later_text', "I will send payment by check");
    $this->fillRichTextField('pay_later_receipt', "I will send payment by check");
    $this->clickLink("_qf_Amount_upload_done-bottom");

    //add financial type of account type expense
    $financialType = 'Donation';
    $setTitle = 'Conference Fees - ' . substr(sha1(rand()), 0, 7);
    $usedFor = 'Contribution';
    $setHelp = 'Select your conference options.';
    $this->_testAddSet($setTitle, $usedFor, $setHelp, $financialType);
    $sid = $this->urlArg('sid');

    $validateStrings = array();
    $fields = array(
      'Full Conference' => 'Text',
      'Meal Choice' => 'Select',
      'Pre-conference Meetup?' => 'Radio',
      'Evening Sessions' => 'CheckBox',
    );

    $this->_testAddPriceFields($fields, $validateStrings, $financialType);

    //Add profile Details
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $name = $this->_testCreateUser($firstName, $lastName);
    $this->openCiviPage("admin/synchUser", "reset=1", NULL);
    $this->clickLink("_qf_CMSUser_next-bottom");

    $this->openCiviPage("admin/setting/preferences/contribute", "reset=1", "deferred_revenue_enabled");
    $this->check('deferred_revenue_enabled');
    $this->waitForElementPresent('default_invoice_page');
    $this->select('default_invoice_page', "value=$pageId");
    $this->clickLink("_qf_Contribute_next-bottom");

    $this->webtestLogin($name, "Test12345");
    $this->_testContributeWithPayLater($pageId, $firstName);

    $this->_testContributeWithPayNow($firstName);

    $this->openCiviPage("user", "reset=1");
    $this->assertFalse($this->isTextPresent("Pay Now"));

    $this->webtestLogin();

    $this->openCiviPage("admin/contribute/amount", "reset=1&action=update&id=$pageId", 'price_set_id');
    $this->select('price_set_id', "value=9");
    $this->clickLink("_qf_Amount_upload_done-bottom");

    $this->webtestLogin($name, "Test12345");

    $this->_testContributeWithPayLater($pageId, $firstName, TRUE);

    $this->_testContributeWithPayNow($firstName, TRUE);

    $this->openCiviPage("user", "reset=1");
    $this->assertFalse($this->isTextPresent("Pay Now"));

    // Type search name in autocomplete.
    $this->webtestLogin();
    $this->openCiviPage("civicrm/dashboard", "reset=1", 'sort_name_navigation');
    $this->click('sort_name_navigation');
    $this->type('css=input#sort_name_navigation', $firstName);
    $this->typeKeys('css=input#sort_name_navigation', $firstName);
    $this->waitForElementPresent("css=ul.ui-autocomplete li");
    $this->clickLink("css=ul.ui-autocomplete li", 'tab_contribute');

    $this->click('css=li#tab_contribute a');
    $this->waitForElementPresent('link=Record Contribution (Check, Cash, EFT ...)');

    $amountValues = array(
      1 => '$ 588.50',
      2 => '$ 98.50',
    );
    foreach ($amountValues as $row => $amount) {
      $this->clickLink("xpath=//table[@class='selector row-highlight']/tbody/tr[{$row}]/td[8]/span//a[text()='View']", "_qf_ContributionView_cancel-bottom", FALSE);

      // View Contribution Record and test for expected values
      $expected = array(
        'From' => "{$firstName} {$lastName}",
        'Financial Type' => $financialType,
        'Fee Amount' => '$ 1.50',
        'Net Amount' => $amount,
        'Received Into' => 'Payment Processor Account',
        'Payment Method' => 'Credit Card (Test Processor)',
        'Contribution Status' => 'Completed',
      );
      $this->webtestVerifyTabularData($expected);

      $this->clickAjaxLink("xpath=//span[text()='Done']");
    }
  }

  /**
   * Contribute using pay now link
   * @param string $firstName
   * @param bool $priceSet
   */
  public function _testContributeWithPayNow($firstName, $priceSet = FALSE) {
    //user dashboard
    $this->openCiviPage("user", "reset=1");
    $this->waitForElementPresent("xpath=//a/span[contains(text(), 'Pay Now')]");
    $this->clickLink("xpath=//a/span[contains(text(), 'Pay Now')]");

    if (empty($priceSet)) {
      $this->waitForElementPresent("total_amount");
      $this->assertTrue($this->isElementPresent("xpath=//input[@id='total_amount'][@readonly=1][@value='100.00']"));
    }
    else {
      $this->assertElementContainsText("xpath=//div[@class='header-dark']", "Contribution Information");
      $this->assertElementContainsText("xpath=//div[@class='crm-section no-label total_amount-section']", "Contribution Total: $ 590.00");
    }

    $this->assertFalse($this->isElementPresent("priceset"));
    $this->assertFalse($this->isElementPresent("xpath=//div[@class='crm-public-form-item crm-section is_pledge-section']"));
    $this->assertFalse($this->isElementPresent("xpath=//div[@class='crm-public-form-item crm-section premium_block-section']"));
    $this->assertFalse($this->isElementPresent("xpath=//div[@class='crm-public-form-item crm-group custom_pre_profile-group']"));
    $this->assertFalse($this->isElementPresent("xpath=//input[@id=email-5]"));
    $this->assertFalse($this->isElementPresent("xpath=//input[@name='payment_processor_id'][@value=0]"));
    $this->click("xpath=//input[@name='payment_processor_id'][@value=1]");
    $this->waitForAjaxContent();

    $this->webtestAddCreditCardDetails();
    $this->webtestAddBillingDetails();
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");

    $this->clickLink("_qf_Confirm_next-bottom");
    $firstName = strtolower($firstName);
    $emailText = "An email receipt has also been sent to {$firstName}@example.com";
    $this->waitForTextPresent($emailText);

  }

  /**
   * Contribute with pay later
   *
   * @param int $pageId
   * @param string $firstName
   * @param bool $priceSet
   */
  public function _testContributeWithPayLater($pageId, $firstName, $priceSet = FALSE) {
    $this->openCiviPage("contribute/transact", "reset=1&action=preview&id=$pageId", NULL);
    $this->waitForElementPresent("email-5");

    $this->type("email-5", $firstName . "@example.com");

    if (empty($priceSet)) {
      $this->click("xpath=//div[@class='crm-section other_amount-section']//div[2]/input");
      $this->type("xpath=//div[@class='crm-section other_amount-section']//div[2]/input", 100);
      $this->typeKeys("xpath=//div[@class='crm-section other_amount-section']//div[2]/input", 100);
    }
    else {
      $this->type("xpath=//input[@class='four crm-form-text required']", "1");
      $this->click("xpath=//input[@class='crm-form-radio']");
      $this->click("xpath=//input[@class='crm-form-checkbox']");
    }

    $this->waitForTextPresent("Payment Method");
    $payLaterText = "I will send payment by check";
    $this->click("xpath=//label[text() = '{$payLaterText}']/preceding-sibling::input[1]");

    $this->waitForAjaxContent();
    $this->clickLink("_qf_Main_upload-bottom");
    $this->waitForElementPresent("xpath=//div[@class='bold pay_later_receipt-section']");

    $payLaterInstructionsText = "I will send payment by check";
    $this->assertElementContainsText("xpath=//div[@class='bold pay_later_receipt-section']/p", $payLaterInstructionsText);
    $this->clickLink("_qf_Confirm_next-bottom");

    $this->waitForElementPresent("xpath=//div[@class='help']/div/p");
    $this->assertElementContainsText("xpath=//div[@class='help']/div/p", $payLaterInstructionsText);
  }

  /**
   * Create test user
   *
   * @param string $firstName
   * @param string $lastName
   *
   * @return string
   */
  public function _testCreateUser($firstName, $lastName) {
    $this->open($this->sboxPath . "admin/people/create");

    $this->waitForElementPresent("edit-submit");

    $name = "TestUser" . substr(sha1(rand()), 0, 4);
    $this->type("edit-name", $name);

    $emailId = substr(sha1(rand()), 0, 7) . '@web.com';
    $this->type("edit-mail", $emailId);
    $this->type("edit-pass-pass1", "Test12345");
    $this->type("edit-pass-pass2", "Test12345");

    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    //Address Details
    $this->type("street_address-1", "902C El Camino Way SW");
    $this->type("city-1", "Dumfries");
    $this->type("postal_code-1", "1234");
    $this->select("state_province-1", "value=1019");

    $this->click("edit-submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    return $name;
  }

}

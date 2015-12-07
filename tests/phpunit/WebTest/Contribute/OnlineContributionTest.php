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

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //login to check contribution

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Contribution
    $this->openCiviPage("contribute/search", "reset=1", "contribution_date_low");
    $this->type("sort_name", "$lastName $firstName");
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Contribution is a Test?')]/../../td[2]/label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->clickLink("_qf_Search_refresh", "xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']");
    $this->clickLink("xpath=xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", "_qf_ContributionView_cancel-bottom", FALSE);

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
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", "_qf_ContributionView_cancel-bottom", FALSE);

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

}

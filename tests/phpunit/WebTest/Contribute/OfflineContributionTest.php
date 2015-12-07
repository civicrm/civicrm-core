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
 * Class WebTest_Contribute_OfflineContributionTest
 */
class WebTest_Contribute_OfflineContributionTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testStandaloneContributeAdd() {
    $this->webtestLogin();

    // Create a contact to be used as soft creditor
    $softCreditFname = substr(sha1(rand()), 0, 7);
    $softCreditLname = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($softCreditFname, $softCreditLname, FALSE);

    //financial account for check
    $this->openCiviPage("admin/options/payment_instrument", "reset=1");
    $financialAccount = $this->getText("xpath=//div[@id='payment_instrument']/table/tbody//tr/td[1]/div[text()='Check']/../../td[3]");

    // Add new Financial Account
    $orgName = 'Alberta ' . substr(sha1(rand()), 0, 7);
    $financialAccountTitle = 'Financial Account ' . substr(sha1(rand()), 0, 4);
    $financialAccountDescription = "{$financialAccountTitle} Description";
    $accountingCode = 1033;
    $financialAccountType = 'Asset';
    $taxDeductible = FALSE;
    $isActive = FALSE;
    $isTax = TRUE;
    $taxRate = 9;
    $isDefault = FALSE;

    //Add new organisation
    if ($orgName) {
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

    $firstName = 'John' . substr(sha1(rand()), 0, 7);
    $lastName = 'Dsouza' . substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, $lastName);

    $this->waitForElementPresent("css=li#tab_contribute a");
    $this->click("css=li#tab_contribute a");
    $this->waitForElementPresent("link=Record Contribution (Check, Cash, EFT ...)");
    $this->clickLink("link=Record Contribution (Check, Cash, EFT ...)", "_qf_Contribution_cancel-bottom", FALSE);

    // select financial type
    $this->select("financial_type_id", "value=1");

    // fill in Received Date
    $this->webtestFillDate('receive_date');

    // source
    $this->type("source", "Mailer 1");

    // total amount
    $this->type("total_amount", "100");

    // select payment instrument type = Check and enter chk number
    $this->select("payment_instrument_id", "value=4");
    $this->waitForElementPresent("check_number");
    $this->type("check_number", "check #1041");

    $this->type("trxn_id", "P20901X1" . rand(100, 10000));

    // create first soft credit
    $this->click("softCredit");
    $this->waitForElementPresent("soft_credit_amount_1");
    $this->webtestFillAutocomplete("{$softCreditLname}, {$softCreditFname}", 's2id_soft_credit_contact_id_1');
    $this->type("soft_credit_amount_1", "50");

    // add second soft credit field
    $this->click("addMoreSoftCredit");
    $this->waitForElementPresent("soft_credit_amount_2");
    // create new individual via soft credit
    $softCreditSecondFname = substr(sha1(rand()), 0, 7);
    $softCreditSecondLname = substr(sha1(rand()), 0, 7);
    $this->webtestNewDialogContact($softCreditSecondFname, $softCreditSecondLname, NULL, 4, 's2id_soft_credit_contact_id_2', 'soft_credit_1');
    // enter the second soft credit
    $this->verifyText("soft_credit_amount_2", ""); // it should be blank cause first soft credit != total_amount
    $this->type("soft_credit_amount_2", "100"); //the sum of the soft credit amounts can exceed total_amount
    $this->select("soft_credit_type[2]", "In Honor of");

    //Custom Data
    // $this->click('CIVICRM_QFID_3_6');

    //Additional Detail section
    $this->click("AdditionalDetail");
    $this->waitForElementPresent("thankyou_date");

    $this->type("note", "This is a test note.");
    $this->type("non_deductible_amount", "10.00");
    $this->type("fee_amount", "0");
    $this->type("net_amount", "0");
    $this->type("invoice_id", time());
    $this->webtestFillDate('thankyou_date');

    //Premium section
    $this->click("Premium");
    $this->waitForElementPresent("fulfilled_date");
    $this->select("product_name[0]", "label=Coffee Mug ( MUG-101 )");
    $this->select("product_name[1]", "label=Black");
    $this->webtestFillDate('fulfilled_date');

    // Clicking save.
    $this->click("_qf_Contribution_upload");

    // Is status message correct?
    //$this->assertTrue($this->isTextPresent("The contribution record has been saved."), "Status message didn't show up after saving!");

    // verify if Contribution is created
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[8]/span/a[text()='View']");

    //click through to the Contribution view screen
    $this->click("xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");

    $expected = array(
      'Financial Type' => 'Donation',
      'Total Amount' => '100.00',
      'Contribution Status' => 'Completed',
      'Payment Method' => 'Check',
      'Check Number' => 'check #1041',
      'Non-deductible Amount' => '10.00',
      'Received Into' => $financialAccount,
    );

    $this->waitForElementPresent("xpath=//*[@id='ContributionView']/div[2]");
    foreach ($expected as $value) {
      $this->verifyText("xpath=//*[@id='ContributionView']/div[2]", preg_quote($value));
    }

    // verify if soft credit was created successfully
    $expected = array(
      'Soft Credit To 1' => "{$softCreditFname} {$softCreditLname}",
      'Soft Credit To 2' => "{$softCreditSecondFname} {$softCreditSecondLname}",
      'Amount (Soft Credit Type)' => '100.00 (In Honor of)',
    );

    foreach ($expected as $value) {
      $this->verifyText("css=table.crm-soft-credit-listing", preg_quote($value));
    }

    // go to first soft creditor contact view page
    $this->clickLink("xpath=//*[@id='ContributionView']/div[2]/div[2]/div[2]/table/tbody/tr[1]/td[1]/a", "css=li#tab_contribute a");

    // go to contribution tab
    $this->click("css=li#tab_contribute a");
    $this->waitForElementPresent("link=Record Contribution (Check, Cash, EFT ...)");

    // verify soft credit details
    $expected = array(
      3 => 'Solicited',
      4 => 'Donation',
      2 => '50.00',
      6 => 'Completed',
      1 => "{$firstName} {$lastName}",
    );
    foreach ($expected as $value => $label) {
      $this->verifyText("xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[2]/td[$value]", preg_quote($label));
    }
  }

  public function testDeductibleAmount() {
    $this->webtestLogin();

    // disable verify ssl when using authorize .net
    $this->openCiviPage("admin/setting/url", "reset=1");
    $this->click("id=CIVICRM_QFID_0_verifySSL");
    $this->click("id=_qf_Url_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //add authorize .net payment processor
    $processorName = 'Webtest AuthNet' . substr(sha1(rand()), 0, 7);
    $this->webtestAddPaymentProcessor($processorName, 'AuthNet');

    $this->openCiviPage("admin/contribute/managePremiums", "action=add&reset=1");
    $premiumName = 'test Premium' . substr(sha1(rand()), 0, 7);
    $this->addPremium($premiumName, 'SKU', 3, 12, NULL, NULL);

    $firstName = 'John' . substr(sha1(rand()), 0, 7);
    $lastName = 'Dsouza' . substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, $lastName);

    //scenario 1 : is_deductible = 0 and non deductible amount is entered
    $scenario1 = array(
      'financial_type' => 'Campaign Contribution',
      'total_amount' => 111,
      'non_deductible_amount' => 15,
      'sort_name' => "$lastName, $firstName",
    );
    $this->_doOfflineContribution($scenario1, $firstName, $lastName, $processorName);

    $checkScenario1 = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Campaign Contribution',
      'Total Amount' => 111,
      'Non-deductible Amount' => 15,
      'sort_name' => "$lastName, $firstName",
    );
    $this->_verifyAmounts($checkScenario1);

    //scenario 2 : is_deductible = TRUE and premium is set and premium is greater than total amount
    $scenario2 = array(
      'financial_type' => 'Donation',
      'total_amount' => 10,
      'premium' => "{$premiumName} ( SKU )",
      'sort_name' => "$lastName, $firstName",
    );
    $this->_doOfflineContribution($scenario2, $firstName, $lastName, $processorName);

    $checkScenario2 = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Total Amount' => 10,
      'Non-deductible Amount' => 10,
      'sort_name' => "$lastName, $firstName",
    );
    $this->_verifyAmounts($checkScenario2);

    //scenario 3 : is_deductible = TRUE and premium is set and premium is less than total amount
    $scenario3 = array(
      'financial_type' => 'Donation',
      'total_amount' => 123,
      'premium' => "{$premiumName} ( SKU )",
      'sort_name' => "$lastName, $firstName",
    );
    $this->_doOfflineContribution($scenario3, $firstName, $lastName, $processorName);

    $checkScenario3 = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Total Amount' => 123,
      'Non-deductible Amount' => 12,
      'sort_name' => "$lastName, $firstName",
    );
    $this->_verifyAmounts($checkScenario3);

    //scenario 4 : is_deductible = TRUE and premium is not set
    $scenario4 = array(
      'financial_type' => 'Donation',
      'total_amount' => 123,
      'sort_name' => "$lastName, $firstName",
    );
    $this->_doOfflineContribution($scenario4, $firstName, $lastName, $processorName);

    $checkScenario4 = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Total Amount' => 123,
      'Non-deductible Amount' => '0.00',
      'sort_name' => "$lastName, $firstName",
    );
    $this->_verifyAmounts($checkScenario4);

    //scenario 5 : is_deductible = FALSE, non_deductible_amount = the total amount
    $scenario5 = array(
      'financial_type' => 'Campaign Contribution',
      'total_amount' => 555,
      'sort_name' => "$lastName, $firstName",
    );
    $this->_doOfflineContribution($scenario5, $firstName, $lastName, $processorName);

    $checkScenario5 = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Campaign Contribution',
      'Total Amount' => 555,
      'Non-deductible Amount' => 555,
      'sort_name' => "$lastName, $firstName",
    );
    $this->_verifyAmounts($checkScenario5);
  }

  /**
   * common function for doing offline contribution.
   * @param array $params
   * @param string $firstName
   * @param string $lastName
   * @param $processorName
   */
  public function _doOfflineContribution($params, $firstName, $lastName, $processorName) {

    $this->waitForElementPresent("css=li#tab_contribute a");
    $this->click("css=li#tab_contribute a");
    $this->waitForElementPresent("link=Record Contribution (Check, Cash, EFT ...)");

    // since we don't have live credentials we will switch to test mode
    $url = $this->getAttribute("xpath=//*[@id='Search']/div[2]/div[2]/a[1]@href");
    $url .= '&mode=test';
    $this->open($url);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // start filling out contribution form
    $this->waitForElementPresent('payment_processor_id');
    $this->select('payment_processor_id', "label={$processorName}");

    // select financial type
    $this->select("financial_type_id", "label={$params['financial_type']}");

    // total amount
    $this->type("total_amount", "{$params['total_amount']}");

    // enter credit card info on form
    $this->webtestAddCreditCardDetails();

    // billing address
    $this->webtestAddBillingDetails($firstName, NULL, $lastName);

    if ($nonDeductibleAmt = CRM_Utils_Array::value('non_deductible_amount', $params)) {
      $this->click("AdditionalDetail");
      $this->waitForElementPresent("thankyou_date");
      $this->type("note", "This is a test note.");
      $this->type("non_deductible_amount", "{$nonDeductibleAmt}");
    }

    if (!empty($params['premium'])) {
      //Premium section
      $this->click("Premium");
      $this->waitForElementPresent("fulfilled_date");
      $this->select("product_name[0]", "label={$params['premium']}");
    }
    // Clicking save.
    $this->click("_qf_Contribution_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("The contribution record has been saved."), "Status message didn't show up after saving!");
  }

  /**
   * common function for verifing total_amount, and non_deductible_amount
   * @param $verifyData
   */
  public function _verifyAmounts($verifyData) {
    // since we are doing test contributions we need to search for test contribution and select first contribution
    // record for the contact
    $this->openCiviPage("contribute/search", "reset=1", "contribution_date_low");
    $this->type("sort_name", $verifyData['sort_name']);

    // select show test contributions
    $this->click("contribution_test", "value=1");
    $this->clickLink("_qf_Search_refresh", "xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", FALSE);
    $this->clickLink("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", "_qf_ContributionView_cancel-bottom", FALSE);

    foreach ($verifyData as $label => $value) {
      if ($label == 'sort_name') {
        continue;
      }
      $this->verifyText("xpath=//form[@id='ContributionView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }

    // now find contact and go back to contact summary
    $this->openCiviPage("contact/search", "reset=1", "sort_name");
    $this->type("sort_name", $verifyData['sort_name']);
    $this->clickLink("_qf_Basic_refresh",
      "xpath=//form[@id='Basic']/div[3]/div[1]/div[2]/table/tbody/tr[1]/td[11]/span/a[text()='View']");

    $this->clickLink("xpath=//form[@id='Basic']/div[3]/div[1]/div[2]/table/tbody/tr[1]/td[11]/span/a[text()='View']",
      'crm-contact-actions-link', FALSE);
  }

  public function testOnlineContributionWithZeroAmount() {
    $this->webtestLogin();

    // Create a contact to be used as soft creditor
    $firstName = 'John' . substr(sha1(rand()), 0, 7);
    $lastName = 'Peterson' . substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, $lastName);
    $this->waitForElementPresent("css=li#tab_contribute a");
    $this->click("css=li#tab_contribute a");
    $this->waitForElementPresent("link=Record Contribution (Check, Cash, EFT ...)");
    $this->clickLink("link=Record Contribution (Check, Cash, EFT ...)", "_qf_Contribution_cancel-bottom", FALSE);

    // select financial type
    $this->select("financial_type_id", "value=1");

    // total amount
    $this->type("total_amount", "0.00");

    // select payment instrument
    $this->select("payment_instrument_id", "value=1");

    $this->type("trxn_id", "X20901X1" . rand(100, 10000));
    $this->click('_qf_Contribution_upload-bottom');
    $this->waitForText("crm-notification-container", "The contribution record has been saved.");

    $this->waitForElementPresent("xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[8]/span/a[text()='View']", "_qf_ContributionView_cancel-bottom", FALSE);
    $expected = array(
      'Financial Type' => 'Donation',
      'Total Amount' => '0.00',
      'Contribution Status' => 'Completed',
      'Payment Method' => 'Credit Card',
    );
    $this->webtestVerifyTabularData($expected);
  }

}

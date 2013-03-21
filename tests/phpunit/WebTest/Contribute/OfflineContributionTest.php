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

class WebTest_Contribute_OfflineContributionTest extends CiviSeleniumTestCase {

  protected $captureScreenshotOnFailure = TRUE;
  protected $screenshotPath = '/var/www/api.dev.civicrm.org/public/sc';
  protected $screenshotUrl = 'http://api.dev.civicrm.org/sc/';

  protected function setUp() {
    parent::setUp();
  }

  function testStandaloneContributeAdd() {
    $this->webtestLogin();

    // Create a contact to be used as soft creditor
    $softCreditFname = substr(sha1(rand()), 0, 7);
    $softCreditLname = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact( $softCreditFname, $softCreditLname, false );

    //financial account for check
    $this->openCiviPage("admin/options/payment_instrument", "group=payment_instrument&reset=1");
    $financialAccount = $this->getText("xpath=//div[@id='payment_instrument']/div[2]/table/tbody//tr/td[1][text()='Check']/../td[3]");

    // Add new Financial Account
    $orgName = 'Alberta '.substr(sha1(rand()), 0, 7);
    $financialAccountTitle = 'Financial Account '.substr(sha1(rand()), 0, 4);
    $financialAccountDescription = "{$financialAccountTitle} Description";
    $accountingCode = 1033;
    $financialAccountType = 'Asset';
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

    $firstName = 'John'.substr(sha1(rand()), 0, 7);
    $lastName = 'Dsouza'.substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, $lastName);

    $this->waitForElementPresent("css=li#tab_contribute a");
    $this->click("css=li#tab_contribute a");
    $this->waitForElementPresent("link=Record Contribution (Check, Cash, EFT ...)");
    $this->click("link=Record Contribution (Check, Cash, EFT ...)");
    $this->waitForPageToLoad($this->getTimeoutMsec());

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

    // soft credit
    $this->click("soft_credit_to");
    $this->type("soft_credit_to", $softCreditFname);
    $this->typeKeys("soft_credit_to", $softCreditFname);

    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");

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

    //Honoree section
    $this->click("Honoree");
    $this->waitForElementPresent("honor_email");

    $this->click("CIVICRM_QFID_1_2");
    $this->select("honor_prefix_id", "label=Ms.");
    $this->type("honor_first_name", "Foo");
    $this->type("honor_last_name", "Bar");
    $this->type("honor_email", "foo@bar.com");

    //Premium section
    $this->click("Premium");
    $this->waitForElementPresent("fulfilled_date");
    $this->select("product_name[0]", "label=Coffee Mug ( MUG-101 )");
    $this->select("product_name[1]", "label=Black");
    $this->webtestFillDate('fulfilled_date');

    // Clicking save.
    $this->click("_qf_Contribution_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("The contribution record has been saved."), "Status message didn't show up after saving!");

    // verify if Membership is created
    $this->waitForElementPresent( "xpath=//div[@id='Contributions']//table//tbody/tr[1]/td[8]/span/a[text()='View']" );

    //click through to the Membership view screen
    $this->click( "xpath=//div[@id='Contributions']//table/tbody/tr[1]/td[8]/span/a[text()='View']" );
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");

    $expected = array('Financial Type'   => 'Donation',

      'Total Amount'        => '100.00',
      'Contribution Status' => 'Completed',
      'Paid By'             => 'Check',
      'Check Number'        => 'check #1041',
      'Non-deductible Amount' => '10.00',
      'Received Into'       => $financialAccount,
      'Soft Credit To'      => "{$softCreditFname} {$softCreditLname}"
    );
    foreach($expected as $label => $value) {
      $this->verifyText("xpath=id('ContributionView')/div[2]/table[1]/tbody//tr/td[1][text()='$label']/../td[2]", preg_quote($value));
    }

    // go to soft creditor contact view page
    $this->click("xpath=id('ContributionView')/div[2]/table[1]/tbody//tr/td[1][text()='Soft Credit To']/../td[2]/a[text()='{$softCreditFname} {$softCreditLname}']");

    // go to contribution tab
    $this->waitForElementPresent("css=li#tab_contribute a");
    $this->click("css=li#tab_contribute a");
    $this->waitForElementPresent("link=Record Contribution (Check, Cash, EFT ...)");

    // verify soft credit details
    $expected = array( 3  => 'Donation',

      2  => '100.00',
      5  => 'Completed',
      1  => "{$firstName} {$lastName}"
    );
    foreach($expected as $value => $label) {
      $this->verifyText("xpath=id('Search')/div[2]/table[2]/tbody/tr[2]/td[$value]", preg_quote($label));
    }
  }

  function testDeductibleAmount() {
    $this->webtestLogin();

    //add authorize .net payment processor
    $processorName = 'Webtest AuthNet' . substr(sha1(rand()), 0, 7);
    $this->webtestAddPaymentProcessor($processorName, 'AuthNet');

    $this->openCiviPage("admin/contribute/managePremiums", "action=add&reset=1");
    $premiumName = 'test Premium' . substr(sha1(rand()), 0, 7);
    $this->addPremium($premiumName, 'SKU', 3, 12, NULL, NULL);

    $firstName = 'John'.substr(sha1(rand()), 0, 7);
    $lastName = 'Dsouza'.substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, $lastName);

    //scenario 1 : is_deductible = 0 and non deductible amount is entered
    $scenario1 = array(
      'financial_type' => 'Campaign Contribution',
      'total_amount' => 111,
      'non_deductible_amount' => 15
    );
    $this->_doOfflineContribution($scenario1, $firstName, $lastName, $processorName);

    $checkScenario1 = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Campaign Contribution',
      'Total Amount' => 111,
      'Non-deductible Amount' => 15
    );
    $this->_verifyAmounts($checkScenario1);

    //scenario 2 : is_deductible = TRUE and premium is set and premium is greater than total amount
    $scenario2 = array(
      'financial_type' => 'Donation',
      'total_amount' => 10,
      'premium' => "{$premiumName} ( SKU )"
    );
    $this->_doOfflineContribution($scenario2, $firstName, $lastName, $processorName);

    $checkScenario2 = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Total Amount' => 10,
      'Non-deductible Amount' => 10
    );
    $this->_verifyAmounts($checkScenario2);

    //scenario 3 : is_deductible = TRUE and premium is set and premium is less than total amount
    $scenario3 = array(
      'financial_type' => 'Donation',
      'total_amount' => 123,
      'premium' => "{$premiumName} ( SKU )"
    );
    $this->_doOfflineContribution($scenario3, $firstName, $lastName, $processorName);

    $checkScenario3 = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Total Amount' => 123,
      'Non-deductible Amount' => 12
    );
    $this->_verifyAmounts($checkScenario3);

    //scenario 4 : is_deductible = TRUE and premium is not set
    $scenario4 = array(
      'financial_type' => 'Donation',
      'total_amount' => 123,
    );
    $this->_doOfflineContribution($scenario4, $firstName, $lastName, $processorName);

    $checkScenario4 = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Total Amount' => 123,
      'Non-deductible Amount' => '0.00'
    );
    $this->_verifyAmounts($checkScenario4);

    //scenario 5 : is_deductible = FALSE, non_deductible_amount = the total amount
    $scenario5 = array(
      'financial_type' => 'Campaign Contribution',
      'total_amount' => 555,
    );
    $this->_doOfflineContribution($scenario5, $firstName, $lastName, $processorName);

    $checkScenario5 = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Campaign Contribution',
      'Total Amount' => 555,
      'Non-deductible Amount' => 555
    );
    $this->_verifyAmounts($checkScenario5);
  }

  //common function for doing offline contribution
  function _doOfflineContribution($params, $firstName, $lastName, $processorName) {

    $this->waitForElementPresent("css=li#tab_contribute a");
    $this->click("css=li#tab_contribute a");
    $this->waitForElementPresent("link=Submit Credit Card Contribution");
    $this->click("link=Submit Credit Card Contribution");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // since we don't have live credentials we will switch to test mode
    $url = $this->getLocation();
    $url = str_replace('mode=live', 'mode=test', $url);
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

    if (CRM_Utils_Array::value('premium', $params)) {
      //Premium section
      $this->click("Premium");
      $this->waitForElementPresent("fulfilled_date");
      $this->select("product_name[0]", "label={$params['premium']}");
    }
    // Clicking save.
    $this->click("_qf_Contribution_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("The contribution record has been processed."), "Status message didn't show up after saving!");
  }

  //common function for verifing total_amount, and non_deductible_amount
  function _verifyAmounts($verifyData) {
    $this->waitForElementPresent( "xpath=//div[@id='Contributions']//table//tbody/tr[1]/td[8]/span/a[text()='View']" );
    $this->click( "xpath=//div[@id='Contributions']//table/tbody/tr[1]/td[8]/span/a[text()='View']" );
    $this->waitForPageToLoad($this->getTimeoutMsec());

    foreach ($verifyData as $label => $value) {
      $this->verifyText("xpath=//form[@id='ContributionView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }

    $this->click("_qf_ContributionView_cancel-top");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }
}

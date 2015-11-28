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
 * Class WebTest_Contribute_OnlineMultiplePaymentProcessorTest
 */
class WebTest_Contribute_OnlineMultiplePaymentProcessorTest extends CiviSeleniumTestCase {
  protected function setUp() {
    parent::setUp();
  }

  public function testOnlineMultpiplePaymentProcessor() {

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $proProcessorName = "Pro " . substr(sha1(rand()), 0, 7);
    $standardProcessorName = "Standard " . substr(sha1(rand()), 0, 7);
    $donationPageTitle = "Donation" . substr(sha1(rand()), 0, 7);
    $pageId = $this->webtestAddContributionPage($hash = NULL,
      $rand = NULL,
      $pageTitle = $donationPageTitle,
      $processor = array($proProcessorName => 'Dummy', $standardProcessorName => 'PayPal_Standard'),
      $amountSection = TRUE,
      $payLater = TRUE,
      $onBehalf = FALSE,
      $pledges = TRUE,
      $recurring = FALSE,
      $membershipTypes = FALSE,
      $memPriceSetId = NULL,
      $friend = FALSE,
      $profilePreId = 1,
      $profilePostId = NULL,
      $premiums = FALSE,
      $widget = FALSE,
      $pcp = FALSE,
      $isAddPaymentProcessor = TRUE,
      $isPcpApprovalNeeded = FALSE,
      $isSeparatePayment = FALSE,
      $honoreeSection = FALSE,
      $allowOtherAmmount = TRUE
    );

    $this->openCiviPage("contribute/transact", "reset=1&action=preview&id=$pageId", NULL);
    $this->waitForTextPresent($donationPageTitle);

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);

    $this->type("email-5", $firstName . "@example.com");

    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    $this->type("xpath=//div[@class='crm-section other_amount-section']//div[2]/input", 100);

    $streetAddress = "100 Main Street";
    $this->type("street_address-1", $streetAddress);
    $this->type("city-1", "San Francisco");
    $this->type("postal_code-1", "94117");
    $this->select("country-1", "value=1228");
    $this->select("state_province-1", "value=1001");

    $this->assertTrue($this->isTextPresent("Payment Method"));
    $xpath = "xpath=//label[text() = '{$proProcessorName}']/preceding-sibling::input[1]";
    $this->click($xpath);

    $this->waitForElementPresent("credit_card_type");

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

  }

  public function testOnlineMultiplePaymentProcessorWithPayLater() {

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $proProcessorName = "Pro " . substr(sha1(rand()), 0, 7);
    $standardProcessorName = "Standard " . substr(sha1(rand()), 0, 7);
    $donationPageTitle = "Donation" . substr(sha1(rand()), 0, 7);
    $hash = substr(sha1(rand()), 0, 7);
    $pageId = $this->webtestAddContributionPage($hash,
      $rand = NULL,
      $pageTitle = $donationPageTitle,
      $processor = array($proProcessorName => 'Dummy'),
      $amountSection = TRUE,
      $payLater = TRUE,
      $onBehalf = FALSE,
      $pledges = TRUE,
      $recurring = FALSE,
      $membershipTypes = FALSE,
      $memPriceSetId = NULL,
      $friend = FALSE,
      $profilePreId = 1,
      $profilePostId = NULL,
      $premiums = FALSE,
      $widget = FALSE,
      $pcp = FALSE,
      $isAddPaymentProcessor = TRUE,
      $isPcpApprovalNeeded = FALSE,
      $isSeparatePayment = FALSE,
      $honoreeSection = FALSE,
      $allowOtherAmount = TRUE
    );

    $this->openCiviPage("contribute/transact", "reset=1&action=preview&id=$pageId", NULL);
    $this->waitForTextPresent($donationPageTitle);

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);

    $this->type("email-5", $firstName . "@example.com");

    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    $this->type("xpath=//div[@class='crm-section other_amount-section']//div[2]/input", 100);

    $streetAddress = "100 Main Street";
    $this->type("street_address-1", $streetAddress);
    $this->type("city-1", "San Francisco");
    $this->type("postal_code-1", "94117");
    $this->select("country-1", "value=1228");
    $this->select("state_province-1", "value=1001");

    $this->assertTrue($this->isTextPresent("Payment Method"));
    $payLaterText = "Pay later label $hash";
    $xpath = "xpath=//label[text() = '{$payLaterText}']/preceding-sibling::input[1]";
    $this->click($xpath);

    $this->waitForAjaxContent();
    $this->click("_qf_Main_upload-bottom");
    $this->waitForElementPresent("xpath=//div[@class='bold pay_later_receipt-section']");

    $payLaterInstructionsText = "Pay later instructions $hash";
    $this->verifyText("xpath=//div[@class='bold pay_later_receipt-section']/p", $payLaterInstructionsText);
    $this->click("_qf_Confirm_next-bottom");

    $this->waitForElementPresent("xpath=//div[@class='help']/div/p");
    $this->verifyText("xpath=//div[@class='help']/div/p", $payLaterInstructionsText);

    //login to check contribution
    $this->openCiviPage("contribute/search", "reset=1", 'contribution_date_low');

    $this->type('sort_name', "$lastName $firstName");
    $this->check('contribution_test');
    $this->click('_qf_Search_refresh');
    $this->waitForElementPresent("xpath=//div[@id='contributionSearch']/table/tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->click("xpath=//div[@id='contributionSearch']/table/tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");
    $expected = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Contribution Status' => 'Pending : Pay Later',
    );
    $this->webtestVerifyTabularData($expected);
    $this->click('_qf_ContributionView_cancel-bottom');
  }

}

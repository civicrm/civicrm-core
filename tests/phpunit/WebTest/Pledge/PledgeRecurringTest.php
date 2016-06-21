<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
class WebTest_Pledge_PledgeRecurringTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testOnlinePledgeRecurringAdd() {
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
    $pledges = TRUE;
    $isPledgeStart = TRUE;
    $recurring = FALSE;
    $memberships = FALSE;
    $friend = FALSE;
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
      $pcp,
      TRUE,
      FALSE,
      FALSE,
      FALSE,
      TRUE,
      TRUE,
      'Donation',
      TRUE,
      FALSE,
      $isPledgeStart
    );

    //logout
    $this->webtestLogout();

    //Open Live Contribution Page
    $this->openCiviPage("contribute/transact", "reset=1&id=$pageId&action=preview", "_qf_Main_upload-bottom");

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);

    $this->type("email-5", $firstName . "@example.com");
    $this->click("CIVICRM_QFID_1_is_pledge");
    $this->type("pledge_frequency_interval", 1);
    $this->type("pledge_installments", 5);

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
    $this->waitForElementPresent("xpath=//div[@class='crm-group billing_name_address-group']//div[@class='crm-section no-label billing_name-section']");
    $this->assertElementContainsText("xpath=//div[@class='crm-group billing_name_address-group']//div[@class='crm-section no-label billing_name-section']", $firstName . "billing");
    $this->assertElementContainsText("xpath=//div[@class='crm-group billing_name_address-group']//div[@class='crm-section no-label billing_name-section']", $lastName . "billing");

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
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Contribution is Recurring?')]/../../td[2]/label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->clickLink("_qf_Search_refresh", "xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", FALSE);
    $this->clickLink("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", "_qf_ContributionView_cancel-bottom", FALSE);

    // View Recurring Contribution Record
    $verifyData = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation (test)',
      'Contribution Status' => 'Completed',
      'Payment Method' => 'Credit Card (Test Processor)',
      'Online Contribution Page' => $pageTitle,
      'Fee Amount' => "$ 1.50",
      'Net Amount' => "$ 98.50",
    );
    foreach ($verifyData as $label => $value) {
      $this->verifyText("xpath=//form[@id='ContributionView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }
  }

}

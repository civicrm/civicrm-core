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
 * Class WebTest_Contribute_OfflineRecurContributionTest
 */
class WebTest_Contribute_OfflineRecurContributionTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testOfflineRecurContribution() {
    $this->webtestLogin();

    // We need a payment processor
    $processorName = 'Webtest AuthNet' . substr(sha1(rand()), 0, 7);
    $this->webtestAddPaymentProcessor($processorName, 'AuthNet');

    // create a new contact for whom recurring contribution is to be created
    $firstName = 'Jane' . substr(sha1(rand()), 0, 7);
    $middleName = 'Middle';
    $lastName = 'Recuroff_' . substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, $lastName, "{$firstName}@example.com");
    $contactName = "$firstName $lastName";

    $this->click('css=li#tab_contribute a');

    $this->waitForElementPresent('link=Record Contribution (Check, Cash, EFT ...)');
    // since we don't have live credentials we will switch to test mode
    $url = $this->getAttribute("xpath=//*[@id='Search']/div[2]/div[2]/a[1]@href");
    $url .= '&mode=test';
    $this->open($url);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // start filling out contribution form
    $this->waitForElementPresent('payment_processor_id');
    $this->select('payment_processor_id', "label={$processorName}");

    $this->click('financial_type_id');
    $this->select('financial_type_id', 'label=Donation');
    $this->type('total_amount', '10');

    // recurring contribution fields
    $this->click('is_recur');
    $this->type('frequency_interval', '1');
    $this->select('frequency_unit', 'label=month(s)');
    $this->type('installments', '12');

    $this->click('is_email_receipt');
    $this->waitForElementPresent('credit_card_type');

    // enter credit card info on form
    $this->webtestAddCreditCardDetails();

    // billing address
    $this->webtestAddBillingDetails($firstName, $middleName, $lastName);
    $this->click('_qf_Contribution_upload-bottom');
    $this->waitForElementPresent('link=Edit');
    $this->waitForText('crm-notification-container', "The contribution record has been saved.");
    // Use Find Contributions to make sure test recurring contribution exists
    $this->openCiviPage("contribute/search", "reset=1", 'contribution_currency_type');

    $this->type('sort_name', "$lastName, $firstName");
    $this->click('contribution_test');
    $this->click('_qf_Search_refresh');

    $this->waitForElementPresent('css=#contributionSearch table tbody tr td span a.action-item:first-child');
    $this->click('css=#contributionSearch table tbody tr td span a.action-item:first-child');
    $this->waitForElementPresent('_qf_ContributionView_cancel-bottom');

    // View Recurring Contribution Record
    $verifyData = array(
      'From' => "$contactName",
      'Financial Type' => 'Donation (test)',
      'Total Amount' => 'Installments: 12, Interval: 1 month(s)',
      'Contribution Status' => 'Pending : Incomplete Transaction',
      'Payment Method' => 'Credit Card',
    );

    foreach ($verifyData as $label => $value) {
      $this->assertElementContainsText("xpath=//form[@id='ContributionView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td", $value);
    }
  }

}

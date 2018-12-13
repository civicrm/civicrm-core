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
 * Class WebTest_Contribute_OnlineRecurContributionTest
 */
class WebTest_Contribute_OnlineRecurContributionTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testOnlineRecurContribution() {
    require_once 'ContributionPageAddTest.php';

    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = $contributionAmount = 2 * rand(2, 50);
    $pageTitle = 'Donate Online Recurring ' . $hash;
    $processorType = 'AuthNet';
    $processorName = "Webtest AuthNet " . substr(sha1(rand()), 0, 7);
    $amountSection = TRUE;
    $payLater = FALSE;
    $onBehalf = FALSE;
    $pledges = FALSE;
    $recurring = TRUE;
    $memberships = FALSE;
    $memPriceSetId = NULL;
    $friend = TRUE;
    $profilePreId = NULL;
    $profilePostId = NULL;
    $premiums = FALSE;
    $widget = FALSE;
    $pcp = FALSE;

    // open browser, login
    $this->webtestLogin('admin');

    // create a new online contribution page with recurring enabled (using a newly created AuthNet processor)
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

    //now do the test online recurring contribution as an anonymous user.
    $anonymous = TRUE;
    $firstName = 'Jane' . substr(sha1(rand()), 0, 7);
    $middleName = 'Middle';
    $lastName = 'Recuron_' . substr(sha1(rand()), 0, 7);
    $email = $firstName . '@example.com';
    $contactName = "$firstName $lastName";

    // logout
    $this->webtestLogout();

    $this->openCiviPage("contribute/transact", "reset=1&action=preview&id=$pageId", "_qf_Main_upload-bottom");

    // helper AddContributionPage sets Minimum Other Amout = $rand / 2 so must contribute more than that
    $this->click("xpath=//div[@class='crm-section other_amount-section']//div[2]/input");
    $this->type("xpath=//div[@class='crm-section other_amount-section']//div[2]/input", $contributionAmount);

    // recurring contribution - each month for 12 months
    $this->click("is_recur");
    $this->type("frequency_interval", "1");
    $this->type("installments", "12");

    $this->type("email-5", $email);

    $this->webtestAddCreditCardDetails();
    $this->webtestAddBillingDetails($firstName, $middleName, $lastName);
    $this->click("_qf_Main_upload-bottom");

    // Confirmation page
    $this->waitForElementPresent("_qf_Confirm_next-bottom");
    $text = 'I want to contribute this amount every month for 12 installments.';
    $this->assertTrue($this->isTextPresent($text), 'Missing recurring contribution text (confirmation): ' . $text);
    $text = $rand;
    $this->assertTrue($this->isTextPresent($contributionAmount), 'Missing contribution amount (confirmation): ' . $contributionAmount);
    $this->click("_qf_Confirm_next-bottom");

    // Thank-you page
    $this->waitForElementPresent("thankyou_footer");
    $this->assertTrue($this->isElementPresent('tell-a-friend'), 'Missing tell-a-friend div');
    $text = 'This recurring contribution will be automatically processed every month for a total 12 installments';
    $this->assertTrue($this->isTextPresent($text), 'Missing recurring contribution text (thank-you): ' . $text);
    $this->assertTrue($this->isTextPresent($contributionAmount), 'Missing contribution amount (thank-you): ' . $contributionAmount);

    // Log back in and verify that test contribution has been recorded
    $this->webtestLogin();
    $this->openCiviPage("contribute/search", "reset=1", "contribution_currency_type");

    $this->type("sort_name", "{$lastName}, {$firstName}");
    $this->click("contribution_test");
    $this->click("_qf_Search_refresh");

    $this->waitForElementPresent('css=#contributionSearch table tbody tr td a.action-item:first-child');
    $this->click('css=#contributionSearch table tbody tr td a.action-item:first-child');
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");

    // View Recurring Contribution Record
    $verifyData = array(
      'From' => "$contactName",
      'Financial Type' => 'Donation (test)',
      'Total Amount' => 'Installments: 12, Interval: 1 month(s)',
      'Contribution Status' => 'Pending : Incomplete Transaction',
      'Payment Method' => 'Credit Card',
      'Online Contribution Page' => $pageTitle,
    );
    foreach ($verifyData as $label => $value) {
      $this->verifyText("xpath=//form[@id='ContributionView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }
  }

}

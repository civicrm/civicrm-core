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
 * Class WebTest_Member_OnlineMembershipCreateTest
 */
class WebTest_Member_OnlineMembershipCreateTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testOnlineMembershipCreate() {
    //check for online contribution and profile listings permissions
    $permissions = array("edit-1-make-online-contributions", "edit-1-profile-listings-and-forms");
    $this->changePermissions($permissions);

    // Log in as normal user
    $this->webtestLogin();

    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);

    // Use default payment processor
    $processorName = 'Test Processor';

    // create contribution page with randomized title and default params
    $amountSection = TRUE;
    $payLater = TRUE;
    $allowOtherAmount = FALSE;
    $onBehalf = FALSE;
    $pledges = FALSE;
    $recurring = FALSE;
    $memberships = TRUE;
    $memPriceSetId = NULL;
    $friend = TRUE;
    $profilePreId = 1;
    $profilePostId = NULL;
    $premiums = TRUE;
    $widget = FALSE;
    $pcp = TRUE;
    $isSeparatePayment = TRUE;
    $contributionTitle = "Title $hash";
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $contributionTitle,
      array($processorName => 'Dummy'),
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
      $isSeparatePayment,
      TRUE,
      FALSE
    );

    // create two new membership types
    $memTypeParams1 = $this->webtestAddMembershipType();
    $memTypeTitle1 = $memTypeParams1['membership_type'];
    $memTypeId1 = explode('&id=', $this->getAttribute("xpath=//div[@id='membership_type']/table/tbody//tr/td[1]/div[text()='{$memTypeTitle1}']/../../td[12]/span/a[3]@href"));
    $memTypeId1 = $memTypeId1[1];

    $memTypeParams2 = $this->webtestAddMembershipType();
    $memTypeTitle2 = $memTypeParams2['membership_type'];
    $memTypeId2 = explode('&id=', $this->getAttribute("xpath=//div[@id='membership_type']/table/tbody//tr/td[1]/div[text()='{$memTypeTitle2}']/../../td[12]/span/a[3]@href"));
    $memTypeId2 = $memTypeId2[1];

    // edit contribution page memberships tab to add two new membership types
    $this->openCiviPage("admin/contribute/membership", "reset=1&action=update&id={$pageId}", '_qf_MembershipBlock_next-bottom');
    $this->click("membership_type_$memTypeId1");
    $this->click("membership_type_$memTypeId2");
    $this->clickLink('_qf_MembershipBlock_next', '_qf_MembershipBlock_next-bottom');
    $text = "'MembershipBlock' information has been saved.";
    $this->waitForText('crm-notification-container', $text);

    //logout
    $this->webtestLogout();

    // signup for membership 1
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);

    $this->_testOnlineMembershipSignup($pageId, $memTypeTitle1, $firstName, $lastName, $payLater, $hash);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Member
    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");
    $this->click("xpath=//tr/td[1]/p/label[contains(text(),'Membership is a Test?')]/../label[contains(text(),'Yes')]/preceding-sibling::input[1]");
    $this->type("sort_name", "$lastName $firstName");
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='memberSearch']/table/tbody/tr");
    $this->click("xpath=//table[@class='selector row-highlight']/tbody/tr/td[11]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    //View Membership Record
    $verifyData = array(
      'Member' => $firstName . ' ' . $lastName,
      'Membership Type' => $memTypeTitle1,
      'Source' => 'Online Contribution:' . ' ' . $contributionTitle,
      'Status' => 'Pending',
    );
    $this->webtestVerifyTabularData($verifyData);

    // Click View action link on associated contribution record
    $this->waitForElementPresent("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[1][text()='View']");
    $this->click("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[1][text()='View']");
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");
    //View Contribution Record
    $verifyData = array(
      'From' => $firstName . ' ' . $lastName,
      'Total Amount' => '$ 100.00',
      'Contribution Status' => 'Pending : Pay Later',
    );
    $this->webtestVerifyTabularData($verifyData);

    //CRM-15735 - verify membership dates gets changed w.r.t receive_date of contribution.
    $receiveDate = date('F jS, Y', strtotime("-1 month"));
    $endDate = date('F jS, Y', strtotime("+1 year -1 month -1 day"));
    $this->clickAjaxLink("xpath=//button//span[contains(text(),'Edit')]", 'receive_date');
    $this->select('contribution_status_id', 'Completed');
    $this->webtestFillDate('receive_date', '-1 month');
    $this->clickAjaxLink("xpath=//button//span[contains(text(),'Save')]", "xpath=//div[@class='ui-dialog-buttonset']/button[3]/span[2]");
    $updatedData = array(
      'Status' => 'New',
      'Member Since' => $receiveDate,
      'Start date' => $receiveDate,
      'End date' => $endDate,
    );
    $this->webtestVerifyTabularData($updatedData);

    // CRM-8141 signup for membership 2 with same anonymous user info (should create 2 separate membership records because membership orgs are different)
    //logout
    $this->webtestLogout();

    $this->_testOnlineMembershipSignup($pageId, $memTypeTitle2, $firstName, $lastName, $payLater, $hash);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Member
    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$lastName $firstName");
    $this->click("xpath=//tr/td[1]/p/label[contains(text(),'Membership is a Test?')]/../label[contains(text(),'Yes')]/preceding-sibling::input[1]");
    $this->click("_qf_Search_refresh");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('search-status', '2 Results', 'Missing text: ' . '2 Results');
  }

  /**
   * Test online membership signup.
   *
   * @param int $pageId
   * @param int $memTypeId
   * @param string $firstName
   * @param string $lastName
   * @param bool $payLater
   * @param string $hash
   * @param bool $otherAmount
   * @param bool $amountSection
   * @param bool $freeMembership
   */
  public function _testOnlineMembershipSignup($pageId, $memTypeId, $firstName, $lastName, $payLater, $hash, $otherAmount = FALSE, $amountSection = TRUE, $freeMembership = FALSE, $onBehalf = FALSE, $onBehalfParams = array()) {
    //Open Live Contribution Page
    $this->openCiviPage("contribute/transact", "reset=1&id=$pageId&action=preview", "_qf_Main_upload-bottom");
    // Select membership type 1
    $this->waitForElementPresent("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label");
    if ($memTypeId != 'No thank you') {
      $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]//div/span/label/span[1][contains(text(),'$memTypeId')]");
    }
    else {
      $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label[contains(text(),'$memTypeId')]");
    }
    if (!$otherAmount && $amountSection) {
      $this->click("xpath=//div[@class='crm-section contribution_amount-section']/div[2]//span/label[text()='No thank you']");
    }
    elseif ($amountSection) {
      $this->clickAt("xpath=//div[@class='content other_amount-content']/input");
      $this->keyDown("xpath=//div[@class='content other_amount-content']/input", " ");
      $this->type("xpath=//div[@class='content other_amount-content']/input", $otherAmount);
      $this->typeKeys("xpath=//div[@class='content other_amount-content']/input", $otherAmount);
    }
    if ($payLater) {
      $this->waitForAjaxContent();
      $this->click("xpath=//label[text()='Pay later label {$hash}']");
    }
    if ($onBehalf && $onBehalfParams) {
      $this->type("onbehalf[organization_name]", $onBehalfParams['org_name']);
      $this->type("onbehalf[phone-3-1]", $onBehalfParams['org_phone']);
      $this->type("onbehalf[email-3]", $onBehalfParams['org_email']);
      $this->type("onbehalf[street_address-3]", "100 Main Street");
      $this->type("onbehalf[city-3]", "San Francisco");
      $this->type("onbehalf[postal_code-3]", $onBehalfParams['org_postal_code']);
      $this->select("onbehalf[country-3]", "value=1228");
      $this->select("onbehalf[state_province-3]", "value=1001");
    }
    $this->type("email-5", $firstName . "@example.com");
    $this->waitForElementPresent("first_name");
    $this->type("first_name", $firstName);
    $this->waitForElementPresent("last_name");
    $this->type("last_name", $lastName);

    $streetAddress = "100 Main Street";
    $this->waitForElementPresent("street_address-1");
    $this->type("street_address-1", $streetAddress);
    $this->type("city-1", "San Francisco");
    $this->type("postal_code-1", "94117");
    $this->select("country-1", "value=1228");
    $this->select("state_province-1", "value=1001");

    if ($freeMembership) {
      $this->waitForElementPresent("xpath=//div[@id='payment_information'][@style='display: none;']");
    }
    else {
      if (!$payLater && $amountSection) {
        $this->click("xpath=//label[text()='Test Processor']");
        $this->waitForAjaxContent();
        //Credit Card Info
        $this->select("credit_card_type", "value=Visa");
        $this->type("credit_card_number", "4111111111111111");
        $this->type("cvv2", "000");
        $this->select("credit_card_exp_date[M]", "value=1");
        $this->select("credit_card_exp_date[Y]", "value=2020");

        //Billing Info
        $this->waitForElementPresent("billing_first_name");
        $this->type("billing_first_name", $firstName . "billing");
        $this->waitForElementPresent("billing_last_name");
        $this->type("billing_last_name", $lastName . "billing");
        $this->type("billing_street_address-5", "15 Main St.");
        $this->type(" billing_city-5", "San Jose");
        $this->select("billing_country_id-5", "value=1228");
        $this->select("billing_state_province_id-5", "value=1004");
        $this->type("billing_postal_code-5", "94129");
      }
    }
    $this->click("_qf_Main_upload-bottom");
    $this->waitForElementPresent("_qf_Confirm_next-bottom");

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  public function testOnlineMembershipCreateWithContribution() {
    //login with admin credentials & make sure we do have required permissions.
    $permissions = array("edit-1-make-online-contributions", "edit-1-profile-listings-and-forms");
    $this->changePermissions($permissions);

    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    // Use default payment processor
    $processorName = 'Test Processor';
    $amountSection = TRUE;
    $payLater = TRUE;
    $allowOtherAmount = TRUE;
    $onBehalf = FALSE;
    $pledges = FALSE;
    $recurring = FALSE;
    $memberships = TRUE;
    $memPriceSetId = NULL;
    $friend = FALSE;
    $profilePreId = 1;
    $profilePostId = NULL;
    $premiums = FALSE;
    $widget = FALSE;
    $pcp = FALSE;
    $isSeparatePayment = FALSE;
    $membershipsRequired = FALSE;
    $fixedAmount = FALSE;
    $contributionTitle = "Title $hash";
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $contributionTitle,
      array($processorName => 'Dummy'),
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
      $isSeparatePayment,
      TRUE,
      $allowOtherAmount,
      TRUE,
      'Donation',
      $fixedAmount,
      $membershipsRequired
    );
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);

    //logout
    $this->webtestLogout();

    $this->_testOnlineMembershipSignup($pageId, 'No thank you', $firstName, $lastName, FALSE, $hash, 50);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Contribution
    $this->openCiviPage("contribute/search", "reset=1", "contribution_date_low");

    $this->type("sort_name", "$lastName $firstName");
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Contribution is a Test?')]/../../td[2]/label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[10]/span/a[text()='View']");

    // assert financial data - CRM-17863
    $this->waitForElementPresent("xpath=//tr/td[@class='crm-contribution-amount']/a[@title='view payments']");
    $this->click("xpath=//tr/td[@class='crm-contribution-amount']/a[@title='view payments']");
    $this->waitForAjaxContent();
    $verifyFinancialData = array(
      1 => '50.00',
      2 => 'Donation',
      6 => 'Completed',
    );
    foreach ($verifyFinancialData as $col => $data) {
      $this->verifyText("xpath=//tr[@class='crm-child-row']/td/div/table/tbody/tr[2]/td[{$col}]", $data);
    }
    $this->clickLink("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[10]/span/a[text()='View']", "_qf_ContributionView_cancel-bottom", FALSE);

    //View Contribution Record and verify data
    $expected = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Total Amount' => '50.00',
      'Contribution Status' => 'Completed',
      'Received Into' => 'Deposit Bank Account',
      'Source' => "Online Contribution: $contributionTitle",
      'Online Contribution Page' => $contributionTitle,
    );
    $this->webtestVerifyTabularData($expected);
  }

  /**
   * CRM-16302 - To check whether membership, contribution are
   * created for free membership signup.
   */
  public function testOnlineMembershipCreateWithZeroContribution() {
    //login with admin credentials & make sure we do have required permissions.
    $permissions = array("edit-1-make-online-contributions", "edit-1-profile-listings-and-forms");
    $this->changePermissions($permissions);

    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    $amountSection = $payLater = $allowOtherAmount = $pledges = $recurring = FALSE;
    $premiums = $widget = $pcp = $isSeparatePayment = $membershipsRequired = $fixedAmount = $friend = FALSE;
    $memberships = TRUE;
    $memPriceSetId = NULL;
    $onBehalf = TRUE;
    $profilePreId = 1;
    $profilePostId = NULL;
    $contributionTitle = "Title $hash";
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $contributionTitle,
      NULL,
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
      FALSE,
      FALSE,
      $isSeparatePayment,
      FALSE,
      $allowOtherAmount,
      TRUE,
      'Member Dues',
      $fixedAmount,
      $membershipsRequired
    );
    $memTypeParams = $this->webtestAddMembershipType('rolling', 1, 'year', 'no', 0);
    $memTypeTitle = $memTypeParams['membership_type'];
    $memTypeId = explode('&id=', $this->getAttribute("xpath=//div[@id='membership_type']/table/tbody//tr/td[1]/div[text()='{$memTypeTitle}']/../../td[12]/span/a[3]@href"));
    $memTypeId = $memTypeId[1];

    // edit contribution page amounts tab to uncheck real time monetary transaction
    $this->openCiviPage("admin/contribute/membership", "reset=1&action=update&id={$pageId}", '_qf_MembershipBlock_next-bottom');
    $this->click("membership_type_$memTypeId");
    $this->clickLink('_qf_MembershipBlock_next', '_qf_MembershipBlock_next-bottom');
    $text = "'MembershipBlock' information has been saved.";
    $this->waitForText('crm-notification-container', $text);

    $processors = array(
      'Test Processor',
      'AuthNet',
      'PayPal',
      'PayPal_Standard',
    );
    foreach ($processors as $processor) {
      if ($processor == 'Test Processor') {
        $processorName = $processor;
      }
      else {
        $processorName = $processor . substr(sha1(rand()), 0, 7);
        $this->webtestAddPaymentProcessor($processorName, $processor);
      }
      $this->openCiviPage("admin/contribute/amount", "reset=1&action=update&id={$pageId}", '_qf_Amount_upload_done-bottom');
      $this->assertTrue($this->isTextPresent($processorName));
      $this->check("xpath=//label[text() = '{$processorName}']/preceding-sibling::input[1]");
      $this->clickLink('_qf_Amount_upload_done-bottom');
      $this->waitForText('crm-notification-container', "'Amount' information has been saved.");

      $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
      $lastName = 'An' . substr(sha1(rand()), 0, 7);

      //logout
      $this->webtestLogout();

      $this->_testOnlineMembershipSignup($pageId, $memTypeTitle, $firstName, $lastName, $payLater, $hash, $allowOtherAmount, $amountSection, TRUE);

      // Log in using webtestLogin() method
      $this->webtestLogin();

      //Find Contribution
      $this->openCiviPage("contribute/search", "reset=1", "contribution_date_low");
      $this->type("sort_name", "$lastName $firstName");
      $this->click("xpath=//tr/td[1]/label[contains(text(), 'Contribution is a Test?')]/../../td[2]/label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
      $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[10]/span/a[text()='View']");

      // assert financial data - CRM-17863
      $this->waitForElementPresent("xpath=//tr/td[@class='crm-contribution-amount']/a[@title='view payments']");
      $this->click("xpath=//tr/td[@class='crm-contribution-amount']/a[@title='view payments']");
      $this->waitForAjaxContent();
      $verifyFinancialData = array(
        1 => '0.00',
        2 => 'Member Dues',
        3 => 'Credit Card',
        6 => 'Completed',
      );
      foreach ($verifyFinancialData as $col => $data) {
        $this->verifyText("xpath=//tr[@class='crm-child-row']/td/div/table/tbody/tr[2]/td[{$col}]", $data);
      }
      $this->clickLink("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[10]/span/a[text()='View']", "_qf_ContributionView_cancel-bottom", FALSE);

      //View Contribution Record and verify data
      $expected = array(
        'From' => "{$firstName} {$lastName}",
        'Financial Type' => 'Member Dues (test) ',
        'Total Amount' => '0.00',
        'Contribution Status' => 'Completed',
        'Source' => "Online Contribution: $contributionTitle",
        'Online Contribution Page' => $contributionTitle,
      );
      $this->webtestVerifyTabularData($expected);

      //Find Member
      $this->openCiviPage("member/search", "reset=1", "member_end_date_high");
      $this->click("xpath=//tr/td[1]/p/label[contains(text(),'Membership is a Test?')]/../label[contains(text(),'Yes')]/preceding-sibling::input[1]");

      $this->type("sort_name", "$lastName $firstName");
      $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='memberSearch']/table/tbody/tr");
      $this->click("xpath=//table[@class='selector row-highlight']/tbody/tr/td[11]/span/a[text()='View']");
      $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

      //View Membership Record
      $verifyData = array(
        'Member' => $firstName . ' ' . $lastName,
        'Membership Type' => $memTypeTitle,
        'Source' => 'Online Contribution:' . ' ' . $contributionTitle,
        'Status' => 'New',
      );

      $this->webtestVerifyTabularData($verifyData);
    }
  }

  public function testOnlineMembershipCreateOnBehalfWithOrgDedupe() {
    // Add unsupervised dedupe rule.
    $this->webtestLogin();
    $this->openCiviPage("contact/deduperules", "reset=1");
    $this->waitForElementPresent("xpath=//div[@id='option13_wrapper']/table/tbody/tr[2]/td[3]/span/a[2][text()='Edit Rule']");
    $this->click("xpath=//div[@id='option13_wrapper']/table/tbody/tr[2]/td[3]/span/a[2][text()='Edit Rule']");
    $this->waitForElementPresent('_qf_DedupeRules_next');

    $this->type('title', "Postal Code unsupervised dedupe rule");
    $this->click('CIVICRM_QFID_Unsupervised_used');
    $this->select('where_0', "Organization Name");
    $this->type('weight_0', 10);
    $this->select('where_1', "Postal Code");
    $this->type('length_1', 3);
    $this->type('weight_1', 10);
    $this->type('threshold', 20);
    $this->click('_qf_DedupeRules_next');
    $this->webtestLogout();

    //login with admin credentials & make sure we do have required permissions.
    $permissions = array("edit-1-make-online-contributions", "edit-1-profile-listings-and-forms");
    $this->changePermissions($permissions);

    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    $amountSection = $payLater = $allowOtherAmount = $pledges = $recurring = $membershipsRequired = FALSE;
    $premiums = $widget = $pcp = $isSeparatePayment = $fixedAmount = $friend = FALSE;
    $memberships = FALSE;
    $memPriceSetId = NULL;
    $profilePreId = 1;
    $profilePostId = NULL;
    $onBehalf = TRUE;
    $contributionTitle = "Title $hash";
    $pageId = $this->webtestAddContributionPage(
      $hash,
      $rand,
      $contributionTitle,
      NULL,
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
      FALSE,
      FALSE,
      $isSeparatePayment,
      FALSE,
      $allowOtherAmount,
      TRUE,
      'Member Dues',
      $fixedAmount,
      $membershipsRequired
    );
    $memTypeParams = $this->webtestAddMembershipType('rolling', 1, 'year', 'no', 0);
    $memTypeTitle = $memTypeParams['membership_type'];
    $memTypeId = explode('&id=', $this->getAttribute("xpath=//div[@id='membership_type']/table/tbody//tr/td[1]/div[text()='{$memTypeTitle}']/../../td[12]/span/a[3]@href"));
    $memTypeId = $memTypeId[1];

    // edit contribution page amounts tab to uncheck real time monetary transaction
    $this->openCiviPage("admin/contribute/membership", "reset=1&action=update&id={$pageId}", '_qf_MembershipBlock_submit_savenext');
    $this->click('member_is_active');
    $this->waitForElementPresent('displayFee');
    $this->type('new_title', "Title - New Membership $hash");
    $this->type('renewal_title', "Title - Renewals $hash");
    $this->click("membership_type_$memTypeId");
    $this->clickLink('_qf_MembershipBlock_submit_savenext');

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);

    $onBehalfParams = array(
      'org_name' => 'Test Org',
      'org_phone' => '123-456-789',
      'org_email' => 'testorg@test.com',
      'org_postal_code' => 'ABC 123',
    );

    $this->_testOnlineMembershipSignup($pageId, $memTypeTitle, $firstName, $lastName, $payLater, $hash, $allowOtherAmount, $amountSection, TRUE, TRUE, $onBehalfParams);
    $onBehalfParams['org_postal_code'] = 'XYZ 123';
    $this->_testOnlineMembershipSignup($pageId, $memTypeTitle, $firstName, $lastName, $payLater, $hash, $allowOtherAmount, $amountSection, TRUE, TRUE, $onBehalfParams);

    // Log in using webtestLogin() method
    $this->webtestLogin();
  }

}

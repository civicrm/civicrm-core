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
class WebTest_Member_OnlineMembershipCreateTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testOnlineMembershipCreate() {
    //login with admin credentials & make sure we do have required permissions.
    $this->webtestLogin(TRUE);

    //check for online contribution and profile listings permissions
    $permissions = array("edit-1-make-online-contributions", "edit-1-profile-listings-and-forms");
    $this->changePermissions($permissions);

    // now logout and login with admin credentials
    $this->openCiviPage("logout", "reset=1", NULL);

    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // We need a payment processor
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);

    // create contribution page with randomized title and default params

    $amountSection     = TRUE;
    $payLater          = TRUE;
    $allowOtherAmmount = FALSE;
    $onBehalf          = FALSE;
    $pledges           = FALSE;
    $recurring         = FALSE;
    $memberships       = TRUE;
    $memPriceSetId     = NULL;
    $friend            = TRUE;
    $profilePreId      = 1;
    $profilePostId     = NULL;
    $premiums          = TRUE;
    $widget            = FALSE;
    $pcp               = TRUE;
    $isSeparatePayment = TRUE;
    $contributionTitle = "Title $hash";
    $pageId            = $this->webtestAddContributionPage($hash,
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
    $memTypeTitle1  = $memTypeParams1['membership_type'];
    $memTypeId1     = explode('&id=', $this->getAttribute("xpath=//div[@id='membership_type']/div[2]/table/tbody//tr/td[text()='{$memTypeTitle1}']/../td[12]/span/a[3]@href"));
    $memTypeId1     = $memTypeId1[1];

    $memTypeParams2 = $this->webtestAddMembershipType();
    $memTypeTitle2  = $memTypeParams2['membership_type'];
    $memTypeId2     = explode('&id=', $this->getAttribute("xpath=//div[@id='membership_type']/div[2]/table/tbody//tr/td[text()='{$memTypeTitle2}']/../td[12]/span/a[3]@href"));
    $memTypeId2     = $memTypeId2[1];

    // edit contribution page memberships tab to add two new membership types
    $this->openCiviPage("admin/contribute/membership", "reset=1&action=update&id={$pageId}", '_qf_MembershipBlock_next-bottom');
    $this->click("membership_type_$memTypeId1");
    $this->click("membership_type_$memTypeId2");
    $this->click('_qf_MembershipBlock_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_MembershipBlock_next-bottom');
    $text = "'MembershipBlock' information has been saved.";
    $this->assertElementContainsText('crm-notification-container', $text, 'Missing text: ' . $text);
    
    //logout
    $this->openCiviPage("logout", "reset=1", NULL);

    // signup for membership 1
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);

    $this->_testOnlineMembershipSignup($pageId, $memTypeTitle1, $firstName, $lastName, $payLater, $hash);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Member
    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$firstName $lastName");
    $this->click("_qf_Search_refresh");

    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("xpath=//div[@id='memberSearch']/table/tbody/tr");
    $this->click("xpath=//div[@id='memberSearch']/table/tbody/tr/td[11]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    //View Membership Record
    $verifyData = array(
      'Member' => $firstName . ' ' . $lastName,
      'Membership Type' => $memTypeTitle1,
      'Source' => 'Online Contribution:' . ' ' . $contributionTitle,
    );
    if ($payLater) {
      $verifyData['Status'] = 'Pending';
    }
    else { 
      $verifyData['Status'] = 'New';
    }
    $this->webtestVerifyTabularData($verifyData);

    // Click View action link on associated contribution record
    $this->waitForElementPresent("xpath=//form[@id='MembershipView']/div[2]/div/table[@class='selector']/tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->click("xpath=//form[@id='MembershipView']/div[2]/div/table[@class='selector']/tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");
    //View Contribution Record
    $verifyData = array(
      'From' => $firstName . ' ' . $lastName,
      'Total Amount' => '$ 100.00',
    );
    if ($payLater) {
      $verifyData['Contribution Status'] = 'Pending : Pay Later';
    }
    else {
      $verifyData['Contribution Status'] = 'Completed';
    }
    $this->webtestVerifyTabularData($verifyData);

    // CRM-8141 signup for membership 2 with same anonymous user info (should create 2 separate membership records because membership orgs are different)
    //logout
    $this->openCiviPage("logout", "reset=1", NULL);

    $this->_testOnlineMembershipSignup($pageId, $memTypeTitle2, $firstName, $lastName, $payLater, $hash);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Member
    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$firstName $lastName");
    $this->click("_qf_Search_refresh");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('search-status', '2 Results', 'Missing text: ' . '2 Results');
  }

  function _testOnlineMembershipSignup($pageId, $memTypeId, $firstName, $lastName, $payLater, $hash, $otherAmount = FALSE) {
    //Open Live Contribution Page
    $this->openCiviPage("contribute/transact", "reset=1&id=$pageId", "_qf_Main_upload-bottom");
    // Select membership type 1
    $this->waitForElementPresent("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label");
    if ($memTypeId != 'No thank you') {
    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label/span[2][contains(text(),'$memTypeId')]");
    } 
    else {
      $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label[contains(text(),'$memTypeId')]"); 
    }
    if (!$otherAmount) {
      $this->click("xpath=//div[@class='crm-section contribution_amount-section']/div[2]//span/label[text()='No thank you']");
    }
    else {
      $this->type("xpath=//div[@class='content other_amount-content']/input", $otherAmount);
    }
    if ($payLater) {
      $this->click("xpath=//div[@class='crm-section payment_processor-section']/div[2]//label[text()='Pay later label {$hash}']");
    }
    $this->type("email-5", $firstName . "@example.com");

    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    $streetAddress = "100 Main Street";
    $this->type("street_address-1", $streetAddress);
    $this->type("city-1", "San Francisco");
    $this->type("postal_code-1", "94117");
    $this->select("country-1", "value=1228");
    $this->select("state_province-1", "value=1001");
    if (!$payLater) {
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
    }
    $this->click("_qf_Main_upload-bottom");
    $this->waitForElementPresent("_qf_Confirm_next-bottom");

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }
  
  function testOnlineMembershipCreateWithContribution() {
    //login with admin credentials & make sure we do have required permissions.
    $this->webtestLogin(TRUE);
    
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    // We need a payment processor
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);
    $amountSection     = TRUE;
    $payLater          = TRUE;
    $allowOtherAmmount = TRUE;
    $onBehalf          = FALSE;
    $pledges           = FALSE;
    $recurring         = FALSE;
    $memberships       = TRUE;
    $memPriceSetId     = NULL;
    $friend            = FALSE;
    $profilePreId      = 1;
    $profilePostId     = NULL;
    $premiums          = FALSE;
    $widget            = FALSE;
    $pcp               = FALSE;
    $isSeparatePayment = FALSE;
    $membershipsRequired = FALSE;
    $fixedAmount         = FALSE;
    $contributionTitle = "Title $hash";
    $pageId            = $this->webtestAddContributionPage($hash,
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
      $allowOtherAmmount,
      TRUE,
      'Donation',
      $fixedAmount,
      $membershipsRequired
    );
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);

    //logout
    $this->openCiviPage("logout", "reset=1", NULL);

    $this->_testOnlineMembershipSignup($pageId, 'No thank you', $firstName, $lastName, FALSE, $hash, 50);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Contribution
    $this->openCiviPage("contribute/search","reset=1", "contribution_date_low");

    $this->type("sort_name", "$firstName $lastName");
    $this->click("_qf_Search_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->click("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");

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
}
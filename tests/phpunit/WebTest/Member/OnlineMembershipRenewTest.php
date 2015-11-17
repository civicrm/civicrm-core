<?php
/*
   +--------------------------------------------------------------------+
   | CiviCRM version 4.6                                                |
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
 * Class WebTest_Member_OnlineMembershipRenewTest
 */
class WebTest_Member_OnlineMembershipRenewTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   * FIXME: This test tries to update a contribution page (id=2) that may not exist :(
   */
  public function testOnlineMembershipRenew() {
    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    $this->openCiviPage("admin/contribute/amount", "reset=1&action=update&id=2", "_qf_Amount_next");
    // this contribution page for membership signup
    // select newly created processor
    $xpath = "xpath=//label[text() = '{$processorName}']/preceding-sibling::input[1]";
    $this->waitForText('css=.crm-contribution-contributionpage-amount-form-block-payment_processor', $processorName);
    $this->check($xpath);

    // save
    $this->waitForElementPresent("_qf_Amount_next");
    $this->clickLink('_qf_Amount_next');

    // go to Membership block
    $this->click('css=#tab_membership a');
    $this->waitForElementPresent("member_is_active");
    $this->check("member_is_active");

    $this->waitForElementPresent("new_title");

    if ($this->isElementPresent("member_price_set_id")) {
      $this->waitForElementPresent("member_price_set_id");

      $this->select("member_price_set_id", "label=- none -");
    }

    $this->waitForElementPresent("membership_type-block");
    $this->check("xpath=//tr[@id='membership_type-block']/td[2]/table/tbody/tr/td/label[text()='General']/../input[2]");
    $this->check("xpath=//tr[@id='membership_type-block']/td[2]/table/tbody/tr/td/label[text()='Student']/../input[2]");
    $this->click("_qf_MembershipBlock_next-bottom");
    $this->waitForTextPresent("'MembershipBlock' information has been saved");

    // go to Profiles
    $this->click('css=#tab_custom a');

    // fill in Profiles
    $this->waitForElementPresent('custom_pre_id');
    $this->select('css=tr.crm-contribution-contributionpage-custom-form-block-custom_pre_id span.crm-profile-selector-select select', 'value=1');

    // save
    $this->click('_qf_Custom_upload_done');
    $this->waitForPageToLoad();

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $email = $firstName . "@example.com";

    //logout
    $this->webtestLogout();

    //Go to online membership signup page
    $this->openCiviPage("contribute/transact", "reset=1&id=2", "_qf_Main_upload-bottom");

    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]/div[2]/span/label/span[1][contains(text(),'Student')]");

    //Type first name and last name and email
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->type("email-5", $email);
    $this->select("state_province-1", "value=1001");

    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->select("credit_card_type", "label=Visa");
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

    // Log in using webtestLogin() method
    $this->webtestLogin();
    //Find Member
    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$lastName $firstName");
    $this->clickLink("_qf_Search_refresh", 'css=#memberSearch table tbody tr td span a.action-item:first-child');
    $this->click('css=#memberSearch table tbody tr td span a.action-item:first-child');
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    //View Membership Record
    $verifyMembershipData = array(
      'Member' => $firstName . ' ' . $lastName,
      'Membership Type' => 'Student',
      'Status' => 'New',
      'Source' => 'Online Contribution: Member Signup and Renewal',
    );
    foreach ($verifyMembershipData as $label => $value) {
      $this->verifyText("xpath=//form[@id='MembershipView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }

    //logout
    $this->webtestLogout();

    $this->openCiviPage("contribute/transact", "reset=1&id=2", "_qf_Main_upload-bottom");

    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]/div[2]/span/label/span[1][contains(text(),'Student')]");

    //Type first name and last name and email
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->type("email-5", $email);
    $this->select("state_province-1", "value=1001");

    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->select("credit_card_type", "label=Visa");
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

    $this->webtestLogin();
    //Find Member
    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$lastName $firstName");
    $this->clickLink("_qf_Search_refresh", 'css=#memberSearch table tbody tr td span a.action-item:first-child');
    $this->click('css=#memberSearch table tbody tr td span a.action-item:first-child');
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    //View Membership Record
    $verifyMembershipData = array(
      'Member' => $firstName . ' ' . $lastName,
      'Membership Type' => 'Student',
      'Status' => 'New',
      'Source' => 'Online Contribution: Member Signup and Renewal',
    );
    foreach ($verifyMembershipData as $label => $value) {
      $this->verifyText("xpath=//form[@id='MembershipView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }
  }

  /**
   * FIXME: This test tries to update a contribution page (id=2) that may not exist :(
   */
  public function testOnlineMembershipRenewChangeType() {
    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    $this->openCiviPage("admin/contribute/amount", "reset=1&action=update&id=2", '_qf_Amount_next');

    //this contribution page for membership signup
    $xpath = "xpath=//label[text() = '{$processorName}']/preceding-sibling::input[1]";
    $this->waitForText('css=.crm-contribution-contributionpage-amount-form-block-payment_processor', $processorName);
    $this->check($xpath);

    // save
    $this->waitForElementPresent('_qf_Amount_next');
    $this->click('_qf_Amount_next');
    $this->waitForPageToLoad();

    // go to Membership block
    $this->click('css=#tab_membership a');
    $this->waitForElementPresent("member_is_active");
    $this->check("member_is_active");

    $this->waitForElementPresent("new_title");
    if ($this->isElementPresent("member_price_set_id")) {
      $this->waitForElementPresent("member_price_set_id");

      $this->select("member_price_set_id", "label=- none -");
    }

    $this->waitForElementPresent("membership_type-block");
    $this->check("xpath=//tr[@id='membership_type-block']/td[2]/table/tbody/tr/td/label[text()='General']/../input[2]");
    $this->check("xpath=//tr[@id='membership_type-block']/td[2]/table/tbody/tr/td/label[text()='Student']/../input[2]");
    $this->click("_qf_MembershipBlock_next-bottom");
    $this->waitForTextPresent("'MembershipBlock' information has been saved");

    // go to Profiles
    $this->click('css=#tab_custom a');

    // fill in Profiles
    $this->waitForElementPresent('custom_pre_id');
    $this->select('css=tr.crm-contribution-contributionpage-custom-form-block-custom_pre_id span.crm-profile-selector-select select', 'value=1');

    // save
    $this->click('_qf_Custom_upload_done');
    $this->waitForPageToLoad();

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);

    //Go to online membership signup page
    $this->openCiviPage("contribute/transact", "reset=1&id=2", "_qf_Main_upload-bottom");
    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]/div[1]/span/label/span[1][contains(text(),'General')]");
    //Type first name and last name
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    $this->select("state_province-1", "value=1001");
    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->select("credit_card_type", "label=Visa");
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

    //Find Member
    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$lastName $firstName");
    $this->clickLink("_qf_Search_refresh", 'css=#memberSearch table tbody tr td span a.action-item:first-child');
    $this->click('css=#memberSearch table tbody tr td span a.action-item:first-child');
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    $membershipCreatedId = $this->urlArg('id');

    $memberSince = date('F jS, Y');

    //View Membership Record
    $verifyMembershipData = array(
      'Member' => $firstName . ' ' . $lastName,
      'Membership Type' => 'General',
      'Status' => 'New',
      'Source' => 'Online Contribution: Member Signup and Renewal',
    );
    foreach ($verifyMembershipData as $label => $value) {
      $this->verifyText("xpath=//form[@id='MembershipView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }
    $this->openCiviPage("contribute/transact", "reset=1&id=2", "_qf_Main_upload-bottom");
    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]/div[2]/span/label/span[1][contains(text(),'Student')]");

    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->select("credit_card_type", "label=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");
    $this->click("_qf_Confirm_next-bottom");

    //Find Member
    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$lastName $firstName");
    $this->clickLink("_qf_Search_refresh", 'css=#memberSearch table tbody tr td span a.action-item:first-child');
    $this->click('css=#memberSearch table tbody tr td span a.action-item:first-child');
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    $membershipRenewedId = $this->urlArg('id');

    //View Membership Record
    $verifyMembershipData = array(
      'Member' => $firstName . ' ' . $lastName,
      'Membership Type' => 'Student',
      'Status' => 'New',
      'Source' => 'Online Contribution: Member Signup and Renewal',
      'Member Since' => $memberSince,
    );
    foreach ($verifyMembershipData as $label => $value) {
      $this->verifyText("xpath=//form[@id='MembershipView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }
    $this->assertEquals($membershipCreatedId, $membershipRenewedId);
  }

  public function testUpdateInheritedMembershipOnBehalfOfRenewal() {
    // Log in as admin
    $this->webtestLogin('admin');

    $this->enableComponents('CiviMember');

    //check for online contribution and profile listings permissions
    $permissions = array("edit-1-make-online-contributions", "edit-1-profile-listings-and-forms");
    $this->changePermissions($permissions);

    // Log in as normal user
    $this->webtestLogin();

    $this->openCiviPage("contact/add", "reset=1&ct=Organization", '_qf_Contact_cancel');

    $title = substr(sha1(rand()), 0, 7);
    $this->type('organization_name', "Organization $title");
    $this->type('email_1_email', "$title@org.com");
    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForText('crm-notification-container', "Organization $title has been created.");

    $this->openCiviPage("admin/member/membershipType", "reset=1&action=browse");

    $this->click('link=Add Membership Type');
    $this->waitForElementPresent('_qf_MembershipType_cancel-bottom');

    $membershipTypeTitle = "Membership Type $title";
    $this->type('name', "Membership Type $title");

    $this->select2('member_of_contact_id', $title);

    $this->type('minimum_fee', '100');
    $this->select('financial_type_id', 'value=2');
    $this->type('duration_interval', 1);
    $this->select('duration_unit', 'label=year');
    $this->select('period_type', 'value=rolling');

    $this->select2('relationship_type_id', 'Employer of', TRUE);

    $this->click('_qf_MembershipType_upload-bottom');
    $this->waitForElementPresent('link=Add Membership Type');
    $this->waitForText('crm-notification-container', "The membership type 'Membership Type $title' has been saved.");

    $this->waitForElementPresent("xpath=//div[@id='membership_type']/table/tbody//tr/td[1]/div[text()='{$membershipTypeTitle}']/../../td[12]/span/a[3][text()='Delete']/@href");
    $url = $this->getAttribute("xpath=//div[@id='membership_type']/table/tbody//tr/td[1]/div[text()='{$membershipTypeTitle}']/../../td[12]/span/a[3][text()='Delete']/@href");
    $matches = array();
    preg_match('/id=([0-9]+)/', $url, $matches);
    $membershipTypeId = $matches[1];

    // Use default payment processor
    $processorName = 'Test Processor';

    // create contribution page with randomized title and default params
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    $amountSection = FALSE;
    $payLater = FALSE;
    $onBehalf = FALSE;
    $pledges = FALSE;
    $recurring = FALSE;
    $memberships = FALSE;
    $memPriceSetId = NULL;
    $friend = FALSE;
    $profilePreId = 1;
    $profilePostId = NULL;
    $premiums = FALSE;
    $widget = FALSE;
    $pcp = FALSE;

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
      TRUE
    );

    $hash = substr(sha1(rand()), 0, 7);
    $this->openCiviPage("admin/contribute/settings", "reset=1&action=update&id=$pageId");

    $this->click('link=Title');
    $this->waitForElementPresent('_qf_Settings_cancel-bottom');
    $this->click('is_organization');
    $this->select("xpath=//input[@id='onbehalf_profile_id']/parent::td/div/div/span/select", "value=9");
    $this->type('for_organization', "On behalf $hash");
    $this->click('_qf_Settings_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click('link=Memberships');
    $this->waitForElementPresent('_qf_MembershipBlock_cancel-bottom');
    $this->click('member_is_active');
    $this->type('new_title', "Title - New Membership $hash");
    $this->type('renewal_title', "Title - Renewals $hash");
    $this->click("membership_type_{$membershipTypeId}");
    $this->click('is_required');
    $this->click('_qf_MembershipBlock_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //logout
    $this->webtestLogout();

    //get Url for Live Contribution Page
    $this->openCiviPage("contribute/transact", "reset=1&id=$pageId", '_qf_Main_upload-bottom');
    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]/div/span/label/span[1][contains(text(),'$membershipTypeTitle')]");
    $firstName = 'Eia' . substr(sha1(rand()), 0, 4);
    $lastName = 'Ande' . substr(sha1(rand()), 0, 4);
    $name = $firstName . ' ' . $lastName;
    $organisationName = 'TestOrg' . substr(sha1(rand()), 0, 7);

    $email = $firstName . '@example.com';
    $this->type('email-5', $email);
    $this->click('is_for_organization');
    $this->type('onbehalf_organization_name', $organisationName);
    $this->type('onbehalf_phone-3-1', '2222-222222');
    $this->type('onbehalf_email-3', $organisationName . '@example.com');
    $this->type('onbehalf_street_address-3', '54A Excelsior Ave. Apt 1C');
    $this->type('onbehalf_city-3', 'Driftwood');
    $this->type('onbehalf_postal_code-3', '75653');
    $this->select('onbehalf_country-3', "value=1228");
    $this->select('onbehalf_state_province-3', "value=1061");

    $this->type('first_name', $firstName);
    $this->type('last_name', $lastName);
    $this->select("state_province-1", "value=1001");

    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->select("credit_card_type", "label=Visa");
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

    //Find member
    $endDate = date('F jS, Y', strtotime(" +1 year -1 day"));
    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$organisationName");
    $this->clickLink("_qf_Search_refresh", 'css=#memberSearch table tbody tr td span a.action-item:first-child');
    $this->click('css=#memberSearch table tbody tr td span a.action-item:first-child');
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    //View Membership Record
    $verifyMembershipData = array(
      'Member' => $organisationName,
      'Membership Type' => $membershipTypeTitle,
      'Status' => 'New',
      'End date' => $endDate,
    );

    foreach ($verifyMembershipData as $label => $value) {
      $this->verifyText("xpath=//form[@id='MembershipView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }

    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$lastName, $firstName");
    $this->clickLink("_qf_Search_refresh", 'css=#memberSearch table tbody tr td span a.action-item:first-child');
    $this->click('css=#memberSearch table tbody tr td span a.action-item:first-child');
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    //View Membership Record
    $verifyMembershipData = array(
      'Member' => $name,
      'Membership Type' => $membershipTypeTitle,
      'Status' => 'New',
      'End date' => $endDate,
    );

    foreach ($verifyMembershipData as $label => $value) {
      $this->verifyText("xpath=//form[@id='MembershipView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }

    //logout
    $this->webtestLogout();

    $this->openCiviPage("contribute/transact", "reset=1&id=$pageId", "_qf_Main_upload-bottom");

    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]/div/span/label/span[1][contains(text(),'$membershipTypeTitle')]");
    $this->type("email-5", $email);
    $this->click('is_for_organization');
    $this->type('onbehalf_organization_name', $organisationName);
    $this->type('onbehalf_phone-3-1', '2222-222222');
    $this->type('onbehalf_email-3', $organisationName . '@example.com');
    $this->type('onbehalf_street_address-3', '22A Excelsior Ave. Unit 1h');
    $this->type('onbehalf_city-3', 'Driftwood');
    $this->type('onbehalf_postal_code-3', '75653');
    $this->select('onbehalf_country-3', "value=1228");
    $this->select('onbehalf_state_province-3', "value=1061");

    $this->type('first_name', $firstName);
    $this->type('last_name', $lastName);
    $this->select("state_province-1", "value=1001");

    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->select("credit_card_type", "label=Visa");
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

    //Find member
    $endDate = date('F jS, Y', strtotime(" +2 year -1 day"));
    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$organisationName");
    $this->clickLink("_qf_Search_refresh", 'css=#memberSearch table tbody tr td span a.action-item:first-child');
    $this->click('css=#memberSearch table tbody tr td span a.action-item:first-child');
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    //View Membership Record
    $verifyMembershipData = array(
      'Member' => $organisationName,
      'Membership Type' => $membershipTypeTitle,
      'End date' => $endDate,
    );

    foreach ($verifyMembershipData as $label => $value) {
      $this->verifyText("xpath=//form[@id='MembershipView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }

    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$lastName, $firstName");
    $this->clickLink("_qf_Search_refresh", 'css=#memberSearch table tbody tr td span a.action-item:first-child');
    $this->click('css=#memberSearch table tbody tr td span a.action-item:first-child');
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    //View Membership Record
    $verifyMembershipData = array(
      'Member' => $name,
      'Membership Type' => $membershipTypeTitle,
      'End date' => $endDate,
    );

    foreach ($verifyMembershipData as $label => $value) {
      $this->verifyText("xpath=//form[@id='MembershipView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }
  }

  /**
   * CRM-16165
   */
  public function testOnRecurringContributionAndMembershipRenewal() {
    // Log in as admin
    $this->webtestLogin('admin');

    $this->enableComponents('CiviMember');

    // Log in as normal user
    $this->webtestLogin();

    // Add membership Type
    $this->openCiviPage("admin/member/membershipType", "reset=1&action=browse");

    $this->click('link=Add Membership Type');
    $this->waitForElementPresent('_qf_MembershipType_cancel-bottom');

    $title = substr(sha1(rand()), 0, 7);
    $membershipTypeTitle = "Membership Type $title";
    $this->type('name', "Membership Type $title");

    $this->select2('member_of_contact_id', 'Default Organization');

    $this->type('minimum_fee', '100');
    $this->select('financial_type_id', 'value=2');
    $this->type('duration_interval', 1);
    $this->select('duration_unit', 'label=year');
    $this->select('period_type', 'value=rolling');
    $this->click('CIVICRM_QFID_1_auto_renew');

    $this->click('_qf_MembershipType_upload-bottom');
    $this->waitForElementPresent('link=Add Membership Type');
    $this->waitForText('crm-notification-container', "The membership type 'Membership Type $title' has been saved.");

    $this->waitForElementPresent("xpath=//div[@id='membership_type']/table/tbody//tr/td[1]/div[text()='{$membershipTypeTitle}']/../../td[12]/span/a[3][text()='Delete']/@href");
    $url = $this->getAttribute("xpath=//div[@id='membership_type']/table/tbody//tr/td[1]/div[text()='{$membershipTypeTitle}']/../../td[12]/span/a[3][text()='Delete']/@href");
    $matches = array();
    preg_match('/id=([0-9]+)/', $url, $matches);
    $membershipTypeId = $matches[1];

    // Use default payment processor
    $processorName = 'Test Processor';

    // create contribution page with randomized title and default params
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    $amountSection = FALSE;
    $payLater = FALSE;
    $onBehalf = FALSE;
    $pledges = FALSE;
    $recurring = FALSE;
    $memberships = FALSE;
    $memPriceSetId = NULL;
    $friend = FALSE;
    $profilePreId = NULL;
    $profilePostId = NULL;
    $premiums = FALSE;
    $widget = FALSE;
    $pcp = FALSE;

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
      TRUE
    );

    $this->openCiviPage("admin/contribute/amount", "reset=1&action=update&id=$pageId", '_qf_Amount_next');

    //this contribution page for membership signup
    $xpath = "xpath=//label[text() = '{$processorName}']/preceding-sibling::input[1]";
    $this->waitForText('css=.crm-contribution-contributionpage-amount-form-block-payment_processor', $processorName);
    $this->check($xpath);

    //enable contribution amaount
    $this->click('amount_block_is_active');
    $this->waitForElementPresent('amount_label');
    $this->type('amount_label', 'Additional Contribution');
    $this->click('is_allow_other_amount');
    $this->type('min_amount', '10');
    $this->type('max_amount', '1000');

    // save
    $this->waitForElementPresent('_qf_Amount_submit_savenext');
    $this->click('_qf_Amount_submit_savenext');
    $this->waitForPageToLoad();

    //enable membership block
    $this->waitForElementPresent("member_is_active");
    $this->check("member_is_active");
    $this->waitForElementPresent("new_title");
    $this->type('new_title', 'Membership Levels');
    $this->type('membership_type_label', 'Membership Levels');
    $this->check("membership_type_$membershipTypeId");
    $this->check("is_required");
    $this->clickLink('_qf_MembershipBlock_next', '_qf_MembershipBlock_next-bottom');

    //Scenario 1(a) - (is_separate_payment=FALSE + recurring contribution + non-renewal membership) on Amount page
    $this->click('link=Amounts');
    $this->waitForElementPresent("is_recur");
    $this->click('is_recur');
    $this->clickLink('_qf_Amount_next', '_qf_Amount_next-bottom');
    $this->waitForElementPresent("is_recur");
    $this->assertTrue($this->isTextPresent("You need to enable Separate Membership Payment when online contribution page is configured for both Membership and Recurring Contribution."));

    //Scenario 1(b) - (is_separate_payment=FALSE + recurring contribution + non-renewal membership) on MembershipBlock page
    $this->click('link=Memberships');
    $this->waitForElementPresent("is_separate_payment");
    $this->click('is_separate_payment');//enable is_separate_payment
    $this->clickLink('_qf_MembershipBlock_next', '_qf_MembershipBlock_next-bottom');
    $this->click('link=Amounts');//switch back to amount page
    $this->waitForElementPresent("is_recur");
    $this->click('is_recur');//enable recurring contribution
    $this->clickLink('_qf_Amount_next', '_qf_Amount_next-bottom');
    $this->click('link=Memberships');//switch back to MembershipBlock page
    $this->waitForElementPresent("is_separate_payment");
    $this->uncheck('is_separate_payment');//disable is_separate_payment
    $this->clickLink('_qf_MembershipBlock_next', '_qf_MembershipBlock_next-bottom');
    $this->waitForElementPresent("is_separate_payment");
    $this->assertTrue($this->isTextPresent("You need to enable Separate Membership Payment when online contribution page is configured for both Membership and Recurring Contribution"));

    //Scenario 2(a) - (is_separate_payment=TRUE + recurring contribution + auto-renewal membership) on MembershipBlock page
    $this->click('is_separate_payment');//enable is_separate_payment
    $this->select("auto_renew_$membershipTypeId", 'value=1');//choose auto-renew as optional
    $this->clickLink('_qf_MembershipBlock_next', '_qf_MembershipBlock_next-bottom');
    $this->assertTrue($this->isTextPresent("You cannot enable both Recurring Contributions and Auto-renew memberships on the same online contribution page"));

    //Scenario 2(b) - (is_separate_payment=TRUE + recurring contribution + auto-renewal membership) on Amount page
    $this->click('link=Amounts');//switch back to amount page
    $this->waitForElementPresent("is_recur");
    $this->uncheck('is_recur');//disable recurring contribution
    $this->clickLink('_qf_Amount_next', '_qf_Amount_next-bottom');
    $this->click('link=Memberships');//switch back to MembershipBlock page
    $this->waitForElementPresent("auto_renew_$membershipTypeId");
    $this->select("auto_renew_$membershipTypeId", 'value=1');//choose auto-renew as optional
    $this->clickLink('_qf_MembershipBlock_next', '_qf_MembershipBlock_next-bottom');
    $this->click('link=Amounts');//switch back to amount page
    $this->waitForElementPresent("is_recur");
    $this->click('is_recur');//enable recurring contribution
    $this->clickLink('_qf_Amount_next', '_qf_Amount_next-bottom');
    $this->waitForElementPresent("is_recur");
    $this->assertTrue($this->isTextPresent("You cannot enable both Recurring Contributions and Auto-renew memberships on the same online contribution page"));

    //Scenario 3 - Online Registration on
    // (is_separate_payment=TRUE + non-recurring Additional contribution + auto-renewal membership) setting
    $firstName = 'Eia' . substr(sha1(rand()), 0, 4);
    $lastName = 'Ande' . substr(sha1(rand()), 0, 4);
    $email = $firstName . '@test.com';

    $this->openCiviPage("contribute/transact", "reset=1&id=$pageId", "_qf_Main_upload-bottom");

    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]/div/span/label/span[1][contains(text(),'$membershipTypeTitle')]");
    $this->waitForElementPresent("auto_renew");
    $this->type("xpath=//div[@class='content other_amount-content']/input[@type='text']", '20');
    $this->click("auto_renew");

    //fill email
    $this->type('email-5', $email);

    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->select("credit_card_type", "label=Visa");
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
    $this->assertTrue($this->isTextPresent("I want this membership to be renewed automatically every 1 year(s)."));
    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $expectedParams = array(
      'Pending' => array(
        'total_amount' => 100.00),
      'Completed' => array(
        'total_amount' => 20.00,
      ),
    );

    //Assert that additional contribution and auto-renewal membrship
    $membership = $this->webtest_civicrm_api("Membership", "get", array('membership_type_id' => $membershipTypeId));
    $this->assertEquals($membership['count'], 1);
    $membershipId = $membership['id'];
    $this->assertEquals($membership['values'][$membershipId]['membership_name'], $membershipTypeTitle);
    //CRM-16165: if membership contribution status is pending then membership status should be pending
    $pendingStatus = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus', 'Pending', 'id', 'name');
    $this->assertEquals($membership['values'][$membershipId]['status_id'], $pendingStatus);

    //check if the membership created is set to reccuring
    $recurringContributionId = $membership['values'][$membershipId]['contribution_recur_id'];
    $recurringContribution = $this->webtest_civicrm_api("ContributionRecur", "get", array('id' => $recurringContributionId));
    $this->assertEquals($recurringContribution['count'], 1);
    $this->assertEquals($recurringContribution['values'][$recurringContributionId]['auto_renew'], 1);
    $this->assertEquals($recurringContribution['values'][$recurringContributionId]['frequency_unit'], 'year');
    $this->assertEquals($recurringContribution['values'][$recurringContributionId]['frequency_interval'], 1);

    $results = $this->webtest_civicrm_api("Contribution", "get", array('source' => array('LIKE' => "%$contributionTitle%")));
    foreach ($results['values'] as $value) {
      $status = $value['contribution_status'];
      $this->assertEquals($value['total_amount'], $expectedParams[$status]['total_amount']);
      $this->webtest_civicrm_api("Contribution", "delete", array('id' => $value['contribution_id']));
    }

    //Cleanup data before trying next combination
    $this->webtest_civicrm_api("Membership", "delete", array('id' => $membershipId));
    $this->webtest_civicrm_api("ContributionRecur", "delete", array('id' => $recurringContributionId));

    //Scenario 4 - Online Registration on
    // (is_separate_payment=TRUE + recurring Additional contribution + non auto-renewal membership) setting
    $this->openCiviPage("admin/contribute/membership", "reset=1&action=update&id=$pageId");
    $this->waitForElementPresent("auto_renew_$membershipTypeId");
    $this->select("auto_renew_$membershipTypeId", 'value=0');
    $this->clickLink('_qf_MembershipBlock_next', '_qf_MembershipBlock_next-bottom');
    $this->click('link=Amounts');//switch back to amount page
    $this->waitForElementPresent("is_recur");
    $this->click('is_recur');//disable recurring contribution
    $this->clickLink('_qf_Amount_next', '_qf_Amount_next-bottom');

    $this->openCiviPage("contribute/transact", "reset=1&id=$pageId", "_qf_Main_upload-bottom");
    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]/div/span/label/span[1][contains(text(),'$membershipTypeTitle')]");
    $this->type("xpath=//div[@class='content other_amount-content']/input[@type='text']", '30');
    $this->click("is_recur");

    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->select("credit_card_type", "label=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");

    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");
    $this->assertTrue($this->isTextPresent("I want to contribute this amount every month."));
    $this->click("_qf_Confirm_next-bottom");
    sleep(10);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $expectedParams = array(
      'Pending' => array(
        'total_amount' => 30.00),
      'Completed' => array(
        'total_amount' => 100.00,
      ),
    );

    //Assert that additional contribution and auto-renewal membrship
    $membership = $this->webtest_civicrm_api("Membership", "get", array('membership_type_id' => $membershipTypeId));
    $this->assertEquals($membership['count'], 1);
    $membershipId = $membership['id'];
    $this->assertEquals($membership['values'][$membershipId]['membership_name'], $membershipTypeTitle);
    $this->assertEquals($membership['values'][$membershipId]['status_id'], 1);
    $this->assertEquals($membership['values'][$membershipId]['source'], "Online Contribution: $contributionTitle");

    $results = $this->webtest_civicrm_api("Contribution", "get", array('source' => array('LIKE' => "%$contributionTitle%")));
    foreach ($results['values'] as $value) {
      $status = $value['contribution_status'];
      $this->assertEquals($value['total_amount'], $expectedParams[$status]['total_amount']);
      if ($status == 'Pending') {
        $recurringContributionId = $value['contribution_recur_id'];
        $recurringContribution = $this->webtest_civicrm_api("ContributionRecur", "get", array('id' => $recurringContributionId));
        $this->assertEquals($recurringContribution['count'], 1);
        $this->assertEquals($recurringContribution['values'][$recurringContributionId]['frequency_unit'], 'month');
        $this->assertEquals($recurringContribution['values'][$recurringContributionId]['frequency_interval'], 1);
      }
      $this->webtest_civicrm_api("Contribution", "delete", array('id' => $value['contribution_id']));
    }
  }

}

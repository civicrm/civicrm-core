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
class WebTest_Member_OnlineMembershipRenewTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   * FIXME: This test tries to update a contribution page (id=2) that may not exist :(
   */
  function testOnlineMembershipRenew() {
    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // We need a payment processor
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);
    $this->webtestAddPaymentProcessor($processorName);

    $this->openCiviPage("admin/contribute/amount", "reset=1&action=update&id=2", "_qf_Amount_next");
    // this contribution page for membership signup
    // select newly created processor
    $xpath = "xpath=//label[text() = '{$processorName}']/preceding-sibling::input[1]";
    $this->assertTrue($this->isTextPresent($processorName));
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
    $this->select('custom_pre_id', 'value=1');

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

    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label/span[2][contains(text(),'Student')]");

    //Type first name and last name and email
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->type("email-5", $email);

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
    //Find Member
    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$firstName $lastName");
    $this->clickLink("_qf_Search_refresh", 'css=#memberSearch table tbody tr td span a.action-item-first');
    $this->click('css=#memberSearch table tbody tr td span a.action-item-first');
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

    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label/span[2][contains(text(),'Student')]");

    //Type first name and last name and email
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->type("email-5", $email);

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

    $this->webtestLogin();
    //Find Member
    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$firstName $lastName");
    $this->clickLink("_qf_Search_refresh", 'css=#memberSearch table tbody tr td span a.action-item-first');
    $this->click('css=#memberSearch table tbody tr td span a.action-item-first');
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
  function testOnlineMembershipRenewChangeType() {
    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // We need a payment processor
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);
    $this->webtestAddPaymentProcessor($processorName);

    $this->openCiviPage("admin/contribute/amount", "reset=1&action=update&id=2", '_qf_Amount_next');

    //this contribution page for membership signup
    $xpath = "xpath=//label[text() = '{$processorName}']/preceding-sibling::input[1]";
    $this->assertTrue($this->isTextPresent($processorName));
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
    $this->select('custom_pre_id', 'value=1');

    // save
    $this->click('_qf_Custom_upload_done');
    $this->waitForPageToLoad();

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);

    //Go to online membership signup page
    $this->openCiviPage("contribute/transact", "reset=1&id=2", "_qf_Main_upload-bottom");
    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label/span[2][contains(text(),'General')]");
    //Type first name and last name
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

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

    //Find Member
    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$firstName $lastName");
    $this->clickLink("_qf_Search_refresh", 'css=#memberSearch table tbody tr td span a.action-item-first');
    $this->click('css=#memberSearch table tbody tr td span a.action-item-first');
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
    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label/span[2][contains(text(),'Student')]");

    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->select("credit_card_type", "label=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");
    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Find Member
    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$firstName $lastName");
    $this->clickLink("_qf_Search_refresh", 'css=#memberSearch table tbody tr td span a.action-item-first');
    $this->click('css=#memberSearch table tbody tr td span a.action-item-first');
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

  function testUpdateInheritedMembershipOnBehalfOfRenewal() {
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

    $this->assertTrue($this->isTextPresent("Organization $title has been created."));

    $this->openCiviPage("admin/member/membershipType", "reset=1&action=browse");

    $this->click('link=Add Membership Type');
    $this->waitForElementPresent('_qf_MembershipType_cancel-bottom');

    $membershipTypeTitle = "Membership Type $title";
    $this->type('name', "Membership Type $title");

    $this->type('member_of_contact', $title);
    $this->click('member_of_contact');
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");

    $this->type('minimum_fee', '100');
    $this->select( 'financial_type_id', 'value=2' );
    $this->type('duration_interval', 1);
    $this->select('duration_unit', 'label=year');
    $this->select('period_type', 'label=rolling');

    $this->removeSelection('relationship_type_id', 'label=- select -');
    $this->addSelection('relationship_type_id', 'label=Employer of');

    $this->click('_qf_MembershipType_upload-bottom');
    $this->waitForElementPresent('link=Add Membership Type');
    $this->assertTrue($this->isTextPresent("The membership type 'Membership Type $title' has been saved."));

    $url = $this->getAttribute("xpath=//div[@id='membership_type']//div[@class='dataTables_wrapper']//table/tbody//tr/td[1][text()='{$membershipTypeTitle}']/../td[12]/span/a[3][text()='Delete']/@href");

    $matches = array();
    preg_match('/id=([0-9]+)/', $url, $matches);
    $membershipTypeId = $matches[1];

    // We need a payment processor
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);

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
    $this->select('onbehalf_profile_id', "value=9");
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
    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label/span[2][contains(text(),'$membershipTypeTitle')]");
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
    $this->clickLink("_qf_Search_refresh", 'css=#memberSearch table tbody tr td span a.action-item-first');
    $this->click('css=#memberSearch table tbody tr td span a.action-item-first');
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
    $this->clickLink("_qf_Search_refresh", 'css=#memberSearch table tbody tr td span a.action-item-first');
    $this->click('css=#memberSearch table tbody tr td span a.action-item-first');
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

    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label/span[2][contains(text(),'$membershipTypeTitle')]");
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
    $this->clickLink("_qf_Search_refresh", 'css=#memberSearch table tbody tr td span a.action-item-first');
    $this->click('css=#memberSearch table tbody tr td span a.action-item-first');
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
    $this->clickLink("_qf_Search_refresh", 'css=#memberSearch table tbody tr td span a.action-item-first');
    $this->click('css=#memberSearch table tbody tr td span a.action-item-first');
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
}


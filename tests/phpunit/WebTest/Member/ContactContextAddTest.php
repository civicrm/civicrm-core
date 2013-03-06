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
class WebTest_Member_ContactContextAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testContactMemberAdd() {
    $this->open($this->sboxPath);
    $this->webtestLogin();

    // Create a membership type to use for this test (defaults for this helper function are rolling 1 year membership)
    $memTypeParams = $this->webtestAddMembershipType();
    $lifeTimeMemTypeParams = $this->webtestAddMembershipType('rolling', 1, 'lifetime');

    // Go directly to the URL of the screen that you will be testing (New Individual).
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    $firstName = "John_" . substr(sha1(rand()), 0, 7);

    //fill in first name
    $this->type("first_name", $firstName);

    //fill in last name
    $lastName = "Smith_" . substr(sha1(rand()), 0, 7);
    $this->type("last_name", $lastName);

    //fill in email
    $email = substr(sha1(rand()), 0, 7) . "john@gmail.com";
    $this->type("email_1_email", $email);

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', "$firstName $lastName has been created.");

    // click through to the membership view screen
    $this->click("css=li#tab_member a");

    $this->waitForElementPresent("link=Add Membership");
    $this->click("link=Add Membership");

    $this->waitForElementPresent("_qf_Membership_cancel-bottom");

    // fill in Membership Organization and Type
    $this->select("membership_type_id[0]", "label={$memTypeParams['member_of_contact']}");
    // Wait for membership type select to reload
    $this->waitForTextPresent($memTypeParams['membership_type']);
    sleep(3);
    $this->select("membership_type_id[1]", "label={$memTypeParams['membership_type']}");

    $sourceText = "Membership ContactAddTest Webtest";
    // fill in Source
    $this->type("source", $sourceText);

    // Let Join Date stay default

    // fill in Start Date
    $this->webtestFillDate('start_date');

    // Clicking save.
    $this->click("_qf_Membership_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // page was loaded
    $this->waitForTextPresent($sourceText);

    // Is status message correct?
    $this->assertElementContainsText('crm-notification-container', "membership for $firstName $lastName has been added.",
      "Status message didn't show up after saving!");

    // click through to the membership view screen
    $this->click("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    $verifyData = array(
      'Membership Type' => $memTypeParams['membership_type'],
      'Status' => 'New',
      'Source' => $sourceText,
    );
    $this->webtestVerifyTabularData($verifyData);

    $this->click("_qf_MembershipView_cancel-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    // page was loaded
    $this->waitForTextPresent($sourceText);

    // click through to the activities screen
    $this->click("css=li#tab_activity a");
    // page was loaded
    $this->waitForTextPresent('Membership Signup');

    // click through to the activiy view screen (which is the membership view
    $this->click("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    $verifyData = array(
      'Membership Type' => $memTypeParams['membership_type'],
      'Status' => 'New',
      'Source' => $sourceText,
    );
    $this->webtestVerifyTabularData($verifyData);
    $this->click("_qf_MembershipView_cancel-bottom");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("xpath=//div[@id='memberships']/div/table/tbody//tr/td[1][text()='{$memTypeParams['membership_type']}']/../td[7]");
    $this->click("xpath=//div[@id='memberships']/div/table/tbody//tr/td[1][text()='{$memTypeParams['membership_type']}']/../td[9]/span/a[2][text()='Edit']");
    $this->waitForElementPresent("_qf_Membership_cancel-bottom");

    // fill in Membership Organization and Type
    $this->select("membership_type_id[0]", "label={$lifeTimeMemTypeParams['member_of_contact']}");
    // Wait for membership type select to reload
    $this->waitForTextPresent($lifeTimeMemTypeParams['membership_type']);
    $this->select("membership_type_id[1]", "label={$lifeTimeMemTypeParams['membership_type']}");

    $this->waitForElementPresent("xpath=//form[@id='Membership']/div[2]/div[2]//table/tbody//tr[@class='crm-membership-form-block-end_date']/td[2]");
    $this->click("xpath=//form[@id='Membership']/div[2]/div[2]//table/tbody//tr[@class='crm-membership-form-block-end_date']/td[2]/span/a[text()='clear']");

    $this->click("_qf_Membership_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // page was loaded
    $this->waitForTextPresent($sourceText);
    $this->click("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    $verifyData = array(
      'Status' => 'New',
      'Source' => $sourceText,
      'End date' => '',
    );
    $this->webtestVerifyTabularData($verifyData);
  }

  function testMemberAddWithLifeTimeMembershipType() {
    $this->open($this->sboxPath);
    $this->webtestLogin();

    // Create a membership type to use for this test (defaults for this helper function are rolling 1 year membership)
    $lifeTimeMemTypeParams = $this->webtestAddMembershipType('rolling', 1, 'lifetime');

    // Go directly to the URL of the screen that you will be testing (New Individual).
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    $firstName = "John_" . substr(sha1(rand()), 0, 7);

    //fill in first name
    $this->type("first_name", $firstName);

    //fill in last name
    $lastName = "Smith_" . substr(sha1(rand()), 0, 7);;
    $this->type("last_name", $lastName);

    //fill in email
    $email = substr(sha1(rand()), 0, 7) . "john@gmail.com";
    $this->type("email_1_email", $email);

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', "$firstName $lastName has been created.");

    // click through to the membership view screen
    $this->click("css=li#tab_member a");

    $this->waitForElementPresent("link=Add Membership");
    $this->click("link=Add Membership");

    $this->waitForElementPresent("_qf_Membership_cancel-bottom");

    // fill in Membership Organization and Type
    $this->select("membership_type_id[0]", "label={$lifeTimeMemTypeParams['member_of_contact']}");

    // Wait for membership type select to reload
    $this->waitForTextPresent($lifeTimeMemTypeParams['membership_type']);
    sleep(3);
    $this->select("membership_type_id[1]", "label={$lifeTimeMemTypeParams['membership_type']}");

    $sourceText = "Check Lifetime membership type webtest";
    // fill in Source
    $this->type("source", $sourceText);

    // Let Join Date stay default

    // fill in Start Date
    $this->webtestFillDate('start_date');

    // Clicking save.
    $this->click("_qf_Membership_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // page was loaded
    $this->waitForTextPresent($sourceText);

    // Is status message correct?
    $this->assertElementContainsText('crm-notification-container', "membership for $firstName $lastName has been added.",
      "Status message didn't show up after saving!");

    // click through to the membership view screen
    $this->click("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    $verifyData = array(
      'Status' => 'New',
      'Source' => $sourceText,
      'End date' => '',
    );
    $this->webtestVerifyTabularData($verifyData);
    $this->click("_qf_MembershipView_cancel-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }
}



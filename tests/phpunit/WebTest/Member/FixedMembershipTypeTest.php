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
 * Class WebTest_Member_FixedMembershipTypeTest
 */
class WebTest_Member_FixedMembershipTypeTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testMembershipTypeScenario1() {
    // Scenario 1
    // Rollover Date < Start Date
    // Join Date > Rollover Date and Join Date < Start Date

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $this->openCiviPage("contact/add", "reset=1&ct=Organization", '_qf_Contact_cancel');

    $title = substr(sha1(rand()), 0, 7);
    $this->type('organization_name', "Organization $title");
    $this->type('email_1_email', "$title@org.com");
    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("Organization $title has been created."));

    $this->openCiviPage("admin/member/membershipType", "reset=1&action=browse");

    $this->click("link=Add Membership Type");
    $this->waitForElementPresent('_qf_MembershipType_cancel-bottom');

    $this->type('name', "Membership Type $title");
    $this->select2('member_of_contact_id', $title);

    $this->type('minimum_fee', '100');
    $this->select('financial_type_id', 'value=2');
    $this->type('duration_interval', 1);
    $this->select('duration_unit', "label=year");

    $this->select('period_type', "value=fixed");
    $this->waitForElementPresent('fixed_period_rollover_day[d]');

    // fixed period start set to April 1
    $this->select('fixed_period_start_day[M]', 'value=4');
    // rollover date set to Jan 31
    $this->select('fixed_period_rollover_day[M]', 'value=1');

    $this->click('relationship_type_id', 'value=4_b_a');

    $this->click('_qf_MembershipType_upload-bottom');
    $this->waitForElementPresent('link=Add Membership Type');
    $this->waitForText('crm-notification-container', "The membership type 'Membership Type $title' has been saved.");

    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    $firstName = "John_" . substr(sha1(rand()), 0, 7);

    //fill in first name
    $this->type('first_name', $firstName);

    //fill in last name
    $lastName = "Smith_" . substr(sha1(rand()), 0, 7);;
    $this->type('last_name', $lastName);

    //fill in email
    $email = substr(sha1(rand()), 0, 7) . "john@gmail.com";
    $this->type('email_1_email', $email);

    // Clicking save.
    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("$firstName $lastName has been created."));

    // click through to the membership view screen
    $this->click('css=li#tab_member a');

    $this->waitForElementPresent('link=Add Membership');
    $this->click('link=Add Membership');

    $this->waitForElementPresent('_qf_Membership_cancel-bottom');

    // fill in Membership Organization and Type
    $this->select('membership_type_id[0]', "label=Organization $title");
    $this->select('membership_type_id[1]', "label=Membership Type $title");

    $sourceText = "Membership ContactAddTest with Fixed Membership Type";
    // fill in Source
    $this->type('source', $sourceText);

    //build the membership dates.
    require_once 'CRM/Core/Config.php';
    require_once 'CRM/Utils/Array.php';
    require_once 'CRM/Utils/Date.php';
    $currentYear = date('Y');
    $currentMonth = date('m');
    $previousYear = $currentYear - 1;
    $nextYear = $currentYear + 1;

    $todayDate = date('Y-m-d');

    // the member-since date we will type in to membership form
    $joinDate = date('Y-m-d', mktime(0, 0, 0, 3, 25, $currentYear));

    // expected calc'd start date
    $startDate = date('Y-m-d', mktime(0, 0, 0, 4, 1, $previousYear));

    // expected calc'd end date
    $endDate = date('Y-m-d', mktime(0, 0, 0, 3, 31, $nextYear));

    foreach (array(
               'joinDate',
               'startDate',
               'endDate',
             ) as $date) {
      $$date = CRM_Utils_Date::customFormat($$date, $this->webtestGetSetting('dateformatFull'));
    }

    $query = "
SELECT end_event_adjust_interval

  FROM civicrm_membership_status

 WHERE start_event = 'join_date'
   AND name = 'New'";
    $endInterval = CRM_Core_DAO::singleValueQuery($query);

    // Add endInterval to March 25 (join date month above) to get end of New status period
    $endNewStatus = date('Y-m-d', mktime(0, 0, 0, $endInterval + 3, 25, $currentYear));

    $status = 'Current';
    // status will be 'New' if today is >= join date and <= endNewStatus date
    if ((strtotime($todayDate) >= strtotime($joinDate)) && (strtotime($todayDate) <= strtotime($endNewStatus))) {
      $status = 'New';
    }

    // fill in Join Date
    $this->webtestFillDate('join_date', $joinDate);

    // Clicking save.
    $this->click('_qf_Membership_upload');

    // page was loaded
    $this->waitForTextPresent($sourceText);

    $this->waitForElementPresent("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    // Is status message correct?
    $this->assertTrue($this->isTextPresent("Membership Type $title membership for $firstName $lastName has been added."),
      "Status message didn't show up after saving!"
    );

    // click through to the membership view screen
    $this->click("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    $this->webtestVerifyTabularData(
      array(
        'Membership Type' => "Membership Type $title",
        'Status' => $status,
        'Source' => $sourceText,
        'Member Since' => $joinDate,
        'Start date' => $startDate,
        'End date' => $endDate,
      )
    );
  }

  public function testMembershipTypeScenario2() {
    // Scenario 2
    // Rollover Date < Join Date

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $this->openCiviPage("contact/add", "reset=1&ct=Organization", '_qf_Contact_cancel');

    $title = substr(sha1(rand()), 0, 7);
    $this->type('organization_name', "Organization $title");
    $this->type('email_1_email', "$title@org.com");
    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("Organization $title has been created."));

    $this->openCiviPage("admin/member/membershipType", "reset=1&action=browse");

    $this->click("link=Add Membership Type");
    $this->waitForElementPresent('_qf_MembershipType_cancel-bottom');

    $this->type('name', "Membership Type $title");
    $this->select2('member_of_contact_id', $title);

    $this->type('minimum_fee', '100');
    $this->select('financial_type_id', 'value=2');

    $this->type('duration_interval', 2);
    $this->select('duration_unit', "label=year");

    $this->select('period_type', "value=fixed");
    $this->waitForElementPresent('fixed_period_rollover_day[d]');

    $this->select('fixed_period_start_day[M]', 'value=9');
    $this->select('fixed_period_rollover_day[M]', 'value=6');
    $this->select('fixed_period_rollover_day[d]', 'value=30');

    $this->click('relationship_type_id', 'value=4_b_a');

    $this->click('_qf_MembershipType_upload-bottom');
    $this->waitForElementPresent('link=Add Membership Type');
    $this->waitForText('crm-notification-container', "The membership type 'Membership Type $title' has been saved.");

    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    $firstName = "John_" . substr(sha1(rand()), 0, 7);

    //fill in first name
    $this->type('first_name', $firstName);

    //fill in last name
    $lastName = "Smith_" . substr(sha1(rand()), 0, 7);;
    $this->type('last_name', $lastName);

    //fill in email
    $email = substr(sha1(rand()), 0, 7) . "john@gmail.com";
    $this->type('email_1_email', $email);

    // Clicking save.
    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("$firstName $lastName has been created."));

    // click through to the membership add screen
    $this->click('css=li#tab_member a');

    $this->waitForElementPresent('link=Add Membership');
    $this->click('link=Add Membership');

    $this->waitForElementPresent('_qf_Membership_cancel-bottom');

    // fill in Membership Organization and Type
    $this->select('membership_type_id[0]', "label=Organization {$title}");
    // Wait for membership type select to reload
    $this->waitForTextPresent("Membership Type {$title}");
    $this->select('membership_type_id[1]', "label=Membership Type {$title}");

    $sourceText = "Membership ContactAddTest with Fixed Membership Type Scenario 2";
    // fill in Source
    $this->type('source', $sourceText);

    //build the membership dates.
    require_once 'CRM/Core/Config.php';
    require_once 'CRM/Utils/Array.php';
    require_once 'CRM/Utils/Date.php';
    $currentYear = date('Y');
    $currentMonth = date('m');
    $previousYear = $currentYear - 1;

    $todayDate = date('Y-m-d');

    // the member-since date we will type in to membership form
    $joinDate = date('Y-m-d', mktime(0, 0, 0, 7, 15, $currentYear));

    // expected calc'd start date
    $startDate = date('Y-m-d', mktime(0, 0, 0, 9, 1, $previousYear));

    // expected calc'd end date
    $endDate = date('Y-m-d', mktime(0, 0, 0, 8, 31, $currentYear + 2));
    foreach (array(
               'joinDate',
               'startDate',
               'endDate',
             ) as $date) {
      $$date = CRM_Utils_Date::customFormat($$date, $this->webtestGetSetting('dateformatFull'));
    }

    $query = "
SELECT end_event_adjust_interval

  FROM civicrm_membership_status

 WHERE start_event = 'join_date'
   AND name = 'New'";
    $endInterval = CRM_Core_DAO::singleValueQuery($query);

    // Add endInterval to July 15 (join date month above) to get end of New status period
    $endNewStatus = date('Y-m-d', mktime(0, 0, 0, $endInterval + 7, 15, $currentYear));

    $status = 'Current';
    // status will be 'New' if today is >= join date and <= endNewStatus date
    if ((strtotime($todayDate) >= strtotime($joinDate)) && (strtotime($todayDate) <= strtotime($endNewStatus))) {
      $status = 'New';
    }

    // fill in Join Date
    $this->webtestFillDate('join_date', $joinDate);

    // Clicking save.
    $this->click('_qf_Membership_upload');
    $this->waitForElementPresent("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");

    // page was loaded
    $this->waitForTextPresent($sourceText);

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("Membership Type $title membership for $firstName $lastName has been added."),
      "Status message didn't show up after saving!"
    );

    // click through to the membership view screen
    $this->click("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    $this->webtestVerifyTabularData(
      array(
        'Membership Type' => "Membership Type {$title}",
        'Status' => $status,
        'Source' => $sourceText,
        'Member Since' => $joinDate,
        'Start date' => $startDate,
        'End date' => $endDate,
      )
    );
  }

  public function testMembershipTypeScenario3() {
    // Scenario 3
    // Standard Fixed scenario - Jan 1 Fixed Period Start and October 31 rollover
    // Join Date is later than Rollover Date

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $this->openCiviPage("contact/add", "reset=1&ct=Organization", '_qf_Contact_cancel');

    $title = substr(sha1(rand()), 0, 7);
    $this->type('organization_name', "Organization $title");
    $this->type('email_1_email', "$title@org.com");
    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("Organization $title has been created."));

    $this->openCiviPage("admin/member/membershipType", "reset=1&action=browse");

    $this->click("link=Add Membership Type");
    $this->waitForElementPresent('_qf_MembershipType_cancel-bottom');

    $this->type('name', "Membership Type $title");
    $this->select2('member_of_contact_id', $title);

    $this->type('minimum_fee', '100');
    $this->select('financial_type_id', 'value=2');
    $this->type('duration_interval', 1);
    $this->select('duration_unit', "label=year");

    $this->select('period_type', "value=fixed");
    $this->waitForElementPresent('fixed_period_rollover_day[d]');

    $this->select('fixed_period_rollover_day[M]', 'value=10');
    $this->select('fixed_period_rollover_day[d]', 'value=31');

    $this->click('relationship_type_id', 'value=4_b_a');

    $this->click('_qf_MembershipType_upload-bottom');
    $this->waitForElementPresent('link=Add Membership Type');

    $this->waitForText('crm-notification-container', "The membership type 'Membership Type $title' has been saved.");
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    $firstName = "John_" . substr(sha1(rand()), 0, 7);

    //fill in first name
    $this->type('first_name', $firstName);

    //fill in last name
    $lastName = "Smith_" . substr(sha1(rand()), 0, 7);;
    $this->type('last_name', $lastName);

    //fill in email
    $email = substr(sha1(rand()), 0, 7) . "john@gmail.com";
    $this->type('email_1_email', $email);

    // Clicking save.
    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("$firstName $lastName has been created."));

    // click through to the membership view screen
    $this->click('css=li#tab_member a');

    $this->waitForElementPresent('link=Add Membership');
    $this->click('link=Add Membership');

    $this->waitForElementPresent('_qf_Membership_cancel-bottom');

    // fill in Membership Organization and Type
    $this->select('membership_type_id[0]', "label=Organization {$title}");
    // Wait for membership type select to reload
    $this->waitForTextPresent("Membership Type {$title}");
    $this->select('membership_type_id[1]', "label=Membership Type {$title}");

    $sourceText = "Membership ContactAddTest with Fixed Membership Type Scenario 3";
    // fill in Source
    $this->type('source', $sourceText);

    //build the membership dates.
    require_once 'CRM/Core/Config.php';
    require_once 'CRM/Utils/Array.php';
    require_once 'CRM/Utils/Date.php';
    $currentYear = date('Y');
    $currentMonth = date('m');
    $previousYear = $currentYear - 1;
    $nextYear = $currentYear + 1;
    $todayDate = date('Y-m-d');
    $joinDate = date('Y-m-d', mktime(0, 0, 0, 11, 15, $currentYear));
    $startDate = date('Y-m-d', mktime(0, 0, 0, 1, 1, $currentYear));
    $endDate = date('Y-m-d', mktime(0, 0, 0, 12, 31, $nextYear));
    foreach (array(
               'joinDate',
               'startDate',
               'endDate',
             ) as $date) {
      $$date = CRM_Utils_Date::customFormat($$date, $this->webtestGetSetting('dateformatFull'));
    }

    $query = "
SELECT end_event_adjust_interval

  FROM civicrm_membership_status

 WHERE start_event = 'join_date'
   AND name = 'New'";
    $endInterval = CRM_Core_DAO::singleValueQuery($query);

    // Add endInterval to Nov 15 (join date month above) to get end of New status period
    $endNewStatus = date('Y-m-d', mktime(0, 0, 0, $endInterval - 1, 15, $nextYear));

    $status = 'Current';
    // status will be 'New' if today is >= join date and <= endNewStatus date
    if ((strtotime($todayDate) >= strtotime($joinDate)) && (strtotime($todayDate) <= strtotime($endNewStatus))) {
      $status = 'New';
    }

    // fill in Join Date
    $this->webtestFillDate('join_date', $joinDate);

    // Clicking save.
    $this->click('_qf_Membership_upload');
    $this->waitForElementPresent("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");

    // page was loaded
    $this->waitForTextPresent($sourceText);

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("Membership Type $title membership for $firstName $lastName has been added."),
      "Status message didn't show up after saving!"
    );

    // click through to the membership view screen
    $this->click("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    $this->webtestVerifyTabularData(
      array(
        'Membership Type' => "Membership Type {$title}",
        'Status' => $status,
        'Source' => $sourceText,
        'Member Since' => $joinDate,
        'Start date' => $startDate,
        'End date' => $endDate,
      )
    );
  }

  public function testMembershipTypeScenario4() {
    // Scenario 4
    // Standard Fixed scenario - Jan 1 Fixed Period Start and October 31 rollover
    // Join Date is earlier than Rollover Date

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $this->openCiviPage("contact/add", "reset=1&ct=Organization", '_qf_Contact_cancel');

    $title = substr(sha1(rand()), 0, 7);
    $this->type('organization_name', "Organization $title");
    $this->type('email_1_email', "$title@org.com");
    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("Organization $title has been created."));

    $this->openCiviPage("admin/member/membershipType", "reset=1&action=browse");

    $this->click("link=Add Membership Type");
    $this->waitForElementPresent("xpath=//button//span[contains(text(),'Cancel')]");

    $this->type('name', "Membership Type $title");
    $this->select2('member_of_contact_id', $title);

    $this->type('minimum_fee', '100');
    $this->select('financial_type_id', 'value=2');
    $this->type('duration_interval', 1);
    $this->select('duration_unit', "label=year");

    $this->select('period_type', "value=fixed");
    $this->waitForElementPresent('fixed_period_rollover_day[d]');

    $this->select('fixed_period_start_day[M]', 'value=1');
    $this->select('fixed_period_rollover_day[M]', 'value=10');
    $this->select('fixed_period_rollover_day[d]', 'value=31');

    $this->click('relationship_type_id', 'value=4_b_a');

    $this->click('_qf_MembershipType_upload-bottom');
    $this->waitForElementPresent('link=Add Membership Type');
    $this->waitForText('crm-notification-container', "The membership type 'Membership Type $title' has been saved.");

    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    $firstName = "John_" . substr(sha1(rand()), 0, 7);

    //fill in first name
    $this->type('first_name', $firstName);

    //fill in last name
    $lastName = "Smith_" . substr(sha1(rand()), 0, 7);;
    $this->type('last_name', $lastName);

    //fill in email
    $email = substr(sha1(rand()), 0, 7) . "john@gmail.com";
    $this->type('email_1_email', $email);

    // Clicking save.
    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("$firstName $lastName has been created."));

    // click through to the membership view screen
    $this->click('css=li#tab_member a');

    $this->waitForElementPresent('link=Add Membership');
    $this->click('link=Add Membership');

    $this->waitForElementPresent('_qf_Membership_cancel-bottom');

    // fill in Membership Organization and Type
    $this->select('membership_type_id[0]', "label=Organization $title");
    $this->select('membership_type_id[1]', "label=Membership Type $title");

    $sourceText = "Membership ContactAddTest with Fixed Membership Type Scenario 4";
    // fill in Source
    $this->type('source', $sourceText);

    //build the membership dates.
    require_once 'CRM/Core/Config.php';
    require_once 'CRM/Utils/Array.php';
    require_once 'CRM/Utils/Date.php';
    $currentYear = date('Y');
    $currentMonth = date('m');
    $nextYear = $currentYear + 1;
    $todayDate = date('Y-m-d');

    // the member-since date we will type in to membership form
    $joinDate = date('Y-m-d', mktime(0, 0, 0, 1, 15, $currentYear));

    // expected calc'd start and end dates
    $startDate = date('Y-m-d', mktime(0, 0, 0, 1, 1, $currentYear));
    $endDate = date('Y-m-d', mktime(0, 0, 0, 12, 31, $currentYear));
    foreach (array(
               'joinDate',
               'startDate',
               'endDate',
             ) as $date) {
      $$date = CRM_Utils_Date::customFormat($$date, $this->webtestGetSetting('dateformatFull'));
    }

    $query = "
SELECT end_event_adjust_interval

  FROM civicrm_membership_status

 WHERE start_event = 'join_date'
   AND name = 'New'";
    $endInterval = CRM_Core_DAO::singleValueQuery($query);

    // Add endInterval to Jan 6 (join date month above) to get end of New status period
    $endNewStatus = date('Y-m-d', mktime(0, 0, 0, $endInterval + 1, 15, $currentYear));

    $status = 'Current';
    // status will be 'New' if today is >= join date and <= endNewStatus date
    if ((strtotime($todayDate) >= strtotime($joinDate)) && (strtotime($todayDate) <= strtotime($endNewStatus))) {
      $status = 'New';
    }

    // fill in Join Date
    $this->webtestFillDate('join_date', $joinDate);

    // Clicking save.
    $this->click('_qf_Membership_upload');
    $this->waitForElementPresent("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");

    // page was loaded
    $this->waitForTextPresent($sourceText);

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("Membership Type $title membership for $firstName $lastName has been added."),
      "Status message didn't show up after saving!"
    );

    // click through to the membership view screen
    $this->click("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    $this->webtestVerifyTabularData(
      array(
        'Membership Type' => "Membership Type $title",
        'Status' => $status,
        'Source' => $sourceText,
        'Member Since' => $joinDate,
        'Start date' => $startDate,
        'End date' => $endDate,
      )
    );
  }

}

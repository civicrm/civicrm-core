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
 * Class WebTest_Member_InheritedMembershipTest
 */
class WebTest_Member_InheritedMembershipTest extends CiviSeleniumTestCase {
  protected function setUp() {
    parent::setUp();
  }

  public function testInheritedMembership() {
    //$this->markTestSkipped('Skipping for now as it works fine locally.');
    // Log in using webtestLogin() method
    $this->webtestLogin();

    $this->openCiviPage('contact/add', 'reset=1&ct=Organization', '_qf_Contact_cancel');

    $title = substr(sha1(rand()), 0, 7);
    $this->type('organization_name', "Organization $title");
    $this->type('email_1_email', "$title@org.com");
    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Organization {$title} has been created.");

    $this->openCiviPage('admin/member/membershipType', 'reset=1&action=browse');

    $this->click('link=Add Membership Type');
    $this->waitForElementPresent('_qf_MembershipType_cancel-bottom');

    $this->type('name', "Membership Type $title");

    $this->select2('member_of_contact_id', $title);

    $this->type('minimum_fee', '100');
    $this->select('financial_type_id', 'value=2');
    $this->type('duration_interval', 1);
    $this->select('duration_unit', 'label=year');

    $this->select('period_type', 'value=rolling');

    $this->select2('relationship_type_id', 'Employer of', TRUE);
    $this->type('max_related', '5');

    $this->click('_qf_MembershipType_upload-bottom');
    $this->waitForElementPresent('link=Add Membership Type');
    $this->waitForText('crm-notification-container', "Membership Type $title");

    $this->openCiviPage('contact/add', 'reset=1&ct=Organization', '_qf_Contact_cancel');

    // creating another Orgnization
    $title1 = substr(sha1(rand()), 0, 7);
    $this->type('organization_name', "Organization $title1");
    $this->type('email_1_email', "$title1@org.com");
    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // click through to the membership view screen
    $this->click('css=li#tab_member a');

    $this->waitForElementPresent('link=Add Membership');
    $this->click('link=Add Membership');

    $this->waitForElementPresent('_qf_Membership_cancel-bottom');

    // fill in Membership Organization and Type
    $this->select('membership_type_id[0]', "label=Organization $title");
    $this->select('membership_type_id[1]', "label=Membership Type $title");

    $sourceText = 'Membership ContactAddTest with Fixed Membership Type';
    // fill in Source
    $this->type('source', $sourceText);

    // Clicking save.
    $this->click('_qf_Membership_upload');
    $this->waitForElementPresent('link=Add Membership');

    // page was loaded
    $this->waitForTextPresent($sourceText);

    // Is status message correct?
    $this->waitForText('crm-notification-container', "Membership Type $title");

    // click through to the membership view screen
    $this->click("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_MembershipView_cancel-bottom');

    $joinDate = date('Y-m-d');
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 1, date('Y') + 1));
    foreach (array(
               'joinDate',
               'startDate',
               'endDate',
             ) as $date) {
      $$date = CRM_Utils_Date::customFormat($$date, $this->webtestGetSetting('dateformatFull'));
    }

    $this->webtestVerifyTabularData(
      array(
        'Membership Type' => "Membership Type $title",
        'Status' => 'New',
        'Source' => $sourceText,
        'Member Since' => $joinDate,
        'Start date' => $startDate,
        'End date' => $endDate,
        'Max related' => "5",
      )
    );

    // Adding contact
    // We're using Quick Add block on the main page for this.
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, 'Anderson', "$firstName@anderson.name");

    // visit relationship tab
    $this->click('css=li#tab_rel a');
    $this->waitForElementPresent('css=div.action-link');
    $this->click("//div[@class='action-link']/a/span");
    $this->waitForElementPresent('_qf_Relationship_cancel-bottom');
    $this->click('relationship_type_id');
    $this->select('relationship_type_id', 'label=Employee of');

    $this->select2('related_contact_id', $title1, TRUE);

    $description = 'Well here is some description !!!!';
    $this->type('description', $description);

    //save the relationship
    $this->click('_qf_Relationship_upload-bottom');
    $this->waitForElementPresent("xpath=//div[@class='crm-contact-relationship-current']");
    //check the status message
    $this->waitForText('crm-notification-container', 'Relationship created.');

    $this->waitForElementPresent("xpath=//div[@class='crm-contact-relationship-current']/div/table/tbody//tr/td[9]/span/a[text()='View']");

    // click through to the membership view screen
    $this->click('css=li#tab_member a');
    $this->waitForElementPresent('css=div#memberships');

    // click through to the membership view screen
    $this->click("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    $this->webtestVerifyTabularData(
      array(
        'Membership Type' => "Membership Type $title",
        'Status' => 'New',
        'Source' => $sourceText,
        'Member Since' => $joinDate,
        'Start date' => $startDate,
        'End date' => $endDate,
      )
    );
    $this->click("_qf_MembershipView_cancel-bottom");
    $this->waitForElementPresent('css=div#memberships');

    //1. change relationship status on form
    $this->click('css=li#tab_rel a');
    $this->waitForElementPresent('css=div.action-link');

    $this->click("//li[@id='tab_rel']/a");
    $this->waitForElementPresent("xpath=//div[@class='crm-contact-relationship-current']/div/table/tbody//tr/td[9]/span/a[text()='Edit']");
    $id = explode('&id=', $this->getAttribute("xpath=//div[@class='crm-contact-relationship-current']/div/table/tbody//tr/td[9]/span/a@href"));
    $id = explode('&', $id[0]);
    $id = explode('=', $id[2]);
    $id = $id[1];
    $this->click("xpath=//div[@class='crm-contact-relationship-current']/div/table/tbody//tr/td[9]/span/a[text()='Edit']");

    $this->waitForElementPresent('is_active');
    //disable relationship
    if ($this->isChecked('is_active')) {
      $this->click('is_active');
    }
    $this->click('_qf_Relationship_upload');
    $this->waitForElementPresent("xpath=//div[@class='crm-contact-relationship-current']");
    //check the status message
    $this->waitForText('crm-notification-container', 'Relationship record has been updated');

    // click through to the membership view screen
    $this->click('css=li#tab_member a');

    //verify inherited membership has been removed
    $this->openCiviPage("contact/view", "reset=1&cid=$id&selectedChild=member", "xpath=//div[@class='view-content']/div[3]");
    $this->waitForTextPresent("No memberships have been recorded for this contact.");

    // visit relationship tab and re-enable the relationship
    $this->click('css=li#tab_rel a');
    $this->waitForElementPresent('css=div.action-link');
    $this->click("//li[@id='tab_rel']/a");

    $this->waitForElementPresent("xpath=//div[@class='crm-contact-relationship-past']/div/table/tbody//tr/td[9]/span/a[text()='Edit']");
    $this->click("xpath=//div[@class='crm-contact-relationship-past']/div/table/tbody//tr/td[9]/span/a[text()='Edit']");
    $this->waitForElementPresent('is_active');
    if (!$this->isChecked('is_active')) {
      $this->click('is_active');
    }
    $this->click('_qf_Relationship_upload');
    $this->waitForElementPresent("xpath=//div[@class='crm-contact-relationship-current']");
    //check the status message
    $this->waitForText('crm-notification-container', 'Relationship record has been updated.');

    //check for memberships
    $this->click('css=li#tab_member a');
    $this->waitForElementPresent('css=div#memberships');

    //2 . visit relationship tab and disable the relationship (by links)
    //disable relationship
    $this->click('css=li#tab_rel a');
    $this->waitForElementPresent('css=div.action-link');
    $this->waitForElementPresent("xpath=//div[@class='crm-contact-relationship-current']/div/table/tbody//tr/td[9]/span[2][text()='more']/ul/li[1]/a[text()='Disable']");
    $this->click("xpath=//div[@class='crm-contact-relationship-current']/div/table/tbody//tr/td[9]/span[2][text()='more']/ul/li[1]/a[text()='Disable']");
    $this->waitForTextPresent('Are you sure you want to disable this relationship?');
    $this->click("xpath=//div[@class='ui-dialog-buttonset']//button//span[text()='Yes']");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(10);

    //verify inherited membership has been removed
    $this->openCiviPage("contact/view", "reset=1&cid={$id}&selectedChild=member", "xpath=//div[@class='view-content']/div[3]");
    $this->waitForTextPresent("No memberships have been recorded for this contact.");

    //enable relationship
    $this->click('css=li#tab_rel a');
    $this->waitForElementPresent('css=div.action-link');

    $this->waitForElementPresent("xpath=//div[@class='crm-contact-relationship-past']/div/table/tbody/tr/td[9]/span[2][text()='more']/ul/li[1]/a[text()='Enable']");
    $this->click("xpath=//div[@class='crm-contact-relationship-past']/div/table/tbody/tr/td[9]/span[2][text()='more']/ul/li[1]/a[text()='Enable']");

    //verify membership
    $this->click('css=li#tab_member a');
    $this->waitForTextPresent("No memberships have been recorded for this contact.");
  }

  /**
   * Webtest for CRM-10146
   */
  public function testInheritedMembershipActivity() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    $this->openCiviPage('contact/add', 'reset=1&ct=Organization', '_qf_Contact_cancel');

    $title = substr(sha1(rand()), 0, 7);
    $this->type('organization_name', "Organization $title");
    $this->type('email_1_email', "$title@org.com");
    $this->clickLink('_qf_Contact_upload_view');
    $this->waitForText('crm-notification-container', "Organization {$title} has been created.");

    $this->openCiviPage('admin/member/membershipType', 'reset=1&action=browse');

    $this->click('link=Add Membership Type');
    $this->waitForElementPresent('_qf_MembershipType_cancel-bottom');

    $this->type('name', "Membership Type $title");

    $this->select2('member_of_contact_id', $title);

    $this->type('minimum_fee', '100');
    $this->select('financial_type_id', 'label=Member Dues');
    $this->type('duration_interval', 1);
    $this->select('duration_unit', 'label=year');

    $this->select('period_type', 'value=rolling');

    $this->select2('relationship_type_id', 'Employer of', TRUE);

    $this->type('max_related', '5');

    $this->click('_qf_MembershipType_upload-bottom');
    $this->waitForElementPresent('link=Add Membership Type');
    $this->waitForText('crm-notification-container', "Membership Type $title");

    // creating another Organization
    $this->openCiviPage('contact/add', 'reset=1&ct=Organization', '_qf_Contact_cancel');
    $org1 = substr(sha1(rand()), 0, 7);
    $this->type('organization_name', "Organization $org1");
    $this->type('email_1_email', "$org1@org.com");
    $this->clickLink('_qf_Contact_upload_view');

    // click through to the membership view screen
    $this->click('css=li#tab_member a');

    $this->waitForElementPresent('link=Add Membership');
    $this->click('link=Add Membership');

    $this->waitForElementPresent('_qf_Membership_cancel-bottom');

    // fill in Membership Organization and Type
    $this->select('membership_type_id[0]', "label=Organization $title");
    $this->select('membership_type_id[1]', "label=Membership Type $title");

    $sourceText = 'Membership ContactAddTest with Rolling Membership Type';
    // fill in Source
    $this->type('source', $sourceText);

    // Clicking save.
    $this->click('_qf_Membership_upload');

    // page was loaded
    $this->waitForTextPresent($sourceText);

    // Is status message correct?
    $this->waitForText('crm-notification-container', "Membership Type $title");

    // Adding contact
    $this->openCiviPage('contact/add', 'reset=1&ct=Individual', '_qf_Contact_cancel-bottom');
    $firstName = substr(sha1(rand()), 0, 7);
    $lastName = 'Anderson';
    $email = "$firstName@anderson.name";
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    // Set Current Employer
    $this->select2('employer_id', $org1);
    $this->waitForText('s2id_employer_id', $org1);

    $this->type("email_1_email", $email);
    $this->clickLink("_qf_Contact_upload_view-bottom");
    $cid = $this->urlArg('cid');

    // click through to the membership view screen
    $this->click('css=li#tab_member a');
    $this->waitForElementPresent("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");

    // check number of membership for contact
    $this->assertEquals(1, $this->getText("xpath=//li[@id='tab_member']/a/em"));

    $url = $this->parseURL($this->getAttribute("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']@href"));
    $expectedMembershipId = $url['queryString']['id'];

    // click through to the activity view screen
    $this->click('css=li#tab_activity a');
    $this->waitForElementPresent("xpath=//table[@class='contact-activity-selector-activity crm-ajax-table dataTable no-footer']/tbody/tr/td[8]/span/a");

    // check number of activity for contact
    $this->assertEquals(1, $this->getText("xpath=//li[@id='tab_activity']/a/em"));

    $url = $this->parseURL($this->getAttribute("xpath=//table[@class='contact-activity-selector-activity crm-ajax-table dataTable no-footer']/tbody/tr/td[8]/span/a@href"));
    $expectedMembershipActivityId = $url['queryString']['id'];

    // verify membership id with membership activity id
    $this->assertEquals($expectedMembershipId, $expectedMembershipActivityId);

    // click through to the relationship view screen
    $this->click('css=li#tab_rel a');
    $this->waitForElementPresent("xpath=//div[@class='crm-contact-relationship-current']/div/table/tbody/tr/td[2]/a");
    $this->assertTrue($this->isElementPresent("xpath=//div[@class='crm-contact-relationship-current']/div/table/tbody/tr/td[2]/a[text()='Organization $org1']"));
    $this->assertEquals(1, $this->getText("xpath=//li[@id='tab_rel']/a/em"));

    // Edit Contact but do not change any field
    $this->waitForElementPresent("xpath=//ul[@id='actions']/li[2]/a/span");
    $this->clickLink("xpath=//ul[@id='actions']/li[2]/a/span");
    $this->waitForElementPresent('_qf_Contact_cancel-bottom');
    $this->clickLink("_qf_Contact_upload_view-top");

    // click through to the membership view screen after edit
    $this->click('css=li#tab_member a');
    $this->waitForElementPresent("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->assertEquals(1, $this->getText("xpath=//li[@id='tab_member']/a/em"));

    $url = $this->parseURL($this->getAttribute("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']@href"));
    $actualMembershipId1 = $url['queryString']['id'];

    // click through to the activity view screen after edit
    $this->click('css=li#tab_activity a');
    $this->waitForElementPresent("xpath=//table[@class='contact-activity-selector-activity crm-ajax-table dataTable no-footer']/tbody/tr/td[8]/span/a");
    $this->assertEquals(1, $this->getText("xpath=//li[@id='tab_activity']/a/em"));
    $url = $this->parseURL($this->getAttribute("xpath=//table[@class='contact-activity-selector-activity dataTable no-footer']/tbody/tr/td[8]/span/a@href"));
    $actualMembershipActivityId1 = $url['queryString']['id'];

    // verify membership id and membership activity id with previous one
    // FIXME: These 2 lines are currently failing because the inherited membership and signup activity or being recreated when contact is edited/saved. dgg
    $this->assertEquals($expectedMembershipId, $actualMembershipId1);
    $this->assertEquals($expectedMembershipActivityId, $actualMembershipActivityId1);

    // click through to the relationship view screen after edit
    $this->click('css=li#tab_rel a');
    $this->waitForElementPresent("xpath=//div[@class='crm-contact-relationship-current']/div/table/tbody/tr/td[2]/a");
    $this->assertTrue($this->isElementPresent("xpath=//div[@class='crm-contact-relationship-current']/div/table/tbody/tr/td[2]/a[text()='Organization $org1']"));
    $this->assertEquals(1, $this->getText("xpath=//li[@id='tab_rel']/a/em"));

    // change the current employer of the contact
    // creating another membership type
    $this->openCiviPage('admin/member/membershipType', 'reset=1&action=browse');

    $this->click('link=Add Membership Type');
    $this->waitForElementPresent('_qf_MembershipType_cancel-bottom');

    $this->type('name', "Membership Type Another $title");

    $this->select2('member_of_contact_id', $title);

    $this->type('minimum_fee', '100');
    $this->select('financial_type_id', 'label=Member Dues');
    $this->type('duration_interval', 1);
    $this->select('duration_unit', 'label=year');

    $this->select('period_type', 'value=rolling');

    $this->select2('relationship_type_id', 'Employer of', TRUE);
    $this->type('max_related', '5');

    $this->click('_qf_MembershipType_upload-bottom');
    $this->waitForElementPresent('link=Add Membership Type');
    $this->waitForText('crm-notification-container', "Membership Type Another $title");

    // creating another Orgnization
    $this->openCiviPage('contact/add', 'reset=1&ct=Organization', '_qf_Contact_cancel');
    $org2 = substr(sha1(rand()), 0, 7);
    $this->type('organization_name', "Organization $org2");
    $this->type('email_1_email', "$org2@org.com");
    $this->clickLink('_qf_Contact_upload_view');

    // click through to the membership view screen
    $this->click('css=li#tab_member a');
    $this->waitForElementPresent('link=Add Membership');
    $this->click('link=Add Membership');
    $this->waitForElementPresent('_qf_Membership_cancel-bottom');

    // fill in Membership Organization and Type
    $this->select('membership_type_id[0]', "label=Organization $title");
    $this->select('membership_type_id[1]', "label=Membership Type Another $title");

    $sourceText = 'Membership ContactAddTest with Rolling Membership Type';
    $this->type('source', $sourceText);
    $this->clickLink('_qf_Membership_upload', 'link=Add Membership', FALSE);
    $this->waitForTextPresent($sourceText);

    // Is status message correct?
    $this->waitForText('crm-notification-container', "Membership Type Another $title");

    // edit contact
    $this->openCiviPage("contact/add", "reset=1&action=update&cid=$cid", "_qf_Contact_cancel-bottom");

    // change Current Employer
    $this->select2('employer_id', $org2);
    $this->clickLink("_qf_Contact_upload_view-bottom");

    // click through to the membership view screen
    $this->click('css=li#tab_member a');
    $this->waitForElementPresent("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->assertEquals(1, $this->getText("xpath=//li[@id='tab_member']/a/em"));
    $url = $this->parseURL($this->getAttribute("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']@href"));
    $actualMembershipId2 = $url['queryString']['id'];

    // click through to the activity view screen
    $this->click('css=li#tab_activity a');
    $this->waitForElementPresent("xpath=//table[@class='contact-activity-selector-activity dataTable no-footer']/tbody//tr/td[8]/span/a[text()='View']");
    $this->assertEquals(1, $this->getText("xpath=//li[@id='tab_activity']/a/em"));
    $url = $this->parseURL($this->getAttribute("xpath=//table[@class='contact-activity-selector-activity dataTable no-footer']/tbody//tr/td[8]/span/a[text()='View']@href"));
    $actualMembershipActivityId2 = $url['queryString']['id'];

    // verify membership id and membership activity id with previous one
    $this->assertNotEquals($expectedMembershipId, $actualMembershipId2);
    $this->assertNotEquals($expectedMembershipId, $actualMembershipActivityId2);

    // click through to the relationship view screen
    $this->click('css=li#tab_rel a');
    $this->waitForElementPresent("xpath=//div[@class='crm-contact-relationship-current']/div/table/tbody/tr/td[2]/a");
    $this->assertTrue($this->isElementPresent("xpath=//div[@class='crm-contact-relationship-current']/div/table/tbody/tr/td[2]/a[text()='Organization $org2']"));
    $this->assertEquals(1, $this->getText("xpath=//li[@id='tab_rel']/a/em"));

    // creating another Orgnization with no membership
    $this->openCiviPage('contact/add', 'reset=1&ct=Organization', '_qf_Contact_cancel');
    $org3 = substr(sha1(rand()), 0, 7);
    $this->type('organization_name', "Organization $org3");
    $this->type('email_1_email', "$org3@org.com");
    $this->clickLink('_qf_Contact_upload_view');

    // edit contact
    $this->openCiviPage("contact/add", "reset=1&action=update&cid=$cid", "_qf_Contact_cancel-bottom");

    // change Current Employer
    $this->select2('employer_id', $org3);
    $this->clickLink("_qf_Contact_upload_view-bottom");

    // click through to the membership view screen
    $this->click('css=li#tab_member a');

    $this->waitForAjaxContent();
    $this->waitForTextPresent("No memberships have been recorded for this contact.");
    $this->assertEquals(0, $this->getText("xpath=//li[@id='tab_member']/a/em"));

    // click through to the activity view screen
    $this->click('css=li#tab_activity a');
    $this->waitForText("xpath=//table[@class='contact-activity-selector-activity dataTable no-footer']/tbody/tr/td", "No matches found.");
    $this->assertEquals(0, $this->getText("xpath=//li[@id='tab_activity']/a/em"));

    // click through to the relationship view screen
    $this->click('css=li#tab_rel a');
    $this->waitForElementPresent("xpath=//div[@class='crm-contact-relationship-current']/div/table/tbody/tr/td[2]/a");
    $this->assertTrue($this->isElementPresent("xpath=//div[@class='crm-contact-relationship-current']/div/table/tbody/tr/td[2]/a[text()='Organization $org3']"));
    $this->assertEquals(1, $this->getText("xpath=//li[@id='tab_rel']/a/em"));
  }

}

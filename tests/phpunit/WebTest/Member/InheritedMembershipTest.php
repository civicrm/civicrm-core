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
class WebTest_Member_InheritedMembershipTest extends CiviSeleniumTestCase {
  protected function setUp() {
    parent::setUp();
  }

  function testInheritedMembership() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $this->openCiviPage('contact/add', 'reset=1&ct=Organization', '_qf_Contact_cancel');

    $title = substr(sha1(rand()), 0, 7);
    $this->type('organization_name', "Organization $title");
    $this->type('email_1_email', "$title@org.com");
    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', "Organization {$title} has been created.");

    // Go directly to the URL
    $this->openCiviPage('admin/member/membershipType', 'reset=1&action=browse');

    $this->click('link=Add Membership Type');
    $this->waitForElementPresent('_qf_MembershipType_cancel-bottom');

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

    $this->type('max_related', '5');

    $this->click('_qf_MembershipType_upload-bottom');
    $this->waitForElementPresent('link=Add Membership Type');
    $this->assertElementContainsText('crm-notification-container', "The membership type 'Membership Type $title' has been saved.");

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
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // page was loaded
    $this->waitForTextPresent($sourceText);

    // Is status message correct?
    $this->assertElementContainsText('crm-notification-container', "Membership Type $title membership for Organization $title1 has been added.",
      "Status message didn't show up after saving!");

    // click through to the membership view screen
    $this->click("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_MembershipView_cancel-bottom');

    $joinDate   = date('Y-m-d');
    $startDate  = date('Y-m-d');
    $endDate    = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 1, date('Y') + 1));
    $configVars = new CRM_Core_Config_Variables();
    foreach (array(
      'joinDate', 'startDate', 'endDate') as $date) {
      $$date = CRM_Utils_Date::customFormat($$date, $configVars->dateformatFull);
    }

    $this->webtestVerifyTabularData(
      array(
        'Membership Type' => "Membership Type $title",
        'Status' => 'New',
        'Source' => $sourceText,
        'Member Since' => $joinDate,
        'Start date' => $startDate,
        'End date' => $endDate,
        'Max related' => "5"
      )
    );

    // Adding contact
    // We're using Quick Add block on the main page for this.
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, 'Anderson', "$firstName@anderson.name");

    // visit relationship tab
    $this->click('css=li#tab_rel a');
    $this->waitForElementPresent('css=div.action-link');
    $this->click("//div[@class='crm-container-snippet']/div/div[1]/div[1]/a/span");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click('relationship_type_id');
    $this->select('relationship_type_id', 'label=Employee of');

    $this->webtestFillAutocomplete($title1);

    $this->waitForElementPresent('quick-save');

    $description = 'Well here is some description !!!!';
    $this->type('description', $description);

    //save the relationship
    $this->click('quick-save');
    $this->waitForElementPresent('current-relationships');
    //check the status message
    $this->assertElementContainsText('crm-notification-container', 'New relationship created.');

    $this->waitForElementPresent("xpath=//div[@id='current-relationships']//div//table/tbody//tr/td[9]/span/a[text()='View']");

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
    $this->waitForElementPresent("xpath=//div[@id='current-relationships']//div//table/tbody//tr/td[9]/span/a[text()='Edit']");
    $this->click("xpath=//div[@id='current-relationships']//div//table/tbody//tr/td[9]/span/a[text()='Edit']");
    $matches = array();
    preg_match('/cid=([0-9]+)/', $this->getLocation(), $matches);
    $id = $matches[1];

    $this->waitForElementPresent('is_active');
    //disable relationship
    if ($this->isChecked('is_active')) {
      $this->click('is_active');
    }
    $this->click('_qf_Relationship_upload');
    $this->waitForElementPresent('inactive-relationships');
    //check the status message
    $this->assertElementContainsText('crm-notification-container', 'Relationship record has been updated.');

    // click through to the membership view screen
    $this->click('css=li#tab_member a');

    //verify inherited membership has been removed
    $this->openCiviPage("contact/view", "reset=1&cid=$id&selectedChild=member", "xpath=//div[@class='crm-container-snippet']/div/div[3]");
    $this->assertElementContainsText('Memberships', 'No memberships have been recorded for this contact.');

    // visit relationship tab and re-enable the relationship
    $this->click('css=li#tab_rel a');
    $this->waitForElementPresent('css=div.action-link');
    $this->click("//li[@id='tab_rel']/a");

    $this->waitForElementPresent("xpath=//div[@id='inactive-relationships']//div//table/tbody//tr/td[7]/span/a[text()='Edit']");
    $this->click("xpath=//div[@id='inactive-relationships']//div//table/tbody//tr/td[7]/span/a[text()='Edit']");
    $this->waitForElementPresent('is_active');
    if (!$this->isChecked('is_active')) {
      $this->click('is_active');
    }
    $this->click('_qf_Relationship_upload');
    $this->waitForElementPresent('current-relationships');
    //check the status message
    $this->assertElementContainsText('crm-notification-container', 'Relationship record has been updated.');

    //check for memberships
    $this->click('css=li#tab_member a');
    $this->waitForElementPresent('css=div#memberships');

    //2 . visit relationship tab and disable the relationship (by links)
    //disable relationship
    $this->click('css=li#tab_rel a');
    $this->waitForElementPresent('css=div.action-link');
    $this->waitForElementPresent("xpath=//div[@id='current-relationships']//div//table/tbody//tr/td[9]/span[2][text()='more']/ul/li[1]/a[text()='Disable']");
    $this->click("xpath=//div[@id='current-relationships']//div//table/tbody//tr/td[9]/span[2][text()='more']/ul/li[1]/a[text()='Disable']");

    $this->assertTrue((bool)preg_match("/^Are you sure you want to disable this relationship?[\s\S]$/",
        $this->getConfirmation()
      ));
    $this->chooseOkOnNextConfirmation();
    sleep(10);

    //verify inherited membership has been removed
    $this->openCiviPage("contact/view", "reset=1&cid={$id}&selectedChild=member", "xpath=//div[@class='crm-container-snippet']/div/div[3]");
    $this->assertElementContainsText('Memberships', 'No memberships have been recorded for this contact.');

    //enable relationship
    $this->click('css=li#tab_rel a');
    $this->waitForElementPresent('css=div.action-link');

    $this->waitForElementPresent("xpath=//div[@id='inactive-relationships']//div//table/tbody//tr/td[7]/span[2][text()='more']/ul/li[1]/a[text()='Enable']");
    $this->click("xpath=//div[@id='inactive-relationships']//div//table/tbody//tr/td[7]/span[2][text()='more']/ul/li[1]/a[text()='Enable']");

    $this->assertTrue((bool)preg_match("/^Are you sure you want to re-enable this relationship?[\s\S]$/",
        $this->getConfirmation()
      ));
    $this->chooseOkOnNextConfirmation();
    sleep(10);

    //verify membership
    $this->click('css=li#tab_member a');
    $this->waitForElementPresent('css=div#memberships');
  }
}


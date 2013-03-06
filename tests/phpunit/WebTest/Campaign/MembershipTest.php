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
class WebTest_Campaign_MembershipTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testCreateCampaign() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();

    // Create new group
    $title = substr(sha1(rand()), 0, 7);
    $groupName = $this->WebtestAddGroup();

    // Adding contact
    // We're using Quick Add block on the main page for this.
    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName1, "Smith", "$firstName1.smith@example.org");

    // add contact to group
    // visit group tab
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // add to group
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName2, "John", "$firstName2.john@example.org");

    // add contact to group
    // visit group tab
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // add to group
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Enable CiviCampaign module if necessary
    $this->enableComponents(array('CiviCampaign'));

    // add the required Drupal permission
    $permissions = array('edit-2-administer-civicampaign');
    $this->changePermissions($permissions);

    // Go directly to the URL of the screen that you will be testing
    $this->openCiviPage('campaign/add', 'reset=1', '_qf_Campaign_upload-bottom');

    // Let's start filling the form with values.
    $campaignTitle = "Campaign $title";
    $this->type("title", $campaignTitle);

    // select the campaign type
    $this->select("campaign_type_id", "value=2");

    // fill in the description
    $this->type("description", "This is a test campaign");

    // include groups for the campaign
    $this->addSelection("includeGroups-f", "label=$groupName");
    $this->click("//option[@value=4]");
    $this->click("add");

    // fill the end date for campaign
    $this->webtestFillDate("end_date", "+1 year");

    // select campaign status
    $this->select("status_id", "value=2");

    // click save
    $this->click("_qf_Campaign_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertElementContainsText('crm-notification-container', "Campaign Campaign $title has been saved.",
      "Status message didn't show up after saving campaign!"
    );

    $this->waitForElementPresent("//div[@id='campaignList']/div[@class='dataTables_wrapper']/table/tbody/tr/td[text()='{$campaignTitle}']/../td[1]");
    $id = (int) $this->getText("//div[@id='campaignList']/div[@class='dataTables_wrapper']/table/tbody/tr/td[text()='{$campaignTitle}']/../td[1]");
    $this->memberAddTest($campaignTitle, $id);
  }

  function memberAddTest($campaignTitle, $id) {
    //Add new memebershipType
    $memTypeParams = $this->webtestAddMembershipType();
   
    // Adding Adding contact with randomized first name for test testContactContextActivityAdd
    // We're using Quick Add block on the main page for this.
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "John", $firstName . "john@gmail.com");
    $this->assertTrue($this->isTextPresent("{$firstName} John has been created."));

    // click through to the membership view screen
    $this->click("css=li#tab_member a");

    $this->waitForElementPresent("link=Add Membership");
    $this->click("link=Add Membership");

    $this->waitForElementPresent("_qf_Membership_cancel-bottom");

    // fill in Membership Organization and Type
    $this->select("membership_type_id_0", "label={$memTypeParams['member_of_contact']}");

    // Wait for membership type select to reload
    $this->waitForTextPresent($memTypeParams['membership_type']);
    $this->select("membership_type_id[1]", "label={$memTypeParams['membership_type']}");

    $sourceText = "Membership ContactAddTest Webtest";
    // fill in Source
    $this->type("source", $sourceText);

    // select campaign
    $this->click("campaign_id");
    $this->select("campaign_id", "value=$id");


    // Let Join Date stay default
    // fill in Start Date
    $this->webtestFillDate('start_date');

    // Clicking save.
    $this->click("_qf_Membership_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // page was loaded
    $this->waitForTextPresent($sourceText);

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("membership for $firstName John has been added."),
      "Status message didn't show up after saving!"
    );

    // click through to the membership view screen
    $this->click("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    $this->webtestVerifyTabularData(array('Campaign' => $campaignTitle));
  }
}


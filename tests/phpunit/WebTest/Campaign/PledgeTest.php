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
class WebTest_Campaign_PledgeTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testCreateCampaign() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Log in as admin first to verify permissions for CiviGrant
    $this->webtestLogin(TRUE);

    // Enable CiviCampaign module and CiviPledge module if necessary
    $this->enableComponents(array("CiviCampaign", "CiviPledge"));

    // add the required Drupal permission
    $permissions = array(
      'edit-2-access-civipledge',
      'edit-2-edit-pledges',
    );
    $this->changePermissions($permissions);

    $this->open($this->sboxPath . "civicrm/logout?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Log in as demo user
    $this->open($this->sboxPath);
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

    // Go directly to the URL of the screen that you will be testing
    $this->open($this->sboxPath . "civicrm/campaign/add?reset=1");

    // As mentioned before, waitForPageToLoad is not always reliable. Below, we're waiting for the submit
    // button at the end of this page to show up, to make sure it's fully loaded.
    $this->waitForElementPresent("_qf_Campaign_upload-bottom");

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

    $this->assertTrue($this->isTextPresent("Campaign Campaign $title has been saved."),
      "Status message didn't show up after saving campaign!"
    );

    $this->waitForElementPresent("//div[@id='campaignList']/div[@class='dataTables_wrapper']/table/tbody/tr/td[text()='{$campaignTitle}']/../td[1]");
    $id = (int) $this->getText("//div[@id='campaignList']/div[@class='dataTables_wrapper']/table/tbody/tr/td[text()='{$campaignTitle}']/../td[1]");
    $this->pledgeAddTest($campaignTitle, $id);
  }

  function pledgeAddTest($campaignTitle, $id) {
    // create unique name
    $name      = substr(sha1(rand()), 0, 7);
    $firstName = 'Adam' . $name;
    $lastName  = 'Jones' . $name;

    // create new contact
    $this->webtestAddContact($firstName, $lastName, $firstName . "@example.com");

    // wait for action element
    $this->waitForElementPresent('crm-contact-actions-link');

    // now add pledge from contact summary
    $this->click("//a[@id='crm-contact-actions-link']/span/div");

    // wait for add plegde link
    $this->waitForElementPresent('link=Add Pledge');

    $this->click('link=Add Pledge');

    // wait for pledge form to load completely
    $this->waitForElementPresent('_qf_Pledge_upload-bottom');

    // check contact name on pledge form
    $this->assertTrue($this->isTextPresent("$firstName $lastName"));

    // Let's start filling the form with values.
    $this->type("amount", "100");
    $this->type("installments", "10");
    $this->select("frequency_unit", "value=week");
    $this->type("frequency_day", "2");

    $this->webtestFillDate('acknowledge_date', 'now');

    // select campaign
    $this->click("campaign_id");
    $this->select("campaign_id", "value=$id");

    $this->select("contribution_page_id", "value=3");

    //Honoree section
    $this->click("Honoree");
    $this->waitForElementPresent("honor_email");

    $this->click("CIVICRM_QFID_1_2");
    $this->select("honor_prefix_id", "value=3");

    $honreeFirstName = 'First' . substr(sha1(rand()), 0, 4);
    $honreeLastName = 'last' . substr(sha1(rand()), 0, 7);
    $this->type("honor_first_name", $honreeFirstName);
    $this->type("honor_last_name", $honreeLastName);
    $this->type("honor_email", $honreeFirstName . "@example.com");

    //PaymentReminders
    $this->click("PaymentReminders");
    $this->waitForElementPresent("additional_reminder_day");
    $this->type("initial_reminder_day", "4");
    $this->type("max_reminders", "2");
    $this->type("additional_reminder_day", "4");

    $this->click("_qf_Pledge_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("Pledge has been recorded and the payment schedule has been created."));

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span[1]/a[text()='View']");
    //click through to the Pledge view screen
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span[1]/a[text()='View']");
    $this->waitForElementPresent("_qf_PledgeView_next-bottom");
    $pledgeDate = date('F jS, Y', strtotime('now'));

    // verify Activity created
    $this->verifyText("xpath=//form[@id='PledgeView']//table/tbody/tr[8]/td[2]", preg_quote($campaignTitle));
  }
}


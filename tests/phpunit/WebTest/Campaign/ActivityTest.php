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
 * Class WebTest_Campaign_ActivityTest
 */
class WebTest_Campaign_ActivityTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testCreateCampaign() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    $this->webtestLogin('admin');

    // Enable CiviCampaign module if necessary
    $this->enableComponents(array('CiviCampaign'));

    // add the required Drupal permission
    $permissions = array('edit-2-administer-civicampaign');
    $this->changePermissions($permissions);

    // Log in as normal user
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
    $this->waitForElementPresent('link=Remove');

    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName2, "John", "$firstName2.john@example.org");

    // add contact to group
    // visit group tab
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // add to group
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForElementPresent('link=Remove');

    $this->openCiviPage('campaign/add', 'reset=1', '_qf_Campaign_upload-bottom');

    $campaignTitle = "Campaign " . $title;
    $this->type("title", $campaignTitle);

    // select the campaign type
    $this->select("campaign_type_id", "value=2");

    // fill in the description
    $this->type("description", "This is a test campaign");

    // include groups for the campaign
    $this->multiselect2("includeGroups", array("$groupName", "Advisory Board"));

    // fill the end date for campaign
    $this->webtestFillDate("end_date", "+1 year");

    // select campaign status
    $this->select("status_id", "value=2");

    // click save
    $this->click("_qf_Campaign_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForText('crm-notification-container', "Campaign $title");

    $this->waitForElementPresent("xpath=//div[@id='campaignList']/div/table/tbody//tr/td[3]/div[text()='{$campaignTitle}']/../../td[1]");
    $id = (int) $this->getText("xpath=//div[@id='campaignList']/div/table/tbody//tr/td[3]/div[text()='{$campaignTitle}']/../../td[1]");
    $this->activityAddTest($campaignTitle, $id);
  }

  /**
   * @param $campaignTitle
   * @param int $id
   */
  public function activityAddTest($campaignTitle, $id) {
    // Adding Adding contact with randomized first name for test testContactContextActivityAdd
    // We're using Quick Add block on the main page for this.
    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName1, "Summerson", $firstName1 . "@summerson.name");
    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName2, "Anderson", $firstName2 . "@anderson.name");

    $this->click("css=li#tab_activity a");

    // waiting for the activity dropdown to show up
    $this->waitForElementPresent("other_activity");

    // Select the activity type from the activity dropdown
    $this->select("other_activity", "label=Meeting");

    // waitForPageToLoad is not always reliable. Below, we're waiting for the submit
    // button at the end of this page to show up, to make sure it's fully loaded.
    $this->waitForElementPresent("_qf_Activity_upload");

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->waitForElementPresent("//*[@id='s2id_target_contact_id']");
    $this->assertElementContainsText('//*[@id="s2id_target_contact_id"]/ul/li[1]/div', 'Anderson, ' . $firstName2, 'Contact not found in line ' . __LINE__);

    // Now we're filling the "Assigned To" field.
    // Typing contact's name into the field (using typeKeys(), not type()!)...
    $this->select2("assignee_contact_id", $firstName1, TRUE);

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->waitForElementPresent("//*[@id='s2id_assignee_contact_id']");
    $this->assertElementContainsText('//*[@id="s2id_assignee_contact_id"]/ul/li[1]/div', 'Summerson, ' . $firstName1, 'Contact not found in line ' . __LINE__);

    // Since we're here, let's check if screen help is being displayed properly
    //$this->assertTrue($this->isTextPresent("A copy of this activity will be emailed to each Assignee"));

    // Putting the contents into subject field - assigning the text to variable, it'll come in handy later
    $subject = "This is subject of test activity being added through activity tab of contact summary screen.";
    // For simple input fields we can use field id as selector
    $this->type("subject", $subject);

    // select campaign
    $this->waitForElementPresent("campaign_id");
    $this->click("campaign_id");
    $this->select("campaign_id", "value=$id");

    $this->type("location", "Some location needs to be put in this field.");

    // Choosing the Date.
    // Please note that we don't want to put in fixed date, since
    // we want this test to work in the future and not fail because
    // of date being set in the past. Therefore, using helper webtestFillDateTime function.
    $this->webtestFillDateTime('activity_date_time', '+1 month 11:10PM');

    // Setting duration.
    $this->type("duration", "30");

    // Putting in details.
    $this->type("details", "Really brief details information.");

    // Making sure that status is set to Scheduled (using value, not label).
    $this->select("status_id", "value=1");

    // Setting priority.
    $this->select("priority_id", "value=1");

    // Adding attachment
    // TODO TBD

    // Scheduling follow-up.
    $this->click("css=.crm-activity-form-block-schedule_followup div.crm-accordion-header");
    $this->select("followup_activity_type_id", "value=1");
    $this->webtestFillDateTime('followup_date', '+1 month 11:10PM');
    $this->type("followup_activity_subject", "This is subject of schedule follow-up activity");

    // Clicking save.
    $this->clickLink("_qf_Activity_upload", 'link=View', FALSE);

    // Is status message correct?
    $this->waitForText('crm-notification-container', $subject);

    $this->waitForElementPresent("xpath=//div[@class='crm-activity-selector-activity']/div[2]/table/tbody/tr[2]/td[8]/span[1]/a[1][text()='View']");

    // click through to the Activity view screen
    $this->click("xpath=//div[@class='crm-activity-selector-activity']/div[2]/table/tbody/tr[2]/td[8]/span[1]/a[1][text()='View']");
    $this->waitForElementPresent("xpath=//button//span[contains(text(),'Done')]");

    // verify Activity created
    $this->waitForAjaxContent();
    $this->verifyText("xpath=//form[@id='Activity']/div[2]/table/tbody/tr[5]/td[2]/span", $campaignTitle);
  }

}

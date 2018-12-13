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
 * Class WebTest_Campaign_CampaignDescriptionTest
 */
class WebTest_Campaign_CampaignDescriptionTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testCreateCampaign() {
    // Fixme: testing a theory that this test was failing due to permissions
    $this->webtestLogin('admin');

    // Create new group
    $title = substr(sha1(rand()), 0, 7);
    $groupName = $this->WebtestAddGroup();

    // Enable CiviCampaign module if necessary
    $this->enableComponents(array('CiviCampaign'));

    //Creating a new Campaign
    $this->openCiviPage('campaign/add', 'reset=1', '_qf_Campaign_upload-bottom');

    $campaignTitle = "Campaign $title";
    $this->type("title", $campaignTitle);

    // select the campaign type
    $this->select("campaign_type_id", "value=2");

    // fill in the description
    $campaignDescription = "This is a test campaign line 1 \n This is a test campaign line 2 \n This is a test campaign line 3";
    $this->type("description", $campaignDescription);

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

    //Opening Edit Page of the created Campaign
    $this->waitForElementPresent("//div[@id='campaignList']/div/table/tbody//tr/td[3]/div[text()='{$campaignTitle}']/../../td[13]/span/a[1][text()='Edit']");
    $this->clickLink("//div[@id='campaignList']/div/table/tbody//tr/td[3]/div[text()='{$campaignTitle}']/../../td[13]/span/a[1][text()='Edit']", "//textarea[@id='description']", FALSE);
    $this->assertTrue($this->isTextPresent($campaignDescription), 'Missing text: ' . $campaignDescription);
  }

  public function testAjaxCustomGroupLoad() {
    $this->webtestLogin();

    $this->enableComponents(array('CiviCampaign'));
    $triggerElement = array('name' => 'campaign_type_id', 'type' => 'select');
    $customSets = array(
      array('entity' => 'Campaign', 'subEntity' => 'Referral Program', 'triggerElement' => $triggerElement),
      array('entity' => 'Campaign', 'subEntity' => 'Constituent Engagement', 'triggerElement' => $triggerElement),
    );

    $pageUrl = array('url' => 'campaign/add', 'args' => 'reset=1');
    $this->customFieldSetLoadOnTheFlyCheck($customSets, $pageUrl);
  }

}

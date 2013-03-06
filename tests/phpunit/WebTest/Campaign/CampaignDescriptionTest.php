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
class WebTest_Campaign_CampaignDescriptionTest extends CiviSeleniumTestCase {

  protected $captureScreenshotOnFailure = TRUE;
  protected $screenshotPath = '/var/www/api.dev.civicrm.org/public/sc';
  protected $screenshotUrl = 'http://api.dev.civicrm.org/sc/';

  protected function setUp() {
    parent::setUp();
  }

  function testCreateCampaign() {

    $this->open($this->sboxPath);

    $this->webtestLogin();

    // Create new group
    $title = substr(sha1(rand()), 0, 7);
    $groupName = $this->WebtestAddGroup();

    // Enable CiviCampaign module if necessary
    $this->enableComponents(array('CiviCampaign'));

    //Creating a new Campaign
    $this->openCivipage('campaign/add', 'reset=1', '_qf_Campaign_upload-bottom');

    // Let's start filling the form with values.
    $campaignTitle = "Campaign $title";
    $this->type("title", $campaignTitle);

    // select the campaign type
    $this->select("campaign_type_id", "value=2");

    // fill in the description
    $campaignDescription = "This is a test campaign line 1 \n This is a test campaign line 2 \n This is a test campaign line 3";
    $this->type("description", $campaignDescription);

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

    //Opening Edit Page of the created Campaign
    $this->waitForElementPresent("//div[@id='campaignList']/div[@class='dataTables_wrapper']/table/tbody//tr/td[text()='{$campaignTitle}']/../td[13]/span/a[text()='Edit']");
    $this->click("//div[@id='campaignList']/div[@class='dataTables_wrapper']/table/tbody//tr/td[text()='{$campaignTitle}']/../td[13]/span/a[text()='Edit']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Checking for Proper description present
    $this->waitForElementPresent("//textarea[@id='description']");
    $fetchedVaue = $this->getValue('description');
    $this->assertEquals($campaignDescription, $fetchedVaue);
  }
}



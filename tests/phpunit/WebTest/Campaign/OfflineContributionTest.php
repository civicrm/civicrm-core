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
class WebTest_Campaign_OfflineContributionTest extends CiviSeleniumTestCase {

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

    $this->openCiviPage('campaign', 'reset=1', "link=Add Campaign");
    if ($this->isTextPresent('No campaigns found.')) {
      // Go directly to the URL of the screen that you will be testing (Register Participant for Event-standalone).
      $this->openCiviPage('contribute/add', 'reset=1&action=add&context=standalone', '_qf_Contribution_cancel-bottom');
      $this->assertElementContainsText('crm-container', 'There are currently no active Campaigns.');
    }

    // Go directly to the URL of the screen that you will be testing
    $this->openCiviPage('campaign/add', 'reset=1');

    // As mentioned before, waitForPageToLoad is not always reliable. Below, we're waiting for the submit
    // button at the end of this page to show up, to make sure it's fully loaded.

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

    $this->assertElementContainsText('crm-notification-container', "Campaign $campaignTitle has been saved.",
      "Status message didn't show up after saving campaign!"
    );

    $this->waitForElementPresent("xpath=//div[@id='campaignList']/div[@id='campaigns_wrapper']/table[@id='campaigns']/tbody//tr/td[text()='$campaignTitle']");
    $url = explode('id=', $this->getAttribute("xpath=//div[@id='campaignList']/div[@id='campaigns_wrapper']/table[@id='campaigns']/tbody//tr/td[text()='$campaignTitle']/../td[13]/span/a[text()='Edit']@href"));
    $campaignId = $url[1];

    $this->offlineContributionTest($campaignTitle, $campaignId);

    $this->pastCampaignsTest($groupName);
  }

  function offlineContributionTest($campaignTitle, $id, $past = FALSE) {
    // Create a contact to be used as soft creditor
    $softCreditFname = substr(sha1(rand()), 0, 7);
    $softCreditLname = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($softCreditFname, $softCreditLname, FALSE);

    // Adding contact with randomized first name (so we can then select that contact when creating contribution.)
    // We're using Quick Add block on the main page for this.
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Summerson", $firstName . "@summerson.name");

    // go to contribution tab and add contribution.
    $this->click("css=li#tab_contribute a");

    // wait for Record Contribution elenment.
    $this->waitForElementPresent("link=Record Contribution (Check, Cash, EFT ...)");
    $this->click("link=Record Contribution (Check, Cash, EFT ...)");

    $this->waitForElementPresent("_qf_Contribution_cancel-bottom");
        // fill financial type.
        $this->select("financial_type_id", "Donation");

    // fill in Received Date
    $this->webtestFillDate('receive_date');

    // source
    $this->type("source", "Mailer 1");

    if ($past) {
      $this->click("include-past-campaigns");
      sleep(2);
    }

    $this->click("campaign_id");
    $this->select("campaign_id", "value=$id");

    // total amount
    $this->type("total_amount", "100");

    // select payment instrument type = Check and enter chk number
    $this->select("payment_instrument_id", "value=4");
    $this->waitForElementPresent("check_number");
    $this->type("check_number", "check #1041");

    $this->type("trxn_id", "P20901X1" . rand(100, 10000));

    // soft credit
    $this->click("soft_credit_to");
    $this->type("soft_credit_to", $softCreditFname);
    $this->typeKeys("soft_credit_to", $softCreditFname);
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");

    //Additional Detail section
    $this->click("AdditionalDetail");
    $this->waitForElementPresent("thankyou_date");

    $this->type("note", "Test note for {$firstName}.");
    $this->type("non_deductible_amount", "10");
    $this->type("fee_amount", "0");
    $this->type("net_amount", "0");
    $this->type("invoice_id", time());
    $this->webtestFillDate('thankyou_date');

    //Honoree section
    $this->click("Honoree");
    $this->waitForElementPresent("honor_email");

    $this->click("CIVICRM_QFID_1_2");
    $this->select("honor_prefix_id", "label=Ms.");
    $this->type("honor_first_name", "Foo");
    $this->type("honor_last_name", "Bar");
    $this->type("honor_email", "foo@bar.com");

    //Premium section
    $this->click("Premium");
    $this->waitForElementPresent("fulfilled_date");
    $this->select("product_name[0]", "label=Coffee Mug ( MUG-101 )");
    $this->select("product_name[1]", "label=Black");
    $this->webtestFillDate('fulfilled_date');

    // Clicking save.
    $this->click("_qf_Contribution_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertElementContainsText('crm-notification-container', "The contribution record has been saved");

    $this->waitForElementPresent("xpath=//div[@id='Contributions']//table/tbody/tr/td[8]/span/a[text()='View']");

    // click through to the Contribution view screen
    $this->click("xpath=//div[@id='Contributions']//table/tbody/tr/td[8]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ContributionView_cancel-bottom');

    // verify Contribution created
    $this->webtestVerifyTabularData(array('Campaign' => $campaignTitle));

    if ($past) {
      // when campaign component is disabled
      $this->openCiviPage('admin/setting/component', 'reset=1', '_qf_Component_next-bottom');
      $this->addSelection("enableComponents-t", "label=CiviCampaign");
      $this->click("//option[@value='CiviCampaign']");
      $this->click("remove");
      $this->click("_qf_Component_next-bottom");
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->assertTrue($this->isTextPresent("Changes Saved."));

      $this->openCiviPage('contribute/search', 'reset=1', '_qf_Search_refresh');      

      $this->type('sort_name', $firstName);
      $this->click("_qf_Search_refresh");
      $this->waitForElementPresent("_qf_Search_next_print");
      $this->click("xpath=//div[@id='contributionSearch']/table/tbody/tr/td[11]/span/a[text()='Edit']");
      $this->waitForElementPresent("_qf_Contribution_cancel-bottom");
      $this->assertTrue($this->isTextPresent("$campaignTitle"));
    }
  }

  function pastCampaignsTest($groupName) {
    // Go directly to the URL of the screen that you will be testing
    $this->openCiviPage('campaign/add', 'reset=1', '_qf_Campaign_upload-bottom');

    // Let's start filling the form with values.
    $pastTitle = substr(sha1(rand()), 0, 7);
    $pastCampaignTitle = "Past Campaign $pastTitle";
    $this->type("title", $pastCampaignTitle);

    // select the campaign type
    $this->select("campaign_type_id", "value=2");

    // fill in the description
    $this->type("description", "This is a test for past campaign");

    // include groups for the campaign
    $this->addSelection("includeGroups-f", "label=$groupName");
    $this->click("//option[@value=4]");
    $this->click("add");

    // fill the start date for campaign
    $this->webtestFillDate("start_date", "1 January 2011");

    // fill the end date for campaign
    $this->webtestFillDate("end_date", "31 January 2011");

    // select campaign status
    $this->select("status_id", "value=3");

    // click save
    $this->click("_qf_Campaign_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("Campaign $pastCampaignTitle has been saved."),
      "Status message didn't show up after saving campaign!"
    );

    $this->waitForElementPresent("link=Add Campaign");

    $this->waitForElementPresent("Campaigns");
    $this->click("search_form_campaign");
    $this->type("campaign_title", $pastCampaignTitle);
    $this->click("xpath=//div[@class='crm-accordion-body']/table/tbody/tr[4]/td/a[text()='Search']");

    $this->waitForElementPresent("xpath=//div[@id='campaignList']/div[@id='campaigns_wrapper']/table[@id='campaigns']/tbody//tr/td[text()='$pastCampaignTitle']");
    $url = explode('id=', $this->getAttribute("xpath=//div[@id='campaignList']/div[@id='campaigns_wrapper']/table[@id='campaigns']/tbody//tr/td[text()='$pastCampaignTitle']/../td[13]/span/a[text()='Edit']@href"));
    $campaignId = $url[1];

    $this->offlineContributionTest($pastCampaignTitle, $campaignId, TRUE);
  }
}


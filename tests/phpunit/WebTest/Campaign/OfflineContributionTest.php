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
 * Class WebTest_Campaign_OfflineContributionTest
 */
class WebTest_Campaign_OfflineContributionTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testCreateCampaign() {
    $this->webtestLogin('admin');

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

    // Enable CiviCampaign module if necessary
    $this->enableComponents(array('CiviCampaign'));

    // add the required permission
    $this->changePermissions('edit-2-administer-civicampaign');

    // Log in as normal user
    $this->webtestLogin();

    $this->openCiviPage('campaign', 'reset=1', "link=Add Campaign");
    if ($this->isTextPresent('None found.')) {
      $this->openCiviPage('contribute/add', 'reset=1&action=add&context=standalone', '_qf_Contribution_cancel-bottom');
      $this->assertElementContainsText('crm-container', 'There are currently no active Campaigns.');
    }
    $this->openCiviPage('campaign/add', 'reset=1');

    $campaignTitle = "Campaign $title";
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
    $this->clickLink("_qf_Campaign_upload-bottom");

    $this->checkCRMAlert("Campaign $title");

    $this->waitForElementPresent("//td[3]/div[text()='$campaignTitle']");
    $campaignId = $this->urlArg('id', $this->getAttribute("//td[3]/div[text()='$campaignTitle']/../../td[13]/span/a[text()='Edit']@href"));

    $this->offlineContributionTest($campaignTitle, $campaignId);

    $this->pastCampaignsTest($groupName);
  }

  /**
   * @param $campaignTitle
   * @param int $id
   * @param bool $past
   */
  public function offlineContributionTest($campaignTitle, $id, $past = FALSE) {
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
      $this->waitForElementPresent("css=#campaign_id option[value=$id]");
    }

    $this->select("campaign_id", "value=$id");

    // total amount
    $this->type("total_amount", "100");

    // select payment instrument type = Check and enter chk number
    $this->select("payment_instrument_id", "value=4");
    $this->waitForElementPresent("check_number");
    $this->type("check_number", "check #1041");

    $this->type("trxn_id", "P20901X1" . rand(100, 10000));

    // soft credit
    $this->click("xpath=id('Contribution')/div[2]/div[@id='softCredit']/div[1]");
    $this->type("soft_credit_amount_1", "50");
    $this->waitForElementPresent("soft_credit_contact_id_1");
    $this->webtestFillAutocomplete("{$softCreditLname}, {$softCreditFname}", 'soft_credit_contact_id_1');

    //Additional Detail section
    $this->click("AdditionalDetail");
    $this->waitForElementPresent("thankyou_date");

    $this->type("note", "Test note for {$firstName}.");
    $this->type("non_deductible_amount", "10");
    $this->type("fee_amount", "0");
    $this->type("net_amount", "0");
    $this->type("invoice_id", time());
    $this->webtestFillDate('thankyou_date');

    //Premium section
    $this->click("Premium");
    $this->waitForElementPresent("fulfilled_date");
    $this->select("product_name[0]", "label=Coffee Mug ( MUG-101 )");
    $this->select("product_name[1]", "label=Black");
    $this->webtestFillDate('fulfilled_date');

    // Clicking save.
    $this->click("_qf_Contribution_upload-bottom");

    // Is status message correct?
    $this->checkCRMAlert("The contribution record has been saved.");
    $this->waitForElementPresent("xpath=//*[@id='Search']//div[2]//table[2]/tbody/tr/td[8]/span//a[text()='View']");

    // click through to the Contribution view screen
    $this->click("xpath=//*[@id='Search']//div[2]//table[2]/tbody/tr/td[8]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ContributionView_cancel-bottom');

    // verify Contribution created
    $this->webtestVerifyTabularData(array('Campaign' => $campaignTitle));

    if ($past) {
      // when campaign component is disabled
      $this->openCiviPage('admin/setting/component', 'reset=1', '_qf_Component_next-bottom');
      $this->addSelection("enableComponents-t", "label=CiviCampaign");
      $this->click("//option[@value='CiviCampaign']");
      $this->click("remove");
      $this->clickLink("_qf_Component_next-bottom");

      $this->checkCRMAlert("Changes Saved");

      $this->openCiviPage('contribute/search', 'reset=1', '_qf_Search_refresh');

      $this->type('sort_name', $firstName);
      $this->click("_qf_Search_refresh");
      $this->waitForElementPresent("xpath=//table[@class='selector row-highlight']/tbody/tr/td[10]/span//a[text()='Edit']");
      $this->click("xpath=//table[@class='selector row-highlight']/tbody/tr/td[10]/span//a[text()='Edit']");
      $this->waitForElementPresent("_qf_Contribution_cancel-bottom");
      $this->assertTrue($this->isTextPresent("$campaignTitle"));
    }
  }

  /**
   * @param string $groupName
   */
  public function pastCampaignsTest($groupName) {
    $this->openCiviPage('campaign/add', 'reset=1', '_qf_Campaign_upload-bottom');

    $pastTitle = substr(sha1(rand()), 0, 7);
    $pastCampaignTitle = "Past Campaign $pastTitle";
    $this->type("title", $pastCampaignTitle);

    // select the campaign type
    $this->select("campaign_type_id", "value=2");

    // fill in the description
    $this->type("description", "This is a test for past campaign");

    // include groups for the campaign
    $this->multiselect2("includeGroups", array("$groupName", "Advisory Board"));

    // fill the start date for campaign
    $this->webtestFillDate("start_date", "1 January " . (date('Y') - 1));

    // fill the end date for campaign
    $this->webtestFillDate("end_date", "31 January " . (date('Y') - 1));

    // select campaign status
    $this->select("status_id", "value=3");

    // click save
    $this->clickLink("_qf_Campaign_upload-bottom");
    $this->checkCRMAlert("Campaign $pastCampaignTitle has been saved.");

    $this->waitForElementPresent("link=Add Campaign");
    $this->waitForElementPresent("link=Campaigns");
    $this->click("search_form_campaign");
    $this->type("campaign_title", $pastCampaignTitle);
    $this->clickAjaxLink("//a[text()='Search']", "//td[3]/div[text()='$pastCampaignTitle']");
    $campaignId = $this->urlArg('id', $this->getAttribute("//td[3]/div[text()='$pastCampaignTitle']/../../td[13]/span/a[text()='Edit']@href"));

    $this->offlineContributionTest($pastCampaignTitle, $campaignId, TRUE);
  }

}

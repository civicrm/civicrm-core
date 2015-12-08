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
 * Class WebTest_Campaign_OnlineContributionTest
 */
class WebTest_Campaign_OnlineContributionTest extends CiviSeleniumTestCase {

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
    $permissions = array(
      'edit-2-administer-civicampaign',
      'edit-1-make-online-contributions',
      'edit-1-profile-listings-and-forms',
    );
    $this->changePermissions($permissions);

    // Log in as normal user
    $this->webtestLogin();
    $this->openCiviPage("campaign/add", "reset=1", "_qf_Campaign_upload-bottom");

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
    $this->click("_qf_Campaign_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForText('crm-notification-container', "Campaign $title");

    $this->waitForElementPresent("//div[@id='campaignList']/div/table/tbody//tr/td[3]/div[text()='{$campaignTitle}']/../../td[1]");
    $id = (int) $this->getText("//div[@id='campaignList']/div/table/tbody//tr/td[3]/div[text()='{$campaignTitle}']/../../td[1]");

    $this->onlineContributionAddTest($campaignTitle, $id);
  }

  /**
   * @param $campaignTitle
   * @param int $id
   */
  public function onlineContributionAddTest($campaignTitle, $id) {
    // Use default payment processor
    $processorName = 'Test Processor';
    $paymentProcessorId = $this->webtestAddPaymentProcessor($processorName);

    $this->openCiviPage("admin/contribute/add", "reset=1&action=add");

    $contributionTitle = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);

    // fill in step 1 (Title and Settings)
    $contributionPageTitle = "Title $contributionTitle";
    $this->type('title', $contributionPageTitle);
    $this->select('financial_type_id', 'value=1');

    // select campaign
    $this->click("campaign_id");
    $this->select("campaign_id", "value=$id");

    $this->fillRichTextField('intro_text', 'This is Test Introductory Message', 'CKEditor');
    $this->fillRichTextField('footer_text', 'This is Test Footer Message', 'CKEditor');

    // Submit form
    $this->clickLink('_qf_Settings_next', "_qf_Amount_next-bottom");

    // Get contribution page id
    $pageId = $this->urlArg('id');

    //this contribution page for online contribution
    $this->check("payment_processor[{$paymentProcessorId}]");
    $this->assertElementContainsText('crm-container', "Contribution Amounts section enabled");
    $this->type("label_1", "amount 1");
    $this->type("value_1", "100");
    $this->type("label_2", "amount 2");
    $this->type("value_2", "200");
    $this->click("xpath=//*[@id='map-field-table']//tr[2]//input[1][@name='default']");

    $this->click("_qf_Amount_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // go to step 4
    $this->click("//div[@id='mainTabContainer']/ul//li/a[text()='Receipt']");
    $this->waitForElementPresent('_qf_ThankYou_next-bottom');

    // fill in step 4 (Thanks and Receipt)
    $this->type('thankyou_title', "Thank-you Page Title $contributionTitle");
    $this->type('receipt_from_name', "Receipt From Name $contributionTitle");
    $this->type('receipt_from_email', "$contributionTitle@example.org");
    $this->type('receipt_text', "Receipt Message $contributionTitle");
    $this->type('cc_receipt', "$contributionTitle@example.net");
    $this->type('bcc_receipt', "$contributionTitle@example.com");

    $this->click('_qf_ThankYou_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // go to step 5
    $this->click("//div[@id='mainTabContainer']/ul//li/a[text()='Tell a Friend']");
    $this->waitForElementPresent("_qf_Contribute_next-bottom");

    // fill in step 5 (Tell a Friend)
    $this->click('tf_is_active');
    $this->type('tf_title', "TaF Title $contributionTitle");
    $this->type('intro', "TaF Introduction $contributionTitle");
    $this->type('suggested_message', "TaF Suggested Message $contributionTitle");
    $this->type('general_link', "TaF Info Page Link $contributionTitle");
    $this->type('tf_thankyou_title', "TaF Thank-you Title $contributionTitle");
    $this->type('tf_thankyou_text', "TaF Thank-you Message $contributionTitle");

    $this->click('_qf_Contribute_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // go to step 6
    $this->click("//div[@id='mainTabContainer']/ul//li/a[text()='Profiles']");
    $this->waitForElementPresent("_qf_Custom_next-bottom");

    // fill in step 6 (Include Profiles)
    $this->select('css=tr.crm-contribution-contributionpage-custom-form-block-custom_pre_id span.crm-profile-selector-select select', 'value=1');

    $this->click('_qf_Custom_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // go to step 7
    $this->click("//div[@id='mainTabContainer']/ul//li/a[text()='Premiums']");
    $this->waitForElementPresent("_qf_Premium_next-bottom");

    // fill in step 7 (Premiums)
    $this->click('premiums_active');
    $this->type('premiums_intro_title', "Prem Title $contributionTitle");
    $this->type('premiums_intro_text', "Prem Introductory Message $contributionTitle");
    $this->type('premiums_contact_email', "$contributionTitle@example.info");
    $this->type('premiums_contact_phone', rand(100000000, 999999999));
    $this->click('premiums_display_min_contribution');
    $this->type('premiums_nothankyou_label', "No Thank you ");

    $this->click('_qf_Premium_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // go to step 8
    $this->click("//div[@id='mainTabContainer']/ul//li/a[text()='Widgets']");
    $this->waitForElementPresent("_qf_Widget_next-bottom");

    // fill in step 8 (Widget Settings)
    $this->click('is_active');
    $this->type('url_logo', "URL to Logo Image $contributionTitle");
    $this->type('button_title', "Button Title $contributionTitle");
    $this->type('about', "About $contributionTitle");

    $this->click('_qf_Widget_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // go to step 9
    $this->click("//div[@id='mainTabContainer']/ul//li/a[text()='Personal Campaigns']");
    $this->waitForElementPresent("_qf_Contribute_next-bottom");

    // fill in step 9 (Enable Personal Campaign Pages)
    $this->click('pcp_active');
    $this->click('is_approval_needed');
    $this->type('notify_email', "$contributionTitle@example.name");
    $this->select('supporter_profile_id', 'value=2');
    $this->type('tellfriend_limit', 7);
    $this->type('link_text', "'Create Personal Campaign Page' link text $contributionTitle");

    // submit new contribution page
    $this->click('_qf_Contribute_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Make sure our page shows up in search results
    $this->openCiviPage("admin/contribute", "reset=1", "_qf_SearchContribution_refresh");
    $this->type('title', $contributionPageTitle);
    $this->click("_qf_SearchContribution_refresh");
    $this->waitForPageToLoad(2 * $this->getTimeoutMsec());
    $url = $this->assertElementContainsText("//div[@id='configure_contribution_page']//table/tbody", $contributionPageTitle);

    //logout
    $this->webtestLogout();

    //Open Live Contribution Page
    $this->openCiviPage('contribute/transact', "reset=1&id=$pageId&action=preview", '_qf_Main_upload-bottom');

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    $this->type("email-5", $firstName . "@example.com");

    $streetAddress = "100 Main Street";
    $this->type("street_address-1", $streetAddress);
    $this->type("city-1", "San Francisco");
    $this->type("postal_code-1", "94117");
    $this->select("country-1", "value=1228");
    $this->select("state_province-1", "value=1001");

    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");

    //Billing Info
    $this->type("billing_first_name", $firstName . "billing");
    $this->type("billing_last_name", $lastName . "billing");
    $this->type("billing_street_address-5", "15 Main St.");
    $this->type(" billing_city-5", "San Jose");
    $this->select("billing_country_id-5", "value=1228");
    $this->select("billing_state_province_id-5", "value=1004");
    $this->type("billing_postal_code-5", "94129");
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");

    $this->clickLink("_qf_Confirm_next-bottom", NULL);

    //login to check contribution
    $this->webtestLogin();

    //Find Contribution
    $this->openCiviPage("contribute/search", "reset=1", "contribution_date_low");
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Contribution is a Test?')]/../../td[2]/label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->type("sort_name", "$lastName $firstName");
    $this->clickLink("_qf_Search_refresh", "xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", FALSE);
    $this->clickLink("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", "_qf_ContributionView_cancel-bottom", FALSE);
    //View Contribution Record
    $this->verifyText("xpath=id('ContributionView')/div[2]/table[1]/tbody/tr[11]/td[2]", preg_quote($campaignTitle));
  }

}

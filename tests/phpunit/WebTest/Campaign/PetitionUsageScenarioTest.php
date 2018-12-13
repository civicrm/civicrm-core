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
 * Class WebTest_Campaign_PetitionUsageScenarioTest
 */
class WebTest_Campaign_PetitionUsageScenarioTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testPetitionUsageScenario() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    $this->webtestLogin('admin');

    // Enable CiviCampaign module if necessary
    $this->enableComponents("CiviCampaign");

    // handle permissions early

    // let's give permission 'sign CiviCRM Petition' to anonymous user.
    $permissions = array(
      // give profile related permision
      "edit-1-sign-civicrm-petition",
      "edit-1-profile-create",
      "edit-1-profile-edit",
      "edit-1-profile-listings",
      "edit-1-profile-view",
      // now give full permissions to CiviPetition to registered user
      "edit-2-administer-civicampaign",
      "edit-2-manage-campaign",
      "edit-2-gotv-campaign-contacts",
      "edit-2-interview-campaign-contacts",
      "edit-2-release-campaign-contacts",
      "edit-2-reserve-campaign-contacts",
      "edit-2-sign-civicrm-petition",
    );
    $this->changePermissions($permissions);

    // Log in as normal user
    $this->webtestLogin();

    /////////////// Create Campaign ///////////////////////////////

    $this->openCiviPage("campaign/add", "reset=1", "_qf_Campaign_upload-bottom");

    $title = substr(sha1(rand()), 0, 7);
    $this->type("title", "$title Campaign");

    // select the campaign type
    $this->select("campaign_type_id", "value=2");

    // fill in the description
    $this->type("description", "This is a test campaign");

    // include groups for the campaign
    $this->addSelection("includeGroups", "label=Advisory Board");
    $this->click("//option[@value=4]");

    // fill the end date for campaign
    $this->webtestFillDate("end_date", "+1 year");

    // select campaign status
    $this->select("status_id", "value=2");

    // click save
    $this->clickLink('_qf_Campaign_upload-bottom');

    $this->waitForText('crm-notification-container', "Campaign $title Campaign has been saved.");

    ////////////// Create petition using New Individual profile //////////////////////

    $this->openCiviPage("petition/add", "reset=1", "_qf_Petition_next-bottom");

    // fill petition tile.
    $title = substr(sha1(rand()), 0, 7);
    $this->type("title", "$title Petition");

    // fill introduction
    //$this->type("cke_instructions", "This is introduction of $title Petition");

    // select campaign
    $this->select("campaign_id", "value=1");

    // select profile
    $this->select("contact_profile_id", "value=4");

    // click save
    $this->clickLink('_qf_Petition_next-bottom');

    $this->waitForText('crm-notification-container', "Petition has been saved.");

    $this->waitForElementPresent("link=Add Petition");
    $this->waitForElementPresent("search_form_petition");
    $this->click("search_form_petition");
    $this->type("petition_title", $title);

    $this->click("xpath=//div[@class='crm-accordion-body']/table/tbody/tr[2]/td/a[text()='Search']");
    $this->waitForElementPresent("xpath=//div[@id='petitionList']/div/table/tbody//tr//td[@class=' crm-petition-action']//span[text()='more']/ul//li/a[text()='Sign']");
    $url = $this->getAttribute("xpath=//div[@id='petitionList']/div/table/tbody//tr//td[@class=' crm-petition-action']//span[text()='more']/ul//li/a[text()='Sign']@href");

    ////////////// Retrieve Sign Petition Url /////////////////////////

    // logout and sign as anonymous.
    $this->webtestLogout();

    // go to the link that you will be sign as anonymous
    $this->open($url);
    $this->waitForElementPresent("_qf_Signature_next-bottom");

    // fill first name
    $firstName = substr(sha1(rand()), 0, 7);
    $this->type("first_name", $firstName);

    // fill last name
    $lastName = substr(sha1(rand()), 0, 7);
    $this->type("last_name", $lastName);

    // fill email
    $email = $firstName . "@" . $lastName . ".com";
    $this->type("email-Primary", $email);

    // click Sign the petition.
    $this->clickLink('_qf_Signature_next-bottom', NULL);
    $this->waitForText('page-title', "Thank You");

    // login
    $this->webtestLogin();

    $this->openCiviPage("campaign", "reset=1&subPage=petition", "link=Add Petition");

    // check for unconfirmed petition signature
    $this->waitForElementPresent("search_form_petition");
    $this->click("search_form_petition");
    $this->type("petition_title", $title);
    $this->click("xpath=//div[@class='crm-accordion-body']/table/tbody/tr[2]/td/a[text()='Search']");

    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[10]/span[2][text()='more']");
    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[10]/span[2][text()='more']/ul/li[3]/a[text()='Signatures']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // verify tabular data
    $expected = array(
      2 => 'Petition',
      3 => "$title Petition",
      4 => "$lastName, $firstName",
      5 => "$lastName, $firstName",
      8 => 'Scheduled',
    );

    foreach ($expected as $column => $value) {
      $this->verifyText("xpath=//div[@class='crm-search-results']/table/tbody/tr[2]/td[$column]/", preg_quote($value));
    }

    // ONCE MORE, NO EMAIL VERIFICATION AND CUSTOM THANK-YOU
    $this->openCiviPage("petition/add", "reset=1", "_qf_Petition_next-bottom");

    // fill petition tile.
    $title = substr(sha1(rand()), 0, 7);
    $this->type("title", "$title Petition");

    // fill introduction
    //$this->type("cke_instructions", "This is introduction of $title Petition");

    // select campaign
    $this->select("campaign_id", "value=1");

    // select profile
    $this->select("contact_profile_id", "value=4");

    // bypass email confirmation
    $this->click("bypass_confirm");

    // set custom thank-you title and text
    $this->type('thankyou_title', "Awesome $title donation");
    $this->fillRichTextField('thankyou_text', "Thank you for your kind contribution to support $title", 'CKEditor');

    // click save
    $this->clickLink('_qf_Petition_next-bottom');

    $this->waitForText('crm-notification-container', "Petition has been saved.");

    $this->waitForElementPresent("link=Add Petition");

    $this->waitForElementPresent("search_form_petition");
    $this->click("search_form_petition");
    $this->type("petition_title", $title);

    $this->click("xpath=//div[@class='crm-accordion-body']/table/tbody/tr[2]/td/a[text()='Search']");

    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[10]/span[2][text()='more']/ul/li[2]/a[text()='Sign']");
    $url = $this->getAttribute("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[10]/span[2][text()='more']/ul/li[2]/a[text()='Sign']@href");

    // logout and sign as anonymous.
    $this->webtestLogout();

    // go to the link that you will be sign as anonymous
    $this->open($url);
    $this->waitForElementPresent("_qf_Signature_next-bottom");

    // fill first name
    $firstName = substr(sha1(rand()), 0, 7);
    $this->type("first_name", $firstName);

    // fill last name
    $lastName = substr(sha1(rand()), 0, 7);
    $this->type("last_name", $lastName);

    // fill email
    $email = $firstName . "@" . $lastName . ".com";
    $this->type("email-Primary", $email);

    // click Sign the petition.
    $this->clickLink('_qf_Signature_next-bottom', 'thankyou_text');

    // check that thank-you page has appropriate title and message
    $this->waitForText('page-title', "Awesome $title donation");
    $this->waitForText('thankyou_text', "Thank you for your kind contribution to support $title");

    // login
    $this->webtestLogin();

    $this->openCiviPage("campaign", "reset=1&subPage=petition", "link=Add Petition");

    // check for confirmed petition signature
    $this->waitForElementPresent("search_form_petition");
    $this->click("search_form_petition");
    $this->type("petition_title", $title);
    $this->click("xpath=//div[@class='crm-accordion-body']/table/tbody/tr[2]/td/a[text()='Search']");

    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[10]/span[2][text()='more']");
    $this->click("xpath=//table[@class='petitions dataTable no-footer']/tbody/tr/td[10]/span[2][text()='more']/ul/li[3]/a[text()='Signatures']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // verify tabular data
    $expected = array(
      2 => 'Petition',
      3 => "$title Petition",
      4 => "$lastName, $firstName",
      5 => "$lastName, $firstName",
      8 => 'Completed',
    );

    foreach ($expected as $column => $value) {
      $this->verifyText("xpath=//div[@class='crm-search-results']/table/tbody/tr[2]/td[$column]/", preg_quote($value));
    }
  }

}

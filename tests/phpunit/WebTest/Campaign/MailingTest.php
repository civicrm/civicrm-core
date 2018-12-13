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
 * Class WebTest_Campaign_MailingTest
 */
class WebTest_Campaign_MailingTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testCreateCampaign() {
    // Log in as admin first to verify permissions for CiviCampaign
    $this->webtestLogin('admin');

    // Enable CiviCampaign module if necessary
    $this->enableComponents(array('CiviMail', 'CiviCampaign'));

    $this->setupDefaultMailbox();

    // add the required permission
    $this->changePermissions('edit-2-administer-civicampaign');

    // Log in as normal user
    $this->webtestLogin();

    // Create new group
    $title = substr(sha1(rand()), 0, 7);
    $groupName = $this->WebtestAddGroup();

    // Adding contact
    // We're using Quick Add block on the main page for this.
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Smith", "$firstName.smith@example.org");
    $this->_contactNames = array("$firstName.smith@example.org" => "Smith, $firstName");

    // add contact to group
    // visit group tab
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // add to group
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForElementPresent('link=Remove');

    $this->openCiviPage('campaign/add', 'reset=1', '_qf_Campaign_upload-bottom');

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

    $this->mailingAddTest($groupName, $campaignTitle, $title, $firstName);
  }

  /**
   * Test mailing add.
   *
   * @param string $groupName
   * @param string $campaignTitle
   * @param string $title
   * @param string $firstUserName
   */
  public function mailingAddTest($groupName, $campaignTitle, $title, $firstUserName) {
    //---- create mailing contact and add to mailing Group
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Mailson", "mailino$firstName@mailson.co.in");
    $this->_contactNames["mailino$firstName@mailson.co.in"] = "Mailson, $firstName";

    // go to group tab and add to mailing group
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("_qf_GroupContact_next");
    $this->select("group_id", "$groupName");
    $this->click("_qf_GroupContact_next");

    $this->openCiviPage('a/#/mailing/new');
    $this->waitForElementPresent("xpath=//input[@name='mailingName']");
    //-------select recipients----------

    // fill mailing name
    $mailingName = substr(sha1(rand()), 0, 7);
    $this->type("xpath=//input[@name='mailingName']", "Mailing $mailingName Webtest");

    // select campaign
    $this->select2("s2id_crmUiId_4", "Campaign_" . $title);

    // Add the test mailing group
    $this->select2("s2id_crmUiId_8", $groupName, TRUE);

    $this->waitForTextPresent("~2 recipients");

    //--------Mailing content------------
    $tokens = ' {domain.address}{action.optOutUrl}';
    // fill subject for mailing
    $this->type("xpath=//input[@name='subject']", "Test subject {$mailingName} for Webtest");
    // HTML format message
    $HTMLMessage = "This is HTML formatted content for Mailing {$mailingName} Webtest.";
    $this->fillRichTextField("crmUiId_1", $HTMLMessage . $tokens);

    // FIXME: Selenium can't access content in an iframe
    //$this->click("xpath=//div[@class='preview-popup']//a[text()='Preview as HTML']");
    //$this->waitForTextPresent($HTMLMessage);
    //$this->waitForAjaxContent();
    //$this->click("xpath=//button[@title='Close']");

    // Open Plain-text Format pane and type text format msg
    $this->click("//div[starts-with(text(),'Plain Text')]");
    $this->type("xpath=//*[@name='body_text']", "This is text formatted content for Mailing {$mailingName} Webtest.$tokens");

    $this->click("xpath=//div[@class='preview-popup']//a[text()='Preview as Plain Text']");
    $this->waitForTextPresent("This is text formatted content for Mailing {$mailingName} Webtest.");
    $this->click("xpath=//button[@title='Close']");

    //--------track and respond----------
    $this->waitForAjaxContent();
    $this->click('link=Tracking');
    $this->assertChecked("url_tracking");
    $this->assertChecked("open_tracking");
    // no need tracking for this test

    // default header and footer ( with label )
    $this->waitForAjaxContent();
    $this->click('link=Header and Footer');
    $this->select("header_id", "label=Mailing Header");
    $this->select("footer_id", "label=Mailing Footer");

    //---------------Test------------------

    ////////--Commenting test mailing and mailing preview (test mailing and preview not presently working).

    // send test mailing
    //$this->type("test_email", "mailino@mailson.co.in");
    //$this->click("sendtest");

    // verify status message
    //$this->assertTrue($this->isTextPresent("Your test message has been sent. Click 'Next' when you are ready to Schedule or Send your live mailing (you will still have a chance to confirm or cancel sending this mailing on the next page)."));

    // check mailing preview
    //$this->click("//form[@id='Test']/div[2]/div[4]/div[1]");
    //$this->assertTrue($this->isTextPresent("this is test content for Mailing $mailingName Webtest"));

    ////////

    // click next
    $this->click("xpath=//div[@class='crm-wizard-buttons']/button[text()='Next']");

    //----------Schedule or Send------------

    // do check for other option
    $this->waitForTextPresent("Mailing $mailingName Webtest");
    $this->click("xpath=//div[@class='content']//a[text()='~2 recipients']");
    $verifyData = array(
      "$firstUserName Smith" => "$firstUserName.smith@example.org",
      "$firstName Mailson" => "mailino$firstName@mailson.co.in",
    );
    $this->webtestVerifyTabularData($verifyData);
    $this->waitForTextPresent("(Include: $groupName)");
    $this->assertChecked("xpath=//input[@id='schedule-send-now']");

    // finally schedule the mail by clicking submit
    $this->click("xpath=//center/a/div[text()='Submit Mailing']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //----------end New Mailing-------------

    //check redirected page to Scheduled and Sent Mailings and  verify for mailing name
    $this->waitForTextPresent("Find Mailings");
    $this->assertElementContainsText('Search', "Mailing $mailingName Webtest");

    //--------- mail delivery verification---------

    // test undelivered report

    // click report link of created mailing
    $this->click("xpath=//table//tbody/tr[td[1]/text()='Mailing $mailingName Webtest']/descendant::a[text()='Report']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // verify undelivered status message
    $this->assertElementContainsText('crm-container', "Delivery has not yet begun for this mailing. If the scheduled delivery date and time is past, ask the system administrator or technical support contact for your site to verify that the automated mailer task ('cron job') is running - and how frequently.");

    // do check for recipient group
    $this->assertElementContainsText('crm-container', "Members of $groupName");

    // directly send schedule mailing -- not working right now
    $this->openCiviPage('mailing/queue', 'reset=1');

    //click report link of created mailing
    $this->click("xpath=//table//tbody/tr[td[1]/text()='Mailing $mailingName Webtest']/descendant::a[text()='Report']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // do check again for recipient group
    $this->assertElementContainsText('crm-container', "Members of $groupName");

    // check for 100% delivery
    $this->assertElementContainsText('crm-container', "2 (100.00%)");

    // verify intended recipients
    $this->verifyText("xpath=//table//tr[td/a[text()='Intended Recipients']]/descendant::td[2]", preg_quote("2"));

    // verify successful deliveries
    $this->verifyText("xpath=//table//tr[td/a[text()='Successful Deliveries']]/descendant::td[2]", preg_quote("2 (100.00%)"));

    // verify status
    $this->verifyText("xpath=//table//tr[td[1]/text()='Status']/descendant::td[2]", preg_quote("Complete"));

    // verify mailing name
    $this->verifyText("xpath=//table//tr[td[1]/text()='Mailing Name']/descendant::td[2]", preg_quote("Mailing $mailingName Webtest"));

    // verify mailing subject
    $this->verifyText("xpath=//table//tr[td[1]/text()='Subject']/descendant::td[2]", preg_quote("Test subject $mailingName for Webtest"));

    $this->verifyText("xpath=//table//tr[td[1]/text()='Campaign']/descendant::td[2]", preg_quote("$campaignTitle"));

    //---- check for delivery detail--
    $this->click("link=Successful Deliveries");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // check for open page
    $this->waitForTextPresent("Successful Deliveries");
    // verify email
    $this->assertElementContainsText('mailing_event', "mailino$firstName@mailson.co.in");
    //------end delivery verification---------

    // Search Advanced Search for contacts associated with Campaign in the Mailings Tab.
    $this->mailingCampaignAdvancedSearchTest($campaignTitle, $this->_contactNames);
  }

  public function mailingCampaignAdvancedSearchTest($campaignTitle, $contactNames) {
    // Go directly to Advanced Search
    $this->openCiviPage('contact/search/advanced', 'reset=1');

    // Select the Mailing Tab
    $this->clickAjaxLink("CiviMail", 'campaigns');
    $this->multiselect2("campaigns", array("$campaignTitle"));
    $this->click("_qf_Advanced_refresh");

    // Check for contacts inserted while adding Campaing and Mailing
    $this->waitForElementPresent('search-status');
    $this->assertElementContainsText('search-status', '2 Contacts');
  }

}

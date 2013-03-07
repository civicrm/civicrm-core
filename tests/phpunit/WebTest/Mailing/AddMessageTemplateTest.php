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
class WebTest_Mailing_AddMessageTemplateTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testTemplateAdd($useTokens = FALSE, $msgTitle = NULL) {
    $this->webtestLogin();

    // Go directly to the URL of the screen that you will be testing (Add Message Template).
    $this->openCiviPage("admin/messageTemplates/add", "action=add&reset=1");

    // Fill message title.
    if (!$msgTitle) {
      $msgTitle = 'msg_' . substr(sha1(rand()), 0, 7);
    }
    $this->type("msg_title", $msgTitle);
    if ($useTokens) {
      //Add Tokens
      $this->click("css=#msg_subject + a");
      $this->waitForElementPresent("css=#tokenSubject + .ui-dialog-buttonpane button");
      $this->type("filter3", "display");
      $this->addSelection("token3", "label=Display Name");
      $this->type("filter3", "contact");
      $this->addSelection("token3", "label=Contact Type");
      $this->click("css=#tokenSubject + .ui-dialog-buttonpane button");
      $this->click("//span[@id='helptext']/a/label");
      $this->waitForElementPresent("css=#tokenText + .ui-dialog-buttonpane button");
      $this->type("filter1", "display");
      $this->addSelection("token1", "label=Display Name");
      $this->type("filter1", "contact");
      $this->addSelection("token1", "label=Contact Type");
      $this->click("css=#tokenText + .ui-dialog-buttonpane button");
      $this->click("//span[@id='helphtml']/a/label");
      $this->waitForElementPresent("css=#tokenHtml + .ui-dialog-buttonpane button");
      $this->type("filter2", "display");
      $this->addSelection("token2", "label=Display Name");
      $this->type("filter2", "contact");
      $this->addSelection("token2", "label=Contact Type");
      $this->click("css=#tokenHtml + .ui-dialog-buttonpane button");
    }
    else {
      // Fill message subject.
      $msgSubject = "This is subject for message";
      $this->type("msg_subject", $msgSubject);

      // Fill text message.
      $txtMsg = "This is text message";
      $this->type("msg_text", $txtMsg);

      // Fill html message.
      $htmlMsg = "This is HTML message";
      $this->type("msg_html", $htmlMsg);
    }
    // Clicking save.
    $this->click("_qf_MessageTemplates_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct
    $this->assertElementContainsText('crm-notification-container', "The Message Template '$msgTitle' has been saved.");

    // Verify text.
    $this->assertTrue($this->isElementPresent("xpath=id('user')/div[2]/div/table/tbody//tr/td[1][contains(text(), '$msgTitle')]"),
      'Message Template Title not found!');
    if (!$useTokens) {
      $this->assertTrue($this->isElementPresent("xpath=id('user')/div[2]/div/table/tbody//tr/td[2][contains(text(), '$msgSubject')]"),
        'Message Subject not found!');
    }
  }

  function testAddMailingWithMessageTemplate() {
    // Call the above test to set up our environment
    $msgTitle = 'msg_' . substr(sha1(rand()), 0, 7);
    $this->testTemplateAdd(TRUE, $msgTitle);

    // create new mailing group
    $groupName = $this->WebtestAddGroup();

    //Create new contact and add to mailing Group
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Mailson", "mailino$firstName@mailson.co.in");
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("_qf_GroupContact_next");
    $this->select("group_id", "$groupName");
    $this->click("_qf_GroupContact_next");

    // configure default mail-box
    $this->openCiviPage("admin/mailSettings", "action=update&id=1&reset=1", '_qf_MailSettings_cancel-bottom');
    $this->type('name', 'Test Domain');
    $this->type('domain', 'example.com');
    $this->select('protocol', 'value=1');
    $this->click('_qf_MailSettings_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Go directly to Schedule and Send Mailing form
    $this->openCiviPage("mailing/send", "reset=1", "_qf_Group_cancel");

    // fill mailing name
    $mailingName = substr(sha1(rand()), 0, 7);
    $this->type("name", "Mailing $mailingName Webtest");

    // Add the test mailing group
    $this->select("includeGroups-f", "$groupName");
    $this->click("add");

    // click next
    $this->click("_qf_Group_next");
    $this->waitForElementPresent("_qf_Settings_cancel");
    // check for default settings options
    $this->assertChecked("url_tracking");
    $this->assertChecked("open_tracking");

    // do check count for Recipient
    $this->assertElementContainsText('css=.messages', "Total Recipients: 1");
    $this->click("_qf_Settings_next");
    $this->waitForElementPresent("_qf_Upload_cancel");

    $this->click("template");
    $this->select("template", "label=$msgTitle");
    sleep(5);
    $this->click("xpath=id('Upload')/div[2]/fieldset[@id='compose_id']/div[2]/div[1]");
    $this->click('subject');

    // check for default header and footer ( with label )
    $this->select('header_id', "label=Mailing Header");
    $this->select('footer_id', "label=Mailing Footer");

    // do check count for Recipient
    $this->assertElementContainsText('css=.messages', "Total Recipients: 1");

    // click next with nominal content
    $this->click("_qf_Upload_upload");
    $this->waitForElementPresent("_qf_Test_cancel");

    $this->assertElementContainsText('css=.messages', "Total Recipients: 1");

    // click next
    $this->click("_qf_Test_next");
    $this->waitForElementPresent("_qf_Schedule_cancel");

    $this->assertChecked("now");

    // do check count for Recipient
    $this->assertElementContainsText('css=.messages', "Total Recipients: 1");

    // finally schedule the mail by clicking submit
    $this->click("_qf_Schedule_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //check redirected page to Scheduled and Sent Mailings and  verify for mailing name
    $this->assertElementContainsText('page-title', "Scheduled and Sent Mailings");
    $this->assertElementContainsText("xpath=//table[@class='selector']/tbody//tr//td", "Mailing $mailingName Webtest");
    $this->openCiviPage('mailing/queue', 'reset=1');

    // verify status
    $this->verifyText("xpath=id('Search')/table/tbody/tr[1]/td[2]", preg_quote("Complete"));

    //View Activity
    $this->openCiviPage('activity/search', "reset=1", "_qf_Search_refresh");
    $this->type("sort_name", $firstName);
    $this->click("activity_type_id[19]");
    $this->click("_qf_Search_refresh");
    $this->waitForElementPresent("_qf_Search_next_print");

    $this->click("xpath=id('Search')/div[3]/div/div[2]/table/tbody/tr[2]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_ActivityView_next");
    $this->assertElementContainsText('help', "Bulk Email Sent.", "Status message didn't show up after saving!");
  }
}
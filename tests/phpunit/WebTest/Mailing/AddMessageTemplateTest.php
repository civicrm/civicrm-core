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
 * Class WebTest_Mailing_AddMessageTemplateTest
 */
class WebTest_Mailing_AddMessageTemplateTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   * @param bool $useTokens
   * @param null $msgTitle
   */
  public function testTemplateAdd($useTokens = FALSE, $msgTitle = NULL) {
    $this->webtestLogin();

    $this->openCiviPage("admin/messageTemplates/add", "action=add&reset=1");

    // Fill message title.
    if (!$msgTitle) {
      $msgTitle = 'msg_' . substr(sha1(rand()), 0, 7);
    }
    $this->type("msg_title", $msgTitle);
    if ($useTokens) {
      //Add Tokens
      $this->select2("msg_subject", "Display Name");
      $this->select2("msg_subject", "Contact Type");
      $this->select2("xpath=//*[contains(@data-field,'msg_text')]/../div/a", "Display Name", FALSE, TRUE);
      $this->select2("xpath=//*[contains(@data-field,'msg_text')]/../div/a", "Contact Type", FALSE, TRUE);
      $this->select2("xpath=//*[contains(@data-field,'html_message')]/../div/a", "Display Name", FALSE, TRUE);
      $this->select2("xpath=//*[contains(@data-field,'html_message')]/../div/a", "Contact Type", FALSE, TRUE);
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
    $this->waitForText('crm-notification-container', "The Message Template '$msgTitle' has been saved.");

    // Verify text.
    $this->assertTrue($this->isElementPresent("xpath=id('user')/div[2]/div/table/tbody//tr/td[1][contains(text(), '$msgTitle')]"),
      'Message Template Title not found!');
    if (!$useTokens) {
      $this->assertTrue($this->isElementPresent("xpath=id('user')/div[2]/div/table/tbody//tr/td[2][contains(text(), '$msgSubject')]"),
        'Message Subject not found!');
    }
  }

  public function testAddMailingWithMessageTemplate() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
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
    $this->setupDefaultMailbox();

    $this->openCiviPage("a/#/mailing/new");
    $this->waitForElementPresent("xpath=//input[@name='mailingName']");

    // fill mailing name
    $mailingName = substr(sha1(rand()), 0, 7);
    $this->type("xpath=//input[@name='mailingName']", "Mailing $mailingName Webtest");

    // Add the test mailing group
    $this->select2("s2id_crmUiId_8", $groupName, TRUE);

    // do check count for Recipient
    $this->waitForTextPresent("~1 recipient");
    $this->click("msg_template_id");
    $this->select("msg_template_id", "label=$msgTitle");
    $this->waitForAjaxContent();
    $this->select2('s2id_autogen1', "Unsubscribe via web page");
    $this->select2('s2id_autogen1', "Domain (organization) address");
    $this->waitForAjaxContent();
    $this->select2('s2id_autogen3', "Unsubscribe via web page");
    $this->select2('s2id_autogen3', "Domain (organization) address");
    $this->waitForAjaxContent();
    $this->click("xpath=//div[@class='preview-popup']//a[text()='Preview as Plain Text']");
    $this->click('subject');

    // check for default settings options
    $this->click('link=Tracking');
    $this->assertChecked("url_tracking");
    $this->assertChecked("open_tracking");

    // check for default header and footer ( with label )
    $this->click('link=Header and Footer');
    $this->select('header_id', "label=Mailing Header");
    $this->select('footer_id', "label=Mailing Footer");

    // click next
    $this->click("xpath=//div[@class='crm-wizard-buttons']/button[text()='Next']");

    $this->assertChecked("xpath=//input[@id='schedule-send-now']");
    $this->waitForTextPresent("Mailing $mailingName Webtest");
    $this->click("xpath=//div[@class='content']//a[text()='~1 recipient']");
    $this->webtestVerifyTabularData(array("$firstName Mailson" => "mailino$firstName@mailson.co.in"));
    $this->click("xpath=//button[@title='Close']");
    $this->waitForTextPresent("(Include: $groupName)");

    // finally schedule the mail by clicking submit
    $this->click("xpath=//center/a/div[text()='Submit Mailing']");

    //check redirected page to Scheduled and Sent Mailings and  verify for mailing name
    $this->waitForTextPresent("Find Mailings");
    $this->isTextPresent("Mailing $mailingName Webtest");
    $this->openCiviPage('mailing/queue', 'reset=1');

    // verify status
    $this->verifyText("xpath=id('Search')/table/tbody/tr[1]/td[2]", preg_quote("Complete"));

    //View Activity
    $this->openCiviPage('activity/search', "reset=1", "_qf_Search_refresh");
    $this->type("sort_name", $firstName);
    $this->select("activity_type_id", "label=Bulk Email");
    $this->click("_qf_Search_refresh");
    $this->waitForElementPresent("xpath=//form[@id='Search']/div[3]/div/div[2]/table[@class='selector row-highlight']/tbody/tr[2]/td[9]/span/a[1][text()='View']");
    $this->click("xpath=//form[@id='Search']/div[3]/div/div[2]/table[@class='selector row-highlight']/tbody/tr[2]/td[9]/span/a[1][text()='View']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button/span[2]");
    $this->assertElementContainsText("xpath=//div[@class='help']", "Bulk Email Sent.", "Status message didn't show up after saving!");
  }

}

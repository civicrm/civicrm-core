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
class WebTest_Contact_TaskActionSendMassMailing extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testSelectedContacts() {
    $this->open($this->sboxPath);
    $this->webtestLogin();

    // Go directly to the URL of the screen that you will be testing (New Group).
    $this->open($this->sboxPath . "civicrm/group/add&reset=1");
    $this->waitForElementPresent("_qf_Edit_upload");

    // make group name
    $groupName = 'group_' . substr(sha1(rand()), 0, 7);

    // fill group name
    $this->type("title", $groupName);

    // fill description
    $this->type("description", "New mailing group for Webtest");

    // enable Mailing List
    $this->click("group_type[2]");

    // select Visibility as Public Pages
    $this->select("visibility", "value=Public Pages");

    // Clicking save.
    $this->click("_qf_Edit_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Use class names for menu items since li array can change based on which components are enabled
    $this->click("css=ul#civicrm-menu li.crm-Search");
    $this->click("css=ul#civicrm-menu li.crm-Advanced_Search a");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("email");
    $this->click("_qf_Advanced_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());


    // Click "check all" box and act on "Add to group" action
    $this->click("//form[@id='Advanced']/div[3]/div/div[2]/table/thead/tr/th[1]/input");
    $this->select("task", "label=Schedule/Send a Mass Mailing");
    $this->click("Go");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //-------select recipients----------

    $mailingName = 'Selected Contact Mailing Test ' . substr(sha1(rand()), 0, 7);

    $this->waitForElementPresent("name");
    $this->type("name", "$mailingName");
    $this->select("baseGroup", "label=$groupName");
    $this->click("//option[@value='4']");
    $this->click("_qf_Group_next");

    //--------track and respond----------

    $this->waitForElementPresent("_qf_Settings_next");

    // check for default settings options
    $this->assertChecked("url_tracking");
    $this->assertChecked("open_tracking");

    $this->click("_qf_Settings_next");

    //--------Mailing content------------
    // fill subject for mailing
    $this->waitForElementPresent("subject");
    $this->type("subject", "Test subject {$mailingName} for Webtest");

    // check for default option enabled
    $this->assertChecked("CIVICRM_QFID_1_4");

    // HTML format message
    $HTMLMessage = "This is HTML formatted content for Mailing {$mailingName} Webtest.";
    $this->fillRichTextField("html_message", $HTMLMessage);

    // Open Plain-text Format pane and type text format msg
    $this->click("//fieldset[@id='compose_id']/div[2]/div[1]");
    $this->type("text_message", "This is text formatted content for Mailing {$mailingName} Webtest.");

    // select default header and footer ( with label )
    $this->select("header_id", "label=Mailing Header");
    $this->select("footer_id", "label=Mailing Footer");
    $this->click("_qf_Upload_upload");

    $this->waitForElementPresent("_qf_Test_next");
    $this->click("_qf_Test_next");

    //----------Schedule or Send------------

    $this->waitForElementPresent("_qf_Schedule_next");

    $this->assertChecked("now");

    $this->click("_qf_Schedule_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //----------end New Mailing-------------

    //check redirected page to Scheduled and Sent Mailings and  verify for mailing name
    $this->assertTrue($this->isTextPresent("Scheduled and Sent Mailings"));
    $this->assertTrue($this->isTextPresent("$mailingName"));

    //--------- mail delivery verification---------

    // test undelivered report

    // click report link of created mailing
    $this->click("xpath=//table//tbody/tr[td[1]/text()='$mailingName']/descendant::a[text()='Report']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // verify undelivered status message
    $this->assertTrue($this->isTextPresent("Delivery has not yet begun for this mailing. If the scheduled delivery date and time is past, ask the system administrator or technical support contact for your site to verify that the automated mailer task ('cron job') is running - and how frequently."));

    // directly send schedule mailing -- not working right now
    $this->open($this->sboxPath . "civicrm/mailing/queue&reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //click report link of created mailing
    $this->click("xpath=//table//tbody/tr[td[1]/text()='$mailingName']/descendant::a[text()='Report']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }
}



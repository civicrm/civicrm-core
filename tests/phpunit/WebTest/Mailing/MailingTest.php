<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * Class WebTest_Mailing_MailingTest
 */
class WebTest_Mailing_MailingTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAddMailing() {
    $this->webtestLogin();

    //----do create test mailing group
    $this->openCiviPage("group/add", "reset=1", "_qf_Edit_upload");

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
    $this->clickLink("_qf_Edit_upload");

    // Is status message correct?
    $this->waitForText('crm-notification-container', "The Group '$groupName' has been saved.");

    //---- create mailing contact and add to mailing Group
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Mailson", "mailino$firstName@mailson.co.in");

    // Get contact id from url.
    $contactId = $this->urlArg('cid');

    // go to group tab and add to mailing group
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("_qf_GroupContact_next");
    $this->select("group_id", "$groupName");
    $this->clickLink("_qf_GroupContact_next", "_qf_GroupContact_next", FALSE);

    // configure default mail-box
    $this->setupDefaultMailbox();

    $this->openCiviPage("a/#/mailing/new");

    //-------select recipients----------
    $tokens = ' {domain.address}{action.optOutUrl}';

    // fill mailing name
    $mailingName = substr(sha1(rand()), 0, 7);
    $this->waitForElementPresent("xpath=//input[@name='mailingName']");
    $this->type("xpath=//input[@name='mailingName']", "Mailing $mailingName Webtest");

    // Add the test mailing group
    $this->select2("s2id_crmUiId_8", $groupName, TRUE);

    // do check count for Recipient
    $this->waitForTextPresent("~1 recipient");

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
    $this->waitForAjaxContent();
    $this->click("xpath=//button[@title='Close']");

    // select default header and footer ( with label )
    $this->click("xpath=//ul/li/a[text()='Header and Footer']");
    $this->select2("s2id_crmUiId_10", "Mailing Header");
    $this->select2("s2id_crmUiId_11", "Mailing Footer");

    //--------track and respond----------

    // check for default settings options
    $this->click("xpath=//ul/li/a[text()='Tracking']");
    $this->assertChecked("url_tracking");
    $this->assertChecked("open_tracking");

    // click next with default settings
    $this->click("xpath=//div[@class='crm-wizard-buttons']/button[text()='Next']");

    $this->waitForTextPresent("Mailing $mailingName Webtest");
    $this->click("xpath=//div[@class='content']//a[text()='~1 recipient']");
    $this->webtestVerifyTabularData(array("$firstName Mailson" => "mailino$firstName@mailson.co.in"));
    $this->click("xpath=//button[@title='Close']");
    $this->waitForTextPresent("(Include: $groupName)");

    //----------Schedule or Send------------

    // do check for default option enabled
    $this->assertChecked("xpath=//input[@id='schedule-send-now']");

    // click next with nominal content
    $this->click("xpath=//center/a/div[text()='Submit Mailing']");

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

    //----------end New Mailing-------------

    //check redirected page to Scheduled and Sent Mailings and  verify for mailing name
    $this->waitForTextPresent("Find Mailings");
    $this->assertElementContainsText("xpath=//table[@class='selector row-highlight']/tbody//tr//td", "Mailing $mailingName Webtest");

    //--------- mail delivery verification---------
    // test undelivered report

    // click report link of created mailing
    $this->clickLink("xpath=//table//tbody/tr[td[1]/text()='Mailing $mailingName Webtest']/descendant::a[text()='Report']");

    // verify undelivered status message
    $this->assertElementContainsText('css=.messages', "Delivery has not yet begun for this mailing. If the scheduled delivery date and time is past, ask the system administrator or technical support contact for your site to verify that the automated mailer task ('cron job') is running - and how frequently.");

    // do check for recipient group
    $this->assertElementContainsText("xpath=//fieldset/legend[text()='Recipients']/../table/tbody//tr/td", "Members of $groupName");

    // directly send schedule mailing -- not working right now
    $this->openCiviPage("mailing/queue", "reset=1");

    //click report link of created mailing
    $this->clickLink("xpath=//table//tbody/tr[td[1]/text()='Mailing $mailingName Webtest']/descendant::a[text()='Report']");

    // do check again for recipient group
    $this->assertElementContainsText("xpath=//fieldset/legend[text()='Recipients']/../table/tbody//tr/td", "Members of $groupName");

    // verify intended recipients
    $this->verifyText("xpath=//table//tr[td/a[text()='Intended Recipients']]/descendant::td[2]", preg_quote("1"));

    // verify successful deliveries
    $this->verifyText("xpath=//table//tr[td/a[text()='Successful Deliveries']]/descendant::td[2]", preg_quote("1 (100.00%)"));

    // verify status
    $this->verifyText("xpath=//table//tr[td[1]/text()='Status']/descendant::td[2]", preg_quote("Complete"));

    // verify mailing name
    $this->verifyText("xpath=//table//tr[td[1]/text()='Mailing Name']/descendant::td[2]", preg_quote("Mailing $mailingName Webtest"));

    // verify mailing subject
    $this->verifyText("xpath=//table//tr[td[1]/text()='Subject']/descendant::td[2]", preg_quote("Test subject $mailingName for Webtest"));

    //---- check for delivery detail--

    $this->clickLink("link=Successful Deliveries");

    // check for open page
    $this->waitForTextPresent("Successful Deliveries");

    // verify email
    $this->verifyText("xpath=//table[@id='mailing_event']/tbody//tr/td[3]", preg_quote("mailino$firstName@mailson.co.in"));

    $eventQueue = new CRM_Mailing_Event_DAO_Queue();
    $eventQueue->contact_id = $contactId;
    $eventQueue->find(TRUE);

    $permission = array('edit-1-access-civimail-subscribeunsubscribe-pages');
    $this->changePermissions($permission);
    $this->webtestLogout();

    // build forward url
    $forwardUrl = array(
      "mailing/forward",
      "reset=1&jid={$eventQueue->job_id}&qid={$eventQueue->id}&h={$eventQueue->hash}",
    );
    $this->openCiviPage($forwardUrl[0], $forwardUrl[1], NULL);

    $this->type("email_0", substr(sha1(rand()), 0, 7) . '@example.com');
    $this->type("email_1", substr(sha1(rand()), 0, 7) . '@example.com');

    $this->click("comment_show");
    $this->type("forward_comment", "Test Message");

    $this->click("_qf_ForwardMailing_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('css=div.messages', 'Mailing is forwarded successfully to 2 email addresses');

    $this->webtestLogin();

    $this->openCiviPage("mailing/browse/scheduled", "reset=1&scheduled=true");

    //click report link of created mailing
    $this->clickLink("xpath=//table//tbody/tr[td[1]/text()='Mailing $mailingName Webtest']/descendant::a[text()='Report']");

    // verify successful forwards
    $this->verifyText("xpath=//table//tr[td/a[text()='Forwards']]/descendant::td[2]", "2");

    // Mailing is forwarded successfully to 2 email addresses.
    //------end delivery verification---------

    // //------ check with unsubscribe -------
    // // FIX ME: there is an issue with DSN setting for Webtest, need to handle by seperate DSN setting for Webtests
    // // build unsubscribe link
    // require_once 'CRM/Mailing/Event/DAO/Queue.php';
    // $eventQueue = new CRM_Mailing_Event_DAO_Queue( );
    // $eventQueue->contact_id = $contactId;
    // $eventQueue->find(true);

    // // unsubscribe link
    // $unsubscribeUrl = "civicrm/mailing/optout?reset=1&jid={$eventQueue->job_id}&qid={$eventQueue->id}&h={$eventQueue->hash}&confirm=1";

    // // logout to unsubscribe
    // $this->webtestLogout();

    // // click(visit) unsubscribe path
    // $this->open($this->sboxPath . $unsubscribeUrl);
    // $this->waitForPageToLoad($this->getTimeoutMsec());

    // $this->assertTrue($this->isTextPresent('Optout'));
    // $this->assertTrue($this->isTextPresent("mailino$firstName@mailson.co.in"));

    // // unsubscribe
    // $this->click('_qf_optout_next');
    // $this->waitForPageToLoad($this->getTimeoutMsec());

    // $this->assertTrue($this->isTextPresent('Optout'));
    // $this->assertTrue($this->isTextPresent("mailino$firstName@mailson.co.in"));
    // $this->assertTrue($this->isTextPresent('has been successfully opted out.'));

    // //------ end unsubscribe -------
  }

  public function testAdvanceSearchAndReportCheck() {

    $this->webtestLogin();

    $this->openCiviPage("group/add", "reset=1", "_qf_Edit_upload");

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
    $this->clickLink("_qf_Edit_upload");

    // Is status message correct?
    $this->waitForText('crm-notification-container', "The Group '$groupName' has been saved.");

    //---- create mailing contact and add to mailing Group
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Mailson", "mailino$firstName@mailson.co.in");

    // Get contact id from url.
    $contactId = $this->urlArg('cid');

    // go to group tab and add to mailing group
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("_qf_GroupContact_next");
    $this->select("group_id", "$groupName");
    $this->clickLink("_qf_GroupContact_next", "_qf_GroupContact_next", FALSE);

    // configure default mail-box
    $this->openCiviPage("admin/mailSettings", "action=update&id=1&reset=1", '_qf_MailSettings_cancel-bottom');
    $this->type('name', 'Test Domain');
    $this->type('domain', 'example.com');
    $this->select('protocol', 'value=1');
    $this->clickLink('_qf_MailSettings_next-bottom');

    $this->openCiviPage("a/#/mailing/new");

    //-------select recipients----------
    $tokens = ' {domain.address}{action.optOutUrl}';

    // fill mailing name
    $mailingName = substr(sha1(rand()), 0, 7);
    $this->waitForElementPresent("xpath=//input[@name='mailingName']");
    $this->type("xpath=//input[@name='mailingName']", "Mailing $mailingName Webtest");

    // Add the test mailing group
    $this->select2("s2id_crmUiId_8", $groupName, TRUE);

    // do check count for Recipient
    $this->waitForTextPresent("~1 recipient");

    // fill subject for mailing
    $this->type("xpath=//input[@name='subject']", "Test subject {$mailingName} for Webtest");

    // HTML format message
    $HTMLMessage = "This is HTML formatted content for Mailing {$mailingName} Webtest.";
    $this->fillRichTextField("crmUiId_1", $HTMLMessage . $tokens);

    // FIXME: Selenium can't access content in an iframe
    //$this->click("xpath=//div[@class='preview-popup']//a[text()='Preview as HTML']");
    //$this->waitForElementPresent($HTMLMessage);
    //$this->waitForAjaxContent();
    //$this->click("xpath=//button[@title='Close']");

    // Open Plain-text Format pane and type text format msg
    $this->click("//div[starts-with(text(),'Plain Text')]");
    $this->type("xpath=//*[@name='body_text']", "This is text formatted content for Mailing {$mailingName} Webtest.$tokens");

    $this->click("xpath=//div[@class='preview-popup']//a[text()='Preview as Plain Text']");
    $this->waitForTextPresent("This is text formatted content for Mailing {$mailingName} Webtest.");
    $this->waitForAjaxContent();
    $this->click("xpath=//button[@title='Close']");

    // select default header and footer ( with label )
    $this->click("xpath=//ul/li/a[text()='Header and Footer']");
    $this->select2("s2id_crmUiId_10", "Mailing Header");
    $this->select2("s2id_crmUiId_11", "Mailing Footer");

    //--------track and respond----------

    // check for default settings options
    $this->click("xpath=//ul/li/a[text()='Tracking']");
    $this->assertChecked("url_tracking");
    $this->assertChecked("open_tracking");

    // click next with default settings
    $this->click("xpath=//div[@class='crm-wizard-buttons']/button[text()='Next']");

    $this->waitForTextPresent("Mailing $mailingName Webtest");
    $this->click("xpath=//div[@class='content']//a[text()='~1 recipient']");
    $this->webtestVerifyTabularData(array("$firstName Mailson" => "mailino$firstName@mailson.co.in"));
    $this->click("xpath=//button[@title='Close']");
    $this->waitForTextPresent("(Include: $groupName)");

    //----------Schedule or Send------------

    // do check for default option enabled
    $this->assertChecked("xpath=//input[@id='schedule-send-now']");

    // click next with nominal content
    $this->click("xpath=//center/a/div[text()='Submit Mailing']");

    //----------end New Mailing-------------

    //check redirected page to Scheduled and Sent Mailings and  verify for mailing name
    $this->waitForTextPresent("Find Mailings");
    $this->assertElementContainsText("xpath=//form[@class='CRM_Mailing_Form_Search']/table[@class='selector row-highlight']/tbody//tr//td", "Mailing $mailingName Webtest");

    // directly send schedule mailing -- not working right now
    $this->openCiviPage("mailing/queue", "reset=1");

    //click report link of created mailing
    $this->clickLink("xpath=//table//tbody/tr[td[1]/text()='Mailing $mailingName Webtest']/descendant::a[text()='Report']");

    $mailingReportUrl = $this->getLocation();
    // do check again for recipient group
    $this->assertElementContainsText("xpath=//fieldset/legend[text()='Recipients']/../table/tbody//tr/td", "Members of $groupName");

    // verify successful deliveries
    $this->verifyText("xpath=//table//tr[td/a[text()='Successful Deliveries']]/descendant::td[2]", preg_quote("1 (100.00%)"));

    $summaryInfoLinks = array(
      'Intended Recipients',
      'Successful Deliveries',
      'Tracked Opens',
      'Click-throughs',
      'Forwards',
      'Replies',
      'Bounces',
      'Unsubscribe Requests',
      'Opt-out Requests',
    );

    //check for report and adv search links
    foreach ($summaryInfoLinks as $value) {
      $this->assertTrue($this->isElementPresent("xpath=//fieldset/legend[text()='Delivery Summary']/../table//tr[td/a[text()='{$value}']]/descendant::td[3]/span/a[1][text()='Report']"), "Report link missing for {$value}");
      $this->assertTrue($this->isElementPresent("xpath=//fieldset/legend[text()='Delivery Summary']/../table//tr[td/a[text()='{$value}']]/descendant::td[3]/span/a[2][text()='Advanced Search']"), "Advance Search link missing for {$value}");
    }
    // verify mailing name
    $this->verifyText("xpath=//table//tr[td[1]/text()='Mailing Name']/descendant::td[2]", preg_quote("Mailing $mailingName Webtest"));

    // verify mailing subject
    $this->verifyText("xpath=//table//tr[td[1]/text()='Subject']/descendant::td[2]", preg_quote("Test subject $mailingName for Webtest"));

    // after asserts do clicks and confirm filters
    $criteriaCheck = array(
      'Intended Recipients' => array(
        'report' => array('report_name' => 'Mailing Details', 'Mailing' => "Mailing $mailingName Webtest"),
        'search' => array('Mailing Name IN' => "\"Mailing {$mailingName} Webtest"),
      ),
      'Successful Deliveries' => array(
        'report' => array(
          'report_name' => 'Mailing Details',
          'Mailing' => "Mailing $mailingName Webtest",
          "Delivery Status" => " Successful",
        ),
        'search' => array(
          'Mailing Name IN' => "\"Mailing {$mailingName} Webtest",
          'Mailing Delivery -' => "Successful",
        ),
      ),
      'Tracked Opens' => array(
        'report' => array('report_name' => 'Mail Opened', 'Mailing' => "Mailing $mailingName Webtest"),
        'search' => array(
          'Mailing Name IN' => "\"Mailing {$mailingName} Webtest",
          'Mailing: Trackable Opens -' => "Opened",
        ),
      ),
      'Click-throughs' => array(
        'report' => array('report_name' => 'Mail Clickthroughs', 'Mailing' => "Mailing $mailingName Webtest"),
        'search' => array(
          'Mailing Name IN' => "\"Mailing {$mailingName} Webtest",
          'Mailing: Trackable URL Clicks -' => "Clicked",
        ),
      ),
      'Forwards' => array(
        'report' => array(
          'report_name' => 'Mailing Details',
          'Mailing' => "Mailing $mailingName Webtest",
          'Forwarded' => 'Is equal to Yes',
        ),
        'search' => array('Mailing Name IN' => "\"Mailing {$mailingName} Webtest", 'Mailing: -' => "Forwards"),
      ),
      'Replies' => array(
        'report' => array(
          'report_name' => 'Mailing Details',
          'Mailing' => "Mailing $mailingName Webtest",
          'Replied' => 'Is equal to Yes',
        ),
        'search' => array(
          'Mailing Name IN' => "\"Mailing {$mailingName} Webtest",
          'Mailing: Trackable Replies -' => "Replied",
        ),
      ),
      'Bounces' => array(
        'report' => array('report_name' => 'Mail Bounces', 'Mailing' => "Mailing $mailingName Webtest"),
        'search' => array(
          'Mailing Name IN' => "\"Mailing {$mailingName} Webtest",
          'Mailing Delivery -' => "Bounced",
        ),
      ),
      'Unsubscribe Requests' => array(
        'report' => array(
          'report_name' => 'Mailing Details',
          'Mailing' => "Mailing $mailingName Webtest",
          'Unsubscribed' => 'Is equal to Yes',
        ),
        'search' => array(
          'Mailing Name IN' => "\"Mailing {$mailingName} Webtest",
          'Mailing: -' => "Unsubscribe Requests",
        ),
      ),
      'Opt-out Requests' => array(
        'report' => array(
          'report_name' => 'Mailing Details',
          'Mailing' => "Mailing $mailingName Webtest",
          'Opted-out' => 'Is equal to Yes',
        ),
        'search' => array(
          'Mailing Name IN' => "\"Mailing {$mailingName} Webtest",
          'Mailing: -' => "Opt-out Requests",
        ),
      ),
    );
    $this->criteriaCheck($criteriaCheck, $mailingReportUrl);
  }

  /**
   * @param $criteriaCheck
   * @param $mailingReportUrl
   */
  public function criteriaCheck($criteriaCheck, $mailingReportUrl) {
    foreach ($criteriaCheck as $key => $infoFilter) {
      foreach ($infoFilter as $entity => $dataToCheck) {
        $this->open($mailingReportUrl);
        if ($entity == "report") {
          $this->clickLink("xpath=//fieldset/legend[text()='Delivery Summary']/../table//tr[td/a[text()='{$key}']]/descendant::td[3]/span/a[1][text()='Report']");
        }
        else {
          $this->clickLink("xpath=//fieldset/legend[text()='Delivery Summary']/../table//tr[td/a[text()='{$key}']]/descendant::td[3]/span/a[2][text()='Advanced Search']");
        }
        $this->_verifyCriteria($key, $dataToCheck, $entity);
      }
    }
  }

  /**
   * @param $summaryInfo
   * @param $dataToCheck
   * @param $entity
   */
  public function _verifyCriteria($summaryInfo, $dataToCheck, $entity) {
    foreach ($dataToCheck as $key => $value) {
      if ($entity == 'report') {
        if ($key == 'report_name') {
          $this->waitForTextPresent("{$value}");
          continue;
        }
        $this->assertTrue($this->isElementPresent("xpath=//form//div[3]/table/tbody//tr/th[contains(text(),'{$key}')]/../td[contains(text(),'{$value}')]"), "Criteria check for {$key} failed for Report for {$summaryInfo}");
      }
      else {
        $this->waitForTextPresent("Advanced Search");
        $assertedValue = $this->isElementPresent("xpath=//div[@class='crm-results-block']//div[@class='qill'][contains(text(),'{$key} {$value}')]");
        if (!$assertedValue) {
          $assertedValue = $this->isTextPresent("{$key} {$value}");
        }
        $this->assertTrue($assertedValue, "Criteria check for {$key} failed for Advance Search for {$summaryInfo}");
      }
    }
  }

}

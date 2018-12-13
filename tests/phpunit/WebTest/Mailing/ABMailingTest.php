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
 * Class WebTest_Mailing_ABMailingTest
 */
class WebTest_Mailing_ABMailingTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testWithDifferentSubject() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
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

    // no. of user to add into group
    $totalUser = 10;

    //---- create mailing contact and add to mailing Group
    for ($i = 1; $i <= $totalUser; $i++) {
      $firstName = substr(sha1(rand()), 0, 7);
      $this->webtestAddContact($firstName, "Mailson", "mailino$firstName@mailson.co.in");

      // Get contact id from url.
      $contactId = $this->urlArg('cid');

      // go to group tab and add to mailing group
      $this->click("css=li#tab_group a");
      $this->waitForElementPresent("_qf_GroupContact_next");
      $this->select("group_id", "$groupName");
      $this->clickLink("_qf_GroupContact_next", "_qf_GroupContact_next", FALSE);
    }
    // configure default mail-box
    $this->setupDefaultMailbox();

    $this->openCiviPage("a/#/abtest/new", NULL, "xpath=//div[@class='crm-wizard-buttons']");
    $this->waitForElementPresent("xpath=//input[@name='abName']");

    $ABTestName = substr(sha1(rand()), 0, 7) . "ABTestName";
    $this->type("xpath=//input[@name='abName']", "$ABTestName");

    $this->click("xpath=//input[@value='subject']");

    //click on next
    $this->click("//button[@ng-click='crmUiWizardCtrl.next()']");

    // Add the test mailing group
    $this->select2("s2id_crmUiId_13", $groupName, TRUE);

    //$this->fireEvent('xpath=//div[@class="ui-slider-range"]', 'drag');

    //click on next
    $this->click("//button[@ng-click='crmUiWizardCtrl.next()']");
    $this->waitForElementPresent("xpath=//input[@name='subjectA']");

    //-------Compose Mail----------
    $tokens = ' {domain.address}{action.optOutUrl}';

    // fill subject for mailing
    $MailingSubject = substr(sha1(rand()), 0, 7);
    $this->type("xpath=//input[@name='subjectA']", "Test subject {$MailingSubject} for A");
    $this->type("xpath=//input[@name='subjectB']", "Test subject {$MailingSubject} for B");

    // HTML format message
    $HTMLMessage = "This is HTML formatted content for Mailing {$MailingSubject} Webtest.";
    $this->fillRichTextField("crmUiId_1", $HTMLMessage . $tokens);

    // FIXME: Selenium can't access content in an iframe
    //$this->click("xpath=//div[@class='preview-popup']//a[text()='Preview as HTML']");
    //$this->waitForTextPresent($HTMLMessage);
    //$this->waitForAjaxContent();
    //$this->click("xpath=//button[@title='Close']");

    // Open Plain-text Format pane and type text format msg
    $this->click("//div[starts-with(text(),'Plain Text')]");
    $this->type("xpath=//*[@name='body_text']", "This is text formatted content for Mailing {$MailingSubject} Webtest.$tokens");

    $this->click("xpath=//div[@class='preview-popup']//a[text()='Preview as Plain Text']");
    $this->waitForTextPresent("This is text formatted content for Mailing {$MailingSubject} Webtest.");
    $this->waitForAjaxContent();
    $this->click("xpath=//button[@title='Close']");

    // click next with default settings
    $this->click("xpath=//div[@class='crm-wizard-buttons']/button[text()='Next']");

    // click next with nominal content
    $this->click("xpath=//center/a/div[text()='Submit Mailing']");

    $this->waitForElementPresent("xpath=//button[text()='Select as Final']");
    $this->click("xpath=//button[text()='Select as Final']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Submit final mailing']");
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Submit final mailing']");

    //----------end New Mailing-------------

    $this->waitForAjaxContent();

    //check redirected page to Scheduled and Sent Mailings and  verify for mailing name
    $this->openCiviPage("mailing/browse/scheduled", "reset=1&scheduled=true");
    $this->waitForTextPresent("Find Mailings");

    //--------- mail delivery verification---------

    // click report link of created mailing
    $this->clickLink("xpath=//form[@id='Search']/table/tbody//tr/td[@class='crm-mailing-name'][text()='Final ($ABTestName)']/..//td/span/a[text()='Report']");

    // directly send schedule mailing -- not working right now
    $this->openCiviPage("mailing/queue", "reset=1");

    //click report link of created mailing
    $this->clickLink("xpath=//form[@id='Search']/table/tbody//tr/td[@class='crm-mailing-name'][text()='Final ($ABTestName)']/..//td/span/a[text()='Report']");

    //get actual number of user for mailing
    $mailedUser = round($totalUser * ($totalUser / 100));

    //---- check for delivery detail--

    $this->waitForElementPresent("xpath=//table[@class='crm-mailing-ab-table']/tbody/tr//td//a[text()=" . $mailedUser . "]");
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody/tr//td//a[2]", "$mailedUser");
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']//tbody//tr//td/span", 'Complete');

    //check value for Mailing A
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]//tr/td[2]", 'Test A (' . $ABTestName . ')');
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]/tr[3]//td[2]", 'Test subject ' . $MailingSubject . ' for A');
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']//tbody//tr[@ng-controller='ViewRecipCtrl']//td/div", "Include: " . $groupName);

    ////check value for Mailing B
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]//tr//td[3]", 'Test B (' . $ABTestName . ')');
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]/tr[3]//td[3]", 'Test subject ' . $MailingSubject . ' for B');

    //check value for Mailing Final
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]//tr//td[4]", 'Final (' . $ABTestName . ')');
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]/tr[3]//td[4]", 'Test subject ' . $MailingSubject . ' for A');
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']//tbody//tr[@ng-controller='ViewRecipCtrl']//td/div", "Include: " . $groupName);
  }

  public function testWithDifferentFrom() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
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

    // no. of user to add into group
    $totalUser = 10;

    //---- create mailing contact and add to mailing Group
    for ($i = 1; $i <= $totalUser; $i++) {
      $firstName = substr(sha1(rand()), 0, 7);
      $this->webtestAddContact($firstName, "Mailson", "mailino$firstName@mailson.co.in");

      // Get contact id from url.
      $contactId = $this->urlArg('cid');

      // go to group tab and add to mailing group
      $this->click("css=li#tab_group a");
      $this->waitForElementPresent("_qf_GroupContact_next");
      $this->select("group_id", "$groupName");
      $this->clickLink("_qf_GroupContact_next", "_qf_GroupContact_next", FALSE);
    }
    // configure default mail-box
    $this->setupDefaultMailbox();

    // configure new Form
    $this->openCiviPage("admin/options/from_email_address", "reset=1");
    $this->waitForElementPresent("xpath=//div[@class='action-link']/a/span[contains(text(), 'Add From Email Address')]");
    $this->click("xpath=//div[@class='action-link']/a/span[contains(text(), 'Add From Email Address')]");
    $this->waitForAjaxContent();

    // make Form Email address
    $formEmailAddressA = 'ABMailing_' . substr(sha1(rand()), 0, 7);
    $aEmailID = '"' . $formEmailAddressA . '" <' . $formEmailAddressA . '@abtest.org>';
    $this->type("xpath=//input[@name='label']", "$aEmailID");
    $this->click("xpath=//button/span[text()='Save']");

    // make Form Email address
    $this->click("xpath=//div[@class='action-link']/a/span[contains(text(), 'Add From Email Address')]");
    $this->waitForAjaxContent();
    $formEmailAddressB = 'ABMailing_' . substr(sha1(rand()), 0, 7);
    $bEmailID = '"' . $formEmailAddressB . '" <' . $formEmailAddressB . '@abtest.org>';
    $this->type("xpath=//input[@name='label']", "$bEmailID");
    $this->click("xpath=//button/span[text()='Save']");

    $this->openCiviPage("a/#/abtest/new", NULL, "xpath=//div[@class='crm-wizard-buttons']");
    $this->waitForElementPresent("xpath=//input[@name='abName']");

    $ABTestName = substr(sha1(rand()), 0, 7) . "ABTestName";
    $this->type("xpath=//input[@name='abName']", "$ABTestName");

    $this->click("xpath=//input[@value='from']");

    //click on next
    $this->click("//button[@ng-click='crmUiWizardCtrl.next()']");

    // Add the test mailing group
    $this->select2("s2id_crmUiId_13", $groupName, TRUE);

    //click on next
    $this->click("//button[@ng-click='crmUiWizardCtrl.next()']");
    $this->waitForElementPresent("xpath=//input[@name='subject']");

    //-------Compose Mail----------
    $tokens = ' {domain.address}{action.optOutUrl}';

    // fill subject for mailing
    $MailingSubject = substr(sha1(rand()), 0, 7);
    $this->type("xpath=//input[@name='subject']", "Test subject {$MailingSubject} for webtest");
    $this->waitForElementPresent("xpath=//div[@id='s2id_crmUiId_20']");

    // choose form email address for A
    $this->select("crmUiId_20", "value=$aEmailID");

    // choose form email address for B
    $this->select("crmUiId_21", "value=$bEmailID");

    // HTML format message
    $HTMLMessage = "This is HTML formatted content for Mailing {$MailingSubject} Webtest.";
    $this->fillRichTextField("crmUiId_1", $HTMLMessage . $tokens);

    // FIXME: Selenium can't access content in an iframe
    //$this->click("xpath=//div[@class='preview-popup']//a[text()='Preview as HTML']");
    //$this->waitForTextPresent($HTMLMessage);
    //$this->waitForAjaxContent();
    //$this->click("xpath=//button[@title='Close']");

    // Open Plain-text Format pane and type text format msg
    $this->click("//div[starts-with(text(),'Plain Text')]");
    $this->type("xpath=//*[@name='body_text']", "This is text formatted content for Mailing {$MailingSubject} Webtest.$tokens");

    $this->click("xpath=//div[@class='preview-popup']//a[text()='Preview as Plain Text']");
    $this->waitForTextPresent("This is text formatted content for Mailing {$MailingSubject} Webtest.");
    $this->waitForAjaxContent();
    $this->click("xpath=//button[@title='Close']");

    // click next with default settings
    $this->click("xpath=//div[@class='crm-wizard-buttons']/button[text()='Next']");

    // click next with nominal content
    $this->click("xpath=//center/a/div[text()='Submit Mailing']");

    $this->waitForElementPresent("xpath=//button[text()='Select as Final']");
    $this->click("xpath=//button[text()='Select as Final']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Submit final mailing']");
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Submit final mailing']");

    //----------end New Mailing-------------

    $this->waitForAjaxContent();

    //check redirected page to Scheduled and Sent Mailings and  verify for mailing name
    $this->openCiviPage("mailing/browse/scheduled", "reset=1&scheduled=true");
    $this->waitForTextPresent("Find Mailings");

    //--------- mail delivery verification---------

    // click report link of created mailing
    $this->clickLink("xpath=//form[@id='Search']/table/tbody//tr/td[@class='crm-mailing-name'][text()='Final ($ABTestName)']/..//td/span/a[text()='Report']");

    // directly send schedule mailing -- not working right now
    $this->openCiviPage("mailing/queue", "reset=1");

    //click report link of created mailing
    $this->clickLink("xpath=//form[@id='Search']/table/tbody//tr/td[@class='crm-mailing-name'][text()='Final ($ABTestName)']/..//td/span/a[text()='Report']");

    //get actual number of user for mailing
    $mailedUser = round($totalUser * ($totalUser / 100));

    //---- check for delivery detail--
    $this->waitForElementPresent("xpath=//table[@class='crm-mailing-ab-table']/tbody/tr//td//a[text()=" . $mailedUser . "]");
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody/tr//td//a[2]", "$mailedUser");
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']//tbody//tr//td/span", 'Complete');

    //check value for Mailing A
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]//tr/td[2]", 'Test A (' . $ABTestName . ')');
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]/tr[2]/td[2]", "$aEmailID");
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]/tr[3]//td[2]", 'Test subject ' . $MailingSubject . ' for webtest');
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']//tbody//tr[@ng-controller='ViewRecipCtrl']//td/div", "Include: " . $groupName);

    //check value for Mailing B
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]//tr//td[3]", 'Test B (' . $ABTestName . ')');
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]/tr[2]/td[3]", "$bEmailID");
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]/tr[3]//td[3]", 'Test subject ' . $MailingSubject . ' for webtest');

    //check value for Mailing Final
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]//tr//td[4]", 'Final (' . $ABTestName . ')');
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]/tr[2]/td[4]", "$aEmailID");
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]/tr[3]//td[4]", 'Test subject ' . $MailingSubject . ' for webtest');
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']//tbody//tr[@ng-controller='ViewRecipCtrl']//td/div", "Include: " . $groupName);
  }

  public function testWithDifferentABMailing() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
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

    // no. of user to add into group
    $totalUser = 10;

    //---- create mailing contact and add to mailing Group
    for ($i = 1; $i <= $totalUser; $i++) {
      $firstName = substr(sha1(rand()), 0, 7);
      $this->webtestAddContact($firstName, "Mailson", "mailino$firstName@mailson.co.in");

      // Get contact id from url.
      $contactId = $this->urlArg('cid');

      // go to group tab and add to mailing group
      $this->click("css=li#tab_group a");
      $this->waitForElementPresent("_qf_GroupContact_next");
      $this->select("group_id", "$groupName");
      $this->clickLink("_qf_GroupContact_next", "_qf_GroupContact_next", FALSE);
    }
    // configure default mail-box
    $this->setupDefaultMailbox();

    $this->openCiviPage("a/#/abtest/new", NULL, "xpath=//div[@class='crm-wizard-buttons']");
    $this->waitForElementPresent("xpath=//input[@name='abName']");

    $ABTestName = substr(sha1(rand()), 0, 7) . "ABTestName";
    $this->type("xpath=//input[@name='abName']", "$ABTestName");

    $this->click("xpath=//input[@value='full_email']");

    //click on next
    $this->click("//button[@ng-click='crmUiWizardCtrl.next()']");

    // Add the test mailing group
    $this->select2("s2id_crmUiId_13", $groupName, TRUE);

    //click on next
    $this->click("//button[@ng-click='crmUiWizardCtrl.next()']");
    $this->waitForElementPresent("xpath=//input[@name='subjectA']");

    //-------Compose A----------
    $tokens = ' {domain.address}{action.optOutUrl}';

    // fill subject for mailing
    $AMailingSubject = substr(sha1(rand()), 0, 7);
    $this->type("xpath=//input[@name='subjectA']", "Test subject {$AMailingSubject} for Webtest");

    // HTML format message
    $AHTMLMessage = "This is HTML formatted content for Mailing {$AMailingSubject} Webtest.";
    $this->fillRichTextField("crmUiId_19", $AHTMLMessage . $tokens);

    // FIXME: Selenium can't access content in an iframe
    //$this->click("xpath=//div[@class='preview-popup']//a[text()='Preview as HTML']");
    //$this->waitForTextPresent($AHTMLMessage);
    //$this->waitForAjaxContent();
    //$this->click("xpath=//button[@title='Close']");

    // Open Plain-text Format pane and type text format msg
    $this->click("//div[starts-with(text(),'Plain Text')]");
    $this->type("xpath=//*[@name='body_text']", "This is text formatted content for Mailing {$AMailingSubject} Webtest.$tokens");

    $this->click("xpath=//div[@class='preview-popup']//a[text()='Preview as Plain Text']");
    $this->waitForTextPresent("This is text formatted content for Mailing {$AMailingSubject} Webtest.");
    $this->waitForAjaxContent();
    $this->click("xpath=//button[@title='Close']");

    // click next with default settings
    $this->click("xpath=//div[@class='crm-wizard-buttons']/button[text()='Next']");
    $this->waitForElementPresent("xpath=//input[@name='subjectB']");

    //-------Compose B----------

    // fill subject for mailing
    $BMailingSubject = substr(sha1(rand()), 0, 7);
    $this->type("xpath=//input[@name='subjectB']", "Test subject {$BMailingSubject} for Webtest");

    // HTML format message
    $BHTMLMessage = "This is HTML formatted content for Mailing {$BMailingSubject} Webtest.";
    $this->fillRichTextField("crmUiId_28", $BHTMLMessage . $tokens);

    // FIXME: Selenium can't access content in an iframe
    //$this->click("xpath=//div[@class='preview-popup']//a[text()='Preview as HTML']");
    //$this->waitForTextPresent($BHTMLMessage);
    //$this->waitForAjaxContent();
    //$this->click("xpath=//button[@title='Close']");

    // Open Plain-text Format pane and type text format msg
    $this->waitForElementPresent("xpath=//div[@id='tab-mailingB']//div[contains(text(), 'Plain Text')]");
    $this->click("xpath=//div[@id='tab-mailingB']//div[contains(text(), 'Plain Text')]");
    $this->type("xpath=//div[@id='tab-mailingB']//*[@name='body_text']", "This is text formatted content for Mailing {$BMailingSubject} Webtest.$tokens");

    $this->click("xpath=//div[@crm-mailing='abtest.mailings.b']//div[@class='preview-popup']//a[text()='Preview as Plain Text']");
    $this->waitForTextPresent("This is text formatted content for Mailing {$BMailingSubject} Webtest.");
    $this->waitForAjaxContent();
    $this->click("xpath=//button[@title='Close']");

    // click next with default settings
    $this->click("xpath=//div[@class='crm-wizard-buttons']/button[text()='Next']");

    // click next with nominal content
    $this->click("xpath=//center/a/div[text()='Submit Mailing']");

    $this->waitForElementPresent("xpath=//button[text()='Select as Final']");
    $this->click("xpath=//button[text()='Select as Final']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Submit final mailing']");
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Submit final mailing']");

    //----------end New Mailing-------------

    $this->waitForAjaxContent();

    //check redirected page to Scheduled and Sent Mailings and  verify for mailing name
    $this->openCiviPage("mailing/browse/scheduled", "reset=1&scheduled=true");
    $this->waitForTextPresent("Find Mailings");

    //--------- mail delivery verification---------

    // click report link of created mailing
    $this->clickLink("xpath=//form[@id='Search']/table/tbody//tr/td[@class='crm-mailing-name'][text()='Final ($ABTestName)']/..//td/span/a[text()='Report']");

    // directly send schedule mailing -- not working right now
    $this->openCiviPage("mailing/queue", "reset=1");

    //click report link of created mailing
    $this->clickLink("xpath=//form[@id='Search']/table/tbody//tr/td[@class='crm-mailing-name'][text()='Final ($ABTestName)']/..//td/span/a[text()='Report']");

    //get actual number of user for mailing
    $mailedUser = round($totalUser * ($totalUser / 100));

    //---- check for delivery detail--
    $this->waitForElementPresent("xpath=//table[@class='crm-mailing-ab-table']/tbody/tr//td//a[text()=" . $mailedUser . "]");
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody/tr//td//a[2]", "$mailedUser");
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']//tbody//tr//td/span", 'Complete');

    //check value for Mailing A
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]//tr/td[2]", 'Test A (' . $ABTestName . ')');
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]/tr[3]//td[2]", 'Test subject ' . $AMailingSubject . ' for Webtest');
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']//tbody//tr[@ng-controller='ViewRecipCtrl']//td/div", "Include: " . $groupName);

    //check value for Mailing B
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]//tr//td[3]", 'Test B (' . $ABTestName . ')');
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]/tr[3]//td[3]", 'Test subject ' . $BMailingSubject . ' for Webtest');

    //check value for Mailing Final
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]//tr//td[4]", 'Final (' . $ABTestName . ')');
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']/tbody[3]/tr[3]//td[4]", 'Test subject ' . $AMailingSubject . ' for Webtest');
    $this->assertElementContainsText("xpath=//table[@class='crm-mailing-ab-table']//tbody//tr[@ng-controller='ViewRecipCtrl']//td/div", "Include: " . $groupName);
  }

}

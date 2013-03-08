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
class WebTest_Report_AddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAddReport() {
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

    // create contact
    $firstName   = 'reportuser_' . substr(sha1(rand()), 0, 7);
    $displayName = "Anderson, $firstName";
    $emailId     = "$firstName.anderson@example.org";
    $this->webtestAddContact($firstName, "Anderson", $emailId);

    // Go directly to the URL of the screen that you will be testing (New Tag).
    $this->openCiviPage('report/contact/summary', 'reset=1', '_qf_Summary_submit' );

    // enable email field
    $this->click("fields[email]");

    // enable phone field
    $this->click("fields[phone]");

    // apply Contact Name filter
    $this->select("sort_name_op", "value=has");
    $this->type("sort_name_value", $firstName);

    // preview result
    $this->click("_qf_Summary_submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is filter statistics present?
    $this->assertElementContainsText("xpath=//tr/th[@class='statistics'][text()='Contact Name']/../td", "Contains $firstName", "Statistics did not found!");
    
    // Is Contact Name present in result?
    $this->assertElementContainsText('css=td.crm-report-civicrm_contact_sort_name', $displayName, "Contact Name did not found!");

    // Is email Id present on result?
    $this->assertElementContainsText('css=td.crm-report-civicrm_email_email', $emailId, "Email did not found!");
  
    // check criteria
    $this->click("css=div.crm-report_criteria-accordion div.crm-accordion-header");
    $this->waitForElementPresent("sort_name_value");

    // Is Contact Name filter?
    $this->assertContains($firstName, $this->getValue("sort_name_value"), "Filter Contact Name expected $firstName");

    // Is Email Field?
    $this->assertEquals("on", $this->getValue("fields[email]"));

    // Is Phone Field?
    $this->assertEquals("on", $this->getValue("fields[phone]"));

    // Create report
    $this->click("css=div.crm-report_setting-accordion div.crm-accordion-header");
    $this->waitForElementPresent("title");

    $reportName        = 'ContactSummary_' . substr(sha1(rand()), 0, 7);
    $reportDescription = "New Contact Summary Report";
    $emaiSubject       = "Contact Summary Report";
    $emailCC           = "tesmail@example.org";

    // Fill Report Title
    $this->type("title", $reportName);

    // Fill Report Description
    $this->type("description", $reportDescription);

    // Fill Email Subject
    $this->type("email_subject", $emaiSubject);

    // Fill Email To
    $this->type("email_to", $emailId);

    // Fill Email CC
    $this->type("email_cc", $emailCC);

    // We want navigation menu
    $this->click("is_navigation");
    $this->waitForElementPresent("parent_id");

    // Navigation menu under Reports section
    $this->select("parent_id", "label=Reports");

    // Set permission as access CiviCRM
    $this->select("permission", "value=access CiviCRM");

    // click to create report
    $this->click("_qf_Summary_submit_save");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Open report list
    $this->openCiviPage('report/list', 'reset=1');
    
    // Is report is resent in list?
    $this->assertElementContainsText('css=table.report-layout', $reportName);

    // Visit report
    $this->click("link=$reportName");
    $this->waitForPageToLoad($this->getTimeoutMsec());


   // Is filter statistics present?
    $this->assertElementContainsText("xpath=//tr/th[@class='statistics'][text()='Contact Name']/../td", "Contains $firstName", "Statistics did not found!");
    
    // Is Contact Name present in result?
    $this->assertElementContainsText('css=td.crm-report-civicrm_contact_sort_name', $displayName, "Contact Name did not found!");

    // Is email Id present on result?
    $this->assertElementContainsText('css=td.crm-report-civicrm_email_email', $emailId, "Email did not found!");

    // check report criteria
    $this->click("css=div.crm-report_criteria-accordion div.crm-accordion-header");
    $this->waitForElementPresent("sort_name_value");

    // Is Contact Name filter?
    $this->assertContains($firstName, $this->getValue("sort_name_value"), "Filter Contact Name expected $firstName");

    // Is Email Field?
    $this->assertEquals("on", $this->getValue("fields[email]"));

    // Is Phone Field?
    $this->assertEquals("on", $this->getValue("fields[phone]"));

    // Check Report settings
    $this->click("css=div.crm-report_setting-accordion div.crm-accordion-header");
    $this->waitForElementPresent("title");

    // Is correct Report Title?
    $this->assertContains($reportName, $this->getValue("title"), "Report Title expected $reportName");

    // Is correct Report Description?
    $this->assertContains($reportDescription, $this->getValue("description"), "Report Description expected $reportDescription");

    // Is correct email Subject?
    $this->assertContains($emaiSubject, $this->getValue("email_subject"), "Email Subject expected $emaiSubject");

    // Is correct email to?
    $this->assertContains($emailId, $this->getValue("email_to"), "Email To expected $emailId");

    // Is correct email cc?
    $this->assertContains($emailCC, $this->getValue("email_cc"), "Email CC expected $emailCC");

    // Is Navigation?
    $this->assertEquals("on", $this->getValue("is_navigation"));

    // Is correct Navigation Parent?
    $this->assertSelectedLabel("parent_id", "Reports");

    // Is correct access permission?
    $this->assertSelectedLabel("permission", "access CiviCRM");
  }
}



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
 * Class WebTest_Report_AddTest
 */
class WebTest_Report_AddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAddReport() {
    $this->webtestLogin();

    // create contact
    $firstName = 'reportuser_' . substr(sha1(rand()), 0, 7);
    $displayName = "Anderson, $firstName";
    $emailId = "$firstName.anderson@example.org";
    $this->webtestAddContact($firstName, "Anderson", $emailId);

    $this->openCiviPage('report/contact/summary', 'reset=1', '_qf_Summary_submit');

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
    $this->click("xpath=//div[@class='crm-report-criteria']/div[@id='mainTabContainer']/ul//li/a[text()='Filters']");
    $this->waitForElementPresent("xpath=//div[@class='crm-submit-buttons']");

    // Is Contact Name filter?
    $this->assertContains($firstName, $this->getValue("sort_name_value"), "Filter Contact Name expected $firstName");

    // Is Email Field?
    $this->assertEquals("on", $this->getValue("fields[email]"));

    // Is Phone Field?
    $this->assertEquals("on", $this->getValue("fields[phone]"));

    // Create report

    $reportName = 'ContactSummary_' . substr(sha1(rand()), 0, 7);
    $reportDescription = "New Contact Summary Report";
    $emaiSubject = "Contact Summary Report";
    $emailCC = "tesmail@example.org";
    $this->click("xpath=//div[@class='crm-report-criteria']/div[@id='mainTabContainer']/ul//li/a[text()='Developer']");
    $this->waitForElementPresent("xpath=//div[@class='crm-submit-buttons']");
    $this->click("_qf_Summary_submit_save");

    // Fill Report Title
    $this->waitForElementPresent("xpath=//div[@class='crm-confirm-dialog ui-dialog-content ui-widget-content modal-dialog']/table/tbody/tr[1]/td[2]/input[@type='text']");
    $this->type("xpath=//div[@class='crm-confirm-dialog ui-dialog-content ui-widget-content modal-dialog']/table/tbody/tr[1]/td[2]/input[@type='text']", $reportName);

    // Fill Report Description
    $this->waitForElementPresent("xpath=//div[@class='crm-confirm-dialog ui-dialog-content ui-widget-content modal-dialog']/table/tbody/tr[2]/td[2]/input[@type='text']");
    $this->type("xpath=//div[@class='crm-confirm-dialog ui-dialog-content ui-widget-content modal-dialog']/table/tbody/tr[2]/td[2]/input[@type='text']", $reportDescription);
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button[1]/span[2]");
    $this->waitForElementPresent('_qf_Summary_submit_save');

    // Fill Email Subject
    $this->click("xpath=//div[@class='crm-report-criteria']/div[@id='mainTabContainer']/ul//li/a[text()='Email Delivery']");
    $this->waitForAjaxContent();
    $this->type("email_subject", $emaiSubject);

    // Fill Email To
    $this->waitForElementPresent('email_to');
    $this->type("email_to", $emailId);

    // Fill Email CC
    $this->waitForElementPresent('email_cc');
    $this->type("email_cc", $emailCC);

    // We want navigation menu
    $this->click("xpath=//div[@class='crm-report-criteria']/div[@id='mainTabContainer']/ul//li/a[text()='Access']");
    $this->click("is_navigation");

    // Navigation menu under Reports section
    $this->waitForElementPresent("parent_id");
    $this->select("parent_id", "label=Reports");

    // Set permission as access CiviCRM
    $this->waitForElementPresent("permission");
    $this->select("permission", "value=access CiviCRM");

    // click to create report
    $this->click("_qf_Summary_submit_save");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Open report list
    $this->openCiviPage('report/list', 'reset=1');

    // Is report is resent in list?
    $this->assertElementContainsText('css=table.report-layout', $reportName);

    // Visit report
    $this->click("xpath=//div[@id='Contact']//table/tbody//tr/td/a/strong[text() = '$reportName']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("_qf_Summary_submit");
    $this->waitForAjaxContent();
    // Is filter statistics present?
    $this->assertElementContainsText("xpath=//tr/th[@class='statistics'][text()='Contact Name']/../td", "Contains $firstName", "Statistics did not found!");

    // Is Contact Name present in result?
    $this->assertElementContainsText('css=td.crm-report-civicrm_contact_sort_name', $displayName, "Contact Name did not found!");

    // Is email Id present on result?
    $this->assertElementContainsText('css=td.crm-report-civicrm_email_email', $emailId, "Email did not found!");

    // check report criteria
    $this->click("xpath=//div[@id='mainTabContainer']/ul/li[3]/a");
    $this->waitForElementPresent("sort_name_value");

    // Is Contact Name filter?
    $this->assertContains($firstName, $this->getValue("sort_name_value"), "Filter Contact Name expected $firstName");

    // Is Email Field?
    $this->assertEquals("on", $this->getValue("fields[email]"));

    // Is Phone Field?
    $this->assertEquals("on", $this->getValue("fields[phone]"));

    // Check Report settings
    $this->click("xpath=//div[@class='crm-report-criteria']/div[@id='mainTabContainer']/ul/li[4]/a");
    $this->waitForElementPresent("title");

    // Is correct Report Title?
    $this->assertContains($reportName, $this->getValue("title"), "Report Title expected $reportName");

    // Is correct Report Description?
    $this->assertContains($reportDescription, $this->getValue("description"), "Report Description expected $reportDescription");

    // Is correct email Subject?
    $this->waitForElementPresent("mainTabContainer");
    $this->click("xpath=//div[@class='crm-report-criteria']/div[@id='mainTabContainer']/ul/li[5]/a");
    $this->waitForAjaxContent();
    $this->assertContains($emaiSubject, $this->getValue("email_subject"), "Email Subject expected $emaiSubject");

    // Is correct email to?
    $this->assertContains($emailId, $this->getValue("email_to"), "Email To expected $emailId");

    // Is correct email cc?
    $this->assertContains($emailCC, $this->getValue("email_cc"), "Email CC expected $emailCC");

    // Is Navigation?
    $this->click("xpath=//div[@class='crm-report-criteria']/div[@id='mainTabContainer']/ul/li[6]/a");
    $this->assertEquals("on", $this->getValue("is_navigation"));

    // Is correct Navigation Parent?
    $this->assertSelectedLabel("parent_id", "Reports");

    // Is correct access permission?
    $this->assertSelectedLabel("permission", "access CiviCRM");
  }

}

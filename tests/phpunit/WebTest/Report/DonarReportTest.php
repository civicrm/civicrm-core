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
class WebTest_Report_DonarReportTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testDonarReportPager() {
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

    // now create new donar detail report instance
    $this->openCiviPage('report/contribute/detail', 'reset=1', '_qf_Detail_submit');
    
    // preview result
    $this->click("_qf_Detail_submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Create report
    $this->click("css=div.crm-report_setting-accordion div.crm-accordion-header");
    $this->waitForElementPresent("title");

    $reportName = 'ContributeDetail_' . substr(sha1(rand()), 0, 7);
    $reportDescription = "New Contribute Detail Report";

    // Fill Report Title
    $this->type("title", $reportName);

    // Fill Report Description
    $this->type("description", $reportDescription);

    // We want navigation menu
    $this->click("is_navigation");
    $this->waitForElementPresent("parent_id");

    // Navigation menu under Reports section
    $this->select("parent_id", "label=Reports");

    // Set permission as access CiviCRM
    $this->select("permission", "value=access CiviCRM");

    // click to create report
    $this->click("_qf_Detail_submit_save");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Open report list
    $this->openCiviPage('report/list', 'reset=1');

    // Is report is resent in list?
    $this->assertElementContainsText('css=div#Contribute > table.report-layout', $reportName);

    // Visit report
    $this->click("link=$reportName");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //now select the criteria
    //click report criteria accordian
    $this->click("css=div.crm-report_criteria-accordion div.crm-accordion-header");

    //enter contribution amount
    $this->select('total_amount_op', "value=gte");
    $this->type('total_amount_value', "10");

    // click preview
    $this->click("_qf_Detail_submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Is greater than or equal to 100
    //check for criteria
    $this->assertElementContainsText('css=table.statistics-table', "Is greater than or equal to 10", "Criteria is not selected");

    //click on next link
    $this->click("_qf_Detail_submit_save");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // check if criteria still exits
    $this->assertElementContainsText('css=table.statistics-table', "Is greater than or equal to 10", "Criteria is not selected");
  }
}
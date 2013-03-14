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
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/


require_once 'CiviTest/CiviSeleniumTestCase.php';
class WebTest_Generic_CheckDashboardTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testCheckDashboardElements() {
    $this->open($this->sboxPath);

    $this->webtestLogin();

    $this->open($this->sboxPath . "civicrm");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isElementPresent("link=Configure Your Dashboard"));

    // Test Activities widget enable and full screen.
    $this->_testActivityDashlet();

    // More dashlet tests can be added here using the functions modeled below
  }

  function _testAddDashboardElement($widgetConfigureID, $widgetEnabledSelector, $widgetTitle) {
    // Check if desired widget is already loaded on dashboard and remove it if it is so we can test adding it.
    sleep(10);
    if ($this->isElementPresent($widgetEnabledSelector)) {
      $this->_testRemoveDashboardElement($widgetConfigureID, $widgetEnabledSelector, $widgetTitle);
    };
    $this->click("link=Configure Your Dashboard");
    $this->waitForElementPresent("dashlets-header-col-0");
    $this->mouseDownAt($widgetConfigureID, "");
    sleep(3);
    $this->mouseMoveAt("existing-dashlets-col-1", "");
    sleep(3);
    $this->mouseUpAt("existing-dashlets-col-1", "");
    sleep(3);
    $this->click("link=Done");
    $this->waitForElementPresent("link=Configure Your Dashboard");
    $this->waitForTextPresent("$widgetTitle");

    // click Full Screen icon and test full screen container
    $this->waitForElementPresent("css=li#widget-2 a.fullscreen-icon");
    $this->click("css=li#widget-2 a.fullscreen-icon");
    $this->waitForElementPresent("ui-id-1");
    $this->assertTrue($this->isTextPresent($widgetTitle));
    sleep(5);
    $this->click("link=close");
  }

  function _testRemoveDashboardElement($widgetConfigureID, $widgetEnabledSelector) {
    $this->click("link=Configure Your Dashboard");
    $this->waitForElementPresent("dashlets-header-col-0");
    $this->mouseDownAt("{$widgetConfigureID}", "");
    sleep(1);
    $this->mouseMoveAt("available-dashlets", "");
    sleep(1);
    $this->mouseUpAt("available-dashlets", "");
    sleep(1);
    $this->click("link=Done");
    $this->waitForElementPresent("link=Configure Your Dashboard");
    // giving time for activity widget to load (and make sure it did NOT)
    sleep(10);
    $this->assertFalse($this->isElementPresent($widgetEnabledSelector));
  }

  function _testActivityDashlet() {
    // Add an activity that will show up in the widget
    $this->WebtestAddActivity();
    $widgetTitle = "Activities";
    $widgetEnabledSelector = "contact-activity-selector-dashlet_wrapper";
    $widgetConfigureID = "2-0";

    // now add the widget
    $this->open($this->sboxPath . "civicrm");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForTextPresent("Configure Your Dashboard");
    $this->_testAddDashboardElement($widgetConfigureID, $widgetEnabledSelector, $widgetTitle);

    // If CiviCase enabled, click 'more' link for context menu pop-up in the widget selector
    if ($this->isElementPresent("//table[@id='contact-activity-selector-dashlet']/tbody/tr[1]/td[8]/span[text()='more ']")) {
      // click 'Delete Activity' link
      $this->click("//table[@id='contact-activity-selector-dashlet']/tbody/tr[1]/td[8]/span[text()='more ']/ul/li[2]/a[text()='Delete']");
    }
    else {
      // click 'Delete Activity' link
      $this->click("//table[@id='contact-activity-selector-dashlet']/tbody/tr[1]/td[9]/span//a[text()='Delete']");
    }
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Activity_next-bottom");
    $this->assertTrue($this->isTextPresent("Are you sure you want to delete"));
    $this->click("_qf_Activity_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("Selected Activity has been deleted successfully."));
    // FIXMED: Currently there's a bug, dashboard context is ignored after delete so we should already be back on home dash.
    // Issue filed: CRM-
    //  $this->assertTrue($this->isTextPresent("Configure Your Dashboard");
    $this->open($this->sboxPath . "civicrm");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForTextPresent("Configure Your Dashboard");

    // cleanup by removing the widget
    $this->_testRemoveDashboardElement($widgetConfigureID, $widgetEnabledSelector);
  }
}



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
class WebTest_Mailing_AddNewMailingComponentTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testHeaderAdd() {
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

    // Go directly to the URL of the screen that you will be testing (Add New Mailing Component).
    $this->openCiviPage("admin/component", "action=add&reset=1");

    // fill component name.
    $componentName = 'ComponentName_' . substr(sha1(rand()), 0, 7);
    $this->type("name", $componentName);

    // fill component type
    $this->click("component_type");
    $this->select("component_type", "value=Header");

    // fill subject
    $subject = "This is subject for New Mailing Component.";
    $this->type("subject", $subject);

    // fill text message
    $txtMsg = "This is Header Text Message";
    $this->type("body_text", $txtMsg);

    // fill html message
    $htmlMsg = "This is Header HTML Message";
    $this->type("body_html", $htmlMsg);
    $this->click("is_default");

    // Clicking save.
    $this->click("_qf_Component_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct.
    $this->assertElementContainsText('crm-notification-container', "The mailing component '$componentName' has been saved.");

    // Verify text.
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody//tr/td[text()='{$componentName}']/../td[2][text()='Header']/../td[3][text()='{$subject}']/../td[4][text()='{$txtMsg}']/../td[5][text()='{$htmlMsg}']"), "The row doesn't consists of proper component details");
  }

  function testFooterAdd() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    $this->webtestLogin();

    // Go directly to the URL of the screen that you will be testing (Add New Mailing Component).
    $this->openCiviPage("admin/component", "action=add&reset=1");

    // fill component name.
    $componentName = 'ComponentName_' . substr(sha1(rand()), 0, 7);
    $this->type("name", $componentName);

    // fill component type
    $this->click("component_type");
    $this->select("component_type", "value=Footer");

    // fill subject
    $subject = "This is subject for New Mailing Component.";
    $this->type("subject", $subject);

    // fill text message
    $txtMsg = "This is Footer Text Message";
    $this->type("body_text", $txtMsg);

    // fill html message
    $htmlMsg = "This is Footer HTML Message";
    $this->type("body_html", $htmlMsg);
    $this->click("is_default");

    // Clicking save.
    $this->click("_qf_Component_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct.
    $this->assertElementContainsText('crm-notification-container',  "The mailing component '$componentName' has been saved.");

    // Verify text.
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody//tr/td[text()='{$componentName}']/../td[2][text()='Footer']/../td[3][text()='{$subject}']/../td[4][text()='{$txtMsg}']/../td[5][text()='{$htmlMsg}']"), "The row doesn't consists of proper component details");
  }

  function testAutomatedAdd() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    $this->webtestLogin();

    // Go directly to the URL of the screen that you will be testing (Add New Mailing Component).
    $this->openCiviPage("admin/component", "action=add&reset=1");

    // fill component name.
    $componentName = 'ComponentName_' . substr(sha1(rand()), 0, 7);
    $this->type("name", $componentName);

    // fill component type
    $this->click("component_type");
    $this->select("component_type", "value=Reply");

    // fill subject
    $subject = "This is subject for New Mailing Component.";
    $this->type("subject", $subject);

    // fill text message
    $txtMsg = "This is Automated Text Message";
    $this->type("body_text", $txtMsg);

    // fill html message
    $htmlMsg = "This is Automated HTML Message";
    $this->type("body_html", $htmlMsg);
    $this->click("is_default");

    // Clicking save.
    $this->click("_qf_Component_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct.
    $this->assertElementContainsText('crm-notification-container',  "The mailing component '$componentName' has been saved.");

    // Verify text
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody//tr/td[text()='{$componentName}']/../td[2][text()='Reply']/../td[3][text()='{$subject}']/../td[4][text()='{$txtMsg}']/../td[5][text()='{$htmlMsg}']"), "The row doesn't consists of proper component details");
  }
}
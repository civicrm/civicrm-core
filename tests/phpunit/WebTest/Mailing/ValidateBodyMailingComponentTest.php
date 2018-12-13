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
 * Class WebTest_Mailing_ValidateBodyMailingComponentTest
 */
class WebTest_Mailing_ValidateBodyMailingComponentTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testWithoutBodyTextAndHTML() {
    $this->webtestLogin();

    $this->openCiviPage("admin/component", "action=add&reset=1");

    // fill component name.
    $componentName = 'ComponentName_' . substr(base_convert(rand(), 10, 36), 0, 7);
    $this->type("name", $componentName);

    // fill component type
    $this->click("component_type");
    $this->select("component_type", "value=Header");

    // fill subject
    $subject = "This is subject for New Mailing Component.";
    $this->type("subject", $subject);

    // fill no text message

    // fill no html message

    $this->click("is_default");
    // Clicking save.
    $this->click("_qf_Component_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct.
    $status = "Please provide either HTML or TEXT format for the Body.";
    $this->waitForText('crm-notification-container', $status);

    // Verify the error text.
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody//tr/td[2]/span[text()='{$status}']"), "The row doesn't consists of proper component details");
  }

}

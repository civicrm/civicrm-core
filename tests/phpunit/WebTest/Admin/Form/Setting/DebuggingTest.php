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
 * Class WebTest_Admin_Form_Setting_LocalizationTest
 */
class WebTest_Admin_Form_Setting_DebuggingTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testSetCivicrmEnvironment() {
    $this->webtestLogin();
    $this->openCiviPage('admin/setting/debug', 'reset=1');
    $this->click('xpath=//tr[@class="crm-debugging-form-block-civicrmEnvironment"]/td[2]/label[contains(text(), "No")]');
    $this->click('_qf_Debugging_next-top');

    $this->waitForPageToLoad($this->getTimeoutMsec());
    try {
      $this->assertFalse($this->isTextPresent('Your changes have been saved.'));
    }
    catch (PHPUnit_Framework_AssertionFailedError$e) {
      array_push($this->verificationErrors, $e->toString());
    }
    global $civicrm_setting;
    $civicrm_setting[CRM_Core_BAO_Setting::DEVELOPER_PREFERENCES_NAME]['civicrmEnvironment'] = TRUE;
    $this->openCiviPage('admin/setting/debug', 'reset=1');
    $disabled = $this->getAttribute("xpath=//tr[@class='crm-debugging-form-block-civicrmEnvironment']/td[2]/input[1]@disabled");
    $this->assertEquals(1, $disabled);
  }

}

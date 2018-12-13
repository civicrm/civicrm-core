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

require_once 'ReleaseTestCase.php';

/**
 * name of the class doesn't end with Test on purpose - this way this
 * webtest is not picked up by the suite, since it needs to run
 * on specially prepare sandbox
 * more details: http://wiki.civicrm.org/confluence/display/CRMDOC/Release+testing+script
 * Class WebTest_Release_UpgradeScript
 */
class WebTest_Release_UpgradeScript extends WebTest_Release_ReleaseTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testUpgrade() {
    $this->webtestLogin();
    $this->open($this->settings->upgradeURL);
    $this->waitForTextPresent("Upgrade CiviCRM to Version");
    $this->clickAndWait("css=input[type=submit]");

    $this->waitForTextPresent("CiviCRM upgrade was successful.");
  }

}

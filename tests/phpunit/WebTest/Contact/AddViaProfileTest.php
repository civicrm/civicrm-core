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
class WebTest_Contact_AddViaProfileTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAddViaCreateProfile() {
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

    // Go directly to the URL of the screen that you will be testing (Create Profile).
    $this->openCiviPage('profile/create', 'reset=1&gid=1', '_qf_Edit_cancel');

    $firstName = 'Jo' . substr(sha1(rand()), 0, 4);
    $lastName = 'Ad' . substr(sha1(rand()), 0, 7);

    //contact details section
    //fill in first name
    $this->type("first_name", $firstName);

    //fill in last name
    $this->type("last_name", $lastName);

    //address section
    $this->type("street_address-1", "902C El Camino Way SW");
    $this->type("city-1", "Dumfries");
    $this->type("postal_code-1", "1234");
    $this->assertElementContainsText('country-1', "United States");
    $this->select("state_province-1", "value=1019");

    // Clicking save.
    $this->click("_qf_Edit_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertElementContainsText('css=.msg-text', "Your information has been saved.");
  }
}



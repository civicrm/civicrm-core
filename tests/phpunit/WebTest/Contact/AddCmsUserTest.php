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
class WebTest_Contact_AddCmsUserTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAuthenticAddUser() {
    $this->open($this->sboxPath);

    $this->webtestLogin(TRUE);

    // Go directly to the URL of the screen that will Create User Authentically.
    $this->open($this->sboxPath . "admin/people/create");


    $this->waitForElementPresent("edit-submit");

    $name = "TestUserAuthenticated" . substr(sha1(rand()), 0, 4);
    $this->type("edit-name", $name);

    $emailId = substr(sha1(rand()), 0, 7) . '@web.com';
    $this->type("edit-mail", $emailId);
    $this->type("edit-pass-pass1", "Test12345");
    $this->type("edit-pass-pass2", "Test12345");

    //Add profile Details
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);

    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    //Address Details
    $this->type("street_address-1", "902C El Camino Way SW");
    $this->type("city-1", "Dumfries");
    $this->type("postal_code-1", "1234");
    $this->select("state_province-1", "value=1019");

    $this->click("edit-submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  function testAnonymousAddUser() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Make sure Drupal account settings allow visitors to register for account w/o admin approval
    // login as admin
    $this->webtestLogin(TRUE);
    $this->open($this->sboxPath . "admin/config/people/accounts");
    $this->waitForElementPresent("edit-submit");

    $this->click('edit-user-register-1');
    $this->click('edit-user-email-verification');
    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    // logout
    $this->open($this->sboxPath . 'civicrm/logout?reset=1');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Go directly to the URL of the screen that will Create User Anonymously.
    $this->open($this->sboxPath . "user/register");

    $this->waitForElementPresent("edit-submit");
    $name = "TestUserAnonymous" . substr(sha1(rand()), 0, 7);
    $this->type("edit-name", $name);
    $emailId = substr(sha1(rand()), 0, 7) . '@web.com';
    $this->type("edit-mail", $emailId);


    //Add profile Details
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    //Address Details
    $this->type("street_address-1", "902C El Camino Way SW");
    $this->type("city-1", "Dumfries");
    $this->type("postal_code-1", "1234");
    $this->assertTrue($this->isTextPresent("United States"));
    $this->select("state_province-1", "value=1019");

    $this->click("edit-submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("A welcome message with further instructions has been sent to your e-mail address."));

    $this->webtestLogin();

    $this->open($this->sboxPath . "civicrm/contact/search?reset=1");
    $this->waitForElementPresent("_qf_Basic_refresh");
    $this->type("sort_name", $emailId);
    $this->click("_qf_Basic_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent($emailId));
    $this->assertTrue($this->isTextPresent($lastName . ', ' . $firstName));
    $this->assertTrue($this->isTextPresent("902C El Camino Way SW"));
    $this->assertTrue($this->isTextPresent("Dumfries"));
    $this->assertTrue($this->isTextPresent("1234"));
  }
}


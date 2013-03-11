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
class WebTest_Contact_DupeContactTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testDuplicateContactAdd() {
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

    // Go directly to the URL of New Individual.
    $this->openCiviPage('contact/add', 'reset=1&ct=Individual');

    $firstName = substr(sha1(rand()), 0, 7);
    $lastName1 = substr(sha1(rand()), 0, 7);
    $email     = "{$firstName}@example.com";
    $lastName2 = substr(sha1(rand()), 0, 7);

    //contact details section
    //select prefix
    $this->click("prefix_id");
    $this->select("prefix_id", "value=3");

    //fill in first name
    $this->type("first_name", "$firstName");

    //fill in last name
    $this->type("last_name", "$lastName1");

    //fill in email
    $this->type("email_1_email", "$email");

    //check for matching contact
    //$this->click("_qf_Contact_refresh_dedupe");
    //$this->waitForPageToLoad($this->getTimeoutMsec());

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', "Contact Saved");

    // Go directly to the URL of New Individual.
    $this->openCiviPage('contact/add' , 'reset=1&ct=Individual');

    //contact details section


    //fill in first name
    $this->type("first_name", "$firstName");

    //fill in last name
    $this->type("last_name", "$lastName1");

    //fill in email
    $this->type("email_1_email", "$email");

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertElementContainsText("css=.notify-content", "Please correct the following errors in the form fields below: One matching contact was found. You can View or Edit the existing contact.");
  }
}



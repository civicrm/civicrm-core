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
class WebTest_Contact_TagAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAddTag() {
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

    // Go directly to the URL of the screen that you will be testing (New Tag).
    $this->open($this->sboxPath . "civicrm/admin/tag?action=add&reset=1");

    // As mentioned before, waitForPageToLoad is not always reliable. Below, we're waiting for the submit
    // button at the end of this page to show up, to make sure it's fully loaded.
    $this->waitForElementPresent("_qf_Tag_next");

    // take a tag name
    $tagName = 'tag_' . substr(sha1(rand()), 0, 7);

    // fill tag name
    $this->type("name", $tagName);

    // fill description
    $this->type("description", "Adding new tag.");

    // select used for contact
    $this->select("used_for", "value=civicrm_contact");

    // check reserved
    $this->click("is_reserved");

    // Clicking save.
    $this->click("_qf_Tag_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("The tag '$tagName' has been saved."));

    // sort by ID desc
    $this->click("xpath=//table//tr/th[text()=\"ID\"]");
    $this->waitForElementPresent("css=table.display tbody tr td");

    // verify text
    $this->waitForElementPresent("xpath=//table//tbody/tr/td[1][text()= '$tagName']");
    $this->waitForElementPresent("xpath=//table//tbody/tr/td[1][text()= '$tagName']/following-sibling::td[2][text()='Adding new tag. ']");
    $this->waitForElementPresent("xpath=//table//tbody/tr/td[1][text()= '$tagName']/following-sibling::td[4][text()= 'Contacts']");
    $this->waitForElementPresent("xpath=//table//tbody/tr/td[1][text()= '$tagName']/following-sibling::td[7]/span/a[text()= 'Edit']");
  }

  function testAddTagSet() {
    $this->open($this->sboxPath);
    $this->webtestLogin();

    // Go directly to the URL of the screen that you will be testing (New Tag).
    $this->open($this->sboxPath . "civicrm/admin/tag?action=add&reset=1&tagset=1");

    // take a tagset name
    $tagSetName = 'tagset_' . substr(sha1(rand()), 0, 7);

    // fill tagset name
    $this->type("name", $tagSetName);

    // fill description
    $this->type("description", "Adding new tag set.");

    // select used for contact
    $this->select("used_for", "value=civicrm_contact");

    // check reserved
    $this->click("is_reserved");

    // Clicking save.
    $this->click("_qf_Tag_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("The tag '$tagSetName' has been saved."));

    // sort by ID desc
    $this->click("xpath=//table//tr/th[text()=\"ID\"]");
    $this->waitForElementPresent("css=table.display tbody tr td");

    // verify text
    $this->waitForElementPresent("xpath=//table//tbody/tr/td[1][text()= '$tagSetName']");
    $this->waitForElementPresent("xpath=//table//tbody/tr/td[1][text()= '$tagSetName']/following-sibling::td[2][text()='Adding new tag set. ']");
    $this->waitForElementPresent("xpath=//table//tbody/tr/td[1][text()= '$tagSetName']/following-sibling::td[4][text()= 'Contacts']");
    $this->waitForElementPresent("xpath=//table//tbody/tr/td[1][text()= '$tagSetName']/following-sibling::td[7]/span/a[text()= 'Edit']");
  }
}



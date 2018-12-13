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
 * Class WebTest_Contact_TagAddTest
 */
class WebTest_Contact_TagAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAddTag() {
    $this->webtestLogin();

    $this->openCiviPage("admin/tag", "action=add&reset=1", "_qf_Tag_next");

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
    $this->click("xpath=//div[@id='cat']/div/table/thead/tr/th[2]/div[text()='ID']");
    $this->waitForElementPresent("css=table.display tbody tr td");

    // verify text
    $this->assertTrue($this->isTextPresent($tagName), 'Missing text: ' . $tagName);
    $this->assertTrue($this->isTextPresent('Adding new tag.'), 'Missing text: ' . 'Adding new tag.');
    $this->assertTrue($this->isTextPresent('Contacts'), 'Missing text: ' . 'Contacts');
    $this->assertTrue($this->isTextPresent('Edit'), 'Missing text: ' . 'Edit');
  }

  public function testAddTagSet() {
    $this->webtestLogin();

    $this->openCiviPage("admin/tag", "action=add&reset=1&tagset=1");

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
    $this->click("xpath=//table[@class='display dataTable no-footer']/thead/tr/th[2]/div[text()='ID']");
    $this->waitForElementPresent("css=table.display tbody tr td");

    // verify text
    $this->assertTrue($this->isTextPresent($tagSetName), 'Missing text: ' . $tagSetName);
    $this->assertTrue($this->isTextPresent('Adding new tag set.'), 'Missing text: ' . 'Adding new tag set.');
    $this->assertTrue($this->isTextPresent('Contacts'), 'Missing text: ' . 'Contacts');
    $this->assertTrue($this->isTextPresent('Edit'), 'Missing text: ' . 'Edit');
  }

}

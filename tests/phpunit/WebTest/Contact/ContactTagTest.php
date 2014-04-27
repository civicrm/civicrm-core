<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
class WebTest_Contact_ContactTagTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testTagAContact() {
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
    $this->waitForText('crm-notification-container', "The tag '$tagName' has been saved.");

    // Adding contact
    // We're using Quick Add block on the main page for this.
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Anderson", "$firstName@anderson.name");

    // visit tag tab
    $this->click("css=li#tab_tag a");
    $this->waitForElementPresent("css=div#tagtree");

    // check tag we have created
    $this->click("xpath=//ul/li/label[text()=\"$tagName\"]");
    $this->waitForElementPresent("css=.success");

    // Is status message correct?
    $this->waitForText('crm-notification-container', "Saved");
  }

  function testTagSetContact() {
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
    $this->waitForText('crm-notification-container', "The tag '$tagSetName' has been saved.");

    // Adding contact
    // We're using Quick Add block on the main page for this.
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Anderson", "$firstName@anderson.name");

    // visit tag tab
    $this->click("css=li#tab_tag a");
    $this->waitForElementPresent("css=div#tagtree");

    //add Tagset to contact
    $this->click("//div[@id='Tag']/div[2]/div[1]/div/ul/li[1]/input");
    $this->type("//div[@id='Tag']/div[2]/div[1]/div/ul/li[1]/input", 'tagset1');
    $this->typeKeys("//div[@id='Tag']/div[2]/div[1]/div/ul/li[1]/input", 'tagset1');

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("css=div.token-input-dropdown-facebook");
    $this->waitForElementPresent("css=li.token-input-dropdown-item2-facebook");

    // ...need to use mouseDownAt on first result (which is a li element), click does not work
    $this->mouseDownAt("css=li.token-input-dropdown-item2-facebook");

    //$this->waitForElementPresent("//div[@id='Tag']/div[3]/div[1]/ul/li[1]/span");
    $this->click("//div[@id='Tag']/div[2]/div[1]/div/ul/li[2]/input");
    $this->type("//div[@id='Tag']/div[2]/div[1]/div/ul/li[2]/input", 'tagset2');
    $this->typeKeys("//div[@id='Tag']/div[2]/div[1]/div/ul/li[2]/input", 'tagset2');

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("css=div.token-input-dropdown-facebook");
    $this->waitForElementPresent("css=li.token-input-dropdown-item2-facebook");

    // ...need to use mouseDownAt on first result (which is a li element), click does not work
    $this->mouseDownAt("css=li.token-input-dropdown-item2-facebook");

    $this->click("//div[@id='Tag']/div[2]/div[1]/div/ul/li");

    // Type search name in autocomplete.
    $this->click("css=input#sort_name_navigation");
    $this->type("css=input#sort_name_navigation", $firstName);
    $this->typeKeys("css=input#sort_name_navigation", $firstName);

    // Wait for result list.
    $this->waitForElementPresent("//*[@id='ui-id-1']/li[1]/a");

    // Visit contact summary page.
    $this->click("//*[@id='ui-id-1']/li[1]/a");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('tags', "tagset1, tagset2");
  }
}


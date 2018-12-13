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
 * Class WebTest_Contact_ContactTagTest
 */
class WebTest_Contact_ContactTagTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testTagAContact() {
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
    $this->click("xpath=//ul/li/span/label[text()=\"$tagName\"]");
    $this->checkCRMStatus();
  }

  public function testTagSetContact() {
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
    $this->click("xpath=//div[@id='Tag']/div[2]/div/div/ul/li/input");
    $this->keyDown("xpath=//div[@id='Tag']/div[2]/div/div/ul/li/input", " ");
    $this->type("xpath=//div[@id='Tag']/div[2]/div/div/ul/li/input", 'tagset1');
    $this->typeKeys("xpath=//div[@id='Tag']/div[2]/div/div/ul/li/input", 'tagset1');

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("xpath=//div[@class='select2-result-label']");

    // ...need to use mouseDownAt on first result (which is a li element), click does not work
    $this->clickAt("xpath=//div[@class='select2-result-label']");
    $this->waitForElementPresent("//div[@id='Tag']/div[2]/div/div/ul/li[1]/div[text()='tagset1']");
    $this->click("xpath=//div[@id='Tag']/div[2]/div/div/ul/li[2]/input");
    $this->keyDown("xpath=//div[@id='Tag']/div[2]/div/div/ul/li[2]/input", " ");
    $this->type("xpath=//div[@id='Tag']/div[2]/div/div/ul/li[2]/input", 'tagset2');
    $this->typeKeys("xpath=//div[@id='Tag']/div[2]/div/div/ul/li[2]/input", 'tagset2');

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("xpath=//div[@class='select2-result-label']");

    // ...need to use mouseDownAt on first result (which is a li element), click does not work
    $this->clickAt("xpath=//div[@class='select2-result-label']");

    // Type search name in autocomplete.
    $this->click("css=input#sort_name_navigation");
    $this->type("css=input#sort_name_navigation", $firstName);
    $this->typeKeys("css=input#sort_name_navigation", $firstName);

    // Wait for result list.
    $this->waitForElementPresent("css=ul.ui-autocomplete li");

    // Visit contact summary page.
    $this->click("css=ul.ui-autocomplete li");
    $this->waitForAjaxContent();
    $this->waitForText('tags', "tagset1, tagset2");
  }

}

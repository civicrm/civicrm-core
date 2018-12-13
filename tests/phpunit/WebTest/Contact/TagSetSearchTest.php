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
 * Class WebTest_Contact_TagSetSearchTest
 */
class WebTest_Contact_TagSetSearchTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testTagSetSearch() {
    $this->webtestLogin();

    $tagSet1 = $this->_testAddTagSet();
    $tagSet2 = $this->_testAddTagSet();

    // Individual 1
    $contact1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($contact1, "Anderson", "{$contact1}@example.com");

    $this->click('css=li#tab_tag a');
    $this->waitForElementPresent("xpath=//*[@id='contact_taglist_{$tagSet1}']/../div/ul/li[1]/input");

    // Add tag1 for Individual 1
    $tag1 = substr(sha1(rand()), 0, 5);
    $this->click("xpath=//*[@id='contact_taglist_{$tagSet1}']/../div/ul/li[1]/input");
    $this->keyDown("xpath=//*[@id='contact_taglist_{$tagSet1}']/../div/ul/li[1]/input", " ");
    $this->type("xpath=//*[@id='contact_taglist_{$tagSet1}']/../div/ul/li[1]/input", $tag1);
    $this->typeKeys("xpath=//*[@id='contact_taglist_{$tagSet1}']/../div/ul/li[1]/input", $tag1);
    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("xpath=//div[@class='select2-result-label']");
    // ...need to use mouseDownAt on first result (which is a li element), click does not work
    $this->clickAt("xpath=//div[@class='select2-result-label']");
    $this->waitForTextPresent($tag1);

    // Add tag2 for Individual 1
    $tag2 = substr(sha1(rand()), 0, 5);
    $this->click("xpath=//*[@id='contact_taglist_{$tagSet2}']/../div/ul/li[1]/input");
    $this->keyDown("xpath=//*[@id='contact_taglist_{$tagSet2}']/../div/ul/li[1]/input", " ");
    $this->type("xpath=//*[@id='contact_taglist_{$tagSet2}']/../div/ul/li[1]/input", $tag2);
    $this->typeKeys("xpath=//*[@id='contact_taglist_{$tagSet2}']/../div/ul/li[1]/input", $tag2);
    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("xpath=//div[@class='select2-result-label']");
    // ...need to use mouseDownAt on first result (which is a li element), click does not work
    $this->clickAt("xpath=//div[@class='select2-result-label']");
    $this->waitForTextPresent($tag2);

    // Individual 2
    $contact2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($contact2, "Anderson", "{$contact2}@example.com");

    $this->click('css=li#tab_tag a');
    $this->waitForElementPresent("xpath=//*[@id='contact_taglist_{$tagSet1}']/../div/ul/li[1]/input");

    // Add tag1 for Individual 2
    $this->click("xpath=//*[@id='contact_taglist_{$tagSet1}']/../div/ul/li[1]/input");
    $this->keyDown("xpath=//*[@id='contact_taglist_{$tagSet1}']/../div/ul/li[1]/input", " ");
    $this->type("xpath=//*[@id='contact_taglist_{$tagSet1}']/../div/ul/li[1]/input", $tag1);
    $this->typeKeys("xpath=//*[@id='contact_taglist_{$tagSet1}']/../div/ul/li[1]/input", $tag1);
    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("xpath=//div[@class='select2-result-label']");
    // ...need to use mouseDownAt on first result (which is a li element), click does not work
    $this->clickAt("xpath=//div[@class='select2-result-label']");
    $this->waitForTextPresent($tag1);

    // Go to Advance search.
    $this->openCiviPage('contact/search/advanced', 'reset=1');

    // Check both the tagset.
    $this->assertTrue($this->isElementPresent("contact_taglist_{$tagSet1}"));
    $this->assertTrue($this->isElementPresent("contact_taglist_{$tagSet2}"));

    // Search contact using tags.
    $this->select2("contact_taglist_{$tagSet1}", $tag1, TRUE);

    $this->waitForTextPresent($tag1);
    $this->select2("contact_taglist_{$tagSet2}", $tag2, TRUE);

    $this->waitForTextPresent($tag2);

    $this->click("_qf_Advanced_refresh");
    $this->waitForPageToLoad(2 * $this->getTimeoutMsec());

    // Check result.
    $this->assertElementContainsText('search-status', "2 Contacts");
    $this->assertElementContainsText('css=.crm-search-results table.selector', "Anderson, $contact1");
    $this->assertElementContainsText('css=.crm-search-results table.selector', "Anderson, $contact2");
  }

  /**
   * @return array
   */
  public function _testAddTagSet() {
    // Go to add tag set url.
    $this->openCiviPage('admin/tag', 'action=add&reset=1&tagset=1');

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

    // sort by ID desc
    $this->click("xpath=//div[@id='cat']/div/table/thead/tr/th[2]/div[text()='ID']");
    $this->waitForElementPresent("css=table.display tbody tr td");

    // verify text
    $this->assertTrue($this->isTextPresent($tagSetName), 'Missing text: ' . $tagSetName);

    $tagid = explode('&id=', $this->getAttribute("xpath=//table[@class='display dataTable no-footer']/tbody//tr/td[1]/div[text()= '$tagSetName']/../../td[8]/span/a[text()= 'Edit']@href"));
    $tagid = explode('&', $tagid[1]);
    $tagid = $tagid[0];

    return $tagid;
  }

}

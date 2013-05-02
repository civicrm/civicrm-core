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
class WebTest_Contact_SearchTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testQuickSearch() {
    $this->webtestLogin();

    // Adding contact
    // We're using Quick Add block on the main page for this.
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Anderson", "$firstName.anderson@example.org");

    $sortName = "Anderson, $firstName";
    $displayName = "$firstName Anderson";

    $this->openCiviPage("dashboard", "reset=1");

    // type sortname in autocomplete
    $this->click("css=input#sort_name_navigation");
    $this->type("css=input#sort_name_navigation", $sortName);
    $this->typeKeys("css=input#sort_name_navigation", $sortName);

    // wait for result list
    $this->waitForElementPresent("css=div.ac_results-inner li");

    // visit contact summary page
    $this->click("css=div.ac_results-inner li");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is contact present?
    $this->assertTrue($this->isTextPresent("$displayName"), "Contact did not find!");
  }

  function testQuickSearchPartial() {
    $this->webtestLogin();

    // Adding contact
    // We're using Quick Add block on the main page for this.
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Adams", "{$firstName}.adams@example.org");

    $sortName = "Adams, {$firstName}";

    $this->openCiviPage("dashboard", "reset=1");

    // type partial sortname in autocomplete
    $this->click("css=input#sort_name_navigation");
    $this->type("css=input#sort_name_navigation", 'ada');
    $this->typeKeys("css=input#sort_name_navigation", 'ada');

    $this->clickLink("_qf_Advanced_refresh");

    // make sure we're on search results page
    $this->waitForElementPresent("alpha-filter");

    // Is contact present in search result?
    $this->assertElementContainsText('css=.crm-search-results > table.row-highlight', $sortName);
  }

  function testContactSearch() {
    $this->webtestLogin();

    // Create new tag.
    $tagName = 'tag_' . substr(sha1(rand()), 0, 7);
    self::addTag($tagName, $this);

    // Create new group
    $groupName = 'group_' . substr(sha1(rand()), 0, 7);
    $this->WebtestAddGroup($groupName);

    // Adding contact
    // We're using Quick Add block on the main page for this.
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Smith", "$firstName.smith@example.org");

    $sortName = "Smith, $firstName";
    $displayName = "$firstName Smith";

    // add contact to group
    // visit group tab
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // add to group
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // tag a contact
    // visit tag tab
    $this->click("css=li#tab_tag a");
    $this->waitForElementPresent("css=div#tagtree");

    // select tag
    $this->click("xpath=//ul/li/label[text()=\"$tagName\"]");
    $this->waitForElementPresent("css=.success");

    // visit contact search page
    $this->openCiviPage("contact/search", "reset=1");

    // fill name as first_name
    $this->type("css=.crm-basic-criteria-form-block input#sort_name", $firstName);

    // select contact type as Indiividual
    $this->select("contact_type", "value=Individual");

    // select group
    $this->select("group", "label=$groupName");

    // select tag
    $this->select("tag", "label=$tagName");

    // click to search
    $this->click("_qf_Basic_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is contact present in search result?
    $this->assertElementContainsText('css=.crm-search-results > table.row-highlight', $sortName);
  }

  /**
   * This code is reused with advanced search, hence the reference to $self
   *
   * @static
   */
  static function addTag($tagName = 'New Tag', $self) {
    $self->openCiviPage('admin/tag', array('reset' => 1, 'action' => 'add'), '_qf_Tag_next');

    // fill tag name
    $self->type("name", $tagName);

    // fill description
    $self->type("description", "Adding new tag.");

    // select used for contact
    $self->select("used_for", "value=civicrm_contact");

    // check reserved
    $self->click("is_reserved");

    // Clicking save.
    $self->click("_qf_Tag_next");
    $self->waitForPageToLoad($self->getTimeoutMsec());

    // Is status message correct?
    $self->assertTrue($self->isTextPresent("The tag '$tagName' has been saved."));
  }

  // CRM-6586
  function testContactSearchExport() {
    $this->webtestLogin();

    // Create new  group
    $parentGroupName = 'Parentgroup_' . substr(sha1(rand()), 0, 7);
    $this->WebtestAddGroup($parentGroupName);

    // Create new group and select the previously selected group as parent group for this new group.
    $childGroupName = 'Childgroup_' . substr(sha1(rand()), 0, 7);
    $this->WebtestAddGroup($childGroupName, $parentGroupName);

    // Adding Parent group contact
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Smith", "$firstName.smith@example.org");

    $sortName = "Smith, $firstName";
    $displayName = "$firstName Smith";

    // add contact to parent  group
    // visit group tab
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // add to group
    $this->select("group_id", "label=$parentGroupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Adding child group contact
    // We're using Quick Add block on the main page for this.
    $childName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($childName, "John", "$childName.john@example.org");

    $childSortName = "John, $childName";
    $childDisplayName = "$childName John";

    // add contact to child group
    // visit group tab
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // add to child group
    $this->select("group_id", "*$childGroupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // visit contact search page
    $this->openCiviPage("contact/search", "reset=1");

    // select contact type as Indiividual
    $this->select("contact_type", "value=Individual");

    // select group
    $this->select("group", "label=$parentGroupName");

    // click to search
    $this->click("_qf_Basic_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is contact present in search result?
    $this->assertElementContainsText('css=.crm-search-results > table.row-highlight', $sortName);
    $this->assertElementContainsText('css=.crm-search-results > table.row-highlight', $childSortName);

    // select to export all the contasct from search result
    $this->click("CIVICRM_QFID_ts_all_4");

    // Select the task action to export
    $this->click("task");
    $this->select("task", "label=Export Contacts");
    $this->click("Go");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click("_qf_Select_next-bottom");
  }
}

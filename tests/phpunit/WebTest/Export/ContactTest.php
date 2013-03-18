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


require_once 'WebTest/Export/ExportCiviSeleniumTestCase.php';
class WebTest_Export_ContactTest extends ExportCiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   *  Test Contact Export.
   */
  function testContactExport() {
    $this->open($this->sboxPath);

    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();

    // Create new  group
    $parentGroupName = 'Parentgroup_' . substr(sha1(rand()), 0, 7);
    $this->addContactGroup($parentGroupName);

    // Create new group and select the previously selected group as parent group for this new group.
    $childGroupName = 'Childgroup_' . substr(sha1(rand()), 0, 7);
    $this->addContactGroup($childGroupName, $parentGroupName);

    // Adding Parent group contact
    // We're using Quick Add block on the main page for this.
    $firstName = 'a' . substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Smith", "$firstName.smith@example.org");

    $sortName = "Smith, $firstName";
    $displayName = "$firstName Smith";

    // Add contact to parent  group
    // visit group tab.
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // Add to group.
    $this->select("group_id", "label=$parentGroupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Adding child group contact
    // We're using Quick Add block on the main page for this.
    $childName = 'b' . substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($childName, "John", "$childName.john@example.org");

    $childSortName = "John, $childName";
    $childDisplayName = "$childName John";

    // Add contact to child group
    // visit group tab.
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // Add to child group.
    $this->select("group_id", "label=regexp:$childGroupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Visit contact search page.
    $this->openCiviPage("contact/search", "reset=1");

    // Select contact type as Indiividual.
    $this->select("contact_type", "value=Individual");

    // Select group.
    $this->select("group", "label=$parentGroupName");

    // Click to search.
    $this->click("_qf_Basic_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is contact present in search result?
    $this->assertElementContainsText('css=div.crm-search-results', $sortName, "Contact did not found in search result!");
       
    // Is contact present in search result?
    $this->assertElementContainsText('css=div.crm-search-results', $childSortName, "Contact did not found in search result!");
    
    // select to export all the contasct from search result.
    $this->click("CIVICRM_QFID_ts_all_4");

    // Select the task action to export.
    $this->click("task");
    $this->select("task", "label=Export Contacts");
    $this->click("Go");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $csvFile = $this->downloadCSV("_qf_Select_next-bottom");

    // Build header row for assertion.
    require_once 'CRM/Contact/BAO/Contact.php';
    $expotableFields = CRM_Contact_BAO_Contact::exportableFields('All', FALSE, TRUE);

    $checkHeaders = array();
    foreach ($expotableFields as $key => $field) {
      // Exclude custom fields.
      if ($key && (substr($key, 0, 6) == 'custom')) {
        continue;
      }
      $checkHeaders[] = $field['title'];
    }

    // All other rows to be check.
    $checkRows = array(
      1 => array(
        'First Name' => $firstName,
        'Last Name' => 'Smith',
        'Email' => "$firstName.smith@example.org",
        'Sort Name' => $sortName,
        'Display Name' => $displayName,
      ),
      2 => array(
        'First Name' => $childName,
        'Last Name' => 'John',
        'Email' => "$childName.john@example.org",
        'Sort Name' => $childSortName,
        'Display Name' => $childDisplayName,
      ),
    );

    // Read CSV and fire assertions.
    $this->reviewCSV($csvFile, $checkHeaders, $checkRows, 2);
  }

  function testMergeHousehold() {
    $this->open($this->sboxPath);

    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();

    // Create new  group
    $groupName = 'TestGroup_' . substr(sha1(rand()), 0, 7);
    $this->addContactGroup($groupName);

    // Adding Parent group contact
    // We're using Quick Add block on the main page for this.
    $houseHold = 'H' . substr(sha1(rand()), 0, 5) . ' House';

    $this->openCiviPage("contact/add", "reset=1&ct=Household");
    $this->click('household_name');
    $this->type('household_name', $houseHold);

    //address section
    $this->click("addressBlock");
    $this->waitForElementPresent("address_1_street_address");

    // fill in address
    $this->click("//div[@id='addressBlockId']/div[1]");
    $this->type("address_1_street_address", "121A Sherman St. Apt. 12");
    $this->type("address_1_city", "Dumfries");
    $this->type("address_1_postal_code", "1234");
    $this->assertElementContainsText('address_1', "- select - United States");
    $this->select("address_1_state_province_id", "value=1019");

    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Add contact to group
    // visit group tab.
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // Add to group.
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $firstName1 = 'aa' . substr(sha1(rand()), 0, 5);
    $this->webtestAddContact($firstName1, "Smith", "{$firstName1}.smith@example.org");

    $sortName1 = "Smith, {$firstName1}";
    $displayName1 = "{$firstName1} Smith";

    // Add contact to parent  group
    // visit group tab.
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // Add to group.
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $firstName2 = 'bb' . substr(sha1(rand()), 0, 5);

    $this->openCiviPage("contact/add", "reset=1&ct=Individual", "_qf_Contact_upload_view-bottom");
    $this->type('first_name', $firstName2);
    $this->type('last_name', "Smith");
    $this->type('email_1_email', "{$firstName2}.smith@example.org");

    //address section
    $this->click("addressBlock");
    $this->waitForElementPresent("address_1_street_address");

    $this->click("//div[@id='addressBlockId']/div[1]");

    $this->click("address[1][use_shared_address]");
    $this->waitForElementPresent("contact_1");
    $this->webtestFillAutocomplete($houseHold);
    $this->waitForTextPresent("121A Sherman");

    $this->click('_qf_Contact_upload_view-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $sortName2 = "Smith, {$firstName2}";
    $displayName2 = "{$firstName2} Smith";

    // Add contact to parent  group
    // visit group tab.
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // Add to group.
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->openCiviPage("contact/search", "reset=1", NULL);

    // Select group.
    $this->select("group", "label=$groupName");

    // Click to search.
    $this->click("_qf_Basic_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is contact present in search result?
    $this->assertElementContainsText('css=div.crm-search-results', $sortName1, "Contact did not found in search result!");
   
    // Is contact present in search result?
    $this->assertElementContainsText('css=div.crm-search-results', $sortName2, "Contact did not found in search result!");
 
    // Is contact present in search result?
    $this->assertElementContainsText('css=div.crm-search-results', $houseHold, "Contact did not found in search result!");
 
    // select to export all the contasct from search result.
    $this->click("CIVICRM_QFID_ts_all_4");

    // Select the task action to export.
    $this->click("task");
    $this->select("task", "label=Export Contacts");
    $this->click("Go");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click("CIVICRM_QFID_2_10");

    $csvFile = $this->downloadCSV("_qf_Select_next-bottom");

    // Build header row for assertion.
    require_once 'CRM/Contact/BAO/Contact.php';
    $expotableFields = CRM_Contact_BAO_Contact::exportableFields('All', FALSE, TRUE);

    $checkHeaders = array();
    foreach ($expotableFields as $key => $field) {
      // Exclude custom fields.
      if ($key && (substr($key, 0, 6) == 'custom')) {
        continue;
      }
      $checkHeaders[] = $field['title'];
    }

    // All other rows to be check.
    $checkRows = array(
      1 => array(
        'Contact Type' => 'Household',
        'Household Name' => $houseHold,
      ),
      2 => array(
        'Contact Type' => 'Individual',
        'First Name' => $firstName1,
        'Email' => "{$firstName1}.smith@example.org",
        'Sort Name' => $sortName1,
        'Display Name' => $displayName1,
      ),
    );

    // Read CSV and fire assertions.
    $this->reviewCSV($csvFile, $checkHeaders, $checkRows, 2);
  }

  function addContactGroup($groupName = 'New Group', $parentGroupName = "- select -") {
    $this->openCiviPage("group/add", "reset=1", "_qf_Edit_upload");

    // Fill group name.
    $this->type("title", $groupName);

    // Fill description.
    $this->type("description", "Adding new group.");

    // Check Access Control.
    $this->click("group_type[1]");

    // Check Mailing List.
    $this->click("group_type[2]");

    // Select Visibility as Public Pages.
    $this->select("visibility", "value=Public Pages");

    // Select parent group.
    $this->select("parents", "label=$parentGroupName");

    // Clicking save.
    $this->click("_qf_Edit_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertElementContainsText('crm-notification-container', "The Group '$groupName' has been saved.");
  }
}
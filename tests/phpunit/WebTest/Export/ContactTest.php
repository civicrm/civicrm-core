<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

/**
 * Class WebTest_Export_ContactTest
 */
class WebTest_Export_ContactTest extends ExportCiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testPrefixGenderSuffix() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    $this->webtestLogin();

    // Create new  group
    $parentGroupName = 'TestSuffixPrefixGender_' . substr(sha1(rand()), 0, 7);
    $this->WebtestAddGroup($parentGroupName);

    // Adding Parent group contact
    // We're using Quick Add block on the main page for this.
    $firstContactName = 'TestExport' . substr(sha1(rand()), 0, 7);

    list($emailContactFirst, $prefixLabelContactFrst, $suffixLabelContactFrst, $genderLabelContactFrst) = WebTest_Export_ContactTest::webtestAddContactWithGenderPrefixSuffix($firstContactName, "Smith", "$firstContactName.smith@example.org", NULL);

    $sortFirstName = "Smith, $firstContactName";
    $displayFirstName = "$firstContactName Smith";

    // Add contact to parent  group
    // visit group tab.
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // Add to group.
    $this->select("group_id", "label=$parentGroupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForAjaxContent();

    $secondContactName = 'TestExport2' . substr(sha1(rand()), 0, 7);
    list($emailContactSecond, $prefixLabelContactScnd, $suffixLabelContactScnd, $genderLabelContactScnd) = WebTest_Export_ContactTest::webtestAddContactWithGenderPrefixSuffix($secondContactName, "John", "$secondContactName.john@example.org", NULL);

    $sortSecondName = "John, $secondContactName";
    $displaySecondName = "$secondContactName John";

    // Add contact to parent  group
    // visit group tab.
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // Add to group.
    $this->select("group_id", "label=$parentGroupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForAjaxContent();

    $this->openCiviPage("contact/search", "reset=1");

    // Select contact type as Indiividual.
    $this->select("contact_type", "value=Individual");

    // Select group.
    $this->select("group", "label=$parentGroupName");

    // Click to search.
    $this->clickLink("_qf_Basic_refresh");

    // Is contact present in search result?
    $this->assertElementContainsText('css=div.crm-search-results', $sortFirstName, "Contact did not found in search result!");

    // Is contact present in search result?
    $this->assertElementContainsText('css=div.crm-search-results', $sortSecondName, "Contact did not found in search result!");

    // select to export all the contasct from search result.
    $this->click("CIVICRM_QFID_ts_all_4");

    // Select the task action to export.
    $this->click("task");
    $this->select("task", "label=Export Contacts");
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
        'First Name' => $secondContactName,
        'Last Name' => 'John',
        'Email' => '' . strtolower($emailContactSecond) . '',
        'Individual Suffix' => $suffixLabelContactScnd,
        'Gender' => $genderLabelContactScnd,
      ),
      2 => array(
        'First Name' => $firstContactName,
        'Last Name' => 'Smith',
        'Email' => '' . strtolower($emailContactFirst) . '',
        'Sort Name' => $sortFirstName,
        'Display Name' => $prefixLabelContactFrst . ' ' . $displayFirstName . ' ' . $suffixLabelContactFrst,
        'Individual Prefix' => $prefixLabelContactFrst,
        'Individual Suffix' => $suffixLabelContactFrst,
        'Gender' => $genderLabelContactFrst,
      ),
    );

    // Read CSV and fire assertions.
    $this->reviewCSV($csvFile, $checkHeaders, $checkRows, 2);
  }

  /**
   *  Test Contact Export.
   */
  public function testContactExport() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    $this->webtestLogin();

    // Create new  group
    $parentGroupName = 'Parentgroup_' . substr(sha1(rand()), 0, 7);
    $this->WebtestAddGroup($parentGroupName);

    // Create new group and select the previously selected group as parent group for this new group.
    $childGroupName = 'Childgroup_' . substr(sha1(rand()), 0, 7);
    $this->WebtestAddGroup($childGroupName, $parentGroupName);

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
    $this->waitForAjaxContent();

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
    $this->waitForAjaxContent();

    // Visit contact search page.
    $this->openCiviPage("contact/search", "reset=1");

    // Select contact type as Indiividual.
    $this->select("contact_type", "value=Individual");

    // Select group.
    $this->select("group", "label=$parentGroupName");

    // Click to search.
    $this->clickLink("_qf_Basic_refresh");

    // Is contact present in search result?
    $this->assertElementContainsText('css=div.crm-search-results', $sortName, "Contact did not found in search result!");

    // Is contact present in search result?
    $this->assertElementContainsText('css=div.crm-search-results', $childSortName, "Contact did not found in search result!");

    // select to export all the contasct from search result.
    $this->click("CIVICRM_QFID_ts_all_4");

    // Select the task action to export.
    $this->click("task");
    $this->select("task", "label=Export Contacts");
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
        'First Name' => $childName,
        'Last Name' => 'John',
        'Email' => "$childName.john@example.org",
        'Sort Name' => $childSortName,
        'Display Name' => $childDisplayName,
      ),
      2 => array(
        'First Name' => $firstName,
        'Last Name' => 'Smith',
        'Email' => "$firstName.smith@example.org",
        'Sort Name' => $sortName,
        'Display Name' => $displayName,
      ),
    );

    // Read CSV and fire assertions.
    $this->reviewCSV($csvFile, $checkHeaders, $checkRows, 2);
  }

  public function testMergeHousehold() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    $this->webtestLogin();

    // Create new  group
    $groupName = 'TestGroup_' . substr(sha1(rand()), 0, 7);
    $this->WebtestAddGroup($groupName);

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
    $this->select("address_1_country_id", "United States");
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
    $this->waitForAjaxContent();

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
    $this->waitForAjaxContent();

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
    $this->waitForElementPresent("address_1_master_contact_id");
    $this->select2('address_1_master_contact_id', $houseHold);
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
    $this->waitForAjaxContent();

    $this->openCiviPage("contact/search", "reset=1", NULL);

    // Select group.
    $this->select("group", "label=$groupName");

    // Click to search.
    $this->clickLink("_qf_Basic_refresh");

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

  /**
   * @param string $fname
   * @param string $lname
   * @param null $email
   * @param null $contactSubtype
   *
   * @return array
   */
  public function webtestAddContactWithGenderPrefixSuffix($fname = 'Anthony', $lname = 'Anderson', $email = NULL, $contactSubtype = NULL) {
    $url = $this->sboxPath . 'civicrm/contact/add?reset=1&ct=Individual';
    if ($contactSubtype) {
      $url = $url . "&cst={$contactSubtype}";
    }
    $this->open($url);
    $this->waitForElementPresent('_qf_Contact_upload_view-bottom');

    $this->type('first_name', $fname);
    $this->type('last_name', $lname);
    if ($email === TRUE) {
      $email = substr(sha1(rand()), 0, 7) . '@example.org';
    }
    if ($email) {
      $this->type('email_1_email', $email);
    }
    $genderLabelArray = array(
      1 => 'Female',
      2 => 'Male',
      3 => 'Transgender',
    );
    $prefix = rand(1, 4);
    $suffix = rand(1, 8);
    $gender = rand(1, 3);
    $genderLabel = "civicrm_gender_" . $genderLabelArray[$gender] . "_$gender";
    $this->select("prefix_id", "value=$prefix");
    $this->select("suffix_id", "value=$suffix");
    $this->click("demographics");
    $this->waitForElementPresent("civicrm_gender_Female_1");
    $this->click($genderLabel, "value=$gender");
    $this->waitForElementPresent('_qf_Contact_upload_view-bottom');
    $this->click('_qf_Contact_upload_view-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $prefixLabel = WebTest_Export_ContactTest::getOptionLabel('individual_prefix', $prefix);
    $suffixLabel = WebTest_Export_ContactTest::getOptionLabel('individual_suffix', $suffix);
    $genderLabel = WebTest_Export_ContactTest::getOptionLabel('gender', $gender);
    return array($email, $prefixLabel, $suffixLabel, $genderLabel);
  }

  /**
   * @param string $optionGroupName
   * @param $optionValue
   *
   * @return array|int
   */
  public function getOptionLabel($optionGroupName, $optionValue) {
    $params = array(
      'version' => 3,
      'sequential' => 1,
      'option_group_name' => $optionGroupName,
      'value' => $optionValue,
      'return' => 'label',
    );
    $optionLabel = $this->webtest_civicrm_api("OptionValue", "getvalue", $params);
    return $optionLabel;
  }

}

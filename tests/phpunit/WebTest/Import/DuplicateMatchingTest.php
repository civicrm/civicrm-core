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

require_once 'WebTest/Import/ImportCiviSeleniumTestCase.php';

/**
 * Class WebTest_Import_DuplicateMatchingTest
 */
class WebTest_Import_DuplicateMatchingTest extends ImportCiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   *  Test contact import for Individuals Duplicate Matching.
   */
  public function testIndividualDuplicateMatchingImport() {
    $this->webtestLogin();

    $this->openCiviPage("contact/add", "reset=1&ct=Individual", 'first_name');

    $email = substr(sha1(rand()), 0, 7) . '@example.com';

    // fill in first name
    $firstName = substr(sha1(rand()), 0, 7);
    $this->type('first_name', $firstName);

    // fill in last name
    $lastName = substr(sha1(rand()), 0, 7);
    $this->type('last_name', $lastName);

    //fill in email
    $this->type('email_1_email', $email);

    // Clicking save.
    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");

    $existingContact = array(
      'first_name' => $firstName,
      'last_name' => $lastName,
      'email' => $email,
    );

    // Get sample import data.
    list($headers, $rows) = $this->_individualDuplicateMatchingCSVData($existingContact);

    // Import and check Individual contacts in Skip mode.
    $other = array('callbackImportSummary' => 'checkDuplicateContacts');
    $this->importContacts($headers, $rows, 'Individual', 'Skip', array(), $other);

    // Get imported contact Ids
    $importedContactIds = $this->_getImportedContactIds($rows);

    // Build update mode import headers
    $updateHeaders = array(
      'email' => 'Email',
      'first_name' => 'First Name',
      'last_name' => 'Last Name',
    );

    // Create update mode import rows
    $updateRows = array();
    $contact = current($rows);
    foreach ($importedContactIds as $cid) {
      $updateRows[$cid] = array(
        'email' => $contact['email'],
        'first_name' => substr(sha1(rand()), 0, 7),
        'last_name' => 'Anderson' . substr(sha1(rand()), 0, 7),
      );
      $contact = next($rows);
    }

    // Import and check Individual contacts in Update mode.
    $this->importContacts($updateHeaders, $updateRows, 'Individual', 'Update');

    // Headers that should not updated.
    $fillHeaders = $updateHeaders;

    // Headers that should fill.
    $fillHeaders['gender'] = 'Gender';
    $fillHeaders['dob'] = 'Birth Date';

    $fillRows = array();
    foreach ($importedContactIds as $cid) {
      $fillRows[$cid] = array(
        'email' => $updateRows[$cid]['email'],
        // should not update
        'first_name' => substr(sha1(rand()), 0, 7),
        // should not update
        'last_name' => 'Anderson' . substr(sha1(rand()), 0, 7),
        'gender' => 'Male',
        'dob' => '1986-04-16',
      );
    }

    // Import and check Individual contacts in Fill mode.
    $this->importContacts($fillHeaders, $fillRows, 'Individual', 'Fill');

    foreach ($importedContactIds as $cid) {
      $this->openCiviPage("contact/view", "reset=1&cid={$cid}");

      // Check old display name.
      $displayName = "{$updateRows[$cid]['first_name']} {$updateRows[$cid]['last_name']}";
      $this->assertTrue($this->isTextPresent("$displayName"), 'Contact display name should not update in fill mode!');

      $this->verifyText('css=div.crm-contact-gender_display', preg_quote($fillRows[$cid]['gender']));
    }

    // Recreate same conacts using 'No Duplicate Checking'
    $this->importContacts($headers, $rows, 'Individual', 'No Duplicate Checking');
  }

  /**
   *  Test contact import for Organization Duplicate Matching.
   */
  public function testOrganizationDuplicateMatchingImport() {
    $this->webtestLogin();

    //create oranization
    $this->openCiviPage("contact/add", "reset=1&ct=Organization", 'organization_name');

    // get value for organization contact
    $organizationName = 'org_' . substr(sha1(rand()), 0, 7);
    $organizationEmail = substr(sha1(rand()), 0, 7) . '@example.org';

    $this->click('organization_name');

    //fill in first name
    $this->type('organization_name', $organizationName);

    //fill in Email
    $this->type('email_1_email', $organizationEmail);

    // Clicking save.
    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Reset Organization strict dedupe rule for  Organization name
    // and Organization email (default)
    $this->webtestStrictDedupeRuleDefault('Organization');

    $organizationFields = array(
      'organization_name' => $organizationName,
      'email' => $organizationEmail,
    );
    // Get sample import data.
    list($headers, $rows) = $this->_organizationDuplicateMatchingCSVData($organizationFields);

    // Import and check Individual contacts in Skip mode.
    $other = array('callbackImportSummary' => 'checkDuplicateContacts');
    $this->importContacts($headers, $rows, 'Organization', 'Skip', array(), $other);

    // Get imported contact Ids
    $importedContactIds = $this->_getImportedContactIds($rows, 'Organization');

    // Build update mode import headers
    $updateHeaders = array(
      'email' => 'Email',
      'organization_name' => 'Organization Name',
    );

    // Create update mode import rows
    $updateRows = array();
    $contact = current($rows);
    foreach ($importedContactIds as $cid) {
      $updateRows[$cid] = array(
        'email' => $contact['email'],
        'organization_name' => 'UpdatedOrg ' . substr(sha1(rand()), 0, 7),
      );
      $contact = next($rows);
    }

    // Import and check Individual contacts in Update mode.
    $this->importContacts($updateHeaders, $updateRows, 'Organization', 'Update');

    // Headers that should not updated.
    $fillHeaders = $updateHeaders;

    // Headers that should fill.
    $fillHeaders['legal_name'] = 'Legal Name';

    $fillRows = array();
    foreach ($importedContactIds as $cid) {
      $fillRows[$cid] = array(
        'email' => $updateRows[$cid]['email'],
        // should not update
        'organization_name' => 'UpdateOrg ' . substr(sha1(rand()), 0, 7),
        'legal_name' => 'org ' . substr(sha1(rand()), 0, 7),
      );
    }

    // Import and check Individual contacts in Fill mode.
    $this->importContacts($fillHeaders, $fillRows, 'Organization', 'Fill');

    foreach ($importedContactIds as $cid) {
      $this->openCiviPage("contact/view", "reset=1&cid={$cid}");

      // Check old Organization name.
      $organizationName = $updateRows[$cid]['organization_name'];
      $this->assertTrue($this->isTextPresent("$organizationName"), 'Contact should not update in fill mode!');
      $this->verifyText("xpath=//div[@id='crm-contactinfo-content']/div/div[3]/div[2]", preg_quote($fillRows[$cid]['legal_name']));
    }

    // Recreate same conacts using 'No Duplicate Checking'
    $this->importContacts($headers, $rows, 'Organization', 'No Duplicate Checking');
  }

  /**
   *  Test contact import for Household Duplicate Matching.
   */
  public function testHouseholdDuplicateMatchingImport() {
    $this->webtestLogin();

    // create household
    $this->openCiviPage("contact/add", "reset=1&ct=Household", 'household_name');

    // get values for household contact
    $householdName = 'household_' . substr(sha1(rand()), 0, 7);
    $householdEmail = substr(sha1(rand()), 0, 7) . '@example.com';

    //fill in household name
    $this->type('household_name', $householdName);

    //fill in Email
    $this->type('email_1_email', $householdEmail);

    // Clicking save.
    $this->click('_qf_Contact_upload_view');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Reset Household strict dedupe rule for Household name
    // and Household email (default)
    $this->webtestStrictDedupeRuleDefault('Household');

    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Store household contact value in array
    $householdFields = array(
      'household_name' => $householdName,
      'email' => $householdEmail,
    );

    // Get sample import data.
    list($headers, $rows) = $this->_householdDuplicateMatchingCSVData($householdFields);

    // Import and check Individual contacts in Skip mode.
    $other = array('callbackImportSummary' => 'checkDuplicateContacts');
    $this->importContacts($headers, $rows, 'Household', 'Skip', array(), $other);

    // Get imported contact Ids
    $importedContactIds = $this->_getImportedContactIds($rows, 'Household');

    // Build update mode import headers
    $updateHeaders = array(
      'email' => 'Email',
      'household_name' => 'Household Name',
    );

    // Create update mode import rows
    $updateRows = array();
    $contact = current($rows);
    foreach ($importedContactIds as $cid) {
      $updateRows[$cid] = array(
        'email' => $contact['email'],
        'household_name' => 'UpdatedHousehold ' . substr(sha1(rand()), 0, 7),
      );
      $contact = next($rows);
    }

    $this->importContacts($updateHeaders, $updateRows, 'Household', 'Update');

    // Headers that should not updated.
    $fillHeaders = $updateHeaders;

    // Headers that should fill.
    $fillHeaders['nick_name'] = 'Nick Name';

    $fillRows = array();
    foreach ($importedContactIds as $cid) {
      $fillRows[$cid] = array(
        'email' => $updateRows[$cid]['email'],
        // should not update
        'household_name' => 'UpdatedHousehold ' . substr(sha1(rand()), 0, 7),
        'nick_name' => 'Household ' . substr(sha1(rand()), 0, 7),
      );
    }

    // Import and check Individual contacts in Fill mode.
    $this->importContacts($fillHeaders, $fillRows, 'Household', 'Fill');

    foreach ($importedContactIds as $cid) {
      $this->openCiviPage("contact/view", "reset=1&cid={$cid}");

      // Check old Household name.
      $householdName = $updateRows[$cid]['household_name'];
      $this->assertTrue($this->isTextPresent("$householdName"), 'Contact should not update in fill mode!');
      $this->verifyText("xpath=//div[@id='crm-contactinfo-content']/div/div[2]/div[2]", preg_quote($fillRows[$cid]['nick_name']));
    }

    // Recreate same conacts using 'No Duplicate Checking'
    $this->importContacts($headers, $rows, 'Household', 'No Duplicate Checking');
  }

  /**
   * @param $originalHeaders
   * @param $originalRows
   * @param $checkSummary
   */
  public function checkDuplicateContacts($originalHeaders, $originalRows, $checkSummary) {
    $this->assertTrue($this->isTextPresent('CiviCRM has detected one record which is a duplicate of existing CiviCRM contact record. These records have not been imported.'));

    $checkSummary = array(
      'Total Rows' => '2',
      'Duplicate Rows' => '1',
      'Total Contacts' => '1',
    );

    foreach ($checkSummary as $label => $value) {
      $this->verifyText("xpath=//table[@id='summary-counts']/tbody/tr/td[text()='{$label}']/following-sibling::td", preg_quote($value));
    }
  }

  /*
   *  Helper function to provide data for contact import for
   *  Individual Duplicate Matching.
   */
  /**
   * @param $individualFields
   *
   * @return array
   */
  public function _individualDuplicateMatchingCSVData($individualFields) {
    $headers = array(
      'first_name' => 'First Name',
      'middle_name' => 'Middle Name',
      'last_name' => 'Last Name',
      'email' => 'Email',
      'phone' => 'Phone',
      'address_1' => 'Additional Address 1',
      'address_2' => 'Additional Address 2',
      'city' => 'City',
      'state' => 'State',
      'country' => 'Country',
    );

    $rows = array(
      array(
        'first_name' => isset($individualFields['first_name']) ? $individualFields['first_name'] : substr(sha1(rand()), 0, 7),
        'middle_name' => isset($individualFields['middle_name']) ? $individualFields['middle_name'] : substr(sha1(rand()), 0, 7),
        'last_name' => isset($individualFields['last_name']) ? $individualFields['last_name'] : 'Anderson',
        'email' => isset($individualFields['email']) ? $individualFields['email'] : substr(sha1(rand()), 0, 7) . '@example.com',
        'phone' => '6949912154',
        'address_1' => 'Add 1',
        'address_2' => 'Add 2',
        'city' => 'Watson',
        'state' => 'NY',
        'country' => 'UNITED STATES',
      ),
      array(
        'first_name' => substr(sha1(rand()), 0, 7),
        'middle_name' => substr(sha1(rand()), 0, 7),
        'last_name' => 'Summerson',
        'email' => substr(sha1(rand()), 0, 7) . '@example.com',
        'phone' => '6944412154',
        'address_1' => 'Add 1',
        'address_2' => 'Add 2',
        'city' => 'Watson',
        'state' => 'NY',
        'country' => 'UNITED STATES',
      ),
    );

    return array($headers, $rows);
  }

  /*
   *  Helper function to provide data for contact import for
   *  Organizations Duplicate Matching.
   */
  /**
   * @param $organizationFields
   *
   * @return array
   */
  public function _organizationDuplicateMatchingCSVData($organizationFields) {
    $headers = array(
      'organization_name' => 'Organization Name',
      'email' => 'Email',
      'phone' => 'Phone',
      'address_1' => 'Additional Address 1',
      'address_2' => 'Additional Address 2',
      'city' => 'City',
      'state' => 'State',
      'country' => 'Country',
    );
    $rows = array(
      array(
        'organization_name' => isset($organizationFields['organization_name']) ? $organizationFields['organization_name'] : 'org_' . substr(sha1(rand()), 0, 7),
        'email' => isset($organizationFields['email']) ? $organizationFields['email'] : substr(sha1(rand()), 0, 7) . 'example.org',
        'phone' => '9949912154',
        'address_1' => 'Add 1',
        'address_2' => 'Add 2',
        'city' => 'Watson',
        'state' => 'NY',
        'country' => 'UNITED STATES',
      ),
      array(
        'organization_name' => 'org_' . substr(sha1(rand()), 0, 7),
        'email' => substr(sha1(rand()), 0, 7) . '@example.org',
        'phone' => '6949412154',
        'address_1' => 'Add 1',
        'address_2' => 'Add 2',
        'city' => 'Watson',
        'state' => 'NY',
        'country' => 'UNITED STATES',
      ),
    );

    return array($headers, $rows);
  }

  /**
   * Helper function to provide data for contact import for Household.
   * Duplicate Matching.
   *
   * @param $householdFields
   *
   * @return array
   */
  public function _householdDuplicateMatchingCSVData($householdFields) {
    $headers = array(
      'household_name' => 'Household Name',
      'email' => 'Email',
      'phone' => 'Phone',
      'address_1' => 'Additional Address 1',
      'address_2' => 'Additional Address 2',
      'city' => 'City',
      'state' => 'State',
      'country' => 'Country',
    );

    $rows = array(
      array(
        'household_name' => isset($householdFields['household_name']) ? $householdFields['household_name'] : 'household_' . substr(sha1(rand()), 0, 7),
        'email' => isset($householdFields['email']) ? $householdFields['email'] : substr(sha1(rand()), 0, 7) . '@example.com',
        'phone' => '3949912154',
        'address_1' => 'Add 1',
        'address_2' => 'Add 2',
        'city' => 'Watson',
        'state' => 'NY',
        'country' => 'UNITED STATES',
      ),
      array(
        'household_name' => 'household_' . substr(sha1(rand()), 0, 7),
        'email' => substr(sha1(rand()), 0, 7) . '@example.org',
        'phone' => '5949412154',
        'address_1' => 'Add 1',
        'address_2' => 'Add 2',
        'city' => 'Watson',
        'state' => 'NY',
        'country' => 'UNITED STATES',
      ),
    );

    return array($headers, $rows);
  }

}

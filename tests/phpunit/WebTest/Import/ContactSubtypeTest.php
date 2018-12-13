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
 * Class WebTest_Import_ContactSubtypeTest
 */
class WebTest_Import_ContactSubtypeTest extends ImportCiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   *  Test contact import for Individuals Subtype.
   */
  public function testIndividualSubtypeImport() {
    $this->webtestLogin();

    // Get sample import data.
    list($headers, $rows) = $this->_individualSubtypeCSVData();

    // Import and check Individual contacts in Skip mode with contact type Parent.
    $other = array('contactSubtype' => 'Parent');

    $this->importContacts($headers, $rows, 'Individual', 'Skip', array(), $other);

    // Get imported contact Ids
    $importedContactIds = $this->_getImportedContactIds($rows);

    // Build update mode import headers
    $updateHeaders = array(
      'contact_id' => 'Internal Contact ID',
      'first_name' => 'First Name',
      'last_name' => 'Last Name',
    );

    // Create update mode import rows
    $updateRows = array();
    foreach ($importedContactIds as $cid) {
      $updateRows[$cid] = array(
        'contact_id' => $cid,
        'first_name' => substr(sha1(rand()), 0, 7),
        'last_name' => 'Anderson' . substr(sha1(rand()), 0, 7),
      );
    }

    // Import and check Individual contacts in Update mode with contact type Parent.
    $this->importContacts($updateHeaders, $updateRows, 'Individual', 'Update', array(), $other);

    // Visit contacts to check updated data.
    foreach ($updateRows as $updatedRow) {
      $this->openCiviPage("contact/view", "reset=1&cid={$updatedRow['contact_id']}");
      $displayName = "{$updatedRow['first_name']} {$updatedRow['last_name']}";
      $this->assertTrue($this->isTextPresent("$displayName"), "Contact did not update!");
    }

    // Headers that should not updated.
    $fillHeaders = $updateHeaders;

    // Headers that should fill.
    $fillHeaders['gender'] = 'Gender';
    $fillHeaders['dob'] = 'Birth Date';

    $fillRows = array();
    foreach ($importedContactIds as $cid) {
      $fillRows[$cid] = array(
        'contact_id' => $cid,
        // should not update
        'first_name' => substr(sha1(rand()), 0, 7),
        // should not update
        'last_name' => 'Anderson' . substr(sha1(rand()), 0, 7),
        'gender' => 'Male',
        'dob' => '1986-04-16',
      );
    }

    // Import and check Individual contacts in Update mode with contact type Parent.
    $this->importContacts($fillHeaders, $fillRows, 'Individual', 'Fill', array(), $other);

    // Visit contacts to check filled data.
    foreach ($fillRows as $cid => $fillRow) {
      $this->openCiviPage("contact/view", "reset=1&cid={$fillRow['contact_id']}");

      // Check old display name.
      $displayName = "{$updateRows[$cid]['first_name']} {$updateRows[$cid]['last_name']}";
      $this->assertTrue($this->isTextPresent("$displayName"), "Contact should not update in fill mode!");

      $this->verifyText("css=div#contact-summary div.crm-contact-gender_display", preg_quote($fillRow['gender']));
    }

    // Recreate same conacts using 'No Duplicate Checking' with contact type Parent.
    $this->importContacts($headers, $rows, 'Individual', 'No Duplicate Checking', array(), $other);
  }

  /**
   *  Test contact import for Organization Subtype.
   */
  public function testOrganizationSubtypeImport() {
    $this->webtestLogin();

    // Get sample import data.
    list($headers, $rows) = $this->_organizationSubtypeCSVData();

    // Import and check Organization contacts
    $other = array('contactSubtype' => 'Team');

    $this->importContacts($headers, $rows, 'Organization', 'Skip', array(), $other);

    // Get imported contact Ids
    $importedContactIds = $this->_getImportedContactIds($rows, 'Organization');

    // Build update mode import headers
    $updateHeaders = array(
      'contact_id' => 'Internal Contact ID',
      'organization_name' => 'Organization Name',
    );

    // Create update mode import rows
    $updateRows = array();
    foreach ($importedContactIds as $cid) {
      $updateRows[$cid] = array(
        'contact_id' => $cid,
        'organization_name' => 'UpdatedOrg ' . substr(sha1(rand()), 0, 7),
      );
    }

    // Import and check Individual contacts in Update mode with contact type Team.
    $this->importContacts($updateHeaders, $updateRows, 'Organization', 'Update', array(), $other);

    // Visit contacts to check updated data.
    foreach ($updateRows as $updatedRow) {
      $organizationName = $updatedRow['organization_name'];
      $this->openCiviPage("contact/view", "reset=1&cid={$updatedRow['contact_id']}");

      $this->assertTrue($this->isTextPresent("$organizationName"), "Contact did not update!");
    }

    // Headers that should not updated.
    $fillHeaders = $updateHeaders;

    // Headers that should fill.
    $fillHeaders['legal_name'] = 'Legal Name';

    $fillRows = array();
    foreach ($importedContactIds as $cid) {
      $fillRows[$cid] = array(
        'contact_id' => $cid,
        // should not update
        'organization_name' => 'UpdateOrg ' . substr(sha1(rand()), 0, 7),
        'legal_name' => 'org ' . substr(sha1(rand()), 0, 7),
      );
    }

    // Import and check Individual contacts in Update mode with contact type Team.
    $this->importContacts($fillHeaders, $fillRows, 'Organization', 'Fill', array(), $other);

    // Visit contacts to check filled data.
    foreach ($fillRows as $cid => $fillRow) {
      $this->openCiviPage("contact/view", "reset=1&cid={$fillRow['contact_id']}");

      // Check old Organization name.
      $organizationName = $updateRows[$cid]['organization_name'];
      $this->assertTrue($this->isTextPresent("$organizationName"), "Contact should not update in fill mode!");
      $this->verifyText("xpath=//div[@id='crm-contactinfo-content']/div/div[3]/div[2]", preg_quote($fillRow['legal_name']));
    }

    // Recreate same conacts using 'No Duplicate Checking' with contact type Team.
    $this->importContacts($headers, $rows, 'Organization', 'No Duplicate Checking', array(), $other);
  }

  /**
   *  Test contact import for Household Subtype.
   */
  public function testHouseholdSubtypeImport() {
    $this->webtestLogin();

    // Create Household Subtype
    $householdSubtype = $this->_createHouseholdSubtype();

    // Get sample import data.
    list($headers, $rows) = $this->_householdSubtypeCSVData();

    // Import and check Organization contacts
    $other = array('contactSubtype' => $householdSubtype);

    $this->importContacts($headers, $rows, 'Household', 'Skip', array(), $other);

    // Get imported contact Ids
    $importedContactIds = $this->_getImportedContactIds($rows, 'Household');

    // Build update mode import headers
    $updateHeaders = array(
      'contact_id' => 'Internal Contact ID',
      'household_name' => 'Household Name',
    );

    // Create update mode import rows
    $updateRows = array();
    foreach ($importedContactIds as $cid) {
      $updateRows[$cid] = array(
        'contact_id' => $cid,
        'household_name' => 'UpdatedHousehold ' . substr(sha1(rand()), 0, 7),
      );
    }

    // Import and check Individual contacts in Update mode.
    $this->importContacts($updateHeaders, $updateRows, 'Household', 'Update', array(), $other);

    // Visit contacts to check updated data.
    foreach ($updateRows as $updatedRow) {
      $householdName = $updatedRow['household_name'];
      $this->openCiviPage("contact/view", "reset=1&cid={$updatedRow['contact_id']}");

      $this->assertTrue($this->isTextPresent("$householdName"), "Contact did not update!");
    }

    // Headers that should not updated.
    $fillHeaders = $updateHeaders;

    // Headers that should fill.
    $fillHeaders['nick_name'] = 'Nick Name';

    $fillRows = array();
    foreach ($importedContactIds as $cid) {
      $fillRows[$cid] = array(
        'contact_id' => $cid,
        // should not update
        'household_name' => 'UpdatedHousehold ' . substr(sha1(rand()), 0, 7),
        'nick_name' => 'Household ' . substr(sha1(rand()), 0, 7),
      );
    }

    // Import and check Individual contacts in Update mode.
    $this->importContacts($fillHeaders, $fillRows, 'Household', 'Fill', array(), $other);

    // Visit contacts to check filled data.
    foreach ($fillRows as $cid => $fillRow) {
      $this->openCiviPage("contact/view", "reset=1&cid={$fillRow['contact_id']}");

      // Check old Household name.
      $householdName = $updateRows[$cid]['household_name'];
      $this->assertTrue($this->isTextPresent("$householdName"), "Contact should not update in fill mode!");
      $this->verifyText("xpath=//div[@id='crm-contactinfo-content']/div/div[2]/div[2]", preg_quote($fillRow['nick_name']));
    }

    // Recreate same conacts using 'No Duplicate Checking'
    $this->importContacts($headers, $rows, 'Household', 'No Duplicate Checking', array(), $other);
  }

  /*
   *  Helper function to create Household Subtype.
   */
  /**
   * @return string
   */
  public function _createHouseholdSubtype() {

    // Visit to create contact subtype
    $this->openCiviPage("admin/options/subtype", "action=add&reset=1");

    // Create Household subtype
    $householdSubtype = substr(sha1(rand()), 0, 7);
    $this->type("label", $householdSubtype);
    $this->select("parent_id", "label=Household");
    $this->click("_qf_ContactType_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    return $householdSubtype;
  }

  /*
   *  Helper function to provide data for contact import for Individuals Subtype.
   */
  /**
   * @return array
   */
  public function _individualSubtypeCSVData() {
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
        'first_name' => substr(sha1(rand()), 0, 7),
        'middle_name' => substr(sha1(rand()), 0, 7),
        'last_name' => 'Anderson',
        'email' => substr(sha1(rand()), 0, 7) . '@example.com',
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
   *  Helper function to provide data for contact import for Organizations Subtype.
   */
  /**
   * @return array
   */
  public function _organizationSubtypeCSVData() {
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
        'organization_name' => 'org_' . substr(sha1(rand()), 0, 7),
        'email' => substr(sha1(rand()), 0, 7) . '@example.org',
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

  /*
   *  Helper function to provide data for contact import for Household Subtype.
   */
  /**
   * @return array
   */
  public function _householdSubtypeCSVData() {
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
        'household_name' => 'household_' . substr(sha1(rand()), 0, 7),
        'email' => substr(sha1(rand()), 0, 7) . '@example.org',
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

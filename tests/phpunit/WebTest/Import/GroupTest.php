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
 * Class WebTest_Import_GroupTest
 */
class WebTest_Import_GroupTest extends ImportCiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   *  Test contact import for Individuals.
   */
  public function testIndividualImportWithGroup() {
    $this->webtestLogin();

    // Get sample import data.
    list($headers, $rows) = $this->_individualGroupCSVData();

    // Group Name
    $groupName = substr(sha1(rand()), 0, 7);

    // Import and check Individual Contacts in Skip mode and Add them in Group
    $other = array(
      'createGroup' => TRUE,
      'createGroupName' => $groupName,
    );

    // Create New Group And Import Contacts In Group
    $this->importContacts($headers, $rows, 'Individual', 'Skip', array(), $other);

    $count = count($rows);

    // Direct URL To Search
    $this->openCiviPage("contact/search", "reset=1");

    // Select GroupName
    $this->select("group", "label={$groupName}");

    $this->clickLink("_qf_Basic_refresh");

    // To Check Number Of Imported Contacts
    $this->assertTrue($this->isTextPresent("{$count} Contacts"), "Contacts Not Found");

    // To Add New Contacts In Already Existing Group
    $other = array('selectGroup' => $groupName);

    // Create New Individual Record
    list($headers, $rows) = $this->_individualGroupCSVData();

    // Import Contacts In Existing Group
    $this->importContacts($headers, $rows, 'Individual', 'Skip', array(), $other);
    $count += count($rows);

    // Direct URL To Search
    $this->openCiviPage("contact/search", "reset=1");

    // Select GroupName
    $this->select("group", "label={$groupName}");

    $this->clickLink("_qf_Basic_refresh");

    // To Check Imported Contacts
    $this->assertTrue($this->isTextPresent("{$count} Contacts"), "Contacts Not Found");
  }

  /**
   * Helper function to provide data for contact import for Individuals.
   *
   * @return array
   */
  public function _individualGroupCSVData() {
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

}

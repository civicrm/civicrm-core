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
 * Class WebTest_Import_SavedMapping
 */
class WebTest_Import_SavedMappingTest extends ImportCiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test Saved Import Mapping for Individuals.
   */
  public function testSaveIndividualMapping() {

    // Logging in.
    $this->webtestLogin();

    // Get sample import data.
    list($headers, $rows) = $this->_individualCSVData();

    // Create New Mapping Name
    $mappingName = 'contactimport_' . substr(sha1(rand()), 0, 7);

    $other = array(
      'saveMapping' => TRUE,
      'saveMappingName' => $mappingName,
    );

    // Map Fields
    $fieldMapper = array(
      'mapper[0][0]' => 'prefix_id',
      'mapper[4][0]' => 'suffix_id',
      'mapper[6][0]' => 'phone',
      'mapper[6][1]' => '5',
      'mapper[7][0]' => 'supplemental_address_1',
      'mapper[7][1]' => '5',
      'mapper[8][0]' => 'supplemental_address_2',
      'mapper[8][1]' => '5',
      'mapper[9][0]' => 'supplemental_address_3',
      'mapper[9][1]' => '5',
      'mapper[10][0]' => 'city',
      'mapper[10][1]' => '5',
      'mapper[11][0]' => 'state_province',
      'mapper[11][1]' => '5',
      'mapper[12][0]' => 'country',
      'mapper[12][1]' => '5',
    );

    // Import and check Individual contacts in Skip mode.
    $this->importContacts($headers, $rows, 'Individual', 'Skip', $fieldMapper, $other);

    list($headers, $rows) = $this->_individualCSVData();

    // Sending Mapped Name for Re-use
    $other = array('useMappingName' => $mappingName);
    $this->importContacts($headers, $rows, 'Individual', 'Skip', array(), $other);
  }

  /**
   * Helper function to provide csv data for Individuals contact import.
   *
   * @return array
   */
  public function _individualCSVData() {
    $headers = array(
      'individual_prefix' => 'Individual Prefix',
      'first_name' => 'First Name',
      'middle_name' => 'Middle Name',
      'last_name' => 'Last Name',
      'individual_suffix' => 'Individual Suffix',
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
        'individual_prefix' => 'Mr.',
        'first_name' => substr(sha1(rand()), 0, 7),
        'middle_name' => substr(sha1(rand()), 0, 7),
        'last_name' => 'Anderson',
        'individual_suffix' => 'Jr.',
        'email' => substr(sha1(rand()), 0, 7) . '@example.com',
        'phone' => '6949912154',
        'address_1' => 'Add 1',
        'address_2' => 'Add 2',
        'city' => 'Watson',
        'state' => 'NY',
        'country' => 'UNITED STATES',
      ),
      array(
        'individual_prefix' => 'Mr.',
        'first_name' => substr(sha1(rand()), 0, 7),
        'middle_name' => substr(sha1(rand()), 0, 7),
        'last_name' => 'Summerson',
        'individual_suffix' => 'Jr.',
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

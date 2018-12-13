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
 * Class WebTest_Import_TagTest
 */
class WebTest_Import_TagTest extends ImportCiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test contact import for Individuals.
   */
  public function testContactImportWithTag() {
    $this->webtestLogin();

    // Get sample import data.
    list($headers, $rows) = $this->_contactTagCSVData();

    // Creating a new Tag
    $tagName = 'tag_' . substr(sha1(rand()), 0, 7);

    // Import and check Individual contacts in Skip mode.
    $other = array(
      'createTag' => TRUE,
      'createTagName' => $tagName,
    );

    $this->importContacts($headers, $rows, 'Individual', 'Skip', array(), $other);

    $this->openCiviPage("contact/search", "reset=1");
    $this->select('tag', "label={$tagName}");
    // click to search
    $this->click('_qf_Basic_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // count rows
    $countContacts = count($rows);
    // Is status message correct?
    $this->assertTrue($this->isTextPresent("{$countContacts} Contacts"));

    // Get sample import data.
    list($headers, $rows) = $this->_contactTagCSVData();

    // Import and check Individual contacts in Skip mode.
    // Sending Tag Name For Re-use
    $other = array('selectTag' => array($tagName));

    $this->importContacts($headers, $rows, 'Individual', 'Skip', array(), $other);

    $this->openCiviPage("contact/search", "reset=1");

    $this->select('tag', "label={$tagName}");
    // click to search
    $this->click('_qf_Basic_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Counting Contact rows old rows + new rows
    $countContacts += count($rows);
    // Is status message correct?
    $this->assertTrue($this->isTextPresent("{$countContacts} Contacts"));
  }

  /**
   * Helper function to provide data for contact import for sample.
   *
   * @return array
   */
  public function _contactTagCSVData() {
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

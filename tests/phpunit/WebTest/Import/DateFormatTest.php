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
 * Class WebTest_Import_DateFormatTest
 */
class WebTest_Import_DateFormatTest extends ImportCiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   *  Test contact import for yyyy_mm_dd date format.
   */
  public function testDateFormat_yyyy_mm_dd() {
    $this->webtestLogin();

    // Get sample import data.
    list($headers, $rows) = $this->_individualCSVData_yyyy_mm_dd();

    // Import and check Individual contacts in Skip mode and yyyy-mm-dd OR yyyymmdd dateformat.
    $other = array('dateFormat' => 'yyyy-mm-dd OR yyyymmdd');

    $this->importContacts($headers, $rows, 'Individual', 'Skip', array(), $other);
  }

  /**
   *  Test contact import for mm_dd_yy date format.
   */
  public function testDateFormat_mm_dd_yy() {
    $this->webtestLogin();

    // Get sample import data.
    list($headers, $rows) = $this->_individualCSVData_mm_dd_yy();

    // Import and check Individual contacts in Skip mode and
    // mm/dd/yy OR mm-dd-yy date format.
    $other = array('dateFormat' => 'mm/dd/yy OR mm-dd-yy');

    $this->importContacts($headers, $rows, 'Individual', 'Skip', array(), $other);
  }

  /**
   *  Test contact import for mm_dd_yyyy date format.
   */
  public function testDateFormat_mm_dd_yyyy() {
    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded
    $this->webtestLogin();

    // Get sample import data.
    list($headers, $rows) = $this->_individualCSVData_mm_dd_yyyy();

    // Import and check Individual contacts in Skip mode and
    // mm/dd/yyyy OR mm-dd-yyyy date format.
    $other = array('dateFormat' => 'mm/dd/yyyy OR mm-dd-yyyy');

    $this->importContacts($headers, $rows, 'Individual', 'Skip', array(), $other);
  }

  /**
   *  Test contact import for Month_dd_yyyy date format.
   */
  public function testDateFormat_Month_dd_yyyy() {
    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded
    $this->webtestLogin();

    // Get sample import data.
    list($headers, $rows) = $this->_individualCSVData_Month_dd_yyyy();

    // Import and check Individual contacts in Skip mode and
    // Month dd, yyyy date format.
    $other = array('dateFormat' => 'Month dd, yyyy');

    $this->importContacts($headers, $rows, 'Individual', 'Skip', array(), $other);
  }

  /**
   *  Test contact import for dd_mon_yy date format.
   */
  public function testDateFormat_dd_mon_yy() {
    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded
    $this->webtestLogin();

    // Get sample import data.
    list($headers, $rows) = $this->_individualCSVData_dd_mon_yy();

    // Import and check Individual contacts in Skip mode and
    // dd-mon-yy OR dd/mm/yy date format.
    $other = array('dateFormat' => 'dd-mon-yy OR dd/mm/yy');

    $this->importContacts($headers, $rows, 'Individual', 'Skip', array(), $other);
  }

  /**
   *  Test contact import for dd_mm_yyyy date format.
   */
  public function testDateFormat_dd_mm_yyyy() {
    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded
    $this->webtestLogin();

    // Get sample import data.
    list($headers, $rows) = $this->_individualCSVData_dd_mm_yyyy();

    // Import and check Individual contacts in Skip mode and
    // dd/mm/yyyy date format.
    $other = array('dateFormat' => 'dd/mm/yyyy');

    $this->importContacts($headers, $rows, 'Individual', 'Skip', array(), $other);
  }

  /**
   *  Helper function to provide data for contact import for Individuals and yyyy-mm-dd OR yyyymmdd dateformat.
   */
  /**
   * @return array
   */
  public function _individualCSVData_yyyy_mm_dd() {
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
      'birth_date' => 'Birth Date',
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
        'birth_date' => '1998-12-25',
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
        'birth_date' => '1999-11-26',
      ),
    );

    return array($headers, $rows);
  }

  /**
   *  Helper function to provide data for contact import for Individuals and mm/dd/yy OR mm-dd-yy dateformat.
   */
  /**
   * @return array
   */
  public function _individualCSVData_mm_dd_yy() {
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
      'birth_date' => 'Birth Date',
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
        'birth_date' => '12/23/98',
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
        'birth_date' => '11/24/88',
      ),
    );

    return array($headers, $rows);
  }

  /**
   *  Helper function to provide data for contact import for Individuals and mm/dd/yyyy OR mm-dd-yyyy dateformat.
   */
  /**
   * @return array
   */
  public function _individualCSVData_mm_dd_yyyy() {
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
      'birth_date' => 'Birth Date',
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
        'birth_date' => '11/12/1995',
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
        'birth_date' => '12/12/1995',
      ),
    );

    return array($headers, $rows);
  }

  /**
   *  Helper function to provide data for contact import for Individuals and Month dd, yyyy dateformat.
   */
  /**
   * @return array
   */
  public function _individualCSVData_Month_dd_yyyy() {
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
      'birth_date' => 'Birth Date',
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
        'birth_date' => 'December 12, 1998',
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
        'birth_date' => 'December 13, 1998',
      ),
    );

    return array($headers, $rows);
  }

  /**
   *  Helper function to provide data for contact import for Individuals and dd-mon-yy OR dd/mm/yy dateformat.
   */
  /**
   * @return array
   */
  public function _individualCSVData_dd_mon_yy() {
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
      'birth_date' => 'Birth Date',
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
        'birth_date' => '25/12/98',
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
        'birth_date' => '26/12/99',
      ),
    );

    return array($headers, $rows);
  }

  /**
   *  Helper function to provide data for contact import for Individuals and dd/mm/yyyy dateformat.
   */
  /**
   * @return array
   */
  public function _individualCSVData_dd_mm_yyyy() {
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
      'birth_date' => 'Birth Date',
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
        'birth_date' => '25/12/1998',
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
        'birth_date' => '24/11/1996',
      ),
    );

    return array($headers, $rows);
  }

}

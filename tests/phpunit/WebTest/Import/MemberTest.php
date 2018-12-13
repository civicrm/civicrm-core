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
 * Class WebTest_Import_MemberTest
 */
class WebTest_Import_MemberTest extends ImportCiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   *  Test participant import for Individuals.
   */
  public function testMemberImportIndividual() {

    $this->webtestLogin();

    // Get membership import data for Individuals.
    list($headers, $rows, $fieldMapper) = $this->_memberIndividualCSVData();

    // Import participants and check imported data.
    $this->importCSVComponent('Membership', $headers, $rows, 'Individual', 'Skip', $fieldMapper);
  }

  /**
   *  Test participant import for Households.
   */
  public function testMemberImportHousehold() {

    $this->webtestLogin();

    // Get membership import data for Households.
    list($headers, $rows, $fieldMapper) = $this->_memberHouseholdCSVData();

    // Import participants and check imported data.
    $this->importCSVComponent('Membership', $headers, $rows, 'Household', 'Skip', $fieldMapper);
  }

  /**
   *  Test participant import for Organizations.
   */
  public function testMemberImportOrganization() {

    $this->webtestLogin();

    // Get membership import data for Organizations.
    list($headers, $rows, $fieldMapper) = $this->_memberOrganizationCSVData();

    // Import participants and check imported data.
    $this->importCSVComponent('Membership', $headers, $rows, 'Organization', 'Skip', $fieldMapper);
  }

  /**
   * Helper function to provide data for Membeship import for Individuals.
   *
   * @return array
   */
  public function _memberIndividualCSVData() {
    $memTypeParams = $this->webtestAddMembershipType();

    $firstName1 = substr(sha1(rand()), 0, 7);
    $email1 = 'mail_' . substr(sha1(rand()), 0, 7) . '@example.com';
    $this->webtestAddContact($firstName1, 'Anderson', $email1);
    $startDate1 = date('Y-m-d');

    $firstName2 = substr(sha1(rand()), 0, 7);
    $email2 = 'mail_' . substr(sha1(rand()), 0, 7) . '@example.com';
    $this->webtestAddContact($firstName2, 'Anderson', $email2);
    $year = date('Y') - 1;
    $startDate2 = date('Y-m-d', mktime(0, 0, 0, 9, 10, $year));

    $headers = array(
      'email' => 'Email',
      'membership_type_id' => 'Membership Type',
      'membership_start_date' => 'Membership Start Date',
    );

    $rows = array(
      array(
        'email' => $email1,
        'membership_type_id' => $memTypeParams['membership_type'],
        'membership_start_date' => $startDate1,
      ),
      array(
        'email' => $email2,
        'membership_type_id' => $memTypeParams['membership_type'],
        'membership_start_date' => $startDate2,
      ),
    );

    $fieldMapper = array(
      'mapper[0][0]' => 'email',
      'mapper[1][0]' => 'membership_type_id',
      'mapper[2][0]' => 'membership_start_date',
    );
    return array($headers, $rows, $fieldMapper);
  }

  /**
   * Helper function to provide data for Membeship import for Households.
   *
   * @return array
   */
  public function _memberHouseholdCSVData() {
    $memTypeParams = $this->webtestAddMembershipType();

    $household1 = substr(sha1(rand()), 0, 7) . ' home';
    $this->webtestAddHousehold($household1, TRUE);
    $startDate1 = date('Y-m-d');

    $household2 = substr(sha1(rand()), 0, 7) . ' home';
    $this->webtestAddHousehold($household2, TRUE);
    $year = date('Y') - 1;
    $startDate2 = date('Y-m-d', mktime(0, 0, 0, 12, 31, $year));

    $headers = array(
      'household_name' => 'Household Name',
      'membership_type_id' => 'Membership Type',
      'membership_start_date' => 'Membership Start Date',
    );

    $rows = array(
      array(
        'household_name' => $household1,
        'membership_type_id' => $memTypeParams['membership_type'],
        'membership_start_date' => $startDate1,
      ),
      array(
        'household_name' => $household2,
        'membership_type_id' => $memTypeParams['membership_type'],
        'membership_start_date' => $startDate2,
      ),
    );

    $fieldMapper = array(
      'mapper[0][0]' => 'household_name',
      'mapper[1][0]' => 'membership_type_id',
      'mapper[2][0]' => 'membership_start_date',
    );
    return array($headers, $rows, $fieldMapper);
  }

  /**
   * Helper function to provide data for Membeship import for Organizations.
   *
   * @return array
   */
  public function _memberOrganizationCSVData() {
    $memTypeParams = $this->webtestAddMembershipType();

    $organization1 = substr(sha1(rand()), 0, 7) . ' org';
    $this->webtestAddOrganization($organization1, TRUE);
    $startDate1 = date('Y-m-d');

    $organization2 = substr(sha1(rand()), 0, 7) . ' org';
    $this->webtestAddOrganization($organization2, TRUE);
    $year = date('Y') - 1;
    $startDate2 = date('Y-m-d', mktime(0, 0, 0, 12, 31, $year));

    $headers = array(
      'organization_name' => 'Organization Name',
      'membership_type_id' => 'Membership Type',
      'membership_start_date' => 'Membership Start Date',
    );

    $rows = array(
      array(
        'organization_name' => $organization1,
        'membership_type_id' => $memTypeParams['membership_type'],
        'membership_start_date' => $startDate1,
      ),
      array(
        'organization_name' => $organization2,
        'membership_type_id' => $memTypeParams['membership_type'],
        'membership_start_date' => $startDate2,
      ),
    );

    $fieldMapper = array(
      'mapper[0][0]' => 'organization_name',
      'mapper[1][0]' => 'membership_type_id',
      'mapper[2][0]' => 'membership_start_date',
    );
    return array($headers, $rows, $fieldMapper);
  }

}

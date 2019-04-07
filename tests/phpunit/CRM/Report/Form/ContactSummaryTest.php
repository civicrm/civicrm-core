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
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *  Test Contact Summary report outcome
 *
 * @package CiviCRM
 */
class CRM_Report_Form_ContactSummaryTest extends CiviReportTestCase {
  protected $_tablesToTruncate = array(
    'civicrm_contact',
    'civicrm_email',
    'civicrm_phone',
    'civicrm_address',
  );

  public function setUp() {
    parent::setUp();
    $this->quickCleanup($this->_tablesToTruncate);
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Ensure the new Odd/Event street number sort column works correctly
   */
  public function testOddEvenStreetNumber() {
    // Create 5 contacts where:
    //  Contact A - Odd Street number - 3
    //  Contact B - Odd Street number - 5
    //  Contact C - Even Street number - 2
    //  Contact D - Even Street number - 4
    //  Contact E - No Street number
    $contactIDs = [
      'odd_street_number_1' => $this->individualCreate([
        'api.Address.create' => [
          'location_type_id' => 1,
          'is_primary' => 1,
          'street_number' => 3,
        ],
      ]),
      'odd_street_number_2' => $this->individualCreate([
        'api.Address.create' => [
          'location_type_id' => 1,
          'is_primary' => 1,
          'street_number' => 5,
        ],
      ]),
      'even_street_number_1' => $this->individualCreate([
        'api.Address.create' => [
          'location_type_id' => 1,
          'is_primary' => 1,
          'street_number' => 2,
        ],
      ]),
      'even_street_number_2' => $this->individualCreate([
        'api.Address.create' => [
          'location_type_id' => 1,
          'is_primary' => 1,
          'street_number' => 4,
        ],
      ]),
      'no_street_number' => $this->individualCreate(),
    ];

    $input = [
      'fields' => [
        'address_street_number',
        'address_odd_street_number',
      ],
    ];
    $obj = $this->getReportObject('CRM_Report_Form_Contact_Summary', $input);

    $expectedCases = [
      // CASE A: Sorting by odd street number in desc order + street number in desc order
      [
        'order_bys' => [
          [
            'column' => 'address_odd_street_number',
            'order' => 'DESC',
          ],
          [
            'column' => 'address_street_number',
            'order' => 'DESC',
          ],
        ],
        'expected_contact_ids' => [
          $contactIDs['odd_street_number_2'],
          $contactIDs['odd_street_number_1'],
          $contactIDs['even_street_number_2'],
          $contactIDs['even_street_number_1'],
          $contactIDs['no_street_number'],
        ],
        'expected_orderby_clause' => 'ORDER BY (address_civireport.street_number % 2) DESC, address_civireport.street_number DESC',
      ],
      // CASE B: Sorting by odd street number in asc order + street number in desc order
      [
        'order_bys' => [
          [
            'column' => 'address_odd_street_number',
            'order' => 'ASC',
          ],
          [
            'column' => 'address_street_number',
            'order' => 'DESC',
          ],
        ],
        'expected_contact_ids' => [
          $contactIDs['no_street_number'],
          $contactIDs['even_street_number_2'],
          $contactIDs['even_street_number_1'],
          $contactIDs['odd_street_number_2'],
          $contactIDs['odd_street_number_1'],
        ],
        'expected_orderby_clause' => 'ORDER BY (address_civireport.street_number % 2) ASC, address_civireport.street_number DESC',
      ],
      // CASE C: Sorting by odd street number in desc order + street number in asc order
      [
        'order_bys' => [
          [
            'column' => 'address_odd_street_number',
            'order' => 'DESC',
          ],
          [
            'column' => 'address_street_number',
            'order' => 'ASC',
          ],
        ],
        'expected_contact_ids' => [
          $contactIDs['odd_street_number_1'],
          $contactIDs['odd_street_number_2'],
          $contactIDs['even_street_number_1'],
          $contactIDs['even_street_number_2'],
          $contactIDs['no_street_number'],
        ],
        'expected_orderby_clause' => 'ORDER BY (address_civireport.street_number % 2) DESC, address_civireport.street_number ASC',
      ],
      // CASE A: Sorting by odd street number in asc order + street number in asc order
      [
        'order_bys' => [
          [
            'column' => 'address_odd_street_number',
            'order' => 'ASC',
          ],
          [
            'column' => 'address_street_number',
            'order' => 'ASC',
          ],
        ],
        'expected_contact_ids' => [
          $contactIDs['no_street_number'],
          $contactIDs['even_street_number_1'],
          $contactIDs['even_street_number_2'],
          $contactIDs['odd_street_number_1'],
          $contactIDs['odd_street_number_2'],
        ],
        'expected_orderby_clause' => 'ORDER BY (address_civireport.street_number % 2) ASC, address_civireport.street_number ASC',
      ],
    ];

    foreach ($expectedCases as $case) {
      $obj->setParams(array_merge($obj->getParams(), ['order_bys' => $case['order_bys']]));
      $sql = $obj->buildQuery();
      $rows = CRM_Core_DAO::executeQuery($sql)->fetchAll();

      // check the order of contact IDs
      $this->assertEquals($case['expected_contact_ids'], CRM_Utils_Array::collect('civicrm_contact_id', $rows));
      // check the order clause
      $this->assertEquals(TRUE, !empty(strstr($sql, $case['expected_orderby_clause'])));
    }
  }

}

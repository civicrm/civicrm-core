<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *  Test Contact Summary report outcome
 *
 * @package CiviCRM
 */
class CRM_Report_Form_ContactSummaryTest extends CiviReportTestCase {
  protected $_tablesToTruncate = [
    'civicrm_contact',
    'civicrm_email',
    'civicrm_phone',
    'civicrm_address',
  ];

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
    $customLocationType = $this->callAPISuccess('LocationType', 'create', [
      'name' => 'Custom Location Type',
      'display_name' => 'CiviTest Custom Location Type',
      'is_active' => 1,
    ]);
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
    // Create a non primary address to check that we are only outputting primary contact details.
    $this->callAPISuccess('Address', 'create', [
      'contact_id' => $contactIDs['even_street_number_2'],
      'location_type_id' => $customLocationType['id'],
      'is_primary' => 0,
      'street_number' => 6,
    ]);
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
      // Ensure that we are only fetching primary fields.
      foreach ($rows as $row) {
        if ($row['civicrm_contact_id'] == $contactIDs['even_street_number_2']) {
          $this->assertEquals(4, $row['civicrm_address_address_street_number']);
        }
      }
    }
    $this->callAPISuccess('LocationType', 'Delete', ['id' => $customLocationType['id']]);
  }

  /**
   * Test that Loation Type prints out a sensible piece of data
   */
  public function testLocationTypeIdHandling() {
    $customLocationType = $this->callAPISuccess('LocationType', 'create', [
      'name' => 'Custom Location Type',
      'display_name' => 'CiviTest Custom Location Type',
      'is_active' => 1,
    ]);
    $this->individualCreate([
      'api.Address.create' => [
        'location_type_id' => $customLocationType['id'],
        'is_primary' => 1,
        'street_number' => 3,
      ],
    ]);
    $input = [
      'fields' => [
        'address_street_number',
        'address_odd_street_number',
        'address_location_type_id',
      ],
    ];
    $obj = $this->getReportObject('CRM_Report_Form_Contact_Summary', $input);
    $obj->setParams($obj->getParams());
    $sql = $obj->buildQuery(TRUE);
    $rows = [];
    $obj->buildRows($sql, $rows);
    $obj->formatDisplay($rows);
    $this->assertEquals('CiviTest Custom Location Type', $rows[0]['civicrm_address_address_location_type_id']);
  }

}

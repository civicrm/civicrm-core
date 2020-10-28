<?php

/**
 *  Include dataProvider for tests
 * @group headless
 */
class CRM_Case_BAO_QueryTest extends CiviCaseTestCase {

  /**
   * Check that Qill is calculated correctly.
   *
   * CRM-17120 check the qill is still calculated after changing function used
   * to retrieve function.
   *
   * I could not find anyway to actually do this search with the relevant fields
   * as parameters & don't know if they exist as legitimate code or code cruft so
   * this test was the only way I could verify the change.
   *  - case_activity_type
   *  - case_activity_status_id
   *  - case_activity_medium_id
   */
  public function testWhereClauseSingle() {
    $params = [
      0 => [
        0 => 'case_activity_type',
        1 => '=',
        2 => 6,
        3 => 1,
        4 => 0,
      ],
      1 => [
        0 => 'case_activity_status_id',
        1 => '=',
        2 => 1,
        3 => 1,
        4 => 0,
      ],
      2 => [
        0 => 'case_activity_medium_id',
        1 => '=',
        2 => 1,
        3 => 1,
        4 => 0,
      ],
    ];

    $queryObj = new CRM_Contact_BAO_Query($params, NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CASE);
    $this->assertEquals(
      [
        0 => 'Activity Type = Contribution',
        1 => 'Activity Status = Scheduled',
        2 => 'Activity Medium = In Person',
      ],
      $queryObj->_qill[1]
    );
  }

  /**
   * Test the qill for a find cases search.
   */
  public function testFindCasesQuery() {
    $params = [
      [
        0 => 'case_type_id',
        1 => 'IN',
        2 => [1],
        3 => 0,
        4 => 0,
      ],
      [
        0 => 'case_status_id',
        1 => 'IN',
        2 => [1],
        3 => 0,
        4 => 0,
      ],
      [
        0 => 'case_deleted',
        1 => '=',
        2 => 0,
        3 => 0,
        4 => 0,
      ],
      [
        0 => 'case_owner',
        1 => '=',
        2 => 1,
        3 => 0,
        4 => 0,
      ],
    ];

    $query = new CRM_Contact_BAO_Query($params, NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CASE);

    $this->assertEquals(
      [
        0 => [
          0 => 'Case Type(s) In Housing Support',
          1 => 'Case Status(s) In Ongoing',
          2 => 'Case = All Cases',
        ],
      ],
      $query->_qill
    );

    $this->assertEquals(
      [
        0 => [
          0 => 'civicrm_case.case_type_id IN ("1")',
          1 => 'civicrm_case.status_id IN ("1")',
          2 => 'civicrm_case.is_deleted = 0',
          3 => 'civicrm_case_contact.contact_id = contact_a.id',
        ],
      ],
      $query->_where
    );
  }

}

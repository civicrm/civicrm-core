<?php

/**
 *  Include dataProvider for tests
 * @group headless
 */
class CRM_Case_BAO_QueryTest extends CiviUnitTestCase {

  /**
   * Set up function.
   *
   * Ensure CiviCase is enabled.
   */
  public function setUp() {
    parent::setUp();
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
  }

  /**
   * Check that Qill is calculated correctly.
   *
   * CRM-17120 check the qill is still calculated after changing function used
   * to retrieve function.
   *
   * Note that the Qill doesn't actually appear to have the correct labels to
   * start with. I didn't attempt to fix that. I just prevented regression.
   *
   * I could not find anyway to actually do this search with the relevant fields
   * as parameters & don't know if they exist as legitimate code or code cruft so
   * this test was the only way I could verify the change.
   *  - case_recent_activity_type
   *  - case_activity_status_id
   *  - case_activity_medium_id
   */
  public function testWhereClauseSingle() {
    $params = array(
      0 => array(
        0 => 'case_recent_activity_type',
        1 => '=',
        2 => 6,
        3 => 1,
        4 => 0,
      ),
      1 => array(
        0 => 'case_activity_status_id',
        1 => '=',
        2 => 1,
        3 => 1,
        4 => 0,
      ),
      2 => array(
        0 => 'case_activity_medium_id',
        1 => '=',
        2 => 1,
        3 => 1,
        4 => 0,
      ),
    );

    $queryObj = new CRM_Contact_BAO_Query($params, NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CASE);
    $this->assertEquals(
      array(
        0 => 'Activity Type = Contribution',
        1 => 'Activity Type = Scheduled',
        2 => 'Activity Medium = In Person',
      ),
      $queryObj->_qill[1]
    );
  }

}

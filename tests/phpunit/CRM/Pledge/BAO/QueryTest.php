<?php

/**
 * @group headless
 */
class CRM_Pledge_BAO_QueryTest extends CiviUnitTestCase {

  /**
   * Amount range fields (pledge_amount_low/_high) share their name suffix
   * with date range fields but must be treated as numbers, not dates.
   *
   * https://lab.civicrm.org/dev/core/-/issues/6752
   */
  public function testPledgeAmountRangeIsNotTreatedAsDate(): void {
    $params = [
      ['pledge_amount_low', '>=', 100, 0, 0],
      ['pledge_amount_high', '<=', 200, 0, 0],
    ];
    $obj = new CRM_Contact_BAO_Query($params, NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_PLEDGE);
    $this->assertEquals([
      "\n( civicrm_pledge.amount >= 100 ) AND\n( civicrm_pledge.amount <= 200 )\n",
    ], $obj->_where[0]);
  }

  /**
   * Genuine date range fields (e.g. pledge_create_date_low/_high) should
   * still be routed through the date query builder.
   */
  public function testPledgeCreateDateRangeIsTreatedAsDate(): void {
    $params = [
      ['pledge_create_date_low', '>=', '20200201000000', 0, 0],
    ];
    $obj = new CRM_Contact_BAO_Query($params, NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_PLEDGE);
    $this->assertEquals(["civicrm_pledge.create_date >= '20200201000000'"], $obj->_where[0]);
  }

}

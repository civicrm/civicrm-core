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
 * Test the Pledge Detail report.
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Report_Form_Pledge_DetailTest extends CiviReportTestCase {

  public function setUp(): void {
    parent::setUp();
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviPledge');
  }

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_pledge', 'civicrm_pledge_payment']);
    parent::tearDown();
  }

  /**
   * The overall pledge count adds up the counts of each currency.
   */
  public function testStatisticsTotalCountAcrossCurrencies(): void {
    $this->pledgeCreate(['currency' => 'USD']);
    $this->pledgeCreate(['currency' => 'USD']);
    $this->pledgeCreate(['currency' => 'EUR']);
    $this->pledgeCreate(['currency' => 'EUR']);
    $this->pledgeCreate(['currency' => 'EUR']);

    $counts = $this->callAPISuccess('ReportTemplate', 'getstatistics', [
      'report_id' => 'pledge/detail',
    ])['values']['counts'];

    $this->assertSame('Total No Pledges', $counts['count2']['title']);
    $this->assertEquals(5, $counts['count2']['value']);
  }

}

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
 * Test for CRM_Financial_Page_Ajax class.
 * @group headless
 */
class CRM_Financial_Page_AjaxBatchSummaryTest extends CiviUnitTestCase {

  /**
   * Test the makeBatchSummary function.
   *
   * We want to ensure changing the method of obtaining status and payment_instrument
   * does not cause any regression.
   */
  public function testMakeBatchSummary() {
    $batch = $this->callAPISuccess('Batch', 'create', ['title' => 'test', 'status_id' => 'Open', 'payment_instrument_id' => 'Cash']);

    $batchID = $batch['id'];
    $params = ['id' => $batchID];
    $makeBatchSummary = CRM_Financial_Page_AJAX::makeBatchSummary($batchID, $params);

    $this->assertEquals('Open', $makeBatchSummary['status']);
    $this->assertEquals('Cash', $makeBatchSummary['payment_instrument']);
  }

}

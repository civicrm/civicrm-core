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
    $batch = $this->callAPISuccess('Batch', 'create', array('title' => 'test', 'status_id' => 'Open', 'payment_instrument_id' => 'Cash'));

    $batchID = $batch['id'];
    $params = array('id' => $batchID);
    $makeBatchSummary = CRM_Financial_Page_AJAX::makeBatchSummary($batchID, $params);

    $this->assertEquals('Open', $makeBatchSummary['status']);
    $this->assertEquals('Cash', $makeBatchSummary['payment_instrument']);
  }

}

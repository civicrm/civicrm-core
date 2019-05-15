<?php
/**
 *  File for the BatchTest class
 *
 *  (PHP 5)
 *
 * @package   CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

/**
 *  Test CRM/Batch/BAO/Batch.php getBatchFinancialItems
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Batch_BAO_BatchTest extends CiviUnitTestCase {

  /**
   * This test checks that a batch search
   * by payment method works.
   * This function could later be expanded to include
   * checks that other types of searches are also
   * working.
   *
   * It creates two contributions, one with payment method credit
   * card and one with payment method check.  After performing a
   * search by payment method for checks, it makes sure that the
   * results are only contributions made by check.
   */
  public function testGetBatchFinancialItems() {

    // create two contributions: one check and one credit card

    $contactId = $this->individualCreate(array('first_name' => 'John', 'last_name' => 'Doe'));
    $this->contributionCreate([
      'contact_id' => $contactId,
      'total_amount' => 1,
      'payment_instrument_id' => 'Check',
      'financial_type_id' => 'Donation',
      'contribution_status_id' => 'Completed',
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'trxn_id' => '22ereerwww322323',
      'id' => NULL,
      'fee_amount' => 0,
      'net_amount' => 1,
      'currency' => 'USD',
      'invoice_id' => '22ed39c9e9ee6ef6031621ce0eafe6da70',
      'skipCleanMoney' => TRUE,
    ]);
    $this->contributionCreate([
      'contact_id' => $contactId,
      'total_amount' => 1,
      'payment_instrument_id' => 'Credit Card',
      'financial_type_id' => 'Member Dues',
      'contribution_status_id' => 'Completed',
      'receive_date' => '20080523000000',
      'receipt_date' => '20080523000000',
      'trxn_id' => '22ereerwww323323',
      'id' => NULL,
      'fee_amount' => 0,
      'net_amount' => 1,
      'currency' => 'USD',
      'invoice_id' => '22ed39c9e9ee6ef6031621ce0eafe6da71',
      'skipCleanMoney' => TRUE,
    ]);

    //create an empty batch to use for the search, and run the search

    $batchParams = array('title' => 'Test Batch');
    $batchParams['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Batch_BAO_Batch', 'status_id', 'Open');
    $batch = CRM_Batch_BAO_Batch::create($batchParams);
    $entityId = $batch->id;
    $returnvalues = array(
      'civicrm_financial_trxn.payment_instrument_id as payment_method',
    );
    $notPresent = TRUE;
    $params['contribution_payment_instrument_id']
      = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check');
    $result = CRM_Batch_BAO_Batch::getBatchFinancialItems($entityId, $returnvalues, $notPresent, $params, TRUE)->fetchAll();
    $this->assertEquals(count($result), 1, 'In line' . __LINE__);
    $this->assertEquals($result[0]['payment_method'], CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check'), 'In line' . __LINE__);
    $params['financial_type_id'] = implode(',', [
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'),
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Member Dues'),
    ]);
    $result = CRM_Batch_BAO_Batch::getBatchFinancialItems($entityId, $returnvalues, $notPresent, $params, TRUE)->fetchAll();
    $this->assertEquals(count($result), 1, 'In line' . __LINE__);
  }

}

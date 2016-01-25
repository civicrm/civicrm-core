<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';


/**
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 */
class api_v3_OrderTest extends CiviUnitTestCase {

  /**
   * Assume empty database with just civicrm_data.
   */
  protected $_individualId;
  protected $_financialTypeId = 1;
  protected $_apiversion;
  public $debug = 0;

  /**
   * Setup function.
   */
  public function setUp() {
    parent::setUp();

    $this->_apiversion = 3;
    $this->_individualId = $this->individualCreate();
  }

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(array('civicrm_uf_match'));
  }

  /**
   * Test Get Payment api.
   */
  public function testGetOrder() {
    $p = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'trxn_id' => 23456,
      'contribution_status_id' => 1,
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $p);

    $params = array(
      'contribution_id' => $contribution['id'],
    );

    $order = $this->callAPIAndDocument('Order', 'get', $params, __FUNCTION__, __FILE__);

    $this->assertEquals(1, $order['count']);
    $expectedResult = array(
      'total_amount' => 100,
      'trxn_id' => 23456,
      'contribution_id' => $contribution['id'],
      'contribution_status' => 'Completed',
      'net_amount' => 100,
    );
    $lineItems[] = array(
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'contribution_id' => $contribution['id'],
      'unit_price' => 100,
      'line_total' => 100,
      'financial_type_id' => 1,
    );
    $this->checkPaymentResult($order, $expectedResult, $lineItems);
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution['id'],
    ));
  }

  /**
   * Function to assert db values
   */
  public function checkPaymentResult($results, $expectedResult, $lineItems = NULL) {
    foreach ($expectedResult as $key => $value) {
      $this->assertEquals($results['values'][$results['id']][$key], $value);
    }

    if ($lineItems && !empty($results['values'][$results['id']]['line_items'])) {
      foreach ($lineItems as $key => $items) {
        foreach ($items as $k => $item) {
          $this->assertEquals($results['values'][$results['id']]['line_items'][$key][$k], $item);
        }
      }
    }
  }

  /**
   * Test cancel order api
   */
  public function testCancelOrder() {
    $p = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'trxn_id' => 23456,
      'contribution_status_id' => 1,
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $p);

    $params = array(
      'contribution_id' => $contribution['id'],
    );

    $this->callAPIAndDocument('order', 'cancel', $params, __FUNCTION__, __FILE__);

    $order = $this->callAPIAndDocument('Order', 'get', $params, __FUNCTION__, __FILE__);
    $expectedResult = array(
      'total_amount' => 100,
      'trxn_id' => 23456,
      'contribution_id' => $contribution['id'],
      'contribution_status' => 'Cancelled',
      'net_amount' => 100,
    );
    $this->checkPaymentResult($order, $expectedResult);

    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution['id'],
    ));
  }

  /**
   * Test delete order api
   */
  public function testDeleteOrder() {
    $p = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'trxn_id' => 23456,
      'contribution_status_id' => 1,
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $p);

    $params = array(
      'contribution_id' => $contribution['id'],
    );

    $this->callAPIAndDocument('order', 'delete', $params, __FUNCTION__, __FILE__);
    $order = $this->callAPIAndDocument('order', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $order['count']);
  }

}

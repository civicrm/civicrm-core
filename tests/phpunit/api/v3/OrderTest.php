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
   * Test Get order api.
   */
  public function testGetOrder() {
    $contribution = $this->addOrder(FALSE, 100);

    $params = array(
      'contribution_id' => $contribution['id'],
    );

    $order = $this->callAPISuccess('Order', 'get', $params);

    $this->assertEquals(1, $order['count']);
    $expectedResult = array(
      $contribution['id'] => array(
        'total_amount' => 100,
        'contribution_id' => $contribution['id'],
        'contribution_status' => 'Completed',
        'net_amount' => 100,
      ),
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
   * Test Get Order api for participant contribution.
   */
  public function testGetOrderParticipant() {
    $contribution = $this->addOrder(FALSE, 100);
    list($items, $contribution) = $this->createParticipantWithContribution();

    $params = array(
      'contribution_id' => $contribution['id'],
    );

    $order = $this->callAPISuccess('Order', 'get', $params);

    $this->assertEquals(2, count($order['values'][$contribution['id']]['line_items']));
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution['id'],
    ));
  }

  /**
   * Function to assert db values
   */
  public function checkPaymentResult($results, $expectedResult, $lineItems = NULL) {
    foreach ($expectedResult[$results['id']] as $key => $value) {
      $this->assertEquals($results['values'][$results['id']][$key], $value);
    }

    if ($lineItems) {
      foreach ($lineItems as $key => $items) {
        foreach ($items as $k => $item) {
          $this->assertEquals($results['values'][$results['id']]['line_items'][$key][$k], $item);
        }
      }
    }
  }

  /**
   * add order
   *
   * @param bool $isPriceSet
   * @param float $amount
   * @param array $extraParams
   *
   * @return array
   */
  public function addOrder($isPriceSet, $amount = 300, $extraParams = array()) {
    $p = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'total_amount' => $amount,
      'financial_type_id' => $this->_financialTypeId,
      'contribution_status_id' => 1,
    );

    if ($isPriceSet) {
      $priceFields = $this->createPriceSet();
      foreach ($priceFields['values'] as $key => $priceField) {
        $lineItems[1][$key] = array(
          'price_field_id' => $priceField['price_field_id'],
          'price_field_value_id' => $priceField['id'],
          'label' => $priceField['label'],
          'field_title' => $priceField['label'],
          'qty' => 1,
          'unit_price' => $priceField['amount'],
          'line_total' => $priceField['amount'],
          'financial_type_id' => $priceField['financial_type_id'],
        );
      }
      $p['line_item'] = $lineItems;
    }
    $p = array_merge($extraParams, $p);
    return $this->callAPISuccess('Contribution', 'create', $p);
  }

  /**
   * Test create order api
   */
  public function testAddOrder() {
    $order = $this->addOrder(FALSE, 100);
    $params = array(
      'contribution_id' => $order['id'],
    );
    $order = $this->callAPISuccess('order', 'get', $params);
    $expectedResult = array(
      $order['id'] => array(
        'total_amount' => 100,
        'contribution_id' => $order['id'],
        'contribution_status' => 'Completed',
        'net_amount' => 100,
      ),
    );
    $lineItems[] = array(
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $order['id'],
      'contribution_id' => $order['id'],
      'unit_price' => 100,
      'line_total' => 100,
      'financial_type_id' => 1,
    );
    $this->checkPaymentResult($order, $expectedResult, $lineItems);
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $order['id'],
    ));
  }

  /**
   * Test create order api for membership
   */
  public function testAddOrderForMembership() {
    require_once 'CiviTest/Membership.php';
    $membership = new Membership();
    $membershipType = $membership->createMembershipType();
    $membershipType1 = $membership->createMembershipType();
    $membershipType = $membershipTypes = array($membershipType->id, $membershipType1->id);
    $p = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'total_amount' => 200,
      'financial_type_id' => $this->_financialTypeId,
      'contribution_status_id' => 1,
    );
    $priceFields = $this->createPriceSet();
    foreach ($priceFields['values'] as $key => $priceField) {
      $lineItems[$key] = array(
        'price_field_id' => $priceField['price_field_id'],
        'price_field_value_id' => $priceField['id'],
        'label' => $priceField['label'],
        'field_title' => $priceField['label'],
        'qty' => 1,
        'unit_price' => $priceField['amount'],
        'line_total' => $priceField['amount'],
        'financial_type_id' => $priceField['financial_type_id'],
        'entity_table' => 'civicrm_membership',
        'membership_type_id' => array_pop($membershipType),
      );
    }
    $p['line_items'][] = array(
      'line_item' => array(array_pop($lineItems)),
      'params' => array(
        'contact_id' => $this->_individualId,
        'membership_type_id' => array_pop($membershipTypes),
        'join_date' => '2006-01-21',
        'start_date' => '2006-01-21',
        'end_date' => '2006-12-21',
        'source' => 'Payment',
        'is_override' => 1,
        'status_id' => 1,
      ),
    );
    $order = $this->callAPISuccess('order', 'create', $p);
    $params = array(
      'contribution_id' => $order['id'],
    );
    $order = $this->callAPISuccess('order', 'get', $params);
    $expectedResult = array(
      $order['id'] => array(
        'total_amount' => 200,
        'contribution_id' => $order['id'],
        'contribution_status' => 'Completed',
        'net_amount' => 200,
      ),
    );
    $this->checkPaymentResult($order, $expectedResult);
    $this->callAPISuccessGetCount('MembershipPayment', $params, 1);
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $order['id'],
    ));
    $p['line_items'][] = array(
      'line_item' => array(array_pop($lineItems)),
      'params' => array(
        'contact_id' => $this->_individualId,
        'membership_type_id' => array_pop($membershipTypes),
        'join_date' => '2006-01-21',
        'start_date' => '2006-01-21',
        'end_date' => '2006-12-21',
        'source' => 'Payment',
        'is_override' => 1,
        'status_id' => 1,
      ),
    );
    $p['total_amount'] = 300;
    $order = $this->callAPISuccess('order', 'create', $p);
    $expectedResult = array(
      $order['id'] => array(
        'total_amount' => 300,
        'contribution_status' => 'Completed',
        'net_amount' => 300,
      ),
    );
    $paymentMembership = array(
      'contribution_id' => $order['id'],
    );
    $order = $this->callAPISuccess('order', 'get', $paymentMembership);
    $this->checkPaymentResult($order, $expectedResult);
    $this->callAPISuccessGetCount('MembershipPayment', $paymentMembership, 2);
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $order['id'],
    ));
  }

  /**
   * Test create order api for participant
   */
  public function testAddOrderForPariticipant() {
    require_once 'CiviTest/Event.php';
    $this->_eventId = Event::create($this->_individualId);
    $p = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'total_amount' => 300,
      'financial_type_id' => $this->_financialTypeId,
      'contribution_status_id' => 1,
    );
    $priceFields = $this->createPriceSet();
    foreach ($priceFields['values'] as $key => $priceField) {
      $lineItems[$key] = array(
        'price_field_id' => $priceField['price_field_id'],
        'price_field_value_id' => $priceField['id'],
        'label' => $priceField['label'],
        'field_title' => $priceField['label'],
        'qty' => 1,
        'unit_price' => $priceField['amount'],
        'line_total' => $priceField['amount'],
        'financial_type_id' => $priceField['financial_type_id'],
        'entity_table' => 'civicrm_participant',
      );
    }
    $p['line_items'][] = array(
      'line_item' => $lineItems,
      'params' => array(
        'contact_id' => $this->_individualId,
        'event_id' => $this->_eventId,
        'status_id' => 1,
        'role_id' => 1,
        'register_date' => '2007-07-21 00:00:00',
        'source' => 'Online Event Registration: API Testing',
      ),
    );
    $order = $this->callAPISuccess('order', 'create', $p);
    $params = array(
      'contribution_id' => $order['id'],
    );
    $order = $this->callAPISuccess('order', 'get', $params);
    $expectedResult = array(
      $order['id'] => array(
        'total_amount' => 300,
        'contribution_id' => $order['id'],
        'contribution_status' => 'Completed',
        'net_amount' => 300,
      ),
    );
    $this->checkPaymentResult($order, $expectedResult);
    $this->callAPISuccessGetCount('ParticipantPayment', $params, 1);
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $order['id'],
    ));
    $p['line_items'][] = array(
      'line_item' => $lineItems,
      'params' => array(
        'contact_id' => $this->individualCreate(),
        'event_id' => $this->_eventId,
        'status_id' => 1,
        'role_id' => 1,
        'register_date' => '2007-07-21 00:00:00',
        'source' => 'Online Event Registration: API Testing',
      ),
    );
    $p['total_amount'] = 600;
    $order = $this->callAPISuccess('order', 'create', $p);
    $expectedResult = array(
      $order['id'] => array(
        'total_amount' => 600,
        'contribution_status' => 'Completed',
        'net_amount' => 600,
      ),
    );
    $paymentParticipant = array(
      'contribution_id' => $order['id'],
    );
    $order = $this->callAPISuccess('order', 'get', $paymentParticipant);
    $this->checkPaymentResult($order, $expectedResult);
    $this->callAPISuccessGetCount('ParticipantPayment', $paymentParticipant, 2);
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $order['id'],
    ));
  }

  /**
   * Test create order api with line items
   */
  public function testAddOrderWithLineItems() {
    $order = $this->addOrder(TRUE);
    $params = array(
      'contribution_id' => $order['id'],
    );
    $order = $this->callAPISuccess('order', 'get', $params);
    $expectedResult = array(
      $order['id'] => array(
        'total_amount' => 300,
        'contribution_id' => $order['id'],
        'contribution_status' => 'Completed',
        'net_amount' => 300,
      ),
    );
    $items[] = array(
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $order['id'],
      'contribution_id' => $order['id'],
      'unit_price' => 100,
      'line_total' => 100,
    );
    $items[] = array(
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $order['id'],
      'contribution_id' => $order['id'],
      'unit_price' => 200,
      'line_total' => 200,
    );
    $this->checkPaymentResult($order, $expectedResult, $items);
    $params = array(
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $order['id'],
    );
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $this->assertEquals($eft['values'][$eft['id']]['amount'], 300);
    $params = array(
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $eft['values'][$eft['id']]['financial_trxn_id'],
    );
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $amounts = array(200, 100);
    foreach ($eft['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $order['id'],
    ));
  }

  /**
   * Test delete order api
   */
  public function testDeleteOrder() {
    $order = $this->addOrder(FALSE, 100);
    $params = array(
      'contribution_id' => $order['id'],
    );
    try {
      $this->callAPISuccess('order', 'delete', $params);
      $this->fail("Missed expected exception");
    }
    catch (Exception $expected) {
      $this->callAPISuccess('Contribution', 'create', array(
        'contribution_id' => $order['id'],
        'is_test' => TRUE,
      ));
      $this->callAPISuccess('order', 'delete', $params);
      $order = $this->callAPISuccess('order', 'get', $params);
      $this->assertEquals(0, $order['count']);
    }
  }

}

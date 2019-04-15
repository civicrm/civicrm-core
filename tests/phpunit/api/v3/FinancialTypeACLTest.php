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
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 */
class api_v3_FinancialTypeACLTest extends CiviUnitTestCase {

  use CRMTraits_Financial_FinancialACLTrait;

  protected $_individualId;
  protected $_contribution;
  protected $_financialTypeId = 1;
  protected $_apiversion;
  protected $_entity = 'Contribution';
  public $debug = 0;
  protected $_params;
  protected $_ids = array();
  protected $_pageParams = array();

  /**
   * Parameters to create payment processor.
   *
   * @var array
   */
  protected $_processorParams = array();

  /**
   * ID of created event.
   *
   * @var int
   */
  protected $_eventID;

  /**
   * Setup function.
   */
  public function setUp() {
    parent::setUp();

    $this->_apiversion = 3;
    $this->_individualId = $this->individualCreate();
    $this->_params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    );
    $this->_processorParams = array(
      'domain_id' => 1,
      'name' => 'Dummy',
      'payment_processor_type_id' => 10,
      'financial_account_id' => 12,
      'is_active' => 1,
      'user_name' => '',
      'url_site' => 'http://dummy.com',
      'url_recur' => 'http://dummy.com',
      'billing_mode' => 1,
    );
    $this->_pageParams = array(
      'title' => 'Test Contribution Page',
      'financial_type_id' => 1,
      'currency' => 'USD',
      'financial_account_id' => 1,
      'payment_processor' => $this->processorCreate(),
      'is_active' => 1,
      'is_allow_other_amount' => 1,
      'min_amount' => 10,
      'max_amount' => 1000,
    );
  }

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(array('civicrm_uf_match'));
    $this->disableFinancialACLs();
  }

  /**
   * Test Get.
   */
  public function testCreateACLContribution() {
    $this->enableFinancialACLs();
    $p = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'trxn_id' => 23456,
      'invoice_id' => 78910,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'check_permissions' => TRUE,
    );

    $this->setPermissions([
      'access CiviCRM',
      'access CiviContribute',
      'edit contributions',
    ]);
    $result = $this->callAPIFailure('contribution', 'create', $p);
    $this->assertEquals('You do not have permission to create this contribution', $result['error_message']);
    $this->addFinancialAclPermissions([['add', 'Donation']]);

    $contribution = $this->callAPISuccess('contribution', 'create', $p);

    $params = array(
      'contribution_id' => $contribution['id'],
    );

    $this->setPermissions([
      'access CiviCRM',
      'access CiviContribute',
      'edit contributions',
      'view contributions of type Donation',
      'delete contributions of type Donation',
    ]);

    $contribution = $this->callAPISuccess('contribution', 'get', $params);

    $this->assertEquals(1, $contribution['count']);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], 1);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['non_deductible_amount'], 10.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['fee_amount'], 5.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['net_amount'], 95.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 23456);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 78910);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Completed');
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution['id'],
    ));
  }

  /**
   * Test that acl contributions can be retrieved.
   */
  public function testGetACLContribution() {
    $this->enableFinancialACLs();

    $this->setPermissions([
      'access CiviCRM',
      'access CiviContribute',
      'view all contacts',
      'add contributions of type Donation',
    ]);
    $contribution = $this->callAPISuccess('Contribution', 'create', $this->_params);
    $this->callAPISuccess('Contribution', 'create', array_merge($this->_params, ['financial_type_id' => 'Member Dues']));

    $params = array(
      'id' => $contribution['id'],
      'check_permissions' => TRUE,
    );
    $contribution = $this->callAPISuccess('contribution', 'get', $params);
    $this->assertEquals($contribution['count'], 0);

    $this->addFinancialAclPermissions([['view', 'Donation']]);
    $this->callAPISuccessGetSingle('contribution', $params);
    $this->callAPISuccessGetCount('contribution', ['financial_type_id' => 'Member Dues', 'check_permissions' => 1], 0);
    $this->markTestIncomplete('check_permissions = 0 should be respected but is not - I have added a todo at the right place but not changed it as yet');
    $this->callAPISuccessGetCount('contribution', ['financial_type_id' => 'Member Dues'], 1);
  }

  /**
   * Test checks that passing in line items suppresses the create mechanism.
   */
  public function testCreateACLContributionChainedLineItems() {
    $this->enableFinancialACLs();
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'check_permissions' => TRUE,
      'api.line_item.create' => array(
        array(
          'price_field_id' => 1,
          'qty' => 2,
          'line_total' => '20',
          'unit_price' => '10',
          'financial_type_id' => 1,
        ),
        array(
          'price_field_id' => 1,
          'qty' => 1,
          'line_total' => '80',
          'unit_price' => '80',
          'financial_type_id' => 2,
        ),
      ),
    );

    $this->setPermissions([
      'access CiviCRM',
      'access CiviContribute',
      'edit contributions',
      'delete in CiviContribute',
      'add contributions of type Donation',
      'delete contributions of type Donation',
    ]);
    $this->callAPIFailure('contribution', 'create', $params, 'Error in call to LineItem_create : You do not have permission to create this line item');

    // Check that the entire contribution has rolled back.
    $contribution = $this->callAPISuccess('contribution', 'get', array());
    $this->assertEquals(0, $contribution['count']);

    $this->addFinancialAclPermissions([
      ['add', 'Member Dues'],
      ['view', 'Donation'],
      ['view', 'Member Dues'],
      ['delete', 'Member Dues'],
    ]);
    $contribution = $this->callAPISuccess('contribution', 'create', $params);

    $lineItemParams = array(
      'contribution_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
    );
    $lineItems = $this->callAPISuccess('LineItem', 'get', $lineItemParams);
    $this->assertEquals(3, $lineItems['count']);
    $this->assertEquals(100.00, $lineItems['values'][3]['line_total']);
    $this->assertEquals(20, $lineItems['values'][4]['line_total']);
    $this->assertEquals(80, $lineItems['values'][5]['line_total']);
    $this->assertEquals(1, $lineItems['values'][3]['financial_type_id']);
    $this->assertEquals(1, $lineItems['values'][4]['financial_type_id']);
    $this->assertEquals(2, $lineItems['values'][5]['financial_type_id']);

    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution['id'],
    ));
  }

  /**
   * Test that acl contributions can be edited.
   */
  public function testEditACLContribution() {
    $this->enableFinancialACLs();
    $contribution = $this->callAPISuccess('Contribution', 'create', $this->_params);

    $params = array(
      'id' => $contribution['id'],
      'check_permissions' => TRUE,
      'total_amount' => 200.00,
    );

    $this->setPermissions([
      'access CiviCRM',
      'access CiviContribute',
      'edit contributions',
      'view contributions of type Donation',
    ]);
    $this->callAPIFailure('Contribution', 'create', $params);

    $this->addFinancialAclPermissions([['edit', 'Donation']]);
    $contribution = $this->callAPISuccess('Contribution', 'create', $params);

    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 200.00);
  }

  /**
   * Test that acl contributions can be deleted.
   */
  public function testDeleteACLContribution() {
    $this->enableFinancialACLs();

    $this->setPermissions([
      'access CiviCRM',
      'access CiviContribute',
      'view all contacts',
      'add contributions of type Donation',
    ]);
    $contribution = $this->callAPISuccess('Contribution', 'create', $this->_params);

    $params = array(
      'contribution_id' => $contribution['id'],
      'check_permissions' => TRUE,
    );
    $this->addPermissions(['delete in CiviContribute']);
    $this->callAPIFailure('Contribution', 'delete', $params);

    $this->addFinancialAclPermissions([['delete', 'Donation']]);
    $contribution = $this->callAPISuccess('Contribution', 'delete', $params);

    $this->assertEquals($contribution['count'], 1);
  }

}

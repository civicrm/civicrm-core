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
 * Class api_v3_LineItemTest
 * @group headless
 */
class api_v3_LineItemTest extends CiviUnitTestCase {
  use CRM_Financial_Form_SalesTaxTrait;

  protected $params;
  protected $_entity = 'line_item';

  /**
   * Should financials be checked after the test but before tear down.
   *
   * @var bool
   */
  protected $isValidateFinancialsOnPostAssert = TRUE;

  /**
   * Prepare for test.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
    $contributionParams = [
      'contact_id' => $this->individualCreate(),
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => 'Donation',
      'non_deductible_amount' => 10.00,
      'fee_amount' => 51.00,
      'net_amount' => 91.00,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $this->params = [
      'price_field_value_id' => 1,
      'price_field_id' => 1,
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'qty' => 1,
      'unit_price' => 50,
      'line_total' => 50,
    ];
  }

  /**
   * Test tax is calculated correctly on the line item.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testCreateLineItemWithTax($version): void {
    $this->_apiversion = $version;
    $this->enableSalesTaxOnFinancialType('Donation');
    $this->params['financial_type_id'] = 'Donation';
    $result = $this->callAPISuccess('LineItem', 'create', $this->params);
    $lineItem = $this->callAPISuccessGetSingle('LineItem', ['id' => $result['id']]);
    $this->assertEquals(5, $lineItem['tax_amount']);
    $this->assertEquals(50, $lineItem['line_total']);
  }

  /**
   * Enable tax for the given financial type.
   *
   * @param string $type
   *
   * @throws \CRM_Core_Exception
   * @todo move to a trait, share.
   *
   * @dataProvider versionThreeAndFour
   *
   */
  public function enableSalesTaxOnFinancialType($type): void {
    $this->enableTaxAndInvoicing();
    $this->addTaxAccountToFinancialType(CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', $type));
  }

  /**
   * Test basic create line item.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateLineItem(int $version): void {
    $this->_apiversion = $version;
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->params, __FUNCTION__, __FILE__)['values'];
    $this->assertCount(1, $result);
    $this->getAndCheck($this->params, key($result), $this->_entity);
  }

  /**
   * Test zero is valid for amount fields.
   *
   * https://github.com/civicrm/civicrm-core/pull/20342
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateLineItemZero(int $version): void {
    $this->_apiversion = $version;
    $this->callAPISuccess('LineItem', 'create', array_merge($this->params, ['unit_price' => 0, 'line_total' => 0]));
    $this->callAPISuccess('LineItem', 'create', array_merge($this->params, ['unit_price' => 0.0, 'line_total' => 0.0]));
  }

  /**
   * Test basic get line item.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetBasicLineItem($version): void {
    $this->_apiversion = $version;
    $getParams = [
      'entity_table' => 'civicrm_contribution',
    ];
    $getResult = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $getResult['count']);
  }

  /**
   * Test delete line item.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   *
   * @throws \CRM_Core_Exception
   */
  public function testDeleteLineItem(int $version): void {
    // Deleting line items does not leave valid financial data.
    $this->isValidateFinancialsOnPostAssert = FALSE;
    $this->_apiversion = $version;
    $getParams = [
      'entity_table' => 'civicrm_contribution',
    ];
    $getResult = $this->callAPISuccess($this->_entity, 'get', $getParams);
    $deleteParams = ['id' => $getResult['id']];
    $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get');
    $this->assertEquals(0, $checkDeleted['count']);
  }

  /**
   * Test getfields function.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetFieldsLineItem(): void {
    $result = $this->callAPISuccess($this->_entity, 'getfields', ['action' => 'create']);
    $this->assertEquals(1, $result['values']['entity_id']['api.required']);
  }

}

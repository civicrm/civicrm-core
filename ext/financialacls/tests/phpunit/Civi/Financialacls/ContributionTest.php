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
namespace Civi\Financialacls;

require_once 'BaseTestClass.php';

/**
 * Class CRM_Contribute_BAO_ContributionTest
 * @group headless
 */
class ContributionTest extends BaseTestClass {

  /**
   * Test the annual query returns a correct result when multiple line items
   * are present.
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function testAnnualWithMultipleLineItems(): void {
    $this->createContributionWithTwoLineItems();
    $this->addFinancialAclPermissions([['view', 'Donation']]);
    $sql = \CRM_Contribute_BAO_Contribution::getAnnualQuery([$this->ids['Contact']['logged_in']]);
    $result = \CRM_Core_DAO::executeQuery($sql);
    $result->fetch();
    $this->assertEquals(0, $result->N);

    // It didn't find any rows cos it is restricted to only find contributions where all lines are visible.
    \CRM_Core_DAO::executeQuery('UPDATE civicrm_line_item SET financial_type_id = ' . \CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'));
    $result = \CRM_Core_DAO::executeQuery($sql);
    $result->fetch();
    $this->assertEquals(300, $result->amount);
    $this->assertEquals(1, $result->count);
  }

  /**
   * Create a contribution with 2 line items.
   *
   * This also involves creating t
   *
   * @param string $identifier
   *   Name to identify price set.
   */
  protected function createContributionWithTwoLineItems(string $identifier = 'Donation'): void {
    $priceSet = $this->createTestEntity('PriceSet', [
      'title' => 'Price Set',
      'name' => 'price_set',
      'financial_type_id.name' => 'Donation',
      'extends' => 1,
    ], $identifier);
    $this->createTestEntity('PriceField', [
      'label' => 'Price Field',
      'name' => 'price_field',
      'html_type' => 'CheckBox',
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'price_set_id' => $priceSet['id'],
      'is_enter_qty' => 1,
      'financial_type_id.name' => 'Donation',
    ], $identifier . '-1');
    $this->createTestEntity('PriceField', [
      'label' => 'Price Field',
      'name' => 'price_field-2',
      'html_type' => 'CheckBox',
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'price_set_id' => $priceSet['id'],
      'is_enter_qty' => 1,
      'financial_type_id.name' => 'Donation',
    ], $identifier . '-2');
    $this->createTestEntity('PriceFieldValue', [
      'name' => 'Price Field 1',
      'label' => 'Price Field 1',
      'value' => 100,
      'weight' => 1,
      'amount' => 100,
      'price_field_id' => $this->ids['PriceField'][$identifier . '-1'],
      'financial_type_id:name' => 'Donation',
    ], $identifier . '-1');
    $this->createTestEntity('PriceFieldValue', [
      'name' => 'Price Field 2',
      'label' => 'Price Field 2',
      'value' => 200,
      'weight' => 2,
      'amount' => 200,
      'price_field_id' => $this->ids['PriceField'][$identifier . '-2'],
      'financial_type_id:name' => 'Donation',
    ], $identifier . '-2');

    $params['line_items'][]['line_item'][] = [
      'price_field_id' => $this->ids['PriceField'][$identifier . '-1'],
      'price_field_value_id' => $this->ids['PriceFieldValue'][$identifier . '-1'],
      'label' => 'price 1',
      'qty' => 1,
      'line_total' => 100,
      'financial_type_id' => \CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Event Fee'),
      'entity_table' => 'civicrm_contribution',
    ];
    $params['line_items'][]['line_item'][] = [
      'price_field_id' => $this->ids['PriceField'][$identifier . '-2'],
      'price_field_value_id' => $this->ids['PriceFieldValue'][$identifier . '-2'],
      'label' => 'price 2',
      'qty' => 1,
      'line_total' => 200,
      'financial_type_id' => \CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Event Fee'),
      'entity_table' => 'civicrm_contribution',
    ];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts'];
    $params['contact_id'] = $this->createLoggedInUser();
    $params['financial_type_id'] = 'Donation';
    $params['total_amount'] = 300;
    $order = $this->callAPISuccess('Order', 'create', $params + ['version' => 3]);
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $order['id'],
      'total_amount' => $params['total_amount'],
      'version' => 3,
    ]);
  }

}

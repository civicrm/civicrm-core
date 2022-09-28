<?php

namespace Civi\Financialacls;

// I fought the Autoloader and the autoloader won.
use Civi\Api4\LineItem;

require_once 'BaseTestClass.php';

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class LineItemTest extends BaseTestClass {

  /**
   * Test api applies permissions on line item actions (delete & get).
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testLineItemApiPermissions($version): void {
    $contact1 = $this->individualCreate();
    $lineItems = $this->callAPISuccess('LineItem', 'get', ['sequential' => TRUE])['values'];
    $this->assertCount(0, $lineItems);
    $this->createPriceSet();
    $order = $this->callAPISuccess('Order', 'create', [
      'financial_type_id' => 'Donation',
      'contact_id' => $contact1,
      'line_items' => [
        [
          'line_item' => [
            [
              'financial_type_id' => \CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'),
              'line_total' => 40,
              'price_field_id' => $this->ids['PriceField'][0],
              'price_field_value_id' => $this->ids['PriceFieldValue'][0],
              'qty' => 1,
            ],
            [
              'financial_type_id' => \CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Member Dues'),
              'line_total' => 50,
              'price_field_id' => $this->ids['PriceField'][0],
              'price_field_value_id' => $this->ids['PriceFieldValue'][1],
              'qty' => 1,
            ],
          ],
        ],
      ],
    ]);
    $this->_apiversion = $version;

    $this->setupLoggedInUserWithLimitedFinancialTypeAccess();

    $lineItems = $this->callAPISuccess('LineItem', 'get', ['sequential' => TRUE])['values'];
    $this->assertCount(2, $lineItems);
    $this->callAPISuccessGetCount('LineItem', ['check_permissions' => TRUE], 1);

    $this->callAPISuccess('LineItem', 'Delete', ['check_permissions' => ($version == 3), 'id' => $lineItems[0]['id']]);
    $this->callAPIFailure('LineItem', 'Delete', ['check_permissions' => TRUE, 'id' => $lineItems[1]['id']]);
    $lineParams = [
      'entity_id' => $order['id'],
      'entity_table' => 'civicrm_contribution',
      'line_total' => 20,
      'unit_price' => 20,
      'price_field_id' => $this->ids['PriceField'][0],
      'qty' => 1,
      'financial_type_id' => 'Donation',
      'check_permissions' => ($version === 3),
    ];
    $line = $this->callAPISuccess('LineItem', 'Create', $lineParams);
    $lineParams['financial_type_id'] = 'Event Fee';
    $lineParams['check_permissions'] = TRUE;
    $this->callAPIFailure('LineItem', 'Create', $lineParams);

    $this->callAPIFailure('LineItem', 'Create', ['id' => $line['id'], 'check_permissions' => TRUE, 'financial_type_id' => 'Event Fee']);
    $invalidLineItem = $this->callAPISuccess('LineItem', 'Create', ['id' => $line['id'], 'check_permissions' => ($version == 3), 'financial_type_id' => 'Donation']);
    LineItem::delete(FALSE)->addWhere('id', '=', $invalidLineItem['id'])->execute();
  }

}

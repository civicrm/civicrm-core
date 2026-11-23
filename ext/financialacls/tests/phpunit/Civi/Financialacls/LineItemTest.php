<?php

namespace Civi\Financialacls;

use Civi\Api4\LineItem;
use Civi\Api4\Order;

// I fought the Autoloader and the autoloader won.
require_once 'BaseTestClass.php';

/**
 * @group headless
 */
class LineItemTest extends BaseTestClass {

  protected $_apiversion = 4;

  /**
   * Test api applies permissions on line item actions (delete & get).
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testLineItemApiPermissions($version): void {
    $contact1 = $this->individualCreate();
    $this->assertCount(0, LineItem::get(FALSE)->execute());
    $this->createPriceSet();
    $order = Order::create(FALSE)
      ->setContributionValues([
        'contact_id' => $contact1,
        'financial_type_id:name' => 'Donation',
      ])
      ->addLineItem([
        'financial_type_id:name' => 'Donation',
        'line_total' => 40,
        'price_field_id' => $this->ids['PriceField']['default'],
        'price_field_value_id' => $this->ids['PriceFieldValue']['default-1'],
        'qty' => 1,
      ])
      ->addLineItem([
        'financial_type_id:name' => 'Member Dues',
        'line_total' => 50,
        'price_field_id' => $this->ids['PriceField']['default'],
        'price_field_value_id' => $this->ids['PriceFieldValue']['default-2'],
        'qty' => 1,
      ])
      ->execute()->first();
    $this->_apiversion = $version;

    $this->setupLoggedInUserWithLimitedFinancialTypeAccess();

    $lineItems = $this->callAPISuccess('LineItem', 'get', ['sequential' => TRUE])['values'];
    $this->assertCount(2, $lineItems);
    $this->callAPISuccessGetCount('LineItem', ['check_permissions' => TRUE], 0);

    $this->callAPISuccess('LineItem', 'Delete', ['check_permissions' => ($version == 3), 'id' => $lineItems[0]['id']]);
    $this->callAPIFailure('LineItem', 'Delete', ['check_permissions' => TRUE, 'id' => $lineItems[1]['id']]);
    $lineParams = [
      'entity_id' => $order['id'],
      'entity_table' => 'civicrm_contribution',
      'line_total' => 20,
      'unit_price' => 20,
      'price_field_id' => $this->ids['PriceField']['default'],
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

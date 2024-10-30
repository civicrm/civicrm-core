<?php

namespace Civi\Financialacls;

require_once 'BaseTestClass.php';

/**
 * @group headless
 */
class ProductTest extends BaseTestClass {

  /**
   * Test api applies permissions on line item actions (delete & get).
   *
   * @dataProvider versionThreeAndFour
   */
  public function testProductApiPermissions($version): void {
    $this->createTestEntity('Product', [
      'name' => '10_dollars',
      'description' => '10 dollars worth of monopoly money',
      'options' => 'White, Black, Green',
      'price' => 2,
      'min_contribution' => 10,
      'cost' => .05,
      'financial_type_id:name' => 'Member Dues',
    ], '10_dollars');
    $this->_apiversion = $version;
    $this->setupLoggedInUserWithLimitedFinancialTypeAccess();
    $products = $this->callAPISuccess('Product', 'get', ['sequential' => TRUE])['values'];
    $this->assertCount(1, $products);
    $this->callAPISuccessGetCount('Product', ['check_permissions' => TRUE], 0);
    $this->callAPIFailure('Product', 'Delete', ['check_permissions' => TRUE, 'id' => $products[0]['id']]);
    $this->callAPISuccess('Product', 'Delete', ['check_permissions' => FALSE, 'id' => $products[0]['id']]);
  }

}

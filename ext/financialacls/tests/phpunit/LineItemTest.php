<?php

use CRM_Financialacls_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use Civi\Api4\PriceField;

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
class LineItemTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use Civi\Test\ContactTestTrait;
  use Civi\Test\Api3TestTrait;

  /**
   * @return \Civi\Test\CiviEnvBuilder
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Test api applies permissions on line item actions (delete & get).
   * @dataProvider versionThreeAndFour
   */
  public function testLineItemApiPermissions($version) {
    $contact1 = $this->individualCreate();
    $defaultPriceFieldID = $this->getDefaultPriceFieldID();
    $order = $this->callAPISuccess('Order', 'create', [
      'financial_type_id' => 'Donation',
      'contact_id' => $contact1,
      'line_items' => [
        [
          'line_item' => [
            [
              'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'),
              'line_total' => 40,
              'price_field_id' => $defaultPriceFieldID,
              'qty' => 1,
            ],
            [
              'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Member Dues'),
              'line_total' => 50,
              'price_field_id' => $defaultPriceFieldID,
              'qty' => 1,
            ],
          ],
        ],
      ],
    ]);
    $this->_apiversion = $version;

    $this->setPermissions([
      'access CiviCRM',
      'access CiviContribute',
      'edit contributions',
      'delete in CiviContribute',
      'view contributions of type Donation',
      'delete contributions of type Donation',
      'add contributions of type Donation',
      'edit contributions of type Donation',
    ]);
    Civi::settings()->set('acl_financial_type', TRUE);
    $this->createLoggedInUser();

    $lineItems = $this->callAPISuccess('LineItem', 'get', ['sequential' => TRUE])['values'];
    $this->assertCount(2, $lineItems);
    $this->callAPISuccessGetCount('LineItem', ['check_permissions' => TRUE], 1);

    $this->callAPISuccess('LineItem', 'Delete', ['check_permissions' => TRUE, 'id' => $lineItems[0]['id']]);
    $this->callAPIFailure('LineItem', 'Delete', ['check_permissions' => TRUE, 'id' => $lineItems[1]['id']]);
    $lineParams = [
      'entity_id' => $order['id'],
      'entity_table' => 'civicrm_contribution',
      'line_total' => 20,
      'unit_price' => 20,
      'price_field_id' => $defaultPriceFieldID,
      'qty' => 1,
      'financial_type_id' => 'Donation',
      'check_permissions' => TRUE,
    ];
    $line = $this->callAPISuccess('LineItem', 'Create', $lineParams);
    $lineParams['financial_type_id'] = 'Event Fee';
    $this->callAPIFailure('LineItem', 'Create', $lineParams);

    $this->callAPIFailure('LineItem', 'Create', ['id' => $line['id'], 'check_permissions' => TRUE, 'financial_type_id' => 'Event Fee']);
    $this->callAPISuccess('LineItem', 'Create', ['id' => $line['id'], 'check_permissions' => TRUE, 'financial_type_id' => 'Donation']);
  }

  /**
   * Set ACL permissions, overwriting any existing ones.
   *
   * @param array $permissions
   *   Array of permissions e.g ['access CiviCRM','access CiviContribute'],
   */
  protected function setPermissions($permissions) {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = $permissions;
    if (isset(\Civi::$statics['CRM_Financial_BAO_FinancialType'])) {
      unset(\Civi::$statics['CRM_Financial_BAO_FinancialType']);
    }
  }

  /**
   * @return mixed
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function getDefaultPriceFieldID(): int {
    return PriceField::get()
      ->addWhere('price_set_id:name', '=', 'default_contribution_amount')
      ->addWhere('name', '=', 'contribution_amount')
      ->addWhere('html_type', '=', 'Text')
      ->addSelect('id')->execute()->first()['id'];
  }

}

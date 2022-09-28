<?php

namespace Civi\Financialacls;

use Civi\Api4\PriceField;
use Civi\Api4\PriceFieldValue;
use Civi\Api4\PriceSet;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use Civi\Test\ContactTestTrait;
use Civi\Test\Api3TestTrait;

/**
 * @group headless
 */
class BaseTestClass extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use ContactTestTrait;
  use Api3TestTrait;

  /**
   * IDs set up for test.
   *
   * @var array
   */
  protected $ids = [];

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
   * Set ACL permissions, overwriting any existing ones.
   *
   * @param array $permissions
   *   Array of permissions e.g ['access CiviCRM','access CiviContribute'],
   */
  protected function setPermissions(array $permissions) {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = $permissions;
    if (isset(\Civi::$statics['CRM_Financial_BAO_FinancialType'])) {
      unset(\Civi::$statics['CRM_Financial_BAO_FinancialType']);
    }
  }

  protected function setupLoggedInUserWithLimitedFinancialTypeAccess(): void {
    $this->setPermissions([
      'access CiviCRM',
      'access CiviContribute',
      'access CiviMember',
      'edit contributions',
      'delete in CiviContribute',
      'view contributions of type Donation',
      'delete contributions of type Donation',
      'add contributions of type Donation',
      'edit contributions of type Donation',
    ]);
    \Civi::settings()->set('acl_financial_type', TRUE);
    $this->createLoggedInUser();
  }

  /**
   * Create price set.
   *
   * @throws \API_Exception
   */
  protected function createPriceSet(): void {
    $priceSet = PriceSet::create(FALSE)->setValues([
      'title' => 'Price Set',
      'name' => 'price_set',
      'financial_type_id.name' => 'Event Fee',
      'extends' => 1,
    ])->execute()->first();
    $this->ids['PriceSet'][0] = $priceSet['id'];
    $this->ids['PriceField'][0] = PriceField::create(FALSE)->setValues([
      'label' => 'Price Field',
      'name' => 'price_field',
      'html_type' => 'CheckBox',
      'option_label' => ['1' => 'Price Field 1', '2' => 'Price Field 2'],
      'option_value' => ['1' => 100, '2' => 200],
      'option_name' => ['1' => 'Price Field 1', '2' => 'Price Field 2'],
      'option_weight' => ['1' => 1, '2' => 2],
      'option_amount' => ['1' => 100, '2' => 200],
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'is_active' => ['1' => 1, '2' => 1],
      'price_set_id' => $priceSet['id'],
      'is_enter_qty' => 1,
      'financial_type_id.name' => 'Event Fee',
    ])->execute()->first()['id'];
    $this->ids['PriceFieldValue'] = array_keys((array) PriceFieldValue::get()
      ->addWhere('price_field_id', '=', $this->ids['PriceField'][0])
      ->execute()->indexBy('id'));
  }

}

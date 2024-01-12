<?php

namespace Civi\Financialacls;

use Civi\Api4\Contribution;
use Civi\Api4\FinancialType;
use Civi\Api4\PriceField;
use Civi\Api4\PriceFieldValue;
use Civi\Api4\PriceSet;
use Civi\Api4\Product;
use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\ContactTestTrait;
use Civi\Test\Api3TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class BaseTestClass extends TestCase implements HeadlessInterface, HookInterface {

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
  public function setUpHeadless(): CiviEnvBuilder {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function tearDown(): void {
    Contribution::delete(FALSE)->addWhere('id', '>', 0)->execute();
    FinancialType::delete(FALSE)->addWhere('name', 'LIKE', '%test%')->execute();
    Product::delete(FALSE)->addWhere('name', '=', '10_dollars')->execute();
    $this->cleanupPriceSets();
  }

  /**
   * Set ACL permissions, overwriting any existing ones.
   *
   * @param array $permissions
   *   Array of permissions e.g ['access CiviCRM','access CiviContribute'],
   */
  protected function setPermissions(array $permissions): void {
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
      'view all contacts',
      'view contributions of type Donation',
      'delete contributions of type Donation',
      'add contributions of type Donation',
      'edit contributions of type Donation',
      'view all contacts',
    ]);
    \Civi::settings()->set('acl_financial_type', TRUE);
    $this->createLoggedInUser();
  }

  /**
   * Create price set.
   *
   * @throws \CRM_Core_Exception
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

  /**
   * Delete extraneous price sets.
   *
   * @throws \CRM_Core_Exception
   */
  protected function cleanupPriceSets(): void {
    $addedPriceSets = array_keys((array) PriceSet::get(FALSE)
      ->addWhere('name', 'NOT IN', [
        'default_contribution_amount',
        'default_membership_type_amount',
      ])->execute()->indexBy('id'));
    if (empty($addedPriceSets)) {
      return;
    }
    PriceFieldValue::delete(FALSE)
      ->addWhere('price_field_id.price_set_id', 'IN', $addedPriceSets)
      ->execute();
    PriceField::delete(FALSE)
      ->addWhere('price_set_id', 'IN', $addedPriceSets)
      ->execute();
    PriceSet::delete(FALSE)->addWhere('id', 'IN', $addedPriceSets)->execute();
  }

}

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
    $this->createLoggedInUser();
  }

  /**
   * Create price set.
   *
   * @param string $identifier
   */
  protected function createPriceSet(string $identifier = 'default'): void {
    $priceSet = $this->createTestEntity('PriceSet', [
      'title' => 'Price Set',
      'name' => 'price_set',
      'financial_type_id.name' => 'Event Fee',
      'extends' => 1,
    ], $identifier);
    $this->createTestEntity('PriceField', [
      'label' => 'Price Field',
      'name' => 'price_field',
      'html_type' => 'CheckBox',
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'is_active' => ['1' => 1, '2' => 1],
      'price_set_id' => $priceSet['id'],
      'is_enter_qty' => 1,
      'financial_type_id.name' => 'Event Fee',
    ], $identifier);
    $this->createTestEntity('PriceFieldValue', [
      'name' => 'Price Field 1',
      'label' => 'Price Field 1',
      'value' => 100,
      'weight' => 1,
      'amount' => 100,
      'price_field_id' => $this->ids['PriceField'][$identifier],
      'financial_type_id:name' => 'Event Fee',
    ], $identifier . '-1');
    $this->createTestEntity('PriceFieldValue', [
      'name' => 'Price Field 2',
      'label' => 'Price Field 2',
      'value' => 200,
      'weight' => 2,
      'amount' => 200,
      'price_field_id' => $this->ids['PriceField'][$identifier],
      'financial_type_id:name' => 'Event Fee',
    ], $identifier . '-2');
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

  /**
   * Add a permission to the financial ACLs.
   *
   * @param array $aclPermissions
   *   Array of ACL permissions in the format
   *   [[$action, $financialType], [$action, $financialType]
   */
  protected function addFinancialAclPermissions(array $aclPermissions):void {
    $permissions = \CRM_Core_Config::singleton()->userPermissionClass->permissions;
    foreach ($aclPermissions as $aclPermission) {
      $permissions[] = $aclPermission[0] . ' contributions of type ' . $aclPermission[1];
    }
    $this->setPermissions($permissions);
  }

}

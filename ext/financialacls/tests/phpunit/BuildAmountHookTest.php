<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use Civi\Api4\PriceField;
use Civi\Api4\PriceSet;
use Civi\Api4\PriceFieldValue;

/**
 * Test that that financial acls are applied in the context of buildAmountHook.
 *
 * @group headless
 */
class BuildAmountHookTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

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
   */
  public function testBuildAmount() {
    $priceSet = PriceSet::create()->setValues(['name' => 'test', 'title' => 'test', 'extends' => 'CiviMember'])->execute()->first();
    PriceField::create()->setValues([
      'financial_type_id:name' => 'Donation',
      'name' => 'donation',
      'label' => 'donation',
      'price_set_id' => $priceSet['id'],
      'html_type' => 'Select',
    ])->addChain('field_values', PriceFieldValue::save()->setRecords([
      ['financial_type_id:name' => 'Donation', 'name' => 'a', 'label' => 'a', 'amount' => 1],
      ['financial_type_id:name' => 'Member Dues', 'name' => 'b', 'label' => 'b', 'amount' => 2],
    ])->setDefaults(['price_field_id' => '$id']))->execute();
    Civi::settings()->set('acl_financial_type', TRUE);
    $this->setPermissions([
      'access CiviCRM',
      'access CiviContribute',
      'view contributions of type Donation',
      'delete contributions of type Donation',
      'add contributions of type Donation',
      'edit contributions of type Donation',
    ]);
    $this->createLoggedInUser();
    $form = new CRM_Member_Form_Membership();
    $form->controller = new CRM_Core_Controller();
    $form->set('priceSetId', $priceSet['id']);
    CRM_Price_BAO_PriceSet::buildPriceSet($form);
    $priceField = reset($form->_priceSet['fields']);
    $this->assertCount(1, $priceField['options']);
    $this->assertEquals('a', reset($priceField['options'])['name']);
  }

  /**
   * Set ACL permissions, overwriting any existing ones.
   *
   * @param array $permissions
   *   Array of permissions e.g ['access CiviCRM','access CiviContribute'],
   */
  protected function setPermissions(array $permissions) {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = $permissions;
    if (isset(\Civi::$statics['CRM_Financial_BAO_FinancialType'])) {
      unset(\Civi::$statics['CRM_Financial_BAO_FinancialType']);
    }
  }

}

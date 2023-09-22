<?php

namespace Civi\Financialacls;

use Civi\Api4\PriceField;
use Civi\Api4\PriceSet;
use Civi\Api4\PriceFieldValue;

// I fought the Autoloader and the autoloader won.
require_once 'BaseTestClass.php';

/**
 * Test that that financial acls are applied in the context of buildAmountHook.
 *
 * @group headless
 */
class BuildAmountHookTest extends BaseTestClass {

  /**
   * Test api applies permissions on line item actions (delete & get).
   */
  public function testBuildAmount(): void {
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
    $this->setupLoggedInUserWithLimitedFinancialTypeAccess();
    $form = new \CRM_Member_Form_Membership();
    $form->controller = new \CRM_Core_Controller();
    $form->set('priceSetId', $priceSet['id']);
    \CRM_Price_BAO_PriceSet::buildPriceSet($form);
    $priceField = reset($form->_priceSet['fields']);
    $this->assertCount(1, $priceField['options']);
    $this->assertEquals('a', reset($priceField['options'])['name']);
  }

}

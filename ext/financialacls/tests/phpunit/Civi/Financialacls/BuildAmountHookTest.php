<?php

namespace Civi\Financialacls;

use Civi\Api4\PriceField;
use Civi\Api4\PriceSet;
use Civi\Api4\PriceFieldValue;
use Civi\Test\FormTrait;
use Civi\Test\FormWrapper;

// I fought the Autoloader and the autoloader won.
require_once 'BaseTestClass.php';

/**
 * Test that that financial acls are applied in the context of buildAmountHook.
 *
 * @group headless
 */
class BuildAmountHookTest extends BaseTestClass {
  use FormTrait;

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
    $form = $this->getTestForm('CRM_Member_Form_Membership', ['price_set_id' => $priceSet['id']])->processForm(FormWrapper::CONSTRUCTED);
    $fields = $form->getPriceFieldMetadata();
    $priceField = reset($fields);
    $this->assertCount(1, $priceField['options']);
    $this->assertEquals('a', reset($priceField['options'])['name']);
  }

}

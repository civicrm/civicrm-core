<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Test class for CRM_Price_BAO_PriceSet.
 * @group headless
 */
class CRM_Price_BAO_PriceFieldValueTest extends CiviUnitTestCase {

  protected $_apiversion = 4;

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Verifies visibility field exists and is configured as a pseudoconstant
   * referencing the 'visibility' option group.
   */
  public function testVisibilityFieldExists(): void {
    $fields = CRM_Price_DAO_PriceFieldValue::fields();

    $this->assertArrayKeyExists('visibility_id', $fields);
    $this->assertSame('visibility', $fields['visibility_id']['pseudoconstant']['optionGroupName']);
  }

  public function testEmptyStringLabel(): void {
    $priceSetID = $this->createTestEntity('PriceSet', [
      'name' => 'default_goat_priceset',
      'title' => 'Goat accommodation',
      'is_active' => TRUE,
      'help_pre' => "Where does your goat sleep",
      'help_post' => "thank you for your time",
      'extends:name' => ['CiviContribute'],
      'financial_type_id:name' => 'Donation',
      'is_quick_config' => TRUE,
      'is_reserved' => TRUE,
    ])['id'];

    $priceFieldID = $this->createTestEntity('PriceField', [
      'price_set_id' => $priceSetID,
      'name' => 'grassvariety',
      'label' => 'Grass Variety',
      'html_type' => 'Text',
      'is_enter_qty' => TRUE,
      'is_active' => TRUE,
    ])['id'];
    $params = [
      'price_field_id' => $priceFieldID,
      'name' => 'rye_grass',
      'label' => '',
      'amount' => 1,
      'financial_type_id' => $this->getFinancialTypeID('Donation'),
    ];
    $priceFieldValue = CRM_Price_BAO_PriceFieldValue::create($params);
    $priceFieldValue->find(TRUE);
    $this->assertNull($priceFieldValue->label);
  }

}

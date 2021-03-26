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

  /**
   * Verifies visibility field exists and is configured as a pseudoconstant
   * referencing the 'visibility' option group.
   */
  public function testVisibilityFieldExists() {
    $fields = CRM_Price_DAO_PriceFieldValue::fields();

    $this->assertArrayKeyExists('visibility_id', $fields);
    $this->assertEquals('visibility', $fields['visibility_id']['pseudoconstant']['optionGroupName']);
  }

  public function testEmptyStringLabel() {
    // Put stuff here that should happen before all tests in this unit.
    $priceSetParams = [
      'name' => 'default_goat_priceset',
      'title' => 'Goat accommodation',
      'is_active' => 1,
      'help_pre' => "Where does your goat sleep",
      'help_post' => "thank you for your time",
      'extends' => 2,
      'financial_type_id' => 1,
      'is_quick_config' => 1,
      'is_reserved' => 1,
    ];

    $price_set = $this->callAPISuccess('price_set', 'create', $priceSetParams);
    $this->priceSetID = $price_set['id'];

    $priceFieldParams = [
      'price_set_id' => $this->priceSetID,
      'name' => 'grassvariety',
      'label' => 'Grass Variety',
      'html_type' => 'Text',
      'is_enter_qty' => 1,
      'is_active' => 1,
    ];
    $priceField = $this->callAPISuccess('price_field', 'create', $priceFieldParams);
    $this->priceFieldID = $priceField['id'];
    $this->_params = [
      'price_field_id' => $this->priceFieldID,
      'name' => 'rye_grass',
      'label' => '',
      'amount' => 1,
      'financial_type_id' => 1,
    ];
    $priceFieldValue = CRM_Price_BAO_PriceFieldValue::create($this->_params);
    $priceFieldValue->find(TRUE);
    $this->assertEquals('', $priceFieldValue->label);
  }

}

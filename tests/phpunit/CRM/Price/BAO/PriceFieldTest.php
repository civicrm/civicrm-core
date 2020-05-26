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
 * Test class for CRM_Price_BAO_PriceField.
 * @group headless
 */
class CRM_Price_BAO_PriceFieldTest extends CiviUnitTestCase {

  /**
   * Test that when re-submitting the price field values with the option ids added
   * in the format that the contribution page / event page configuration screen
   * does it it doesn't duplicate the options
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitPriceFieldWithOptions() {
    $priceSet = $this->callAPISuccess('PriceSet', 'create', [
      'is_active' => 1,
      'extends' => 2,
      'is_quick_config' => 1,
      'financial_type_id' => 1,
      'name' => 'test_price_set',
      'title' => 'Test Price Set',
    ]);
    $priceFieldParams = [
      'name' => 'contribution_amount',
      'is_active' => 1,
      'weight' => 2,
      'is_required' => 1,
      'label' => 'Contribution Amount',
      'html_type' => 'Radio',
      'financial_type_id' => 1,
      'option_label' => [
        1 => 'Low',
        2 => 'Medium',
        3 => 'High',
      ],
      'option_amount' => [
        1 => 10,
        2 => 50,
        3 => 100,
      ],
      'option_weight' => [
        1 => 1,
        2 => 2,
        3 => 3,
      ],
      'default_option' => 2,
      'price_set_id' => $priceSet['id'],
    ];
    $priceField = $this->callAPISuccess('PriceField', 'create', $priceFieldParams);
    $priceFieldParams['id'] = $priceField['id'];
    $fieldOptions = $this->callAPISuccess('PriceFieldValue', 'get', ['price_field_id' => $priceField['id']]);
    foreach ($fieldOptions['values'] as $fieldOption) {
      if ($fieldOption['amount'] < 20) {
        $key = 1;
      }
      elseif ($fieldOption['amount'] < 60) {
        $key = 2;
      }
      else {
        $key = 3;
      }
      $priceFieldParams['option_id'][$key] = $fieldOption['id'];
    }
    $priceFieldParams['default_option'] = 3;
    $options = $this->callAPISuccess('PriceFieldValue', 'get', ['price_field_id' => $priceField['id']]);
    $this->assertEquals(3, $options['count']);
  }

  /**
   * Test the name can be retrieved from the id using the pseudoConstant.
   */
  public function testGetFromPseudoConstant() {
    $this->assertNotEmpty(CRM_Core_PseudoConstant::getKey('CRM_Price_BAO_PriceField', 'price_set_id', 'default_contribution_amount'));
  }

}

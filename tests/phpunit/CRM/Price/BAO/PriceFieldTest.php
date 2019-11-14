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
   * Sets up the fixtures.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture.
   */
  protected function tearDown() {
  }

  /**
   * Test that when re-submitting the price field values with the option ids added
   * in the format that the contribution page / event page configuration screen
   * does it it doesn't duplicate the options
   */
  public function testSubmitPriceFieldWithOptions() {
    $this->priceSet = civicrm_api3('PriceSet', 'create', [
      'is_active' => 1,
      'extends' => 2,
      'is_quick_config' => 1,
      'financial_type_id' => 1,
      'name' => 'test_price_set',
      'title' => 'Test Price Set',
    ]);
    $this->priceFieldParams = [
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
      'price_set_id' => $this->priceSet['id'],
    ];
    $this->priceField = civicrm_api3('PriceField', 'create', $this->priceFieldParams);
    $this->priceFieldParams['id'] = $this->priceField['id'];
    $fieldOptions = civicrm_api3('PriceFieldValue', 'get', ['price_field_id' => $this->priceField['id']]);
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
      $this->priceFieldParams['option_id'][$key] = $fieldOption['id'];
    }
    $this->priceFieldParams['default_option'] = 3;
    $options = $this->callAPISuccess('PriceFieldValue', 'get', ['price_field_id' => $this->priceField['id']]);
    $this->assertEquals(3, $options['count']);
  }

}

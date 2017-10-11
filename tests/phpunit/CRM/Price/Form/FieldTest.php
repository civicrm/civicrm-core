<?php

class CRM_Price_Form_FieldTest extends CiviUnitTestCase {

  protected $priceFieldValues;
  protected $visibilityOptionsKeys;
  protected $visibilityOptions;
  protected $publicFieldParams;
  protected $adminFieldParams;

  public function setUp() {
    parent::setUp();

    $this->visibilityOptionsKeys = CRM_Price_BAO_PriceFieldValue::buildOptions('visibility_id', NULL, array(
      'labelColumn' => 'name',
      'flip' => TRUE,
    ));

    $this->publicFieldParams = $this->initializeFieldParameters(array(
      'label' => 'Public Price Field',
      'name' => 'public_price',
      'visibility_id' => $this->visibilityOptionsKeys['public'],
    ));

    $this->adminFieldParams = $this->initializeFieldParameters(array(
      'label' => 'Public Price Field',
      'name' => 'public_price',
      'visibility_id' => $this->visibilityOptionsKeys['admin'],
    ));
  }

  public function testPublicFieldWithOnlyAdminOptionsIsNotAllowed() {
    $this->publicFieldParams['option_label'][1] = 'Admin Price';
    $this->publicFieldParams['option_amount'][1] = 10;
    $this->publicFieldParams['option_visibility_id'][1] = $this->visibilityOptionsKeys['admin'];

    $form = new CRM_Price_Form_Field();
    $form->_action = CRM_Core_Action::ADD;
    $files = array();

    $validationResult = $form->formRule($this->publicFieldParams, $files, $form);
    $this->assertType('array', $validationResult);
    $this->assertTrue(array_key_exists('visibility_id', $validationResult));
  }

  public function testAdminFieldDoesNotAllowPublicOptions() {
    $this->adminFieldParams['option_label'][1] = 'Admin Price';
    $this->adminFieldParams['option_amount'][1] = 10;
    $this->adminFieldParams['option_visibility_id'][1] = $this->visibilityOptionsKeys['public'];

    $form = new CRM_Price_Form_Field();
    $form->_action = CRM_Core_Action::ADD;
    $files = array();

    $validationResult = $form->formRule($this->adminFieldParams, $files, $form);
    $this->assertType('array', $validationResult);
    $this->assertTrue(array_key_exists('visibility_id', $validationResult));
  }

  private function initializeFieldParameters($params) {
    $defaultParams = array(
      'label' => 'Price Field',
      'name' => CRM_Utils_String::titleToVar('Price Field'),
      'html_type' => 'Select',
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'is_enter_qty' => 1,
      'financial_type_id' => $this->getFinancialTypeId('Event Fee'),
      'visibility_id' => $this->visibilityOptionsKeys['public'],
    );

    for ($index = 1; $index <= CRM_Price_Form_Field::NUM_OPTION; $index++) {
      $defaultParams['option_label'][$index] = NULL;
      $defaultParams['option_value'][$index] = NULL;
      $defaultParams['option_name'][$index] = NULL;
      $defaultParams['option_weight'][$index] = NULL;
      $defaultParams['option_amount'][$index] = NULL;
      $defaultParams['option_visibility_id'][$index] = NULL;
    }

    return array_merge($defaultParams, $params);
  }

}

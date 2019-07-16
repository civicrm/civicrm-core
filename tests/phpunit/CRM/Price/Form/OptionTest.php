<?php

class CRM_Price_Form_OptionTest extends CiviUnitTestCase {

  protected $priceFieldValues;

  protected $visibilityOptionsKeys;

  protected $visibilityOptions;

  protected $publicValue;

  protected $adminValue;

  public function setUp() {
    parent::setUp();

    $this->visibilityOptions = CRM_Price_BAO_PriceFieldValue::buildOptions('visibility_id', NULL, [
      'labelColumn' => 'name',
    ]);
    $this->visibilityOptionsKeys = CRM_Price_BAO_PriceFieldValue::buildOptions('visibility_id', NULL, [
      'labelColumn' => 'name',
      'flip' => TRUE,
    ]);
  }

  public function testChangingUniquePublicOptionOnPublicFieldIsNotAllowed() {
    $this->setUpPriceSet([
      'html_type' => 'Select',
      'visibility_id' => $this->visibilityOptionsKeys['public'],
      'option_label' => ['1' => 'Price Field 1', '2' => 'Price Field 2'],
      'option_value' => ['1' => 100, '2' => 200],
      'option_name' => ['1' => 'Price Field 1', '2' => 'Price Field 2'],
      'option_weight' => ['1' => 1, '2' => 2],
      'option_amount' => ['1' => 100, '2' => 200],
      'option_visibility_id' => [1 => $this->visibilityOptionsKeys['public'], 2 => $this->visibilityOptionsKeys['admin']],
    ]);

    $params = [
      'fieldId' => $this->publicValue['price_field_id'],
      'optionId' => $this->publicValue['id'],
      'visibility_id' => $this->visibilityOptionsKeys['admin'],
    ];

    $form = new CRM_Price_Form_Option();
    $form->_action = CRM_Core_Action::ADD;
    $files = [];

    $validationResult = $form->formRule($params, $files, $form);
    $this->assertType('array', $validationResult);
    $this->assertTrue(array_key_exists('visibility_id', $validationResult));
  }

  public function testAddingPublicOptionToAdminFieldIsNotAllowed() {
    $this->setUpPriceSet([
      'html_type' => 'Select',
      'visibility_id' => $this->visibilityOptionsKeys['admin'],
      'option_label' => ['1' => 'Price Field 1', '2' => 'Price Field 2'],
      'option_value' => ['1' => 100, '2' => 200],
      'option_name' => ['1' => 'Price Field 1', '2' => 'Price Field 2'],
      'option_weight' => ['1' => 1, '2' => 2],
      'option_amount' => ['1' => 100, '2' => 200],
      'option_visibility_id' => [1 => $this->visibilityOptionsKeys['admin'], 2 => $this->visibilityOptionsKeys['admin']],
    ]);

    $params = [
      'fieldId' => $this->adminValue['price_field_id'],
      'optionId' => $this->adminValue['id'],
      'visibility_id' => $this->visibilityOptionsKeys['public'],
    ];

    $form = new CRM_Price_Form_Option();
    $form->_action = CRM_Core_Action::ADD;
    $files = [];

    $validationResult = $form->formRule($params, $files, $form);
    $this->assertType('array', $validationResult);
    $this->assertTrue(array_key_exists('visibility_id', $validationResult));
  }

  private function setUpPriceSet($params) {
    $priceSetCreateResult = $this->createPriceSet('contribution_page', NULL, $params);

    $this->priceFieldValues = $priceSetCreateResult['values'];

    foreach ($this->priceFieldValues as $currentField) {
      if ($this->visibilityOptions[$currentField['visibility_id']] == 'public') {
        $this->publicValue = $currentField;
      }
      else {
        $this->adminValue = $currentField;
      }
    }
  }

}

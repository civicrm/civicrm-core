<?php

class CRM_Price_Form_FieldTest extends CiviUnitTestCase {

  protected $priceFieldValues;
  protected $visibilityOptionsKeys;
  protected $visibilityOptions;
  protected $publicFieldParams;
  protected $adminFieldParams;

  public function setUp(): void {
    parent::setUp();

    $this->visibilityOptionsKeys = CRM_Core_PseudoConstant::get('CRM_Price_BAO_PriceFieldValue', 'visibility_id', [
      'labelColumn' => 'name',
      'flip' => TRUE,
    ]);

    $this->publicFieldParams = $this->initializeFieldParameters([
      'label' => 'Public Price Field',
      'name' => 'public_price',
      'visibility_id' => $this->visibilityOptionsKeys['public'],
    ]);

    $this->adminFieldParams = $this->initializeFieldParameters([
      'label' => 'Public Price Field',
      'name' => 'public_price',
      'visibility_id' => $this->visibilityOptionsKeys['admin'],
    ]);
  }

  public function testPublicFieldWithOnlyAdminOptionsIsNotAllowed() {
    $this->publicFieldParams['option_label'][1] = 'Admin Price';
    $this->publicFieldParams['option_amount'][1] = 10;
    $this->publicFieldParams['option_visibility_id'][1] = $this->visibilityOptionsKeys['admin'];

    $form = new CRM_Price_Form_Field();
    $form->_action = CRM_Core_Action::ADD;
    $files = [];

    $validationResult = $form->formRule($this->publicFieldParams, $files, $form);
    $this->assertIsArray($validationResult);
    $this->assertTrue(array_key_exists('visibility_id', $validationResult));
  }

  public function testAdminFieldDoesNotAllowPublicOptions() {
    $this->adminFieldParams['option_label'][1] = 'Admin Price';
    $this->adminFieldParams['option_amount'][1] = 10;
    $this->adminFieldParams['option_visibility_id'][1] = $this->visibilityOptionsKeys['public'];

    $form = new CRM_Price_Form_Field();
    $form->_action = CRM_Core_Action::ADD;
    $files = [];

    $validationResult = $form->formRule($this->adminFieldParams, $files, $form);
    $this->assertIsArray($validationResult);
    $this->assertTrue(array_key_exists('visibility_id', $validationResult));
  }

  /**
   * Test submitting a large float value is stored correctly in the db.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   */
  public function testLargeFloatOptionValue($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $thousands = Civi::settings()->get('monetaryThousandSeparator');
    $decimal = Civi::settings()->get('monetaryDecimalPoint');
    $paramsSet['title'] = 'Price Set' . substr(sha1(rand()), 0, 7);
    $paramsSet['name'] = CRM_Utils_String::titleToVar($paramsSet['title']);
    $paramsSet['is_active'] = TRUE;
    $paramsSet['financial_type_id'] = 'Event Fee';
    $paramsSet['extends'] = 1;
    $priceSet = $this->callAPISuccess('price_set', 'create', $paramsSet);
    $form = new CRM_Price_Form_Field();
    $form->_action = CRM_Core_Action::ADD;
    $form->setPriceSetId($priceSet['id']);
    $this->publicFieldParams['option_label'][1] = 'Large Float';
    $this->publicFieldParams['option_amount'][1] = '123' . $thousands . '456' . $thousands . '789' . $decimal . '987654321';
    $this->publicFieldParams['option_visibility_id'][1] = $this->visibilityOptionsKeys['public'];
    $priceField = $form->submit($this->publicFieldParams);
    $priceOptions = $this->callAPISuccess('PriceFieldValue', 'get', ['price_field_id' => $priceField->id]);
    $this->assertEquals(123456789.987654321, $priceOptions['values'][$priceOptions['id']]['amount']);
  }

  private function initializeFieldParameters($params) {
    $defaultParams = [
      'label' => 'Price Field',
      'name' => CRM_Utils_String::titleToVar('Price Field'),
      'html_type' => 'Select',
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'is_enter_qty' => 1,
      'financial_type_id' => $this->getFinancialTypeId('Event Fee'),
      'visibility_id' => $this->visibilityOptionsKeys['public'],
      'price' => 10,
    ];

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

  /**
   * Minimal test intended to check for no glaring errors which membership types are validated.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPriceFieldFormRuleOnMembership() {
    $membershipTypeID = $this->membershipTypeCreate();
    $membershipTypeID2 = $this->membershipTypeCreate(['member_of_contact_id' => $this->setupIDs['contact']]);

    $priceSetID = $this->callAPISuccess('PriceSet', 'create', ['title' => 'blah', 'extends' => 'CiviMember'])['id'];
    /* @var \CRM_Price_Form_Field $form */
    $form = $this->getFormObject('CRM_Price_Form_Field');
    $_REQUEST['sid'] = $priceSetID;
    $form->preProcess();
    $form->buildQuickForm();
    $form->_action = CRM_Core_Action::ADD;
    $errors = CRM_Price_Form_Field::formRule(
      [
        'qfKey' => '91c63cb3e611280f3cd81787847e86568f72cf1ad387c785999ff66f90c575a5_568',
        'entryURL' => 'http://dmaster.local/civicrm/admin/price/field?reset=1&amp;action=add&amp;sid=9',
        'sid' => '9',
        'fid' => '',
        '_qf_default' => 'Field:next',
        '_qf_Field_next' => '1',
        'label' => 'member',
        'html_type' => 'CheckBox',
        'price' => '5',
        'non_deductible_amount' => '',
        'financial_type_id' => '1',
        'membership_type_id' =>
          [
            1 => $membershipTypeID,
            2 => $membershipTypeID2,
            3 => '',
            4 => '',
            5 => '',
            6 => '',
            7 => '',
            8 => '',
            9 => '',
            10 => '',
            11 => '',
            12 => '',
            13 => '',
            14 => '',
            15 => '',
          ],
        'membership_num_terms' =>
          [
            1 => '1',
            2 => 1,
            3 => '',
            4 => '',
            5 => '',
            6 => '',
            7 => '',
            8 => '',
            9 => '',
            10 => '',
            11 => '',
            12 => '',
            13 => '',
            14 => '',
            15 => '',
          ],
        'option_label' =>
          [
            1 => 'General',
            2 => 'Student',
            3 => '',
            4 => '',
            5 => '',
            6 => '',
            7 => '',
            8 => '',
            9 => '',
            10 => '',
            11 => '',
            12 => '',
            13 => '',
            14 => '',
            15 => '',
          ],
        'option_amount' =>
          [
            1 => '100.00',
            2 => '50',
            3 => '',
            4 => '',
            5 => '',
            6 => '',
            7 => '',
            8 => '',
            9 => '',
            10 => '',
            11 => '',
            12 => '',
            13 => '',
            14 => '',
            15 => '',
          ],
        'option_financial_type_id' =>
          [
            1 => '2',
            2 => '1',
            3 => '1',
            4 => '1',
            5 => '1',
            6 => '1',
            7 => '1',
            8 => '1',
            9 => '1',
            10 => '1',
            11 => '1',
            12 => '1',
            13 => '1',
            14 => '1',
            15 => '1',
          ],
        'option_weight' =>
          [
            1 => '1',
            2 => '2',
            3 => '3',
            4 => '4',
            5 => '5',
            6 => '6',
            7 => '7',
            8 => '8',
            9 => '9',
            10 => '10',
            11 => '11',
            12 => '12',
            13 => '13',
            14 => '14',
            15 => '15',
          ],
        'option_visibility_id' =>
          [
            1 => '1',
            2 => '1',
            3 => '1',
            4 => '1',
            5 => '1',
            6 => '1',
            7 => '1',
            8 => '1',
            9 => '1',
            10 => '1',
            11 => '1',
            12 => '1',
            13 => '1',
            14 => '1',
            15 => '1',
          ],
        'option_status' =>
          [
            1 => '1',
            2 => '1',
            3 => '1',
            4 => '1',
            5 => '1',
            6 => '1',
            7 => '1',
            8 => '1',
            9 => '1',
            10 => '1',
            11 => '1',
            12 => '1',
            13 => '1',
            14 => '1',
            15 => '1',
          ],
        'options_per_line' => '1',
        'is_display_amounts' => '1',
        'weight' => '1',
        'help_pre' => '',
        'help_post' => '',
        'active_on' => '',
        'expire_on' => '',
        'visibility_id' => '1',
        'is_active' => '1',
      ], [], $form);
    $this->assertEquals([
      '_qf_default' => 'You have selected multiple memberships for the same organization or entity. Please review your selections and choose only one membership per entity.',
    ], $errors);
  }

}

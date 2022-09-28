<?php
require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Class CRM_Case_Form_CustomDataTest
 * @group headless
 */
class CRM_Case_Form_CustomDataTest extends CiviCaseTestCase {

  protected $custom_group;

  public function setUp(): void {
    parent::setUp();
    $this->custom_group = $this->customGroupCreate(['extends' => 'Case']);
    $this->custom_group = $this->custom_group['values'][$this->custom_group['id']];
  }

  /**
   * Test that changes to custom fields on cases generate the correct details
   * body for ChangeCustomData.
   *
   * @dataProvider customDataProvider
   *
   * @param array $input
   * @param array $expected
   */
  public function testChangeCustomDataFormattedDetails(array $input, array $expected) {
    // set up custom field, with any overrides from input params
    $custom_field = $this->callAPISuccess('custom_field', 'create', array_merge([
      'custom_group_id' => $this->custom_group['id'],
      'label' => 'What?',
      'data_type' => 'String',
      'html_type' => 'Text',
      'is_active' => 1,
    ], $input['custom_field_params']));
    $custom_field = $custom_field['values'][$custom_field['id']];

    // set up case and set the custom field initial value
    $client_id = $this->individualCreate([], 0, TRUE);
    $caseObj = $this->createCase($client_id, $this->_loggedInUser);
    if (isset($input['custom_field_oldvalue'])) {
      $this->callAPISuccess('CustomValue', 'create', [
        "custom_{$custom_field['id']}" => $input['custom_field_oldvalue'],
        'entity_id' => $caseObj->id,
      ]);
    }

    // set up form
    $form = $this->getFormObject('CRM_Case_Form_CustomData');
    $form->set('groupID', $this->custom_group['id']);
    $form->set('entityID', $caseObj->id);
    $form->set('subType', $this->caseTypeId);
    $form->set('cid', $client_id);
    $form->preProcess();

    // We need to do money conversion here because to do the creation above it
    // needs to be in machine format, but then for the form stuff it needs to
    // be in user format.
    if (($input['custom_field_params']['data_type'] ?? '') === 'Money' && CRM_Core_I18n::singleton()->getLocale() !== 'en_US') {
      $expected['string'] = $this->convertCurrency($expected['string']);
      if (isset($input['custom_field_oldvalue'])) {
        $input['custom_field_oldvalue'] = $this->convertCurrency($input['custom_field_oldvalue']);
      }
      $input['custom_field_newvalue'] = $this->convertCurrency($input['custom_field_newvalue']);
    }

    // Simulate an edit with formValues.
    // The suffix is always going to be '1' since we created it above and it's
    // the first entry in the custom_value_XX table. If it doesn't exist yet
    // then our new entry will still be the first and will have suffix 1.
    $custom_field_name = "custom_{$custom_field['id']}_1";
    $formValues = [$custom_field_name => $input['custom_field_newvalue']];

    // compute and compare
    $output = $form->formatCustomDataChangesForDetail($formValues);
    $this->assertEquals($expected['string'], $output);
  }

  /**
   * Same as testChangeCustomDataFormattedDetails but in a different locale.
   *
   * @dataProvider customDataProvider
   *
   * @param array $input
   * @param array $expected
   */
  public function testChangeCustomDataFormattedDetailsLocale(array $input, array $expected) {
    CRM_Core_I18n::singleton()->setLocale('it_IT');
    CRM_Core_Config::singleton()->defaultCurrency = 'EUR';
    CRM_Core_Config::singleton()->monetaryThousandSeparator = ' ';
    CRM_Core_Config::singleton()->monetaryDecimalPoint = ',';

    $this->testChangeCustomDataFormattedDetails($input, $expected);

    CRM_Core_Config::singleton()->defaultCurrency = 'USD';
    CRM_Core_Config::singleton()->monetaryThousandSeparator = ',';
    CRM_Core_Config::singleton()->monetaryDecimalPoint = '.';
    CRM_Core_I18n::singleton()->setLocale('en_US');
  }

  /**
   * data provider for testChangeCustomDataFormattedDetails
   *
   * @return array
   */
  public function customDataProvider(): array {
    return [
      0 => [
        'input' => [
          'custom_field_params' => [
            'html_type' => 'Select',
            'option_values' => [
              [
                'name' => 'Red',
                'label' => 'Red',
                'value' => '1',
                'is_active' => 1,
                'weight' => 1,
              ],
              [
                'name' => 'Green',
                'label' => 'Green',
                'value' => '2',
                'is_active' => 1,
                'weight' => 2,
              ],
            ],
          ],
          'custom_field_oldvalue' => '1',
          'custom_field_newvalue' => '2',
        ],
        'expected' => [
          'string' => 'What?: Red => Green',
        ],
      ],

      1 => [
        'input' => [
          'custom_field_params' => [
            'html_type' => 'Select',
            'option_values' => [
              [
                'name' => 'Red',
                'label' => 'Red',
                'value' => '1',
                'is_active' => 1,
                'weight' => 1,
              ],
              [
                'name' => 'Green',
                'label' => 'Green',
                'value' => '2',
                'is_active' => 1,
                'weight' => 2,
              ],
            ],
          ],
          'custom_field_oldvalue' => '',
          'custom_field_newvalue' => '2',
        ],
        'expected' => [
          'string' => 'What?:  => Green',
        ],
      ],

      2 => [
        'input' => [
          'custom_field_params' => [
            'html_type' => 'Select',
            'option_values' => [
              [
                'name' => 'Red',
                'label' => 'Red',
                'value' => '1',
                'is_active' => 1,
                'weight' => 1,
              ],
              [
                'name' => 'Green',
                'label' => 'Green',
                'value' => '2',
                'is_active' => 1,
                'weight' => 2,
              ],
            ],
          ],
          'custom_field_oldvalue' => '1',
          'custom_field_newvalue' => '',
        ],
        'expected' => [
          'string' => 'What?: Red => ',
        ],
      ],

      3 => [
        'input' => [
          'custom_field_params' => [
            'html_type' => 'Select',
            'option_values' => [
              [
                'name' => 'Red',
                'label' => 'Red',
                'value' => '1',
                'is_active' => 1,
                'weight' => 1,
              ],
              [
                'name' => 'Green',
                'label' => 'Green',
                'value' => '2',
                'is_active' => 1,
                'weight' => 2,
              ],
            ],
          ],
          // Note no old value, simulating as if we already have existing cases, but just added the field definition now.
          'custom_field_newvalue' => '2',
        ],
        'expected' => [
          'string' => 'What?:  => Green',
        ],
      ],

      4 => [
        'input' => [
          'custom_field_params' => [
            'data_type' => 'Money',
          ],
          'custom_field_oldvalue' => '1.23',
          'custom_field_newvalue' => '2.34',
        ],
        'expected' => [
          'string' => 'What?: 1.23 => 2.34',
        ],
      ],

      5 => [
        'input' => [
          'custom_field_params' => [
            'data_type' => 'Money',
          ],
          'custom_field_oldvalue' => '',
          'custom_field_newvalue' => '2.34',
        ],
        'expected' => [
          'string' => 'What?: 0.00 => 2.34',
        ],
      ],

      6 => [
        'input' => [
          'custom_field_params' => [
            'data_type' => 'Money',
          ],
          'custom_field_oldvalue' => '1.23',
          'custom_field_newvalue' => '',
        ],
        'expected' => [
          'string' => 'What?: 1.23 => ',
        ],
      ],

      7 => [
        'input' => [
          'custom_field_params' => [
            'data_type' => 'Money',
          ],
          'custom_field_newvalue' => '2.34',
        ],
        'expected' => [
          'string' => 'What?:  => 2.34',
        ],
      ],

      8 => [
        'input' => [
          'custom_field_params' => [],
          'custom_field_oldvalue' => 'some text',
          'custom_field_newvalue' => 'some new text',
        ],
        'expected' => [
          'string' => 'What?: some text => some new text',
        ],
      ],

      9 => [
        'input' => [
          'custom_field_params' => [],
          'custom_field_oldvalue' => '',
          'custom_field_newvalue' => 'some new text',
        ],
        'expected' => [
          'string' => 'What?:  => some new text',
        ],
      ],

      10 => [
        'input' => [
          'custom_field_params' => [],
          'custom_field_oldvalue' => 'some text',
          'custom_field_newvalue' => '',
        ],
        'expected' => [
          'string' => 'What?: some text => ',
        ],
      ],

      11 => [
        'input' => [
          'custom_field_params' => [],
          'custom_field_newvalue' => 'some new text',
        ],
        'expected' => [
          'string' => 'What?:  => some new text',
        ],
      ],
    ];
  }

  /**
   * Convert to locale currency format for purposes of these tests
   * @param string $input
   * @return string
   */
  private function convertCurrency(string $input): string {
    $conversion_table = [
      ',' => CRM_Core_Config::singleton()->monetaryThousandSeparator,
      '.' => CRM_Core_Config::singleton()->monetaryDecimalPoint,
    ];
    return strtr($input, $conversion_table);
  }

  /**
   * Test that when custom case data is edited but not changed that it doesn't
   * create a meaningless empty activity.
   */
  public function testCustomDataNoChangeNoActivity() {
    // Create a custom group and field
    $customField = $this->callAPISuccess('custom_field', 'create', [
      'custom_group_id' => $this->custom_group['id'],
      'label' => 'What?',
      'data_type' => 'String',
      'html_type' => 'Text',
      'is_active' => 1,
    ]);
    $customField = $customField['values'][$customField['id']];

    // Create a case and set the custom field to something
    $individual = $this->individualCreate();
    $caseObj = $this->createCase($individual, $this->_loggedInUser);
    $caseId = $caseObj->id;
    $this->callAPISuccess('CustomValue', 'create', [
      "custom_{$customField['id']}" => 'immutable text',
      'entity_id' => $caseId,
    ]);

    // run the form
    $form = new CRM_Case_Form_CustomData();
    $form->controller = new CRM_Core_Controller_Simple('CRM_Case_Form_CustomData', 'Case Data');
    $form->set('groupID', $this->custom_group['id']);
    $form->set('entityID', $caseId);
    // this is case type
    $form->set('subType', 1);
    $form->set('cid', $individual);
    ob_start();
    $form->controller->_actions['display']->perform($form, 'display');
    ob_end_clean();

    // Don't change any field values and now call postProcess.
    // Because the form does a redirect it triggers an exception during unit
    // tests but that's ok.
    $hadException = FALSE;
    try {
      $form->controller->_actions['upload']->perform($form, 'upload');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $hadException = TRUE;
      $this->assertEquals('redirect', $e->errorData['context']);
      // compare parts of query string separately to avoid any clean url format mismatches
      $this->assertStringContainsString('civicrm/contact/view/case', $e->errorData['url']);
      $this->assertStringContainsString("reset=1&id={$caseId}&cid={$individual}&action=view", $e->errorData['url']);
    }
    // Make sure we did have an exception since otherwise we might get a
    // false pass for the asserts above since they'd never run.
    $this->assertTrue($hadException);

    // there shouldn't be an activity of type Change Custom Data on the case
    $result = $this->callAPISuccess('Activity', 'get', [
      'activity_type_id' => 'Change Custom Data',
      'case_id' => $caseId,
    ]);
    $this->assertEquals(0, $result['count']);
  }

}

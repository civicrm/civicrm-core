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

use Civi\Api4\CustomGroup;
use Civi\Api4\CustomField;
use Civi\Api4\OptionValue;

/**
 * Trait Custom Data trait.
 *
 * Trait for setting up custom data in tests.
 */
trait CRMTraits_Custom_CustomDataTrait {

  /**
   * Create a custom group with fields of multiple types.
   *
   * @param array $groupParams
   */
  public function createCustomGroupWithFieldsOfAllTypes(array $groupParams = []): void {
    $this->createCustomGroup($groupParams);
    $this->ids['CustomField'] = $this->createCustomFieldsOfAllTypes();
  }

  /**
   * Create a custom group.
   *
   * @param array $params
   *
   * @return int
   */
  public function createCustomGroup(array $params = []): int {
    $params = array_merge([
      'title' => 'Custom Group',
      'extends' => $this->entity ?? 'Contact',
      'weight' => 5,
      'style' => 'Inline',
      'max_multiple' => 0,
    ], $params);
    $identifier = $params['name'] ?? $params['title'];
    try {
      $this->ids['CustomGroup'][$identifier] = CustomGroup::create(FALSE)
        ->setValues($params)
        ->execute()
        ->first()['id'];
    }
    catch (API_Exception $e) {
      $this->fail('Could not create group ' . $e->getMessage());
    }
    return $this->ids['CustomGroup'][$identifier];
  }

  /**
   * Get the table_name for the specified custom group.
   *
   * @param string $identifier
   *
   * @return string
   */
  public function getCustomGroupTable(string $identifier = 'Custom Group'): string {
    return $this->callAPISuccessGetValue('CustomGroup', ['id' => $this->ids['CustomGroup'][$identifier], 'return' => 'table_name']);
  }

  /**
   * Get the the column name for the identified custom field.
   *
   * @param string $key
   *   Identifier - generally keys map to data type - eg. 'text', 'int' etc.
   *
   * @return string
   */
  protected function getCustomFieldColumnName(string $key): string {
    return $this->callAPISuccessGetValue('CustomField', ['id' => $this->getCustomFieldID($key), 'return' => 'column_name']);
  }

  /**
   * Create a custom group with a single field.
   *
   * @param array $groupParams
   *   Params for the group to be created.
   * @param string $customFieldType
   *
   * @param string|null $identifier
   *
   * @param array $fieldParams
   *
   */
  public function createCustomGroupWithFieldOfType(array $groupParams = [], string $customFieldType = 'text', ?string $identifier = NULL, array $fieldParams = []): void {
    $supported = ['text', 'select', 'date', 'checkbox', 'int', 'contact_reference', 'radio', 'multi_country'];
    if (!in_array($customFieldType, $supported, TRUE)) {
      $this->fail('we have not yet extracted other custom field types from createCustomFieldsOfAllTypes, Use consistent syntax when you do');
    }
    $groupParams['title'] = empty($groupParams['title']) ? $identifier . 'Group with field ' . $customFieldType : $groupParams['title'];
    $groupParams['name'] = $identifier ?? 'Custom Group';
    $this->createCustomGroup($groupParams);
    $reference = &$this->ids['CustomField'][$identifier . $customFieldType];
    $fieldParams = array_merge($fieldParams, ['custom_group_id' => $this->ids['CustomGroup'][$groupParams['name']]]);
    switch ($customFieldType) {
      case 'text':
        $reference = $this->createTextCustomField($fieldParams)['id'];
        return;

      case 'select':
        $reference = $this->createSelectCustomField($fieldParams)['id'];
        return;

      case 'checkbox':
        $reference = $this->createStringCheckboxCustomField($fieldParams)['id'];
        return;

      case 'int':
        $reference = $this->createIntCustomField($fieldParams)['id'];
        return;

      case 'date':
        $reference = $this->createDateCustomField($fieldParams)['id'];
        return;

      case 'contact_reference':
        $reference = $this->createContactReferenceCustomField($fieldParams)['id'];
        return;

      case 'radio':
        $reference = $this->createIntegerRadioCustomField($fieldParams)['id'];
        return;

      case 'multi_country':
        $reference = $this->createMultiCountryCustomField($fieldParams)['id'];
        return;

    }
  }

  /**
   * @return array
   */
  public function createCustomFieldsOfAllTypes(): array {
    $customGroupID = $this->ids['CustomGroup']['Custom Group'];
    $ids = [];
    $ids['text'] = (int) $this->createTextCustomField(['custom_group_id' => $customGroupID])['id'];
    $ids['select_string'] = (int) $this->createSelectCustomField(['custom_group_id' => $customGroupID])['id'];
    $ids['select_date'] = (int) $this->createDateCustomField(['custom_group_id' => $customGroupID])['id'];
    $ids['int'] = (int) $this->createIntCustomField(['custom_group_id' => $customGroupID])['id'];
    $ids['link'] = (int) $this->createLinkCustomField(['custom_group_id' => $customGroupID])['id'];
    $ids['file'] = (int) $this->createFileCustomField(['custom_group_id' => $customGroupID])['id'];
    $ids['country'] = (int) $this->createCountryCustomField(['custom_group_id' => $customGroupID])['id'];
    $ids['multi_country'] = (int) $this->createMultiCountryCustomField(['custom_group_id' => $customGroupID])['id'];
    $ids['contact_reference'] = $this->createContactReferenceCustomField(['custom_group_id' => $customGroupID])['id'];
    $ids['state'] = (int) $this->createStateCustomField(['custom_group_id' => $customGroupID])['id'];
    $ids['multi_state'] = (int) $this->createMultiStateCustomField(['custom_group_id' => $customGroupID])['id'];
    $ids['boolean'] = (int) $this->createBooleanCustomField(['custom_group_id' => $customGroupID])['id'];
    $ids['checkbox'] = (int) $this->createStringCheckboxCustomField(['custom_group_id' => $customGroupID])['id'];
    return $ids;
  }

  /**
   * Get the custom field name for the relevant key.
   *
   * e.g returns 'custom_5' where 5 is the id of the field using the key.
   *
   * Generally keys map to data types.
   *
   * @param string $key
   *
   * @return string
   */
  protected function getCustomFieldName(string $key): string {
    return 'custom_' . $this->getCustomFieldID($key);
  }

  /**
   * Add another option to the custom field.
   *
   * @param string $key
   * @param array $values
   *
   * @return int
   * @throws \API_Exception
   */
  protected function addOptionToCustomField(string $key, array $values): int {
    $optionGroupID = CustomField::get(FALSE)
      ->addWhere('id', '=', $this->getCustomFieldID($key))
      ->addSelect('option_group_id')
      ->execute()->first()['option_group_id'];
    return (int) OptionValue::create(FALSE)
      ->setValues(array_merge(['option_group_id' => $optionGroupID], $values))
      ->execute()->first()['value'];
  }

  /**
   * Get the custom field name for the relevant key.
   *
   * e.g returns 'custom_5' where 5 is the id of the field using the key.
   *
   * Generally keys map to data types.
   *
   * @param string $key
   *
   * @return string
   */
  protected function getCustomFieldID(string $key): string {
    return $this->ids['CustomField'][$key];
  }

  /**
   * Get the option group id of the created field.
   *
   * @param string $key
   *
   * @return string
   */
  protected function getOptionGroupID(string $key): string {
    return (string) $this->callAPISuccessGetValue('CustomField', [
      'id' => $this->getCustomFieldID($key),
      'return' => 'option_group_id',
    ]);
  }

  /**
   * Get the option group id of the created field.
   *
   * @param string $key
   *
   * @return string
   */
  protected function getOptionGroupName(string $key): string {
    return (string) $this->callAPISuccessGetValue('CustomField', [
      'id' => $this->getCustomFieldID($key),
      'return' => 'option_group_id.name',
    ]);
  }

  /**
   * Create a custom text fields.
   *
   * @param array $params
   *   Parameter overrides, must include custom_group_id.
   *
   * @return array
   */
  protected function createIntCustomField(array $params = []): array {
    $params = array_merge($this->getFieldsValuesByType('Int'), $params);
    return $this->callAPISuccess('CustomField', 'create', $params)['values'][0];
  }

  /**
   * Create a custom text fields.
   *
   * @param array $params
   *   Parameter overrides, must include custom_group_id.
   *
   * @return array
   */
  protected function createBooleanCustomField(array $params = []): array {
    $params = array_merge($this->getFieldsValuesByType('Boolean'), $params);
    return $this->callAPISuccess('CustomField', 'create', $params)['values'][0];
  }

  /**
   * Create a custom text fields.
   *
   * @param array $params
   *   Parameter overrides, must include custom_group_id.
   *
   * @return array
   */
  protected function createContactReferenceCustomField(array $params = []): array {
    $params = array_merge($this->getFieldsValuesByType('ContactReference'), $params);
    return $this->callAPISuccess('custom_field', 'create', $params)['values'][0];
  }

  /**
   * Create a custom text fields.
   *
   * @param array $params
   *   Parameter overrides, must include custom_group_id.
   *
   * @return array
   */
  protected function createTextCustomField(array $params = []): array {
    $params = array_merge($this->getFieldsValuesByType('String'), $params);
    return $this->callAPISuccess('custom_field', 'create', $params)['values'][0];
  }

  /**
   * Create a custom text fields.
   *
   * @param array $params
   *   Parameter overrides, must include custom_group_id.
   *
   * @return array
   */
  protected function createLinkCustomField(array $params = []): array {
    $params = array_merge($this->getFieldsValuesByType('Link'), $params);
    return $this->callAPISuccess('custom_field', 'create', $params)['values'][0];
  }

  /**
   * Create a custom country fields.
   *
   * @param array $params
   *   Parameter overrides, must include custom_group_id.
   *
   * @return array
   */
  protected function createCountryCustomField(array $params = []): array {
    $params = array_merge($this->getFieldsValuesByType('Country'), $params);
    return $this->callAPISuccess('custom_field', 'create', $params)['values'][0];
  }

  /**
   * Create a custom multi select country fields.
   *
   * @param array $params
   *   Parameter overrides, must include custom_group_id.
   *
   * @return array
   */
  protected function createMultiCountryCustomField(array $params = []): array {
    $params = array_merge($this->getFieldsValuesByType('Country', 'Multi-Select Country'), $params);
    return $this->callAPISuccess('custom_field', 'create', $params)['values'][0];
  }

  /**
   * Create a custom state fields.
   *
   * @param array $params
   *   Parameter overrides, must include custom_group_id.
   *
   * @return array
   */
  protected function createStateCustomField(array $params = []): array {
    $params = array_merge($this->getFieldsValuesByType('StateProvince'), $params);
    return $this->callAPISuccess('custom_field', 'create', $params)['values'][0];
  }

  /**
   * Create a custom multi select state fields.
   *
   * @param array $params
   *   Parameter overrides, must include custom_group_id.
   *
   * @return array
   */
  protected function createMultiStateCustomField(array $params = []): array {
    $params = array_merge($this->getFieldsValuesByType('StateProvince', 'Multi-Select State/Province'), $params);
    return $this->callAPISuccess('custom_field', 'create', $params)['values'][0];
  }

  /**
   * Create a custom text fields.
   *
   * @param array $params
   *   Parameter overrides, must include custom_group_id.
   *
   * @return array
   */
  protected function createFileCustomField(array $params = []): array {
    $params = array_merge($this->getFieldsValuesByType('File'), $params);
    return $this->callAPISuccess('custom_field', 'create', $params)['values'][0];
  }

  /**
   * Create custom select field.
   *
   * @param array $params
   *   Parameter overrides, must include custom_group_id.
   *
   * @return array
   */
  protected function createSelectCustomField(array $params): array {
    $params = array_merge($this->getFieldsValuesByType('String', 'Select'), $params);
    return $this->callAPISuccess('custom_field', 'create', $params)['values'][0];
  }

  /**
   * Create custom select field.
   *
   * @param array $params
   *   Parameter overrides, must include custom_group_id.
   *
   * @return array
   */
  protected function createAutoCompleteCustomField(array $params): array {
    $params = array_merge($this->getFieldsValuesByType('String', 'Autocomplete-Select'), $params);
    return $this->callAPISuccess('custom_field', 'create', $params)['values'][0];
  }

  /**
   * Create a custom field of  type date.
   *
   * @param array $params
   *
   * @return array
   */
  protected function createDateCustomField(array $params): array {
    $params = array_merge($this->getFieldsValuesByType('Date'), $params);
    return $this->callAPISuccess('custom_field', 'create', $params)['values'][0];
  }

  /**
   * Create a custom field of  type radio with integer values.
   *
   * @param array $params
   *
   * @return array
   */
  protected function createStringCheckboxCustomField(array $params): array {
    $params = array_merge($this->getFieldsValuesByType('String', 'CheckBox'), $params);
    return $this->callAPISuccess('custom_field', 'create', $params)['values'][0];
  }

  /**
   * Create a custom field of  type radio with integer values.
   *
   * @param array $params
   *
   * @return array
   */
  protected function createIntegerRadioCustomField(array $params): array {
    $params = array_merge($this->getFieldsValuesByType('Int', 'Radio'), $params);
    return $this->callAPISuccess('custom_field', 'create', $params)['values'][0];
  }

  /**
   * Get default field values for the type of field.
   *
   * @param string $dataType
   * @param string $htmlType
   *
   * @return array
   */
  public function getFieldsValuesByType(string $dataType, string $htmlType = 'default'): array {
    $values = $this->getAvailableFieldCombinations()[$dataType];
    return array_merge([
      'is_searchable' => 1,
      'sequential' => 1,
      'default_value' => '',
      'is_required' => 0,
    ], array_merge($values['default'], $values[$htmlType])
    );
  }

  /**
   * Get data available for custom fields.
   *
   * The 'default' key holds general values. Where more than one html type is an option
   * then the any values that  differ to the defaults are keyed by html key.
   *
   * The order below is consistent with the UI.
   *
   * @return array
   */
  protected function getAvailableFieldCombinations(): array {
    return [
      'String' => [
        'default' => [
          'label' => 'Enter text here',
          'html_type' => 'Text',
          'data_type' => 'String',
          'default_value' => 'xyz',
          'text_length' => 300,
        ],
        'Select' => [
          'label' => 'Pick Color',
          'html_type' => 'Select',
          'data_type' => 'String',
          'text_length' => '',
          'default_value' => '',
          'option_values' => [
            [
              'label' => 'Red',
              'value' => 'R',
              'weight' => 1,
              'is_active' => 1,
            ],
            [
              'label' => 'Yellow',
              'value' => 'Y',
              'weight' => 2,
              'is_active' => 1,
            ],
            [
              'label' => 'Green',
              'value' => 'G',
              'weight' => 3,
              'is_active' => 1,
            ],
          ],
        ],
        'Radio' => [
          'label' => 'Pick Color',
          'html_type' => 'Radio',
          'data_type' => 'String',
          'text_length' => '',
          'default_value' => '',
          'option_values' => [
            [
              'label' => 'Red',
              'value' => 'R',
              'weight' => 1,
              'is_active' => 1,
            ],
            [
              'label' => 'Yellow',
              'value' => 'Y',
              'weight' => 2,
              'is_active' => 1,
            ],
            [
              'label' => 'Green',
              'value' => 'G',
              'weight' => 3,
              'is_active' => 1,
            ],
          ],
        ],
        'CheckBox' => [
          'label' => 'Pick Shade',
          'html_type' => 'CheckBox',
          'data_type' => 'String',
          'text_length' => '',
          'default_value' => '',
          'option_values' => [
            [
              'label' => 'Lilac',
              'value' => 'L',
              'weight' => 1,
              'is_active' => 1,
            ],
            [
              'label' => 'Purple',
              'value' => 'P',
              'weight' => 2,
              'is_active' => 1,
            ],
            [
              'label' => 'Mauve',
              'value' => 'M',
              'weight' => 3,
              'is_active' => 1,
            ],
            [
              'label' => 'Violet',
              'value' => 'V',
              'weight' => 4,
              'is_active' => 1,
            ],
          ],
        ],
        'Multi-Select' => [
          'label' => 'Pick Color',
          'html_type' => 'Multi-Select',
          'data_type' => 'String',
          'text_length' => '',
          'default_value' => '',
          'option_values' => [
            [
              'label' => 'Red',
              'value' => 'R',
              'weight' => 1,
              'is_active' => 1,
            ],
            [
              'label' => 'Yellow',
              'value' => 'Y',
              'weight' => 2,
              'is_active' => 1,
            ],
            [
              'label' => 'Green',
              'value' => 'G',
              'weight' => 3,
              'is_active' => 1,
            ],
          ],
        ],
        'Autocomplete-Select' => [
          'label' => 'Pick Color',
          'html_type' => 'Autocomplete-Select',
          'data_type' => 'String',
          'text_length' => '',
          'default_value' => '',
          'option_values' => [
            [
              'label' => 'Red',
              'value' => 'R',
              'weight' => 1,
              'is_active' => 1,
            ],
            [
              'label' => 'Yellow',
              'value' => 'Y',
              'weight' => 2,
              'is_active' => 1,
            ],
            [
              'label' => 'Green',
              'value' => 'G',
              'weight' => 3,
              'is_active' => 1,
            ],
          ],
        ],
      ],
      'Int' => [
        'default' => [
          'label' => 'Enter integer here',
          'html_type' => 'Text',
          'data_type' => 'Int',
          'default_value' => '4',
          'is_search_range' => 1,
        ],
        'Select' => [
          'label' => 'Integer select',
          'html_type' => 'Select',
          'option_values' => [
            [
              'label' => '50',
              'value' => 3,
              'weight' => 1,
              'is_active' => 1,
            ],
            [
              'label' => '100',
              'value' => 4,
              'weight' => 2,
              'is_active' => 1,
            ],
          ],
        ],
        'Radio' => [
          'label' => 'Integer radio',
          'html_type' => 'Radio',
          'option_values' => [
            [
              'label' => '50',
              'value' => 3,
              'weight' => 1,
              'is_active' => 1,
            ],
            [
              'label' => '100',
              'value' => 4,
              'weight' => 2,
              'is_active' => 1,
            ],
            [
              'label' => 'Red Testing',
              'value' => 5,
              'weight' => 3,
              'is_active' => 1,
            ],
          ],
        ],
      ],
      'Date' => [
        'default' => [
          'name' => 'test_date',
          'label' => 'Test Date',
          'html_type' => 'Select Date',
          'data_type' => 'Date',
          'default_value' => '20090711',
          'weight' => 3,
          'is_search_range' => 1,
          'time_format' => 1,
        ],
      ],
      'Float' => [
        'default' => [
          'label' => 'Number',
          'html_type' => 'Text',
          'data_type' => 'Float',
        ],
        'Select' => [
          'label' => 'Number select',
          'html_type' => 'Select',
          'option_values' => [
            [
              'label' => '50',
              'value' => 3,
              'weight' => 1,
              'is_active' => 1,
            ],
            [
              'label' => '100',
              'value' => 4,
              'weight' => 2,
              'is_active' => 1,
            ],
          ],
        ],
        'Radio' => [
          'label' => 'Number radio',
          'html_type' => 'Radio',
          'option_values' => [
            [
              'label' => '50',
              'value' => 3,
              'weight' => 1,
              'is_active' => 1,
            ],
            [
              'label' => '100',
              'value' => 4,
              'weight' => 2,
              'is_active' => 1,
            ],
          ],
        ],
      ],
      'Money' => [
        'default' => [
          'label' => 'Money',
          'html_type' => 'Text',
          'data_type' => 'Money',
        ],
        'Select' => [
          'label' => 'Money select',
          'html_type' => 'Select',
          'option_values' => [
            [
              'label' => '50',
              'value' => 3,
              'weight' => 1,
              'is_active' => 1,
            ],
            [
              'label' => '100',
              'value' => 4,
              'weight' => 2,
              'is_active' => 1,
            ],
          ],
        ],
        'Radio' => [
          'label' => 'Money radio',
          'html_type' => 'Radio',
          'option_values' => [
            [
              'label' => '50',
              'value' => 3,
              'weight' => 1,
              'is_active' => 1,
            ],
            [
              'label' => '100',
              'value' => 4,
              'weight' => 2,
              'is_active' => 1,
            ],
          ],
        ],
      ],
      'Memo' => [
        'default' => [
          'label' => 'Memo',
          'html_type' => 'TextArea',
          'data_type' => 'Memo',
          'attributes' => 'rows=4, cols=60',
        ],
        'RichTextEditor' => [
          'label' => 'Memo Rich Text Editor',
          'html_type' => 'Memo',
        ],
      ],
      'Boolean' => [
        'default' => [
          'data_type' => 'Boolean',
          'html_type' => 'Radio',
          'label' => 'Yes No',
        ],
      ],
      'StateProvince' => [
        'default' => [
          'data_type' => 'StateProvince',
          'html_type' => 'Select State/Province',
          'label' => 'State',
          'option_type' => 0,
        ],
        'Multi-Select State/Province' => [
          'html_type' => 'Multi-Select State/Province',
          'label' => 'State-multi',
        ],
      ],
      'Country' => [
        'default' => [
          'data_type' => 'Country',
          'html_type' => 'Select Country',
          'label' => 'Country',
          'option_type' => 0,
        ],
        'Multi-Select Country' => [
          'html_type' => 'Multi-Select Country',
          'label' => 'Country-multi',
          'option_type' => 0,
        ],
      ],
      'File' => [
        'default' => [
          'label' => 'My file',
          'data_type' => 'File',
          'html_type' => 'File',
        ],
      ],
      'Link' => [
        'default' => [
          'name' => 'test_link',
          'label' => 'test_link',
          'html_type' => 'Link',
          'data_type' => 'Link',
          'default_value' => 'https://civicrm.org',
        ],
      ],
      'ContactReference' => [
        'default' => [
          'label' => 'Contact reference field',
          'html_type' => 'Autocomplete-Select',
          'data_type' => 'ContactReference',
        ],
      ],
    ];
  }

}

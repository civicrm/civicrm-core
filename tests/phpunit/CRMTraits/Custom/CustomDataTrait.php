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
  public function createCustomGroupWithFieldsOfAllTypes($groupParams = []) {
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
  public function createCustomGroup($params = []) {
    $params = array_merge([
      'title' => 'Custom Group',
      'extends' => [$this->entity ?? 'Contact'],
      'weight' => 5,
      'style' => 'Inline',
      'max_multiple' => 0,
    ], $params);
    $identifier = $params['name'] ?? $params['title'];
    $this->ids['CustomGroup'][$identifier] = $this->callAPISuccess('CustomGroup', 'create', $params)['id'];
    return $this->ids['CustomGroup'][$identifier];
  }

  /**
   * Get the table_name for the specified custom group.
   *
   * @param string $identifier
   *
   * @return string
   */
  public function getCustomGroupTable($identifier = 'Custom Group') {
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
  protected function getCustomFieldColumnName($key) {
    return $this->callAPISuccessGetValue('CustomField', ['id' => $this->getCustomFieldID($key), 'return' => 'column_name']);
  }

  /**
   * Create a custom group with a single field.
   *
   * @param array $groupParams
   * @param string $customFieldType
   *
   * @param string $identifier
   *
   * @throws \CRM_Core_Exception
   */
  public function createCustomGroupWithFieldOfType($groupParams = [], $customFieldType = 'text', $identifier = NULL) {
    $supported = ['text', 'select', 'date', 'int'];
    if (!in_array($customFieldType, $supported, TRUE)) {
      throw new CRM_Core_Exception('we have not yet extracted other custom field types from createCustomFieldsOfAllTypes, Use consistent syntax when you do');
    }
    $groupParams['title'] = empty($groupParams['title']) ? $identifier . 'Group with field ' . $customFieldType : $groupParams['title'];
    $groupParams['name'] = $identifier ?? 'Custom Group';
    $this->createCustomGroup($groupParams);
    switch ($customFieldType) {
      case 'text':
        $customField = $this->createTextCustomField(['custom_group_id' => $this->ids['CustomGroup'][$groupParams['name']]]);
        break;

      case 'select':
        $customField = $this->createSelectCustomField(['custom_group_id' => $this->ids['CustomGroup'][$groupParams['name']]]);
        break;

      case 'int':
        $customField = $this->createIntCustomField(['custom_group_id' => $this->ids['CustomGroup'][$groupParams['name']]]);
        break;

      case 'date':
        $customField = $this->createDateCustomField(['custom_group_id' => $this->ids['CustomGroup'][$groupParams['name']]]);
        break;
    }
    $this->ids['CustomField'][$identifier . $customFieldType] = $customField['id'];
  }

  /**
   * @return array
   */
  public function createCustomFieldsOfAllTypes() {
    $customGroupID = $this->ids['CustomGroup']['Custom Group'];
    $ids = [];
    $customField = $this->createTextCustomField(['custom_group_id' => $customGroupID]);
    $ids['text'] = $customField['id'];

    if ((!empty($this->entity) && $this->entity !== 'Contribution') || empty($this->entity)) {
      $customField = $this->createSelectCustomField(['custom_group_id' => $customGroupID]);
      $ids['select_string'] = $customField['id'];
    }

    $customField = $this->createDateCustomField(['custom_group_id' => $customGroupID]);
    $ids['select_date'] = $customField['id'];

    $customField = $this->createIntCustomField(['custom_group_id' => $customGroupID]);
    $ids['int'] = $customField['id'];

    $params = [
      'custom_group_id' => $customGroupID,
      'name' => 'test_link',
      'label' => 'test_link',
      'html_type' => 'Link',
      'data_type' => 'Link',
      'default_value' => 'http://civicrm.org',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    ];

    $customField = $this->callAPISuccess('custom_field', 'create', $params);

    $ids['link'] = $customField['id'];
    $fileField = $this->customFieldCreate([
      'custom_group_id' => $customGroupID,
      'data_type' => 'File',
      'html_type' => 'File',
      'default_value' => '',
    ]);

    $ids['file'] = $fileField['id'];
    $ids['country'] = $this->customFieldCreate([
      'custom_group_id' => $customGroupID,
      'data_type' => 'Country',
      'html_type' => 'Select Country',
      'default_value' => '',
      'label' => 'Country',
      'option_type' => 0,
    ])['id'];

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
  protected function getCustomFieldName($key) {
    return 'custom_' . $this->getCustomFieldID($key);
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
  protected function getCustomFieldID($key) {
    return $this->ids['CustomField'][$key];
  }

  /**
   * Create a custom text fields.
   *
   * @param array $params
   *   Parameter overrides, must include custom_group_id.
   *
   * @return array
   */
  protected function createIntCustomField($params = []) {
    $params = array_merge([
      'label' => 'Enter integer here',
      'html_type' => 'Text',
      'data_type' => 'Int',
      'default_value' => '4',
      'weight' => 1,
      'is_required' => 1,
      'sequential' => 1,
      'is_searchable' => 1,
      'is_search_range' => 1,
    ], $params);

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
  protected function createTextCustomField($params = []) {
    $params = array_merge([
      'label' => 'Enter text here',
      'html_type' => 'Text',
      'data_type' => 'String',
      'default_value' => 'xyz',
      'weight' => 1,
      'is_required' => 1,
      'sequential' => 1,
      'is_searchable' => 1,
      'text_length' => 300,
    ], $params);

    return $this->callAPISuccess('CustomField', 'create', $params)['values'][0];
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
    $optionValue = [
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
    ];

    $params = array_merge([
      'label' => 'Pick Color',
      'html_type' => 'Select',
      'data_type' => 'String',
      'weight' => 2,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'option_values' => $optionValue,
    ], $params);

    $customField = $this->callAPISuccess('custom_field', 'create', $params);
    return $customField['values'][$customField['id']];
  }

  /**
   * Create a custom field of  type date.
   *
   * @param array $params
   *
   * @return array
   */
  protected function createDateCustomField($params): array {
    $params = array_merge([
      'name' => 'test_date',
      'label' => 'Test Date',
      'html_type' => 'Select Date',
      'data_type' => 'Date',
      'default_value' => '20090711',
      'weight' => 3,
      'is_searchable' => 1,
      'is_search_range' => 1,
      'time_format' => 1,
    ], $params);

    $customField = $this->callAPISuccess('custom_field', 'create', $params);
    return $customField['values'][$customField['id']];
  }

}

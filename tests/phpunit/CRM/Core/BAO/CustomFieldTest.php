<?php

/**
 * Class CRM_Core_BAO_CustomFieldTest
 *
 * @group headless
 */
class CRM_Core_BAO_CustomFieldTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  protected $customFieldID;

  /**
   * Clean up after test.
   *
   * @throws \Exception
   */
  public function tearDown() {
    $this->quickCleanup([], TRUE);
    parent::tearDown();
  }

  /**
   * Test creating a custom field.
   */
  public function testCreateCustomField() {
    $customGroup = $this->createCustomField();
    $customFieldID = $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customGroup['id'], 'id', 'custom_group_id',
      'Database check for created CustomField.'
    );
    $fields = [
      'id' => $customFieldID,
      'label' => 'editTestFld',
      'is_active' => 1,
      'data_type' => 'String',
      'html_type' => 'Text',
      'custom_group_id' => $customGroup['id'],
    ];

    CRM_Core_BAO_CustomField::create($fields);
    $this->assertDBNotNull('CRM_Core_DAO_CustomField', 1, 'id', 'is_active', 'Database check for edited CustomField.');
    $this->assertDBNotNull('CRM_Core_DAO_CustomField', $fields['label'], 'id', 'label', 'Database check for edited CustomField.');

    $dbFieldName = $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customFieldID, 'name', 'id', 'Database check for edited CustomField.');
    $dbColumnName = $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customFieldID, 'column_name', 'id', 'Database check for edited CustomField.');
    $this->assertEquals(strtolower("{$dbFieldName}_{$customFieldID}"), $dbColumnName,
      "Column name ends in ID");

    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Test custom field create accepts passed column name.
   */
  public function testCreateCustomFieldColumnName() {
    $customGroup = $this->customGroupCreate(['extends' => 'Individual']);
    $fields = [
      'label' => 'testFld 2',
      'column_name' => 'special_colname',
      'data_type' => 'String',
      'html_type' => 'Text',
      'custom_group_id' => $customGroup['id'],
    ];
    CRM_Core_BAO_CustomField::create($fields);
    $customFieldID = $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customGroup['id'], 'id', 'custom_group_id',
      'Database check for created CustomField.'
    );
    $dbColumnName = $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customFieldID, 'column_name', 'id', 'Database check for edited CustomField.');
    $this->assertEquals($fields['column_name'], $dbColumnName,
      "Column name set as specified");

    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Test that name is used for the column.
   */
  public function testCreateCustomFieldName() {
    $customGroup = $this->customGroupCreate(['extends' => 'Individual']);
    $fields = [
      'label' => 'testFld 2',
      'name' => 'special_fldlname',
      'data_type' => 'String',
      'html_type' => 'Text',
      'custom_group_id' => $customGroup['id'],
    ];
    CRM_Core_BAO_CustomField::create($fields);
    $customFieldID = $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customGroup['id'], 'id', 'custom_group_id',
      'Database check for created CustomField.'
    );
    $dbFieldName = $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customFieldID, 'name', 'id', 'Database check for edited CustomField.');
    $this->assertEquals($fields['name'], $dbFieldName,
      "Column name set as specified");

    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Test get fields function.
   */
  public function testGetFields() {
    $customGroup = $this->customGroupCreate(['extends' => 'Individual']);
    $fields = [
      'label' => 'testFld1',
      'data_type' => 'String',
      'html_type' => 'Text',
      'is_active' => 1,
      'custom_group_id' => $customGroup['id'],
    ];
    CRM_Core_BAO_CustomField::create($fields);
    $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customGroup['id'], 'id', 'custom_group_id',
      'Database check for created CustomField.'
    );
    $fields = [
      'label' => 'testFld2',
      'data_type' => 'String',
      'html_type' => 'Text',
      'is_active' => 1,
      'custom_group_id' => $customGroup['id'],
    ];
    CRM_Core_BAO_CustomField::create($fields);
    $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customGroup['id'], 'id', 'custom_group_id',
      'Database check for created CustomField.'
    );

    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * @throws \Exception
   */
  public function testGetDisplayedValues() {
    $customGroup = $this->customGroupCreate(['extends' => 'Individual']);
    $fieldsToCreate = [
      [
        'data_type' => 'Country',
        'html_type' => 'Select Country',
        'tests' => [
          'United States' => 1228,
          '' => NULL,
        ],
      ],
      [
        'data_type' => 'StateProvince',
        'html_type' => 'Multi-Select State/Province',
        'tests' => [
          '' => 0,
          'Alabama' => 1000,
          'Alabama, Alaska' => [1000, 1001],
        ],
      ],
      [
        'data_type' => 'String',
        'html_type' => 'Radio',
        'option_values' => [
          'key' => 'KeyLabel',
        ],
        'tests' => [
          'KeyLabel' => 'key',
        ],
      ],
      [
        'data_type' => 'String',
        'html_type' => 'CheckBox',
        'option_values' => [
          'key1' => 'Label1',
          'key2' => 'Label2',
          'key3' => 'Label3',
          'key4' => 'Label4',
        ],
        'tests' => [
          'Label1' => ['key1'],
          'Label2' => 'key2',
          'Label2, Label3' => ['key2', 'key3'],
          'Label3, Label4' => CRM_Utils_Array::implodePadded(['key3', 'key4']),
          'Label1, Label4' => ['key1' => 1, 'key4' => 1],
        ],
      ],
      [
        'data_type' => 'Date',
        'html_type' => 'Select Date',
        'date_format' => 'd M yy',
        'time_format' => 1,
        'tests' => [
          '1 Jun 1999 1:30PM' => '1999-06-01 13:30',
          '' => '',
        ],
      ],
    ];
    foreach ($fieldsToCreate as $num => $field) {
      $params = $field + ['label' => 'test field ' . $num, 'custom_group_id' => $customGroup['id']];
      unset($params['tests']);
      $createdField = $this->callAPISuccess('customField', 'create', $params);
      foreach ($field['tests'] as $expected => $input) {
        $this->assertEquals($expected, CRM_Core_BAO_CustomField::displayValue($input, $createdField['id']));
      }
    }

    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Test CRM_Core_BAO_CustomField::displayValue.
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function testGetDisplayedValuesContactRef() {
    $customGroup = $this->customGroupCreate(['extends' => 'Individual']);
    $params = [
      'data_type' => 'ContactReference',
      'html_type' => 'Autocomplete-Select',
      'label' => 'test ref',
      'custom_group_id' => $customGroup['id'],
    ];
    $createdField = $this->callAPISuccess('customField', 'create', $params);
    $contact1 = $this->individualCreate();
    $contact2 = $this->individualCreate(['custom_' . $createdField['id'] => $contact1['id']]);

    $this->assertEquals($contact1['display_name'], CRM_Core_BAO_CustomField::displayValue($contact2['id'], $createdField['id']));
    $this->assertEquals("Bob", CRM_Core_BAO_CustomField::displayValue("Bob", $createdField['id']));

    $this->contactDelete($contact2['id']);
    $this->contactDelete($contact1['id']);
    $this->customGroupDelete($customGroup['id']);
  }

  public function testDeleteCustomField() {
    $customGroup = $this->customGroupCreate(['extends' => 'Individual']);
    $fields = [
      'custom_group_id' => $customGroup['id'],
      'label' => 'Throwaway Field',
      'dataType' => 'Memo',
      'htmlType' => 'TextArea',
    ];

    $customField = $this->customFieldCreate($fields);
    $fieldObject = new CRM_Core_BAO_CustomField();
    $fieldObject->id = $customField['id'];
    $fieldObject->find(TRUE);
    CRM_Core_BAO_CustomField::deleteField($fieldObject);
    $this->assertDBNull('CRM_Core_DAO_CustomField', $customGroup['id'], 'id',
      'custom_group_id', 'Database check for deleted Custom Field.'
    );
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Move a custom field from $groupA to $groupB.
   *
   * Make sure that data records are correctly matched and created.
   *
   * @throws \CRM_Core_Exception
   */
  public function testMoveField() {
    $countriesByName = array_flip(CRM_Core_PseudoConstant::country(FALSE, FALSE));
    $this->assertTrue($countriesByName['Andorra'] > 0);
    $groups = [
      'A' => $this->customGroupCreate([
        'title' => 'Test_Group A',
        'name' => 'test_group_a',
        'extends' => ['Individual'],
        'style' => 'Inline',
        'is_multiple' => 0,
        'is_active' => 1,
        'version' => 3,
      ]),
      'B' => $this->customGroupCreate([
        'title' => 'Test_Group B',
        'name' => 'test_group_b',
        'extends' => ['Individual'],
        'style' => 'Inline',
        'is_multiple' => 0,
        'is_active' => 1,
        'version' => 3,
      ]),
    ];
    $groupA = $groups['A']['values'][$groups['A']['id']];
    $groupB = $groups['B']['values'][$groups['B']['id']];
    $countryA = $this->customFieldCreate([
      'custom_group_id' => $groups['A']['id'],
      'label' => 'Country A',
      'dataType' => 'Country',
      'htmlType' => 'Select Country',
      'default_value' => NULL,
    ]);
    $countryB = $this->customFieldCreate([
      'custom_group_id' => $groups['A']['id'],
      'label' => 'Country B',
      'dataType' => 'Country',
      'htmlType' => 'Select Country',
      'default_value' => NULL,
    ]);
    $countryC = $this->customFieldCreate([
      'custom_group_id' => $groups['B']['id'],
      'label' => 'Country C',
      'dataType' => 'Country',
      'htmlType' => 'Select Country',
      'default_value' => NULL,
    ]);

    $fields = [
      'countryA' => $countryA['values'][$countryA['id']],
      'countryB' => $countryB['values'][$countryB['id']],
      'countryC' => $countryC['values'][$countryC['id']],
    ];
    $contacts = [
      'alice' => $this->individualCreate([
        'first_name' => 'Alice',
        'last_name' => 'Albertson',
        'custom_' . $fields['countryA']['id'] => $countriesByName['Andorra'],
        'custom_' . $fields['countryB']['id'] => $countriesByName['Barbados'],
      ]),
      'bob' => $this->individualCreate([
        'first_name' => 'Bob',
        'last_name' => 'Roberts',
        'custom_' . $fields['countryA']['id'] => $countriesByName['Austria'],
        'custom_' . $fields['countryB']['id'] => $countriesByName['Bermuda'],
        'custom_' . $fields['countryC']['id'] => $countriesByName['Chad'],
      ]),
      'carol' => $this->individualCreate([
        'first_name' => 'Carol',
        'last_name' => 'Carolson',
        'custom_' . $fields['countryC']['id'] => $countriesByName['Cambodia'],
      ]),
    ];

    // Move!
    CRM_Core_BAO_CustomField::moveField($fields['countryB']['id'], $groupB['id']);

    // Group[A] no longer has fields[countryB]
    $errorScope = CRM_Core_TemporaryErrorScope::useException();
    try {
      $this->assertDBQuery(1, "SELECT {$fields['countryB']['column_name']} FROM " . $groupA['table_name']);
      $this->fail('Expected exception when querying column on wrong table');
    }
    catch (PEAR_Exception$e) {
    }
    $errorScope = NULL;

    // Alice: Group[B] has fields[countryB], but fields[countryC] did not exist before
    $this->assertDBQuery(1,
      "SELECT count(*) FROM {$groupB['table_name']}
            WHERE entity_id = %1
            AND {$fields['countryB']['column_name']} = %3
            AND {$fields['countryC']['column_name']} is null",
      [
        1 => [$contacts['alice'], 'Integer'],
        3 => [$countriesByName['Barbados'], 'Integer'],
      ]
    );

    // Bob: Group[B] has merged fields[countryB] and fields[countryC] on the same record
    $this->assertDBQuery(1,
      "SELECT count(*) FROM {$groupB['table_name']}
            WHERE entity_id = %1
            AND {$fields['countryB']['column_name']} = %3
            AND {$fields['countryC']['column_name']} = %4",
      [
        1 => [$contacts['bob'], 'Integer'],
        3 => [$countriesByName['Bermuda'], 'Integer'],
        4 => [$countriesByName['Chad'], 'Integer'],
      ]
    );

    // Carol: Group[B] still has fields[countryC] but did not get fields[countryB]
    $this->assertDBQuery(1,
      "SELECT count(*) FROM {$groupB['table_name']}
            WHERE entity_id = %1
            AND {$fields['countryB']['column_name']} is null
            AND {$fields['countryC']['column_name']} = %4",
      [
        1 => [$contacts['carol'], 'Integer'],
        4 => [$countriesByName['Cambodia'], 'Integer'],
      ]
    );

    $this->customGroupDelete($groups['A']['id']);
    $this->customGroupDelete($groupB['id']);
  }

  /**
   * Test get custom field id function.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function testGetCustomFieldID() {
    $this->createCustomField();
    $fieldID = CRM_Core_BAO_CustomField::getCustomFieldID('testFld');
    $this->assertEquals($this->customFieldID, $fieldID);

    $fieldID = CRM_Core_BAO_CustomField::getCustomFieldID('testFld', 'new custom group');
    $this->assertEquals($this->customFieldID, $fieldID);

    $fieldID = CRM_Core_BAO_CustomField::getCustomFieldID('testFld', 'new custom group', TRUE);
    $this->assertEquals('custom_' . $this->customFieldID, $fieldID);

    // create field with same name in a different group
    $this->createCustomField('other custom group');
    $otherFieldID = CRM_Core_BAO_CustomField::getCustomFieldID('testFld', 'other custom group');
    // make sure it does not return the field ID of the first field
    $this->assertNotEquals($fieldID, $otherFieldID);
  }

  /**
   * Create a custom field
   *
   * @param string $groupTitle
   *
   * @return array
   */
  protected function createCustomField($groupTitle = 'new custom group') {
    $customGroup = $this->customGroupCreate([
      'extends' => 'Individual',
      'title' => $groupTitle,
    ]);
    $fields = [
      'label' => 'testFld',
      'data_type' => 'String',
      'html_type' => 'Text',
      'custom_group_id' => $customGroup['id'],
    ];
    $field = CRM_Core_BAO_CustomField::create($fields);
    $this->customFieldID = $field->id;
    return $customGroup;
  }

  /**
   * Test the getFieldsForImport function.
   *
   * @throws \Exception
   */
  public function testGetFieldsForImport() {
    $this->entity = 'Contact';
    $this->createCustomGroupWithFieldsOfAllTypes();
    $customGroupID = $this->ids['CustomGroup']['Custom Group'];
    $expected = [
      $this->getCustomFieldName('country') => [
        'name' => $this->getCustomFieldName('country'),
        'type' => 1,
        'title' => 'Country',
        'headerPattern' => '//',
        'import' => 1,
        'custom_field_id' => $this->getCustomFieldID('country'),
        'options_per_line' => NULL,
        'text_length' => NULL,
        'data_type' => 'Country',
        'html_type' => 'Select Country',
        'is_search_range' => '0',
        'id' => $this->getCustomFieldID('country'),
        'label' => 'Country',
        'groupTitle' => 'Custom Group',
        'default_value' => NULL,
        'custom_group_id' => $customGroupID,
        'extends' => 'Contact',
        'extends_entity_column_value' => NULL,
        'extends_entity_column_id' => NULL,
        'is_view' => '0',
        'is_multiple' => '0',
        'option_group_id' => NULL,
        'date_format' => NULL,
        'time_format' => NULL,
        'is_required' => '0',
        'table_name' => 'civicrm_value_custom_group_' . $customGroupID,
        'column_name' => 'country_' . $this->getCustomFieldID('country'),
        'where' => 'civicrm_value_custom_group_' . $customGroupID . '.country_' . $this->getCustomFieldID('country'),
        'extends_table' => 'civicrm_contact',
        'search_table' => 'contact_a',
        'pseudoconstant' => [
          'table' => 'civicrm_country',
          'keyColumn' => 'id',
          'labelColumn' => 'name',
          'nameColumn' => 'iso_code',
        ],
      ],
      $this->getCustomFieldName('file') => [
        'name' => $this->getCustomFieldName('file'),
        'type' => 2,
        'title' => 'Custom Field',
        'headerPattern' => '//',
        'import' => 1,
        'custom_field_id' => $this->getCustomFieldID('file'),
        'options_per_line' => NULL,
        'text_length' => NULL,
        'data_type' => 'File',
        'html_type' => 'File',
        'is_search_range' => '0',
        'id' => $this->getCustomFieldID('file'),
        'label' => 'Custom Field',
        'groupTitle' => 'Custom Group',
        'default_value' => NULL,
        'custom_group_id' => $customGroupID,
        'extends' => 'Contact',
        'extends_entity_column_value' => NULL,
        'extends_entity_column_id' => NULL,
        'is_view' => '0',
        'is_multiple' => '0',
        'option_group_id' => NULL,
        'date_format' => NULL,
        'time_format' => NULL,
        'is_required' => '0',
        'table_name' => 'civicrm_value_custom_group_' . $customGroupID,
        'column_name' => 'custom_field_' . $this->getCustomFieldID('file'),
        'where' => 'civicrm_value_custom_group_' . $customGroupID . '.custom_field_' . $this->getCustomFieldID('file'),
        'extends_table' => 'civicrm_contact',
        'search_table' => 'contact_a',
      ],
      $this->getCustomFieldName('text') => [
        'name' => $this->getCustomFieldName('text'),
        'type' => 2,
        'title' => 'Enter text here',
        'headerPattern' => '//',
        'import' => 1,
        'custom_field_id' => $this->getCustomFieldID('text'),
        'options_per_line' => NULL,
        'text_length' => 300,
        'data_type' => 'String',
        'html_type' => 'Text',
        'is_search_range' => '0',
        'id' => $this->getCustomFieldID('text'),
        'label' => 'Enter text here',
        'groupTitle' => 'Custom Group',
        'default_value' => 'xyz',
        'custom_group_id' => '1',
        'extends' => 'Contact',
        'extends_entity_column_value' => NULL,
        'extends_entity_column_id' => NULL,
        'is_view' => '0',
        'is_multiple' => '0',
        'option_group_id' => NULL,
        'date_format' => NULL,
        'time_format' => NULL,
        'is_required' => '1',
        'table_name' => 'civicrm_value_custom_group_' . $customGroupID,
        'column_name' => 'enter_text_here_' . $this->getCustomFieldID('text'),
        'where' => 'civicrm_value_custom_group_' . $customGroupID . '.enter_text_here_' . $this->getCustomFieldID('text'),
        'extends_table' => 'civicrm_contact',
        'search_table' => 'contact_a',
        'maxlength' => 300,
      ],
      $this->getCustomFieldName('select_string') => [
        'name' => $this->getCustomFieldName('select_string'),
        'type' => 2,
        'title' => 'Pick Color',
        'headerPattern' => '//',
        'import' => 1,
        'custom_field_id' => $this->getCustomFieldID('select_string'),
        'options_per_line' => NULL,
        'text_length' => NULL,
        'data_type' => 'String',
        'html_type' => 'Select',
        'is_search_range' => '0',
        'id' => $this->getCustomFieldID('select_string'),
        'label' => 'Pick Color',
        'groupTitle' => 'Custom Group',
        'default_value' => NULL,
        'custom_group_id' => $customGroupID,
        'extends' => 'Contact',
        'extends_entity_column_value' => NULL,
        'extends_entity_column_id' => NULL,
        'is_view' => '0',
        'is_multiple' => '0',
        'option_group_id' => $this->callAPISuccessGetValue('CustomField', ['id' => $this->getCustomFieldID('select_string'), 'return' => 'option_group_id']),
        'date_format' => NULL,
        'time_format' => NULL,
        'is_required' => '1',
        'table_name' => 'civicrm_value_custom_group_' . $customGroupID,
        'column_name' => 'pick_color_' . $this->getCustomFieldID('select_string'),
        'where' => 'civicrm_value_custom_group_' . $customGroupID . '.pick_color_' . $this->getCustomFieldID('select_string'),
        'extends_table' => 'civicrm_contact',
        'search_table' => 'contact_a',
        'pseudoconstant' => [
          'optionGroupName' => $this->callAPISuccessGetValue('CustomField', ['id' => $this->getCustomFieldID('select_string'), 'return' => 'option_group_id.name']),
          'optionEditPath' => 'civicrm/admin/options/' . $this->callAPISuccessGetValue('CustomField', ['id' => $this->getCustomFieldID('select_string'), 'return' => 'option_group_id.name']),
        ],
      ],
      $this->getCustomFieldName('select_date') => [
        'name' => $this->getCustomFieldName('select_date'),
        'type' => 4,
        'title' => 'test_date',
        'headerPattern' => '//',
        'import' => 1,
        'custom_field_id' => $this->getCustomFieldID('select_date'),
        'options_per_line' => NULL,
        'text_length' => NULL,
        'data_type' => 'Date',
        'html_type' => 'Select Date',
        'is_search_range' => '0',
        'date_format' => 'mm/dd/yy',
        'time_format' => '1',
        'id' => $this->getCustomFieldID('select_date'),
        'label' => 'test_date',
        'groupTitle' => 'Custom Group',
        'default_value' => '20090711',
        'custom_group_id' => $customGroupID,
        'extends' => 'Contact',
        'extends_entity_column_value' => NULL,
        'extends_entity_column_id' => NULL,
        'is_view' => '0',
        'is_multiple' => '0',
        'option_group_id' => NULL,
        'is_required' => '0',
        'table_name' => 'civicrm_value_custom_group_' . $customGroupID,
        'column_name' => 'test_date_' . $this->getCustomFieldID('select_date'),
        'where' => 'civicrm_value_custom_group_' . $customGroupID . '.test_date_' . $this->getCustomFieldID('select_date'),
        'extends_table' => 'civicrm_contact',
        'search_table' => 'contact_a',
      ],
      $this->getCustomFieldName('link') => [
        'name' => $this->getCustomFieldName('link'),
        'type' => 2,
        'title' => 'test_link',
        'headerPattern' => '//',
        'import' => 1,
        'custom_field_id' => $this->getCustomFieldID('link'),
        'options_per_line' => NULL,
        'text_length' => NULL,
        'data_type' => 'Link',
        'html_type' => 'Link',
        'is_search_range' => '0',
        'id' => $this->getCustomFieldID('link'),
        'label' => 'test_link',
        'groupTitle' => 'Custom Group',
        'default_value' => 'http://civicrm.org',
        'custom_group_id' => $customGroupID,
        'extends' => 'Contact',
        'extends_entity_column_value' => NULL,
        'extends_entity_column_id' => NULL,
        'is_view' => '0',
        'is_multiple' => '0',
        'option_group_id' => NULL,
        'date_format' => NULL,
        'time_format' => NULL,
        'is_required' => '1',
        'table_name' => 'civicrm_value_custom_group_' . $customGroupID,
        'column_name' => 'test_link_' . $this->getCustomFieldID('link'),
        'where' => 'civicrm_value_custom_group_' . $customGroupID . '.test_link_' . $this->getCustomFieldID('link'),
        'extends_table' => 'civicrm_contact',
        'search_table' => 'contact_a',
      ],
    ];
    $this->assertEquals($expected, CRM_Core_BAO_CustomField::getFieldsForImport());
  }

  /**
   * Test the bulk create function works.
   */
  public function testBulkCreate() {
    $customGroup = $this->customGroupCreate([
      'extends' => 'Individual',
      'title' => 'my bulk group',
    ]);
    CRM_Core_BAO_CustomField::bulkSave([
      [
        'label' => 'Test',
        'data_type' => 'String',
        'html_type' => 'Text',
        'column_name' => 'my_text',
      ],
      [
        'label' => 'test_link',
        'data_type' => 'Link',
        'html_type' => 'Link',
        'is_search_range' => '0',
      ],
    ],
    [
      'custom_group_id' => $customGroup['id'],
      'is_active' => 1,
      'is_searchable' => 1,
    ]);
    $dao = CRM_Core_DAO::executeQuery(('SHOW CREATE TABLE ' . $customGroup['values'][$customGroup['id']]['table_name']));
    $dao->fetch();
    $this->assertContains('`test_link_2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL', $dao->Create_Table);
    $this->assertContains('KEY `INDEX_my_text` (`my_text`)', $dao->Create_Table);
  }

  /**
   * Check that outputting the display value for a file field with No description doesn't generate error
   */
  public function testFileDisplayValueNoDescription() {
    $customGroup = $this->customGroupCreate([
      'extends' => 'Individual',
      'title' => 'Test Contact File Custom Group',
    ]);
    $fileField = $this->customFieldCreate([
      'custom_group_id' => $customGroup['id'],
      'data_type' => 'File',
      'html_type' => 'File',
      'default_value' => '',
    ]);
    $filePath = Civi::paths()->getPath('[civicrm.files]/custom/test_file.txt');
    $file = $this->callAPISuccess('File', 'create', [
      'uri' => $filePath,
    ]);
    $individual = $this->individualCreate(['custom_' . $fileField['id'] => $file['id']]);
    $expectedDisplayValue = CRM_Core_BAO_File::paperIconAttachment('*', $file['id'])[$file['id']];
    $this->assertEquals($expectedDisplayValue, CRM_Core_BAO_CustomField::displayValue($file['id'], $fileField['id']));
  }

}

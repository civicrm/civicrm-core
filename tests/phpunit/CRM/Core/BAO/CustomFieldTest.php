<?php

/**
 * Class CRM_Core_BAO_CustomFieldTest
 * @group headless
 */
class CRM_Core_BAO_CustomFieldTest extends CiviUnitTestCase {

  protected $customFieldID;

  public function setUp() {
    parent::setUp();
  }

  public function testCreateCustomField() {
    $customGroup = $this->createCustomField();
    $customFieldID = $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customGroup['id'], 'id', 'custom_group_id',
      'Database check for created CustomField.'
    );
    $fields = array(
      'id' => $customFieldID,
      'label' => 'editTestFld',
      'is_active' => 1,
      'data_type' => 'String',
      'html_type' => 'Text',
      'custom_group_id' => $customGroup['id'],
    );

    CRM_Core_BAO_CustomField::create($fields);
    $this->assertDBNotNull('CRM_Core_DAO_CustomField', 1, 'id', 'is_active', 'Database check for edited CustomField.');
    $this->assertDBNotNull('CRM_Core_DAO_CustomField', $fields['label'], 'id', 'label', 'Database check for edited CustomField.');

    $dbFieldName = $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customFieldID, 'name', 'id', 'Database check for edited CustomField.');
    $dbColumnName = $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customFieldID, 'column_name', 'id', 'Database check for edited CustomField.');
    $this->assertEquals(strtolower("{$dbFieldName}_{$customFieldID}"), $dbColumnName,
      "Column name ends in ID");

    $this->customGroupDelete($customGroup['id']);
  }

  public function testCreateCustomFieldColumnName() {
    $customGroup = $this->customGroupCreate(array('extends' => 'Individual'));
    $fields = array(
      'label' => 'testFld 2',
      'column_name' => 'special_colname',
      'data_type' => 'String',
      'html_type' => 'Text',
      'custom_group_id' => $customGroup['id'],
    );
    CRM_Core_BAO_CustomField::create($fields);
    $customFieldID = $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customGroup['id'], 'id', 'custom_group_id',
      'Database check for created CustomField.'
    );
    $dbColumnName = $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customFieldID, 'column_name', 'id', 'Database check for edited CustomField.');
    $this->assertEquals($fields['column_name'], $dbColumnName,
      "Column name set as specified");

    $this->customGroupDelete($customGroup['id']);
  }

  public function testCreateCustomFieldName() {
    $customGroup = $this->customGroupCreate(array('extends' => 'Individual'));
    $fields = array(
      'label' => 'testFld 2',
      'name' => 'special_fldlname',
      'data_type' => 'String',
      'html_type' => 'Text',
      'custom_group_id' => $customGroup['id'],
    );
    CRM_Core_BAO_CustomField::create($fields);
    $customFieldID = $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customGroup['id'], 'id', 'custom_group_id',
      'Database check for created CustomField.'
    );
    $dbFieldName = $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customFieldID, 'name', 'id', 'Database check for edited CustomField.');
    $this->assertEquals($fields['name'], $dbFieldName,
      "Column name set as specified");

    $this->customGroupDelete($customGroup['id']);
  }

  public function testGetFields() {
    $customGroup = $this->customGroupCreate(array('extends' => 'Individual'));
    $fields = array(
      'label' => 'testFld1',
      'data_type' => 'String',
      'html_type' => 'Text',
      'is_active' => 1,
      'custom_group_id' => $customGroup['id'],
    );
    CRM_Core_BAO_CustomField::create($fields);
    $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customGroup['id'], 'id', 'custom_group_id',
      'Database check for created CustomField.'
    );
    $fields = array(
      'label' => 'testFld2',
      'data_type' => 'String',
      'html_type' => 'Text',
      'is_active' => 1,
      'custom_group_id' => $customGroup['id'],
    );
    CRM_Core_BAO_CustomField::create($fields);
    $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customGroup['id'], 'id', 'custom_group_id',
      'Database check for created CustomField.'
    );

    $this->customGroupDelete($customGroup['id']);
  }

  public function testGetDisplayedValues() {
    $customGroup = $this->customGroupCreate(array('extends' => 'Individual'));
    $fieldsToCreate = array(
      array(
        'data_type' => 'Country',
        'html_type' => 'Select Country',
        'tests' => array(
          'United States' => 1228,
          '' => NULL,
        ),
      ),
      array(
        'data_type' => 'StateProvince',
        'html_type' => 'Multi-Select State/Province',
        'tests' => array(
          '' => 0,
          'Alabama' => 1000,
          'Alabama, Alaska' => array(1000, 1001),
        ),
      ),
      array(
        'data_type' => 'String',
        'html_type' => 'Radio',
        'option_values' => array(
          'key' => 'KeyLabel',
        ),
        'tests' => array(
          'KeyLabel' => 'key',
        ),
      ),
      array(
        'data_type' => 'String',
        'html_type' => 'CheckBox',
        'option_values' => array(
          'key1' => 'Label1',
          'key2' => 'Label2',
          'key3' => 'Label3',
          'key4' => 'Label4',
        ),
        'tests' => array(
          'Label1' => array('key1'),
          'Label2' => 'key2',
          'Label2, Label3' => array('key2', 'key3'),
          'Label3, Label4' => CRM_Utils_Array::implodePadded(array('key3', 'key4')),
          'Label1, Label4' => array('key1' => 1, 'key4' => 1),
        ),
      ),
      array(
        'data_type' => 'Date',
        'html_type' => 'Select Date',
        'date_format' => 'd M yy',
        'time_format' => 1,
        'tests' => array(
          '1 Jun 1999 1:30PM' => '1999-06-01 13:30',
          '' => '',
        ),
      ),
    );
    foreach ($fieldsToCreate as $num => $field) {
      $params = $field + array(
        'label' => 'test field ' . $num,
        'custom_group_id' => $customGroup['id'],
      );
      unset($params['tests']);
      $createdField = $this->callAPISuccess('customField', 'create', $params);
      foreach ($field['tests'] as $expected => $input) {
        $this->assertEquals($expected, CRM_Core_BAO_CustomField::displayValue($input, $createdField['id']));
      }
    }

    $this->customGroupDelete($customGroup['id']);
  }

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
    $customGroup = $this->customGroupCreate(array('extends' => 'Individual'));
    $fields = array(
      'custom_group_id' => $customGroup['id'],
      'label' => 'Throwaway Field',
      'dataType' => 'Memo',
      'htmlType' => 'TextArea',
    );

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
   */
  public function testMoveField() {
    $countriesByName = array_flip(CRM_Core_PseudoConstant::country(FALSE, FALSE));
    $this->assertTrue($countriesByName['Andorra'] > 0);
    $groups = array(
      'A' => $this->customGroupCreate(array(
        'title' => 'Test_Group A',
        'name' => 'test_group_a',
        'extends' => array('Individual'),
        'style' => 'Inline',
        'is_multiple' => 0,
        'is_active' => 1,
        'version' => 3,
      )),
      'B' => $this->customGroupCreate(array(
        'title' => 'Test_Group B',
        'name' => 'test_group_b',
        'extends' => array('Individual'),
        'style' => 'Inline',
        'is_multiple' => 0,
        'is_active' => 1,
        'version' => 3,
      )),
    );
    $groupA = $groups['A']['values'][$groups['A']['id']];
    $groupB = $groups['B']['values'][$groups['B']['id']];
    $countryA = $this->customFieldCreate(array(
      'custom_group_id' => $groups['A']['id'],
      'label' => 'Country A',
      'dataType' => 'Country',
      'htmlType' => 'Select Country',
      'default_value' => NULL,
    ));
    $countryB = $this->customFieldCreate(array(
      'custom_group_id' => $groups['A']['id'],
      'label' => 'Country B',
      'dataType' => 'Country',
      'htmlType' => 'Select Country',
      'default_value' => NULL,
    ));
    $countryC = $this->customFieldCreate(array(
      'custom_group_id' => $groups['B']['id'],
      'label' => 'Country C',
      'dataType' => 'Country',
      'htmlType' => 'Select Country',
      'default_value' => NULL,
    ));

    $fields = array(
      'countryA' => $countryA['values'][$countryA['id']],
      'countryB' => $countryB['values'][$countryB['id']],
      'countryC' => $countryC['values'][$countryC['id']],
    );
    $contacts = array(
      'alice' => $this->individualCreate(array(
        'first_name' => 'Alice',
        'last_name' => 'Albertson',
        'custom_' . $fields['countryA']['id'] => $countriesByName['Andorra'],
        'custom_' . $fields['countryB']['id'] => $countriesByName['Barbados'],
      )),
      'bob' => $this->individualCreate(array(
        'first_name' => 'Bob',
        'last_name' => 'Roberts',
        'custom_' . $fields['countryA']['id'] => $countriesByName['Austria'],
        'custom_' . $fields['countryB']['id'] => $countriesByName['Bermuda'],
        'custom_' . $fields['countryC']['id'] => $countriesByName['Chad'],
      )),
      'carol' => $this->individualCreate(array(
        'first_name' => 'Carol',
        'last_name' => 'Carolson',
        'custom_' . $fields['countryC']['id'] => $countriesByName['Cambodia'],
      )),
    );

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
      array(
        1 => array($contacts['alice'], 'Integer'),
        3 => array($countriesByName['Barbados'], 'Integer'),
      )
    );

    // Bob: Group[B] has merged fields[countryB] and fields[countryC] on the same record
    $this->assertDBQuery(1,
      "SELECT count(*) FROM {$groupB['table_name']}
            WHERE entity_id = %1
            AND {$fields['countryB']['column_name']} = %3
            AND {$fields['countryC']['column_name']} = %4",
      array(
        1 => array($contacts['bob'], 'Integer'),
        3 => array($countriesByName['Bermuda'], 'Integer'),
        4 => array($countriesByName['Chad'], 'Integer'),
      )
    );

    // Carol: Group[B] still has fields[countryC] but did not get fields[countryB]
    $this->assertDBQuery(1,
      "SELECT count(*) FROM {$groupB['table_name']}
            WHERE entity_id = %1
            AND {$fields['countryB']['column_name']} is null
            AND {$fields['countryC']['column_name']} = %4",
      array(
        1 => array($contacts['carol'], 'Integer'),
        4 => array($countriesByName['Cambodia'], 'Integer'),
      )
    );

    $this->customGroupDelete($groups['A']['id']);
    $this->customGroupDelete($groupB['id']);
  }

  /**
   * Test get custom field id function.
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
      'title'   => $groupTitle,
    ]);
    $fields = array(
      'label' => 'testFld',
      'data_type' => 'String',
      'html_type' => 'Text',
      'custom_group_id' => $customGroup['id'],
    );
    $field = CRM_Core_BAO_CustomField::create($fields);
    $this->customFieldID = $field->id;
    return $customGroup;
  }

}

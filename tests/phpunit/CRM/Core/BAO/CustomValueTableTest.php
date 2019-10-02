<?php

/**
 * Class CRM_Core_BAO_CustomValueTableTest
 * @group headless
 */
class CRM_Core_BAO_CustomValueTableTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Test store function for country.
   */
  public function testStoreCountry() {
    $params = [];
    $contactID = $this->individualCreate();
    $customGroup = $this->customGroupCreate();
    $fields = [
      'custom_group_id' => $customGroup['id'],
      'data_type' => 'Country',
      'html_type' => 'Select Country',
      'default_value' => '',
    ];

    $customField = $this->customFieldCreate($fields);

    $params[] = [
      $customField['id'] => [
        'value' => 1228,
        'type' => 'Country',
        'custom_field_id' => $customField['id'],
        'custom_group_id' => $customGroup['id'],
        'table_name' => $customGroup['values'][$customGroup['id']]['table_name'],
        'column_name' => $customField['values'][$customField['id']]['column_name'],
        'file_id' => '',
      ],
    ];

    CRM_Core_BAO_CustomValueTable::store($params, 'civicrm_contact', $contactID);

    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
    $this->contactDelete($contactID);
  }

  /**
   * Test store function for file.
   */
  public function testStoreFile() {
    $contactID = $this->individualCreate();
    $file = $this->callAPISuccess('File', 'create', ['uri' => 'dummy_data']);
    $customGroup = $this->customGroupCreate();
    $fields = [
      'custom_group_id' => $customGroup['id'],
      'data_type' => 'File',
      'html_type' => 'File',
      'default_value' => '',
    ];

    $customField = $this->customFieldCreate($fields);

    $params[] = [
      $customField['id'] => [
        'value' => 'i/contact_house.png',
        'type' => 'File',
        'custom_field_id' => $customField['id'],
        'custom_group_id' => $customGroup['id'],
        'table_name' => $customGroup['values'][$customGroup['id']]['table_name'],
        'column_name' => $customField['values'][$customField['id']]['column_name'],
        'file_id' => $file['id'],
      ],
    ];

    CRM_Core_BAO_CustomValueTable::store($params, 'civicrm_contact', $contactID);

    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
    $this->contactDelete($contactID);
  }

  /**
   * Test store function for state province.
   */
  public function testStoreStateProvince() {
    $contactID = $this->individualCreate();
    $customGroup = $this->customGroupCreate();
    $fields = [
      'custom_group_id' => $customGroup['id'],
      'data_type' => 'StateProvince',
      'html_type' => 'Select State/Province',
      'default_value' => '',
    ];

    $customField = $this->customFieldCreate($fields);

    $params[] = [
      $customField['id'] => [
        'value' => 1029,
        'type' => 'StateProvince',
        'custom_field_id' => $customField['id'],
        'custom_group_id' => $customGroup['id'],
        'table_name' => $customGroup['values'][$customGroup['id']]['table_name'],
        'column_name' => $customField['values'][$customField['id']]['column_name'],
        'file_id' => 1,
      ],
    ];

    CRM_Core_BAO_CustomValueTable::store($params, 'civicrm_contact', $contactID);

    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
    $this->contactDelete($contactID);
  }

  /**
   * Test store function for date.
   */
  public function testStoreDate() {
    $params = [];
    $contactID = $this->individualCreate();
    $customGroup = $this->customGroupCreate();
    $fields = [
      'custom_group_id' => $customGroup['id'],
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'default_value' => '',
    ];

    $customField = $this->customFieldCreate($fields);

    $params[] = [
      $customField['id'] => [
        'value' => '20080608000000',
        'type' => 'Date',
        'custom_field_id' => $customField['id'],
        'custom_group_id' => $customGroup['id'],
        'table_name' => $customGroup['values'][$customGroup['id']]['table_name'],
        'column_name' => $customField['values'][$customField['id']]['column_name'],
        'file_id' => '',
      ],
    ];

    CRM_Core_BAO_CustomValueTable::store($params, 'civicrm_contact', $contactID);

    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
    $this->contactDelete($contactID);
  }

  /**
   * Test store function for rich text editor.
   */
  public function testStoreRichTextEditor() {
    $params = [];
    $contactID = $this->individualCreate();
    $customGroup = $this->customGroupCreate();
    $fields = [
      'custom_group_id' => $customGroup['id'],
      'html_type' => 'RichTextEditor',
      'data_type' => 'Memo',
    ];

    $customField = $this->customFieldCreate($fields);

    $params[] = [
      $customField['id'] => [
        'value' => '<p><strong>This is a <u>test</u></p>',
        'type' => 'Memo',
        'custom_field_id' => $customField['id'],
        'custom_group_id' => $customGroup['id'],
        'table_name' => $customGroup['values'][$customGroup['id']]['table_name'],
        'column_name' => $customField['values'][$customField['id']]['column_name'],
        'file_id' => '',
      ],
    ];

    CRM_Core_BAO_CustomValueTable::store($params, 'civicrm_contact', $contactID);

    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
    $this->contactDelete($contactID);
  }

  /**
   * Test getEntityValues function for stored value.
   */
  public function testGetEntityValues() {

    $params = [];
    $contactID = $this->individualCreate();
    $customGroup = $this->customGroupCreate(['extends' => 'Individual']);
    $fields = [
      'custom_group_id' => $customGroup['id'],
      'html_type' => 'RichTextEditor',
      'data_type' => 'Memo',
    ];

    $customField = $this->customFieldCreate($fields);

    $params[] = [
      $customField['id'] => [
        'value' => '<p><strong>This is a <u>test</u></p>',
        'type' => 'Memo',
        'custom_field_id' => $customField['id'],
        'custom_group_id' => $customGroup['id'],
        'table_name' => $customGroup['values'][$customGroup['id']]['table_name'],
        'column_name' => $customField['values'][$customField['id']]['column_name'],
        'file_id' => '',
      ],
    ];

    CRM_Core_BAO_CustomValueTable::store($params, 'civicrm_contact', $contactID);

    $entityValues = CRM_Core_BAO_CustomValueTable::getEntityValues($contactID, 'Individual');

    $this->assertEquals($entityValues[$customField['id']], '<p><strong>This is a <u>test</u></p>',
      'Checking same for returned value.'
    );
    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
    $this->contactDelete($contactID);
  }

  public function testCustomGroupMultiple() {
    $params = [];
    $contactID = $this->individualCreate();
    $customGroup = $this->customGroupCreate();

    $fields = [
      'custom_group_id' => $customGroup['id'],
      'data_type' => 'String',
      'html_type' => 'Text',
    ];

    $customField = $this->customFieldCreate($fields);

    $params = [
      'entityID' => $contactID,
      'custom_' . $customField['id'] . '_-1' => 'First String',
    ];
    $error = CRM_Core_BAO_CustomValueTable::setValues($params);

    $newParams = [
      'entityID' => $contactID,
      'custom_' . $customField['id'] => 1,
    ];
    $result = CRM_Core_BAO_CustomValueTable::getValues($newParams);

    $this->assertEquals($params['custom_' . $customField['id'] . '_-1'], $result['custom_' . $customField['id']]);
    $this->assertEquals($params['entityID'], $result['entityID']);

    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
    $this->contactDelete($contactID);
  }

}

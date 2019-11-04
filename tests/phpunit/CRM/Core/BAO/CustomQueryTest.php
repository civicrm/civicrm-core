<?php

/**
 *  Include dataProvider for tests
 * @group headless
 */
class CRM_Core_BAO_CustomQueryTest extends CiviUnitTestCase {
  use CRMTraits_Custom_CustomDataTrait;

  /**
   * Restore database to empty state.
   *
   * Note that rollback won't remove custom tables.
   *
   * @throws \Exception
   */
  public function tearDown() {
    $tablesToTruncate = [
      'civicrm_contact',
    ];
    $this->quickCleanup($tablesToTruncate, TRUE);
    parent::tearDown();
  }

  /**
   * Test filtering by relative custom data dates.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchCustomDataDateRelative() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'ContactTestTest');
    $dateCustomField = $this->customFieldCreate([
      'custom_group_id' => $ids['custom_group_id'],
      'label' => 'date field',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'default_value' => NULL,
    ]);
    $dateCustomFieldName = 'custom_' . $dateCustomField['id'];
    $formValues = [
      $dateCustomFieldName . '_relative' => 'this.year',
      $dateCustomFieldName . '_from' => '',
      $dateCustomFieldName . '_to' => '',
    ];
    // Assigning the relevant form value to be within a custom key is normally done in
    // build field params. It would be better if it were all done in convertFormValues
    // but for now we just imitate it.

    $params = CRM_Contact_BAO_Query::convertFormValues($formValues);
    $queryObj = new CRM_Contact_BAO_Query($params);
    $this->assertEquals(
      "civicrm_value_testsearchcus_1.date_field_2 BETWEEN '" . date('Y') . "0101000000' AND '" . date('Y') . "1231235959'",
      $queryObj->_where[0][0]
    );
    $this->assertEquals('date field is This calendar year (between January 1st, ' . date('Y') . " 12:00 AM and December 31st, " . date('Y') . " 11:59 PM)", $queryObj->_qill[0][0]);
    $queryObj = new CRM_Contact_BAO_Query($params);
    $this->assertEquals([
      'id' => $dateCustomField['id'],
      'label' => 'date field',
      'extends' => 'Contact',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'is_search_range' => '0',
      'column_name' => 'date_field_' . $dateCustomField['id'],
      'table_name' => 'civicrm_value_testsearchcus_' . $ids['custom_group_id'],
      'option_group_id' => NULL,
      'groupTitle' => 'testSearchCustomDataDateRelative',
      'default_value' => NULL,
      'text_length' => NULL,
      'options_per_line' => NULL,
      'custom_group_id' => '1',
      'extends_entity_column_value' => NULL,
      'extends_entity_column_id' => NULL,
      'is_view' => '0',
      'is_multiple' => '0',
      'date_format' => 'mm/dd/yy',
      'time_format' => NULL,
      'is_required' => '0',
      'extends_table' => 'civicrm_contact',
      'search_table' => 'contact_a',
      'headerPattern' => '//',
      'title' => 'date field',
      'custom_field_id' => $dateCustomField['id'],
      'name' => 'custom_' . $dateCustomField['id'],
      'type' => 4,
      'where' => 'civicrm_value_testsearchcus_' . $ids['custom_group_id'] . '.date_field_' . $dateCustomField['id'],
      'import' => 1,
    ], $queryObj->getFieldSpec('custom_' . $dateCustomField['id']));

  }

  /**
   * Test filtering by the renamed custom date fields.
   *
   * The conversion to date picker will result int these fields
   * being renamed _high & _low and needing to return correctly.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchCustomDataDateHighLow() {
    $this->createCustomGroupWithFieldOfType([], 'date');
    $dateCustomFieldName = $this->getCustomFieldName('date');
    // Assigning the relevant form value to be within a custom key is normally done in
    // build field params. It would be better if it were all done in convertFormValues
    // but for now we just imitate it.
    $formValues = [
      $dateCustomFieldName . '_low' => '2014-06-06',
      $dateCustomFieldName . '_high' => '2015-06-06',
    ];

    $params = CRM_Contact_BAO_Query::convertFormValues($formValues);
    $queryObject = new CRM_Contact_BAO_Query($params);
    $queryObject->query();
    $this->assertEquals(
      '( civicrm_value_group_with_fi_1.' . $this->getCustomFieldColumnName('date') . ' >= \'20140606000000\' ) AND
( civicrm_value_group_with_fi_1.' . $this->getCustomFieldColumnName('date') . ' <= \'20150606235959\' )',
      trim($queryObject->_where[0][0])
    );
    $this->assertEquals('Test Date - greater than or equal to "June 6th, 2014 12:00 AM" AND less than or equal to "June 6th, 2015 11:59 PM"', $queryObject->_qill[0][0]);
    $this->assertEquals(1, $queryObject->_whereTables['civicrm_contact']);
    $this->assertEquals('LEFT JOIN ' . $this->getCustomGroupTable() . ' ON ' . $this->getCustomGroupTable() . '.entity_id = `contact_a`.id', trim($queryObject->_whereTables[$this->getCustomGroupTable()]));
  }

  /**
   * Test filtering by the renamed custom date fields.
   *
   * The conversion to date picker will result int these fields
   * being renamed _high & _low and needing to return correctly.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchCustomDataDateLowWithPermsInPlay() {
    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['view all contacts', 'access all custom data'];
    $this->createCustomGroupWithFieldOfType([], 'date');
    $dateCustomFieldName = $this->getCustomFieldName('date');
    // Assigning the relevant form value to be within a custom key is normally done in
    // build field params. It would be better if it were all done in convertFormValues
    // but for now we just imitate it.
    $formValues = [
      $dateCustomFieldName . '_low' => '2014-06-06',
    ];

    $params = CRM_Contact_BAO_Query::convertFormValues($formValues);
    $queryObject = new CRM_Contact_BAO_Query($params);
    $queryObject->query();
    $this->assertEquals(
      'civicrm_value_group_with_fi_1.' . $this->getCustomFieldColumnName('date') . ' >= \'20140606000000\'',
      trim($queryObject->_where[0][0])
    );
    $this->assertEquals(
      'FROM civicrm_contact contact_a   LEFT JOIN civicrm_address ON ( contact_a.id = civicrm_address.contact_id AND civicrm_address.is_primary = 1 )  LEFT JOIN civicrm_country ON ( civicrm_address.country_id = civicrm_country.id )  LEFT JOIN civicrm_email ON (contact_a.id = civicrm_email.contact_id AND civicrm_email.is_primary = 1)  LEFT JOIN civicrm_phone ON (contact_a.id = civicrm_phone.contact_id AND civicrm_phone.is_primary = 1)  LEFT JOIN civicrm_im ON (contact_a.id = civicrm_im.contact_id AND civicrm_im.is_primary = 1)  LEFT JOIN civicrm_worldregion ON civicrm_country.region_id = civicrm_worldregion.id  
LEFT JOIN ' . $this->getCustomGroupTable() . ' ON ' . $this->getCustomGroupTable() . '.entity_id = `contact_a`.id',
      trim($queryObject->_fromClause)
    );
    $this->assertEquals('Test Date - greater than or equal to "June 6th, 2014 12:00 AM"', $queryObject->_qill[0][0]);
    $this->assertEquals(1, $queryObject->_whereTables['civicrm_contact']);
    $this->assertEquals('LEFT JOIN ' . $this->getCustomGroupTable() . ' ON ' . $this->getCustomGroupTable() . '.entity_id = `contact_a`.id', trim($queryObject->_whereTables[$this->getCustomGroupTable()]));
  }

  /**
   * Test filtering by relative custom data dates.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchCustomDataDateFromTo() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'ContactTestTest');
    $dateCustomField = $this->customFieldCreate([
      'custom_group_id' => $ids['custom_group_id'],
      'label' => 'date field',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'default_value' => NULL,
    ]);
    $dateCustomFieldName = 'custom_' . $dateCustomField['id'];
    // Assigning the relevant form value to be within a custom key is normally done in
    // build field params. It would be better if it were all done in convertFormValues
    // but for now we just imitate it.
    $formValues = [
      $dateCustomFieldName . '_from' => '2014-06-06',
      $dateCustomFieldName . '_to' => '2015-06-06',
    ];

    $params = CRM_Contact_BAO_Query::convertFormValues($formValues);
    $queryObj = new CRM_Contact_BAO_Query($params);
    $queryObj->query();
    $this->assertEquals(
      'civicrm_value_testsearchcus_1.date_field_2 BETWEEN "20140606000000" AND "20150606235959"',
      $queryObj->_where[0][0]
    );
    $this->assertEquals($queryObj->_qill[0][0], "date field BETWEEN 'June 6th, 2014 12:00 AM AND June 6th, 2015 11:59 PM'");

    //CRM-17236 - Test custom date is correctly displayed without time.
    $formattedValue = CRM_Core_BAO_CustomField::displayValue(date('Ymdhms'), $dateCustomField['id']);
    $this->assertEquals(date('m/d/Y'), $formattedValue);
  }

  /**
   * Test filtering by relative custom data.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchCustomDataFromTo() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'ContactTestTest');
    $datas = [
      'Int' => 2,
      'Float' => 12.123,
      'Money' => 91.21,
    ];
    foreach ($datas as $type => $data) {
      $customField = $this->customFieldCreate(
        [
          'custom_group_id' => $ids['custom_group_id'],
          'label' => "$type field",
          'data_type' => $type,
          'html_type' => 'Text',
          'default_value' => NULL,
        ]
      );
      $customFieldName = 'custom_' . $customField['id'];
      // Assigning the relevant form value to be within a custom key is normally done in
      // build field params. It would be better if it were all done in convertFormValues
      // but for now we just imitate it.
      $from = $data - 1;
      $to = $data;
      $formValues = [
        $customFieldName . '_from' => $from,
        $customFieldName . '_to' => $to,
      ];

      $params = CRM_Contact_BAO_Query::convertFormValues($formValues);
      $queryObj = new CRM_Contact_BAO_Query($params);
      $queryObj->query();
      $this->assertEquals(
        'civicrm_value_testsearchcus_1.' . strtolower($type) . "_field_{$customField['id']} BETWEEN \"$from\" AND \"$to\"",
        $queryObj->_where[0][0]
      );
      $this->assertEquals($queryObj->_qill[0][0], "$type field BETWEEN $from, $to");
    }
  }

  /**
   * Test filtering by relative custom data.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchCustomDataFromAndTo() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'ContactTestTest');
    $dataSet = [
      'Date' => ['value' => '2015-06-06', 'sql_string' => '"20150606235959"', 'qill_string' => "'June 6th, 2015 11:59 PM'", 'qill_string_greater' => "'June 6th, 2015 12:00 AM'"],
      // @todo - investigate the impact of using quotes on what should be an integer field.
      'Int' => ['value' => 2, 'sql_string' => '"2"'],
      'Float' => ['value' => 12.123, 'sql_string' => '"12.123"'],
      'Money' => ['value' => 91.21],
    ];
    foreach ($dataSet as $type => $values) {
      $data = $values['value'];
      $isDate = ($type === 'Date');
      $customField = $this->customFieldCreate(
        [
          'custom_group_id' => $ids['custom_group_id'],
          'label' => "$type field",
          'data_type' => $type,
          'html_type' => ($isDate) ? 'Select Date' : 'Text',
          'default_value' => NULL,
        ]
      );
      $customFieldName = 'custom_' . $customField['id'];

      $expectedValue = $values['sql_string'] ?? $data;
      $expectedQillValue = $values['qill_string'] ?? $data;
      $toQillValue = chr(226) . chr(137) . chr(164) . ' ' . $expectedQillValue;
      $fromQillValue = chr(226) . chr(137) . chr(165) . ' ' . ($values['qill_string_greater'] ?? $expectedQillValue);

      // Assigning the relevant form value to be within a custom key is normally done in
      // build field params. It would be better if it were all done in convertFormValues
      // but for now we just imitate it.

      //Scenario 2 : TO date filter
      $formValues = [
        $customFieldName . '_to' => $data,
      ];

      $params = CRM_Contact_BAO_Query::convertFormValues($formValues);
      $queryObj = new CRM_Contact_BAO_Query($params);
      $queryObj->query();

      $this->assertEquals(
        'civicrm_value_testsearchcus_1.' . strtolower($type) . "_field_{$customField['id']} <= $expectedValue",
        $queryObj->_where[0][0]
      );
      $this->assertEquals($queryObj->_qill[0][0],
        "$type field $toQillValue"
      );

      //Scenario 2 : FROM date filter
      $formValues = [
        $customFieldName . '_from' => $values['value'],
      ];

      $params = CRM_Contact_BAO_Query::convertFormValues($formValues);
      $queryObj = new CRM_Contact_BAO_Query($params);
      $queryObj->query();

      $expectedValue = ($isDate) ? '"20150606000000"' : $expectedValue;
      $this->assertEquals(
        'civicrm_value_testsearchcus_1.' . strtolower($type) . "_field_{$customField['id']} >= $expectedValue",
        $queryObj->_where[0][0]
      );
      $this->assertEquals(
        "$type field $fromQillValue",
        $queryObj->_qill[0][0]
      );
    }
  }

  /**
   * Test filtering by relative custom data dates.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchCustomDataDateEquals() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'ContactTestTest');
    $dateCustomField = $this->customFieldCreate([
      'custom_group_id' => $ids['custom_group_id'],
      'label' => 'date field',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'default_value' => NULL,
    ]);
    $dateCustomFieldName = 'custom_' . $dateCustomField['id'];
    $this->individualCreate([$dateCustomFieldName => "2015-01-01"]);
    // Assigning the relevant form value to be within a custom key is normally done in
    // build field params. It would be better if it were all done in convertFormValues
    // but for now we just imitate it.
    $formValues = [$dateCustomFieldName => '2015-06-06'];
    $params = CRM_Contact_BAO_Query::convertFormValues($formValues);
    $queryObj = new CRM_Contact_BAO_Query($params);
    $queryObj->query();

    $this->assertEquals(
      "civicrm_value_testsearchcus_1.date_field_2 = '2015-06-06'",
      $queryObj->_where[0][0]
    );
    $this->assertEquals($queryObj->_qill[0][0], "date field = 'June 6th, 2015'");
  }

  /**
   * Test search builder style query including custom address fields.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddressCustomFields() {
    $this->createCustomGroupWithFieldOfType(['extends' => 'Address'], 'int');
    $individualID = $this->individualCreate();
    $this->callAPISuccess('Address', 'create', [
      'contact_id' => $individualID,
      'street_address' => '10 Downing Street',
      'location_type_id' => 'Home',
      $this->getCustomFieldName('int') => 5,
    ]);

    $queryObject = new CRM_Contact_BAO_Query(
      [[$this->getCustomFieldName('int') . '-1', '=', 5, 1, 0]],
      ['contact_type' => 1, 'location' => ['Home' => ['location_type' => 1, $this->getCustomFieldName('int') => 1]]]
    );
    $queryObject->query();
    $tableName = $this->getCustomGroupTable();
    $fieldName = $this->getCustomFieldColumnName('int');

    $this->assertEquals([], $queryObject->_where[0]);
    $this->assertEquals($tableName . '.' . $fieldName . ' = 5', implode(', ', $queryObject->_where[1]));
    $this->assertEquals(1, $queryObject->_whereTables['civicrm_contact']);
    $this->assertEquals('LEFT JOIN civicrm_address `Home-address` ON (`Home-address`.contact_id = contact_a.id AND `Home-address`.location_type_id = 1)', trim($queryObject->_whereTables['Home-address']));
    $this->assertEquals("LEFT JOIN {$tableName} ON {$tableName}.entity_id = `Home-address`.id", trim($queryObject->_whereTables[$tableName]));
    $this->assertEquals([], $queryObject->_qill[0]);
    $this->assertEquals(['Enter integer here = 5'], $queryObject->_qill[1]);
  }

}

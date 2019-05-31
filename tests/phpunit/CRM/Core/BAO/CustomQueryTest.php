<?php

/**
 *  Include dataProvider for tests
 * @group headless
 */
class CRM_Core_BAO_CustomQueryTest extends CiviUnitTestCase {

  /**
   * Restore database to empty state.
   *
   * Note that rollback won't remove custom tables.
   *
   * @throws \Exception
   */
  public function tearDown() {
    $tablesToTruncate = array(
      'civicrm_contact',
    );
    $this->quickCleanup($tablesToTruncate, TRUE);
    parent::tearDown();
  }

  /**
   * Test filtering by relative custom data dates.
   */
  public function testSearchCustomDataDateRelative() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'ContactTestTest');
    $dateCustomField = $this->customFieldCreate(array(
      'custom_group_id' => $ids['custom_group_id'],
      'label' => 'date field',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'default_value' => NULL,
    ));
    $dateCustomFieldName = 'custom_' . $dateCustomField['id'];
    $formValues = array(
      $dateCustomFieldName . '_relative' => 'this.year',
      $dateCustomFieldName . '_from' => '',
      $dateCustomFieldName . '_to' => '',
    );
    // Assigning the relevant form value to be within a custom key is normally done in
    // build field params. It would be better if it were all done in convertFormValues
    // but for now we just imitate it.
    $params[$dateCustomField['id']] = CRM_Contact_BAO_Query::convertFormValues($formValues);
    $queryObj = new CRM_Core_BAO_CustomQuery($params);
    $queryObj->Query();
    $this->assertEquals(
      'civicrm_value_testsearchcus_1.date_field_2 BETWEEN "' . date('Y') . '0101000000" AND "' . date('Y') . '1231235959"',
      $queryObj->_where[0][0]
    );
    $this->assertEquals($queryObj->_qill[0][0], "date field BETWEEN 'January 1st, " . date('Y') . " 12:00 AM AND December 31st, " . date('Y') . " 11:59 PM'");
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
    ], $queryObj->getFields()[$dateCustomField['id']]);

  }

  /**
   * Test filtering by relative custom data dates.
   */
  public function testSearchCustomDataDateFromTo() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'ContactTestTest');
    $dateCustomField = $this->customFieldCreate(array(
      'custom_group_id' => $ids['custom_group_id'],
      'label' => 'date field',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'default_value' => NULL,
    ));
    $dateCustomFieldName = 'custom_' . $dateCustomField['id'];
    // Assigning the relevant form value to be within a custom key is normally done in
    // build field params. It would be better if it were all done in convertFormValues
    // but for now we just imitate it.
    $formValues = array(
      $dateCustomFieldName . '_from' => '2014-06-06',
      $dateCustomFieldName . '_to' => '2015-06-06',
    );

    $params[$dateCustomField['id']] = CRM_Contact_BAO_Query::convertFormValues($formValues);
    $queryObj = new CRM_Core_BAO_CustomQuery($params);
    $queryObj->Query();
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
   */
  public function testSearchCustomDataFromTo() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'ContactTestTest');
    $datas = array(
      'Int' => 2,
      'Float' => 12.123,
      'Money' => 91.21,
    );
    foreach ($datas as $type => $data) {
      $customField = $this->customFieldCreate(
        array(
          'custom_group_id' => $ids['custom_group_id'],
          'label' => "$type field",
          'data_type' => $type,
          'html_type' => 'Text',
          'default_value' => NULL,
        )
      );
      $customFieldName = 'custom_' . $customField['id'];
      // Assigning the relevant form value to be within a custom key is normally done in
      // build field params. It would be better if it were all done in convertFormValues
      // but for now we just imitate it.
      $from = $data - 1;
      $to = $data;
      $formValues = array(
        $customFieldName . '_from' => $from,
        $customFieldName . '_to' => $to,
      );

      $params = array($customField['id'] => CRM_Contact_BAO_Query::convertFormValues($formValues));
      $queryObj = new CRM_Core_BAO_CustomQuery($params);
      $queryObj->Query();
      $this->assertEquals(
        "civicrm_value_testsearchcus_1." . strtolower($type) . "_field_{$customField['id']} BETWEEN \"$from\" AND \"$to\"",
        $queryObj->_where[0][0]
      );
      $this->assertEquals($queryObj->_qill[0][0], "$type field BETWEEN $from, $to");
    }
  }

  /**
   * Test filtering by relative custom data.
   */
  public function testSearchCustomDataFromAndTo() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'ContactTestTest');
    $datas = array(
      'Date' => '2015-06-06',
      'Int' => 2,
      'Float' => 12.123,
      'Money' => 91.21,
    );
    foreach ($datas as $type => $data) {
      $isDate = ($type === 'Date');
      $customField = $this->customFieldCreate(
        array(
          'custom_group_id' => $ids['custom_group_id'],
          'label' => "$type field",
          'data_type' => $type,
          'html_type' => ($isDate) ? 'Select Date' : 'Text',
          'default_value' => NULL,
        )
      );
      $customFieldName = 'custom_' . $customField['id'];

      $expectedValue = ($isDate) ? '"20150606235959"' : (($type == 'Money') ? $data : "\"$data\"");
      $expectedQillValue = ($isDate) ? "'June 6th, 2015 11:59 PM'" : $data;

      // Assigning the relevant form value to be within a custom key is normally done in
      // build field params. It would be better if it were all done in convertFormValues
      // but for now we just imitate it.

      //Scenrio 2 : TO date filter
      $formValues = array(
        $customFieldName . '_to' => $data,
      );

      $params = array($customField['id'] => CRM_Contact_BAO_Query::convertFormValues($formValues));
      $queryObj = new CRM_Core_BAO_CustomQuery($params);
      $queryObj->Query();
      $wierdStringThatMeansGreaterEquals = chr(226) . chr(137) . chr(164);

      $this->assertEquals(
        "civicrm_value_testsearchcus_1." . strtolower($type) . "_field_{$customField['id']} <= $expectedValue",
        $queryObj->_where[0][0]
      );
      $this->assertEquals($queryObj->_qill[0][0],
        "$type field " . $wierdStringThatMeansGreaterEquals . " $expectedQillValue"
      );

      //Scenrio 2 : FROM date filter
      $formValues = array(
        $customFieldName . '_from' => $data,
      );

      $params = array($customField['id'] => CRM_Contact_BAO_Query::convertFormValues($formValues));
      $queryObj = new CRM_Core_BAO_CustomQuery($params);
      $queryObj->Query();
      $wierdStringThatMeansLessThanEquals = chr(226) . chr(137) . chr(165);

      $expectedValue = ($isDate) ? '"20150606000000"' : $expectedValue;
      $expectedQillValue = ($isDate) ? "'June 6th, 2015 12:00 AM'" : $expectedQillValue;
      $this->assertEquals(
        "civicrm_value_testsearchcus_1." . strtolower($type) . "_field_{$customField['id']} >= $expectedValue",
        $queryObj->_where[0][0]
      );
      $this->assertEquals($queryObj->_qill[0][0],
        "$type field " . $wierdStringThatMeansLessThanEquals . " $expectedQillValue"
      );
    }
  }

  /**
   * Test filtering by relative custom data dates.
   */
  public function testSearchCustomDataDateEquals() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'ContactTestTest');
    $dateCustomField = $this->customFieldCreate(array(
      'custom_group_id' => $ids['custom_group_id'],
      'label' => 'date field',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'default_value' => NULL,
    ));
    $dateCustomFieldName = 'custom_' . $dateCustomField['id'];
    $this->individualCreate(array($dateCustomFieldName => "2015-01-01"));
    // Assigning the relevant form value to be within a custom key is normally done in
    // build field params. It would be better if it were all done in convertFormValues
    // but for now we just imitate it.
    $formValues = array($dateCustomFieldName => '2015-06-06');
    $params[$dateCustomField['id']] = CRM_Contact_BAO_Query::convertFormValues($formValues);
    $queryObj = new CRM_Core_BAO_CustomQuery($params);
    $queryObj->Query();

    $this->assertEquals(
      "civicrm_value_testsearchcus_1.date_field_2 = '2015-06-06'",
      $queryObj->_where[0][0]
    );
    $this->assertEquals($queryObj->_qill[0][0], "date field = 'June 6th, 2015'");
  }

}

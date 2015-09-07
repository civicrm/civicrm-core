<?php
require_once 'CiviTest/CiviUnitTestCase.php';
/**
 *  Include dataProvider for tests
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
    $this->assertEquals($queryObj->_qill[0][0], "date field BETWEEN 'January 1st, 2015 12:00 AM AND December 31st, 2015 11:59 PM'");
  }

  /**
   * Test filtering by relative custom data dates.
   */
  public function testSearchCustomDataDateFrom() {
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
    $formValues = array($dateCustomFieldName . '_from' => '2015-06-06');
    $params[$dateCustomField['id']] = CRM_Contact_BAO_Query::convertFormValues($formValues);
    $queryObj = new CRM_Core_BAO_CustomQuery($params);
    $queryObj->Query();
    $this->assertEquals(
      'civicrm_value_testsearchcus_1.date_field_2 >= "20150606000000"',
      $queryObj->_where[0][0]
    );
    $wierdStringThatMeansLessThanEquals = chr(226) . chr(137) . chr(165);
    $this->assertEquals($queryObj->_qill[0][0],
      "date field " . $wierdStringThatMeansLessThanEquals . " 'June 6th, 2015 12:00 AM'"
    );
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
  }

  /**
   * Test filtering by relative custom data dates.
   */
  public function testSearchCustomDataDateTo() {
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
      $dateCustomFieldName . '_to' => '2015-06-06',
    );

    $params[$dateCustomField['id']] = CRM_Contact_BAO_Query::convertFormValues($formValues);
    $queryObj = new CRM_Core_BAO_CustomQuery($params);
    $queryObj->Query();
    $this->assertEquals(
      'civicrm_value_testsearchcus_1.date_field_2 <= "20150606235959"',
      $queryObj->_where[0][0]
    );
    $wierdStringThatMeansGreaterEquals = chr(226) . chr(137) . chr(164);
    $this->assertEquals($queryObj->_qill[0][0],
      "date field " . $wierdStringThatMeansGreaterEquals . " 'June 6th, 2015 11:59 PM'"
    );
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

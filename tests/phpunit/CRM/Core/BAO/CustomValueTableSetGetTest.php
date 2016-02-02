<?php

/**
 * Class CRM_Core_BAO_CustomValueTableSetGetTest
 */
class CRM_Core_BAO_CustomValueTableSetGetTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Test setValues() and GetValues() methods with custom Date field
   */
  public function testSetGetValuesDate() {
    $params = array();
    $contactID = Contact::createIndividual();

    //create Custom Group
    $customGroup = Custom::createGroup($params, 'Individual', TRUE);

    //create Custom Field of data type Date
    $fields = array(
      'groupId' => $customGroup->id,
      'dataType' => 'Date',
      'htmlType' => 'Select Date',
    );
    $customField = Custom::createField($params, $fields);

    // Retrieve the field ID for sample custom field 'test_Date'
    $params = array('label' => 'test_Date');
    $field = array();

    CRM_Core_BAO_CustomField::retrieve($params, $field);
    $fieldID = $field['id'];

    // Set test_Date to a valid date value
    $date = '20080608000000';
    $params = array(
      'entityID' => $contactID,
      'custom_' . $fieldID => $date,
    );
    $result = CRM_Core_BAO_CustomValueTable::setValues($params);
    $this->assertEquals($result['is_error'], 0, 'Verify that is_error = 0 (success).');

    // Check that the date value is stored
    $values = array();
    $params = array(
      'entityID' => $contactID,
      'custom_' . $fieldID => 1,
    );
    $values = CRM_Core_BAO_CustomValueTable::getValues($params);

    $this->assertEquals($values['is_error'], 0, 'Verify that is_error = 0 (success).');
    $this->assertEquals($values['custom_' . $fieldID . '_1'],
      CRM_Utils_Date::mysqlToIso($date),
      'Verify that the date value is stored for contact ' . $contactID
    );

    // Now set test_Date to an invalid date value and try to reset
    $badDate = '20080631000000';
    $params = array(
      'entityID' => $contactID,
      'custom_' . $fieldID => $badDate,
    );

    $errorScope = CRM_Core_TemporaryErrorScope::useException();
    $message = NULL;
    try {
      $result = CRM_Core_BAO_CustomValueTable::setValues($params);
    }
    catch (Exception $e) {
      $message = $e->getMessage();
    }
    $errorScope = NULL;

    // Check that an exception has been thrown
    $this->assertNotNull($message, 'Verify than an exception is thrown when bad date is passed');

    $params = array(
      'entityID' => $contactID,
      'custom_' . $fieldID => 1,
    );
    $values = CRM_Core_BAO_CustomValueTable::getValues($params);
    $this->assertEquals($values['custom_' . $fieldID . '_1'],
      CRM_Utils_Date::mysqlToIso($date),
      'Verify that the date value has NOT been updated for contact ' . $contactID
    );

    // Test setting test_Date to null
    $params = array(
      'entityID' => $contactID,
      'custom_' . $fieldID => NULL,
    );
    $result = CRM_Core_BAO_CustomValueTable::setValues($params);

    // Check that the date value is empty
    $params = array(
      'entityID' => $contactID,
      'custom_' . $fieldID => 1,
    );
    $values = CRM_Core_BAO_CustomValueTable::getValues($params);
    $this->assertEquals($values['is_error'], 0, 'Verify that is_error = 0 (success).');

    // Cleanup
    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
    Contact::delete($contactID);
  }

  /**
   * Test setValues() and getValues() methods with custom field YesNo(Boolean) Radio
   */
  public function testSetGetValuesYesNoRadio() {
    $params = array();
    $contactID = Contact::createIndividual();

    //create Custom Group
    $customGroup = Custom::createGroup($params, 'Individual', TRUE);

    //create Custom Field of type YesNo(Boolean) Radio
    $fields = array(
      'groupId' => $customGroup->id,
      'dataType' => 'Boolean',
      'htmlType' => 'Radio',
    );
    $customField = Custom::createField($params, $fields);

    // Retrieve the field ID for sample custom field 'test_Boolean'
    $params = array('label' => 'test_Boolean');
    $field = array();

    //get field Id
    CRM_Core_BAO_CustomField::retrieve($params, $field);

    $fieldID = $field['id'];

    // valid boolean value '1' for Boolean Radio
    $yesNo = '1';
    $params = array(
      'entityID' => $contactID,
      'custom_' . $fieldID => $yesNo,
    );
    $result = CRM_Core_BAO_CustomValueTable::setValues($params);

    $this->assertEquals($result['is_error'], 0, 'Verify that is_error = 0 (success).');

    // Check that the YesNo radio value is stored
    $values = array();
    $params = array(
      'entityID' => $contactID,
      'custom_' . $fieldID => 1,
    );
    $values = CRM_Core_BAO_CustomValueTable::getValues($params);

    $this->assertEquals($values['is_error'], 0, 'Verify that is_error = 0 (success).');
    $this->assertEquals($values["custom_{$fieldID}_1"], $yesNo,
      'Verify that the boolean value is stored for contact ' . $contactID
    );

    // Now set YesNo radio to an invalid boolean value and try to reset
    $badYesNo = '20';
    $params = array(
      'entityID' => $contactID,
      'custom_' . $fieldID => $badYesNo,
    );

    $errorScope = CRM_Core_TemporaryErrorScope::useException();
    $message = NULL;
    try {
      $result = CRM_Core_BAO_CustomValueTable::setValues($params);
    }
    catch (Exception $e) {
      $message = $e->getMessage();
    }
    $errorScope = NULL;

    // Check that an exception has been thrown
    $this->assertNotNull($message, 'Verify than an exception is thrown when bad boolean is passed');

    $params = array(
      'entityID' => $contactID,
      'custom_' . $fieldID => 1,
    );
    $values = CRM_Core_BAO_CustomValueTable::getValues($params);

    $this->assertEquals($values["custom_{$fieldID}_1"], $yesNo,
      'Verify that the date value has NOT been updated for contact ' . $contactID
    );

    // Cleanup
    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
    Contact::delete($contactID);
  }

}

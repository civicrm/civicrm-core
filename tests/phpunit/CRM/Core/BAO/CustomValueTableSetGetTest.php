<?php

/**
 * Class CRM_Core_BAO_CustomValueTableSetGetTest
 * @group headless
 */
class CRM_Core_BAO_CustomValueTableSetGetTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Test setValues() and GetValues() methods with custom Date field
   */
  public function testSetGetValuesDate() {
    $params = [];
    $contactID = $this->individualCreate();

    //create Custom Group
    $customGroup = $this->customGroupCreate(['is_multiple' => 1]);

    //create Custom Field of data type Date
    $fields = [
      'custom_group_id' => $customGroup['id'],
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'default_value' => '',
    ];
    $customField = $this->customFieldCreate($fields);

    // Retrieve the field ID for sample custom field 'test_Date'
    $params = ['label' => 'test_Date'];
    $field = [];

    CRM_Core_BAO_CustomField::retrieve($params, $field);
    $fieldID = $customField['id'];

    // Set test_Date to a valid date value
    $date = '20080608000000';
    $params = [
      'entityID' => $contactID,
      'custom_' . $fieldID => $date,
    ];
    $result = CRM_Core_BAO_CustomValueTable::setValues($params);
    $this->assertEquals($result['is_error'], 0, 'Verify that is_error = 0 (success).');

    // Check that the date value is stored
    $values = [];
    $params = [
      'entityID' => $contactID,
      'custom_' . $fieldID => 1,
    ];
    $values = CRM_Core_BAO_CustomValueTable::getValues($params);

    $this->assertEquals($values['is_error'], 0, 'Verify that is_error = 0 (success).');
    $this->assertEquals($values['custom_' . $fieldID . '_1'],
      CRM_Utils_Date::mysqlToIso($date),
      'Verify that the date value is stored for contact ' . $contactID
    );

    // Now set test_Date to an invalid date value and try to reset
    $badDate = '20080631000000';
    $params = [
      'entityID' => $contactID,
      'custom_' . $fieldID => $badDate,
    ];

    CRM_Core_TemporaryErrorScope::useException();
    $message = NULL;
    try {
      CRM_Core_BAO_CustomValueTable::setValues($params);
    }
    catch (Exception $e) {
      $message = $e->getMessage();
    }
    $errorScope = NULL;

    // Check that an exception has been thrown
    $this->assertNotNull($message, 'Verify than an exception is thrown when bad date is passed');

    $params = [
      'entityID' => $contactID,
      'custom_' . $fieldID => 1,
    ];
    $values = CRM_Core_BAO_CustomValueTable::getValues($params);
    $this->assertEquals($values['custom_' . $fieldID . '_1'],
      CRM_Utils_Date::mysqlToIso($date),
      'Verify that the date value has NOT been updated for contact ' . $contactID
    );

    // Test setting test_Date to null
    $params = [
      'entityID' => $contactID,
      'custom_' . $fieldID => NULL,
    ];
    $result = CRM_Core_BAO_CustomValueTable::setValues($params);

    // Check that the date value is empty
    $params = [
      'entityID' => $contactID,
      'custom_' . $fieldID => 1,
    ];
    $values = CRM_Core_BAO_CustomValueTable::getValues($params);
    $this->assertEquals($values['is_error'], 0, 'Verify that is_error = 0 (success).');

    // Cleanup
    $this->customFieldDelete($customField);
    $this->customGroupDelete($customGroup['id']);
    $this->contactDelete($contactID);
  }

  /**
   * Test setValues() and getValues() methods with custom field YesNo(Boolean) Radio
   */
  public function testSetGetValuesYesNoRadio() {
    $contactID = $this->individualCreate();

    $customGroup = $this->customGroupCreate(['is_multiple' => 1]);

    //create Custom Field of type YesNo(Boolean) Radio
    $fields = [
      'custom_group_id' => $customGroup['id'],
      'data_type' => 'Boolean',
      'html_type' => 'Radio',
      'default_value' => '',
    ];
    $customField = $this->customFieldCreate($fields);

    // Retrieve the field ID for sample custom field 'test_Boolean'
    $params = ['label' => 'test_Boolean'];
    $field = [];

    //get field Id
    CRM_Core_BAO_CustomField::retrieve($params, $field);

    $fieldID = $customField['id'];

    // valid boolean value '1' for Boolean Radio
    $yesNo = '1';
    $params = [
      'entityID' => $contactID,
      'custom_' . $fieldID => $yesNo,
    ];
    $result = CRM_Core_BAO_CustomValueTable::setValues($params);

    $this->assertEquals($result['is_error'], 0, 'Verify that is_error = 0 (success).');

    // Check that the YesNo radio value is stored
    $params = [
      'entityID' => $contactID,
      'custom_' . $fieldID => 1,
    ];
    $values = CRM_Core_BAO_CustomValueTable::getValues($params);

    $this->assertEquals($values['is_error'], 0, 'Verify that is_error = 0 (success).');
    $this->assertEquals($values["custom_{$fieldID}_1"], $yesNo,
      'Verify that the boolean value is stored for contact ' . $contactID
    );

    // Now set YesNo radio to an invalid boolean value and try to reset
    $badYesNo = '20';
    $params = [
      'entityID' => $contactID,
      'custom_' . $fieldID => $badYesNo,
    ];

    CRM_Core_TemporaryErrorScope::useException();
    $message = NULL;
    try {
      CRM_Core_BAO_CustomValueTable::setValues($params);
    }
    catch (Exception $e) {
      $message = $e->getMessage();
    }
    $errorScope = NULL;

    // Check that an exception has been thrown
    $this->assertNotNull($message, 'Verify than an exception is thrown when bad boolean is passed');

    $params = [
      'entityID' => $contactID,
      'custom_' . $fieldID => 1,
    ];
    $values = CRM_Core_BAO_CustomValueTable::getValues($params);

    $this->assertEquals($values["custom_{$fieldID}_1"], $yesNo,
      'Verify that the date value has NOT been updated for contact ' . $contactID
    );

    // Cleanup
    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
    $this->contactDelete($contactID);
  }

}

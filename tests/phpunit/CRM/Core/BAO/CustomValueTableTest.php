<?php

/**
 * Class CRM_Core_BAO_CustomValueTableTest
 */
class CRM_Core_BAO_CustomValueTableTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }


  /**
   * Test store function for country.
   */
  public function testStoreCountry() {
    $params = array();
    $contactID = Contact::createIndividual();
    $customGroup = Custom::createGroup($params, 'Individual');
    $fields = array(
      'groupId' => $customGroup->id,
      'dataType' => 'Country',
      'htmlType' => 'Select Country',
    );

    $customField = Custom::createField($params, $fields);

    $params[] = array(
      $customField->id => array(
        'value' => 1228,
        'type' => 'Country',
        'custom_field_id' => $customField->id,
        'custom_group_id' => $customGroup->id,
        'table_name' => 'civicrm_value_test_group_' . $customGroup->id,
        'column_name' => 'test_Country_' . $customField->id,
        'file_id' => '',
      ),
    );

    CRM_Core_BAO_CustomValueTable::store($params, 'civicrm_contact', $contactID);
    //        $this->assertDBCompareValue('CRM_Custom_DAO_CustomValue', )

    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
    Contact::delete($contactID);
  }

  /**
   * Test store function for file.
   */
  public function atestStoreFile() {
    $params = array();
    $contactID = Contact::createIndividual();
    $customGroup = Custom::createGroup($params, 'Individual');
    $fields = array(
      'groupId' => $customGroup->id,
      'dataType' => 'File',
      'htmlType' => 'File',
    );

    $customField = Custom::createField($params, $fields);

    $params[] = array(
      $customField->id => array(
        'value' => 'i/contact_house.png',
        'type' => 'File',
        'custom_field_id' => $customField->id,
        'custom_group_id' => $customGroup->id,
        'table_name' => 'civicrm_value_test_group_' . $customGroup->id,
        'column_name' => 'test_File_' . $customField->id,
        'file_id' => 1,
      ),
    );

    CRM_Core_BAO_CustomValueTable::store($params, 'civicrm_contact', $contactID);
    //        $this->assertDBCompareValue('CRM_Custom_DAO_CustomValue', )

    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
    Contact::delete($contactID);
  }

  /**
   * Test store function for state province.
   */
  public function testStoreStateProvince() {
    $params = array();
    $contactID = Contact::createIndividual();
    $customGroup = Custom::createGroup($params, 'Individual');
    $fields = array(
      'groupId' => $customGroup->id,
      'dataType' => 'StateProvince',
      'htmlType' => 'Select State/Province',
    );

    $customField = Custom::createField($params, $fields);

    $params[] = array(
      $customField->id => array(
        'value' => 1029,
        'type' => 'StateProvince',
        'custom_field_id' => $customField->id,
        'custom_group_id' => $customGroup->id,
        'table_name' => 'civicrm_value_test_group_' . $customGroup->id,
        'column_name' => 'test_StateProvince_' . $customField->id,
        'file_id' => 1,
      ),
    );

    CRM_Core_BAO_CustomValueTable::store($params, 'civicrm_contact', $contactID);

    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
    Contact::delete($contactID);
  }

  /**
   * Test store function for date.
   */
  public function testStoreDate() {
    $params = array();
    $contactID = Contact::createIndividual();
    $customGroup = Custom::createGroup($params, 'Individual');
    $fields = array(
      'groupId' => $customGroup->id,
      'dataType' => 'Date',
      'htmlType' => 'Select Date',
    );

    $customField = Custom::createField($params, $fields);

    $params[] = array(
      $customField->id => array(
        'value' => '20080608000000',
        'type' => 'Date',
        'custom_field_id' => $customField->id,
        'custom_group_id' => $customGroup->id,
        'table_name' => 'civicrm_value_test_group_' . $customGroup->id,
        'column_name' => 'test_Date_' . $customField->id,
        'file_id' => '',
      ),
    );

    CRM_Core_BAO_CustomValueTable::store($params, 'civicrm_contact', $contactID);
    //        $this->assertDBCompareValue('CRM_Custom_DAO_CustomValue', )

    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
    Contact::delete($contactID);
  }

  /**
   * Test store function for rich text editor.
   */
  public function testStoreRichTextEditor() {
    $params = array();
    $contactID = Contact::createIndividual();
    $customGroup = Custom::createGroup($params, 'Individual');
    $fields = array(
      'groupId' => $customGroup->id,
      'htmlType' => 'RichTextEditor',
      'dataType' => 'Memo',
    );

    $customField = Custom::createField($params, $fields);

    $params[] = array(
      $customField->id => array(
        'value' => '<p><strong>This is a <u>test</u></p>',
        'type' => 'Memo',
        'custom_field_id' => $customField->id,
        'custom_group_id' => $customGroup->id,
        'table_name' => 'civicrm_value_test_group_' . $customGroup->id,
        'column_name' => 'test_Memo_' . $customField->id,
        'file_id' => '',
      ),
    );

    CRM_Core_BAO_CustomValueTable::store($params, 'civicrm_contact', $contactID);
    //        $this->assertDBCompareValue('CRM_Custom_DAO_CustomValue', )

    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
    Contact::delete($contactID);
  }

  /**
   * Test getEntityValues function for stored value.
   */
  public function testgetEntityValues() {

    $params = array();
    $contactID = Contact::createIndividual();
    $customGroup = Custom::createGroup($params, 'Individual');
    $fields = array(
      'groupId' => $customGroup->id,
      'htmlType' => 'RichTextEditor',
      'dataType' => 'Memo',
    );

    $customField = Custom::createField($params, $fields);

    $params[] = array(
      $customField->id => array(
        'value' => '<p><strong>This is a <u>test</u></p>',
        'type' => 'Memo',
        'custom_field_id' => $customField->id,
        'custom_group_id' => $customGroup->id,
        'table_name' => 'civicrm_value_test_group_' . $customGroup->id,
        'column_name' => 'test_Memo_' . $customField->id,
        'file_id' => '',
      ),
    );

    CRM_Core_BAO_CustomValueTable::store($params, 'civicrm_contact', $contactID);
    //        $this->assertDBCompareValue('CRM_Custom_DAO_CustomValue', )

    $entityValues = CRM_Core_BAO_CustomValueTable::getEntityValues($contactID, 'Individual');

    $this->assertEquals($entityValues[$customField->id], '<p><strong>This is a <u>test</u></p>',
      'Checking same for returned value.'
    );
    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
    Contact::delete($contactID);
  }

  public function testCustomGroupMultiple() {
    $params = array();
    $contactID = Contact::createIndividual();
    $customGroup = Custom::createGroup($params, 'Individual');

    $fields = array(
      'groupId' => $customGroup->id,
      'dataType' => 'String',
      'htmlType' => 'Text',
    );

    $customField = Custom::createField($params, $fields);

    $params = array(
      'entityID' => $contactID,
      'custom_' . $customField->id . '_-1' => 'First String',
    );
    $error = CRM_Core_BAO_CustomValueTable::setValues($params);

    $newParams = array(
      'entityID' => $contactID,
      'custom_' . $customField->id => 1,
    );
    $result = CRM_Core_BAO_CustomValueTable::getValues($newParams);

    $this->assertEquals($params['custom_' . $customField->id . '_-1'], $result['custom_' . $customField->id]);
    $this->assertEquals($params['entityID'], $result['entityID']);

    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
    Contact::delete($contactID);
  }

}

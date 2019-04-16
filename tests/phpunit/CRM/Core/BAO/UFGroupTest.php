<?php

/**
 * Class CRM_Core_BAO_UFGroupTest
 * @group headless
 */
class CRM_Core_BAO_UFGroupTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function testUFGroupGetValuesForMultiRecordFields() {
    // create multi record custom set
    $customGroup = $this->customGroupCreate(array('is_multiple' => 1));
    $fields = array(
      'custom_group_id' => $customGroup['id'],
      'dataType'        => 'String',
      'htmlType'        => 'Text',
    );
    $customField = $this->customFieldCreate($fields);

    // create profile with multi record custom fields
    $ufGroupParams = array(
      'group_type' => 'Contact',
      'name'       => 'test_profile_with_multi_records_cs',
      'title'      => 'Profile With Multi Record CS',
      'api.uf_field.create' => array( 
        array(
          'field_name'  => "custom_{$customField['id']}",
          'is_required' => 1,
          'visibility'  => 'Public Pages and Listings', 
          'field_type'  => 'Contact',   
          'label'       => 'row text',
          'is_multi_summary' => 1,
        ),
      )
    );
    $profile = $this->callAPISuccess('uf_group', 'create', $ufGroupParams);

    // create first entry for multi record custom set
    $contactID = $this->individualCreate();
    $params = array(
      'entityID' => $contactID,
      "custom_{$customField['id']}_-1" => 'First Row',
    );
    CRM_Core_BAO_CustomValueTable::setValues($params);

    // create second entry for multi record custom set
    $params = array(
      'entityID' => $contactID,
      "custom_{$customField['id']}_-1" => 'Second Row',
    );
    CRM_Core_BAO_CustomValueTable::setValues($params);

    // create third entry for multi record custom set
    $params = array(
      'entityID' => $contactID,
      "custom_{$customField['id']}_-1" => 'Third Row',
    );
    CRM_Core_BAO_CustomValueTable::setValues($params);

    // check number of fields in profile
    $fields = CRM_Core_BAO_UFGroup::getFields($profile['id'], FALSE, CRM_Core_Action::VIEW);
    $this->assertEquals(count($fields), 1, 'Check for number of fields in profile');

    // test getValues() used to fetch profile field values, for display in receipts
    // for e.g when profile submission is notified.
    $values = array();
    CRM_Core_BAO_UFGroup::getValues($contactID, $fields, $values, FALSE, NULL, TRUE);
    $this->assertNotEmpty($values['row text']);
    // getValues uses core search and fetches oldest value
    $this->assertEquals($values['row text'], 'First Row', 'Check for getValues() fetching oldest record.');

    // test updateMultiRecordValuesWithLatest() which should update entries in $values with 
    // that of latest record.
    CRM_Core_BAO_UFGroup::updateMultiRecordValuesWithLatest($profile['id'], $contactID, $fields, $values);
    $this->assertNotEmpty($values['row text']);
    $this->assertEquals($values['row text'], 'Third Row', 'Check for updateMultiRecordValuesWithLatest() fetching latest record.');

    // delete profile
    $params = array('id' => $profile['id']);
    $profile = $this->callAPISuccess('uf_group', 'delete', $params);

    // delete contact, custom field and set.
    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
    $this->contactDelete($contactID);
  }
}

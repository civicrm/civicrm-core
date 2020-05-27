<?php

/**
 * Class api_v3_CustomSearchTest
 * @group headless
 */
class api_v3_CustomSearchTest extends CiviUnitTestCase {
  protected $_apiversion;

  public function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  public function testCustomSearch() {
    $result = $this->callAPISuccess('CustomSearch', 'create', [
      'label' => 'Invalid, overwritten',
      'description' => 'Longish description of the example search form',
      'class_name' => 'CRM_Contact_Form_Search_Custom_Examplez',
    ]);
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count']);
    $entityId = $result['id'];
    $this->assertTrue(is_numeric($entityId));
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"
      AND label = "CRM_Contact_Form_Search_Custom_Examplez"
      AND option_group_id IN (SELECT id from civicrm_option_group WHERE name = "custom_search") ');
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"');

    // deactivate
    $result = $this->callAPISuccess('CustomSearch', 'create', [
      'id' => $entityId,
      'is_active' => 0,
    ]);

    $this->assertEquals(1, $result['count']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"
      AND label = "CRM_Contact_Form_Search_Custom_Examplez"
      AND option_group_id IN (SELECT id from civicrm_option_group WHERE name = "custom_search") ');
    $this->assertDBQuery(0, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"');

    // activate
    $result = $this->callAPISuccess('CustomSearch', 'create', [
      'id' => $entityId,
      'is_active' => 1,
    ]);

    $this->assertEquals(1, $result['count']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"
      AND label = "CRM_Contact_Form_Search_Custom_Examplez"
      AND option_group_id IN (SELECT id from civicrm_option_group WHERE name = "custom_search") ');
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"');
    $check = $this->callAPISuccess('CustomSearch', 'get', ['id' => $entityId]);
    if (!empty($check['count'])) {
      $result = $this->callAPISuccess('CustomSearch', 'delete', [
        'id' => $entityId,
      ]);
    }
    $this->assertEquals(1, $result['count']);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"
      OR label = "CRM_Contact_Form_Search_Custom_Examplez"
      ');
  }

}

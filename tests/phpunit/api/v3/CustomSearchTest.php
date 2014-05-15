<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class api_v3_CustomSearchTest extends CiviUnitTestCase {
  protected $_apiversion;

  function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
  }

  function tearDown() {}

  public function testCustomSearch() {
    $result = $this->callAPISuccess('CustomSearch', 'create', array(
      'label' => 'Invalid, overwritten',
      'description' => 'Longish description of the example search form',
      'class_name' => 'CRM_Contact_Form_Search_Custom_Examplez',
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $entityId = $result['id'];
    $this->assertTrue(is_numeric($entityId), 'In line ' . __LINE__);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"
      AND label = "CRM_Contact_Form_Search_Custom_Examplez"
      AND option_group_id IN (SELECT id from civicrm_option_group WHERE name = "custom_search") ');
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"');

    // deactivate
    $result = $this->callAPISuccess('CustomSearch', 'create', array(
      'id' => $entityId,
      'is_active' => 0,
    ));

    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"
      AND label = "CRM_Contact_Form_Search_Custom_Examplez"
      AND option_group_id IN (SELECT id from civicrm_option_group WHERE name = "custom_search") ');
    $this->assertDBQuery(0, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"');

    // activate
    $result = $this->callAPISuccess('CustomSearch', 'create', array(
      'id' => $entityId,
      'is_active' => 1,
    ));

    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"
      AND label = "CRM_Contact_Form_Search_Custom_Examplez"
      AND option_group_id IN (SELECT id from civicrm_option_group WHERE name = "custom_search") ');
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"');

    $result = $this->callAPISuccess('CustomSearch', 'delete', array(
      'id' => $entityId,
    ));
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"
      OR label = "CRM_Contact_Form_Search_Custom_Examplez"
      ');
  }
}

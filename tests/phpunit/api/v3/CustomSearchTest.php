<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class api_v3_CustomSearchTest extends CiviUnitTestCase {
  protected $_apiversion;
  public $_eNoticeCompliant = TRUE;
  function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
  }

  function tearDown() {}

  public function testCustomSearch() {
    $result = civicrm_api('CustomSearch', 'create', array(
      'version' => $this->_apiversion,
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
      AND option_group_id = 24');
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"');

    // deactivate
    $result = civicrm_api('CustomSearch', 'create', array(
      'version' => $this->_apiversion,
      'id' => $entityId,
      'is_active' => 0,
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"
      AND label = "CRM_Contact_Form_Search_Custom_Examplez"
      AND option_group_id = 24');
    $this->assertDBQuery(0, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"');

    // activate
    $result = civicrm_api('CustomSearch', 'create', array(
      'version' => $this->_apiversion,
      'id' => $entityId,
      'is_active' => 1,
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"
      AND label = "CRM_Contact_Form_Search_Custom_Examplez"
      AND option_group_id = 24');
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"');

    $result = civicrm_api('CustomSearch', 'delete', array(
      'version' => $this->_apiversion,
      'id' => $entityId,
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Contact_Form_Search_Custom_Examplez"
      OR label = "CRM_Contact_Form_Search_Custom_Examplez"
      ');
  }
}

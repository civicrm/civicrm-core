<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

require_once 'CiviTest/CiviUnitTestCase.php';


/**
 *  Test APIv3 civicrm_report_instance_* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Report
 */

class api_v3_ReportTemplateTest extends CiviUnitTestCase {
  protected $_apiversion;
  public $_eNoticeCompliant = TRUE;
  function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
  }

  function tearDown() {}

  public function testReportTemplate() {
    $result = civicrm_api('ReportTemplate', 'create', array(
      'version' => $this->_apiversion,
      'label' => 'Example Form',
      'description' => 'Longish description of the example form',
      'class_name' => 'CRM_Report_Form_Examplez',
      'report_url' => 'example/path',
      'component' => 'CiviCase',
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $entityId = $result['id'];
    $this->assertTrue(is_numeric($entityId), 'In line ' . __LINE__);
    $this->assertEquals(7, $result['values'][$entityId]['component_id'], 'In line ' . __LINE__);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND option_group_id = 40 ');
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"');

    // change component to null
    $result = civicrm_api('ReportTemplate', 'create', array(
      'version' => $this->_apiversion,
      'id' => $entityId,
      'component' => '',
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND option_group_id = 40');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND component_id IS NULL');

    // deactivate
    $result = civicrm_api('ReportTemplate', 'create', array(
      'version' => $this->_apiversion,
      'id' => $entityId,
      'is_active' => 0,
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND option_group_id = 40');
    $this->assertDBQuery(0, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"');

    // activate
    $result = civicrm_api('ReportTemplate', 'create', array(
      'version' => $this->_apiversion,
      'id' => $entityId,
      'is_active' => 1,
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND option_group_id = 40');
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"');

    $result = civicrm_api('ReportTemplate', 'delete', array(
      'version' => $this->_apiversion,
      'id' => $entityId,
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      ');
  }
}

<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @package CiviCRM_APIv3
 * @subpackage API_Report
 */
class api_v3_ReportTemplateTest extends CiviUnitTestCase {
  protected $_apiversion = 3;

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  public function testReportTemplate() {
    $result = $this->callAPISuccess('ReportTemplate', 'create', array(
      'label' => 'Example Form',
      'description' => 'Longish description of the example form',
      'class_name' => 'CRM_Report_Form_Examplez',
      'report_url' => 'example/path',
      'component' => 'CiviCase',
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count']);
    $entityId = $result['id'];
    $this->assertTrue(is_numeric($entityId));
    $this->assertEquals(7, $result['values'][$entityId]['component_id']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND option_group_id IN (SELECT id from civicrm_option_group WHERE name = "report_template") ');
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"');

    // change component to null
    $result = $this->callAPISuccess('ReportTemplate', 'create', array(
      'id' => $entityId,
      'component' => '',
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND option_group_id IN (SELECT id from civicrm_option_group WHERE name = "report_template") ');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND component_id IS NULL');

    // deactivate
    $result = $this->callAPISuccess('ReportTemplate', 'create', array(
      'id' => $entityId,
      'is_active' => 0,
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND option_group_id IN (SELECT id from civicrm_option_group WHERE name = "report_template") ');
    $this->assertDBQuery(0, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"');

    // activate
    $result = $this->callAPISuccess('ReportTemplate', 'create', array(
      'id' => $entityId,
      'is_active' => 1,
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND option_group_id IN (SELECT id from civicrm_option_group WHERE name = "report_template") ');
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"');

    $result = $this->callAPISuccess('ReportTemplate', 'delete', array(
      'id' => $entityId,
    ));
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count']);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      ');
  }

  /**
   * Test getrows on contact summary report.
   */
  public function testReportTemplateGetRowsContactSummary() {
    $description = "Retrieve rows from a report template (optionally providing the instance_id).";
    $result = $this->callAPIAndDocument('report_template', 'getrows', array(
      'report_id' => 'contact/summary',
      'options' => array('metadata' => array('labels', 'title')),
    ), __FUNCTION__, __FILE__, $description, 'Getrows', 'getrows');
    $this->assertEquals('Contact Name', $result['metadata']['labels']['civicrm_contact_sort_name']);

    //the second part of this test has been commented out because it relied on the db being reset to
    // it's base state
    //wasn't able to get that to work consistently
    // however, when the db is in the base state the tests do pass
    // and because the test covers 'all' contacts we can't create our own & assume the others don't exist
    /*
    $this->assertEquals(2, $result['count']);
    $this->assertEquals('Default Organization', $result[0]['civicrm_contact_sort_name']);
    $this->assertEquals('Second Domain', $result[1]['civicrm_contact_sort_name']);
    $this->assertEquals('15 Main St', $result[1]['civicrm_address_street_address']);
     */
  }

  /**
   * Tet api to get rows from reports.
   *
   * @dataProvider getReportTemplates
   *
   * @param $reportID
   *
   * @throws \PHPUnit_Framework_IncompleteTestError
   */
  public function testReportTemplateGetRowsAllReports($reportID) {
    if (stristr($reportID, 'has existing issues')) {
      $this->markTestIncomplete($reportID);
    }
    $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => $reportID,
    ));
  }

  /**
   * Test get statistics.
   *
   * @dataProvider getReportTemplates
   *
   * @param $reportID
   *
   * @throws \PHPUnit_Framework_IncompleteTestError
   */
  public function testReportTemplateGetStatisticsAllReports($reportID) {
    if (stristr($reportID, 'has existing issues')) {
      $this->markTestIncomplete($reportID);
    }
    if (in_array($reportID, array('contribute/softcredit', 'contribute/bookkeeping'))) {
      $this->markTestIncomplete($reportID . " has non enotices when calling statistics fn");
    }
    $description = "Get Statistics from a report (note there isn't much data to get in the test DB).";
    $result = $this->callAPIAndDocument('report_template', 'getstatistics', array(
      'report_id' => $reportID,
    ), __FUNCTION__, __FILE__, $description, 'Getstatistics', 'getstatistics');
  }

  /**
   * Data provider function for getting all templates.
   *
   * Note that the function needs to
   * be static so cannot use $this->callAPISuccess
   */
  public static function getReportTemplates() {
    $reportsToSkip = array(
      'activity' => 'does not respect function signature on from clause',
      'walklist' => 'Notice: Undefined index: type in CRM_Report_Form_Walklist_Walklist line 155.(suspect the select function should be removed in favour of the parent (state province field) also, type should be added to state province & others? & potentially getAddressColumns fn should be used per other reports',
      'contribute/repeat' => 'Reports with important functionality in postProcess are not callable via the api. For variable setting recommend beginPostProcessCommon, for temp table creation recommend From fn',
      'contribute/topDonor' => 'construction of query in postProcess makes inaccessible ',
      'contribute/sybunt' => 'e notice - (ui gives fatal error at civicrm/report/contribute/sybunt&reset=1&force=1 e-notice is on yid_valueContribute/Sybunt.php(214) because at the force url "yid_relative" not "yid_value" is defined',
      'contribute/lybunt' => 'same as sybunt - fatals on force url & test identifies why',
      'event/income' => 'I do no understand why but error is Call to undefined method CRM_Report_Form_Event_Income::from() in CRM/Report/Form.php on line 2120',
      'contact/relationship' => '(see contribute/repeat), property declaration issue, Undefined property: CRM_Report_Form_Contact_Relationship::$relationType in /Contact/Relationship.php(486):',
      'logging/contact/summary' => '(likely to be test related) probably logging off Undefined index: Form/Contact/LoggingSummary.php(231): PHP',
      'logging/contact/detail' => '(likely to be test related) probably logging off  DB Error: no such table',
      'logging/contribute/summary' => '(likely to be test related) probably logging off DB Error: no such table',
      'logging/contribute/detail' => '(likely to be test related) probably logging off DB Error: no such table',
      'survey/detail' => '(likely to be test related)  Undefined index: CiviCampaign civicrm CRM/Core/Component.php(196)',
      'contribute/history' => 'Declaration of CRM_Report_Form_Contribute_History::buildRows() should be compatible with CRM_Report_Form::buildRows($sql, &$rows)',
    );

    $reports = civicrm_api3('report_template', 'get', array('return' => 'value', 'options' => array('limit' => 500)));
    foreach ($reports['values'] as $report) {
      if (empty($reportsToSkip[$report['value']])) {
        $reportTemplates[] = array($report['value']);
      }
      else {
        $reportTemplates[] = array($report['value'] . " has existing issues :  " . $reportsToSkip[$report['value']]);
      }
    }

    return $reportTemplates;
  }

}

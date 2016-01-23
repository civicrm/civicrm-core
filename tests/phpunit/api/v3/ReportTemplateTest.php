<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
      'contribute/repeat' => 'Reports with important functionality in postProcess are not callable via the api. For variable setting recommend beginPostProcessCommon, for temp table creation recommend From fn',
      'contribute/topDonor' => 'construction of query in postProcess makes inaccessible ',
      'event/income' => 'I do no understand why but error is Call to undefined method CRM_Report_Form_Event_Income::from() in CRM/Report/Form.php on line 2120',
      'logging/contact/summary' => '(likely to be test related) probably logging off Undefined index: Form/Contact/LoggingSummary.php(231): PHP',
      'logging/contact/detail' => '(likely to be test related) probably logging off  DB Error: no such table',
      'logging/contribute/summary' => '(likely to be test related) probably logging off DB Error: no such table',
      'logging/contribute/detail' => '(likely to be test related) probably logging off DB Error: no such table',
      'contribute/history' => 'Declaration of CRM_Report_Form_Contribute_History::buildRows() should be compatible with CRM_Report_Form::buildRows($sql, &$rows)',
      'activitySummary' => 'We use temp tables for the main query generation and name are dynamic. These names are not available in stats() when called directly.',
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

  /**
   * Test Lybunt report to check basic inclusion of a contact who gave in the year before the chosen year.
   */
  public function testLybuntReportWithData() {
    $inInd = $this->individualCreate();
    $outInd = $this->individualCreate();
    $this->contributionCreate(array('contact_id' => $inInd, 'receive_date' => '2014-03-01'));
    $this->contributionCreate(array('contact_id' => $outInd, 'receive_date' => '2015-03-01', 'trxn_id' => NULL, 'invoice_id' => NULL));
    $rows = $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => 'contribute/lybunt',
      'yid_value' => 2015,
      'yid_op' => 'calendar',
      'options' => array('metadata' => array('sql')),
    ));
    $this->assertEquals(1, $rows['count'], "Report failed - the sql used to generate the results was " . print_r($rows['metadata']['sql'], TRUE));
  }

  /**
   * Test Lybunt report to check basic inclusion of a contact who gave in the year before the chosen year.
   */
  public function testLybuntReportWithFYData() {
    $inInd = $this->individualCreate();
    $outInd = $this->individualCreate();
    $this->contributionCreate(array('contact_id' => $inInd, 'receive_date' => '2014-10-01'));
    $this->contributionCreate(array('contact_id' => $outInd, 'receive_date' => '2015-03-01', 'trxn_id' => NULL, 'invoice_id' => NULL));
    $this->callAPISuccess('Setting', 'create', array('fiscalYearStart' => array('M' => 7, 'd' => 1)));
    $rows = $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => 'contribute/lybunt',
      'yid_value' => 2015,
      'yid_op' => 'fiscal',
      'options' => array('metadata' => array('sql')),
      'order_bys' => array(
        array(
          'column' => 'first_name',
          'order' => 'ASC',
        ),
      ),
    ));

    $this->assertEquals(2, $rows['count'], "Report failed - the sql used to generate the results was " . print_r($rows['metadata']['sql'], TRUE));
  }

}

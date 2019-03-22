<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

/**
 *  Test APIv3 civicrm_report_instance_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Report
 * @group headless
 */
class api_v3_ReportTemplateTest extends CiviUnitTestCase {

  use CRMTraits_ACL_PermissionTrait;
  use CRMTraits_PCP_PCPTestTrait;

  protected $_apiversion = 3;

  protected $contactIDs = [];

  /**
   * Our group reports use an alter so transaction cleanup won't work.
   *
   * @throws \Exception
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(array('civicrm_group', 'civicrm_saved_search', 'civicrm_group_contact', 'civicrm_group_contact_cache', 'civicrm_group'));
    parent::tearDown();
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
   * Test api to get rows from reports.
   *
   * @dataProvider getReportTemplatesSupportingSelectWhere
   *
   * @param $reportID
   *
   * @throws \PHPUnit_Framework_IncompleteTestError
   */
  public function testReportTemplateSelectWhere($reportID) {
    $this->hookClass->setHook('civicrm_selectWhereClause', array($this, 'hookSelectWhere'));
    $result = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => $reportID,
      'options' => ['metadata' => ['sql']],
    ]);
    $found = FALSE;
    foreach ($result['metadata']['sql'] as $sql) {
      if (strstr($sql, " =  'Organization' ")) {
        $found = TRUE;
      }
    }
    $this->assertTrue($found, $reportID);
  }

  /**
   * Get templates suitable for SelectWhere test.
   *
   * @return array
   */
  public function getReportTemplatesSupportingSelectWhere() {
    $allTemplates = $this->getReportTemplates();
    // Exclude all that do not work as of test being written. I have not dug into why not.
    $currentlyExcluded = [
      'contribute/repeat',
      'member/summary',
      'event/summary',
      'case/summary',
      'case/timespent',
      'case/demographics',
      'contact/log',
      'contribute/bookkeeping',
      'grant/detail',
      'event/incomesummary',
      'case/detail',
      'Mailing/bounce',
      'Mailing/summary',
      'grant/statistics',
      'logging/contact/detail',
      'logging/contact/summary',
    ];
    foreach ($allTemplates as $index => $template) {
      $reportID = $template[0];
      if (in_array($reportID, $currentlyExcluded) || stristr($reportID, 'has existing issues')) {
        unset($allTemplates[$index]);
      }
    }
    return $allTemplates;
  }

  /**
   * @param \CRM_Core_DAO $entity
   * @param array $clauses
   */
  public function hookSelectWhere($entity, &$clauses) {
    // Restrict access to cases by type
    if ($entity == 'Contact') {
      $clauses['contact_type'][] = " =  'Organization' ";
    }
  }

  /**
   * Test getrows on contact summary report.
   */
  public function testReportTemplateGetRowsContactSummary() {
    $description = "Retrieve rows from a report template (optionally providing the instance_id).";
    $result = $this->callApiSuccess('report_template', 'getrows', array(
      'report_id' => 'contact/summary',
      'options' => array('metadata' => array('labels', 'title')),
    ), __FUNCTION__, __FILE__, $description, 'Getrows');
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
   * Test getrows on Mailing Opened report.
   */
  public function testReportTemplateGetRowsMailingUniqueOpened() {
    $description = "Retrieve rows from a mailing opened report template.";
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/../../CRM/Mailing/BAO/queryDataset.xml'
      )
    );

    // Check total rows without distinct
    global $_REQUEST;
    $_REQUEST['distinct'] = 0;
    $result = $this->callAPIAndDocument('report_template', 'getrows', array(
      'report_id' => 'Mailing/opened',
      'options' => array('metadata' => array('labels', 'title')),
    ), __FUNCTION__, __FILE__, $description, 'Getrows');
    $this->assertEquals(14, $result['count']);

    // Check total rows with distinct
    $_REQUEST['distinct'] = 1;
    $result = $this->callAPIAndDocument('report_template', 'getrows', array(
      'report_id' => 'Mailing/opened',
      'options' => array('metadata' => array('labels', 'title')),
    ), __FUNCTION__, __FILE__, $description, 'Getrows');
    $this->assertEquals(5, $result['count']);

    // Check total rows with distinct by passing NULL value to distinct parameter
    $_REQUEST['distinct'] = NULL;
    $result = $this->callAPIAndDocument('report_template', 'getrows', array(
      'report_id' => 'Mailing/opened',
      'options' => array('metadata' => array('labels', 'title')),
    ), __FUNCTION__, __FILE__, $description, 'Getrows');
    $this->assertEquals(5, $result['count']);
  }

  /**
   * Test api to get rows from reports.
   *
   * @dataProvider getReportTemplates
   *
   * @param $reportID
   *
   * @throws \PHPUnit_Framework_IncompleteTestError
   */
  public function testReportTemplateGetRowsAllReports($reportID) {
    //$reportID = 'logging/contact/summary';
    if (stristr($reportID, 'has existing issues')) {
      $this->markTestIncomplete($reportID);
    }
    if (substr($reportID, 0, '7') === 'logging') {
      Civi::settings()->set('logging', 1);
    }

    $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => $reportID,
    ));
    if (substr($reportID, 0, '7') === 'logging') {
      Civi::settings()->set('logging', 0);
    }
  }

  /**
   * Test logging report when a custom data table has a table removed by hook.
   *
   * Here we are checking that no fatal is triggered.
   */
  public function testLoggingReportWithHookRemovalOfCustomDataTable() {
    Civi::settings()->set('logging', 1);
    $group1 = $this->customGroupCreate();
    $group2 = $this->customGroupCreate(['name' => 'second_one', 'title' => 'second one', 'table_name' => 'civicrm_value_second_one']);
    $this->customFieldCreate(array('custom_group_id' => $group1['id'], 'label' => 'field one'));
    $this->customFieldCreate(array('custom_group_id' => $group2['id'], 'label' => 'field two'));
    $this->hookClass->setHook('civicrm_alterLogTables', array($this, 'alterLogTablesRemoveCustom'));

    $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => 'logging/contact/summary',
    ));
    Civi::settings()->set('logging', 0);
    $this->customGroupDelete($group1['id']);
    $this->customGroupDelete($group2['id']);
  }

  /**
   * Remove one log table from the logging spec.
   *
   * @param array $logTableSpec
   */
  public function alterLogTablesRemoveCustom(&$logTableSpec) {
    unset($logTableSpec['civicrm_value_second_one']);
  }

  /**
   * Test api to get rows from reports with ACLs enabled.
   *
   * Checking for lack of fatal error at the moment.
   *
   * @dataProvider getReportTemplates
   *
   * @param $reportID
   *
   * @throws \PHPUnit_Framework_IncompleteTestError
   */
  public function testReportTemplateGetRowsAllReportsACL($reportID) {
    if (stristr($reportID, 'has existing issues')) {
      $this->markTestIncomplete($reportID);
    }
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookNoResults'));
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
      'event/income' => 'I do no understand why but error is Call to undefined method CRM_Report_Form_Event_Income::from() in CRM/Report/Form.php on line 2120',
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
   * Get contribution templates that work with basic filter tests.
   *
   * These templates require minimal data config.
   */
  public static function getContributionReportTemplates() {
    return array(array('contribute/summary'), array('contribute/detail'), array('contribute/repeat'), array('topDonor' => 'contribute/topDonor'));
  }

  /**
   * Get contribution templates that work with basic filter tests.
   *
   * These templates require minimal data config.
   */
  public static function getMembershipReportTemplates() {
    return array(array('member/detail'));
  }

  public static function getMembershipAndContributionReportTemplatesForGroupTests() {
    $templates = array_merge(self::getContributionReportTemplates(), self::getMembershipReportTemplates());
    foreach ($templates as $key => $value) {
      if (array_key_exists('topDonor', $value)) {
        // Report is not standard enough to test here.
        unset($templates[$key]);
      }

    }
    return $templates;
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
   * Test Lybunt report applies ACLs.
   */
  public function testLybuntReportWithDataAndACLFilter() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('administer CiviCRM');
    $inInd = $this->individualCreate();
    $outInd = $this->individualCreate();
    $this->contributionCreate(array('contact_id' => $inInd, 'receive_date' => '2014-03-01'));
    $this->contributionCreate(array('contact_id' => $outInd, 'receive_date' => '2015-03-01', 'trxn_id' => NULL, 'invoice_id' => NULL));
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookNoResults'));
    $params = array(
      'report_id' => 'contribute/lybunt',
      'yid_value' => 2015,
      'yid_op' => 'calendar',
      'options' => array('metadata' => array('sql')),
      'check_permissions' => 1,
    );

    $rows = $this->callAPISuccess('report_template', 'getrows', $params);
    $this->assertEquals(0, $rows['count'], "Report failed - the sql used to generate the results was " . print_r($rows['metadata']['sql'], TRUE));

    CRM_Utils_Hook::singleton()->reset();
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

    $expected = preg_replace('/\s+/', ' ', 'DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AS
      SELECT SQL_CALC_FOUND_ROWS contact_civireport.id as cid  FROM civicrm_contact contact_civireport    INNER JOIN civicrm_contribution contribution_civireport USE index (received_date) ON contribution_civireport.contact_id = contact_civireport.id
         AND contribution_civireport.is_test = 0
         AND contribution_civireport.receive_date BETWEEN \'20140701000000\' AND \'20150630235959\'
         WHERE contact_civireport.id NOT IN (
      SELECT cont_exclude.contact_id
          FROM civicrm_contribution cont_exclude
          WHERE cont_exclude.receive_date BETWEEN \'2015-7-1\' AND \'20160630235959\')
          AND ( contribution_civireport.contribution_status_id IN (1) )
      GROUP BY contact_civireport.id');
    // Exclude whitespace in comparison as we don't care if it changes & this allows us to make the above readable.
    $whitespacelessSql = preg_replace('/\s+/', ' ', $rows['metadata']['sql'][0]);
    $this->assertContains($expected, $whitespacelessSql);
  }

  /**
   * Test Lybunt report to check basic inclusion of a contact who gave in the year before the chosen year.
   */
  public function testLybuntReportWithFYDataOrderByLastYearAmount() {
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
      'fields' => array('first_name'),
      'order_bys' => array(
        array(
          'column' => 'last_year_total_amount',
          'order' => 'ASC',
        ),
      ),
    ));

    $this->assertEquals(2, $rows['count'], "Report failed - the sql used to generate the results was " . print_r($rows['metadata']['sql'], TRUE));
  }

  /**
   * Test the group filter works on the contribution summary (with a smart group).
   *
   * @dataProvider getMembershipAndContributionReportTemplatesForGroupTests
   *
   * @param string $template
   *   Name of the template to test.
   */
  public function testContributionSummaryWithSmartGroupFilter($template) {
    $groupID = $this->setUpPopulatedSmartGroup();
    $rows = $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => $template,
      'gid_value' => $groupID,
      'gid_op' => 'in',
      'options' => array('metadata' => array('sql')),
    ));
    $this->assertNumberOfContactsInResult(3, $rows, $template);
    if ($template === 'contribute/summary') {
      $this->assertEquals(3, $rows['values'][0]['civicrm_contribution_total_amount_count']);
    }
  }

  /**
   * Test the group filter works on the contribution summary.
   *
   * @dataProvider getMembershipAndContributionReportTemplatesForGroupTests
   */
  public function testContributionSummaryWithNotINSmartGroupFilter($template) {
    $groupID = $this->setUpPopulatedSmartGroup();
    $rows = $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => 'contribute/summary',
      'gid_value' => $groupID,
      'gid_op' => 'notin',
      'options' => array('metadata' => array('sql')),
    ));
    $this->assertEquals(2, $rows['values'][0]['civicrm_contribution_total_amount_count']);
  }

  /**
   * Test no fatal on order by per https://lab.civicrm.org/dev/core/issues/739
   */
  public function testCaseDetailsCaseTypeHeader() {
    $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'case/detail',
      'fields' => ['subject' => 1, 'client_sort_name' => 1],
       'order_bys' => [
         1 => [
          'column' => 'case_type_title',
          'order' => 'ASC',
          'section' => '1',
        ],
      ],
    ]);
  }

  /**
   * Test the group filter works on the contribution summary.
   */
  public function testContributionDetailSoftCredits() {
    $contactID = $this->individualCreate();
    $contactID2 = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $contactID, 'api.ContributionSoft.create' => ['amount' => 5, 'contact_id' => $contactID2]]);
    $template = 'contribute/detail';
    $rows = $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => $template,
      'contribution_or_soft_value' => 'contributions_only',
      'fields' => ['soft_credits' => 1, 'contribution_or_soft' => 1, 'sort_name' => 1],
      'options' => array('metadata' => array('sql')),
    ));
    $this->assertEquals(
      "<a href='/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=" . $contactID2 . "'>Anderson, Anthony</a> $ 5.00",
      $rows['values'][0]['civicrm_contribution_soft_credits']
    );
  }

  /**
   * Test the amount column is populated on soft credit details.
   */
  public function testContributionDetailSoftCreditsOnly() {
    $contactID = $this->individualCreate();
    $contactID2 = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $contactID, 'api.ContributionSoft.create' => ['amount' => 5, 'contact_id' => $contactID2]]);
    $template = 'contribute/detail';
    $rows = $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => $template,
      'contribution_or_soft_value' => 'soft_credits_only',
      'fields' => [
        'sort_name' => '1',
        'email' => '1',
        'financial_type_id' => '1',
        'receive_date' => '1',
        'total_amount' => '1',
      ],
      'options' => array('metadata' => ['sql', 'labels']),
    ));
    foreach (array_keys($rows['metadata']['labels']) as $header) {
      $this->assertTrue(!empty($rows['values'][0][$header]));
    }
  }

  /**
   * Test the group filter works on the various reports.
   *
   * @dataProvider getMembershipAndContributionReportTemplatesForGroupTests
   *
   * @param string $template
   *   Report template unique identifier.
   */
  public function testReportsWithNonSmartGroupFilter($template) {
    $groupID = $this->setUpPopulatedGroup();
    $rows = $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => $template,
      'gid_value' => array($groupID),
      'gid_op' => 'in',
      'options' => array('metadata' => array('sql')),
    ));
    $this->assertNumberOfContactsInResult(1, $rows, $template);
  }

  /**
   * Assert the included results match the expected.
   *
   * There may or may not be a group by in play so the assertion varies a little.
   *
   * @param int $numberExpected
   * @param array $rows
   *   Rows returned from the report.
   * @param string $template
   */
  protected function assertNumberOfContactsInResult($numberExpected, $rows, $template) {
    if (isset($rows['values'][0]['civicrm_contribution_total_amount_count'])) {
      $this->assertEquals($numberExpected, $rows['values'][0]['civicrm_contribution_total_amount_count'], 'wrong row count in ' . $template);
    }
    else {
      $this->assertEquals($numberExpected, count($rows['values']), 'wrong row count in ' . $template);
    }
  }

  /**
   * Test the group filter works on the contribution summary when 2 groups are involved.
   */
  public function testContributionSummaryWithTwoGroups() {
    $groupID = $this->setUpPopulatedGroup();
    $groupID2 = $this->setUpPopulatedSmartGroup();
    $rows = $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => 'contribute/summary',
      'gid_value' => array($groupID, $groupID2),
      'gid_op' => 'in',
      'options' => array('metadata' => array('sql')),
    ));
    $this->assertEquals(4, $rows['values'][0]['civicrm_contribution_total_amount_count']);
  }

  /**
   * CRM-20640: Test the group filter works on the contribution summary when a single contact in 2 groups.
   */
  public function testContributionSummaryWithSingleContactsInTwoGroups() {
    list($groupID1, $individualID) = $this->setUpPopulatedGroup(TRUE);
    // create second group and add the individual to it.
    $groupID2 = $this->groupCreate(array('name' => uniqid(), 'title' => uniqid()));
    $this->callAPISuccess('GroupContact', 'create', array(
      'group_id' => $groupID2,
      'contact_id' => $individualID,
      'status' => 'Added',
    ));

    $rows = $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => 'contribute/summary',
      'gid_value' => array($groupID1, $groupID2),
      'gid_op' => 'in',
      'options' => array('metadata' => array('sql')),
    ));
    $this->assertEquals(1, $rows['count']);
  }

  /**
   * Test the group filter works on the contribution summary when 2 groups are involved.
   */
  public function testContributionSummaryWithTwoGroupsWithIntersection() {
    $groups = $this->setUpIntersectingGroups();

    $rows = $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => 'contribute/summary',
      'gid_value' => $groups,
      'gid_op' => 'in',
      'options' => array('metadata' => array('sql')),
    ));
    $this->assertEquals(7, $rows['values'][0]['civicrm_contribution_total_amount_count']);
  }

  /**
   * Set up a smart group for testing.
   *
   * The smart group includes all Households by filter. In addition an individual
   * is created and hard-added and an individual is created that is not added.
   *
   * One household is hard-added as well as being in the filter.
   *
   * This gives us a range of scenarios for testing contacts are included only once
   * whenever they are hard-added or in the criteria.
   *
   * @return int
   */
  public function setUpPopulatedSmartGroup() {
    $household1ID = $this->householdCreate();
    $individual1ID = $this->individualCreate();
    $householdID = $this->householdCreate();
    $individualID = $this->individualCreate();
    $individualIDRemoved = $this->individualCreate();
    $groupID = $this->smartGroupCreate(array(), array('name' => uniqid(), 'title' => uniqid()));
    $this->callAPISuccess('GroupContact', 'create', array(
      'group_id' => $groupID,
      'contact_id' => $individualIDRemoved,
      'status' => 'Removed',
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'group_id' => $groupID,
      'contact_id' => $individualID,
      'status' => 'Added',
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'group_id' => $groupID,
      'contact_id' => $householdID,
      'status' => 'Added',
    ));
    foreach (array($household1ID, $individual1ID, $householdID, $individualID, $individualIDRemoved) as $contactID) {
      $this->contributionCreate(array('contact_id' => $contactID, 'invoice_id' => '', 'trxn_id' => ''));
      $this->contactMembershipCreate(array('contact_id' => $contactID));
    }

    // Refresh the cache for test purposes. It would be better to alter to alter the GroupContact add function to add contacts to the cache.
    CRM_Contact_BAO_GroupContactCache::clearGroupContactCache($groupID);
    return $groupID;
  }

  /**
   * Set up a static group for testing.
   *
   * An individual is created and hard-added and an individual is created that is not added.
   *
   * This gives us a range of scenarios for testing contacts are included only once
   * whenever they are hard-added or in the criteria.
   *
   * @param bool $returnAddedContact
   *
   * @return int
   */
  public function setUpPopulatedGroup($returnAddedContact = FALSE) {
    $individual1ID = $this->individualCreate();
    $individualID = $this->individualCreate();
    $individualIDRemoved = $this->individualCreate();
    $groupID = $this->groupCreate(array('name' => uniqid(), 'title' => uniqid()));
    $this->callAPISuccess('GroupContact', 'create', array(
      'group_id' => $groupID,
      'contact_id' => $individualIDRemoved,
      'status' => 'Removed',
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'group_id' => $groupID,
      'contact_id' => $individualID,
      'status' => 'Added',
    ));

    foreach (array($individual1ID, $individualID, $individualIDRemoved) as $contactID) {
      $this->contributionCreate(array('contact_id' => $contactID, 'invoice_id' => '', 'trxn_id' => ''));
      $this->contactMembershipCreate(array('contact_id' => $contactID));
    }

    // Refresh the cache for test purposes. It would be better to alter to alter the GroupContact add function to add contacts to the cache.
    CRM_Contact_BAO_GroupContactCache::clearGroupContactCache($groupID);

    if ($returnAddedContact) {
      return array($groupID, $individualID);
    }

    return $groupID;
  }

  /**
   * @return array
   */
  public function setUpIntersectingGroups() {
    $groupID = $this->setUpPopulatedGroup();
    $groupID2 = $this->setUpPopulatedSmartGroup();
    $addedToBothIndividualID = $this->individualCreate();
    $removedFromBothIndividualID = $this->individualCreate();
    $addedToSmartGroupRemovedFromOtherIndividualID = $this->individualCreate();
    $removedFromSmartGroupAddedToOtherIndividualID = $this->individualCreate();
    $this->callAPISuccess('GroupContact', 'create', array(
      'group_id' => $groupID,
      'contact_id' => $addedToBothIndividualID,
      'status' => 'Added',
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'group_id' => $groupID2,
      'contact_id' => $addedToBothIndividualID,
      'status' => 'Added',
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'group_id' => $groupID,
      'contact_id' => $removedFromBothIndividualID,
      'status' => 'Removed',
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'group_id' => $groupID2,
      'contact_id' => $removedFromBothIndividualID,
      'status' => 'Removed',
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'group_id' => $groupID2,
      'contact_id' => $addedToSmartGroupRemovedFromOtherIndividualID,
      'status' => 'Added',
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'group_id' => $groupID,
      'contact_id' => $addedToSmartGroupRemovedFromOtherIndividualID,
      'status' => 'Removed',
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'group_id' => $groupID,
      'contact_id' => $removedFromSmartGroupAddedToOtherIndividualID,
      'status' => 'Added',
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'group_id' => $groupID2,
      'contact_id' => $removedFromSmartGroupAddedToOtherIndividualID,
      'status' => 'Removed',
    ));

    foreach (array(
               $addedToBothIndividualID,
               $removedFromBothIndividualID,
               $addedToSmartGroupRemovedFromOtherIndividualID,
               $removedFromSmartGroupAddedToOtherIndividualID,
             ) as $contactID) {
      $this->contributionCreate(array(
        'contact_id' => $contactID,
        'invoice_id' => '',
        'trxn_id' => '',
      ));
    }
    return array($groupID, $groupID2);
  }

  /**
   * Test Deferred Revenue Report.
   */
  public function testDeferredRevenueReport() {
    $indv1 = $this->individualCreate();
    $indv2 = $this->individualCreate();
    $params = array(
      'contribution_invoice_settings' => array(
        'deferred_revenue_enabled' => '1',
      ),
    );
    $this->callAPISuccess('Setting', 'create', $params);
    $this->contributionCreate(
      array(
        'contact_id' => $indv1,
        'receive_date' => '2016-10-01',
        'revenue_recognition_date' => date('Y-m-t', strtotime(date('ymd') . '+3 month')),
        'financial_type_id' => 2,
      )
    );
    $this->contributionCreate(
      array(
        'contact_id' => $indv1,
        'revenue_recognition_date' => date('Y-m-t', strtotime(date('ymd') . '+22 month')),
        'financial_type_id' => 4,
        'trxn_id' => NULL,
        'invoice_id' => NULL,
      )
    );
    $this->contributionCreate(
      array(
        'contact_id' => $indv2,
        'revenue_recognition_date' => date('Y-m-t', strtotime(date('ymd') . '+1 month')),
        'financial_type_id' => 4,
        'trxn_id' => NULL,
        'invoice_id' => NULL,
      )
    );
    $this->contributionCreate(
      array(
        'contact_id' => $indv2,
        'receive_date' => '2016-03-01',
        'revenue_recognition_date' => date('Y-m-t', strtotime(date('ymd') . '+4 month')),
        'financial_type_id' => 2,
        'trxn_id' => NULL,
        'invoice_id' => NULL,
      )
    );
    $rows = $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => 'contribute/deferredrevenue',
    ));
    $this->assertEquals(2, $rows['count'], "Report failed to get row count");
    $count = array(2, 1);
    foreach ($rows['values'] as $row) {
      $this->assertEquals(array_pop($count), count($row['rows']), "Report failed to get row count");
    }
  }

  /**
   * Test the group filter works on the various reports.
   *
   * @dataProvider getMembershipAndContributionReportTemplatesForGroupTests
   *
   * @param string $template
   *   Report template unique identifier.
   */
  public function testReportsWithNoTInSmartGroupFilter($template) {
    $groupID = $this->setUpPopulatedGroup();
    $rows = $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => $template,
      'gid_value' => array($groupID),
      'gid_op' => 'notin',
      'options' => array('metadata' => array('sql')),
    ));
    $this->assertNumberOfContactsInResult(2, $rows, $template);
  }

  /**
   * Test activity summary report - requiring all current fields to be output.
   */
  public function testActivitySummary() {
    $this->createContactsWithActivities();
    $fields = [
      'contact_source' => '1',
      'contact_assignee' => '1',
      'contact_target' => '1',
      'contact_source_email' => '1',
      'contact_assignee_email' => '1',
      'contact_target_email' => '1',
      'contact_source_phone' => '1',
      'contact_assignee_phone' => '1',
      'contact_target_phone' => '1',
      'activity_type_id' => '1',
      'activity_subject' => '1',
      'activity_date_time' => '1',
      'status_id' => '1',
      'duration' => '1',
      'location' => '1',
      'details' => '1',
      'priority_id' => '1',
      'result' => '1',
      'engagement_level' => '1',
      'address_name' => '1',
      'street_address' => '1',
      'supplemental_address_1' => '1',
      'supplemental_address_2' => '1',
      'supplemental_address_3' => '1',
      'street_number' => '1',
      'street_name' => '1',
      'street_unit' => '1',
      'city' => '1',
      'postal_code' => '1',
      'postal_code_suffix' => '1',
      'country_id' => '1',
      'state_province_id' => '1',
      'county_id' => '1',
    ];
    $params = [
      'fields' => $fields,
      'current_user_op' => 'eq',
      'current_user_value' => '0',
      'include_case_activities_op' => 'eq',
      'include_case_activities_value' => 0,
      'order_bys' => [
        1 => ['column' => 'activity_date_time', 'order' => 'ASC'],
        2 => ['column' => 'activity_type_id', 'order' => 'ASC'],
      ],
    ];

    $params['report_id'] = 'Activity';

    $rows = $this->callAPISuccess('report_template', 'getrows', $params)['values'];
    $expected = [
      'civicrm_contact_contact_source' => 'Łąchowski-Roberts, Anthony',
      'civicrm_contact_contact_assignee' => '<a title=\'View Contact Summary for this Contact\' href=\'/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=4\'>Łąchowski-Roberts, Anthony</a>',
      'civicrm_contact_contact_target' => '<a title=\'View Contact Summary for this Contact\' href=\'/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=3\'>Brzęczysław, Anthony</a>; <a title=\'View Contact Summary for this Contact\' href=\'/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=4\'>Łąchowski-Roberts, Anthony</a>',
      'civicrm_contact_contact_source_id' => $this->contactIDs[2],
      'civicrm_contact_contact_assignee_id' => $this->contactIDs[1],
      'civicrm_contact_contact_target_id' => $this->contactIDs[0] . ';' . $this->contactIDs[1],
      'civicrm_email_contact_source_email' => 'anthony_anderson@civicrm.org',
      'civicrm_email_contact_assignee_email' => 'anthony_anderson@civicrm.org',
      'civicrm_email_contact_target_email' => 'techo@spying.com;anthony_anderson@civicrm.org',
      'civicrm_phone_contact_source_phone' => NULL,
      'civicrm_phone_contact_assignee_phone' => NULL,
      'civicrm_phone_contact_target_phone' => NULL,
      'civicrm_activity_id' => '1',
      'civicrm_activity_source_record_id' => NULL,
      'civicrm_activity_activity_type_id' => 'Meeting',
      'civicrm_activity_activity_subject' => 'Very secret meeting',
      'civicrm_activity_activity_date_time' => date('Y-m-d 23:59:58', strtotime('now')),
      'civicrm_activity_status_id' => 'Scheduled',
      'civicrm_activity_duration' => '120',
      'civicrm_activity_location' => 'Pennsylvania',
      'civicrm_activity_details' => 'a test activity',
      'civicrm_activity_priority_id' => 'Normal',
      'civicrm_address_address_name' => NULL,
      'civicrm_address_street_address' => NULL,
      'civicrm_address_supplemental_address_1' => NULL,
      'civicrm_address_supplemental_address_2' => NULL,
      'civicrm_address_supplemental_address_3' => NULL,
      'civicrm_address_street_number' => NULL,
      'civicrm_address_street_name' => NULL,
      'civicrm_address_street_unit' => NULL,
      'civicrm_address_city' => NULL,
      'civicrm_address_postal_code' => NULL,
      'civicrm_address_postal_code_suffix' => NULL,
      'civicrm_address_country_id' => NULL,
      'civicrm_address_state_province_id' => NULL,
      'civicrm_address_county_id' => NULL,
      'civicrm_contact_contact_source_link' => '/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=' . $this->contactIDs[2],
      'civicrm_contact_contact_source_hover' => 'View Contact Summary for this Contact',
      'civicrm_activity_activity_type_id_hover' => 'View Activity Record',
    ];
    $row = $rows[0];
    // This link is not relative - skip for now
    unset($row['civicrm_activity_activity_type_id_link']);
    if ($row['civicrm_email_contact_target_email'] === 'anthony_anderson@civicrm.org;techo@spying.com') {
      // order is unpredictable
      $expected['civicrm_email_contact_target_email'] = 'anthony_anderson@civicrm.org;techo@spying.com';
    }

    $this->assertEquals($expected, $row);
  }

  /**
   * Set up some activity data..... use some chars that challenge our utf handling.
   */
  public function createContactsWithActivities() {
    $this->contactIDs[] = $this->individualCreate(['last_name' => 'Brzęczysław', 'email' => 'techo@spying.com']);
    $this->contactIDs[] = $this->individualCreate(['last_name' => 'Łąchowski-Roberts']);
    $this->contactIDs[] = $this->individualCreate(['last_name' => 'Łąchowski-Roberts']);

    $this->callAPISuccess('Activity', 'create', [
      'subject' => 'Very secret meeting',
      'activity_date_time' => date('Y-m-d 23:59:58', strtotime('now')),
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_type_id' => 'Meeting',
      'source_contact_id' => $this->contactIDs[2],
      'target_contact_id' => array($this->contactIDs[0], $this->contactIDs[1]),
      'assignee_contact_id' => $this->contactIDs[1],
    ]);
  }

  /**
   * Test the group filter works on the contribution summary.
   */
  public function testContributionDetailTotalHeader() {
    $contactID = $this->individualCreate();
    $contactID2 = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $contactID, 'api.ContributionSoft.create' => ['amount' => 5, 'contact_id' => $contactID2]]);
    $template = 'contribute/detail';
    $rows = $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => $template,
      'contribution_or_soft_value' => 'contributions_only',
      'fields' => [
        'sort_name' => '1',
        'age' => '1',
        'email' => '1',
        'phone' => '1',
        'financial_type_id' => '1',
        'receive_date' => '1',
        'total_amount' => '1',
       ],
      'order_bys' => [['column' => 'sort_name', 'order' => 'ASC', 'section' => '1']],
      'options' => array('metadata' => array('sql')),
    ));
  }

  /**
   * Test PCP report to ensure total donors and total committed is accurate.
   */
  public function testPcpReportTotals() {
    $donor1ContactId = $this->individualCreate();
    $donor2ContactId = $this->individualCreate();
    $donor3ContactId = $this->individualCreate();

    // We are going to create two PCP pages. We will create two contributions
    // on the first PCP page and one contribution on the second PCP page.
    //
    // Then, we will ensure that the first PCP page reports a total of both
    // contributions (but not the contribution made on the second PCP page).

    // A PCP page requires three components:
    // 1. A contribution page
    // 2. A PCP Block
    // 3. A PCP page

    // pcpBLockParams creates a contribution page and returns the parameters
    // necessary to create a PBP Block.
    $blockParams = $this->pcpBlockParams();
    $pcpBlock = CRM_PCP_BAO_PCPBlock::create($blockParams);

    // Keep track of the contribution page id created. We will use this
    // contribution page id for all the PCP pages.
    $contribution_page_id = $pcpBlock->entity_id;

    // pcpParams returns the parameters needed to create a PCP page.
    $pcpParams = $this->pcpParams();
    // Keep track of the owner of the page so we can properly apply the
    // soft credit.
    $pcpOwnerContact1Id = $pcpParams['contact_id'];
    $pcpParams['pcp_block_id'] = $pcpBlock->id;
    $pcpParams['page_id'] = $contribution_page_id;
    $pcpParams['page_type'] = 'contribute';
    $pcp1 = CRM_PCP_BAO_PCP::create($pcpParams);

    // Nice work. Now, let's create a second PCP page.
    $pcpParams = $this->pcpParams();
    // Keep track of the owner of the page.
    $pcpOwnerContact2Id = $pcpParams['contact_id'];
    // We're using the same pcpBlock id and contribution page that we created above.
    $pcpParams['pcp_block_id'] = $pcpBlock->id;
    $pcpParams['page_id'] = $contribution_page_id;
    $pcpParams['page_type'] = 'contribute';
    $pcp2 = CRM_PCP_BAO_PCP::create($pcpParams);

    // Get soft credit types, with the name column as the key.
    $soft_credit_types = CRM_Contribute_BAO_ContributionSoft::buildOptions("soft_credit_type_id", NULL, array("flip" => TRUE, 'labelColumn' => 'name'));
    $pcp_soft_credit_type_id = $soft_credit_types['pcp'];

    // Create two contributions assigned to this contribution page and
    // assign soft credits appropriately.
    // FIRST...
    $contribution1params = array(
      'contact_id' => $donor1ContactId,
      'contribution_page_id' => $contribution_page_id,
      'total_amount' => '75.00',
    );
    $c1 = $this->contributionCreate($contribution1params);
    // Now the soft contribution.
    $p = array(
      'contribution_id' => $c1,
      'pcp_id' => $pcp1->id,
      'contact_id' => $pcpOwnerContact1Id,
      'amount' => 75.00,
      'currency' => 'USD',
      'soft_credit_type_id' => $pcp_soft_credit_type_id,
    );
    $this->callAPISuccess('contribution_soft', 'create', $p);
    // SECOND...
    $contribution2params = array(
      'contact_id' => $donor2ContactId,
      'contribution_page_id' => $contribution_page_id,
      'total_amount' => '25.00',
    );
    $c2 = $this->contributionCreate($contribution2params);
    // Now the soft contribution.
    $p = array(
      'contribution_id' => $c2,
      'pcp_id' => $pcp1->id,
      'contact_id' => $pcpOwnerContact1Id,
      'amount' => 25.00,
      'currency' => 'USD',
      'soft_credit_type_id' => $pcp_soft_credit_type_id,
    );
    $this->callAPISuccess('contribution_soft', 'create', $p);

    // Create one contributions assigned to the second PCP page
    $contribution3params = array(
      'contact_id' => $donor3ContactId,
      'contribution_page_id' => $contribution_page_id,
      'total_amount' => '200.00',
    );
    $c3 = $this->contributionCreate($contribution3params);
    // Now the soft contribution.
    $p = array(
      'contribution_id' => $c3,
      'pcp_id' => $pcp2->id,
      'contact_id' => $pcpOwnerContact2Id,
      'amount' => 200.00,
      'currency' => 'USD',
      'soft_credit_type_id' => $pcp_soft_credit_type_id,
    );
    $this->callAPISuccess('contribution_soft', 'create', $p);

    $template = 'contribute/pcp';
    $rows = $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => $template,
      'title' => 'My PCP',
      'fields' => [
        'amount_1' => '1',
        'soft_id' => '1',
       ],
    ));
    $values = $rows['values'][0];
    $this->assertEquals(100.00, $values['civicrm_contribution_soft_amount_1_sum'], "Total commited should be $100");
    $this->assertEquals(2, $values['civicrm_contribution_soft_soft_id_count'], "Total donors should be 2");
  }

}

<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use Civi\Api4\Contact;
use Civi\Test\ACLPermissionTrait;

/**
 *  Test APIv3 civicrm_report_instance_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Report
 * @group headless
 */
class api_v3_ReportTemplateTest extends CiviUnitTestCase {

  use ACLPermissionTrait;
  use CRMTraits_PCP_PCPTestTrait;
  use CRMTraits_Custom_CustomDataTrait;

  protected $contactIDs = [];

  protected $aclGroupID;

  /**
   * @var int
   */
  protected $activityID;

  /**
   * Our group reports use an alter so transaction cleanup won't work.
   */
  public function tearDown(): void {
    Civi::settings()->set('logging', 0);
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_group', 'civicrm_saved_search', 'civicrm_group_contact', 'civicrm_group_contact_cache', 'civicrm_group'], TRUE);
    (new CRM_Logging_Schema())->dropAllLogTables();
    CRM_Utils_Hook::singleton()->reset();
    parent::tearDown();
  }

  /**
   * Test CRUD actions on a report template.
   *
   * @throws \CRM_Core_Exception
   */
  public function testReportTemplate(): void {
    /** @noinspection SpellCheckingInspection */
    $result = $this->callAPISuccess('ReportTemplate', 'create', [
      'label' => 'Example Form',
      'description' => 'Longish description of the example form',
      'class_name' => 'CRM_Report_Form_Examplez',
      'report_url' => 'example/path',
      'component' => 'CiviCase',
    ]);
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count']);
    $entityId = $result['id'];
    $this->assertIsNumeric($entityId);
    $caseComponentId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Component', 'CiviCase', 'id', 'name');
    $this->assertEquals($caseComponentId, $result['values'][$entityId]['component_id']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND option_group_id IN (SELECT id from civicrm_option_group WHERE name = "report_template") ');
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"');

    // change component to null
    $result = $this->callAPISuccess('ReportTemplate', 'create', [
      'id' => $entityId,
      'component' => '',
    ]);
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND option_group_id IN (SELECT id from civicrm_option_group WHERE name = "report_template") ');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND component_id IS NULL');

    // deactivate
    $result = $this->callAPISuccess('ReportTemplate', 'create', [
      'id' => $entityId,
      'is_active' => 0,
    ]);
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND option_group_id IN (SELECT id from civicrm_option_group WHERE name = "report_template") ');
    $this->assertDBQuery(0, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"');

    // activate
    $result = $this->callAPISuccess('ReportTemplate', 'create', [
      'id' => $entityId,
      'is_active' => 1,
    ]);
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"
      AND option_group_id IN (SELECT id from civicrm_option_group WHERE name = "report_template") ');
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value
      WHERE name = "CRM_Report_Form_Examplez"');

    $result = $this->callAPISuccess('ReportTemplate', 'delete', [
      'id' => $entityId,
    ]);
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
   * @param string $reportID
   */
  public function testReportTemplateSelectWhere(string $reportID): void {
    $this->hookClass->setHook('civicrm_selectWhereClause', [$this, 'hookSelectWhere']);
    $result = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => $reportID,
      'options' => ['metadata' => ['sql']],
    ]);
    $found = FALSE;
    foreach ($result['metadata']['sql'] as $sql) {
      if (strpos($sql, " =  'Organization' ") !== FALSE) {
        $found = TRUE;
      }
    }
    $this->assertTrue($found, $reportID);
  }

  /**
   * Get templates suitable for SelectWhere test.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getReportTemplatesSupportingSelectWhere(): array {
    $allTemplates = self::getReportTemplates();
    // Exclude all that do not work as of test being written. I have not dug into why not.
    $currentlyExcluded = [
      'contribute/history',
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
      if (in_array($reportID, $currentlyExcluded, TRUE)) {
        unset($allTemplates[$index]);
      }
    }
    return $allTemplates;
  }

  /**
   * @param string $entity
   * @param array $clauses
   */
  public function hookSelectWhere(string $entity, array &$clauses): void {
    // Restrict access to cases by type
    if ($entity === 'Contact') {
      $clauses['contact_type'][] = " =  'Organization' ";
    }
  }

  /**
   * Test getrows on contact summary report.
   */
  public function testReportTemplateGetRowsContactSummary(): void {
    $result = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'contact/summary',
      'options' => ['metadata' => ['labels', 'title']],
    ]);
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
  public function testReportTemplateGetRowsMailingUniqueOpened(): void {
    $this->loadXMLDataSet(__DIR__ . '/../../CRM/Mailing/BAO/queryDataset.xml');

    // Check total rows without distinct
    global $_REQUEST;
    $_REQUEST['distinct'] = 0;
    $result = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'Mailing/opened',
      'options' => ['metadata' => ['labels', 'title']],
    ]);
    $this->assertEquals(14, $result['count']);

    // Check total rows with distinct
    $_REQUEST['distinct'] = 1;
    $result = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'Mailing/opened',
      'options' => ['metadata' => ['labels', 'title']],
    ]);
    $this->assertEquals(5, $result['count']);

    // Check total rows with distinct by passing NULL value to distinct parameter
    $_REQUEST['distinct'] = NULL;
    $result = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'Mailing/opened',
      'options' => ['metadata' => ['labels', 'title']],
    ]);
    $this->assertEquals(5, $result['count']);
  }

  /**
   * Test api to get rows from reports.
   *
   * @dataProvider getReportTemplates
   *
   * @param string $reportID
   */
  public function testReportTemplateGetRowsAllReports(string $reportID): void {
    if (strpos($reportID, 'logging') === 0) {
      Civi::settings()->set('logging', 1);
    }

    $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => $reportID,
    ]);
  }

  /**
   * Test logging report when a custom data table has a table removed by hook.
   *
   * Here we are checking that no fatal is triggered.
   */
  public function testLoggingReportWithHookRemovalOfCustomDataTable(): void {
    Civi::settings()->set('logging', 1);
    $group1 = $this->customGroupCreate();
    $group2 = $this->customGroupCreate(['name' => 'second_one', 'title' => 'second one', 'table_name' => 'civicrm_value_second_one']);
    $this->customFieldCreate(['custom_group_id' => $group1['id'], 'label' => 'field one']);
    $this->customFieldCreate(['custom_group_id' => $group2['id'], 'label' => 'field two']);
    $this->hookClass->setHook('civicrm_alterLogTables', [$this, 'alterLogTablesRemoveCustom']);

    $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'logging/contact/summary',
    ]);
  }

  /**
   * Remove one log table from the logging spec.
   *
   * @param array $logTableSpec
   */
  public function alterLogTablesRemoveCustom(array &$logTableSpec): void {
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
   */
  public function testReportTemplateGetRowsAllReportsACL($reportID): void {
    if (strpos($reportID, 'logging') === 0) {
      Civi::settings()->set('logging', 1);
    }
    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclWhereHookNoResults']);
    $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => $reportID,
    ]);
  }

  /**
   * Test get statistics.
   *
   * @dataProvider getReportTemplates
   *
   * @param string $reportID
   *
   */
  public function testReportTemplateGetStatisticsAllReports(string $reportID): void {
    if (in_array($reportID, ['contribute/softcredit', 'contribute/bookkeeping'])) {
      $this->markTestIncomplete($reportID . ' has non e-notices when calling statistics fn');
    }
    if (strpos($reportID, 'logging') === 0) {
      Civi::settings()->set('logging', 1);
    }
    if ($reportID === 'contribute/summary') {
      $this->hookClass->setHook('civicrm_alterReportVar', [$this, 'alterReportVarHook']);
    }
    $this->callAPISuccess('report_template', 'getstatistics', [
      'report_id' => $reportID,
    ]);
  }

  /**
   * Implements hook_civicrm_alterReportVar().
   *
   * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
   */
  public function alterReportVarHook($varType, &$var, &$object): void {
    if ($varType === 'sql' && $object instanceof CRM_Report_Form_Contribute_Summary) {
      $from = $var->getVar('_from');
      $from .= ' LEFT JOIN civicrm_financial_type as temp ON temp.id = contribution_civireport.financial_type_id';
      $var->setVar('_from', $from);
      $where = $var->getVar('_where');
      $where .= ' AND ( temp.id IS NOT NULL )';
      $var->setVar('_where', $where);
    }
  }

  /**
   * Data provider function for getting all templates.
   *
   * Note that the function needs to
   * be static so cannot use $this->callAPISuccess
   *
   * @throws \CRM_Core_Exception
   */
  public static function getReportTemplates(): array {
    $reportTemplates = [];
    $reportsToSkip = [
      'event/income' => "This report overrides buildQuery() so doesn't seem compatible with this test and you get a syntax error `WHERE civicrm_event.id IN( ) GROUP BY civicrm_event.id`",
    ];

    $reports = civicrm_api3('report_template', 'get', ['return' => 'value', 'options' => ['limit' => 500]]);
    foreach ($reports['values'] as $report) {
      if (empty($reportsToSkip[$report['value']])) {
        $reportTemplates[] = [$report['value']];
      }
    }

    return $reportTemplates;
  }

  /**
   * Get contribution templates that work with basic filter tests.
   *
   * These templates require minimal data config.
   */
  public static function getContributionReportTemplates(): array {
    return [['contribute/summary'], ['contribute/detail'], ['contribute/repeat'], ['topDonor' => 'contribute/topDonor']];
  }

  /**
   * Get contribution templates that work with basic filter tests.
   *
   * These templates require minimal data config.
   *
   * @return array
   */
  public static function getMembershipReportTemplates(): array {
    return [['member/detail']];
  }

  /**
   * Get the membership and contribution reports to test.
   *
   * @return array
   */
  public static function getMembershipAndContributionReportTemplatesForGroupTests(): array {
    $templates = array_merge(self::getContributionReportTemplates(), self::getMembershipReportTemplates());
    foreach ($templates as $key => $value) {
      if (array_key_exists('topDonor', $value)) {
        // Report is not standard enough to test here.
        unset($templates[$key]);
      }

    }
    return $templates;
  }

  public static function getContactMembershipAndContributionReportTemplatesForACLGroupTests(): array {
    return array_merge([['contact/summary']], self::getMembershipAndContributionReportTemplatesForGroupTests());
  }

  /**
   * Test Lybunt report to check basic inclusion of a contact who gave in the year before the chosen year.
   */
  public function testLybuntReportWithData(): void {
    $inInd = $this->individualCreate();
    $outInd = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $inInd, 'receive_date' => '2014-03-01']);
    $this->contributionCreate(['contact_id' => $outInd, 'receive_date' => '2015-03-01', 'trxn_id' => NULL, 'invoice_id' => NULL]);
    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'contribute/lybunt',
      'yid_value' => 2015,
      'yid_op' => 'calendar',
      'options' => ['metadata' => ['sql']],
    ]);
    $this->assertEquals(1, $rows['count'], 'Report failed - the sql used to generate the results was ' . print_r($rows['metadata']['sql'], TRUE));
  }

  /**
   * Test Lybunt report applies ACLs.
   */
  public function testLybuntReportWithDataAndACLFilter(): void {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['administer CiviCRM'];
    $inInd = $this->individualCreate();
    $outInd = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $inInd, 'receive_date' => '2014-03-01']);
    $this->contributionCreate(['contact_id' => $outInd, 'receive_date' => '2015-03-01', 'trxn_id' => NULL, 'invoice_id' => NULL]);
    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclWhereHookNoResults']);
    $params = [
      'report_id' => 'contribute/lybunt',
      'yid_value' => 2015,
      'yid_op' => 'calendar',
      'options' => ['metadata' => ['sql']],
      'check_permissions' => 1,
    ];

    $rows = $this->callAPISuccess('report_template', 'getrows', $params);
    $this->assertEquals(0, $rows['count'], 'Report failed - the sql used to generate the results was ' . print_r($rows['metadata']['sql'], TRUE));

    CRM_Utils_Hook::singleton()->reset();
  }

  /**
   * Test Lybunt report to check basic inclusion of a contact who gave in the year before the chosen year.
   */
  public function testLybuntReportWithFYData(): void {
    $inInd = $this->individualCreate();
    $outInd = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $inInd, 'receive_date' => '2014-10-01']);
    $this->contributionCreate(['contact_id' => $outInd, 'receive_date' => '2015-03-01', 'trxn_id' => NULL, 'invoice_id' => NULL]);
    $this->callAPISuccess('Setting', 'create', ['fiscalYearStart' => ['M' => 7, 'd' => 1]]);
    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'contribute/lybunt',
      'yid_value' => 2015,
      'yid_op' => 'fiscal',
      'options' => ['metadata' => ['sql']],
      'order_bys' => [
        [
          'column' => 'first_name',
          'order' => 'ASC',
        ],
      ],
    ]);

    $this->assertEquals(2, $rows['count'], 'Report failed - the sql used to generate the results was ' . print_r($rows['metadata']['sql'], TRUE));
    $inUseCollation = CRM_Core_BAO_SchemaHandler::getInUseCollation();
    $expected = preg_replace('/\s+/', ' ', 'COLLATE ' . $inUseCollation . ' AS
      SELECT SQL_CALC_FOUND_ROWS contact_civireport.id as cid  FROM civicrm_contact contact_civireport    INNER JOIN civicrm_contribution contribution_civireport USE index (received_date) ON contribution_civireport.contact_id = contact_civireport.id
         AND contribution_civireport.is_test = 0
         AND contribution_civireport.is_template = 0
         AND contribution_civireport.receive_date BETWEEN \'20140701000000\' AND \'20150630235959\'
         WHERE contact_civireport.id NOT IN (
      SELECT cont_exclude.contact_id
          FROM civicrm_contribution cont_exclude
          WHERE cont_exclude.receive_date BETWEEN \'2015-7-1\' AND \'20160630235959\')
          AND ( contribution_civireport.contribution_status_id IN (1) )
      GROUP BY contact_civireport.id');
    // Exclude whitespace in comparison as we don't care if it changes & this allows us to make the above readable.
    $whitespaceFreeSql = preg_replace('/\s+/', ' ', $rows['metadata']['sql'][0]);
    $this->assertStringContainsString($expected, $whitespaceFreeSql);
  }

  /**
   * Test Lybunt report to check basic inclusion of a contact who gave in the year before the chosen year.
   */
  public function testLybuntReportWithFYDataOrderByLastYearAmount(): void {
    $inInd = $this->individualCreate();
    $outInd = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $inInd, 'receive_date' => '2014-10-01']);
    $this->contributionCreate(['contact_id' => $outInd, 'receive_date' => '2015-03-01', 'trxn_id' => NULL, 'invoice_id' => NULL]);
    $this->callAPISuccess('Setting', 'create', ['fiscalYearStart' => ['M' => 7, 'd' => 1]]);
    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'contribute/lybunt',
      'yid_value' => 2015,
      'yid_op' => 'fiscal',
      'options' => ['metadata' => ['sql']],
      'fields' => ['first_name' => 1],
      'order_bys' => [
        [
          'column' => 'last_year_total_amount',
          'order' => 'ASC',
        ],
      ],
    ]);

    $this->assertEquals(2, $rows['count'], 'Report failed - the sql used to generate the results was ' . print_r($rows['metadata']['sql'], TRUE));
  }

  /**
   * Test the group filter works on the contribution summary (with a smart
   * group).
   *
   * @dataProvider getMembershipAndContributionReportTemplatesForGroupTests
   *
   * @param string $template
   *   Name of the template to test.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContributionSummaryWithSmartGroupFilter(string $template): void {
    $groupID = $this->setUpPopulatedSmartGroup();
    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => $template,
      'gid_value' => $groupID,
      'gid_op' => 'in',
      'options' => ['metadata' => ['sql']],
    ]);
    $this->assertNumberOfContactsInResult(3, $rows, $template);
    if ($template === 'contribute/summary') {
      $this->assertEquals(3, $rows['values'][0]['civicrm_contribution_total_amount_count']);
    }
  }

  /**
   * Test the group filter works on the contribution summary.
   *
   * @dataProvider getMembershipAndContributionReportTemplatesForGroupTests
   *
   * @param string $template
   *
   * @throws \CRM_Core_Exception
   */
  public function testContributionSummaryWithNotINSmartGroupFilter(string $template): void {
    $groupID = $this->setUpPopulatedSmartGroup();
    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'contribute/summary',
      'gid_value' => $groupID,
      'gid_op' => 'notin',
      'options' => ['metadata' => ['sql']],
    ]);
    $this->assertEquals(2, $rows['values'][0]['civicrm_contribution_total_amount_count']);
  }

  /**
   * Test no fatal on order by per https://lab.civicrm.org/dev/core/issues/739
   *
   * @throws \CRM_Core_Exception
   */
  public function testCaseDetailsCaseTypeHeader(): void {
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testContributionDetailSoftCredits(): void {
    $contactID = $this->individualCreate();
    $contactID2 = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $contactID, 'api.ContributionSoft.create' => ['amount' => 5, 'contact_id' => $contactID2]]);
    $template = 'contribute/detail';
    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => $template,
      'contribution_or_soft_value' => 'contributions_only',
      'fields' => ['soft_credits' => 1, 'contribution_or_soft' => 1, 'sort_name' => 1],
      'options' => ['metadata' => ['sql']],
    ]);
    $this->assertEquals(
      "<a href='/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=" . $contactID2 . "'>Anderson, Anthony II</a> $ 5.00",
      $rows['values'][0]['civicrm_contribution_soft_credits']
    );
  }

  /**
   * Test the amount column is populated on soft credit details.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContributionDetailSoftCreditsOnly(): void {
    $contactID = $this->individualCreate();
    $contactID2 = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $contactID, 'api.ContributionSoft.create' => ['amount' => 5, 'contact_id' => $contactID2]]);
    $template = 'contribute/detail';
    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => $template,
      'contribution_or_soft_value' => 'soft_credits_only',
      'fields' => [
        'sort_name' => '1',
        'email' => '1',
        'financial_type_id' => '1',
        'receive_date' => '1',
        'total_amount' => '1',
      ],
      'options' => ['metadata' => ['sql', 'labels']],
    ]);
    foreach (array_keys($rows['metadata']['labels']) as $header) {
      $this->assertNotEmpty($rows['values'][0][$header]);
    }
  }

  /**
   * Test the group filter works on the various reports.
   *
   * @dataProvider getMembershipAndContributionReportTemplatesForGroupTests
   *
   * @param string $template
   *   Report template unique identifier.
   *
   * @throws \CRM_Core_Exception
   */
  public function testReportsWithNonSmartGroupFilter(string $template): void {
    $groupID = $this->setUpPopulatedGroup();
    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => $template,
      'gid_value' => [$groupID],
      'gid_op' => 'in',
      'options' => ['metadata' => ['sql']],
    ]);
    $this->assertNumberOfContactsInResult(1, $rows, $template);
  }

  /**
   * Test the group filter works on various reports when ACLed user is in play
   *
   * @dataProvider getContactMembershipAndContributionReportTemplatesForACLGroupTests
   *
   * @param string $template
   *   Report template unique identifier.
   */
  public function testReportsWithNonSmartGroupFilterWithACL(string $template): void {
    $this->aclGroupID = $this->setUpPopulatedGroup();
    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->callAPISuccessGetCount('Group', ['check_permissions' => 1], 0);
    $this->hookClass->setHook('civicrm_aclGroup', [$this, 'aclGroupOnly']);
    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclGroupContactsOnly']);
    unset(Civi::$statics['CRM_ACL_API']);
    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => $template,
      'gid_value' => [$this->aclGroupID],
      'gid_op' => 'in',
      'options' => ['metadata' => ['sql']],
    ]);
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
  protected function assertNumberOfContactsInResult(int $numberExpected, array $rows, string $template): void {
    if (isset($rows['values'][0]['civicrm_contribution_total_amount_count'])) {
      $this->assertEquals($numberExpected, $rows['values'][0]['civicrm_contribution_total_amount_count'], 'wrong row count in ' . $template);
    }
    else {
      $this->assertCount($numberExpected, $rows['values'], 'wrong row count in ' . $template);
    }
  }

  /**
   * Test the group filter works on the contribution summary when 2 groups are involved.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContributionSummaryWithTwoGroups(): void {
    $groupID = $this->setUpPopulatedGroup();
    $groupID2 = $this->setUpPopulatedSmartGroup();
    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'contribute/summary',
      'gid_value' => [$groupID, $groupID2],
      'gid_op' => 'in',
      'options' => ['metadata' => ['sql']],
    ]);
    $this->assertEquals(4, $rows['values'][0]['civicrm_contribution_total_amount_count']);
  }

  /**
   * Test we don't get a fatal grouping  by only contribution status id.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContributionSummaryGroupByContributionStatus(): void {
    $params = [
      'report_id' => 'contribute/summary',
      'fields' => ['total_amount' => 1, 'country_id' => 1],
      'group_bys' => ['contribution_status_id' => 1],
      'options' => ['metadata' => ['sql']],
    ];
    $rowsSql = $this->callAPISuccess('report_template', 'getrows', $params)['metadata']['sql'];
    $this->assertStringContainsString('GROUP BY contribution_civireport.contribution_status_id WITH ROLLUP', $rowsSql[0]);
    $statsSql = $this->callAPISuccess('report_template', 'getstatistics', $params)['metadata']['sql'];
    $this->assertStringContainsString('GROUP BY contribution_civireport.contribution_status_id, currency', $statsSql[2]);
  }

  /**
   * Test we don't get a fatal grouping  by only contribution status id.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContributionSummaryGroupByYearFrequency(): void {
    $params = [
      'report_id' => 'contribute/summary',
      'fields' => ['total_amount' => 1, 'country_id' => 1],
      'group_bys' => ['receive_date' => 1],
      'group_bys_freq' => ['receive_date' => 'YEAR'],
      'options' => ['metadata' => ['sql']],
    ];
    $rowsSql = $this->callAPISuccess('report_template', 'getrows', $params)['metadata']['sql'];
    $this->assertStringContainsString('GROUP BY  YEAR(contribution_civireport.receive_date) WITH ROLLUP', $rowsSql[0]);
    $statsSql = $this->callAPISuccess('report_template', 'getstatistics', $params)['metadata']['sql'];
    $this->assertStringContainsString('GROUP BY  YEAR(contribution_civireport.receive_date), currency', $statsSql[2]);
  }

  /**
   * Test we don't get a fatal grouping with QUARTER frequency.
   */
  public function testContributionSummaryGroupByYearQuarterFrequency(): void {
    $params = [
      'report_id' => 'contribute/summary',
      'fields' => ['total_amount' => 1, 'country_id' => 1],
      'group_bys' => ['receive_date' => 1],
      'group_bys_freq' => ['receive_date' => 'QUARTER'],
      'options' => ['metadata' => ['sql']],
    ];
    $rowsSql = $this->callAPISuccess('report_template', 'getrows', $params)['metadata']['sql'];
    $this->assertStringContainsString('GROUP BY YEAR(contribution_civireport.receive_date), QUARTER(contribution_civireport.receive_date) WITH ROLLUP', $rowsSql[0]);
    $statsSql = $this->callAPISuccess('report_template', 'getstatistics', $params)['metadata']['sql'];
    $this->assertStringContainsString('GROUP BY YEAR(contribution_civireport.receive_date), QUARTER(contribution_civireport.receive_date), currency', $statsSql[2]);
  }

  /**
   * Test we don't get a fatal grouping with QUARTER frequency.
   */
  public function testContributionSummaryGroupByDateFrequency(): void {
    $params = [
      'report_id' => 'contribute/summary',
      'fields' => ['total_amount' => 1, 'country_id' => 1],
      'group_bys' => ['receive_date' => 1],
      'group_bys_freq' => ['receive_date' => 'DATE'],
      'options' => ['metadata' => ['sql']],
    ];
    $rowsSql = $this->callAPISuccess('report_template', 'getrows', $params)['metadata']['sql'];
    $this->assertStringContainsString('GROUP BY DATE(contribution_civireport.receive_date) WITH ROLLUP', $rowsSql[0]);
    $statsSql = $this->callAPISuccess('report_template', 'getstatistics', $params)['metadata']['sql'];
    $this->assertStringContainsString('GROUP BY DATE(contribution_civireport.receive_date), currency', $statsSql[2]);
  }

  /**
   * Test we don't get a fatal grouping with QUARTER frequency.
   */
  public function testContributionSummaryGroupByWeekFrequency(): void {
    $params = [
      'report_id' => 'contribute/summary',
      'fields' => ['total_amount' => 1, 'country_id' => 1],
      'group_bys' => ['receive_date' => 1],
      'group_bys_freq' => ['receive_date' => 'YEARWEEK'],
      'options' => ['metadata' => ['sql']],
    ];
    $rowsSql = $this->callAPISuccess('report_template', 'getrows', $params)['metadata']['sql'];
    $this->assertStringContainsString('GROUP BY YEARWEEK(contribution_civireport.receive_date) WITH ROLLUP', $rowsSql[0]);
    $statsSql = $this->callAPISuccess('report_template', 'getstatistics', $params)['metadata']['sql'];
    $this->assertStringContainsString('GROUP BY YEARWEEK(contribution_civireport.receive_date), currency', $statsSql[2]);
  }

  /**
   * CRM-20640: Test the group filter works on the contribution summary when a single contact in 2 groups.
   */
  public function testContributionSummaryWithSingleContactsInTwoGroups(): void {
    $groupID1 = $this->setUpPopulatedGroup();
    $individualID = $this->ids['Contact']['primary'];
    // create second group and add the individual to it.
    $groupID2 = $this->groupCreate(['name' => 'test_group', 'title' => 'test_title']);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID2,
      'contact_id' => $individualID,
      'status' => 'Added',
    ]);

    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'contribute/summary',
      'gid_value' => [$groupID1, $groupID2],
      'gid_op' => 'in',
      'options' => ['metadata' => ['sql']],
    ]);
    $this->assertEquals(1, $rows['count']);
  }

  /**
   * Test the group filter works on the contribution summary when 2 groups are involved.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContributionSummaryWithTwoGroupsWithIntersection(): void {
    $groups = $this->setUpIntersectingGroups();

    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'contribute/summary',
      'gid_value' => $groups,
      'gid_op' => 'in',
      'options' => ['metadata' => ['sql']],
    ]);
    $this->assertEquals(7, $rows['values'][0]['civicrm_contribution_total_amount_count']);
  }

  /**
   * Test date field is correctly handled.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContributionSummaryDateFields(): void {
    $sql = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'contribute/summary',
      'thankyou_date_relative' => '0',
      'thankyou_date_from' => '2020-03-01 00:00:00',
      'thankyou_date_to' => '2020-03-31 23:59:59',
      'options' => ['metadata' => ['sql']],
    ])['metadata']['sql'];
    $expectedSql = 'SELECT contact_civireport.id as civicrm_contact_id, DATE_SUB(contribution_civireport.receive_date, INTERVAL (DAYOFMONTH(contribution_civireport.receive_date)-1) DAY) as civicrm_contribution_receive_date_start, MONTH(contribution_civireport.receive_date) AS civicrm_contribution_receive_date_subtotal, MONTHNAME(contribution_civireport.receive_date) AS civicrm_contribution_receive_date_interval, contribution_civireport.currency as civicrm_contribution_currency, COUNT(contribution_civireport.total_amount) as civicrm_contribution_total_amount_count, SUM(contribution_civireport.total_amount) as civicrm_contribution_total_amount_sum, ROUND(AVG(contribution_civireport.total_amount),2) as civicrm_contribution_total_amount_avg, address_civireport.country_id as civicrm_address_country_id   FROM civicrm_contact contact_civireport
             INNER JOIN civicrm_contribution   contribution_civireport
                     ON contact_civireport.id = contribution_civireport.contact_id AND
                        contribution_civireport.is_test = 0
                         AND contribution_civireport.is_template = 0
             LEFT JOIN civicrm_contribution_soft contribution_soft_civireport
                       ON contribution_soft_civireport.contribution_id = contribution_civireport.id AND contribution_soft_civireport.id = (SELECT MIN(id) FROM civicrm_contribution_soft cs WHERE cs.contribution_id = contribution_civireport.id)
             LEFT  JOIN civicrm_financial_type  financial_type_civireport
                     ON contribution_civireport.financial_type_id =financial_type_civireport.id

                 LEFT JOIN civicrm_address address_civireport
                           ON (contact_civireport.id =
                               address_civireport.contact_id)  AND
                               address_civireport.is_primary = 1
 WHERE ( contribution_civireport.thankyou_date >= 20200301000000) AND ( contribution_civireport.thankyou_date <= 20200331235959) AND ( contribution_civireport.contribution_status_id IN (1) ) GROUP BY EXTRACT(YEAR_MONTH FROM contribution_civireport.receive_date), contribution_civireport.contribution_status_id    LIMIT 25';
    $this->assertLike($expectedSql, $sql[0]);
  }

  /**
   * Set up a smart group for testing.
   *
   * The smart group includes all Households by filter. In addition an
   * individual is created and hard-added and an individual is created that is
   * not added.
   *
   * One household is hard-added as well as being in the filter.
   *
   * This gives us a range of scenarios for testing contacts are included only
   * once whenever they are hard-added or in the criteria.
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  public function setUpPopulatedSmartGroup(): int {
    $household1ID = $this->householdCreate();
    $individual1ID = $this->individualCreate();
    $householdID = $this->householdCreate();
    $individualID = $this->individualCreate();
    $individualIDRemoved = $this->individualCreate();
    $groupID = $this->smartGroupCreate([], ['name' => 'smart_group', 'title' => 'smart group']);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID,
      'contact_id' => $individualIDRemoved,
      'status' => 'Removed',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID,
      'contact_id' => $individualID,
      'status' => 'Added',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID,
      'contact_id' => $householdID,
      'status' => 'Added',
    ]);
    foreach ([$household1ID, $individual1ID, $householdID, $individualID, $individualIDRemoved] as $contactID) {
      $this->contributionCreate(['contact_id' => $contactID, 'invoice_id' => '', 'trxn_id' => '']);
      $this->contactMembershipCreate(['contact_id' => $contactID]);
    }

    // Refresh the cache for test purposes. It would be better to alter to alter the GroupContact add function to add contacts to the cache.
    CRM_Contact_BAO_GroupContactCache::invalidateGroupContactCache($groupID);
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
   * @return int
   */
  public function setUpPopulatedGroup(): int {
    $individual1ID = $this->individualCreate();
    $individualID = $this->ids['Contact']['primary'] = $this->individualCreate();
    $individualIDRemoved = $this->individualCreate();
    $groupID = $this->groupCreate(['name' => uniqid(), 'title' => uniqid()]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID,
      'contact_id' => $individualIDRemoved,
      'status' => 'Removed',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID,
      'contact_id' => $individualID,
      'status' => 'Added',
    ]);

    foreach ([$individual1ID, $individualID, $individualIDRemoved] as $contactID) {
      $this->contributionCreate(['contact_id' => $contactID, 'invoice_id' => '', 'trxn_id' => '']);
      $this->contactMembershipCreate(['contact_id' => $contactID]);
    }

    // Refresh the cache for test purposes. It would be better to alter to alter the GroupContact add function to add contacts to the cache.
    CRM_Contact_BAO_GroupContactCache::invalidateGroupContactCache($groupID);
    return $groupID;
  }

  /**
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function setUpIntersectingGroups(): array {
    $groupID = $this->setUpPopulatedGroup();
    $groupID2 = $this->setUpPopulatedSmartGroup();
    $addedToBothIndividualID = $this->individualCreate();
    $removedFromBothIndividualID = $this->individualCreate();
    $addedToSmartGroupRemovedFromOtherIndividualID = $this->individualCreate();
    $removedFromSmartGroupAddedToOtherIndividualID = $this->individualCreate();
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID,
      'contact_id' => $addedToBothIndividualID,
      'status' => 'Added',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID2,
      'contact_id' => $addedToBothIndividualID,
      'status' => 'Added',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID,
      'contact_id' => $removedFromBothIndividualID,
      'status' => 'Removed',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID2,
      'contact_id' => $removedFromBothIndividualID,
      'status' => 'Removed',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID2,
      'contact_id' => $addedToSmartGroupRemovedFromOtherIndividualID,
      'status' => 'Added',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID,
      'contact_id' => $addedToSmartGroupRemovedFromOtherIndividualID,
      'status' => 'Removed',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID,
      'contact_id' => $removedFromSmartGroupAddedToOtherIndividualID,
      'status' => 'Added',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID2,
      'contact_id' => $removedFromSmartGroupAddedToOtherIndividualID,
      'status' => 'Removed',
    ]);

    foreach ([
      $addedToBothIndividualID,
      $removedFromBothIndividualID,
      $addedToSmartGroupRemovedFromOtherIndividualID,
      $removedFromSmartGroupAddedToOtherIndividualID,
    ] as $contactID) {
      $this->contributionCreate([
        'contact_id' => $contactID,
        'invoice_id' => '',
        'trxn_id' => '',
      ]);
    }
    return [$groupID, $groupID2];
  }

  /**
   * Test Deferred Revenue Report.
   */
  public function testDeferredRevenueReport(): void {
    $this->individualCreate([], 'first');
    $this->individualCreate([], 'second');
    Civi::settings()->set('deferred_revenue_enabled', TRUE);
    $this->contributionCreate(
      [
        'contact_id' => $this->ids['Contact']['first'],
        'receive_date' => '2016-10-01',
        'revenue_recognition_date' => date('Y-m-t', strtotime(date('ymd') . '+3 month')),
        'financial_type_id' => 2,
      ]
    );
    $this->contributionCreate(
      [
        'contact_id' => $this->ids['Contact']['first'],
        'revenue_recognition_date' => date('Y-m-t', strtotime(date('ymd') . '+22 month')),
        'financial_type_id' => 4,
        'trxn_id' => NULL,
        'invoice_id' => NULL,
      ]
    );
    $this->contributionCreate(
      [
        'contact_id' => $this->ids['Contact']['second'],
        'revenue_recognition_date' => date('Y-m-t', strtotime(date('ymd') . '+1 month')),
        'financial_type_id' => 4,
        'trxn_id' => NULL,
        'invoice_id' => NULL,
      ]
    );
    $this->contributionCreate(
      [
        'contact_id' => $this->ids['Contact']['second'],
        'receive_date' => '2016-03-01',
        'revenue_recognition_date' => date('Y-m-t', strtotime(date('ymd') . '+4 month')),
        'financial_type_id' => 2,
        'trxn_id' => NULL,
        'invoice_id' => NULL,
      ]
    );
    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'contribute/deferredrevenue',
    ]);
    $this->assertEquals(2, $rows['count'], 'Report failed to get row count');
    $count = [2, 1];
    foreach ($rows['values'] as $row) {
      $this->assertCount(array_pop($count), $row['rows'], 'Report failed to get row count');
    }
  }

  /**
   * Test the custom data order by works when not in select.
   *
   * @dataProvider getMembershipAndContributionReportTemplatesForGroupTests
   *
   * @param string $template
   *   Report template unique identifier.
   */
  public function testReportsCustomDataOrderBy(string $template): void {
    $this->createCustomGroupWithFieldOfType();
    $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => $template,
      'contribution_or_soft_value' => 'contributions_only',
      'order_bys' => [['column' => 'custom_' . $this->ids['CustomField']['text'], 'order' => 'ASC']],
    ]);
  }

  /**
   * Test the group filter works on the various reports.
   *
   * @dataProvider getMembershipAndContributionReportTemplatesForGroupTests
   *
   * @param string $template
   *   Report template unique identifier.
   *
   * @throws \CRM_Core_Exception
   */
  public function testReportsWithNoTInSmartGroupFilter(string $template): void {
    $groupID = $this->setUpPopulatedGroup();
    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => $template,
      'gid_value' => [$groupID],
      'gid_op' => 'notin',
      'options' => ['metadata' => ['sql']],
    ]);
    $this->assertNumberOfContactsInResult(2, $rows, $template);
  }

  /**
   * Test we don't get a fatal grouping with various frequencies.
   */
  public function testActivitySummaryGroupByFrequency(): void {
    $this->createContactsWithActivities();
    foreach (['MONTH', 'YEARWEEK', 'QUARTER', 'YEAR'] as $frequency) {
      $params = [
        'report_id' => 'activitySummary',
        'fields' => [
          'activity_type_id' => 1,
          'duration' => 1,
          // id is "total activities", which is required by default(?)
          'id' => 1,
        ],
        'group_bys' => ['activity_date_time' => 1],
        'group_bys_freq' => ['activity_date_time' => $frequency],
        'options' => ['metadata' => ['sql']],
      ];
      $rowsSql = $this->callAPISuccess('report_template', 'getrows', $params)['metadata']['sql'];
      $statsSql = $this->callAPISuccess('report_template', 'getstatistics', $params)['metadata']['sql'];
      switch ($frequency) {
        case 'YEAR':
          // Year only contains one grouping.
          // Also note the extra space.
          $this->assertStringContainsString('GROUP BY  YEAR(activity_civireport.activity_date_time)', $rowsSql[1], "Failed for frequency $frequency");
          $this->assertStringContainsString('GROUP BY  YEAR(activity_civireport.activity_date_time)', $statsSql[1], "Failed for frequency $frequency");
          break;

        default:
          $this->assertStringContainsString("GROUP BY YEAR(activity_civireport.activity_date_time), {$frequency}(activity_civireport.activity_date_time)", $rowsSql[1], "Failed for frequency $frequency");
          $this->assertStringContainsString("GROUP BY YEAR(activity_civireport.activity_date_time), {$frequency}(activity_civireport.activity_date_time)", $statsSql[1], "Failed for frequency $frequency");
          break;
      }
    }
  }

  /**
   * Test activity details report - requiring all current fields to be output.
   */
  public function testActivityDetails(): void {
    $this->createContactsWithActivities();
    // Add employers for created contacts.
    foreach ($this->contactIDs as $i => $contactID) {
      $organizationIDs[] = $organizationID = $this->organizationCreate(['organization_name' => 'Test Organization ' . $i]);
      $this->callAPISuccess('Contact', 'create', ['id' => $contactID, 'employer_id' => $organizationID]);
    }

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
      'civicrm_contact_contact_source' => 'Łąchowski-Roberts, Anthony II',
      'civicrm_contact_contact_assignee' => '<a title=\'View Contact Summary for this Contact\' href=\'/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=' . $this->contactIDs[1] . '\'>Łąchowski-Roberts, Anthony II</a>',
      'civicrm_contact_contact_target' => '<a title=\'View Contact Summary for this Contact\' href=\'/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=' . $this->contactIDs[0] . '\'>Brzęczysław, Anthony II</a>; <a title=\'View Contact Summary for this Contact\' href=\'/index.php?q=civicrm/contact/view&amp;reset=1&amp;cid=' . $this->contactIDs[1] . '\'>Łąchowski-Roberts, Anthony II</a>',
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
      'civicrm_activity_activity_date_time' => date('Y-m-d 23:59:58'),
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
      'class' => NULL,
      'civicrm_contact_contact_source_employer_id' => $organizationIDs[2],
      'civicrm_contact_contact_assignee_employer_id' => $organizationIDs[1],
      'civicrm_contact_contact_target_employer_id' => $organizationIDs[0] . ';' . $organizationIDs[1],
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
   * Activity Details report has some whack-a-mole to fix when filtering on null/not null.
   */
  public function testActivityDetailsNullFilters(): void {
    $this->createContactsWithActivities();
    $params = [
      'report_id' => 'activity',
      'location_op' => 'nll',
      'location_value' => '',
    ];
    $rowsWithoutLocation = $this->callAPISuccess('report_template', 'getrows', $params)['values'];
    $this->assertEmpty($rowsWithoutLocation);
    $params['location_op'] = 'nnll';
    $rowsWithLocation = $this->callAPISuccess('report_template', 'getrows', $params)['values'];
    $this->assertCount(1, $rowsWithLocation);
    // Test for CRM-18356 - activity shouldn't appear if target contact filter is null.
    $params = [
      'report_id' => 'activity',
      'contact_target_op' => 'nll',
      'contact_target_value' => '',
    ];
    $rowsWithNullTarget = $this->callAPISuccess('report_template', 'getrows', $params)['values'];
    $this->assertEmpty($rowsWithNullTarget);
  }

  /**
   * Test the source contact filter works.
   *
   * @throws \CRM_Core_Exception
   */
  public function testActivityDetailsContactFilter(): void {
    $this->createContactsWithActivities();
    $params = [
      'report_id' => 'activity',
      'contact_source_op' => 'has',
      'contact_source_value' => 'z',
      'options' => ['metadata' => ['sql']],
    ];
    $rows = $this->callAPISuccess('report_template', 'getrows', $params);
    $this->assertStringContainsString("civicrm_contact_source.sort_name LIKE '%z%'", $rows['metadata']['sql'][3]);
  }

  /**
   * Set up some activity data..... use some chars that challenge our utf handling.
   */
  public function createContactsWithActivities(): void {
    $this->contactIDs[] = $this->individualCreate(['last_name' => 'Brzęczysław', 'email' => 'techo@spying.com']);
    $this->contactIDs[] = $this->individualCreate(['last_name' => 'Łąchowski-Roberts']);
    $this->contactIDs[] = $this->individualCreate(['last_name' => 'Łąchowski-Roberts']);

    $this->activityID = $this->callAPISuccess('Activity', 'create', [
      'subject' => 'Very secret meeting',
      'activity_date_time' => date('Y-m-d 23:59:58'),
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_type_id' => 'Meeting',
      'source_contact_id' => $this->contactIDs[2],
      'target_contact_id' => [$this->contactIDs[0], $this->contactIDs[1]],
      'assignee_contact_id' => $this->contactIDs[1],
    ])['id'];
  }

  /**
   * Test the group filter works on the contribution summary.
   */
  public function testContributionDetailTotalHeader(): void {
    $contactID = $this->individualCreate();
    $contactID2 = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $contactID, 'api.ContributionSoft.create' => ['amount' => 5, 'contact_id' => $contactID2]]);
    $template = 'contribute/detail';
    $this->callAPISuccess('report_template', 'getrows', [
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
      'options' => ['metadata' => ['sql']],
    ]);
  }

  /**
   * Test contact subtype filter on summary report.
   */
  public function testContactSubtypeNotNull(): void {
    $this->individualCreate(['contact_sub_type' => ['Student', 'Parent']]);
    $this->individualCreate();

    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'contact/summary',
      'contact_sub_type_op' => 'nnll',
      'contact_sub_type_value' => [],
      'contact_type_op' => 'eq',
      'contact_type_value' => 'Individual',
    ]);
    $this->assertEquals(1, $rows['count']);
  }

  /**
   * Test contact subtype filter on summary report.
   */
  public function testContactSubtypeNull(): void {
    $this->individualCreate(['contact_sub_type' => ['Student', 'Parent']]);
    $this->individualCreate();

    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'contact/summary',
      'contact_sub_type_op' => 'nll',
      'contact_sub_type_value' => [],
      'contact_type_op' => 'eq',
      'contact_type_value' => 'Individual',
    ]);
    $this->assertEquals(1, $rows['count']);
  }

  /**
   * Test contact subtype filter on summary report.
   */
  public function testContactSubtypeIn(): void {
    $this->individualCreate(['contact_sub_type' => ['Student', 'Parent']]);
    $this->individualCreate();

    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'contact/summary',
      'contact_sub_type_op' => 'in',
      'contact_sub_type_value' => ['Student'],
      'contact_type_op' => 'in',
      'contact_type_value' => 'Individual',
    ]);
    $this->assertEquals(1, $rows['count']);
  }

  /**
   * Test contact subtype filter on summary report.
   */
  public function testContactSubtypeNotIn(): void {
    $this->individualCreate(['contact_sub_type' => ['Student', 'Parent']]);
    $this->individualCreate();

    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'contact/summary',
      'contact_sub_type_op' => 'notin',
      'contact_sub_type_value' => ['Student'],
      'contact_type_op' => 'in',
      'contact_type_value' => 'Individual',
    ]);
    $this->assertEquals(1, $rows['count']);
  }

  /**
   * Test PCP report to ensure total donors and total committed is accurate.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPcpReportTotals(): void {
    $donor1ContactId = $this->individualCreate();
    $donor2ContactId = $this->individualCreate(['last_name' => 'Black']);
    $donor3ContactId = $this->individualCreate(['last_name' => 'Cherry']);

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
    $pcpBlock = CRM_PCP_BAO_PCPBlock::writeRecord($blockParams);

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
    $pcp1 = CRM_PCP_BAO_PCP::writeRecord($pcpParams);

    // Nice work. Now, let's create a second PCP page.
    $pcpParams = $this->pcpParams();
    // Keep track of the owner of the page.
    $pcpOwnerContact2Id = $pcpParams['contact_id'];
    Contact::update()->addWhere('id', '=', $pcpOwnerContact2Id)->setValues(['last_name' => 'Green'])->execute();
    // We're using the same pcpBlock id and contribution page that we created above.
    $pcpParams['pcp_block_id'] = $pcpBlock->id;
    $pcpParams['page_id'] = $contribution_page_id;
    $pcpParams['page_type'] = 'contribute';
    $pcp2 = CRM_PCP_BAO_PCP::writeRecord($pcpParams);

    // Get soft credit types, with the name column as the key.
    $soft_credit_types = CRM_Core_PseudoConstant::get('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', ['flip' => TRUE, 'labelColumn' => 'name']);
    $pcp_soft_credit_type_id = $soft_credit_types['pcp'];

    // Create two contributions assigned to this contribution page and
    // assign soft credits appropriately.
    // FIRST...
    $contribution1params = [
      'contact_id' => $donor1ContactId,
      'contribution_page_id' => $contribution_page_id,
      'total_amount' => '75.00',
    ];
    $c1 = $this->contributionCreate($contribution1params);
    // Now the soft contribution.
    $p = [
      'contribution_id' => $c1,
      'pcp_id' => $pcp1->id,
      'contact_id' => $pcpOwnerContact1Id,
      'amount' => 75.00,
      'currency' => 'USD',
      'soft_credit_type_id' => $pcp_soft_credit_type_id,
    ];
    $this->callAPISuccess('contribution_soft', 'create', $p);
    // SECOND...
    $contribution2params = [
      'contact_id' => $donor2ContactId,
      'contribution_page_id' => $contribution_page_id,
      'total_amount' => '25.00',
    ];
    $c2 = $this->contributionCreate($contribution2params);
    // Now the soft contribution.
    $p = [
      'contribution_id' => $c2,
      'pcp_id' => $pcp1->id,
      'contact_id' => $pcpOwnerContact1Id,
      'amount' => 25.00,
      'currency' => 'USD',
      'soft_credit_type_id' => $pcp_soft_credit_type_id,
    ];
    $this->callAPISuccess('contribution_soft', 'create', $p);

    // Create one contributions assigned to the second PCP page
    $contribution3params = [
      'contact_id' => $donor3ContactId,
      'contribution_page_id' => $contribution_page_id,
      'total_amount' => '200.00',
    ];
    $c3 = $this->contributionCreate($contribution3params);
    // Now the soft contribution.
    $this->callAPISuccess('ContributionSoft', 'create', [
      'contribution_id' => $c3,
      'pcp_id' => $pcp2->id,
      'contact_id' => $pcpOwnerContact2Id,
      'amount' => 200.00,
      'currency' => 'USD',
      'soft_credit_type_id' => $pcp_soft_credit_type_id,
    ]);

    $template = 'contribute/pcp';
    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => $template,
      'title' => 'My PCP',
      'fields' => [
        'amount_1' => '1',
        'soft_id' => '1',
      ],
    ]);
    $values = $rows['values'][0];
    $this->assertEquals(100.00, $values['civicrm_contribution_soft_amount_1_sum'], 'Total committed should be $100');
    $this->assertEquals(2, $values['civicrm_contribution_soft_soft_id_count'], 'Total donors should be 2');
  }

  /**
   * Test a report that uses getAddressColumns();
   */
  public function testGetAddressColumns(): void {
    $template = 'event/participantlisting';
    $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => $template,
      'fields' => [
        'sort_name' => '1',
        'street_address' => '1',
      ],
    ]);
  }

  /**
   * Test that the contribution aggregate by relationship report filters
   * by financial type.
   */
  public function testContributionAggregateByRelationship(): void {
    $contact = $this->individualCreate();
    // Two contributions with different financial types.
    // We don't really care which types, just different.
    $this->contributionCreate(['contact_id' => $contact, 'receive_date' => (date('Y') - 1) . '-07-01', 'financial_type_id' => 1, 'total_amount' => '10']);
    $this->contributionCreate(['contact_id' => $contact, 'receive_date' => (date('Y') - 1) . '-08-01', 'financial_type_id' => 2, 'total_amount' => '20']);
    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'contribute/history',
      'financial_type_id_op' => 'in',
      'financial_type_id_value' => [1],
      'options' => ['metadata' => ['sql']],
      'fields' => [
        'relationship_type_id' => 1,
        'total_amount' => 1,
      ],
    ]);

    // Hmm it has styling in it before being sent to the template. If that gets fixed then will need to update this.
    $this->assertEquals('<strong>10.00</strong>', $rows['values'][$contact]['civicrm_contribution_total_amount'], 'should only include the $10 contribution');

    $this->callAPISuccess('Contact', 'delete', ['id' => $contact]);
  }

  /**
   * Basic test of the repeat contributions report.
   */
  public function testRepeatContributions(): void {
    // our sorting options are limited in this report - default is last name so let's ensure order
    $contact1 = $this->individualCreate(['last_name' => 'Aardvark']);
    $contact2 = $this->individualCreate(['last_name' => 'Zebra']);
    $this->contributionCreate(['contact_id' => $contact1, 'receive_date' => (date('Y') - 1) . '-07-01', 'financial_type_id' => 1, 'total_amount' => '10']);
    $this->contributionCreate(['contact_id' => $contact1, 'receive_date' => (date('Y') - 1) . '-08-01', 'financial_type_id' => 1, 'total_amount' => '20']);
    $this->contributionCreate(['contact_id' => $contact1, 'receive_date' => date('Y') . '-01-01', 'financial_type_id' => 1, 'total_amount' => '40']);
    $this->contributionCreate(['contact_id' => $contact2, 'receive_date' => (date('Y') - 1) . '-09-01', 'financial_type_id' => 1, 'total_amount' => '80']);
    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'contribute/repeat',
      'receive_date1' => 'previous.year',
      'receive_date2' => 'this.year',
      'fields' => [
        'sort_name' => 1,
      ],
    ]);

    $this->assertCount(2, $rows['values']);

    // Should have for both this year and last, and last year was multiple.
    $this->assertEquals($contact1, $rows['values'][0]['contact_civireport_id'], "doesn't seem to be the right contact 1");
    $this->assertSame('30.00', $rows['values'][0]['contribution1_total_amount_sum']);
    $this->assertSame('2', $rows['values'][0]['contribution1_total_amount_count']);
    $this->assertSame('40.00', $rows['values'][0]['contribution2_total_amount_sum']);
    $this->assertSame('1', $rows['values'][0]['contribution2_total_amount_count']);

    // Should only have for last year.
    $this->assertEquals($contact2, $rows['values'][1]['contact_civireport_id'], "doesn't seem to be the right contact 2");
    $this->assertSame('80.00', $rows['values'][1]['contribution1_total_amount_sum']);
    $this->assertSame('1', $rows['values'][1]['contribution1_total_amount_count']);
    $this->assertNull($rows['values'][1]['contribution2_total_amount_sum']);
    $this->assertNull($rows['values'][1]['contribution2_total_amount_count']);

    $this->callAPISuccess('Contact', 'delete', ['id' => $contact1]);
    $this->callAPISuccess('Contact', 'delete', ['id' => $contact2]);
  }

  /**
   * Convoluted test of the convoluted logging detail report.
   *
   * In principle it's just make an update and get the report and see if it
   * matches the update.
   * In practice, besides some setup and trigger-wrangling, the report isn't
   * useful for activities, so we're checking activity_contact records, and
   * because of how an activity update works that's actually a delete+insert.
   *
   * @throws \CRM_Core_Exception
   */
  public function testLoggingDetail(): void {
    \Civi::settings()->set('logging', 1);
    $this->createContactsWithActivities();
    $this->doQuestionableStuffInASeparateFunctionSoNobodyNotices();

    // Do something that creates an update record.
    $this->callAPISuccess('Activity', 'create', [
      'id' => $this->activityID,
      'assignee_contact_id' => $this->contactIDs[0],
      'details' => 'Edited details',
    ]);

    // In normal UI flow you would go to the summary report and drill down,
    // but here we need to go directly to the connection id, so find out what
    // it was.
    $queryParams = [1 => [$this->activityID, 'Integer']];
    $log_conn_id = CRM_Core_DAO::singleValueQuery("SELECT log_conn_id FROM log_civicrm_activity WHERE id = %1 AND log_action='UPDATE' LIMIT 1", $queryParams);

    // There should be only one instance of this after enabling so we can
    // just specify the template id as the lookup criteria.
    $instance_id = $this->callAPISuccess('report_instance', 'getsingle', [
      'return' => ['id'],
      'report_id' => 'logging/contact/detail',
    ])['id'];

    $_GET = $_REQUEST = [
      'reset' => '1',
      'log_conn_id' => $log_conn_id,
      'q' => "civicrm/report/instance/$instance_id",
    ];
    $values = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'logging/contact/detail',
    ])['values'];

    // Note this is a delete+insert which is logically equivalent to update
    $expectedValues = [
      // here's the delete
      0 => [
        'field' => [
          0 => 'Activity ID (id: 2)',
          1 => 'Contact ID (id: 2)',
          2 => 'Activity Contact Type (id: 2)',
        ],
        'from' => [
          0 => 'Very secret meeting (id: 1)',
          1 => 'Mr. Anthony Łąchowski-Roberts II (id: 4)',
          2 => 'Activity Assignees',
        ],
        'to' => [
          0 => '',
          1 => '',
          2 => '',
        ],
      ],
      // this is the insert
      1 => [
        'field' => [
          0 => 'Activity ID (id: 5)',
          1 => 'Contact ID (id: 5)',
          2 => 'Activity Contact Type (id: 5)',
        ],
        'from' => [
          0 => '',
          1 => '',
          2 => '',
        ],
        'to' => [
          0 => 'Very secret meeting (id: 1)',
          1 => 'Mr. Anthony Brzęczysław II (id: 3)',
          2 => 'Activity Assignees',
        ],
      ],
    ];
    $this->assertEquals($expectedValues, $values);

    \Civi::settings()->set('logging', 0);
  }

  /**
   * The issue is that in a unit test the log_conn_id is going to
   * be the same throughout the entire test, which is not how it works normally
   * when you have separate page requests. So use the fact that the conn_id
   * format is controlled by a hidden variable, so we can force different
   * conn_id's during initialization and after.
   * If we don't do this, then the report thinks EVERY log record is part
   * of the one change detail.
   *
   * On the plus side, this doesn't affect other tests since if they enable
   * logging then that'll just recreate the variable and triggers.
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  private function doQuestionableStuffInASeparateFunctionSoNobodyNotices(): void {
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_setting WHERE name='logging_uniqueid_date'");
    // Now we have to rebuild triggers because the format formula is stored in
    // every trigger.
    CRM_Core_Config::singleton(TRUE, TRUE);
    \Civi::service('sql_triggers')->rebuild(NULL, TRUE);
  }

  /**
   * Implement hook to restrict to test group 1.
   *
   * @implements hook_aclGroups
   *
   * @param string $type
   * @param int $contactID
   * @param string $tableName
   * @param array $allGroups
   * @param array $ids
   *
   * @noinspection PhpUnusedParameterInspection
   */
  public function aclGroupOnly(string $type, int $contactID, string $tableName, array $allGroups, array &$ids): void {
    if ($tableName === 'civicrm_group') {
      $ids = [$this->aclGroupID];
    }
  }

  /**
   * Implements hook to limit to contacts only in the aclGroup
   *
   * @param string $type
   * @param array $tables
   * @param array $whereTables
   * @param int|null $contactID
   * @param string|null $where
   *
   * @noinspection PhpUnusedParameterInspection
   */
  public function aclGroupContactsOnly(string $type, array &$tables, array &$whereTables, ?int &$contactID, ?string &$where): void {
    if (!empty($where)) {
      $where .= ' AND ';
    }
    $where .= 'contact_a.id IN (SELECT contact_id FROM civicrm_group_contact WHERE status = \'Added\' AND group_id = ' . $this->aclGroupID . ')';
  }

}

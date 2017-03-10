<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
  protected $_apiversion = 3;

  /**
   * Our group reports use an alter so transaction cleanup won't work.
   *
   * @throws \Exception
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(array('civicrm_group', 'civicrm_saved_search', 'civicrm_group_contact'));
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
      'contribute/topDonor' => 'construction of query in postProcess makes inaccessible ',
      'event/income' => 'I do no understand why but error is Call to undefined method CRM_Report_Form_Event_Income::from() in CRM/Report/Form.php on line 2120',
      'logging/contact/summary' => '(likely to be test related) probably logging off Undefined index: Form/Contact/LoggingSummary.php(231): PHP',
      'logging/contribute/summary' => '(likely to be test related) probably logging off DB Error: no such table',
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
    return array(array('contribute/summary'), array('contribute/detail'), array('contribute/repeat'), array('contribute/topDonor'));
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
   */
  public function testContributionSummaryWithSmartGroupFilter() {
    $groupID = $this->setUpPopulatedSmartGroup();
    $rows = $this->callAPISuccess('report_template', 'getrows', array(
      'report_id' => 'contribute/summary',
      'gid_value' => $groupID,
      'gid_op' => 'in',
      'options' => array('metadata' => array('sql')),
    ));
    $this->assertEquals(3, $rows['values'][0]['civicrm_contribution_total_amount_count']);

  }

  /**
   * Test the group filter works on the contribution summary (with a smart group).
   */
  public function testContributionSummaryWithNotINSmartGroupFilter() {
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
   * Test the group filter works on the contribution summary (with a smart group).
   *
   * @dataProvider getContributionReportTemplates
   *
   * @param string $template
   *   Report template unique identifier.
   */
  public function testContributionSummaryWithNonSmartGroupFilter($template) {
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
    }

    // Refresh the cache for test purposes. It would be better to alter to alter the GroupContact add function to add contacts to the cache.
    CRM_Contact_BAO_GroupContactCache::remove($groupID, FALSE);
    return $groupID;
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
  public function setUpPopulatedGroup() {
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
    }

    // Refresh the cache for test purposes. It would be better to alter to alter the GroupContact add function to add contacts to the cache.
    CRM_Contact_BAO_GroupContactCache::remove($groupID, FALSE);
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

}

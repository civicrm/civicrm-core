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

use Civi\Api4\Contribution;

/**
 * Pager totals of report templates.
 *
 * @group headless
 */
class CRM_Report_Form_PagerTest extends CiviReportTestCase {

  /**
   * Run a report with a small page size and return the report and its pager.
   *
   * @param string $reportClass
   * @param int $page
   * @param array $inputParams
   *
   * @return array
   *   [CRM_Report_Form $report, CRM_Utils_Pager $pager]
   */
  private function runPagedReport(string $reportClass, int $page = 1, array $inputParams = []): array {
    CRM_Utils_GlobalStack::singleton()->push(['_REQUEST' => ['rowCount' => 2, 'crmPID' => $page]]);
    try {
      $report = $this->getReportObject($reportClass, $inputParams);
    }
    finally {
      CRM_Utils_GlobalStack::singleton()->pop();
    }
    return [$report, $report::getTemplate()->getTemplateVars('pager')];
  }

  /**
   * Create three donors with one contribution each this year and last year.
   *
   * @return int[]
   *   Contact IDs, largest donor first.
   */
  private function createDonors(): array {
    $contactIDs = [];
    foreach ([300, 200, 100] as $amount) {
      $contactID = $this->individualCreate([], count($contactIDs));
      $this->contributionCreate(['contact_id' => $contactID, 'total_amount' => $amount, 'receive_date' => date('Y') . '-01-01']);
      $this->contributionCreate(['contact_id' => $contactID, 'total_amount' => $amount, 'receive_date' => (date('Y') - 1) . '-01-01']);
      $contactIDs[] = $contactID;
    }
    return $contactIDs;
  }

  /**
   * @param int[] $contactIDs
   */
  private function deleteContributionsThisYear(array $contactIDs): void {
    Contribution::delete(FALSE)
      ->addWhere('contact_id', 'IN', $contactIDs)
      ->addWhere('receive_date', '>=', date('Y') . '-01-01')
      ->execute();
  }

  /**
   * Create three expired memberships.
   */
  private function createLapsedMemberships(): void {
    $membershipTypeID = $this->membershipTypeCreate();
    foreach ($this->createDonors() as $contactID) {
      $this->createTestEntity('Membership', [
        'contact_id' => $contactID,
        'membership_type_id' => $membershipTypeID,
        'join_date' => '2020-01-01',
        'start_date' => '2020-01-01',
        'end_date' => '2020-12-31',
        'status_id:name' => 'Expired',
        'is_override' => TRUE,
      ], 'lapsed_' . $contactID);
    }
  }

  public function testEmptyReportTotalIsZero(): void {
    [, $pager] = $this->runPagedReport('CRM_Report_Form_Contribute_Detail');
    $this->assertSame(0, (int) $pager->_totalItems);
  }

  public function testDetailTotalCountsAllPages(): void {
    $this->createDonors();
    [$report, $pager] = $this->runPagedReport('CRM_Report_Form_Contribute_Detail');
    $this->assertCount(2, $report->getResultSet());
    $this->assertSame(6, (int) $pager->_totalItems);
  }

  public function testDetailTotalOnLastPage(): void {
    $this->createDonors();
    [$report, $pager] = $this->runPagedReport('CRM_Report_Form_Contribute_Detail', 3);
    $this->assertCount(2, $report->getResultSet());
    $this->assertSame(6, (int) $pager->_totalItems);
  }

  public function testLegacyFoundRowsQueryTotalCountsAllPages(): void {
    $this->createDonors();
    [$report, $pager] = $this->runPagedReport('CRM_Report_Form_LegacyFoundRowsForm');
    $this->assertCount(2, $report->getResultSet());
    $this->assertSame(6, (int) $pager->_totalItems);
  }

  public function testRepeatTotalCountsAllContacts(): void {
    $this->createDonors();
    [$report, $pager] = $this->runPagedReport('CRM_Report_Form_Contribute_Repeat');
    $this->assertCount(2, $report->getResultSet());
    $this->assertSame(3, (int) $pager->_totalItems);
  }

  public function testLybuntTotalCountsAllContacts(): void {
    $this->deleteContributionsThisYear($this->createDonors());
    [, $pager] = $this->runPagedReport('CRM_Report_Form_Contribute_Lybunt');
    // Three donors plus the grand total row.
    $this->assertSame(4, (int) $pager->_totalItems);
  }

  public function testSybuntTotalCountsAllContacts(): void {
    $contactIDs = $this->createDonors();
    $this->deleteContributionsThisYear($contactIDs);
    [, $pager] = $this->runPagedReport('CRM_Report_Form_Contribute_Sybunt');
    $this->assertSame(3, (int) $pager->_totalItems);
  }

  public function testEventSummaryTotalCountsAllEvents(): void {
    $eventIDs = [];
    foreach (['a', 'b', 'c'] as $identifier) {
      $eventIDs[] = $this->eventCreateUnpaid([], $identifier)['id'];
    }
    [$report, $pager] = $this->runPagedReport('CRM_Report_Form_Event_Summary', 1, ['filters' => ['id_op' => 'in', 'id_value' => implode(',', $eventIDs)]]);
    $this->assertCount(2, $report->getResultSet());
    $this->assertSame(3, (int) $pager->_totalItems);
  }

  public function testEventIncomeCountSummaryTotalCountsAllEvents(): void {
    foreach (['a', 'b', 'c'] as $identifier) {
      $this->eventCreateUnpaid([], $identifier);
    }
    [, $pager] = $this->runPagedReport('CRM_Report_Form_Event_IncomeCountSummary');
    // Three events plus the grand total row.
    $this->assertSame(4, (int) $pager->_totalItems);
  }

  public function testPledgeDetailTotalCountsAllPledges(): void {
    foreach ($this->createDonors() as $contactID) {
      $this->pledgeCreate(['contact_id' => $contactID]);
    }
    [, $pager] = $this->runPagedReport('CRM_Report_Form_Pledge_Detail');
    $this->assertSame(3, (int) $pager->_totalItems);
  }

  public function testMemberLapseTotalCountsAllMemberships(): void {
    $this->createLapsedMemberships();
    [$report, $pager] = $this->runPagedReport('CRM_Report_Form_Member_Lapse');
    $this->assertCount(2, $report->getResultSet());
    $this->assertSame(3, (int) $pager->_totalItems);
  }

  public function testLegacyOwnFoundRowsQueryTotalCountsAllPages(): void {
    $this->createLapsedMemberships();
    [$report, $pager] = $this->runPagedReport('CRM_Report_Form_LegacyOwnQueryForm');
    $this->assertCount(2, $report->getResultSet());
    $this->assertSame(3, (int) $pager->_totalItems);
  }

  public function testRowsFoundAcceptsLimitSyntaxes(): void {
    $this->createDonors();
    $report = new CRM_Report_Form_Contribute_Detail();
    $rowsFound = fn(string $sql, int $fetched) => (function () use ($sql, $fetched) {
      $this->setRowsFound($sql, $fetched);
      return $this->_rowsFound;
    })->call($report);
    $sql = 'SELECT id FROM civicrm_contribution';
    $this->assertSame(6, $rowsFound("$sql LIMIT 2", 2));
    $this->assertSame(6, $rowsFound("$sql LIMIT 2, 2", 2));
    $this->assertSame(6, $rowsFound("$sql LIMIT 2 OFFSET 2", 2));
    $this->assertSame(6, $rowsFound("$sql LIMIT 4 OFFSET 4", 2));
    $this->assertSame(6, $rowsFound($sql, 6));
  }

  public function testTopDonorPagesAndContinuesRanks(): void {
    $contactIDs = $this->createDonors();
    [$report, $pager] = $this->runPagedReport('CRM_Report_Form_Contribute_TopDonor', 2);
    $rows = $report->getResultSet();
    $this->assertSame(3, (int) $pager->_totalItems);
    $this->assertCount(1, $rows);
    $this->assertEquals($contactIDs[2], $rows[0]['civicrm_contact_id']);
    $this->assertSame(3, $rows[0]['civicrm_donor_rank']);
  }

}

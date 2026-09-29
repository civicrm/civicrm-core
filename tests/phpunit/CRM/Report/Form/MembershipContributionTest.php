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

/**
 * Reports linking memberships to the contributions that paid for them.
 *
 * @group headless
 */
class CRM_Report_Form_MembershipContributionTest extends CiviReportTestCase {

  /**
   * @var int
   */
  private $membershipID;

  /**
   * @var int
   */
  private $contributionID;

  public function setUp(): void {
    parent::setUp();
    $this->createMembershipPaidOverTwoLineItems();
  }

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    Civi::cache('long')->clear();
    parent::tearDown();
  }

  public function testMemberSummaryCountsContributionOnce(): void {
    $rows = $this->getReportObject('CRM_Report_Form_Member_Summary', [
      'fields' => ['membership_type_id', 'total_amount'],
      'group_bys' => ['membership_type_id'],
    ])->getResultSet();

    $this->assertCount(1, $rows);
    $this->assertEquals(1, $rows[0]['civicrm_contribution_total_amount_count']);
    $this->assertEquals(100, $rows[0]['civicrm_contribution_total_amount_sum']);
  }

  /**
   * Legacy payment records with no matching line item still count.
   */
  public function testMemberSummaryIncludesLegacyPaymentRecords(): void {
    $legacyContributionID = $this->contributionCreate([
      'contact_id' => $this->individualCreate(),
      'financial_type_id' => 'Member Dues',
      'total_amount' => 30,
    ]);
    CRM_Core_DAO::executeQuery('INSERT INTO civicrm_membership_payment (membership_id, contribution_id) VALUES (%1, %2)', [
      1 => [$this->membershipID, 'Integer'],
      2 => [$legacyContributionID, 'Integer'],
    ]);
    // A line item for the same pair must not count it twice.
    CRM_Core_DAO::executeQuery('INSERT INTO civicrm_membership_payment (membership_id, contribution_id) VALUES (%1, %2)', [
      1 => [$this->membershipID, 'Integer'],
      2 => [$this->contributionID, 'Integer'],
    ]);
    Civi::cache('long')->clear();
    $this->assertTrue(CRM_Price_BAO_LineItem::siteHasMembershipPaymentRecordsNotReflectedInLineItems());

    $rows = $this->getReportObject('CRM_Report_Form_Member_Summary', [
      'fields' => ['membership_type_id', 'total_amount'],
      'group_bys' => ['membership_type_id'],
    ])->getResultSet();

    $this->assertEquals(2, $rows[0]['civicrm_contribution_total_amount_count']);
    $this->assertEquals(130, $rows[0]['civicrm_contribution_total_amount_sum']);
  }

  public function testMemberDetailListsContributionOnce(): void {
    $rows = $this->getReportObject('CRM_Report_Form_Member_Detail', [
      'fields' => ['contribution_id'],
      // Not a group by field, so this clears the default grouping by membership.
      'group_bys' => ['none'],
    ])->getResultSet();

    $this->assertEquals([$this->contributionID], array_column($rows, 'civicrm_contribution_contribution_id'));
  }

  /**
   * Creates a membership whose one contribution holds two line items for it.
   *
   * Core does this itself when a price set has more than one value for the
   * same membership type.
   */
  private function createMembershipPaidOverTwoLineItems(): void {
    // The line item split below is not reflected in the financial items.
    $this->isValidateFinancialsOnPostAssert = FALSE;
    $contactID = $this->individualCreate();
    $this->membershipID = $this->contactMembershipCreate(['contact_id' => $contactID]);
    $this->contributionID = $this->contributionCreate([
      'contact_id' => $contactID,
      'financial_type_id' => 'Member Dues',
      'total_amount' => 100,
    ]);
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_line_item SET entity_table = 'civicrm_membership', entity_id = %1, unit_price = 50, line_total = 50 WHERE contribution_id = %2",
      [1 => [$this->membershipID, 'Integer'], 2 => [$this->contributionID, 'Integer']]
    );
    CRM_Core_DAO::executeQuery(
      "INSERT INTO civicrm_line_item (entity_table, entity_id, contribution_id, label, qty, unit_price, line_total, financial_type_id)
       SELECT entity_table, entity_id, contribution_id, label, qty, unit_price, line_total, financial_type_id
       FROM civicrm_line_item WHERE contribution_id = %1",
      [1 => [$this->contributionID, 'Integer']]
    );
    // A site that no longer writes the legacy records is linked by line item alone.
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_membership_payment WHERE contribution_id = %1', [1 => [$this->contributionID, 'Integer']]);
  }

}

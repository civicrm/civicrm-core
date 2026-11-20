<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | Use of this source code is governed by the AGPL license with some  |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use Civi\Api4\LineItem;
use Civi\Api4\Order;
use Civi\Api4\PriceFieldValue;

/**
 * @group headless
 */
class CRM_Member_Page_RecurringContributionsTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'access CiviContribute', 'view all contacts'];
    $this->individualCreate([], 'member');
    $this->membershipTypeCreate([], 'general');
  }

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    \Civi::cache('long')->delete('siteHasMembershipPaymentRecordsNotReflectedInLineItems');
    parent::tearDown();
  }

  /**
   * A recurring contribution linked only by a legacy membership payment is listed.
   */
  public function testLegacyMembershipPaymentOnly(): void {
    $membershipID = $this->createTestEntity('Membership', [
      'contact_id' => $this->ids['Contact']['member'],
      'membership_type_id' => $this->ids['MembershipType']['general'],
    ])['id'];
    $legacyRecurID = $this->createLegacyRecurringContribution($membershipID);

    $this->assertEquals([$legacyRecurID], array_keys($this->getRecurRows($membershipID)));
  }

  /**
   * Recurring contributions linked by line item and by legacy membership payment are both listed.
   */
  public function testLineItemAndLegacyMembershipPayment(): void {
    [$membershipID, $lineItemRecurID] = $this->createMembershipOrder();
    $legacyRecurID = $this->createLegacyRecurringContribution($membershipID);

    $listed = array_keys($this->getRecurRows($membershipID));
    sort($listed);
    $this->assertEquals([$lineItemRecurID, $legacyRecurID], $listed);
  }

  /**
   * The payment processor column shows the processor's name.
   */
  public function testPaymentProcessorName(): void {
    $processorID = $this->paymentProcessorCreate(['name' => 'Recurring test processor']);
    [$membershipID, $recurID] = $this->createMembershipOrder(['payment_processor_id' => $processorID]);

    $this->assertSame('Recurring test processor', $this->getRecurRows($membershipID)[$recurID]['payment_processor']);
  }

  /**
   * Create a membership with a recurring contribution linked by its line item.
   *
   * @param array $recurValues
   *
   * @return int[]
   *   Membership ID and recurring contribution ID.
   */
  private function createMembershipOrder(array $recurValues = []): array {
    $priceFieldValue = PriceFieldValue::get(FALSE)
      ->addWhere('membership_type_id', '=', $this->ids['MembershipType']['general'])
      ->addSelect('id', 'price_field_id', 'amount')
      ->execute()->single();
    $contactID = $this->ids['Contact']['member'];
    $contribution = Order::create(FALSE)
      ->setContributionValues([
        'contact_id' => $contactID,
        'financial_type_id:name' => 'Member Dues',
        'receive_date' => 'now',
      ])
      ->setContributionRecurValues($recurValues + [
        'contact_id' => $contactID,
        'amount' => $priceFieldValue['amount'],
        'frequency_unit' => 'year',
        'frequency_interval' => 1,
        'currency' => 'USD',
      ])
      ->addLineItem([
        'entity_table' => 'civicrm_membership',
        'entity_id.membership_type_id' => $this->ids['MembershipType']['general'],
        'entity_id.contact_id' => $contactID,
        'price_field_id' => $priceFieldValue['price_field_id'],
        'price_field_value_id' => $priceFieldValue['id'],
        'qty' => 1,
        'unit_price' => $priceFieldValue['amount'],
        'line_total' => $priceFieldValue['amount'],
      ])
      ->execute()->single();
    $membershipID = LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->addWhere('entity_table', '=', 'civicrm_membership')
      ->addSelect('entity_id')
      ->execute()->single()['entity_id'];
    return [$membershipID, $contribution['contribution_recur_id']];
  }

  /**
   * Create a recurring contribution linked to the membership by a membership payment record only.
   *
   * @param int $membershipID
   *
   * @return int
   */
  private function createLegacyRecurringContribution(int $membershipID): int {
    $contactID = $this->ids['Contact']['member'];
    $recurID = $this->createTestEntity('ContributionRecur', [
      'contact_id' => $contactID,
      'amount' => 10,
      'frequency_unit' => 'month',
      'frequency_interval' => 1,
      'start_date' => 'now',
    ], 'legacy')['id'];
    $contributionID = $this->createTestEntity('Contribution', [
      'contact_id' => $contactID,
      'contribution_recur_id' => $recurID,
      'financial_type_id:name' => 'Member Dues',
      'total_amount' => 10,
      'receive_date' => 'now',
    ], 'legacy')['id'];
    // MembershipPayment has no APIv4 entity.
    $this->callAPISuccess('MembershipPayment', 'create', [
      'membership_id' => $membershipID,
      'contribution_id' => $contributionID,
    ]);
    return $recurID;
  }

  /**
   * Run the page and return the recurring contribution rows it assigns.
   *
   * @param int $membershipID
   *
   * @return array
   */
  private function getRecurRows(int $membershipID): array {
    \Civi::cache('long')->delete('siteHasMembershipPaymentRecordsNotReflectedInLineItems');
    $_REQUEST['membershipID'] = $membershipID;
    $_REQUEST['cid'] = $this->ids['Contact']['member'];
    $page = new CRM_Member_Page_RecurringContributions();
    ob_start();
    try {
      $page->run();
    }
    finally {
      ob_end_clean();
      unset($_REQUEST['membershipID'], $_REQUEST['cid']);
    }
    return CRM_Core_Smarty::singleton()->getTemplateVars('recurRows') ?? [];
  }

}

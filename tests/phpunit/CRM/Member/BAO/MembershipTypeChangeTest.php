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
use Civi\Api4\ContributionRecur;
use Civi\Api4\LineItem;
use Civi\Api4\Membership;
use Civi\Api4\Order;
use Civi\Api4\PriceFieldValue;
use Civi\Test\FormTrait;

/**
 * Tests for keeping the recurring contribution template in sync when a
 * membership type is manually changed (back office / API / import).
 *
 * The renewal flow (Civi\Membership\OrderCompleteSubscriber) renews the
 * membership type recorded against the template line item. When an auto-renew
 * membership has its type changed outside of an order, the template line item
 * must be repointed at the new type - and its financial type / amount / tax and
 * the recurring contribution amount kept consistent - so the next renewal
 * renews the type the user actually selected rather than reverting.
 *
 * Setup mirrors CRM_Member_Form_MembershipTest: AnnualFixed & AnnualRolling are
 * under $this->ids['Contact']['organization'] (so they share a price field -
 * the explicit PriceFieldValue path), while AnnualRollingOrg2 is under
 * $this->ids['Contact']['organization2'] (a different field - the fallback
 * path).
 *
 * @group headless
 */
class CRM_Member_BAO_MembershipTypeChangeTest extends CiviUnitTestCase {

  use CRMTraits_Financial_OrderTrait;
  use CRMTraits_Financial_PriceSetTrait;
  use FormTrait;

  /**
   * @throws \CRM_Core_Exception
   */
  public function setUp(): void {
    parent::setUp();

    $this->individualCreate([], 'member');
    $this->processorCreate();
    $this->organizationCreate([], 'organization');
    $this->organizationCreate([], 'organization2');

    $this->createTestEntity('MembershipType', [
      'name' => 'AnnualRolling',
      'member_of_contact_id' => $this->ids['Contact']['organization'],
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'minimum_fee' => 50,
      'financial_type_id:name' => 'Member Dues',
    ], 'AnnualRolling');

    $this->createTestEntity('MembershipType', [
      'name' => 'AnnualRolling2',
      'member_of_contact_id' => $this->ids['Contact']['organization'],
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'minimum_fee' => 120,
      'financial_type_id:name' => 'Member Dues',
    ], 'AnnualRolling2');

    $this->createTestEntity('MembershipType', [
      'name' => 'AnnualRollingOrg2',
      'member_of_contact_id' => $this->ids['Contact']['organization2'],
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'minimum_fee' => 75,
      'financial_type_id:name' => 'Member Dues',
    ], 'AnnualRollingOrg2');
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_uf_match', 'civicrm_email']);
    parent::tearDown();
  }

  /**
   * Build an auto-renew membership of the given type with a recurring
   * contribution and a template contribution whose single membership line item
   * points at that type's PriceFieldValue.
   *
   * @param string $fromTypeKey key into $this->ids['MembershipType']
   *
   * @return array ids the tests need
   * @throws \CRM_Core_Exception
   */
  private function setupAutoRenewMembership(string $fromTypeKey = 'AnnualRolling'): array {
    $fromTypeID = $this->ids['MembershipType'][$fromTypeKey];

    $fromValue = PriceFieldValue::get(FALSE)
      ->addSelect('id', 'price_field_id', 'amount', 'financial_type_id')
      ->addWhere('membership_type_id', '=', $fromTypeID)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();
    $this->assertNotEmpty($fromValue, "PriceFieldValue for {$fromTypeKey} should be auto-created with the membership type.");

    $priceFieldID = $fromValue['price_field_id'];
    $year = (int) (CRM_Utils_Time::date('Y')) - 1;

    // Build the recurring contribution, its (real) contribution, and the
    // membership in one order. Order::create links the recur to the membership
    // via the line item, and builds the membership line correctly (no stray
    // default contribution line to clean up).
    $contribution = Order::create(FALSE)
      ->setContributionValues([
        'contact_id' => $this->ids['Contact']['member'],
        'financial_type_id' => $fromValue['financial_type_id'],
        'receive_date' => $year . '-01-01',
      ])
      ->setContributionRecurValues([
        'contact_id' => $this->ids['Contact']['member'],
        'amount' => $fromValue['amount'],
        'frequency_unit' => 'year',
        'frequency_interval' => 1,
        'auto_renew' => TRUE,
        'currency' => 'USD',
        'contribution_status_id:name' => 'In Progress',
      ])
      ->addLineItem([
        'entity_table' => 'civicrm_membership',
        'entity_id.membership_type_id' => $fromTypeID,
        'entity_id.contact_id' => $this->ids['Contact']['member'],
        'entity_id.join_date' => $year . '-01-01',
        'entity_id.start_date' => $year . '-01-01',
        'entity_id.end_date' => $year . '-12-31',
        'price_field_id' => $priceFieldID,
        'price_field_value_id' => $fromValue['id'],
        'qty' => 1,
        'unit_price' => $fromValue['amount'],
        'line_total' => $fromValue['amount'],
        'membership_num_terms' => 1,
      ])
      ->execute()
      ->first();

    // Recover the recur and membership ids the order created/linked.
    $recurID = (int) Contribution::get(FALSE)
      ->addSelect('contribution_recur_id')
      ->addWhere('id', '=', $contribution['id'])
      ->execute()
      ->first()['contribution_recur_id'];
    $this->assertNotEmpty($recurID, 'Order should have created and linked a recurring contribution.');

    $membershipLine = LineItem::get(FALSE)
      ->addSelect('entity_id')
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->addWhere('entity_table', '=', 'civicrm_membership')
      ->execute()
      ->first();
    $membershipID = (int) $membershipLine['entity_id'];
    $this->assertNotEmpty($membershipID, 'Order should have created a membership for the line.');

    // The membership must carry the recur link, otherwise the BAO helper has
    // nothing to act on.
    $membership = Membership::get(FALSE)
      ->addSelect('contribution_recur_id')
      ->addWhere('id', '=', $membershipID)
      ->execute()
      ->first();
    $this->assertEquals($recurID, $membership['contribution_recur_id'], 'Order should link the membership to the recurring contribution.');

    // Derive the template contribution from the real contribution, exactly as
    // the renewal machinery does. This is what the BAO helper will later read
    // and update.
    $templateContributionID = (int) CRM_Contribute_BAO_ContributionRecur::ensureTemplateContributionExists($recurID);
    $this->assertNotEmpty($templateContributionID, 'A template contribution should be derivable from the order contribution.');

    // The BAO matches the template line by entity_id - assert that holds.
    $templateLine = LineItem::get(FALSE)
      ->addSelect('id', 'entity_id')
      ->addWhere('contribution_id', '=', $templateContributionID)
      ->addWhere('entity_table', '=', 'civicrm_membership')
      ->execute()
      ->first();
    $this->assertEquals($membershipID, $templateLine['entity_id'], 'Template membership line must carry entity_id = membership id.');

    return [
      'membership_id' => $membershipID,
      'contribution_recur_id' => $recurID,
      'template_contribution_id' => $templateContributionID,
      'template_line_id' => $templateLine['id'],
      'price_field_id' => $priceFieldID,
    ];
  }

  /**
   * Get the persisted template membership line with its resolved
   * membership_type_id (via the PriceFieldValue).
   *
   * @param int $templateContributionID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function getTemplateMembershipLine(int $templateContributionID): array {
    return (array) LineItem::get(FALSE)
      ->addSelect('id', 'price_field_id', 'price_field_value_id', 'label', 'unit_price', 'line_total', 'tax_amount', 'financial_type_id', 'membership_num_terms', 'price_field_value.membership_type_id')
      ->addJoin('PriceFieldValue AS price_field_value', 'LEFT')
      ->addWhere('contribution_id', '=', $templateContributionID)
      ->addWhere('entity_table', '=', 'civicrm_membership')
      ->execute()
      ->first();
  }

  /* ------------------------------------------------------------------ *
   * Same-organization type change (explicit PriceFieldValue path).
   * ------------------------------------------------------------------ */

  /**
   * API type change repoints the template line, recosts it, updates the
   * template totals and propagates the new amount to the recur.
   *
   * @throws \CRM_Core_Exception
   */
  public function testApiTypeChangeUpdatesTemplateAndRecur(): void {
    $ids = $this->setupAutoRenewMembership('AnnualRolling');

    $toTypeID = $this->ids['MembershipType']['AnnualRolling2'];
    $toValue = PriceFieldValue::get(FALSE)
      ->addSelect('id', 'amount', 'financial_type_id')
      ->addWhere('price_field_id', '=', $ids['price_field_id'])
      ->addWhere('membership_type_id', '=', $toTypeID)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();
    $this->assertNotEmpty($toValue, 'AnnualRolling2 must share the price field with AnnualRolling (same org).');

    Membership::update(FALSE)
      ->addWhere('id', '=', $ids['membership_id'])
      ->addValue('membership_type_id', $toTypeID)
      ->execute();

    $line = $this->getTemplateMembershipLine($ids['template_contribution_id']);

    $this->assertEquals($ids['template_line_id'], $line['id'], 'Existing template line should be reused, not duplicated.');
    $this->assertEquals($toTypeID, $line['price_field_value.membership_type_id']);
    $this->assertEquals($toValue['id'], $line['price_field_value_id']);
    $this->assertEquals($toValue['financial_type_id'], $line['financial_type_id']);
    $this->assertEquals($toValue['amount'], $line['line_total']);

    $templateContribution = Contribution::get(FALSE)
      ->addSelect('total_amount')
      ->addWhere('id', '=', $ids['template_contribution_id'])
      ->execute()
      ->first();
    $this->assertEquals($toValue['amount'], $templateContribution['total_amount']);

    $recur = ContributionRecur::get(FALSE)
      ->addSelect('amount')
      ->addWhere('id', '=', $ids['contribution_recur_id'])
      ->execute()
      ->first();
    $this->assertEquals($toValue['amount'], $recur['amount']);
  }

  /**
   * Back-office form type change also repoints the template line.
   *
   * Follows the edit-on-update idiom from
   * CRM_Member_Form_MembershipTest::testContributionUpdateOnMembershipTypeChange.
   *
   * @throws \CRM_Core_Exception
   */
  public function testFormTypeChangeUpdatesTemplate(): void {
    $ids = $this->setupAutoRenewMembership('AnnualRolling');
    $this->createLoggedInUser();

    $toTypeID = $this->ids['MembershipType']['AnnualRolling2'];

    $_REQUEST['id'] = $ids['membership_id'];
    $params = [
      'cid' => $this->ids['Contact']['member'],
      'contact_id' => $this->ids['Contact']['member'],
      'join_date' => CRM_Utils_Time::date('Y-m-d', strtotime('-1 year')),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => [$this->ids['Contact']['organization'], $toTypeID],
      'status_id' => '',
      'financial_type_id' => '2',
    ];
    /** @var CRM_Member_Form_Membership $form */
    $form = $this->getFormObject('CRM_Member_Form_Membership', $params);
    $form->preProcess();
    $form->buildQuickForm();
    $form->_action = CRM_Core_Action::UPDATE;
    $form->_id = $ids['membership_id'];
    $form->_contactID = $this->ids['Contact']['member'];
    $form->postProcess();

    $line = $this->getTemplateMembershipLine($ids['template_contribution_id']);
    $this->assertEquals($toTypeID, $line['price_field_value.membership_type_id'], 'Template line should point at the new type after the form edit.');
  }

  /* ------------------------------------------------------------------ *
   * Cross-organization type change (fallback resolution path).
   * ------------------------------------------------------------------ */

  /**
   * Changing to a type that has no value in the template line's price field
   * still updates the template via the Order's fallback resolution.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCrossOrgTypeChangeUsesFallback(): void {
    $ids = $this->setupAutoRenewMembership('AnnualRolling');

    $toTypeID = $this->ids['MembershipType']['AnnualRollingOrg2'];

    $sameFieldValue = PriceFieldValue::get(FALSE)
      ->addWhere('price_field_id', '=', $ids['price_field_id'])
      ->addWhere('membership_type_id', '=', $toTypeID)
      ->execute()
      ->first();
    $this->assertEmpty($sameFieldValue, 'AnnualRollingOrg2 must be in a different price field than AnnualRolling for this scenario.');

    Membership::update(FALSE)
      ->addWhere('id', '=', $ids['membership_id'])
      ->addValue('membership_type_id', $toTypeID)
      ->execute();

    $line = $this->getTemplateMembershipLine($ids['template_contribution_id']);
    $this->assertEquals($toTypeID, $line['price_field_value.membership_type_id'], 'Fallback should still resolve the new type.');
    $this->assertNotEquals($ids['price_field_id'], $line['price_field_id'], 'Line should move to the new type\'s own price field.');
  }

  /* ------------------------------------------------------------------ *
   * Taxed membership type (exercise the tax path through the Order).
   * ------------------------------------------------------------------ */

  /**
   * Changing to a type whose financial type carries sales tax recomputes tax on
   * the template line and stores tax-inclusive totals on the template
   * contribution & recur.
   *
   * @throws \CRM_Core_Exception
   */
  public function testTypeChangeToTaxedTypeRecomputesTax(): void {
    // Add 10% tax to the Member Dues financial type used by AnnualRolling2.
    $this->enableTaxAndInvoicing();
    $this->addTaxAccountToFinancialType(
      (int) \Civi\Api4\FinancialType::get(FALSE)->addWhere('name', '=', 'Member Dues')->addSelect('id')->execute()->first()['id'],
      ['tax_rate' => 10]
    );
    \Civi::cache('metadata')->flush();

    $ids = $this->setupAutoRenewMembership('AnnualRolling');

    $toTypeID = $this->ids['MembershipType']['AnnualRolling2'];
    $toValue = PriceFieldValue::get(FALSE)
      ->addSelect('amount')
      ->addWhere('price_field_id', '=', $ids['price_field_id'])
      ->addWhere('membership_type_id', '=', $toTypeID)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();

    Membership::update(FALSE)
      ->addWhere('id', '=', $ids['membership_id'])
      ->addValue('membership_type_id', $toTypeID)
      ->execute();

    $expectedNet = (float) $toValue['amount'];
    $expectedTax = round($expectedNet * 0.10, 2);

    $line = $this->getTemplateMembershipLine($ids['template_contribution_id']);
    $this->assertEquals($toTypeID, $line['price_field_value.membership_type_id']);
    $this->assertEquals($expectedNet, $line['line_total'], 'Line total is the net (pre-tax) amount.');
    $this->assertEquals($expectedTax, $line['tax_amount'], 'Line tax recomputed from the new financial type rate.');

    $templateContribution = Contribution::get(FALSE)
      ->addSelect('total_amount', 'tax_amount')
      ->addWhere('id', '=', $ids['template_contribution_id'])
      ->execute()
      ->first();
    $this->assertEquals($expectedNet + $expectedTax, $templateContribution['total_amount'], 'Template total is tax-inclusive.');
    $this->assertEquals($expectedTax, $templateContribution['tax_amount']);

    $recur = ContributionRecur::get(FALSE)
      ->addSelect('amount')
      ->addWhere('id', '=', $ids['contribution_recur_id'])
      ->execute()
      ->first();
    $this->assertEquals($expectedNet + $expectedTax, $recur['amount']);
  }

  /* ------------------------------------------------------------------ *
   * No-op guards.
   * ------------------------------------------------------------------ */

  /**
   * A status-only change must not touch the template line.
   *
   * @throws \CRM_Core_Exception
   */
  public function testStatusOnlyChangeLeavesTemplateUntouched(): void {
    $ids = $this->setupAutoRenewMembership('AnnualRolling');

    Membership::update(FALSE)
      ->addWhere('id', '=', $ids['membership_id'])
      ->addValue('status_id:name', 'Cancelled')
      ->addValue('is_override', TRUE)
      ->execute();

    $line = $this->getTemplateMembershipLine($ids['template_contribution_id']);
    $this->assertEquals($this->ids['MembershipType']['AnnualRolling'], $line['price_field_value.membership_type_id']);
  }

  /**
   * A membership with no recurring contribution is unaffected and does not
   * error on type change.
   *
   * @throws \CRM_Core_Exception
   */
  public function testNonRecurringTypeChangeIsNoOp(): void {
    $membership = Membership::create(FALSE)
      ->addValue('membership_type_id', $this->ids['MembershipType']['AnnualRolling'])
      ->addValue('contact_id', $this->ids['Contact']['member'])
      ->execute()
      ->first();

    Membership::update(FALSE)
      ->addWhere('id', '=', $membership['id'])
      ->addValue('membership_type_id', $this->ids['MembershipType']['AnnualRolling2'])
      ->execute();

    $this->assertEquals(
      $this->ids['MembershipType']['AnnualRolling2'],
      Membership::get(FALSE)->addWhere('id', '=', $membership['id'])->execute()->first()['membership_type_id']
    );
  }

}

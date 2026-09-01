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
 * Tests financial-item adjustments when fee selections change.
 *
 * When no line items need updating, the old shortcut incorrectly reverses
 * submitted, unchanged line items. These tests verify that an unchanged line
 * retains its revenue while an omitted line is still zeroed and reversed.
 *
 * A separate test covers omitted line items with a negative amount, such as a
 * discount: those were skipped by the amount > 0 guard and kept their negative
 * financial item after the line item itself had been zeroed.
 *
 * A third covers a line item carrying sales tax, which has a separate financial
 * item for the tax. Comparing each item against the line item total matched on
 * neither of them, so such a line was not reversed at all.
 *
 * @group headless
 * @group financial
 */
class CRM_Price_BAO_ChangeFeeSelectionsFinancialItemTest extends CiviUnitTestCase {

  /**
   * IDs created by the current test fixture.
   *
   * @var array
   */
  private $fixture = [];

  /**
   * An unchanged submitted line item retains its financial item.
   */
  public function testUnchangedLineItemKeepsItsFinancialItem(): void {
    $this->createPaidOrderWithTwoPriceFields();

    CRM_Price_BAO_LineItem::changeFeeSelections(
      ['price_' . $this->fixture['kept_field_id'] => $this->fixture['kept_value_id']],
      $this->fixture['participant_id'],
      'participant',
      $this->fixture['contribution_id']
    );

    $lineItem = $this->getLineItemFinancialSummary($this->fixture['kept_value_id']);
    $this->assertEquals(1.0, (float) $lineItem->qty);
    $this->assertEqualsWithDelta(
      (float) $lineItem->line_total,
      (float) $lineItem->net_financial_amount,
      0.001,
      'The submitted, unchanged line item should retain revenue equal to its line total.'
    );
    $this->assertEquals(
      [35.00],
      $this->getFinancialItemAmounts($this->fixture['kept_value_id']),
      'The unchanged line item should still have its single original financial item.'
    );
  }

  /**
   * An omitted line item is still zeroed and financially reversed.
   */
  public function testOmittedLineItemIsStillReversed(): void {
    $this->createPaidOrderWithTwoPriceFields();

    CRM_Price_BAO_LineItem::changeFeeSelections(
      ['price_' . $this->fixture['kept_field_id'] => $this->fixture['kept_value_id']],
      $this->fixture['participant_id'],
      'participant',
      $this->fixture['contribution_id']
    );

    $lineItem = $this->getLineItemFinancialSummary($this->fixture['omitted_value_id']);
    $this->assertEquals(0.0, (float) $lineItem->qty);
    $this->assertEquals(0.0, (float) $lineItem->line_total);
    $this->assertEquals(
      [-10.00, 10.00],
      $this->getFinancialItemAmounts($this->fixture['omitted_value_id']),
      'The omitted line item should have its original item and exactly one reversal.'
    );
  }

  /**
   * An omitted line item with a negative amount is reversed as well.
   *
   * A discount line carries a negative financial item. Zeroing the line item
   * without reversing that item leaves the discount on the revenue account
   * forever, so the accounting side keeps a negative remainder that the
   * contribution no longer accounts for.
   */
  public function testOmittedNegativeLineItemIsAlsoReversed(): void {
    $this->createPaidOrderWithDiscountLine();

    CRM_Price_BAO_LineItem::changeFeeSelections(
      ['price_' . $this->fixture['kept_field_id'] => $this->fixture['kept_value_id']],
      $this->fixture['participant_id'],
      'participant',
      $this->fixture['contribution_id']
    );

    $lineItem = $this->getLineItemFinancialSummary($this->fixture['discount_value_id']);
    $this->assertEquals(0.0, (float) $lineItem->qty);
    $this->assertEquals(0.0, (float) $lineItem->line_total);
    $this->assertEquals(
      [-5.00, 5.00],
      $this->getFinancialItemAmounts($this->fixture['discount_value_id']),
      'The omitted discount line should have its negative item and exactly one reversal.'
    );
  }

  /**
   * A line item carrying sales tax has both of its financial items reversed.
   *
   * Revenue and tax are recorded as two financial items on one line item, each on
   * its own financial account. Both have to be reversed when the line is omitted,
   * and each on its own account: a single combined reversal would move the tax off
   * the tax account and onto the revenue account.
   */
  public function testOmittedTaxedLineItemReversesBothItems(): void {
    $this->createPaidOrderWithTaxedLines();

    CRM_Price_BAO_LineItem::changeFeeSelections(
      ['price_' . $this->fixture['kept_field_id'] => $this->fixture['kept_value_id']],
      $this->fixture['participant_id'],
      'participant',
      $this->fixture['contribution_id']
    );

    $lineItem = $this->getLineItemFinancialSummary($this->fixture['omitted_value_id']);
    $this->assertEquals(0.0, (float) $lineItem->qty);
    $this->assertEquals(0.0, (float) $lineItem->line_total);
    $this->assertEquals(
      [-35.00, -3.50, 3.50, 35.00],
      $this->getFinancialItemAmounts($this->fixture['omitted_value_id']),
      'Both the revenue and the tax item of the omitted line should be reversed.'
    );

    // Each reversal belongs on the account of the item it reverses.
    $perAccount = $this->getFinancialItemTotalsPerAccount($this->fixture['omitted_value_id']);
    $this->assertCount(2, $perAccount, 'Expected items on a revenue and a tax account.');
    foreach ($perAccount as $accountID => $total) {
      $this->assertEqualsWithDelta(
        0.0,
        $total,
        0.001,
        'Financial account ' . $accountID . ' should net to zero after the reversal.'
      );
    }
  }

  /**
   * Create and fully pay an order whose lines carry 10% sales tax.
   */
  private function createPaidOrderWithTaxedLines(): void {
    $financialTypeID = $this->getDonationFinancialTypeID();
    $this->enableTaxAndInvoicing();
    $this->addTaxAccountToFinancialType($financialTypeID);
    $this->createEventWithPriceSet($financialTypeID);

    [$omittedFieldID, $omittedValueID] = $this->createPriceFieldAndValue(
      'thirty_five_euros',
      'Thirty-five euros',
      35.00,
      $financialTypeID
    );
    [$keptFieldID, $keptValueID] = $this->createPriceFieldAndValue(
      'ten_euros',
      'Ten euros',
      10.00,
      $financialTypeID
    );
    $this->fixture += [
      'omitted_field_id' => $omittedFieldID,
      'omitted_value_id' => $omittedValueID,
      'kept_field_id' => $keptFieldID,
      'kept_value_id' => $keptValueID,
    ];

    $omitted = $this->getOrderLineItem($omittedFieldID, $omittedValueID, 'Thirty-five euros', 35.00, $financialTypeID);
    $omitted['tax_amount'] = 3.50;
    $kept = $this->getOrderLineItem($keptFieldID, $keptValueID, 'Ten euros', 10.00, $financialTypeID);
    $kept['tax_amount'] = 1.00;

    $this->createPaidOrder(49.50, [$omitted, $kept], ['tax_amount' => 4.50]);
  }

  /**
   * Create and fully pay an order containing a EUR 10 line and a EUR -5 discount.
   */
  private function createPaidOrderWithDiscountLine(): void {
    $financialTypeID = $this->getDonationFinancialTypeID();
    $this->createEventWithPriceSet($financialTypeID);

    [$keptFieldID, $keptValueID] = $this->createPriceFieldAndValue(
      'ten_euros',
      'Ten euros',
      10.00,
      $financialTypeID
    );
    [$discountFieldID, $discountValueID] = $this->createPriceFieldAndValue(
      'discount_five_euros',
      'Five euro discount',
      -5.00,
      $financialTypeID
    );
    $this->fixture += [
      'kept_field_id' => $keptFieldID,
      'kept_value_id' => $keptValueID,
      'discount_field_id' => $discountFieldID,
      'discount_value_id' => $discountValueID,
    ];

    $this->createPaidOrder(5.00, [
      $this->getOrderLineItem($keptFieldID, $keptValueID, 'Ten euros', 10.00, $financialTypeID),
      $this->getOrderLineItem($discountFieldID, $discountValueID, 'Five euro discount', -5.00, $financialTypeID),
    ]);
  }

  /**
   * Create and fully pay an order containing EUR 10 and EUR 35 price lines.
   */
  private function createPaidOrderWithTwoPriceFields(): void {
    $financialTypeID = $this->getDonationFinancialTypeID();
    $this->createEventWithPriceSet($financialTypeID);

    [$omittedFieldID, $omittedValueID] = $this->createPriceFieldAndValue(
      'ten_euros',
      'Ten euros',
      10.00,
      $financialTypeID
    );
    [$keptFieldID, $keptValueID] = $this->createPriceFieldAndValue(
      'thirty_five_euros',
      'Thirty-five euros',
      35.00,
      $financialTypeID
    );
    $this->fixture += [
      'omitted_field_id' => $omittedFieldID,
      'omitted_value_id' => $omittedValueID,
      'kept_field_id' => $keptFieldID,
      'kept_value_id' => $keptValueID,
    ];

    $this->createPaidOrder(45.00, [
      $this->getOrderLineItem($omittedFieldID, $omittedValueID, 'Ten euros', 10.00, $financialTypeID),
      $this->getOrderLineItem($keptFieldID, $keptValueID, 'Thirty-five euros', 35.00, $financialTypeID),
    ]);
  }

  /**
   * Get the financial type ID of the default Donation type.
   */
  private function getDonationFinancialTypeID(): int {
    return (int) CRM_Core_PseudoConstant::getKey(
      'CRM_Contribute_BAO_Contribution',
      'financial_type_id',
      'Donation'
    );
  }

  /**
   * Create the contact, the price set, and the monetary event they belong to.
   */
  private function createEventWithPriceSet(int $financialTypeID): void {
    $contact = civicrm_api3('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Fee selection',
      'last_name' => 'Financial item test',
    ]);
    $this->fixture['contact_id'] = $contact['id'];

    $priceSet = civicrm_api3('PriceSet', 'create', [
      'name' => strtolower(__CLASS__) . '_' . uniqid(),
      'title' => 'Fee selection financial item test',
      'extends' => 'CiviEvent',
      'financial_type_id' => $financialTypeID,
      'is_active' => 1,
    ]);
    $this->fixture['price_set_id'] = $priceSet['id'];

    $event = civicrm_api3('Event', 'create', [
      'title' => 'Fee selection financial item test',
      'start_date' => date('YmdHis'),
      'event_type_id' => 1,
      'is_monetary' => 1,
      'is_active' => 1,
    ]);
    $this->fixture['event_id'] = $event['id'];
    CRM_Price_BAO_PriceSet::addTo(
      'civicrm_event',
      $this->fixture['event_id'],
      $this->fixture['price_set_id']
    );
  }

  /**
   * Register a participant with the given line items and pay the order in full.
   *
   * @param float $totalAmount
   *   The order total, which is also the amount that gets paid.
   * @param array $lineItems
   *   Line-item parameter arrays as built by getOrderLineItem().
   * @param array $extraOrderParams
   *   Extra parameters for Order.create, such as tax_amount.
   */
  private function createPaidOrder(float $totalAmount, array $lineItems, array $extraOrderParams = []): void {
    $order = civicrm_api3('Order', 'create', $extraOrderParams + [
      'contact_id' => $this->fixture['contact_id'],
      'financial_type_id' => $this->getDonationFinancialTypeID(),
      'total_amount' => $totalAmount,
      'is_test' => 1,
      'line_items' => [
        [
          'params' => [
            'contact_id' => $this->fixture['contact_id'],
            'event_id' => $this->fixture['event_id'],
            'status_id' => 'Registered',
            'role_id' => 'Attendee',
            'register_date' => date('YmdHis'),
          ],
          'line_item' => $lineItems,
        ],
      ],
    ]);
    $this->fixture['contribution_id'] = $order['id'];
    $this->fixture['participant_id'] = (int) CRM_Core_DAO::singleValueQuery(
      "SELECT entity_id
       FROM civicrm_line_item
       WHERE contribution_id = %1 AND entity_table = 'civicrm_participant'
       LIMIT 1",
      [
        1 => [$this->fixture['contribution_id'], 'Integer'],
      ]
    );

    civicrm_api3('Payment', 'create', [
      'contribution_id' => $this->fixture['contribution_id'],
      'total_amount' => $totalAmount,
    ]);
  }

  /**
   * Create one radio price field with one value.
   *
   * @return int[]
   *   The price field ID and price field value ID.
   */
  private function createPriceFieldAndValue(
    string $name,
    string $label,
    float $amount,
    int $financialTypeID
  ): array {
    $priceField = civicrm_api3('PriceField', 'create', [
      'price_set_id' => $this->fixture['price_set_id'],
      'name' => $name,
      'label' => $label,
      'html_type' => 'Radio',
      'is_active' => 1,
    ]);
    $priceFieldValue = civicrm_api3('PriceFieldValue', 'create', [
      'price_field_id' => $priceField['id'],
      'name' => $name,
      'label' => $label,
      'amount' => $amount,
      'financial_type_id' => $financialTypeID,
      'is_active' => 1,
    ]);
    return [(int) $priceField['id'], (int) $priceFieldValue['id']];
  }

  /**
   * Build one Order.create line-item parameter array.
   */
  private function getOrderLineItem(
    int $fieldID,
    int $valueID,
    string $label,
    float $amount,
    int $financialTypeID
  ): array {
    return [
      'price_field_id' => $fieldID,
      'price_field_value_id' => $valueID,
      'entity_table' => 'civicrm_participant',
      'label' => $label,
      'qty' => 1,
      'unit_price' => $amount,
      'line_total' => $amount,
      'financial_type_id' => $financialTypeID,
    ];
  }

  /**
   * Get the individual financial item amounts of one line item, ascending.
   *
   * Asserting on the separate amounts rather than only on their sum makes a
   * missing reversal and a duplicated one distinguishable: both leave a sum that
   * a laxer assertion would accept.
   *
   * @return float[]
   */
  private function getFinancialItemAmounts(int $priceFieldValueID): array {
    $amounts = CRM_Core_DAO::executeQuery(
      "SELECT fi.amount
       FROM civicrm_line_item li
       INNER JOIN civicrm_financial_item fi
         ON fi.entity_table = 'civicrm_line_item' AND fi.entity_id = li.id
       WHERE li.contribution_id = %1 AND li.price_field_value_id = %2
       ORDER BY fi.amount",
      [
        1 => [$this->fixture['contribution_id'], 'Integer'],
        2 => [$priceFieldValueID, 'Integer'],
      ]
    )->fetchMap('amount', 'amount');
    return array_map('floatval', array_values($amounts));
  }

  /**
   * Get the net financial item total per financial account for one line item.
   *
   * @return array
   *   Financial account ID => net amount.
   */
  private function getFinancialItemTotalsPerAccount(int $priceFieldValueID): array {
    $totals = [];
    $dao = CRM_Core_DAO::executeQuery(
      "SELECT fi.financial_account_id AS account_id, SUM(fi.amount) AS total
       FROM civicrm_line_item li
       INNER JOIN civicrm_financial_item fi
         ON fi.entity_table = 'civicrm_line_item' AND fi.entity_id = li.id
       WHERE li.contribution_id = %1 AND li.price_field_value_id = %2
       GROUP BY fi.financial_account_id",
      [
        1 => [$this->fixture['contribution_id'], 'Integer'],
        2 => [$priceFieldValueID, 'Integer'],
      ]
    );
    while ($dao->fetch()) {
      $totals[(int) $dao->account_id] = (float) $dao->total;
    }
    return $totals;
  }

  /**
   * Get the line-item state and the net amount of its financial items.
   */
  private function getLineItemFinancialSummary(int $priceFieldValueID): CRM_Core_DAO {
    $dao = CRM_Core_DAO::executeQuery(
      "SELECT li.qty, li.line_total, COALESCE(SUM(fi.amount), 0) AS net_financial_amount
       FROM civicrm_line_item li
       LEFT JOIN civicrm_financial_item fi
         ON fi.entity_table = 'civicrm_line_item' AND fi.entity_id = li.id
       WHERE li.contribution_id = %1 AND li.price_field_value_id = %2
       GROUP BY li.id, li.qty, li.line_total",
      [
        1 => [$this->fixture['contribution_id'], 'Integer'],
        2 => [$priceFieldValueID, 'Integer'],
      ]
    );
    $this->assertTrue($dao->fetch(), 'Expected line item was not found.');
    return $dao;
  }

  /**
   * Clean up the fixture.
   *
   * quickCleanUpFinancialEntities() covers everything the fixture creates,
   * including non-default price sets.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

}

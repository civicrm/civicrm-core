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
 * Tests that changing a fee selection leaves the recorded payment alone.
 *
 * Swapping one price option for another of the same price changes nothing about
 * what is owed or what was paid, so the payment recorded against the contribution
 * has to come out of it untouched.
 *
 * This pins down behaviour that used to be at risk. The method held a branch that
 * reversed the financial transaction a financial item was linked to, and that
 * transaction is the payment itself, not the line's share of it. The branch never
 * executed because of a mismatch between the name it was called by and the name it
 * was defined under, so the payment survived by accident rather than by design.
 * With the branch removed the outcome is the same, and this test keeps it that way.
 *
 * @group headless
 * @group financial
 */
class CRM_Price_BAO_ChangeFeeSelectionsPaymentTest extends CiviUnitTestCase {

  /**
   * IDs created by the fixture.
   *
   * @var array
   */
  private $fixture = [];

  /**
   * Swapping a price option for one of the same price leaves the payment intact.
   */
  public function testSwappingEqualPricedOptionKeepsThePayment(): void {
    // validatePayments() does not survive a fee change: the financial items of the
    // line that comes in stay allocated to the original payment, so the allocated
    // total ends up above the amount paid. That reproduces on unpatched core and is
    // unrelated to what this test pins down. ChangeFeeSelectionTest skips the same
    // check for the same reason, calling it "likely a real bug".
    $this->isValidateFinancialsOnPostAssert = FALSE;
    $this->createPaidOrder();

    CRM_Price_BAO_LineItem::changeFeeSelections(
      [
        'price_' . $this->fixture['swap_in_field_id'] => $this->fixture['swap_in_value_id'],
        'price_' . $this->fixture['kept_field_id'] => $this->fixture['kept_value_id'],
      ],
      $this->fixture['participant_id'],
      'participant',
      $this->fixture['contribution_id']
    );

    $contribution = civicrm_api3('Contribution', 'getsingle', [
      'id' => $this->fixture['contribution_id'],
      'return' => ['total_amount', 'contribution_status_id'],
    ]);
    $this->assertEquals(85.00, (float) $contribution['total_amount'], 'The swap should not change what is owed.');

    $payments = $this->getContributionTransactions();
    $this->assertEquals(
      [85.00],
      $payments,
      'The contribution should still carry exactly its original payment, with no reversal.'
    );
  }

  /**
   * Get the payment transaction amounts recorded against the contribution.
   *
   * @return float[]
   */
  private function getContributionTransactions(): array {
    $amounts = [];
    $dao = CRM_Core_DAO::executeQuery(
      "SELECT ft.total_amount
       FROM civicrm_entity_financial_trxn eft
       INNER JOIN civicrm_financial_trxn ft ON ft.id = eft.financial_trxn_id
       WHERE eft.entity_table = 'civicrm_contribution'
         AND eft.entity_id = %1
         AND ft.is_payment = 1
       ORDER BY ft.id",
      [1 => [$this->fixture['contribution_id'], 'Integer']]
    );
    while ($dao->fetch()) {
      $amounts[] = (float) $dao->total_amount;
    }
    return $amounts;
  }

  /**
   * Register a participant on a EUR 50 and a EUR 35 option and pay in full.
   *
   * A third option priced at EUR 50 is created to swap the first one for, so the
   * order total stays at EUR 85 and the contribution stays Completed.
   */
  private function createPaidOrder(): void {
    $financialTypeID = (int) CRM_Core_PseudoConstant::getKey(
      'CRM_Contribute_BAO_Contribution',
      'financial_type_id',
      'Donation'
    );

    $this->fixture['contact_id'] = $this->individualCreate();
    $priceSet = civicrm_api3('PriceSet', 'create', [
      'name' => strtolower(__CLASS__) . '_' . uniqid(),
      'title' => 'Fee selection payment test',
      'extends' => 'CiviEvent',
      'financial_type_id' => $financialTypeID,
      'is_active' => 1,
    ]);
    $this->fixture['price_set_id'] = $priceSet['id'];

    $event = civicrm_api3('Event', 'create', [
      'title' => 'Fee selection payment test',
      'start_date' => date('YmdHis'),
      'event_type_id' => 1,
      'is_monetary' => 1,
      'is_active' => 1,
    ]);
    $this->fixture['event_id'] = $event['id'];
    CRM_Price_BAO_PriceSet::addTo('civicrm_event', $event['id'], $priceSet['id']);

    [$swapOutFieldID, $swapOutValueID] = $this->createPriceFieldAndValue('fifty_euros', 50.00, $financialTypeID);
    [$keptFieldID, $keptValueID] = $this->createPriceFieldAndValue('thirty_five_euros', 35.00, $financialTypeID);
    [$swapInFieldID, $swapInValueID] = $this->createPriceFieldAndValue('fifty_euros_again', 50.00, $financialTypeID);
    $this->fixture += [
      'kept_field_id' => $keptFieldID,
      'kept_value_id' => $keptValueID,
      'swap_in_field_id' => $swapInFieldID,
      'swap_in_value_id' => $swapInValueID,
    ];

    $order = civicrm_api3('Order', 'create', [
      'contact_id' => $this->fixture['contact_id'],
      'financial_type_id' => $financialTypeID,
      'total_amount' => 85.00,
      'line_items' => [
        [
          'params' => [
            'contact_id' => $this->fixture['contact_id'],
            'event_id' => $this->fixture['event_id'],
            'status_id' => 'Registered',
            'role_id' => 'Attendee',
            'register_date' => date('YmdHis'),
          ],
          'line_item' => [
            $this->getOrderLineItem($swapOutFieldID, $swapOutValueID, 50.00, $financialTypeID),
            $this->getOrderLineItem($keptFieldID, $keptValueID, 35.00, $financialTypeID),
          ],
        ],
      ],
    ]);
    $this->fixture['contribution_id'] = $order['id'];
    $this->fixture['participant_id'] = (int) CRM_Core_DAO::singleValueQuery(
      "SELECT entity_id
       FROM civicrm_line_item
       WHERE contribution_id = %1 AND entity_table = 'civicrm_participant'
       LIMIT 1",
      [1 => [$this->fixture['contribution_id'], 'Integer']]
    );

    civicrm_api3('Payment', 'create', [
      'contribution_id' => $this->fixture['contribution_id'],
      'total_amount' => 85.00,
    ]);
  }

  /**
   * Create one radio price field with one value.
   *
   * @return int[]
   *   The price field ID and price field value ID.
   */
  private function createPriceFieldAndValue(string $name, float $amount, int $financialTypeID): array {
    $priceField = civicrm_api3('PriceField', 'create', [
      'price_set_id' => $this->fixture['price_set_id'],
      'name' => $name,
      'label' => $name,
      'html_type' => 'Radio',
      'is_active' => 1,
    ]);
    $priceFieldValue = civicrm_api3('PriceFieldValue', 'create', [
      'price_field_id' => $priceField['id'],
      'name' => $name,
      'label' => $name,
      'amount' => $amount,
      'financial_type_id' => $financialTypeID,
      'is_active' => 1,
    ]);
    return [(int) $priceField['id'], (int) $priceFieldValue['id']];
  }

  /**
   * Build one Order.create line-item parameter array.
   */
  private function getOrderLineItem(int $fieldID, int $valueID, float $amount, int $financialTypeID): array {
    return [
      'price_field_id' => $fieldID,
      'price_field_value_id' => $valueID,
      'entity_table' => 'civicrm_participant',
      'label' => 'Option ' . $valueID,
      'qty' => 1,
      'unit_price' => $amount,
      'line_total' => $amount,
      'financial_type_id' => $financialTypeID,
    ];
  }

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->fixture = [];
    parent::tearDown();
  }

}

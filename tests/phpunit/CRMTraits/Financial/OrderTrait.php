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

use Civi\Api4\ContributionRecur;
use Civi\Api4\LineItem;
use Civi\Api4\Order;
use Civi\Api4\Payment;
use Civi\Api4\PriceField;
use Civi\Api4\PriceFieldValue;

/**
 * Trait OrderTrait
 *
 * Trait for setting up orders for tests.
 */
trait CRMTraits_Financial_OrderTrait {

  /**
   * Create a pending membership from a recurring order.
   *
   * @throws \CRM_Core_Exception
   */
  public function createRepeatMembershipOrder(): void {
    $this->createExtraneousContribution();
    $this->ids['contact'][0] = $this->individualCreate();

    $contributionRecur = ContributionRecur::create(FALSE)->setValues([
      'contact_id' => $this->_contactID,
      'amount' => 1000,
      'installments' => 5,
      'frequency_unit:name' => 'month',
      'frequency_interval' => 1,
      'invoice_id' => 'xyz',
      'contribution_status_id:name' => 'Pending',
      'payment_processor_id' => reset($this->ids['PaymentProcessor']),
      // processor provided ID - use contact ID as proxy.
      'processor_id' => $this->_contactID,
    ])->execute()->single();

    $orderID = $this->callAPISuccess('Order', 'create', [
      'total_amount' => '200',
      'financial_type_id' => 'Donation',
      'source' => 'Online Contribution: form payment',
      'contact_id' => $this->_contactID,
      'contribution_page_id' => $this->ids['ContributionPage'][0] ?? NULL,
      'payment_processor_id' => reset($this->ids['PaymentProcessor']),
      'is_test' => 0,
      'receive_date' => '2019-07-25 07:34:23',
      'skipCleanMoney' => TRUE,
      'contribution_recur_id' => $contributionRecur['id'],
      'line_items' => [
        [
          'params' => [
            'contact_id' => $this->ids['contact'][0],
            'membership_type_id:name' => 'General',
            'contribution_recur_id' => $contributionRecur['id'],
            'source' => 'Payment',
          ],
          'line_item' => $this->getMembershipLineItem(),
        ],
      ],
    ])['id'];

    $this->ids['ContributionRecur'][0] = $contributionRecur['id'];
    $this->ids['Contribution'][0] = $orderID;
  }

  /**
   * Create an order with a contribution AND a membership line item.
   *
   * @throws \CRM_Core_Exception
   */
  protected function createContributionAndMembershipOrder(): void {
    $this->ids['MembershipType'][0] = $this->membershipTypeCreate();
    if (empty($this->ids['Contact']['order'])) {
      $this->ids['Contact']['order'] = $this->individualCreate();
    }
    $order = $this->callAPISuccess('Order', 'create', [
      'financial_type_id' => 'Donation',
      'contact_id' => $this->ids['Contact']['order'],
      'is_test' => 0,
      'payment_instrument_id' => 'Check',
      'receive_date' => date('Y-m-d'),
      'line_items' => [
        [
          'params' => [
            'contact_id' => $this->ids['Contact']['order'],
            'source' => 'Payment',
          ],
          'line_item' => [
            [
              'label' => 'Contribution Amount',
              'qty' => 1,
              'unit_price' => 100,
              'line_total' => 100,
              'financial_type_id' => $this->getFinancialTypeID('Donation'),
              'entity_table' => 'civicrm_contribution',
              'price_field_id' => $this->getLatestPriceFieldID('Contribution Amount'),
              'price_field_value_id' => NULL,
            ],
          ],
        ],
        [
          'params' => [
            'contact_id' => $this->ids['Contact']['order'],
            'membership_type_id' => 'General',
            'source' => 'Payment',
            // This is necessary because Membership_BAO otherwise ignores the
            // pending status. I do have a fix but it's held up behind other pending-review PRs
            // so this should be temporary until we get the membership PRs flowing.
            'skipStatusCal' => TRUE,
          ],
          'line_item' => $this->getMembershipLineItem(),
        ],
      ],
    ]);

    $this->ids['Contribution'][0] = $order['id'];
    foreach ($order['values'][$order['id']]['line_item'] as $line) {
      if (($line['entity_table'] ?? '') === 'civicrm_membership') {
        $this->ids['Membership']['order'] = $line['entity_id'];
      }
    }

  }

  /**
   * Create an order with more than one membership.
   *
   * @throws \CRM_Core_Exception
   */
  protected function createMultipleMembershipOrder() {
    $this->createExtraneousContribution();
    $this->ids['contact'][0] = $this->individualCreate();
    $this->ids['contact'][1] = $this->individualCreate();
    $this->ids['MembershipType'][0] = $this->membershipTypeCreate();
    $this->ids['MembershipType'][1] = $this->membershipTypeCreate(['title' => 'Type 2']);
    $priceFieldID = $this->getLatestPriceFieldID('Membership Amount');
    $generalPriceFieldValueID = $this->getLatestPriceFieldValueID('General');

    $orderID = $this->callAPISuccess('Order', 'create', [
      'total_amount' => 400,
      'financial_type_id' => 'Member Dues',
      'contact_id' => $this->_contactID,
      'is_test' => 0,
      'payment_instrument_id' => 'Check',
      'receive_date' => '2019-07-25 07:34:23',
      'line_items' => [
        [
          'params' => [
            'contact_id' => $this->ids['contact'][0],
            'membership_type_id' => $this->ids['MembershipType'][0],
            'source' => 'Payment',
          ],
          'line_item' => [
            [
              'label' => 'General',
              'qty' => 1,
              'unit_price' => 200,
              'line_total' => 200,
              'financial_type_id' => $this->getFinancialTypeID('Donation'),
              'entity_table' => 'civicrm_membership',
              'price_field_id' => $priceFieldID,
              'price_field_value_id' => $generalPriceFieldValueID,
            ],
          ],
        ],
        [
          'params' => [
            'contact_id' => $this->ids['contact'][1],
            'membership_type_id' => $this->ids['MembershipType'][0],
            'source' => 'Payment',
          ],
          'line_item' => [
            [
              'label' => 'General',
              'qty' => 1,
              'unit_price' => 200,
              'line_total' => 200,
              'financial_type_id' => $this->getFinancialTypeID('Donation'),
              'entity_table' => 'civicrm_membership',
              'price_field_id' => $priceFieldID,
              'price_field_value_id' => $generalPriceFieldValueID,
            ],
          ],
        ],
      ],
    ])['id'];

    $this->ids['Contribution'][0] = $orderID;
  }

  /**
   * Create an order for an event.
   *
   * @param array $contributionValues
   *   Overrides for the contribution values.
   *
   * @throws \CRM_Core_Exception
   */
  protected function createEventOrder(array $contributionValues = []): void {
    $this->ids['Contribution'][0] = $this->createParticipantOrder($contributionValues)['id'];
    $this->ids['Participant'][0] = LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $this->ids['Contribution'][0])
      ->addWhere('entity_table', '=', 'civicrm_participant')
      ->addSelect('entity_id')
      ->addOrderBy('id')
      ->execute()->first()['entity_id'];
  }

  /**
   * Create a pending order for two participants (total 300).
   *
   * @param array $contributionValues
   *   Overrides for the contribution values.
   *
   * @return array
   *   The created contribution.
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function createParticipantOrder(array $contributionValues = []): array {
    $orderParams = $this->getParticipantOrderParams();
    return Order::create(FALSE)
      ->setContributionValues(array_merge($orderParams['contribution_params'], $contributionValues))
      ->setLineItems($orderParams['line_items'])
      ->execute()->single();
  }

  /**
   * Create a participant order (total 300) with a payment of 150.
   *
   * @return array
   *   The created contribution.
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function createPartiallyPaidParticipantOrder(): array {
    $order = $this->createParticipantOrder();
    Payment::create(FALSE)
      ->addValue('contribution_id', $order['id'])
      ->addValue('total_amount', 150)
      ->execute();
    return $order;
  }

  /**
   * Create an extraneous contribution to throw off any 'number one bugs'.
   *
   * Ie this means our real data starts from 2 & we won't hit 'pretend passes'
   * just because the number 1 is used for multiple entities.
   */
  protected function createExtraneousContribution() {
    $this->contributionCreate([
      'contact_id' => $this->individualCreate(),
      'is_test' => 1,
      'financial_type_id' => $this->getFinancialTypeID('Donation'),
      'invoice_id' => 'abcd',
      'trxn_id' => '345',
      'receive_date' => '2019-07-25 07:34:23',
    ]);
  }

  /**
   * @return array[]
   * @throws \CRM_Core_Exception
   */
  protected function getMembershipLineItem(): array {
    return [
      [
        'label' => 'General',
        'qty' => 1,
        'unit_price' => 200,
        'line_total' => 200,
        'financial_type_id' => $this->getFinancialTypeID('Donation'),
        'entity_table' => 'civicrm_membership',
        'price_field_id' => $this->getLatestPriceFieldID('Membership Amount'),
        'price_field_value_id' => $this->getLatestPriceFieldValueID('General'),
      ],
    ];
  }

  /**
   * Get the id of the most recently created price field with the given label.
   *
   * @param string $label
   *
   * @return int
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function getLatestPriceFieldID(string $label): int {
    return PriceField::get(FALSE)
      ->addWhere('label', '=', $label)
      ->addSelect('id')
      ->addOrderBy('id', 'DESC')
      ->setLimit(1)
      ->execute()->single()['id'];
  }

  /**
   * Get the id of the most recently created price field value with the given label.
   *
   * @param string $label
   *
   * @return int
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function getLatestPriceFieldValueID(string $label): int {
    return PriceFieldValue::get(FALSE)
      ->addWhere('label', '=', $label)
      ->addSelect('id')
      ->addOrderBy('id', 'DESC')
      ->setLimit(1)
      ->execute()->single()['id'];
  }

}

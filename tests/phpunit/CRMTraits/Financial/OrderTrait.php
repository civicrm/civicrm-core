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

use Civi\Test\Api3TestTrait;

/**
 * Trait OrderTrait
 *
 * Trait for setting up orders for tests.
 */
trait CRMTraits_Financial_OrderTrait {

  use Api3TestTrait;

  /**
   * Create a pending membership from a recurring order.
   *
   * @throws \CRM_Core_Exception
   */
  public function createRepeatMembershipOrder(): void {
    $this->createExtraneousContribution();
    $this->ids['contact'][0] = $this->individualCreate();

    $contributionRecur = $this->callAPISuccess('ContributionRecur', 'create', array_merge([
      'contact_id' => $this->_contactID,
      'amount' => 1000,
      'sequential' => 1,
      'installments' => 5,
      'frequency_unit' => 'Month',
      'frequency_interval' => 1,
      'invoice_id' => $this->_invoiceID,
      'contribution_status_id' => 2,
      'payment_processor_id' => $this->_paymentProcessorID,
      // processor provided ID - use contact ID as proxy.
      'processor_id' => $this->_contactID,
    ]));

    $orderID = $this->callAPISuccess('Order', 'create', [
      'total_amount' => '200',
      'financial_type_id' => 'Donation',
      'source' => 'Online Contribution: form payment',
      'contact_id' => $this->_contactID,
      'contribution_page_id' => $this->_contributionPageID,
      'payment_processor_id' => $this->_paymentProcessorID,
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
    $this->ids['membership_type'][0] = $this->membershipTypeCreate();
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
              'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'),
              'entity_table' => 'civicrm_contribution',
              'price_field_id' => $this->callAPISuccessGetValue('price_field', [
                'return' => 'id',
                'label' => 'Contribution Amount',
                'options' => ['limit' => 1, 'sort' => 'id DESC'],
              ]),
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
    $this->ids['membership_type'][0] = $this->membershipTypeCreate();
    $this->ids['membership_type'][1] = $this->membershipTypeCreate(['name' => 'Type 2']);
    $priceFieldID = $this->callAPISuccessGetValue('price_field', [
      'return' => 'id',
      'label' => 'Membership Amount',
      'options' => ['limit' => 1, 'sort' => 'id DESC'],
    ]);
    $generalPriceFieldValueID = $this->callAPISuccessGetValue('price_field_value', [
      'return' => 'id',
      'label' => 'General',
      'options' => ['limit' => 1, 'sort' => 'id DESC'],
    ]);

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
            'membership_type_id' => $this->ids['membership_type'][0],
            'source' => 'Payment',
          ],
          'line_item' => [
            [
              'label' => 'General',
              'qty' => 1,
              'unit_price' => 200,
              'line_total' => 200,
              'financial_type_id' => 1,
              'entity_table' => 'civicrm_membership',
              'price_field_id' => $priceFieldID,
              'price_field_value_id' => $generalPriceFieldValueID,
            ],
          ],
        ],
        [
          'params' => [
            'contact_id' => $this->ids['contact'][1],
            'membership_type_id' => $this->ids['membership_type'][0],
            'source' => 'Payment',
          ],
          'line_item' => [
            [
              'label' => 'General',
              'qty' => 1,
              'unit_price' => 200,
              'line_total' => 200,
              'financial_type_id' => 1,
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
   * @param array $orderParams
   *
   * @throws \CRM_Core_Exception
   */
  protected function createEventOrder($orderParams = []) {
    $this->ids['Contribution'][0] = $this->callAPISuccess('Order', 'create', array_merge($this->getParticipantOrderParams(), $orderParams))['id'];
    $this->ids['Participant'][0] = $this->callAPISuccessGetValue('ParticipantPayment', ['options' => ['limit' => 1], 'return' => 'participant_id', 'contribution_id' => $this->ids['Contribution'][0]]);
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
      'financial_type_id' => 1,
      'invoice_id' => 'abcd',
      'trxn_id' => 345,
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
        'financial_type_id' => 1,
        'entity_table' => 'civicrm_membership',
        'price_field_id' => $this->callAPISuccess('price_field', 'getvalue', [
          'return' => 'id',
          'label' => 'Membership Amount',
          'options' => ['limit' => 1, 'sort' => 'id DESC'],
        ]),
        'price_field_value_id' => $this->callAPISuccess('price_field_value', 'getvalue', [
          'return' => 'id',
          'label' => 'General',
          'options' => ['limit' => 1, 'sort' => 'id DESC'],
        ]),
      ],
    ];
  }

}

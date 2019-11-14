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
 * Trait OrderTrait
 *
 * Trait for setting up orders for tests.
 */
trait CRMTraits_Financial_OrderTrait {

  use \Civi\Test\Api3TestTrait;

  /**
   * Create a pending membership from a recurring order.
   *
   * @throws \CRM_Core_Exception
   */
  public function createRepeatMembershipOrder() {
    $this->createExtraneousContribution();
    $this->ids['contact'][0] = $this->individualCreate();
    $this->ids['membership_type'][0] = $this->membershipTypeCreate();

    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array_merge([
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
      'contribution_status_id' => 'Pending',
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
            'membership_type_id' => $this->ids['membership_type'][0],
            'contribution_recur_id' => $contributionRecur['id'],
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
          ],
        ],
      ],
    ])['id'];

    $this->ids['ContributionRecur'][0] = $contributionRecur['id'];
    $this->ids['Contribution'][0] = $orderID;
  }

  /**
   * Create an extraneous contribution to throw off any 'number one bugs'.
   *
   * Ie this means our real data starts from 2 & we won't hit 'pretend passes'
   * just because the number 1 is used for multiple entities.
   */
  protected function createExtraneousContribution() {
    $this->contributionCreate([
      'contact_id' => $this->_contactID,
      'is_test' => 1,
      'financial_type_id' => 1,
      'invoice_id' => 'abcd',
      'trxn_id' => 345,
      'receive_date' => '2019-07-25 07:34:23',
    ]);
  }

}

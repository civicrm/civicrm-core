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

use Civi\Api4\ContributionPage;
use Civi\Api4\ContributionRecur;
use Civi\Api4\Order;
use Civi\Api4\Payment;
use Civi\Api4\PaymentProcessor;

/**
 * Trait CRMTraits_Contribute_RecurFormsTrait
 */
trait CRMTraits_Contribute_RecurFormsTrait {

  /**
   * @var int
   */
  protected $paymentProcessorId;

  public function tearDown(): void {
    if ($this->paymentProcessorId) {
      PaymentProcessor::delete(FALSE)
        ->addWhere('id', '=', $this->paymentProcessorId)
        ->execute();
    }
    parent::tearDown();
  }

  /**
   * Get contact id.
   *
   *  return int
   */
  public function getContactID(): int {
    if (!isset($this->ids['Contact'][0])) {
      $this->ids['Contact'][0] = $this->individualCreate();
    }
    return $this->ids['Contact'][0];
  }

  /**
   *
   */
  public function addContribution(): void {
    $this->paymentProcessorId = $this->processorCreate();
    $order = Order::create(FALSE)
      ->addLineItem(['unit_price' => 10, 'financial_type_id:name' => 'Donation', 'qty' => 1])
      ->setContributionValues([
        'contact_id' => $this->getContactID(),
        'contribution_recur_id' => $this->getContributionRecurID(),
        'financial_type_id:name' => 'Donation',
        'total_amount' => 10,
        'contribution_page_id' => $this->getContributionPageID(),
      ])->execute()->single();
    Payment::create(FALSE)
      ->addValue('contribution_id', $order['id'])
      ->addValue('total_amount', 10)
      ->addValue('payment_processor_id', $this->paymentProcessorId)
      ->setNotificationForCompleteOrder(FALSE)
      ->execute();
    CRM_Contribute_BAO_ContributionRecur::ensureTemplateContributionExists($this->getContributionRecurID());
  }

  /**
   * Get contribution recur ID.
   *
   * return int
   */
  public function getContributionRecurID(): int {
    if (!isset($this->ids['ContributionRecur'][0])) {
      $this->ids['ContributionRecur'][0] = ContributionRecur::create(FALSE)
        ->addValue('contact_id', $this->getContactID())
        ->addValue('amount', 10)
        ->addValue('installments', 12)
        ->addValue('frequency_interval', 1)
        ->addValue('frequency_unit', 'month')
        ->addValue('payment_processor_id', $this->paymentProcessorId)
        ->execute()->single()['id'];
    }
    return $this->ids['ContributionRecur'][0];
  }

  /**
   * Get a contribution page id.
   *
   * @return int
   */
  public function getContributionPageID(): int {
    if (!isset($this->ids['ContributionPage'][0])) {
      $this->ids['ContributionPage'][0] = ContributionPage::create(FALSE)
        ->addValue('receipt_from_name', 'Bob')
        ->addValue('receipt_from_email', 'bob@example.org')
        ->addValue('financial_type_id:name', 'Donation')
        ->addValue('title', 'page')
        ->execute()->single()['id'];
    }
    return $this->ids['ContributionPage'][0];
  }

}

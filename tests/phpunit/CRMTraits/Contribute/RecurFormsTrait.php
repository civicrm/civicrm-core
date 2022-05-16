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
      \Civi\Api4\PaymentProcessor::delete(FALSE)
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
    $order = $this->callAPISuccess('Order', 'create', [
      'contact_id' => $this->getContactID(),
      'contribution_recur_id' => $this->getContributionRecurID(),
      'financial_type_id' => 'Donation',
      'total_amount' => 10,
      'contribution_page_id' => $this->getContributionPageID(),
    ]);
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $order['id'],
      'total_amount' => 10,
      'payment_processor_id' => $this->paymentProcessorId,
      'is_send_contribution_notification' => FALSE,
    ]);
  }

  /**
   * Get contribution recur ID.
   *
   * return int
   */
  public function getContributionRecurID(): int {
    if (!isset($this->ids['ContributionRecur'][0])) {
      $this->ids['ContributionRecur'][0] = $this->callAPISuccess('ContributionRecur', 'create', [
        'contact_id' => $this->getContactID(),
        'amount' => 10,
        'installments' => 12,
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
        'payment_processor_id' => $this->paymentProcessorId,
      ])['id'];
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
      $this->ids['ContributionPage'][0] = $this->callAPISuccess('ContributionPage', 'create', [
        'receipt_from_name' => 'Bob',
        'receipt_from_email' => 'bob@example.org',
        'financial_type_id' => 'Donation',
      ])['id'];
    }
    return $this->ids['ContributionPage'][0];
  }

}

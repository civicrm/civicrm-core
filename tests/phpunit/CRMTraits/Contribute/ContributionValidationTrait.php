<?php

use Civi\Api4\Contribution;
use Civi\Api4\FinancialItem;
use Civi\Api4\LineItem;
use Civi\Api4\Payment;

trait CRMTraits_Contribute_ContributionValidationTrait {

  /**
   * @param array $contribution
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function validateContribution(array $contribution): void {
    $lineItems = LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->execute();
    $totalExTax = 0;
    $totalTax = 0;
    $participants = [];
    foreach ($lineItems as $lineItem) {
      $totalExTax += $lineItem['line_total'];
      $totalTax = $lineItem['tax_amount'];
      $itemsExTax = 0;
      $itemsTax = 0;
      if ($lineItem['entity_table'] === 'civicrm_participant' && $lineItem['qty'] > 0) {
        $participants[$lineItem['entity_id']] = $lineItem['entity_id'];
      }
      $financialItems = FinancialItem::get(FALSE)
        ->addWhere('entity_table', '=', 'civicrm_line_item')
        ->addWhere('entity_id', '=', $lineItem['id'])
        ->addSelect('*', 'financial_account_id.*')
        ->execute();
      foreach ($financialItems as $financialItem) {
        if ($financialItem['financial_account_id.is_tax']) {
          $itemsTax += $financialItem['amount'];
        }
        else {
          $itemsExTax += $financialItem['amount'];
        }
      }
      $this->assertEquals($lineItem['line_total'], $itemsExTax);
      $this->assertEquals($lineItem['tax_amount'], $itemsTax);
    }
    $this->assertEquals($contribution['total_amount'], $totalExTax + $totalTax);
    $this->assertEquals($contribution['tax_amount'], $totalTax);

    $participantPayments = $this->callAPISuccess('ParticipantPayment', 'get', ['contribution_id' => $contribution['id'], 'return' => 'participant_id', 'version' => 3])['values'];
    $this->assertCount(count($participants), $participantPayments);
    foreach ($participantPayments as $payment) {
      $this->assertContains($payment['participant_id'], $participants);
    }
  }

  /**
   * Validate all created contributions.
   *
   * @throws \CRM_Core_Exception
   */
  protected function validateAllContributions(): void {
    $contributions = Contribution::get(FALSE)->setSelect(['total_amount', 'tax_amount'])->execute();
    foreach ($contributions as $contribution) {
      $this->validateContribution($contribution);
    }
  }


  /**
   * @param $payments
   *
   * @throws \CRM_Core_Exception
   */
  protected function validatePayments($payments): void {
    foreach ($payments as $payment) {
      $balance = CRM_Contribute_BAO_Contribution::getContributionBalance($payment['contribution_id']);
      if ($balance < 0 && $balance + $payment['total_amount'] === 0.0) {
        // This is an overpayment situation. there are no financial items to allocate the overpayment.
        // This is a pretty rough way at guessing which payment is the overpayment - but
        // for the test suite it should be enough.
        continue;
      }
      $items = $this->callAPISuccess('EntityFinancialTrxn', 'get', [
        'financial_trxn_id' => $payment['id'],
        'entity_table' => 'civicrm_financial_item',
        'return' => ['amount'],
      ])['values'];
      $itemTotal = 0;
      foreach ($items as $item) {
        $itemTotal += $item['amount'];
      }
      $this->assertEquals(round((float) $payment['total_amount'], 2), round($itemTotal, 2));
    }
  }

  /**
   * Validate all created payments.
   *
   * @throws \CRM_Core_Exception
   */
  protected function validateAllPayments(): void {
    $payments = Payment::get(FALSE)->execute();
    $this->validatePayments($payments);
  }

}

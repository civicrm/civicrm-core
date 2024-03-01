<?php

namespace Civi\Test\ExampleData\Contribution;

class Euro5990 extends \Civi\Test\EntityExample {

  public function getExamples(): iterable {
    yield [
      'name' => "entity/{$this->entityName}/{$this->exName}/completed",
    ];
  }

  public function build(array &$example): void {
    $base = [
      'id' => 50,
      'contact_id' => 100,
      'financial_type_id' => 2,
      'payment_instrument_id:label' => 'Debit Card',
      'contribution_page_id' => 2,
      'receive_date' => '2021-07-23 15:39:20',
      'revenue_recognition_date' => '2021-07-23 00:00:00',
      'thankyou_date' => '2021-07-23 15:39:20',
      'cancel_date' => '',
      'cancel_reason' => '',
      'non_deductible_amount' => 5,
      'total_amount' => 5990.99,
      'fee_amount' => 0.99,
      'net_amount' => 5990,
      'currency' => 'EUR',
      'source' => 'Online donation',
      'invoice_number' => 56789,
      'amount_level' => 'premium purchased',
      'contribution_recur_id' => 50,
      'check_number' => '',
      'campaign_id:label' => 'Outreach',
      'creditnote_id' => '',
      'trxn_id' => 123,
      'invoice_id' => 'inv123',
      'is_test' => TRUE,
      'is_pay_later' => FALSE,
    ];

    $extras['completed'] = [
      'contribution_status_id' => 1,
    ];

    $example['data'] = $base + $extras[basename($example['name'])];
  }

}

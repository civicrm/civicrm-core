<?php

namespace Civi\Test\ExampleData\ContributionRecur;

class Euro5990 extends \Civi\Test\EntityExample {

  public function getExamples(): iterable {
    yield [
      'name' => "entity/{$this->entityName}/{$this->exName}/pending",
    ];
    yield [
      'name' => "entity/{$this->entityName}/{$this->exName}/cancelled",
    ];
  }

  public function build(array &$example): void {
    $base = [
      'id' => 50,
      'contact_id' => 100,
      'is_email_receipt' => 1,
      'start_date' => '2021-07-23 15:39:20',
      'end_date' => '2021-07-26 18:07:20',
      'amount' => 5990.99,
      'currency' => 'EUR',
      'frequency_unit' => 'year',
      'frequency_interval' => 2,
      'installments' => 24,
      'payment_instrument_id:label' => 'Debit Card',
      'financial_type_id:label' => 'Member dues',
      'processor_id' => 'abc_xyz',
      'payment_processor_id' => 2,
      'trxn_id' => 123,
      'invoice_id' => 'inv123',
      'sequential' => 1,
      'failure_retry_date' => '2020-01-03',
      'auto_renew' => 1,
      'cycle_day' => '15',
      'is_test' => TRUE,
      'payment_token_id' => 4,
    ];

    $extras['pending'] = [
      'status_id' => 2,
      'cancel_date' => NULL,
      'cancel_reason' => NULL,
    ];
    $extras['cancelled'] = [
      'status_id' => 3,
      'cancel_date' => '2021-08-19 09:12:45',
      'cancel_reason' => 'Because',
    ];

    $example['data'] = $base + $extras[basename($example['name'])];
  }

}

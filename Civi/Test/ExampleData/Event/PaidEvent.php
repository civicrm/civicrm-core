<?php

namespace Civi\Test\ExampleData\Event;

use Civi\Test\EntityExample;

class PaidEvent extends EntityExample {

  public function getExamples(): iterable {
    yield [
      'name' => 'entity/' . $this->entityName . '/' . $this->getExampleName(),
    ];
  }

  public function build(array &$example): void {
    $example['data'] = [
      'id' => 0,
      'title' => 'Annual CiviCRM meet',
      'summary' => 'If you have any CiviCRM related issues or want to track where CiviCRM is heading, Sign up now',
      'description' => 'This event is intended to give brief idea about progress of CiviCRM and giving solutions to common user issues',
      'event_type_id' => 1,
      'is_public' => TRUE,
      'start_date' => 20081021,
      'end_date' => '+ 1 month',
      'is_online_registration' => TRUE,
      'registration_start_date' => 20080601,
      'registration_end_date' => '+ 1 month',
      'is_multiple_registrations' => TRUE,
      'max_participants' => 5,
      'event_full_text' => 'Sorry! We are already full',
      'is_monetary' => TRUE,
      'financial_type_id:name' => 'Event Fee',
      'is_active' => 1,
      'default_role_id' => 1,
      'is_show_location' => TRUE,
      'is_email_confirm' => 1,
      'is_pay_later' => TRUE,
      'pay_later_text' => 'Transfer funds',
      'pay_later_receipt' => 'Please transfer funds to our bank account.',
      'fee_label' => 'Event fees',
      'allow_selfcancelxfer' => TRUE,
    ];
  }

}

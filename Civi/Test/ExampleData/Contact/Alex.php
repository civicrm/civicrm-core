<?php

namespace Civi\Test\ExampleData\Contact;

use Civi\Test\EntityExample;

class Alex extends EntityExample {

  public function getExamples(): iterable {
    yield [
      'name' => "entity/{$this->entityName}/" . $this->getExampleName(),
    ];
  }

  public function build(array &$example): void {
    $example['data'] = [
      'id' => 101,
      'first_name' => 'Alex',
      'last_name' => 'D\u00edaz',
      'contact_type' => 'Individual',
      'sort_name' => 'D\u00edaz, Alex',
      'display_name' => 'Dr. Alex D\u00edaz',
      'prefix_id:label' => 'Dr.',
      'gender_id:label' => 'Female',
      'email_greeting_display' => 'Dear Alex',
      'do_not_email' => 1,
      'do_not_phone' => 1,
      'do_not_mail' => 0,
      'do_not_sms' => 0,
      'do_not_trade' => 0,
      'is_opt_out' => 0,
      'birth_date' => '1994-04-21',
      'is_deceased' => 0,
      'contact_is_deleted' => 0,
      'phone_primary.phone' => '293-6934',
      'email_primary.email' => 'daz.alex67@testing.net',
    ];
  }

}

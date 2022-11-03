<?php

namespace Civi\Test\ExampleData\Contact;

use Civi\Test\EntityExample;

class Barb extends EntityExample {

  public function getExamples(): iterable {
    yield [
      'name' => "entity/{$this->entityName}/" . $this->getExampleName(),
    ];
  }

  public function build(array &$example): void {
    $example['data'] = [
      'contact_id' => 100,
      'contact_type' => 'Individual',
      'sort_name' => 'Johnson, Barbara',
      'display_name' => 'Barbara Johnson',
      'do_not_email' => 1,
      'do_not_phone' => 1,
      'do_not_mail' => 0,
      'do_not_sms' => 0,
      'do_not_trade' => 0,
      'is_opt_out' => 0,
      'nick_name' => 'Barb',
      'first_name' => 'Barbara',
      'last_name' => 'Johnson',
      'prefix_id:label' => 'Ms.',
      'gender_id:label' => 'Female',
      'birth_date' => '1999-05-11',
      'is_deceased' => 0,
      'contact_is_deleted' => 0,
      'phone_primary.phone' => '393-7924',
      'email_primary.email' => 'barb@testing.net',
      'address_primary.street_address' => '1407 Graymalkin Lane',
      'address_primary.supplemental_address_1' => 'Salem Center',
      'address_primary.supplemental_address_2' => 'Danger Room',
      'address_primary.supplemental_address_3' => 'Dimension 3',
      'address_primary.city' => 'Salem Center',
      'email_greeting_id' => 1,
      'email_greeting_display' => 'Dear Barb',
      'postal_greeting_id' => 1,
      'postal_greeting_display' => 'Dear Barb',
    ];
  }

}

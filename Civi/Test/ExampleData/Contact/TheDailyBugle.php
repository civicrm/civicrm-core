<?php

namespace Civi\Test\ExampleData\Contact;

use Civi\Test\EntityExample;

class TheDailyBugle extends EntityExample {

  public function getExamples(): iterable {
    yield [
      'name' => "entity/{$this->entityName}/" . $this->getExampleName(),
    ];
  }

  public function build(array &$example): void {
    $example['data'] = [
      'contact_id' => 0,
      'contact_type' => 'Organization',
      'organization_name' => 'The Daily Bugle',
      'sort_name' => 'Daily Bugle',
      'display_name' => 'The Daily Bugle',
      'do_not_email' => 1,
      'do_not_phone' => 1,
      'do_not_mail' => 0,
      'do_not_sms' => 0,
      'do_not_trade' => 0,
      'is_opt_out' => 0,
      'legal_name' => 'The Daily Bugle',
      'sic_code' => NULL,
      'contact_is_deleted' => '0',
      'email_primary.email' => 'clark@example.com',
      'address_primary.street_address' => 'Goodman Building',
      'address_primary.supplemental_address' => 'Cnr 39th Street and Second Avenue',
      'address_primary.city' => 'New York',
      'email_greeting_id' => 1,
      'email_greeting_display' => 'Dear Bugle Reporters',
      'postal_greeting_id' => 1,
      'postal_greeting_display' => 'Dear Bugle Reporters',
    ];
  }

}

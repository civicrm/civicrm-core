<?php

namespace Civi\Test\ExampleData\Contact;

use Civi\Test\EntityExample;

class Alex extends EntityExample {

  public function getExamples(): iterable {
    yield [
      'name' => "entity/{$this->entityName}/{$this->exName}",
    ];
  }

  public function build(array &$example): void {
    $example['data'] = [
      'id' => 0,
      'first_name' => 'Alex',
      'middle_name' => '',
      'last_name' => 'D\u00edaz',
      'contact_type' => 'Individual',
      'contact_sub_type' => NULL,
      'sort_name' => 'D\u00edaz, Alex',
      'display_name' => 'Dr. Alex D\u00edaz',
      'prefix_id:label' => 'Dr.',
      'gender_id:label' => 'Female',
      'email_greeting_display' => 'Dear Alex',
      'do_not_email' => '1',
      'do_not_phone' => '1',
      'do_not_mail' => '0',
      'do_not_sms' => '0',
      'do_not_trade' => '0',
      'is_opt_out' => '0',
      'legal_identifier' => NULL,
      'external_identifier' => NULL,
      'nick_name' => NULL,
      'legal_name' => NULL,
      'image_URL' => NULL,
      'preferred_communication_method' => NULL,
      'preferred_language' => NULL,
      'formal_title' => NULL,
      'communication_style_id' => NULL,
      'job_title' => NULL,
      'birth_date' => '1994-04-21',
      'is_deceased' => '0',
      'deceased_date' => NULL,
      'household_name' => NULL,
      'organization_name' => NULL,
      'sic_code' => NULL,
      'phone_type_id' => '1',
      'phone_primary.phone' => '293-6934',
      'email_primary.email' => 'daz.alex67@testing.net',
    ];
  }

}

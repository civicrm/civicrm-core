<?php

namespace Civi\Test\ExampleData\Contact;

use Civi\Test\EntityExample;

class Barb extends EntityExample {

  public function getExamples(): iterable {
    yield [
      'name' => "entity/{$this->entityName}/{$this->exName}",
    ];
  }

  public function build(array &$example): void {
    $example['data'] = [
      'contact_id' => 0,
      'contact_type' => 'Individual',
      'contact_sub_type' => NULL,
      'sort_name' => 'Johnson, Barbara',
      'display_name' => 'Barbara Johnson',
      'do_not_email' => '1',
      'do_not_phone' => '1',
      'do_not_mail' => '0',
      'do_not_sms' => '0',
      'do_not_trade' => '0',
      'is_opt_out' => '0',
      'legal_identifier' => NULL,
      'external_identifier' => NULL,
      'nick_name' => 'Barb',
      'legal_name' => NULL,
      'image_URL' => NULL,
      'preferred_communication_method' => NULL,
      'preferred_language' => NULL,
      'first_name' => 'Barbara',
      'middle_name' => '',
      'last_name' => 'Johnson',
      'prefix_id' => '4',
      'suffix_id' => NULL,
      'formal_title' => NULL,
      'communication_style_id' => NULL,
      'job_title' => NULL,
      'gender_id' => '1',
      'birth_date' => '1999-05-11',
      'is_deceased' => '0',
      'deceased_date' => NULL,
      'organization_name' => NULL,
      'phone_primary.phone_type_id' => 1,
      'phone_primary.phone' => '393-7924',
      'email_primary.email' => 'barb@testing.net',
      'email_greeting_display' => 'Dear Barb',
      'postal_greeting_display' => 'Dear Barb',
    ];
  }

}

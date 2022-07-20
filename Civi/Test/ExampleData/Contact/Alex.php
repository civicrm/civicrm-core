<?php

namespace Civi\Test\ExampleData\Contact;

class Alex extends \Civi\Test\EntityExample {

  public function getExamples(): iterable {
    yield [
      'name' => "entity/{$this->entityName}/{$this->exName}",
    ];
  }

  public function build(array &$example): void {
    $example['data'] = [
      'contact_id' => '100',
      'contact_type' => 'Individual',
      'contact_sub_type' => NULL,
      'sort_name' => 'D\u00edaz, Alex',
      'display_name' => 'Dr. Alex D\u00edaz',
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
      'preferred_mail_format' => 'Both',
      'first_name' => 'Alex',
      'middle_name' => '',
      'last_name' => 'D\u00edaz',
      'prefix_id' => '4',
      'suffix_id' => NULL,
      'formal_title' => NULL,
      'communication_style_id' => NULL,
      'job_title' => NULL,
      'gender_id' => '1',
      'birth_date' => '1994-04-21',
      'is_deceased' => '0',
      'deceased_date' => NULL,
      'household_name' => NULL,
      'organization_name' => NULL,
      'sic_code' => NULL,
      'contact_is_deleted' => '0',
      'current_employer' => NULL,
      'address_id' => NULL,
      'street_address' => NULL,
      'supplemental_address_1' => NULL,
      'supplemental_address_2' => NULL,
      'supplemental_address_3' => NULL,
      'city' => NULL,
      'postal_code_suffix' => NULL,
      'postal_code' => NULL,
      'geo_code_1' => NULL,
      'geo_code_2' => NULL,
      'state_province_id' => NULL,
      'country_id' => NULL,
      'phone_id' => '7',
      'phone_type_id' => '1',
      'phone' => '293-6934',
      'email_id' => '7',
      'email' => 'daz.alex67@testing.net',
      'on_hold' => '0',
      'im_id' => NULL,
      'provider_id' => NULL,
      'im' => NULL,
      'worldregion_id' => NULL,
      'world_region' => NULL,
      'languages' => NULL,
      'individual_prefix' => 'Dr.',
      'individual_suffix' => NULL,
      'communication_style' => NULL,
      'gender' => 'Female',
      'state_province_name' => NULL,
      'state_province' => NULL,
      'country' => NULL,
    ];
  }

}

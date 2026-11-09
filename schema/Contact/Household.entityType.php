<?php

return [
  'name' => 'Household',
  'facade_of' => 'Contact',
  'where' => ['contact_type' => 'Household'],
  'getInfo' => fn() => CRM_Contact_BAO_ContactType::getFacadeInfo('Household'),
  // Contact fields which are only for individuals or organizations
  'alterFields' => fn(array $fields) => [
    'first_name' => FALSE,
    'middle_name' => FALSE,
    'last_name' => FALSE,
    'prefix_id' => FALSE,
    'suffix_id' => FALSE,
    'formal_title' => FALSE,
    'gender_id' => FALSE,
    'birth_date' => FALSE,
    'job_title' => FALSE,
    'employer_id' => FALSE,
    'organization_name' => FALSE,
    'legal_name' => FALSE,
    'sic_code' => FALSE,
  ] + $fields,
];

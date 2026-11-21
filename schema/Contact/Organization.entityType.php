<?php

return [
  'name' => 'Organization',
  'facade_of' => 'Contact',
  'where' => ['contact_type' => 'Organization'],
  'getInfo' => fn() => CRM_Contact_BAO_ContactType::getFacadeInfo('Organization'),
  // Contact fields which are only for individuals or households
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
    'household_name' => FALSE,
    'primary_contact_id' => FALSE,
  ] + $fields,
];

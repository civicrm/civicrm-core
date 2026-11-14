<?php

return [
  'name' => 'Individual',
  'facade_of' => 'Contact',
  'where' => ['contact_type' => 'Individual'],
  'getInfo' => fn() => CRM_Contact_BAO_ContactType::getFacadeInfo('Individual'),
  // Contact fields which are only for organizations or households
  'alterFields' => fn(array $fields) => [
    'organization_name' => FALSE,
    'legal_name' => FALSE,
    'sic_code' => FALSE,
    'household_name' => FALSE,
    'primary_contact_id' => FALSE,
  ] + $fields,
];

<?php
use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'requires' => [],
  'entity_type' => NULL,
  'join_entity' => NULL,
  'title' => E::ts('Find Contributions'),
  'description' => 'The original searches for Contributions but also can show just soft credits and recurring contributions.  Maybe recur is better as a separate search?  And soft credits?',
  'is_dashlet' => FALSE,
  'is_public' => FALSE,
  'is_token' => FALSE,
  'contact_summary' => NULL,
  'summary_contact_type' => NULL,
  'icon' => 'fa-credit-card',
  'server_route' => 'civicrm/sk/contrib',
  'permission' => ['access CiviContribute'],
  'redirect' => NULL,
  'create_submission' => FALSE,
  'navigation' => [
    'parent' => 'Experimental',
    'label' => E::ts('Find Contributions'),
    'weight' => 0,
  ],
];

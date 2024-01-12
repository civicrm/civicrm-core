<?php
use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Find Contributions'),
  'description' => 'The original searches for Contributions but also can show just soft credits and recurring contributions.  Maybe recur is better as a separate search?  And soft credits?',
  'icon' => 'fa-credit-card',
  'server_route' => 'civicrm/sk/contrib',
  'permission' => ['access CiviContribute'],
  'navigation' => [
    'parent' => 'Experimental',
    'label' => E::ts('Find Contributions'),
    'weight' => 0,
  ],
];

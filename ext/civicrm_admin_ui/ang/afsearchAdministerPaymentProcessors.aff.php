<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Administer Payment Processors'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/paymentProcessor',
  'permission' => ['administer payment processors'],
];

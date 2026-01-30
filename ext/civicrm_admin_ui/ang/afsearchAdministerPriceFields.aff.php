<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Price Fields'),
  'description' => E::ts('Administer Price Fields'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/price/field',
];

<?php

use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Price Sets'),
  'description' => E::ts('Administer Price Sets'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/price',
];

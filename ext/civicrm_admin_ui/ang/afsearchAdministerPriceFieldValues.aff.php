<?php

use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Price Field Options'),
  'description' => E::ts('Administer Price Field Options'),
  'icon' => 'fa-list-ol',
  'server_route' => 'civicrm/admin/price/field/option',
];

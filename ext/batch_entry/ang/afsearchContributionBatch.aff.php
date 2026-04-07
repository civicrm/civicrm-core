<?php
use CRM_BatchEntry_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Contribution batch'),
  'icon' => 'fa-table-cells-large',
  'server_route' => 'civicrm/batch/contribution',
  'permission' => [
    'access CiviCRM',
    'edit contributions',
  ],
  'confirmation_type' => 'redirect_to_url',
];

<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Cases'),
  'placement' => [
    'contact_summary_tab',
  ],
  'placement_weight' => 50,
  'icon' => 'fa-folder-open',
  'permission' => [
    'access CiviCRM',
    'access my cases and activities',
  ],
];

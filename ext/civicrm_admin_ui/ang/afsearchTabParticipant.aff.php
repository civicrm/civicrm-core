<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Events'),
  'placement' => [
    'contact_summary_tab',
  ],
  'placement_weight' => 40,
  'icon' => 'fa-calendar',
  'permission' => [
    'access CiviCRM',
    'access CiviEvent',
  ],
];

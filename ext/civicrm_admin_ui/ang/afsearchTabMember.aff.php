<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Memberships'),
  'placement' => [
    'contact_summary_tab',
  ],
  'placement_filters' => [
    ['contact_type' => 'Individual', 'Household'],
  ],
  'placement_weight' => 30,
  'icon' => 'fa-id-badge',
  'permission' => [
    'access CiviCRM',
    'access CiviMember',
  ],
];

<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Pledges'),
  'placement_weight' => 20,
  'placement' => [
    'contact_summary_tab',
  ],
  'icon' => 'fa-paper-plane',
  'permission' => [
    'access CiviPledge',
  ],
];

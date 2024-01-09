<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Memberships'),
  'placement' => [
    'contact_summary_tab',
  ],
  'summary_contact_type' => [
    'Individual',
    'Household',
  ],
  'summary_weight' => 30,
  'icon' => 'fa-id-badge',
  'permission' => [
    'access CiviCRM',
    'access CiviMember',
  ],
  'search_displays' => [
    'Contact_Summary_Memberships.Contact_Summary_Memberships_Active',
    'Contact_Summary_Memberships.Contact_Summary_Memberships_Inactive',
  ],
];

<?php

use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Premium Products'),
  'placement' => [],
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/contribute/managePremiums',
  'is_public' => FALSE,
  'permission' => [
    'access CiviCRM',
  ],
  'permission_operator' => 'AND',
  'submit_enabled' => TRUE,
  'create_submission' => FALSE,
];

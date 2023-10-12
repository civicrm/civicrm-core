<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Fields'),
  'description' => E::ts('Administer custom fields list'),
  'server_route' => 'civicrm/admin/custom/group/fields',
  'permission' => ['administer CiviCRM data'],
];

<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Scheduled Reminders'),
  'description' => E::ts('Administer scheduled reminders'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/scheduleReminders',
  'permission' => [
    'administer CiviCRM data',
  ],
];

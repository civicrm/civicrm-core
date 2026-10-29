<?php
use CRM_ScheduledCommunications_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_Schedule_Reminders',
    'entity' => 'Navigation',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('Schedule Reminders'),
        'name' => 'Schedule Reminders',
        'url' => 'civicrm/admin/scheduleReminders?reset=1',
        'permission' => 'administer CiviCRM',
        'permission_operator' => '',
        'parent_id.name' => 'Communications',
        'is_active' => TRUE,
        'has_separator' => NULL,
        'weight' => 4,
      ],
      'match' => ['domain_id', 'name'],
    ],
  ],
];

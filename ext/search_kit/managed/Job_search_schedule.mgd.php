<?php
use CRM_Search_ExtensionUtil as E;

return [
  [
    'name' => 'Job_search_schedule_send_due',
    'entity' => 'Job',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Send Scheduled Search Reports',
        'description' => E::ts('Sends scheduled emails of SearchKit reports that are due.'),
        'run_frequency' => 'Always',
        'api_entity' => 'SearchSchedule',
        'api_action' => 'sendDue',
        'parameters' => 'version=4',
        'is_active' => TRUE,
      ],
      'match' => ['domain_id', 'api_entity', 'api_action'],
    ],
  ],
];

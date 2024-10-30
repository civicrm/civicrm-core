<?php

return [
  'type' => 'search',
  'title' => ts('Activities'),
  'description' => '',
  // Disabled temporarily for https://lab.civicrm.org/dev/core/-/issues/4950
  // 'placement' => ['contact_summary_tab'],
  'summary_weight' => 70,
  'icon' => 'fa-tasks',
  'permission' => [
    'access CiviCRM',
  ],
  'permission_operator' => 'AND',
];

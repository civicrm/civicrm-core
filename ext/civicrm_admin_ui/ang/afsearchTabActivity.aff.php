<?php

return [
  'type' => 'search',
  'title' => ts('Activities'),
  'description' => '',
  'placement' => ['contact_summary_tab'],
  'placement_weight' => 70,
  'icon' => 'fa-tasks',
  'permission' => [
    'access CiviCRM',
  ],
  'permission_operator' => 'AND',
  'requires' => ['crmAdminUi'],
];

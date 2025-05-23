<?php

return [
  'type' => 'search',
  'title' => ts('Notes'),
  'description' => '',
  'placement' => ['contact_summary_tab'],
  'placement_weight' => 100,
  'icon' => 'fa-sticky-note-o',
  'permission' => [
    'access CiviCRM',
  ],
  'permission_operator' => 'AND',
  'navigation' => NULL,
];

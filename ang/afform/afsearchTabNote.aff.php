<?php

return [
  'type' => 'search',
  'title' => ts('Notes'),
  'description' => '',
  'placement' => ['contact_summary_tab'],
  'summary_weight' => 100,
  'icon' => 'fa-sticky-note-o',
  'summary_contact_type' => NULL,
  'permission' => [
    'access CiviCRM',
  ],
  'permission_operator' => 'AND',
  'navigation' => NULL,
];

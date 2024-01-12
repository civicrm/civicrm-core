<?php

return [
  'type' => 'search',
  'title' => ts('Relationships'),
  'permission' => [
    'access CiviCRM',
  ],
  'placement' => ['contact_summary_tab'],
  'icon' => 'fa-handshake-o',
  'summary_weight' => 80,
  'permission_operator' => 'AND',
];

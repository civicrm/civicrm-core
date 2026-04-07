<?php

return [
  'type' => 'form',
  'title' => ts('Edit Organization'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/quick-edit/Organization',
  'permission' => [
    'access CiviCRM',
  ],
  'permission_operator' => 'AND',
  'submit_enabled' => TRUE,
  'create_submission' => FALSE,
];

<?php

return [
  'type' => 'form',
  'title' => ts('Edit Individual'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/quick-edit/Individual',
  'permission' => [
    'access CiviCRM',
  ],
  'permission_operator' => 'AND',
  'submit_enabled' => TRUE,
  'create_submission' => FALSE,
];

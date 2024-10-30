<?php

return [
  'type' => 'form',
  'title' => ts('New Individual'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/quick-add/Individual',
  'permission' => [
    'add contacts',
  ],
  'permission_operator' => 'AND',
  'submit_enabled' => TRUE,
  'create_submission' => FALSE,
];

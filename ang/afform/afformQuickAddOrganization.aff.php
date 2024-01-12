<?php

return [
  'type' => 'form',
  'title' => ts('New Organization'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/quick-add/Organization',
  'permission' => [
    'add contacts',
  ],
  'permission_operator' => 'AND',
  'submit_enabled' => TRUE,
  'create_submission' => FALSE,
];

<?php

return [
  'type' => 'form',
  'title' => ts('Edit Household'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/quick-edit/Household',
  'permission' => [
    'access CiviCRM',
  ],
  'permission_operator' => 'AND',
  'submit_enabled' => TRUE,
  'create_submission' => FALSE,
];

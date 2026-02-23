<?php
use CRM_UserDashboard_ExtensionUtil as E;

return [
  'type' => 'form',
  'title' => E::ts('Update Group Subscriptions'),
  'icon' => 'fa-users',
  'server_route' => 'civicrm/user/group-subscriptions',
  'permission' => [
    'access Contact Dashboard',
  ],
  'create_submission' => TRUE,
  'confirmation_type' => 'show_confirmation_message',
  'confirmation_message' => E::ts('Your group subscriptions have been saved.'),
];

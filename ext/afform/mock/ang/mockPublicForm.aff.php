<?php
return [
  'type' => 'form',
  'title' => ts('My public form'),
  'server_route' => 'civicrm/mock-public-form',
  'is_public' => TRUE,
  'permission' => '*always allow*',
  'placement' => ['msg_token_single'],
  'create_submission' => FALSE,
];

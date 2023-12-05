<?php
return [
  'type' => 'form',
  'title' => ts('My public form'),
  'server_route' => 'civicrm/mock-public-form',
  'permission' => '*always allow*',
  'is_token' => TRUE,
];

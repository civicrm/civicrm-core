<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'type' => 'form',
  'title' => E::ts('Edit my account'),
  'icon' => 'fa-user',
  'server_route' => 'civicrm/user/edit',
  'permission' => [
    'edit my contact',
  ],
  'redirect' => '/civicrm/my-account',
];

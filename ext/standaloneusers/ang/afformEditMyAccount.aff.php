<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'type' => 'form',
  'title' => E::ts('Edit My Account'),
  'icon' => 'fa-user',
  'server_route' => 'civicrm/my-account/edit',
  'permission' => [
    'edit my contact',
  ],
  'redirect' => '/civicrm/my-account',
];

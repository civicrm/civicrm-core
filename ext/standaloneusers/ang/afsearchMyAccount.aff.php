<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('My User Account'),
  'placement' => [],
  'summary_contact_type' => ['Individual'],
  'icon' => 'fa-user',
  'server_route' => 'civicrm/user/me',
];

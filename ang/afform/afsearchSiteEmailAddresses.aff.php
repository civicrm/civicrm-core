<?php

return [
  'type' => 'search',
  'title' => ts('Site Email Addresses'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/options/from_email_address',
  'permission' => [
    'administer CiviCRM system',
  ],
];

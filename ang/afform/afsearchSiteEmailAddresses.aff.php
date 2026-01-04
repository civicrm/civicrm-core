<?php

return [
  'type' => 'search',
  'title' => ts('Site From Email Addresses'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/options/site_email_address',
  'permission' => [
    'administer CiviCRM system',
  ],
];

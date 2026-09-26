<?php
use CRM_MessageAdmin_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Message Templates'),
  'icon' => 'fa-envelope-open-o',
  'server_route' => 'civicrm/admin/messageTemplates',
  'permission' => [
    'edit message templates',
    'edit user-driven message templates',
    'edit system workflow message templates',
  ],
  'permission_operator' => 'OR',
];

<?php
use CRM_Mailing_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Manage A/B Tests'),
  'icon' => 'fa-flask',
  'server_route' => 'civicrm/mailing/abtest',
  'permission' => [
    'access CiviMail',
  ],
];

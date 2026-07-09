<?php

use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Mailings Draft'),
  'server_route' => 'civicrm/mailing/draft',
  'permission' => ['access CiviMail', 'create mailings', 'schedule mailings'],
  'permission_operator' => 'OR',
];

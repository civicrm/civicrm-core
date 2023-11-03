<?php

use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Mailings'),
  'server_route' => 'civicrm/mailing',
  'permission' => ['access CiviMail', 'create mailings', 'schedule mailings'],
  'permission_operator' => 'OR',
];

<?php
use CRM_AfformAdmin_ExtensionUtil as E;

return [
  'type' => 'system',
  'title' => E::ts('Submissions'),
  'server_route' => 'civicrm/admin/afform/submissions',
  'permission' => ['administer afform'],
];

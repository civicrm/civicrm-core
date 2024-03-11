<?php
// This file declares a new entity type. For more details, see "hook_civicrm_entityTypes" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
return [
  [
    'name' => 'CaseRole',
    'class' => 'CRM_Case_DAO_CaseRole',
    'table' => 'civicrm_relationship',
  ],
];

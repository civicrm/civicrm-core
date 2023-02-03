<?php
// This file declares a new entity type. For more details, see "hook_civicrm_entityTypes" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
return [
  [
    'name' => 'UserRole',
    'class' => 'CRM_Standaloneusers_DAO_UserRole',
    'table' => 'civicrm_user_role',
  ],
];

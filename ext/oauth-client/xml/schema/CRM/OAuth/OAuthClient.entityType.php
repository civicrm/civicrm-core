<?php
// This file declares a new entity type. For more details, see "hook_civicrm_entityTypes" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
return [
  [
    'name' => 'OAuthClient',
    'class' => 'CRM_OAuth_DAO_OAuthClient',
    'table' => 'civicrm_oauth_client',
  ],
];

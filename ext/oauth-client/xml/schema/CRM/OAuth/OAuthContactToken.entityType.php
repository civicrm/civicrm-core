<?php
// This file declares a new entity type. For more details, see "hook_civicrm_entityTypes" at:
// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
return [
  [
    'name' => 'OAuthContactToken',
    'class' => 'CRM_OAuth_DAO_OAuthContactToken',
    'table' => 'civicrm_oauth_contact_token',
  ],
];

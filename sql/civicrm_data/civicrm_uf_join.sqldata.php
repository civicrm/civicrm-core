<?php

use CRM_Utils_SQL_Literal as Literal;

$ufGroupID = fn(string $name) => new Literal("(SELECT id FROM civicrm_uf_group WHERE name = '$name')");

return CRM_Core_CodeGen_SqlData::create('civicrm_uf_join')
  ->addDefaults([
    'is_active' => 1,
    'entity_table' => NULL,
    'entity_id' => NULL,
  ])
  ->addValues([
    [
      'module' => 'User Registration',
      'weight' => 1,
      'uf_group_id' => $ufGroupID('name_and_address'),
    ],
    [
      'module' => 'User Account',
      'weight' => 1,
      'uf_group_id' => $ufGroupID('name_and_address'),
    ],
    [
      'module' => 'Profile',
      'weight' => 1,
      'uf_group_id' => $ufGroupID('name_and_address'),
    ],
    [
      'module' => 'Profile',
      'weight' => 2,
      'uf_group_id' => $ufGroupID('supporter_profile'),
    ],
    [
      'module' => 'Profile',
      'weight' => 11,
      'uf_group_id' => $ufGroupID('event_registration'),
    ],
  ]);

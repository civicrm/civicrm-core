<?php
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
      'uf_group_id' => 1,
    ],
    [
      'module' => 'User Account',
      'weight' => 1,
      'uf_group_id' => 1,
    ],
    [
      'module' => 'Profile',
      'weight' => 1,
      'uf_group_id' => 1,
    ],
    [
      'module' => 'Profile',
      'weight' => 2,
      'uf_group_id' => 2,
    ],
    [
      'module' => 'Profile',
      'weight' => 11,
      'uf_group_id' => 12,
    ],
  ]);

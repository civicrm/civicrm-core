<?php

use CRM_Standaloneusers_ExtensionUtil as E;

// Adds User to the list of entities for which custom fields can be created.
return [
  [
    'name' => 'cg_extend_objects:Users',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'cg_extend_objects',
        'label' => E::ts('User'),
        'value' => 'User',
        'name' => 'civicrm_uf_match',
        'icon' => 'fa-user',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => [
        'name',
        'option_group_id',
      ],
    ],
  ],
];

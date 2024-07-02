<?php

return [
  'name' => 'WorldRegion',
  'table' => 'civicrm_worldregion',
  'class' => 'CRM_Core_DAO_Worldregion',
  'getInfo' => fn() => [
    'title' => ts('World Region'),
    'title_plural' => ts('World Regions'),
    'description' => ts('List of World Regions'),
    'add' => '1.8',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('World Region ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Country ID'),
      'add' => '1.8',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('World Region'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => ts('Region name to be associated with countries'),
      'add' => '1.8',
      'unique_name' => 'world_region',
      'usage' => [
        'export',
      ],
    ],
  ],
];

<?php

return [
  'name' => 'ShimThing2',
  'table' => 'civicrm_shim_thing2',
  'class' => 'CRM_Shimmy_DAO_ShimThing2',
  'getInfo' => fn() => [
    'title' => 'Shimthing2',
    'title_plural' => 'Shimthing2s',
    'description' => 'ShimThing2 Example Entity',
    'log' => TRUE,
    'add' => '1.0',
    'icon' => 'fa-clone',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => 'ShimThing2 ID',
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => 'Unique ShimThing2 ID',
      'add' => '1.0',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => 'ShimThing2 Name',
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => 'Unique name for the shim thing',
      'add' => '1.0',
      'input_attrs' => [
        'maxlength' => 255,
      ],
    ],
  ],
];

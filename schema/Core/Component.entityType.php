<?php

return [
  'name' => 'Component',
  'table' => 'civicrm_component',
  'class' => 'CRM_Core_DAO_Component',
  'getInfo' => fn() => [
    'title' => ts('Component'),
    'title_plural' => ts('Components'),
    'description' => ts('Table of Core Components (deprecated - use core extensions instead)'),
    'add' => '2.0',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Component ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Component ID'),
      'add' => '2.0',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Component name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Name of the component.'),
      'add' => '2.0',
    ],
    'namespace' => [
      'title' => ts('Namespace reserved for component.'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => ts('Path to components main directory in a form of a class namespace.'),
      'add' => '2.0',
    ],
  ],
];

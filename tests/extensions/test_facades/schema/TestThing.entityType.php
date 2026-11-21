<?php
use CRM_TestFacades_ExtensionUtil as E;

return [
  'name' => 'TestThing',
  'table' => 'civicrm_test_thing',
  'class' => 'CRM_TestFacades_DAO_TestThing',
  'getInfo' => fn() => [
    'title' => E::ts('Test Thing'),
    'title_plural' => E::ts('Test Things'),
    'description' => E::ts('Things of any type'),
    'log' => TRUE,
    'icon' => 'fa-cube',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique TestThing ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'type' => [
      'title' => E::ts('Thing Type'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => E::ts('Type of Thing'),
    ],
    'name' => [
      'title' => E::ts('Thing Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => E::ts('Name'),
    ],
    'description' => [
      'title' => E::ts('Thing Description'),
      'sql_type' => 'varchar(256)',
      'input_type' => 'Text',
      'description' => E::ts('Description'),
    ],
  ],
  'getIndices' => fn() => [],
  'getPaths' => fn() => [],
];

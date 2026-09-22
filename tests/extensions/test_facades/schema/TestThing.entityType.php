<?php
use CRM_TestFacades_ExtensionUtil as E;

return [
  'name' => 'TestThing',
  'table' => 'civicrm_test_thing',
  'class' => 'CRM_TestFacades_DAO_TestThing',
  'getInfo' => fn() => [
    'title' => E::ts('TestThing'),
    'title_plural' => E::ts('TestThings'),
    'description' => E::ts('FIXME'),
    'log' => TRUE,
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
      'description' => E::ts('Name'),
    ],
  ],
  'getIndices' => fn() => [],
  'getPaths' => fn() => [],
  'getFacades' => fn() => [
    'TestWidget' => [
      'title' => E::ts('Test Widget'),
      'title_plural' => E::ts('Test Widgets'),
      'description' => E::ts('Widgets (facade of Item)'),
      'facade_config' => [
        'field' => 'type',
        'value' => 'widget',
      ],
    ],
    'TestGizmo' => [
      'title' => E::ts('Test Gizmo'),
      'title_plural' => E::ts('Test Gizmos'),
      'description' => E::ts('Gizmos (facade of Item)'),
      'facade_config' => [
        'field' => 'type',
        'value' => 'gizmo',
      ],
      // Optional - Gizmos only use a subset of fields
      'fields' => ['id', 'type', 'name'],
    ],
  ],
];

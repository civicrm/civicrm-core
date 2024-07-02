<?php

return [
  'name' => 'RecurringEntity',
  'table' => 'civicrm_recurring_entity',
  'class' => 'CRM_Core_DAO_RecurringEntity',
  'getInfo' => fn() => [
    'title' => ts('Recurring Entity'),
    'title_plural' => ts('Recurring Entities'),
    'description' => ts('Recurring entities (used by repeating activities and events)'),
    'log' => TRUE,
    'add' => '4.6',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '4.6',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'parent_id' => [
      'title' => ts('Parent ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Recurring Entity Parent ID'),
      'add' => '4.6',
    ],
    'entity_id' => [
      'title' => ts('Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('Recurring Entity Child ID'),
      'add' => '4.6',
    ],
    'entity_table' => [
      'title' => ts('Entity Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Physical tablename for entity, e.g. civicrm_event'),
      'add' => '4.6',
    ],
    'mode' => [
      'title' => ts('Cascade Type'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('1-this entity, 2-this and the following entities, 3-all the entities'),
      'add' => '4.6',
      'default' => TRUE,
    ],
  ],
];

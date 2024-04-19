<?php

return [
  'name' => 'DedupeException',
  'table' => 'civicrm_dedupe_exception',
  'class' => 'CRM_Dedupe_DAO_DedupeException',
  'getInfo' => fn() => [
    'title' => ts('Dedupe Exception'),
    'title_plural' => ts('Dedupe Exceptions'),
    'description' => ts('Dedupe exceptions'),
    'add' => '3.3',
  ],
  'getIndices' => fn() => [
    'UI_contact_id1_contact_id2' => [
      'fields' => [
        'contact_id1' => TRUE,
        'contact_id2' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '3.3',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Dedupe Exception ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique dedupe exception id'),
      'add' => '3.3',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'contact_id1' => [
      'title' => ts('First Dupe Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to Contact ID'),
      'add' => '3.3',
      'input_attrs' => [
        'label' => ts('First Dupe Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'contact_id2' => [
      'title' => ts('Second Dupe Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to Contact ID'),
      'add' => '3.3',
      'input_attrs' => [
        'label' => ts('Second Dupe Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];

<?php

return [
  'name' => 'Note',
  'table' => 'civicrm_note',
  'class' => 'CRM_Core_DAO_Note',
  'getInfo' => fn() => [
    'title' => ts('Note'),
    'title_plural' => ts('Notes'),
    'description' => ts('Notes can be linked to any object in the application.'),
    'log' => TRUE,
    'add' => '1.1',
    'icon' => 'fa-sticky-note',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/note?reset=1&action=add&entity_table=[entity_table]&entity_id=[entity_id]',
    'view' => 'civicrm/note?reset=1&action=view&id=[id]',
    'update' => 'civicrm/note?reset=1&action=update&id=[id]',
    'delete' => 'civicrm/note?reset=1&action=delete&id=[id]',
  ],
  'getIndices' => fn() => [
    'index_entity' => [
      'fields' => [
        'entity_table' => TRUE,
        'entity_id' => TRUE,
      ],
      'add' => '1.1',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Note ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Note ID'),
      'add' => '1.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'entity_table' => [
      'title' => ts('Note Entity'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Name of table where item being referenced is stored.'),
      'add' => '1.1',
      'input_attrs' => [
        'label' => ts('Reference Type'),
      ],
      'pseudoconstant' => [
        'option_group_name' => 'note_used_for',
      ],
    ],
    'entity_id' => [
      'title' => ts('Note Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Foreign key to the referenced item.'),
      'add' => '1.1',
      'input_attrs' => [
        'label' => ts('Reference Item'),
      ],
      'entity_reference' => [
        'dynamic_entity' => 'entity_table',
        'key' => 'id',
      ],
    ],
    'note' => [
      'title' => ts('Note'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Note and/or Comment.'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'rows' => 4,
        'cols' => 60,
      ],
    ],
    'contact_id' => [
      'title' => ts('Created By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Contact ID creator'),
      'add' => '1.1',
      'input_attrs' => [
        'label' => ts('Created By'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'note_date' => [
      'title' => ts('Note Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'description' => ts('Date attached to the note'),
      'add' => '5.36',
      'required' => TRUE,
      'default' => 'CURRENT_TIMESTAMP',
      'input_attrs' => [
        'format_type' => 'activityDateTime',
      ],
    ],
    'created_date' => [
      'title' => ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'required' => TRUE,
      'readonly' => TRUE,
      'description' => ts('When the note was created.'),
      'add' => '5.36',
      'default' => 'CURRENT_TIMESTAMP',
      'input_attrs' => [
        'format_type' => 'activityDateTime',
      ],
    ],
    'modified_date' => [
      'title' => ts('Note Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('When was this note last modified/edited'),
      'add' => '1.1',
      'required' => TRUE,
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'input_attrs' => [
        'label' => ts('Modified Date'),
        'format_type' => 'activityDateTime',
      ],
    ],
    'subject' => [
      'title' => ts('Subject'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('subject of note description'),
      'add' => '1.5',
      'input_attrs' => [
        'size' => '60',
      ],
    ],
    'privacy' => [
      'title' => ts('Privacy'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Foreign Key to Note Privacy Level (which is an option value pair and hence an implicit FK)'),
      'add' => '3.3',
      'default' => '0',
      'pseudoconstant' => [
        'option_group_name' => 'note_privacy',
      ],
    ],
  ],
];

<?php

return [
  'name' => 'Batch',
  'table' => 'civicrm_batch',
  'class' => 'CRM_Batch_DAO_Batch',
  'getInfo' => fn() => [
    'title' => ts('Batch'),
    'title_plural' => ts('Batches'),
    'description' => ts('Stores the details of a batch operation Used primarily when doing batch operations with an external system.'),
    'add' => '3.3',
    'label_field' => 'title',
  ],
  'getIndices' => fn() => [
    'UI_name' => [
      'fields' => [
        'name' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '4.2',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Batch ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique Address ID'),
      'add' => '3.3',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Batch Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Variable name/programmatic handle for this batch.'),
      'add' => '3.3',
    ],
    'title' => [
      'title' => ts('Batch Title'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'localizable' => TRUE,
      'description' => ts('Friendly Name.'),
      'add' => '4.2',
    ],
    'description' => [
      'title' => ts('Batch Description'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'localizable' => TRUE,
      'description' => ts('Description of this batch set.'),
      'add' => '3.3',
      'input_attrs' => [
        'rows' => 4,
        'cols' => 80,
      ],
    ],
    'created_id' => [
      'title' => ts('Created By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Contact ID'),
      'add' => '3.3',
      'input_attrs' => [
        'label' => ts('Created By'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'created_date' => [
      'title' => ts('Batch Created Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('When was this item created'),
      'add' => '3.3',
      'input_attrs' => [
        'format_type' => 'activityDateTime',
      ],
    ],
    'modified_id' => [
      'title' => ts('Modified By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Contact ID'),
      'add' => '3.3',
      'input_attrs' => [
        'label' => ts('Modified By'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'modified_date' => [
      'title' => ts('Batch Modified Date'),
      'sql_type' => 'datetime',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => ts('When was this item modified'),
      'add' => '3.3',
    ],
    'saved_search_id' => [
      'title' => ts('Smart Group ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Saved Search ID'),
      'add' => '4.1',
      'input_attrs' => [
        'label' => ts('Smart Group'),
      ],
      'entity_reference' => [
        'entity' => 'SavedSearch',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'status_id' => [
      'title' => ts('Batch Status'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('fk to Batch Status options in civicrm_option_values'),
      'add' => '4.2',
      'pseudoconstant' => [
        'option_group_name' => 'batch_status',
      ],
    ],
    'type_id' => [
      'title' => ts('Batch Type'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('fk to Batch Type options in civicrm_option_values'),
      'add' => '4.2',
      'pseudoconstant' => [
        'option_group_name' => 'batch_type',
      ],
    ],
    'mode_id' => [
      'title' => ts('Batch Mode'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('fk to Batch mode options in civicrm_option_values'),
      'add' => '4.2',
      'pseudoconstant' => [
        'option_group_name' => 'batch_mode',
      ],
    ],
    'total' => [
      'title' => ts('Batch Total'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => 'Text',
      'description' => ts('Total amount for this batch.'),
      'add' => '4.2',
    ],
    'item_count' => [
      'title' => ts('Batch Number of Items'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Text',
      'description' => ts('Number of items in a batch.'),
      'add' => '4.2',
    ],
    'payment_instrument_id' => [
      'title' => ts('Batch Payment Method'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('fk to Payment Instrument options in civicrm_option_values'),
      'add' => '4.3',
      'pseudoconstant' => [
        'option_group_name' => 'payment_instrument',
      ],
    ],
    'exported_date' => [
      'title' => ts('Batch Exported Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'add' => '4.3',
    ],
    'data' => [
      'title' => ts('Batch Data'),
      'sql_type' => 'longtext',
      'input_type' => 'TextArea',
      'description' => ts('cache entered data'),
      'add' => '4.4',
    ],
  ],
];

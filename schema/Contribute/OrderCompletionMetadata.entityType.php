<?php

return [
  'name' => 'OrderCompletionMetadata',
  'table' => 'civicrm_order_completion_metadata',
  'class' => 'CRM_Contribute_DAO_OrderCompletionMetadata',
  'getInfo' => fn() => [
    'title' => ts('Order Completion Metadata'),
    'title_plural' => ts('Order Completion Metadata'),
    'description' => ts('Order Completion Metadata'),
    'log' => TRUE,
    'add' => '6.20',
  ],
  'getIndices' => fn() => [
    'UI_contribution_line_item' => [
      'fields' => [
        'contribution_id' => TRUE,
        'line_item_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '6.20',
    ],
    'I_line_item' => [
      'fields' => [
        'line_item_id' => TRUE,
      ],
      'unique' => FALSE,
      'add' => '6.20',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Order Completion Metadata ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '1.5',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'contribution_id' => [
      'title' => ts('Contribution ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to contribution table.'),
      'add' => '6.20',
      'required' => TRUE,
      'input_attrs' => [
        'label' => ts('Contribution'),
      ],
      'entity_reference' => [
        'entity' => 'Contribution',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'line_item_id' => [
      'title' => ts('Line Item ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Line Item table.'),
      'add' => '6.20',
      'input_attrs' => [
        'label' => ts('Line Item'),
      ],
      'entity_reference' => [
        'entity' => 'LineItem',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'metadata' => [
      'title' => ts('Order Completion metadata'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Data pertaining to order completion'),
      'add' => '6.20',
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
    ],
  ],
  'created_date' => [
    'title' => ts('Order Completion Metadata Created Date'),
    'sql_type' => 'timestamp',
    'input_type' => 'Select Date',
    'description' => ts('When was this data created'),
    'add' => '4.6',
    'default' => 'CURRENT_TIMESTAMP',
    'input_attrs' => [
      'format_type' => 'mailing',
    ],
  ],
  'modified_date' => [
    'title' => ts('Order Completion Metadata Modified Date'),
    'sql_type' => 'datetime',
    'input_type' => 'Select Date',
    'required' => TRUE,
    'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'readonly' => TRUE,
    'description' => ts('When was this item modified'),
    'add' => '3.3',
  ],

];

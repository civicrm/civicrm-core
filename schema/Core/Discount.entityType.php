<?php

return [
  'name' => 'Discount',
  'table' => 'civicrm_discount',
  'class' => 'CRM_Core_DAO_Discount',
  'getInfo' => fn() => [
    'title' => ts('Discount'),
    'title_plural' => ts('Discounts'),
    'description' => ts('Stores discounts for events on the basis of date'),
    'log' => TRUE,
    'add' => '2.1',
  ],
  'getIndices' => fn() => [
    'index_entity' => [
      'fields' => [
        'entity_table' => TRUE,
        'entity_id' => TRUE,
      ],
      'add' => '2.1',
    ],
    'index_entity_option_id' => [
      'fields' => [
        'entity_table' => TRUE,
        'entity_id' => TRUE,
        'price_set_id' => TRUE,
      ],
      'add' => '2.1',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Discount ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('primary key'),
      'add' => '2.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'entity_table' => [
      'title' => ts('Entity Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('physical tablename for entity being joined to discount, e.g. civicrm_event'),
      'add' => '2.1',
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_Discount', 'entityTables'],
      ],
    ],
    'entity_id' => [
      'title' => ts('Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to entity table specified in entity_table column.'),
      'add' => '2.1',
      'entity_reference' => [
        'dynamic_entity' => 'entity_table',
        'key' => 'id',
      ],
    ],
    'price_set_id' => [
      'title' => ts('Price Set ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('FK to civicrm_price_set'),
      'add' => '4.3',
      'unique_name' => 'participant_discount_name',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Price Set'),
        'control_field' => 'entity_id',
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_price_set',
        'key_column' => 'id',
        'label_column' => 'title',
        'condition_provider' => ['CRM_Core_BAO_Discount', 'alterPriceSetOptions'],
      ],
      'entity_reference' => [
        'entity' => 'PriceSet',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'start_date' => [
      'title' => ts('Discount Start Date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => ts('Date when discount starts.'),
      'add' => '2.1',
    ],
    'end_date' => [
      'title' => ts('Discount End Date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => ts('Date when discount ends.'),
      'add' => '2.1',
    ],
  ],
];

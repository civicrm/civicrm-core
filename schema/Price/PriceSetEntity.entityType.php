<?php

return [
  'name' => 'PriceSetEntity',
  'table' => 'civicrm_price_set_entity',
  'class' => 'CRM_Price_DAO_PriceSetEntity',
  'getInfo' => fn() => [
    'title' => ts('Price Set Entity'),
    'title_plural' => ts('Price Set Entities'),
    'description' => ts('Price Set Entities'),
    'log' => TRUE,
    'add' => '1.8',
  ],
  'getIndices' => fn() => [
    'UI_entity' => [
      'fields' => [
        'entity_table' => TRUE,
        'entity_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '1.8',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Price Set Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Price Set Entity'),
      'add' => '1.8',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'entity_table' => [
      'title' => ts('Entity Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Table which uses this price set'),
      'add' => '1.8',
      'pseudoconstant' => [
        'callback' => ['CRM_Price_BAO_PriceSet', 'entityTables'],
      ],
    ],
    'entity_id' => [
      'title' => ts('Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Item in table'),
      'add' => '1.8',
      'entity_reference' => [
        'dynamic_entity' => 'entity_table',
        'key' => 'id',
      ],
    ],
    'price_set_id' => [
      'title' => ts('Price Set ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('price set being used'),
      'add' => '1.8',
      'input_attrs' => [
        'label' => ts('Price Set'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_price_set',
        'key_column' => 'id',
        'label_column' => 'title',
      ],
      'entity_reference' => [
        'entity' => 'PriceSet',
        'key' => 'id',
      ],
    ],
  ],
];

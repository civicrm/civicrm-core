<?php

return [
  'name' => 'Cart',
  'table' => 'civicrm_event_carts',
  'class' => 'CRM_Event_Cart_DAO_Cart',
  'getInfo' => fn() => [
    'title' => ts('Cart'),
    'title_plural' => ts('Carts'),
    'description' => ts('Event Carts'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Cart ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Cart ID'),
      'add' => '4.1',
      'unique_name' => 'cart_id',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'user_id' => [
      'title' => ts('Created By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to civicrm_contact who created this cart'),
      'add' => '4.1',
      'input_attrs' => [
        'label' => ts('Created By'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'completed' => [
      'title' => ts('Complete?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'add' => '4.1',
      'default' => FALSE,
    ],
  ],
];

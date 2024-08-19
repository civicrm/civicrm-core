<?php

return [
  'name' => 'EventInCart',
  'table' => 'civicrm_events_in_carts',
  'class' => 'CRM_Event_Cart_DAO_EventInCart',
  'getInfo' => fn() => [
    'title' => ts('Event In Cart'),
    'title_plural' => ts('Event In Carts'),
    'description' => ts('Events in Cart'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Event In Cart'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Event In Cart ID'),
      'add' => '4.1',
      'unique_name' => 'event_in_cart_id',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'event_id' => [
      'title' => ts('Event ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Event ID'),
      'add' => '4.1',
      'input_attrs' => [
        'label' => ts('Event'),
      ],
      'entity_reference' => [
        'entity' => 'Event',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'event_cart_id' => [
      'title' => ts('Event Cart ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Event Cart ID'),
      'add' => '4.1',
      'input_attrs' => [
        'label' => ts('Event In Cart'),
      ],
      'entity_reference' => [
        'entity' => 'Cart',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
